<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on round_map.name to speed up search';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_round_map_name ON round_map (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_round_map_name ON round_map');
    }
}
