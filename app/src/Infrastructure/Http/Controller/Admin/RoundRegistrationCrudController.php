<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\RoundRegistration;
use App\Infrastructure\Service\TmColorParser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoundRegistrationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RoundRegistration::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Inscription')
            ->setEntityLabelInPlural('Inscriptions')
            ->setSearchFields(['player.pseudo', 'player.login'])
            ->setDefaultSort(['registeredAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('round', 'Manche')
            ->autocomplete();

        yield TextField::new('playerPseudo', 'Joueur')
            ->hideOnForm()
            ->formatValue(fn($value, RoundRegistration $entity) => TmColorParser::toHtml($entity->getPlayer()->getPseudo()))
            ->renderAsHtml();

        yield AssociationField::new('player', 'Joueur')
            ->onlyOnForms()
            ->autocomplete();

        yield TextField::new('teamTag', 'Équipe')
            ->hideOnForm()
            ->formatValue(fn($value, RoundRegistration $entity) => $entity->getTeam() ? TmColorParser::toHtml($entity->getTeam()->getTag()) : '-')
            ->renderAsHtml();

        yield AssociationField::new('team', 'Équipe')
            ->onlyOnForms()
            ->autocomplete();

        yield BooleanField::new('availableSemiFinal1', 'Demi 1')
            ->renderAsSwitch(false);

        yield BooleanField::new('availableSemiFinal2', 'Demi 2')
            ->renderAsSwitch(false);

        yield BooleanField::new('availableFinal', 'Finale')
            ->renderAsSwitch(false);

        yield DateTimeField::new('registeredAt', 'Inscrit le');
    }
}
