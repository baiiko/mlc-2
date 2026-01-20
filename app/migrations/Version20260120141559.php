<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120141559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE phase_server (id INT AUTO_INCREMENT NOT NULL, server_number INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, phase_id INT NOT NULL, server_id INT NOT NULL, INDEX IDX_E4BAE36C99091188 (phase_id), INDEX IDX_E4BAE36C1844E6B7 (server_id), UNIQUE INDEX phase_server_unique (phase_id, server_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE server (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, login VARCHAR(100) NOT NULL, ip VARCHAR(45) DEFAULT NULL, port INT DEFAULT NULL, max_players INT DEFAULT 32 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5A6DD5F6AA08CB10 (login), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE phase_server ADD CONSTRAINT FK_E4BAE36C99091188 FOREIGN KEY (phase_id) REFERENCES phase (id)');
        $this->addSql('ALTER TABLE phase_server ADD CONSTRAINT FK_E4BAE36C1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
        $this->addSql('ALTER TABLE phase_map ADD author VARCHAR(100) DEFAULT NULL, ADD environment VARCHAR(50) DEFAULT NULL, ADD author_time INT DEFAULT NULL, ADD gold_time INT DEFAULT NULL, ADD silver_time INT DEFAULT NULL, ADD bronze_time INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase_server DROP FOREIGN KEY FK_E4BAE36C99091188');
        $this->addSql('ALTER TABLE phase_server DROP FOREIGN KEY FK_E4BAE36C1844E6B7');
        $this->addSql('DROP TABLE phase_server');
        $this->addSql('DROP TABLE server');
        $this->addSql('ALTER TABLE phase_map DROP author, DROP environment, DROP author_time, DROP gold_time, DROP silver_time, DROP bronze_time');
    }
}
