<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_message table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, server_id INT NOT NULL, player_login VARCHAR(100) DEFAULT NULL, player_pseudo VARCHAR(100) DEFAULT NULL, content LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_DB0F4B86 (server_id), INDEX idx_chat_message_server_created (server_id, created_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_DB0F4B861844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_DB0F4B861844E6B7');
        $this->addSql('DROP TABLE chat_message');
    }
}
