<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\MatchSettingsGeneratorService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
class PhaseCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly RoundRepositoryInterface $roundRepository,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Phase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Phase')
            ->setEntityLabelInPlural('Phases')
            ->setDefaultSort(['round.id' => 'DESC', 'startAt' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateMatchSettings = Action::new('generateMatchSettings', 'Générer MatchSettings', 'fa fa-file-code')
            ->linkToCrudAction('generateMatchSettings')
            ->displayIf(fn (Phase $phase) => $phase->getType()?->isPlayable() ?? false);

        $generateAllMatchSettings = Action::new('generateAllMatchSettings', 'Générer tous les MatchSettings', 'fa fa-files-o')
            ->linkToCrudAction('generateAllMatchSettings')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $generateMatchSettings)
            ->add(Crud::PAGE_INDEX, $generateAllMatchSettings)
            ->add(Crud::PAGE_DETAIL, $generateMatchSettings);
    }

    public function generateMatchSettings(AdminContext $context): Response
    {
        /** @var Phase $phase */
        $phase = $context->getEntity()->getInstance();

        try {
            $filePath = $this->matchSettingsGenerator->saveForPhase($phase);
            $this->addFlash('success', $this->translator->trans('admin.phase.matchsettings_generated', [
                '%filename%' => basename($filePath),
            ]));
        } catch (\Exception $e) {
            $this->addFlash('danger', $this->translator->trans('admin.phase.matchsettings_error', [
                '%error%' => $e->getMessage(),
            ]));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function generateAllMatchSettings(): Response
    {
        $round = $this->roundRepository->findCurrentOrUpcoming();

        if (!$round) {
            $this->addFlash('warning', $this->translator->trans('admin.round.no_active_round'));

            $url = $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl();

            return $this->redirect($url);
        }

        try {
            $generated = $this->matchSettingsGenerator->generateAllForRound($round);
            $this->addFlash('success', $this->translator->trans('admin.round.matchsettings_all_generated', [
                '%files%' => implode(', ', array_keys($generated)),
            ]));
        } catch (\Exception $e) {
            $this->addFlash('danger', $this->translator->trans('admin.phase.matchsettings_error', [
                '%error%' => $e->getMessage(),
            ]));
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('round', 'Manche')
            ->autocomplete();

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Inscriptions' => PhaseType::Registration,
                'Qualifications' => PhaseType::Qualification,
                'Demi-finale' => PhaseType::SemiFinal,
                'Finale' => PhaseType::Final,
            ])
            ->formatValue(fn ($value) => $value?->getLabel());

        yield DateTimeField::new('startAt', 'Début');

        yield DateTimeField::new('endAt', 'Fin');

        yield IntegerField::new('laps', 'Tours')
            ->setHelp('Laisser vide pour utiliser la valeur par défaut selon le type de phase')
            ->hideOnIndex();

        yield IntegerField::new('timeLimit', 'Time Limit (ms)')
            ->setHelp('Laisser vide pour utiliser la valeur par défaut')
            ->hideOnIndex();

        yield IntegerField::new('finishTimeout', 'Finish Timeout (ms)')
            ->setHelp('Laisser vide pour utiliser la valeur par défaut')
            ->hideOnIndex();

        yield IntegerField::new('warmupDuration', 'Warmup (s)')
            ->setHelp('Laisser vide pour utiliser la valeur par défaut')
            ->hideOnIndex();

        yield IntegerField::new('serverCount', 'Serveurs')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();
    }
}
