<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126215506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_map_record ON map_record');
        $this->addSql('ALTER TABLE map_record CHANGE game_mode game_mode INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_map_record ON map_record (map_uid, player_login, laps, game_mode, round_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_map_record ON map_record');
        $this->addSql('ALTER TABLE map_record CHANGE game_mode game_mode INT DEFAULT 3 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_map_record ON map_record (map_uid, player_login, laps)');
    }
}
