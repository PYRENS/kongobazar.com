<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720152102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(60) NOT NULL, hex_code VARCHAR(7) DEFAULT NULL, UNIQUE INDEX UNIQ_665648E95E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_variant (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, sku VARCHAR(100) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, product_id INT NOT NULL, color_id INT DEFAULT NULL, size_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_209AA41DF9038C4 (sku), INDEX IDX_209AA41D4584665A (product_id), INDEX IDX_209AA41D7ADA1FB5 (color_id), INDEX IDX_209AA41D498DA827 (size_id), UNIQUE INDEX UNIQ_VARIANT_PRODUCT_COLOR_SIZE (product_id, color_id, size_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE size (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(20) NOT NULL, type VARCHAR(20) NOT NULL, position INT DEFAULT NULL, UNIQUE INDEX UNIQ_SIZE_TYPE_NAME (type, name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE product_variant ADD CONSTRAINT FK_209AA41D498DA827 FOREIGN KEY (size_id) REFERENCES size (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D4584665A');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D7ADA1FB5');
        $this->addSql('ALTER TABLE product_variant DROP FOREIGN KEY FK_209AA41D498DA827');
        $this->addSql('DROP TABLE color');
        $this->addSql('DROP TABLE product_variant');
        $this->addSql('DROP TABLE size');
    }
}
