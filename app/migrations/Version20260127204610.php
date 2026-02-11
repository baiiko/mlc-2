<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127204610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase_map_result (id INT AUTO_INCREMENT NOT NULL, map_uid VARCHAR(50) NOT NULL, winner VARCHAR(100) NOT NULL, results JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, INDEX IDX_47554F9E99091188 (phase_id), UNIQUE INDEX unique_phase_map_winner (map_uid, winner, phase_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE phase_map_result ADD CONSTRAINT FK_47554F9E99091188 FOREIGN KEY (phase_id) REFERENCES phase (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase_map_result DROP FOREIGN KEY FK_47554F9E99091188');
        $this->addSql('DROP TABLE phase_map_result');
    }
}
