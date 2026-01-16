<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107143541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE team_membership (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, left_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, player_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_B826A04099E6F5DF (player_id), INDEX IDX_B826A040296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A04099E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE team_membership ADD CONSTRAINT FK_B826A040296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY `FK_98197A65296CD8AE`');
        $this->addSql('DROP INDEX IDX_98197A65296CD8AE ON player');
        $this->addSql('ALTER TABLE player ADD deleted_at DATETIME DEFAULT NULL, DROP team_id');
        $this->addSql('ALTER TABLE team ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE team_join_request ADD deleted_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team_membership DROP FOREIGN KEY FK_B826A04099E6F5DF');
        $this->addSql('ALTER TABLE team_membership DROP FOREIGN KEY FK_B826A040296CD8AE');
        $this->addSql('DROP TABLE team_membership');
        $this->addSql('ALTER TABLE player ADD team_id INT DEFAULT NULL, DROP deleted_at');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT `FK_98197A65296CD8AE` FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_98197A65296CD8AE ON player (team_id)');
        $this->addSql('ALTER TABLE team DROP deleted_at');
        $this->addSql('ALTER TABLE team_join_request DROP deleted_at');
    }
}
