<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\PhaseServer;
use App\Domain\Championship\Entity\PhaseType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SERVER_ADMIN')]
class PhaseServerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhaseServer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Serveur de phase')
            ->setEntityLabelInPlural('Serveurs de phases')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('phase', 'Phase')
            ->autocomplete()
            ->setQueryBuilder(function (QueryBuilder $qb) {
                return $qb
                    ->join('entity.round', 'r')
                    ->andWhere('entity.type != :registration')
                    ->andWhere('r.isActive = :active')
                    ->setParameter('registration', PhaseType::Registration)
                    ->setParameter('active', true);
            });

        yield AssociationField::new('server', 'Serveur')
            ->autocomplete();

        yield IntegerField::new('serverNumber', 'Numéro')
            ->setHelp('Numéro du serveur pour cette phase (ex: 1, 2, 3...)');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }
}
