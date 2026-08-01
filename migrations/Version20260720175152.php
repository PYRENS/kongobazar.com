<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720175152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE escrow_transaction (id INT AUTO_INCREMENT NOT NULL, amount_held_usd NUMERIC(10, 2) NOT NULL, amount_released_usd NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) NOT NULL, provider_reference VARCHAR(100) DEFAULT NULL, released_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, order_id INT NOT NULL, UNIQUE INDEX UNIQ_A7C691068D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE escrow_transaction ADD CONSTRAINT FK_A7C691068D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE escrow_transaction DROP FOREIGN KEY FK_A7C691068D9F6D38');
        $this->addSql('DROP TABLE escrow_transaction');
    }
}
