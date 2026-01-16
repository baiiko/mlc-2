<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107142305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE team_join_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, player_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_E1B4E93D99E6F5DF (player_id), INDEX IDX_E1B4E93D296CD8AE (team_id), UNIQUE INDEX unique_pending_request (player_id, team_id, status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE team_join_request ADD CONSTRAINT FK_E1B4E93D99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE team_join_request ADD CONSTRAINT FK_E1B4E93D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_join_request DROP FOREIGN KEY FK_E1B4E93D99E6F5DF');
        $this->addSql('ALTER TABLE team_join_request DROP FOREIGN KEY FK_E1B4E93D296CD8AE');
        $this->addSql('DROP TABLE team_join_request');
    }
}
