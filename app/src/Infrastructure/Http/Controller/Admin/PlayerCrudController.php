<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Player\Service\ChangeLoginServiceInterface;
use App\Application\Player\Service\RoleManagementServiceInterface;
use App\Domain\Championship\Repository\MapRecordRepositoryInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Service\TmColorParser;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class PlayerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RoleManagementServiceInterface $roleManagementService,
        private readonly MapRecordRepositoryInterface $mapRecordRepository,
        private readonly ChangeLoginServiceInterface $changeLoginService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Player::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Joueur')
            ->setEntityLabelInPlural('Joueurs')
            ->setSearchFields(['login', 'pseudo', 'email', 'discord'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $canEdit = fn (Player $player): bool => $this->canEditPlayer($player);
        $canDelete = fn (Player $player): bool => !$this->roleManagementService->hasProtectedRole($player)
            && $this->canEditPlayer($player);

        $deleteRecords = Action::new('deleteRecords', 'Supprimer les records', 'fa fa-trash')
            ->linkToCrudAction('deleteRecords')
            ->setCssClass('btn btn-warning');

        return $actions
            ->disable(Action::NEW)
            ->add(Crud::PAGE_DETAIL, $deleteRecords)
            ->add(Crud::PAGE_INDEX, $deleteRecords)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) use ($canEdit): Action {
                return $action->displayIf(fn (Player $player): bool => $canEdit($player));
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) use ($canDelete): Action {
                return $action->displayIf(fn (Player $player): bool => $canDelete($player));
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) use ($canEdit): Action {
                return $action->displayIf(fn (Player $player): bool => $canEdit($player));
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) use ($canDelete): Action {
                return $action->displayIf(fn (Player $player): bool => $canDelete($player));
            });
    }

    public function delete(AdminContext $context): Response
    {
        /** @var Player $player */
        $player = $context->getEntity()->getInstance();

        if ($this->roleManagementService->hasProtectedRole($player)) {
            $this->addFlash('danger', 'Impossible de supprimer un administrateur.');

            return $this->redirect($context->getReferrer() ?? $this->generateUrl('admin'));
        }

        return parent::delete($context);
    }

    public function deleteRecords(AdminContext $context): Response
    {
        /** @var Player $player */
        $player = $context->getEntity()->getInstance();

        $deletedCount = $this->mapRecordRepository->deleteByPlayerLogin($player->getLogin());

        $this->addFlash('success', \sprintf('%d record(s) supprimé(s) pour %s.', $deletedCount, $player->getLogin()));

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Player && !$this->canEditPlayer($entityInstance)) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cet utilisateur.');

            return;
        }

        if ($entityInstance instanceof Player) {
            $uow = $entityManager->getUnitOfWork();
            $uow->computeChangeSet($entityManager->getClassMetadata(Player::class), $entityInstance);
            $changeSet = $uow->getEntityChangeSet($entityInstance);

            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                if (isset($changeSet['pseudo'])) {
                    $entityInstance->setPseudo($changeSet['pseudo'][0]);
                    $this->addFlash('danger', 'Seul un super administrateur peut modifier le pseudo.');

                    return;
                }

                if (isset($changeSet['login'])) {
                    $entityInstance->setLogin($changeSet['login'][0]);
                    $this->addFlash('danger', 'Seul un super administrateur peut modifier le login.');

                    return;
                }
            }

            if (isset($changeSet['login'])) {
                $oldLogin = $changeSet['login'][0];
                $newLogin = $changeSet['login'][1];

                // Revert login before delegating to service (it will set it again)
                $entityInstance->setLogin($oldLogin);

                $result = $this->changeLoginService->changeLogin($entityInstance, $newLogin);

                if (!$result['success']) {
                    $this->addFlash('danger', $result['error']);

                    return;
                }

                $this->addFlash('info', \sprintf(
                    '%d record(s) et %d phase(s) mis à jour avec le nouveau login.',
                    $result['updatedRecords'],
                    $result['updatedPhases'],
                ));
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

        $loginField = TextField::new('login');

        if (!$isSuperAdmin) {
            $loginField
                ->setFormTypeOption('disabled', true)
                ->setHelp('Seul un super administrateur peut modifier le login');
        }

        $pseudoField = TextField::new('pseudo')
            ->formatValue(fn (string $value): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($value) . '</span>')
            ->renderAsHtml();

        if (!$isSuperAdmin) {
            $pseudoField
                ->setFormTypeOption('disabled', true)
                ->setHelp('Seul un super administrateur peut modifier le pseudo');
        }

        yield $loginField;

        yield $pseudoField;

        yield EmailField::new('email');

        yield TextField::new('discord')
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Actif')
            ->renderAsSwitch(true);

        yield BooleanField::new('newsletter', 'Newsletter')
            ->renderAsSwitch(true)
            ->hideOnIndex();

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices($this->getAssignableRoles())
            ->allowMultipleChoices()
            ->renderExpanded()
            ->hideOnIndex();

        yield ArrayField::new('roles', 'Rôles')
            ->onlyOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();
    }

    private function canEditPlayer(Player $player): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof Player) {
            return false;
        }

        return $this->roleManagementService->canEditPlayer($currentUser, $player);
    }

    private function getAssignableRoles(): array
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof Player) {
            return [];
        }

        return $this->roleManagementService->getAssignableRoles($currentUser);
    }
}
