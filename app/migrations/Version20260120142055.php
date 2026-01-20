<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120142055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE round_map (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, uid VARCHAR(50) DEFAULT NULL, author VARCHAR(100) DEFAULT NULL, environment VARCHAR(50) DEFAULT NULL, author_time INT DEFAULT NULL, gold_time INT DEFAULT NULL, silver_time INT DEFAULT NULL, bronze_time INT DEFAULT NULL, position INT NOT NULL, is_surprise TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, round_id INT NOT NULL, INDEX IDX_2E09619CA6005CA0 (round_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE round_map ADD CONSTRAINT FK_2E09619CA6005CA0 FOREIGN KEY (round_id) REFERENCES round (id)');
        $this->addSql('ALTER TABLE phase_map DROP FOREIGN KEY `FK_1B83C02B99091188`');
        $this->addSql('DROP TABLE phase_map');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase_map (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, uid VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, position INT NOT NULL, is_surprise TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, author VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, environment VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, author_time INT DEFAULT NULL, gold_time INT DEFAULT NULL, silver_time INT DEFAULT NULL, bronze_time INT DEFAULT NULL, INDEX IDX_1B83C02B99091188 (phase_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE phase_map ADD CONSTRAINT `FK_1B83C02B99091188` FOREIGN KEY (phase_id) REFERENCES phase (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE round_map DROP FOREIGN KEY FK_2E09619CA6005CA0');
        $this->addSql('DROP TABLE round_map');
    }
}
