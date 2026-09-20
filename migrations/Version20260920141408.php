<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260920141408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign ADD banner_enabled TINYINT DEFAULT 1 NOT NULL, ADD texts_enabled TINYINT DEFAULT 1 NOT NULL, ADD theme_top_enabled TINYINT DEFAULT 1 NOT NULL, ADD theme_enabled TINYINT DEFAULT 1 NOT NULL, ADD priority_products_enabled TINYINT DEFAULT 1 NOT NULL, ADD priority_sellers_enabled TINYINT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign DROP banner_enabled, DROP texts_enabled, DROP theme_top_enabled, DROP theme_enabled, DROP priority_products_enabled, DROP priority_sellers_enabled');
    }
}
