<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803173545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category_attribute (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, data_type VARCHAR(20) NOT NULL, unit VARCHAR(20) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, nullable TINYINT DEFAULT 1 NOT NULL, filterable TINYINT DEFAULT 0 NOT NULL, show_on_card TINYINT DEFAULT 0 NOT NULL, group_tag VARCHAR(50) DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_3D1A3DCB12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE category_attribute_option (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, position INT DEFAULT 0 NOT NULL, category_attribute_id INT NOT NULL, INDEX IDX_FD5A976E6C310D68 (category_attribute_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_attribute_value (id INT AUTO_INCREMENT NOT NULL, text_value LONGTEXT DEFAULT NULL, number_value NUMERIC(12, 3) DEFAULT NULL, boolean_value TINYINT DEFAULT NULL, product_id INT NOT NULL, category_attribute_id INT NOT NULL, category_attribute_option_id INT DEFAULT NULL, INDEX IDX_CCC4BE1F4584665A (product_id), INDEX IDX_CCC4BE1F6C310D68 (category_attribute_id), INDEX IDX_CCC4BE1F91B687D0 (category_attribute_option_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE category_attribute ADD CONSTRAINT FK_3D1A3DCB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_attribute_option ADD CONSTRAINT FK_FD5A976E6C310D68 FOREIGN KEY (category_attribute_id) REFERENCES category_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT FK_CCC4BE1F4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT FK_CCC4BE1F6C310D68 FOREIGN KEY (category_attribute_id) REFERENCES category_attribute (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_attribute_value ADD CONSTRAINT FK_CCC4BE1F91B687D0 FOREIGN KEY (category_attribute_option_id) REFERENCES category_attribute_option (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_attribute DROP FOREIGN KEY FK_3D1A3DCB12469DE2');
        $this->addSql('ALTER TABLE category_attribute_option DROP FOREIGN KEY FK_FD5A976E6C310D68');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1F4584665A');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1F6C310D68');
        $this->addSql('ALTER TABLE product_attribute_value DROP FOREIGN KEY FK_CCC4BE1F91B687D0');
        $this->addSql('DROP TABLE category_attribute');
        $this->addSql('DROP TABLE category_attribute_option');
        $this->addSql('DROP TABLE product_attribute_value');
    }
}
