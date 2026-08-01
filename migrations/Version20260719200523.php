<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719200523 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pro_profile (service_description LONGTEXT DEFAULT NULL, professional_document_number VARCHAR(50) DEFAULT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE relay_profile (local_address_details LONGTEXT NOT NULL, daily_capacity INT DEFAULT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE seller_profile (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_5A59131EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE seller_profile_delivery_zone (seller_profile_id INT NOT NULL, administrative_unit_id INT NOT NULL, INDEX IDX_41000E763A1E3D2 (seller_profile_id), INDEX IDX_41000E76E66451E1 (administrative_unit_id), PRIMARY KEY (seller_profile_id, administrative_unit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE store_profile (store_name VARCHAR(150) NOT NULL, rccm_number VARCHAR(50) DEFAULT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE pro_profile ADD CONSTRAINT FK_74FE6CD4BF396750 FOREIGN KEY (id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE relay_profile ADD CONSTRAINT FK_CDFB1E71BF396750 FOREIGN KEY (id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seller_profile ADD CONSTRAINT FK_5A59131EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seller_profile_delivery_zone ADD CONSTRAINT FK_41000E763A1E3D2 FOREIGN KEY (seller_profile_id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seller_profile_delivery_zone ADD CONSTRAINT FK_41000E76E66451E1 FOREIGN KEY (administrative_unit_id) REFERENCES administrative_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE store_profile ADD CONSTRAINT FK_985F8BE8BF396750 FOREIGN KEY (id) REFERENCES seller_profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE administrative_unit ADD CONSTRAINT FK_C86D61BE727ACA70 FOREIGN KEY (parent_id) REFERENCES administrative_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E66451E1 FOREIGN KEY (administrative_unit_id) REFERENCES administrative_unit (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pro_profile DROP FOREIGN KEY FK_74FE6CD4BF396750');
        $this->addSql('ALTER TABLE relay_profile DROP FOREIGN KEY FK_CDFB1E71BF396750');
        $this->addSql('ALTER TABLE seller_profile DROP FOREIGN KEY FK_5A59131EA76ED395');
        $this->addSql('ALTER TABLE seller_profile_delivery_zone DROP FOREIGN KEY FK_41000E763A1E3D2');
        $this->addSql('ALTER TABLE seller_profile_delivery_zone DROP FOREIGN KEY FK_41000E76E66451E1');
        $this->addSql('ALTER TABLE store_profile DROP FOREIGN KEY FK_985F8BE8BF396750');
        $this->addSql('DROP TABLE pro_profile');
        $this->addSql('DROP TABLE relay_profile');
        $this->addSql('DROP TABLE seller_profile');
        $this->addSql('DROP TABLE seller_profile_delivery_zone');
        $this->addSql('DROP TABLE store_profile');
        $this->addSql('ALTER TABLE administrative_unit DROP FOREIGN KEY FK_C86D61BE727ACA70');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649E66451E1');
    }
}
