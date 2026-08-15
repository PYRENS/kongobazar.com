<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815121722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ad_zone_setting (id INT AUTO_INCREMENT NOT NULL, zone_key VARCHAR(50) NOT NULL, mode VARCHAR(20) DEFAULT \'random\' NOT NULL, fixed_advertisement_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_CD080255A0DF9565 (zone_key), INDEX IDX_CD0802556BA14EED (fixed_advertisement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ad_zone_setting ADD CONSTRAINT FK_CD0802556BA14EED FOREIGN KEY (fixed_advertisement_id) REFERENCES advertisement (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE advertisement ADD impression_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE category_attribute RENAME INDEX idx_cat_attr_characteristic TO IDX_3D1A3DCBDEE9D12B');
        $this->addSql('ALTER TABLE characteristic_option RENAME INDEX idx_char_opt_characteristic TO IDX_9CE6315EDEE9D12B');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ad_zone_setting DROP FOREIGN KEY FK_CD0802556BA14EED');
        $this->addSql('DROP TABLE ad_zone_setting');
        $this->addSql('ALTER TABLE advertisement DROP impression_count');
        $this->addSql('ALTER TABLE category_attribute RENAME INDEX idx_3d1a3dcbdee9d12b TO IDX_cat_attr_characteristic');
        $this->addSql('ALTER TABLE characteristic_option RENAME INDEX idx_9ce6315edee9d12b TO IDX_char_opt_characteristic');
    }
}
