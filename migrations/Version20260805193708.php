<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805193708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE part_engine_compatibility (id INT AUTO_INCREMENT NOT NULL, part_listing_details_id INT NOT NULL, vehicle_engine_id INT NOT NULL, INDEX IDX_C63C49D1DB9A574A (part_listing_details_id), INDEX IDX_C63C49D11033D958 (vehicle_engine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE part_engine_compatibility ADD CONSTRAINT FK_C63C49D1DB9A574A FOREIGN KEY (part_listing_details_id) REFERENCES part_listing_details (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE part_engine_compatibility ADD CONSTRAINT FK_C63C49D11033D958 FOREIGN KEY (vehicle_engine_id) REFERENCES vehicle_engine (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE part_engine_compatibility DROP FOREIGN KEY FK_C63C49D1DB9A574A');
        $this->addSql('ALTER TABLE part_engine_compatibility DROP FOREIGN KEY FK_C63C49D11033D958');
        $this->addSql('DROP TABLE part_engine_compatibility');
    }
}
