<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719221629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE license (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, pdf_file_name VARCHAR(255) DEFAULT NULL, last_alert_sent_at DATETIME DEFAULT NULL, last_alert_days_before INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, seller_profile_id INT NOT NULL, UNIQUE INDEX UNIQ_5768F4193A1E3D2 (seller_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE license ADD CONSTRAINT FK_5768F4193A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE license DROP FOREIGN KEY FK_5768F4193A1E3D2');
        $this->addSql('DROP TABLE license');
    }
}
