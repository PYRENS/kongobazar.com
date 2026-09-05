<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260903135747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE individual_section_category (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, card_count INT DEFAULT 8 NOT NULL, category_id INT NOT NULL, INDEX IDX_D1BC1E9512469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE individual_section_priority_product (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, section_category_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_2EE948924B7E29D (section_category_id), INDEX IDX_2EE948924584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE individual_section_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE individual_section_category ADD CONSTRAINT FK_D1BC1E9512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE individual_section_priority_product ADD CONSTRAINT FK_2EE948924B7E29D FOREIGN KEY (section_category_id) REFERENCES individual_section_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE individual_section_priority_product ADD CONSTRAINT FK_2EE948924584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE individual_section_category DROP FOREIGN KEY FK_D1BC1E9512469DE2');
        $this->addSql('ALTER TABLE individual_section_priority_product DROP FOREIGN KEY FK_2EE948924B7E29D');
        $this->addSql('ALTER TABLE individual_section_priority_product DROP FOREIGN KEY FK_2EE948924584665A');
        $this->addSql('DROP TABLE individual_section_category');
        $this->addSql('DROP TABLE individual_section_priority_product');
        $this->addSql('DROP TABLE individual_section_setting');
    }
}
