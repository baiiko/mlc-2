<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107105502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player ADD password VARCHAR(255) DEFAULT NULL, ADD activation_token VARCHAR(64) DEFAULT NULL, ADD token_expires_at DATETIME DEFAULT NULL, ADD is_active TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65B1B4826B ON player (activation_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_98197A65B1B4826B ON player');
        $this->addSql('ALTER TABLE player DROP password, DROP activation_token, DROP token_expires_at, DROP is_active');
    }
}
