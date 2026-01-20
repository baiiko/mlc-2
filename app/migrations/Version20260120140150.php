<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120140150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase_map (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uid VARCHAR(50) DEFAULT NULL, position INT NOT NULL, is_surprise TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, INDEX IDX_1B83C02B99091188 (phase_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE phase_map ADD CONSTRAINT FK_1B83C02B99091188 FOREIGN KEY (phase_id) REFERENCES phase (id)');
        $this->addSql('ALTER TABLE round DROP map_name, DROP map_uid');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase_map DROP FOREIGN KEY FK_1B83C02B99091188');
        $this->addSql('DROP TABLE phase_map');
        $this->addSql('ALTER TABLE round ADD map_name VARCHAR(255) DEFAULT NULL, ADD map_uid VARCHAR(50) DEFAULT NULL');
    }
}
