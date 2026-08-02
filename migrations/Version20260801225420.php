<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801225420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fuel_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, active TINYINT DEFAULT 1 NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE license_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE motorcycle_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_compatibility (id INT AUTO_INCREMENT NOT NULL, oem_code VARCHAR(100) DEFAULT NULL, part_listing_details_id INT NOT NULL, brand_id INT NOT NULL, INDEX IDX_83F1FF70DB9A574A (part_listing_details_id), INDEX IDX_83F1FF7044F5D008 (brand_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE part_listing_details (id INT AUTO_INCREMENT NOT NULL, oem_codes JSON DEFAULT NULL, ean VARCHAR(100) DEFAULT NULL, manufacturer_ref VARCHAR(100) DEFAULT NULL, product_id INT NOT NULL, UNIQUE INDEX UNIQ_5B0110DA4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vehicle_engine (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, power_cv INT DEFAULT NULL, power_kw INT DEFAULT NULL, displacement_cc INT DEFAULT NULL, month_start VARCHAR(2) DEFAULT NULL, year_start INT DEFAULT NULL, month_end VARCHAR(2) DEFAULT NULL, year_end INT DEFAULT NULL, brand_name_cache VARCHAR(255) DEFAULT NULL, model_name_cache VARCHAR(255) DEFAULT NULL, variant_name_cache VARCHAR(255) DEFAULT NULL, period_label VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, variant_id INT DEFAULT NULL, model_id INT DEFAULT NULL, fuel_type_id INT DEFAULT NULL, INDEX IDX_41761B5E3B69A9AF (variant_id), INDEX IDX_41761B5E7975B7E7 (model_id), INDEX IDX_41761B5E6A70FE35 (fuel_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vehicle_listing_details (id INT AUTO_INCREMENT NOT NULL, model_year INT DEFAULT NULL, mileage INT DEFAULT NULL, seats INT DEFAULT NULL, steering_side VARCHAR(20) DEFAULT NULL, transmission VARCHAR(20) DEFAULT NULL, product_id INT NOT NULL, vehicle_engine_id INT DEFAULT NULL, license_type_id INT DEFAULT NULL, motorcycle_type_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_49D735994584665A (product_id), INDEX IDX_49D735991033D958 (vehicle_engine_id), INDEX IDX_49D735992C55C7C8 (license_type_id), INDEX IDX_49D735991A19CD7B (motorcycle_type_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vehicle_model (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, name2 VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, brand_id INT NOT NULL, INDEX IDX_B53AF23544F5D008 (brand_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vehicle_variant (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, month_begin VARCHAR(2) NOT NULL, year_begin INT NOT NULL, month_end VARCHAR(2) DEFAULT NULL, year_end INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, model_id INT NOT NULL, INDEX IDX_EE30E2C27975B7E7 (model_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE part_compatibility ADD CONSTRAINT FK_83F1FF70DB9A574A FOREIGN KEY (part_listing_details_id) REFERENCES part_listing_details (id)');
        $this->addSql('ALTER TABLE part_compatibility ADD CONSTRAINT FK_83F1FF7044F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE part_listing_details ADD CONSTRAINT FK_5B0110DA4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE vehicle_engine ADD CONSTRAINT FK_41761B5E3B69A9AF FOREIGN KEY (variant_id) REFERENCES vehicle_variant (id)');
        $this->addSql('ALTER TABLE vehicle_engine ADD CONSTRAINT FK_41761B5E7975B7E7 FOREIGN KEY (model_id) REFERENCES vehicle_model (id)');
        $this->addSql('ALTER TABLE vehicle_engine ADD CONSTRAINT FK_41761B5E6A70FE35 FOREIGN KEY (fuel_type_id) REFERENCES fuel_type (id)');
        $this->addSql('ALTER TABLE vehicle_listing_details ADD CONSTRAINT FK_49D735994584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE vehicle_listing_details ADD CONSTRAINT FK_49D735991033D958 FOREIGN KEY (vehicle_engine_id) REFERENCES vehicle_engine (id)');
        $this->addSql('ALTER TABLE vehicle_listing_details ADD CONSTRAINT FK_49D735992C55C7C8 FOREIGN KEY (license_type_id) REFERENCES license_type (id)');
        $this->addSql('ALTER TABLE vehicle_listing_details ADD CONSTRAINT FK_49D735991A19CD7B FOREIGN KEY (motorcycle_type_id) REFERENCES motorcycle_type (id)');
        $this->addSql('ALTER TABLE vehicle_model ADD CONSTRAINT FK_B53AF23544F5D008 FOREIGN KEY (brand_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE vehicle_variant ADD CONSTRAINT FK_EE30E2C27975B7E7 FOREIGN KEY (model_id) REFERENCES vehicle_model (id)');
        $this->addSql('ALTER TABLE brand ADD type JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE part_compatibility DROP FOREIGN KEY FK_83F1FF70DB9A574A');
        $this->addSql('ALTER TABLE part_compatibility DROP FOREIGN KEY FK_83F1FF7044F5D008');
        $this->addSql('ALTER TABLE part_listing_details DROP FOREIGN KEY FK_5B0110DA4584665A');
        $this->addSql('ALTER TABLE vehicle_engine DROP FOREIGN KEY FK_41761B5E3B69A9AF');
        $this->addSql('ALTER TABLE vehicle_engine DROP FOREIGN KEY FK_41761B5E7975B7E7');
        $this->addSql('ALTER TABLE vehicle_engine DROP FOREIGN KEY FK_41761B5E6A70FE35');
        $this->addSql('ALTER TABLE vehicle_listing_details DROP FOREIGN KEY FK_49D735994584665A');
        $this->addSql('ALTER TABLE vehicle_listing_details DROP FOREIGN KEY FK_49D735991033D958');
        $this->addSql('ALTER TABLE vehicle_listing_details DROP FOREIGN KEY FK_49D735992C55C7C8');
        $this->addSql('ALTER TABLE vehicle_listing_details DROP FOREIGN KEY FK_49D735991A19CD7B');
        $this->addSql('ALTER TABLE vehicle_model DROP FOREIGN KEY FK_B53AF23544F5D008');
        $this->addSql('ALTER TABLE vehicle_variant DROP FOREIGN KEY FK_EE30E2C27975B7E7');
        $this->addSql('DROP TABLE fuel_type');
        $this->addSql('DROP TABLE license_type');
        $this->addSql('DROP TABLE motorcycle_type');
        $this->addSql('DROP TABLE part_compatibility');
        $this->addSql('DROP TABLE part_listing_details');
        $this->addSql('DROP TABLE vehicle_engine');
        $this->addSql('DROP TABLE vehicle_listing_details');
        $this->addSql('DROP TABLE vehicle_model');
        $this->addSql('DROP TABLE vehicle_variant');
        $this->addSql('ALTER TABLE brand DROP type');
    }
}
