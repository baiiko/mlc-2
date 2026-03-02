<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Player\Entity\Player;
use App\Domain\Team\Entity\Team;
use App\Infrastructure\Service\TmColorParser;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TeamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Team::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Équipe')
            ->setEntityLabelInPlural('Équipes')
            ->setSearchFields(['tag', 'fullName'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('tag')
            ->setHelp('Abréviation de l\'équipe (max 10 caractères)')
            ->formatValue(fn (string $value): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($value) . '</span>')
            ->renderAsHtml();

        yield TextField::new('fullName', 'Nom complet')
            ->formatValue(fn (string $value): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($value) . '</span>')
            ->renderAsHtml();

        // Champ pour affichage (index/detail) avec couleurs TM
        yield TextField::new('creatorName', 'Créateur')
            ->hideOnForm()
            ->formatValue(fn ($value, Team $entity): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($entity->getCreator()->getPseudo()) . '</span>')
            ->renderAsHtml();

        // Champ pour édition (formulaire) - pseudo sans couleurs TM
        yield AssociationField::new('creator', 'Créateur')
            ->onlyOnForms()
            ->autocomplete();

        yield TextField::new('activeMembersCount', 'Membres actifs')
            ->hideOnForm()
            ->formatValue(function ($value, Team $entity): string {
                $members = $entity->getActiveMembers();
                $count = \count($members);

                if ($count === 0) {
                    return '0';
                }

                $names = array_map(
                    fn (Player $player): string => '<span class="tm-pseudo">' . TmColorParser::toHtml($player->getPseudo()) . '</span>',
                    $members
                );

                return \sprintf('%d (%s)', $count, implode(', ', $names));
            })
            ->renderAsHtml();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifiée le')
            ->hideOnForm();
    }
}
