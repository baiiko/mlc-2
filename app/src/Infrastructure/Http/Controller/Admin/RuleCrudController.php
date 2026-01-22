<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Application\Content\Service\RuleService;
use App\Domain\Content\Entity\Rule;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RuleCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RuleService $ruleService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Rule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.rule.singular')
            ->setEntityLabelInPlural('admin.rule.plural')
            ->setSearchFields(['content'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::EDIT, Action::DELETE)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::NEW]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'admin.rule.version')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'admin.rule.date')
            ->hideOnForm();

        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextareaField::new('content', 'admin.rule.content')
                ->renderAsHtml();
        } else {
            yield TextEditorField::new('content', 'admin.rule.content')
                ->hideOnIndex();
        }
    }

    public function createEntity(string $entityFqcn): Rule
    {
        return $this->ruleService->createNewRule();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->ruleService->saveAndArchivePrevious($entityInstance);
    }
}
