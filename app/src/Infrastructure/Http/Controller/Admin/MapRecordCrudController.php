<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Admin;

use App\Domain\Championship\Entity\MapRecord;
use App\Domain\Championship\Enum\GameMode;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class MapRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MapRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('admin.map_record.singular')
            ->setEntityLabelInPlural('admin.map_record.plural')
            ->setSearchFields(['playerLogin', 'player', 'mapUid'])
            ->setDefaultSort(['roundId' => 'DESC', 'mapUid' => 'ASC', 'time' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('roundId', 'admin.map_record.round_id'))
            ->add(TextFilter::new('playerLogin', 'admin.map_record.player_login'))
            ->add(TextFilter::new('mapUid', 'admin.map_record.map_uid'))
            ->add(NumericFilter::new('laps', 'admin.map_record.laps'))
            ->add(ChoiceFilter::new('gameMode', 'admin.map_record.game_mode')
                ->setChoices([
                    'Rounds' => GameMode::Rounds,
                    'TimeAttack' => GameMode::TimeAttack,
                    'Team' => GameMode::Team,
                    'Laps' => GameMode::Laps,
                    'Stunts' => GameMode::Stunts,
                    'Cup' => GameMode::Cup,
                ]));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield IntegerField::new('roundId', 'admin.map_record.round_id');

        yield TextField::new('playerLogin', 'admin.map_record.player_login');

        yield TextField::new('player', 'admin.map_record.player_pseudo');

        yield TextField::new('mapUid', 'admin.map_record.map_uid');

        yield IntegerField::new('laps', 'admin.map_record.laps');

        yield Field::new('time', 'admin.map_record.time')
            ->formatValue(fn ($value, ?MapRecord $entity): ?string => $entity?->formatTime());

        yield Field::new('gameMode', 'admin.map_record.game_mode')
            ->formatValue(fn ($value, ?MapRecord $entity): ?string => $entity?->getGameMode()->name);

        yield DateTimeField::new('createdAt', 'admin.map_record.created_at')
            ->hideOnForm();
    }
}
