<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Player\Service\RoleManagementServiceInterface;
use App\Domain\Player\Entity\Player;
use App\Infrastructure\Service\TmColorParser;
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
use Symfony\Component\HttpFoundation\Response;

class PlayerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RoleManagementServiceInterface $roleManagementService,
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

        return $actions
            ->disable(Action::NEW)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) use ($canEdit) {
                return $action->displayIf(fn (Player $player) => $canEdit($player));
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) use ($canDelete) {
                return $action->displayIf(fn (Player $player) => $canDelete($player));
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) use ($canEdit) {
                return $action->displayIf(fn (Player $player) => $canEdit($player));
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) use ($canDelete) {
                return $action->displayIf(fn (Player $player) => $canDelete($player));
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

    public function updateEntity(\Doctrine\ORM\EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Player && !$this->canEditPlayer($entityInstance)) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cet utilisateur.');

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('login')
            ->setFormTypeOption('disabled', true)
            ->setHelp('Le login ne peut pas être modifié');

        yield TextField::new('pseudo')
            ->formatValue(fn ($value) => '<span class="tm-pseudo">' . TmColorParser::toHtml($value) . '</span>')
            ->renderAsHtml();

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
