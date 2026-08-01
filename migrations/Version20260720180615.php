<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720180615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE relay_delivery (id INT AUTO_INCREMENT NOT NULL, pickup_code VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, refusal_reason LONGTEXT DEFAULT NULL, scanned_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, order_id INT NOT NULL, relay_profile_id INT NOT NULL, UNIQUE INDEX UNIQ_80701C9F95888FA (pickup_code), UNIQUE INDEX UNIQ_80701C9F8D9F6D38 (order_id), INDEX IDX_80701C9FCD5CD3D1 (relay_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE relay_delivery ADD CONSTRAINT FK_80701C9F8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE relay_delivery ADD CONSTRAINT FK_80701C9FCD5CD3D1 FOREIGN KEY (relay_profile_id) REFERENCES relay_profile (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE relay_delivery DROP FOREIGN KEY FK_80701C9F8D9F6D38');
        $this->addSql('ALTER TABLE relay_delivery DROP FOREIGN KEY FK_80701C9FCD5CD3D1');
        $this->addSql('DROP TABLE relay_delivery');
    }
}
