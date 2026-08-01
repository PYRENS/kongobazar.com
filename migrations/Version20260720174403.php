<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720174403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, checkout_group VARCHAR(36) NOT NULL, status VARCHAR(20) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, total_amount_usd NUMERIC(10, 2) NOT NULL, exchange_rate_used NUMERIC(12, 4) DEFAULT NULL, payment_method VARCHAR(30) DEFAULT NULL, escrow_status VARCHAR(20) NOT NULL, commission_amount_usd NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, buyer_id INT NOT NULL, seller_profile_id INT NOT NULL, commission_tier_id INT DEFAULT NULL, INDEX IDX_F52993986C755722 (buyer_id), INDEX IDX_F52993983A1E3D2 (seller_profile_id), INDEX IDX_F5299398A811F0C (commission_tier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, order_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F093B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES `user` (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993983A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A811F0C FOREIGN KEY (commission_tier_id) REFERENCES commission_tier (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F093B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variant (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993986C755722');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993983A1E3D2');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A811F0C');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F093B69A9AF');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
    }
}
