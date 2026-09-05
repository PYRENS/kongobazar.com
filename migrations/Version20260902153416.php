<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902153416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE most_viewed_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, include_kbz TINYINT DEFAULT 1 NOT NULL, include_store TINYINT DEFAULT 1 NOT NULL, include_pro TINYINT DEFAULT 1 NOT NULL, include_individual TINYINT DEFAULT 1 NOT NULL, display_count INT DEFAULT 20 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE most_viewed_setting');
    }
}
