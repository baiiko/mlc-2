<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on round_map.author to speed up author filter';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_round_map_author ON round_map (author)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_round_map_author ON round_map');
    }
}
