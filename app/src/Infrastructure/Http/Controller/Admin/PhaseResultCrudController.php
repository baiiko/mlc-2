<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\PhaseResult;
use App\Domain\Championship\Entity\PhaseType;
use App\Infrastructure\Service\TmColorParser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PhaseResultCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhaseResult::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Résultat')
            ->setEntityLabelInPlural('Résultats')
            ->setSearchFields(['player.pseudo', 'player.login'])
            ->setDefaultSort(['phase.id' => 'DESC', 'position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('phase', 'Phase');

        yield TextField::new('player.pseudo', 'Joueur')
            ->hideOnForm()
            ->formatValue(fn ($value, PhaseResult $entity): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($entity->getPlayer()->getPseudo()) . '</span>')
            ->renderAsHtml();

        yield AssociationField::new('player', 'Joueur')
            ->onlyOnForms()
            ->autocomplete();

        yield IntegerField::new('position', 'Position');

        yield TextField::new('formattedTime', 'Temps')
            ->hideOnForm();

        yield IntegerField::new('time', 'Temps (ms)')
            ->onlyOnForms();

        yield BooleanField::new('isQualified', 'Qualifié');

        yield ChoiceField::new('qualifiedTo', 'Qualifié vers')
            ->setChoices([
                'Demi-finale' => PhaseType::SemiFinal,
                'Finale' => PhaseType::Final,
            ])
            ->allowMultipleChoices(false)
            ->renderExpanded(false)
            ->setRequired(false);

        yield IntegerField::new('serverNumber', 'Serveur');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();
    }
}
