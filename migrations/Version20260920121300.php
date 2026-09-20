<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260920121300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campaign_priority_product (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, campaign_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_79C56214F639F774 (campaign_id), INDEX IDX_79C562144584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE campaign_priority_seller (id INT AUTO_INCREMENT NOT NULL, product_limit INT DEFAULT 4 NOT NULL, position INT NOT NULL, campaign_id INT NOT NULL, seller_profile_id INT NOT NULL, INDEX IDX_7E303592F639F774 (campaign_id), INDEX IDX_7E3035923A1E3D2 (seller_profile_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE campaign_priority_product ADD CONSTRAINT FK_79C56214F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_priority_product ADD CONSTRAINT FK_79C562144584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_priority_seller ADD CONSTRAINT FK_7E303592F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_priority_seller ADD CONSTRAINT FK_7E3035923A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign_priority_product DROP FOREIGN KEY FK_79C56214F639F774');
        $this->addSql('ALTER TABLE campaign_priority_product DROP FOREIGN KEY FK_79C562144584665A');
        $this->addSql('ALTER TABLE campaign_priority_seller DROP FOREIGN KEY FK_7E303592F639F774');
        $this->addSql('ALTER TABLE campaign_priority_seller DROP FOREIGN KEY FK_7E3035923A1E3D2');
        $this->addSql('DROP TABLE campaign_priority_product');
        $this->addSql('DROP TABLE campaign_priority_seller');
    }
}
