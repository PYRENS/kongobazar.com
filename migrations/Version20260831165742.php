<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260831165742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE top_vendor_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, display_mode VARCHAR(20) DEFAULT \'auto\' NOT NULL, display_count INT DEFAULT 4 NOT NULL, exclude_pro TINYINT DEFAULT 0 NOT NULL, exclude_boutique TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE top_vendor_targeted_seller (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, setting_id INT NOT NULL, seller_id INT NOT NULL, INDEX IDX_644FDD6EEE35BD72 (setting_id), INDEX IDX_644FDD6E8DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE top_vendor_targeted_seller ADD CONSTRAINT FK_644FDD6EEE35BD72 FOREIGN KEY (setting_id) REFERENCES top_vendor_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE top_vendor_targeted_seller ADD CONSTRAINT FK_644FDD6E8DE820D9 FOREIGN KEY (seller_id) REFERENCES seller_profile (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE top_vendor_targeted_seller DROP FOREIGN KEY FK_644FDD6EEE35BD72');
        $this->addSql('ALTER TABLE top_vendor_targeted_seller DROP FOREIGN KEY FK_644FDD6E8DE820D9');
        $this->addSql('DROP TABLE top_vendor_setting');
        $this->addSql('DROP TABLE top_vendor_targeted_seller');
    }
}
