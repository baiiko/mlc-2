<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\Phase;
use App\Domain\Championship\Entity\PhaseType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PhaseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Phase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Phase')
            ->setEntityLabelInPlural('Phases')
            ->setDefaultSort(['round.id' => 'DESC', 'startAt' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('round', 'Manche')
            ->autocomplete();

        yield ChoiceField::new('type', 'Type')
            ->setChoices([
                'Inscriptions' => PhaseType::Registration,
                'Qualifications' => PhaseType::Qualification,
                'Demi-finale 1' => PhaseType::SemiFinal1,
                'Demi-finale 2' => PhaseType::SemiFinal2,
                'Finale' => PhaseType::Final,
            ])
            ->formatValue(fn ($value) => $value?->getLabel());

        yield DateTimeField::new('startAt', 'Début');

        yield DateTimeField::new('endAt', 'Fin');

        yield IntegerField::new('serverCount', 'Serveurs')
            ->hideOnForm();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();
    }
}
