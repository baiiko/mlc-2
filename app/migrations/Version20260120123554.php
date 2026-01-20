<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120123554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, server_count INT DEFAULT 1 NOT NULL, qualify_to_final_count INT DEFAULT 0 NOT NULL, qualify_to_semi_count INT DEFAULT 0 NOT NULL, qualify_from_semi_count INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, round_id INT NOT NULL, INDEX IDX_B1BDD6CBA6005CA0 (round_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE phase_result (id INT AUTO_INCREMENT NOT NULL, time INT NOT NULL, position INT NOT NULL, is_qualified TINYINT DEFAULT 0 NOT NULL, qualified_to VARCHAR(20) DEFAULT NULL, server_number INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, player_id INT NOT NULL, registration_id INT NOT NULL, INDEX IDX_ADBDF78999091188 (phase_id), INDEX IDX_ADBDF78999E6F5DF (player_id), INDEX IDX_ADBDF789833D8F43 (registration_id), UNIQUE INDEX unique_phase_player (phase_id, player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE round (id INT AUTO_INCREMENT NOT NULL, number INT NOT NULL, name VARCHAR(100) NOT NULL, map_name VARCHAR(255) DEFAULT NULL, map_uid VARCHAR(50) DEFAULT NULL, registration_start_at DATETIME NOT NULL, registration_end_at DATETIME NOT NULL, is_active TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, season_id INT NOT NULL, INDEX IDX_C5EEEA344EC001D1 (season_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE round_registration (id INT AUTO_INCREMENT NOT NULL, registered_at DATETIME NOT NULL, round_id INT NOT NULL, player_id INT NOT NULL, team_id INT DEFAULT NULL, INDEX IDX_B644E47AA6005CA0 (round_id), INDEX IDX_B644E47A99E6F5DF (player_id), INDEX IDX_B644E47A296CD8AE (team_id), UNIQUE INDEX unique_round_player (round_id, player_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, start_date DATETIME NOT NULL, end_date DATETIME DEFAULT NULL, is_active TINYINT DEFAULT 0 NOT NULL, min_players_for_team_ranking INT DEFAULT 4 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F0E45BA9989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE phase ADD CONSTRAINT FK_B1BDD6CBA6005CA0 FOREIGN KEY (round_id) REFERENCES round (id)');
        $this->addSql('ALTER TABLE phase_result ADD CONSTRAINT FK_ADBDF78999091188 FOREIGN KEY (phase_id) REFERENCES phase (id)');
        $this->addSql('ALTER TABLE phase_result ADD CONSTRAINT FK_ADBDF78999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE phase_result ADD CONSTRAINT FK_ADBDF789833D8F43 FOREIGN KEY (registration_id) REFERENCES round_registration (id)');
        $this->addSql('ALTER TABLE round ADD CONSTRAINT FK_C5EEEA344EC001D1 FOREIGN KEY (season_id) REFERENCES season (id)');
        $this->addSql('ALTER TABLE round_registration ADD CONSTRAINT FK_B644E47AA6005CA0 FOREIGN KEY (round_id) REFERENCES round (id)');
        $this->addSql('ALTER TABLE round_registration ADD CONSTRAINT FK_B644E47A99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE round_registration ADD CONSTRAINT FK_B644E47A296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase DROP FOREIGN KEY FK_B1BDD6CBA6005CA0');
        $this->addSql('ALTER TABLE phase_result DROP FOREIGN KEY FK_ADBDF78999091188');
        $this->addSql('ALTER TABLE phase_result DROP FOREIGN KEY FK_ADBDF78999E6F5DF');
        $this->addSql('ALTER TABLE phase_result DROP FOREIGN KEY FK_ADBDF789833D8F43');
        $this->addSql('ALTER TABLE round DROP FOREIGN KEY FK_C5EEEA344EC001D1');
        $this->addSql('ALTER TABLE round_registration DROP FOREIGN KEY FK_B644E47AA6005CA0');
        $this->addSql('ALTER TABLE round_registration DROP FOREIGN KEY FK_B644E47A99E6F5DF');
        $this->addSql('ALTER TABLE round_registration DROP FOREIGN KEY FK_B644E47A296CD8AE');
        $this->addSql('DROP TABLE phase');
        $this->addSql('DROP TABLE phase_result');
        $this->addSql('DROP TABLE round');
        $this->addSql('DROP TABLE round_registration');
        $this->addSql('DROP TABLE season');
    }
}
