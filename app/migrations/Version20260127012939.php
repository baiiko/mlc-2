<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127012939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase_server DROP FOREIGN KEY `FK_E4BAE36C1844E6B7`');
        $this->addSql('ALTER TABLE phase_server DROP FOREIGN KEY `FK_E4BAE36C99091188`');
        $this->addSql('DROP TABLE phase_server');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase_server (id INT AUTO_INCREMENT NOT NULL, server_number INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, server_id INT NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_E4BAE36C1844E6B7 (server_id), INDEX IDX_E4BAE36C99091188 (phase_id), UNIQUE INDEX phase_server_unique (phase_id, server_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE phase_server ADD CONSTRAINT `FK_E4BAE36C1844E6B7` FOREIGN KEY (server_id) REFERENCES server (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE phase_server ADD CONSTRAINT `FK_E4BAE36C99091188` FOREIGN KEY (phase_id) REFERENCES phase (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
