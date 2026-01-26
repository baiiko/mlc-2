<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260125231018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add MatchSettings configuration fields to Phase entity (laps, timeLimit, finishTimeout, warmupDuration)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase ADD laps INT DEFAULT NULL, ADD time_limit INT DEFAULT NULL, ADD finish_timeout INT DEFAULT NULL, ADD warmup_duration INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase DROP laps, DROP time_limit, DROP finish_timeout, DROP warmup_duration');
    }
}
