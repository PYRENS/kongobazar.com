<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260807220730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE part_catalog_attribute_value (id INT AUTO_INCREMENT NOT NULL, text_value LONGTEXT DEFAULT NULL, number_value NUMERIC(12, 3) DEFAULT NULL, boolean_value TINYINT DEFAULT NULL, part_catalog_entry_id INT NOT NULL, category_attribute_id INT NOT NULL, category_attribute_option_id INT DEFAULT NULL, INDEX IDX_A4F8C33A5DF83C9 (part_catalog_entry_id), INDEX IDX_A4F8C33A6C310D68 (category_attribute_id), INDEX IDX_A4F8C33A91B687D0 (category_attribute_option_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_catalog_brand_compatibility (id INT AUTO_INCREMENT NOT NULL, part_catalog_entry_id INT NOT NULL, brand_id INT NOT NULL, INDEX IDX_8377A30D5DF83C9 (part_catalog_entry_id), INDEX IDX_8377A30D44F5D008 (brand_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_catalog_engine_compatibility (id INT AUTO_INCREMENT NOT NULL, part_catalog_entry_id INT NOT NULL, vehicle_engine_id INT NOT NULL, INDEX IDX_8B3821E65DF83C9 (part_catalog_entry_id), INDEX IDX_8B3821E61033D958 (vehicle_engine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_catalog_entry (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(200) NOT NULL, ean VARCHAR(100) DEFAULT NULL, manufacturer_ref VARCHAR(100) DEFAULT NULL, status VARCHAR(20) DEFAULT \'pending_review\' NOT NULL, created_at DATETIME NOT NULL, category_id INT NOT NULL, brand_id INT DEFAULT NULL, created_by_seller_id INT DEFAULT NULL, INDEX IDX_620F2BDD12469DE2 (category_id), INDEX IDX_620F2BDD44F5D008 (brand_id), INDEX IDX_620F2BDDD7ED25B4 (created_by_seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_catalog_image (id INT AUTO_INCREMENT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, updated_at DATETIME DEFAULT NULL, part_catalog_entry_id INT NOT NULL, INDEX IDX_8C13B2F25DF83C9 (part_catalog_entry_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_catalog_oem_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) NOT NULL, part_catalog_entry_id INT NOT NULL, brand_id INT DEFAULT NULL, INDEX IDX_52F66FAD5DF83C9 (part_catalog_entry_id), INDEX IDX_52F66FAD44F5D008 (brand_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE part_catalog_attribute_value ADD CONSTRAINT FK_A4F8C33A5DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_attribute_value ADD CONSTRAINT FK_A4F8C33A6C310D68 FOREIGN KEY (category_attribute_id) REFERENCES category_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_attribute_value ADD CONSTRAINT FK_A4F8C33A91B687D0 FOREIGN KEY (category_attribute_option_id) REFERENCES category_attribute_option (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE part_catalog_brand_compatibility ADD CONSTRAINT FK_8377A30D5DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_brand_compatibility ADD CONSTRAINT FK_8377A30D44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE part_catalog_engine_compatibility ADD CONSTRAINT FK_8B3821E65DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_engine_compatibility ADD CONSTRAINT FK_8B3821E61033D958 FOREIGN KEY (vehicle_engine_id) REFERENCES vehicle_engine (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_entry ADD CONSTRAINT FK_620F2BDD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE part_catalog_entry ADD CONSTRAINT FK_620F2BDD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE part_catalog_entry ADD CONSTRAINT FK_620F2BDDD7ED25B4 FOREIGN KEY (created_by_seller_id) REFERENCES seller_profile (id)');
        $this->addSql('ALTER TABLE part_catalog_image ADD CONSTRAINT FK_8C13B2F25DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_oem_code ADD CONSTRAINT FK_52F66FAD5DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_catalog_oem_code ADD CONSTRAINT FK_52F66FAD44F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE part_listing_details ADD part_catalog_entry_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE part_listing_details ADD CONSTRAINT FK_5B0110DA5DF83C9 FOREIGN KEY (part_catalog_entry_id) REFERENCES part_catalog_entry (id)');
        $this->addSql('CREATE INDEX IDX_5B0110DA5DF83C9 ON part_listing_details (part_catalog_entry_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE part_catalog_attribute_value DROP FOREIGN KEY FK_A4F8C33A5DF83C9');
        $this->addSql('ALTER TABLE part_catalog_attribute_value DROP FOREIGN KEY FK_A4F8C33A6C310D68');
        $this->addSql('ALTER TABLE part_catalog_attribute_value DROP FOREIGN KEY FK_A4F8C33A91B687D0');
        $this->addSql('ALTER TABLE part_catalog_brand_compatibility DROP FOREIGN KEY FK_8377A30D5DF83C9');
        $this->addSql('ALTER TABLE part_catalog_brand_compatibility DROP FOREIGN KEY FK_8377A30D44F5D008');
        $this->addSql('ALTER TABLE part_catalog_engine_compatibility DROP FOREIGN KEY FK_8B3821E65DF83C9');
        $this->addSql('ALTER TABLE part_catalog_engine_compatibility DROP FOREIGN KEY FK_8B3821E61033D958');
        $this->addSql('ALTER TABLE part_catalog_entry DROP FOREIGN KEY FK_620F2BDD12469DE2');
        $this->addSql('ALTER TABLE part_catalog_entry DROP FOREIGN KEY FK_620F2BDD44F5D008');
        $this->addSql('ALTER TABLE part_catalog_entry DROP FOREIGN KEY FK_620F2BDDD7ED25B4');
        $this->addSql('ALTER TABLE part_catalog_image DROP FOREIGN KEY FK_8C13B2F25DF83C9');
        $this->addSql('ALTER TABLE part_catalog_oem_code DROP FOREIGN KEY FK_52F66FAD5DF83C9');
        $this->addSql('ALTER TABLE part_catalog_oem_code DROP FOREIGN KEY FK_52F66FAD44F5D008');
        $this->addSql('DROP TABLE part_catalog_attribute_value');
        $this->addSql('DROP TABLE part_catalog_brand_compatibility');
        $this->addSql('DROP TABLE part_catalog_engine_compatibility');
        $this->addSql('DROP TABLE part_catalog_entry');
        $this->addSql('DROP TABLE part_catalog_image');
        $this->addSql('DROP TABLE part_catalog_oem_code');
        $this->addSql('ALTER TABLE part_listing_details DROP FOREIGN KEY FK_5B0110DA5DF83C9');
        $this->addSql('DROP INDEX IDX_5B0110DA5DF83C9 ON part_listing_details');
        $this->addSql('ALTER TABLE part_listing_details DROP part_catalog_entry_id');
    }
}
