<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801230139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_listing_details (id INT AUTO_INCREMENT NOT NULL, surface NUMERIC(10, 2) DEFAULT NULL, rooms INT DEFAULT NULL, bedrooms INT DEFAULT NULL, bathrooms INT DEFAULT NULL, floor INT DEFAULT NULL, product_id INT NOT NULL, rental_period_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6AA370074584665A (product_id), INDEX IDX_6AA37007536B7DD1 (rental_period_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE rental_period (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE property_listing_details ADD CONSTRAINT FK_6AA370074584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE property_listing_details ADD CONSTRAINT FK_6AA37007536B7DD1 FOREIGN KEY (rental_period_id) REFERENCES rental_period (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_listing_details DROP FOREIGN KEY FK_6AA370074584665A');
        $this->addSql('ALTER TABLE property_listing_details DROP FOREIGN KEY FK_6AA37007536B7DD1');
        $this->addSql('DROP TABLE property_listing_details');
        $this->addSql('DROP TABLE rental_period');
    }
}
