<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828213021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seo_override (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(30) NOT NULL, entity_id INT DEFAULT NULL, page_key VARCHAR(100) DEFAULT NULL, admin_label VARCHAR(200) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, og_title VARCHAR(255) DEFAULT NULL, og_description LONGTEXT DEFAULT NULL, og_image_name VARCHAR(255) DEFAULT NULL, no_index TINYINT DEFAULT 0 NOT NULL, no_follow TINYINT DEFAULT 0 NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX idx_seo_entity (entity_type, entity_id), INDEX idx_seo_page_key (page_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE seo_override');
    }
}
