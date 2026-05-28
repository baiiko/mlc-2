<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\MatchSettingsGeneratorService;
use App\Application\Championship\Service\ServerCommandService;
use App\Domain\Championship\Entity\PhaseType;
use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\Server;
use App\Domain\Championship\Enum\ServerPurpose;
use App\Domain\Championship\Repository\PhaseRepositoryInterface;
use App\Domain\Championship\Repository\RoundRepositoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SERVER_ADMIN')]
class ServerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ServerCommandService $serverCommandService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly PhaseRepositoryInterface $phaseRepository,
        private readonly RoundRepositoryInterface $roundRepository,
        private readonly MatchSettingsGeneratorService $matchSettingsGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Server::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.server.singular')
            ->setEntityLabelInPlural('admin.server.plural')
            ->setSearchFields(['name', 'login', 'ip'])
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $competitionOnly = static fn (Server $server): bool => $server->getPurpose() === ServerPurpose::Competition;
        $freeOnly = static fn (Server $server): bool => $server->getPurpose() === ServerPurpose::Free;

        $setWarmUp = Action::new('setWarmUp', 'admin.server.action.warmup', 'fa fa-clock')
            ->linkToCrudAction('setWarmUp')
            ->displayIf($competitionOnly);

        $restartMap = Action::new('restartMap', 'admin.server.action.restart', 'fa fa-redo')
            ->linkToCrudAction('restartMap')
            ->displayIf($competitionOnly);

        $skipMap = Action::new('skipMap', 'admin.server.action.skip', 'fa fa-forward')
            ->linkToCrudAction('skipMap')
            ->displayIf($competitionOnly);

        $setPhaseQualification = Action::new('setPhaseQualification', 'admin.server.action.phase_qualification', 'fa fa-flag-checkered')
            ->linkToCrudAction('setPhaseQualification')
            ->displayIf($competitionOnly);

        $openChat = Action::new('openChat', 'admin.server.action.chat', 'fa fa-comments')
            ->linkToRoute('admin_server_chat', fn (Server $server): array => ['id' => $server->getId()]);

        $rebootServer = Action::new('rebootServer', 'admin.server.action.reboot', 'fa fa-power-off')
            ->linkToCrudAction('rebootServer')
            ->addCssClass('text-danger');

        $reloadMatchSettings = Action::new('reloadMatchSettings', 'admin.server.action.reload_match_settings', 'fa fa-rotate')
            ->linkToCrudAction('reloadMatchSettings')
            ->displayIf($competitionOnly);

        $generateFree = Action::new('generateFree', 'admin.server.action.generate_free', 'fa fa-file-code')
            ->linkToCrudAction('generateFree')
            ->displayIf($freeOnly);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $setWarmUp)
            ->add(Crud::PAGE_DETAIL, $restartMap)
            ->add(Crud::PAGE_DETAIL, $skipMap)
            ->add(Crud::PAGE_DETAIL, $setPhaseQualification)
            ->add(Crud::PAGE_DETAIL, $reloadMatchSettings)
            ->add(Crud::PAGE_DETAIL, $generateFree)
            ->add(Crud::PAGE_DETAIL, $openChat)
            ->add(Crud::PAGE_DETAIL, $rebootServer)
            ->add(Crud::PAGE_INDEX, $setWarmUp)
            ->add(Crud::PAGE_INDEX, $restartMap)
            ->add(Crud::PAGE_INDEX, $skipMap)
            ->add(Crud::PAGE_INDEX, $setPhaseQualification)
            ->add(Crud::PAGE_INDEX, $reloadMatchSettings)
            ->add(Crud::PAGE_INDEX, $generateFree)
            ->add(Crud::PAGE_INDEX, $openChat)
            ->add(Crud::PAGE_INDEX, $rebootServer);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'admin.server.name');

        yield TextField::new('login', 'admin.server.login')
            ->setHelp('admin.server.login_help');

        yield TextField::new('adminLogin', 'admin.server.admin_login')
            ->setHelp('admin.server.admin_login_help')
            ->hideOnIndex();

        yield Field::new('plainPassword', 'admin.server.password')
            ->setFormType(PasswordType::class)
            ->setFormTypeOptions([
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->onlyOnForms()
            ->setHelp('admin.server.password_help');

        yield TextField::new('relayLogin', 'admin.server.relay_login')
            ->setHelp('admin.server.relay_login_help')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('ip', 'admin.server.ip');

        yield IntegerField::new('port', 'admin.server.port');

        yield IntegerField::new('maxPlayers', 'admin.server.max_players')
            ->setHelp('admin.server.max_players_help');

        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('activeLabel', 'admin.server.active');
        } else {
            yield BooleanField::new('isActive', 'admin.server.active');
        }

        yield ChoiceField::new('purpose', 'admin.server.purpose')
            ->setChoices(array_combine(
                array_map(static fn (ServerPurpose $p): string => $p->getLabel(), ServerPurpose::cases()),
                ServerPurpose::cases(),
            ))
            ->renderAsBadges([
                ServerPurpose::Competition->value => 'primary',
                ServerPurpose::Free->value => 'success',
            ])
            ->setHelp('admin.server.purpose_help')
            ->formatValue(static fn ($value): string => $value instanceof ServerPurpose ? $value->getLabel() : (string) $value);

        yield TextField::new('connectionString', 'admin.server.connection')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'admin.server.created_at')
            ->hideOnForm();
    }

    public function setWarmUp(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $result = $this->serverCommandService->toggleWarmUp($server);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function restartMap(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $result = $this->serverCommandService->restartMap($server);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function skipMap(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $result = $this->serverCommandService->skipMap($server);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function setPhaseQualification(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $result = $this->serverCommandService->sendChatMessage($server, '/phase qualification');

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function reloadMatchSettings(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $phaseName = $this->resolvePhaseName();

        $result = $this->serverCommandService->reloadMatchSettings($server, $phaseName);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function rebootServer(AdminContext $context): Response
    {
        /** @var Server $server */
        $server = $context->getEntity()->getInstance();

        $result = $this->serverCommandService->rebootServer($server);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('danger', $result['message']);
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    public function generateFree(): Response
    {
        $round = $this->roundRepository->findCurrentOrUpcoming();

        if (!$round instanceof Round) {
            $this->addFlash('warning', 'Aucune manche courante ou à venir trouvée.');
        } else {
            try {
                $path = $this->matchSettingsGenerator->saveFree($round);
                $this->addFlash('success', \sprintf('free.xml généré (%s).', $path));
            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Erreur génération free.xml : ' . $e->getMessage());
            }
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    private function resolvePhaseName(): string
    {
        $activePhase = $this->phaseRepository->findActivePlayablePhase();

        if ($activePhase !== null) {
            return match ($activePhase->getType()) {
                PhaseType::SemiFinal => 'demi',
                PhaseType::Final => 'finale',
                default => 'qualification',
            };
        }

        // No competitive phase active right now.
        // → training if we're inside a round window (between two phases of the same round)
        // → free otherwise (between rounds)
        $round = $this->roundRepository->findCurrentOrUpcoming();

        if ($round === null) {
            return 'free';
        }

        $now = new \DateTimeImmutable();
        $starts = [];
        $ends = [];

        foreach ($round->getPhases() as $phase) {
            if ($phase->getStartAt() !== null) {
                $starts[] = $phase->getStartAt();
            }

            if ($phase->getEndAt() !== null) {
                $ends[] = $phase->getEndAt();
            }
        }

        if ($starts === [] || $ends === []) {
            return 'free';
        }

        $firstStart = min($starts);
        $lastEnd = max($ends);

        return ($now >= $firstStart && $now <= $lastEnd) ? 'training' : 'free';
    }
}
