<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720220812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE advertisement (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, target_url VARCHAR(255) DEFAULT NULL, target_space VARCHAR(20) NOT NULL, zone_key VARCHAR(50) NOT NULL, position INT DEFAULT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, click_count INT NOT NULL, is_paid TINYINT NOT NULL, price_amount_usd NUMERIC(10, 2) DEFAULT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, advertiser_id INT DEFAULT NULL, INDEX IDX_C95F6AEEBA2FCBC2 (advertiser_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE coupon (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, scope VARCHAR(20) NOT NULL, discount_type VARCHAR(20) NOT NULL, discount_value NUMERIC(10, 2) NOT NULL, usage_limit INT DEFAULT NULL, used_count INT NOT NULL, expires_at DATETIME DEFAULT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, seller_profile_id INT NOT NULL, category_id INT DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_64BF3F023A1E3D2 (seller_profile_id), INDEX IDX_64BF3F0212469DE2 (category_id), INDEX IDX_64BF3F024584665A (product_id), UNIQUE INDEX UNIQ_COUPON_SELLER_CODE (seller_profile_id, code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE advertisement ADD CONSTRAINT FK_C95F6AEEBA2FCBC2 FOREIGN KEY (advertiser_id) REFERENCES seller_profile (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F023A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F0212469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F024584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE advertisement DROP FOREIGN KEY FK_C95F6AEEBA2FCBC2');
        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F023A1E3D2');
        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F0212469DE2');
        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F024584665A');
        $this->addSql('DROP TABLE advertisement');
        $this->addSql('DROP TABLE coupon');
    }
}
