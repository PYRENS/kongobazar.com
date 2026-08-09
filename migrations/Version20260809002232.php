<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260809002232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle_listing_details ADD trim_level VARCHAR(100) DEFAULT NULL, ADD constructor_version VARCHAR(150) DEFAULT NULL, ADD first_registration_month INT DEFAULT NULL, ADD first_registration_year INT DEFAULT NULL, ADD vehicle_body_type VARCHAR(50) DEFAULT NULL, ADD color VARCHAR(50) DEFAULT NULL, ADD power_din INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vehicle_listing_details DROP trim_level, DROP constructor_version, DROP first_registration_month, DROP first_registration_year, DROP vehicle_body_type, DROP color, DROP power_din');
    }
}
