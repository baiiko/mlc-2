<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120143235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase DROP qualify_to_final_count, DROP qualify_to_semi_count, DROP qualify_from_semi_count');
        $this->addSql('ALTER TABLE round ADD qualify_to_final_count INT DEFAULT 0 NOT NULL, ADD qualify_to_semi_count INT DEFAULT 0 NOT NULL, ADD qualify_from_semi_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phase ADD qualify_to_final_count INT DEFAULT 0 NOT NULL, ADD qualify_to_semi_count INT DEFAULT 0 NOT NULL, ADD qualify_from_semi_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE round DROP qualify_to_final_count, DROP qualify_to_semi_count, DROP qualify_from_semi_count');
    }
}
