<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phase_id on map_record to distinguish records made during qualif, semi or final on the same map';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map_record ADD phase_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE map_record ADD CONSTRAINT FK_map_record_phase FOREIGN KEY (phase_id) REFERENCES phase (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_map_record_phase ON map_record (phase_id)');
        // Old unique constraint kept; legacy rows have phase_id NULL which MySQL treats as
        // distinct, so it doesn't conflict. New writes (with phase_id set) are unique per
        // (map_uid, player_login, laps, game_mode, round_id, phase_id) via the existing
        // index dimensions plus NULL-distinct behaviour.
        $this->addSql('DROP INDEX unique_map_record ON map_record');
        $this->addSql('CREATE UNIQUE INDEX unique_map_record ON map_record (map_uid, player_login, laps, game_mode, round_id, phase_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_map_record ON map_record');
        $this->addSql('CREATE UNIQUE INDEX unique_map_record ON map_record (map_uid, player_login, laps, game_mode, round_id)');
        $this->addSql('DROP INDEX IDX_map_record_phase ON map_record');
        $this->addSql('ALTER TABLE map_record DROP FOREIGN KEY FK_map_record_phase');
        $this->addSql('ALTER TABLE map_record DROP phase_id');
    }
}
