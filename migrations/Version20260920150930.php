<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260920150930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign ADD batch_banners_enabled TINYINT DEFAULT 1 NOT NULL, ADD batch_banner_every INT DEFAULT 1 NOT NULL, ADD batch_banner_mode VARCHAR(10) DEFAULT \'random\' NOT NULL, ADD batch_banner_ad_ids JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign DROP batch_banners_enabled, DROP batch_banner_every, DROP batch_banner_mode, DROP batch_banner_ad_ids');
    }
}
