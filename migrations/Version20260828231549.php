<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828231549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE share_event (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(30) NOT NULL, entity_id INT DEFAULT NULL, page_key VARCHAR(100) DEFAULT NULL, admin_label VARCHAR(200) DEFAULT NULL, platform VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX idx_share_entity (entity_type, entity_id), INDEX idx_share_page_key (page_key), INDEX idx_share_platform (platform), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE share_event');
    }
}
