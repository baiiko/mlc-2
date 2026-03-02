<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Championship\Service\ServerCommandService;
use App\Domain\Championship\Entity\Server;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
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
        $setWarmUp = Action::new('setWarmUp', 'admin.server.action.warmup', 'fa fa-clock')
            ->linkToCrudAction('setWarmUp');

        $restartMap = Action::new('restartMap', 'admin.server.action.restart', 'fa fa-redo')
            ->linkToCrudAction('restartMap');

        $skipMap = Action::new('skipMap', 'admin.server.action.skip', 'fa fa-forward')
            ->linkToCrudAction('skipMap');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $setWarmUp)
            ->add(Crud::PAGE_DETAIL, $restartMap)
            ->add(Crud::PAGE_DETAIL, $skipMap)
            ->add(Crud::PAGE_INDEX, $setWarmUp)
            ->add(Crud::PAGE_INDEX, $restartMap)
            ->add(Crud::PAGE_INDEX, $skipMap);
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
}
