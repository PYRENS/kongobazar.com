<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830093730 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE home_deals_setting (id INT NOT NULL, display_count INT DEFAULT 10 NOT NULL, display_mode VARCHAR(30) DEFAULT \'random\' NOT NULL, mixed_kbz_count INT DEFAULT NULL, mixed_other_count INT DEFAULT NULL, exclude_boutique TINYINT DEFAULT 0 NOT NULL, exclude_pro TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE home_deals_excluded_seller (home_deals_setting_id INT NOT NULL, seller_profile_id INT NOT NULL, INDEX IDX_668DD5132B38D070 (home_deals_setting_id), INDEX IDX_668DD5133A1E3D2 (seller_profile_id), PRIMARY KEY (home_deals_setting_id, seller_profile_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE home_deals_targeted_seller (home_deals_setting_id INT NOT NULL, seller_profile_id INT NOT NULL, INDEX IDX_C2ABCCEA2B38D070 (home_deals_setting_id), INDEX IDX_C2ABCCEA3A1E3D2 (seller_profile_id), PRIMARY KEY (home_deals_setting_id, seller_profile_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE home_deals_targeted_product (home_deals_setting_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_27A700E32B38D070 (home_deals_setting_id), INDEX IDX_27A700E34584665A (product_id), PRIMARY KEY (home_deals_setting_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE home_deals_excluded_seller ADD CONSTRAINT FK_668DD5132B38D070 FOREIGN KEY (home_deals_setting_id) REFERENCES home_deals_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_excluded_seller ADD CONSTRAINT FK_668DD5133A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_targeted_seller ADD CONSTRAINT FK_C2ABCCEA2B38D070 FOREIGN KEY (home_deals_setting_id) REFERENCES home_deals_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_targeted_seller ADD CONSTRAINT FK_C2ABCCEA3A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_targeted_product ADD CONSTRAINT FK_27A700E32B38D070 FOREIGN KEY (home_deals_setting_id) REFERENCES home_deals_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_deals_targeted_product ADD CONSTRAINT FK_27A700E34584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seller_profile ADD is_kbz TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_deals_excluded_seller DROP FOREIGN KEY FK_668DD5132B38D070');
        $this->addSql('ALTER TABLE home_deals_excluded_seller DROP FOREIGN KEY FK_668DD5133A1E3D2');
        $this->addSql('ALTER TABLE home_deals_targeted_seller DROP FOREIGN KEY FK_C2ABCCEA2B38D070');
        $this->addSql('ALTER TABLE home_deals_targeted_seller DROP FOREIGN KEY FK_C2ABCCEA3A1E3D2');
        $this->addSql('ALTER TABLE home_deals_targeted_product DROP FOREIGN KEY FK_27A700E32B38D070');
        $this->addSql('ALTER TABLE home_deals_targeted_product DROP FOREIGN KEY FK_27A700E34584665A');
        $this->addSql('DROP TABLE home_deals_setting');
        $this->addSql('DROP TABLE home_deals_excluded_seller');
        $this->addSql('DROP TABLE home_deals_targeted_seller');
        $this->addSql('DROP TABLE home_deals_targeted_product');
        $this->addSql('ALTER TABLE seller_profile DROP is_kbz');
    }
}
