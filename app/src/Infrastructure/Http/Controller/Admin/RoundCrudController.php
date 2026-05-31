<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\CalculateFinalRankingService;
use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoundCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly CalculateFinalRankingService $calculateFinalRankingService,
        private readonly RoundRepositoryInterface $roundRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Round::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Manche')
            ->setEntityLabelInPlural('Manches')
            ->setSearchFields(['name'])
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $closeRound = Action::new('closeRound', 'Clôturer la manche', 'fa fa-lock')
            ->linkToCrudAction('closeRound')
            ->displayIf(static fn (Round $round): bool => $round->isActive());

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $closeRound)
            ->add(Crud::PAGE_DETAIL, $closeRound);
    }

    public function closeRound(AdminContext $context): Response
    {
        /** @var Round $round */
        $round = $context->getEntity()->getInstance();

        $computed = 0;
        $errors = [];

        foreach ($round->getPhases() as $phase) {
            if (!$phase instanceof Phase || $phase->getType() !== PhaseType::Final) {
                continue;
            }

            try {
                $this->calculateFinalRankingService->calculate($phase);
                ++$computed;
            } catch (\Throwable $e) {
                $errors[] = \sprintf('Phase finale #%d : %s', $phase->getId(), $e->getMessage());
            }
        }

        $round->setIsActive(false);
        $this->roundRepository->save($round);

        if ($errors === []) {
            $this->addFlash('success', \sprintf(
                'Manche clôturée. Classement final calculé sur %d phase(s). is_active = false.',
                $computed,
            ));
        } else {
            $this->addFlash('warning', \sprintf(
                'Manche clôturée (is_active = false) mais erreurs sur le classement : %s',
                implode(' | ', $errors),
            ));
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

        yield AssociationField::new('season', 'Saison')
            ->autocomplete();

        yield IntegerField::new('number', 'Numéro');

        yield TextField::new('name', 'Nom');

        yield DateField::new('startDate', 'Date de départ')
            ->setHelp('Choisir une date : les phases seront générées automatiquement à partir du mercredi suivant')
            ->onlyWhenCreating();

        yield BooleanField::new('isActive', 'Active');

        yield IntegerField::new('qualifyToFinalCount', 'Qualifiés finale')
            ->setHelp('Nombre de joueurs qualifiés directement en finale depuis les qualifications')
            ->hideOnIndex();

        yield IntegerField::new('qualifyToSemiCount', 'Qualifiés demi')
            ->setHelp('Nombre de joueurs qualifiés en demi-finale depuis les qualifications')
            ->hideOnIndex();

        yield IntegerField::new('qualifyFromSemiCount', 'Qualifiés depuis demi')
            ->setHelp('Nombre de joueurs qualifiés de la demi-finale vers la finale')
            ->hideOnIndex();

        yield IntegerField::new('registrationsCount', 'Inscrits')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();
    }
}
