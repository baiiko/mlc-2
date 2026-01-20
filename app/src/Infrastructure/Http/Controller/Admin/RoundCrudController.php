<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\Round;
use App\Domain\Championship\Entity\Season;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class RoundCrudController extends AbstractCrudController
{
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
