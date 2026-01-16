<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107141327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add pseudo column, defaulting to login for existing players
        $this->addSql('ALTER TABLE player ADD pseudo VARCHAR(50) DEFAULT NULL');
        $this->addSql('UPDATE player SET pseudo = login WHERE pseudo IS NULL');
        $this->addSql('ALTER TABLE player MODIFY pseudo VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP pseudo');
    }
}
