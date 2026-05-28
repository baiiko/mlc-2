<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add purpose column on server to distinguish competition and free-mode servers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE server ADD purpose VARCHAR(30) DEFAULT 'competition' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE server DROP purpose');
    }
}
