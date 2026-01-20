<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\Season;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SeasonCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Season::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Saison')
            ->setEntityLabelInPlural('Saisons')
            ->setSearchFields(['name', 'slug'])
            ->setDefaultSort(['startDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nom');

        yield SlugField::new('slug')
            ->setTargetFieldName('name')
            ->hideOnIndex();

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();

        yield DateTimeField::new('startDate', 'Date de début');

        yield DateTimeField::new('endDate', 'Date de fin');

        yield BooleanField::new('isActive', 'Active');

        yield IntegerField::new('minPlayersForTeamRanking', 'Min joueurs équipe')
            ->setHelp('Nombre minimum de joueurs pour le classement équipe');

        yield IntegerField::new('roundsCount', 'Manches')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();
    }
}
