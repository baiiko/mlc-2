<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add karma JSON column to round_map';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE round_map ADD karma JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE round_map DROP karma');
    }
}
