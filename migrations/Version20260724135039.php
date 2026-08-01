<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260724135039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE compare_item (id INT AUTO_INCREMENT NOT NULL, added_at DATETIME NOT NULL, compare_list_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_881C0BAA6A9ADC5F (compare_list_id), INDEX IDX_881C0BAA4584665A (product_id), UNIQUE INDEX UNIQ_COMPAREITEM_LIST_PRODUCT (compare_list_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE compare_list (id INT AUTO_INCREMENT NOT NULL, session_token VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D3CFD6AC844A19ED (session_token), UNIQUE INDEX UNIQ_D3CFD6ACA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE product_view_log (id INT AUTO_INCREMENT NOT NULL, viewed_at DATETIME NOT NULL, product_id INT NOT NULL, INDEX IDX_2A5828CA4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE compare_item ADD CONSTRAINT FK_881C0BAA6A9ADC5F FOREIGN KEY (compare_list_id) REFERENCES compare_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compare_item ADD CONSTRAINT FK_881C0BAA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE compare_list ADD CONSTRAINT FK_D3CFD6ACA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_view_log ADD CONSTRAINT FK_2A5828CA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE advertisement ADD related_category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE advertisement ADD CONSTRAINT FK_C95F6AEED9ADE366 FOREIGN KEY (related_category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_C95F6AEED9ADE366 ON advertisement (related_category_id)');
        $this->addSql('ALTER TABLE brand ADD featured_homepage TINYINT DEFAULT 0 NOT NULL, ADD featured_homepage_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD featured_homepage_block TINYINT DEFAULT 0 NOT NULL, ADD featured_homepage_block_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD featured TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compare_item DROP FOREIGN KEY FK_881C0BAA6A9ADC5F');
        $this->addSql('ALTER TABLE compare_item DROP FOREIGN KEY FK_881C0BAA4584665A');
        $this->addSql('ALTER TABLE compare_list DROP FOREIGN KEY FK_D3CFD6ACA76ED395');
        $this->addSql('ALTER TABLE product_view_log DROP FOREIGN KEY FK_2A5828CA4584665A');
        $this->addSql('DROP TABLE compare_item');
        $this->addSql('DROP TABLE compare_list');
        $this->addSql('DROP TABLE product_view_log');
        $this->addSql('ALTER TABLE advertisement DROP FOREIGN KEY FK_C95F6AEED9ADE366');
        $this->addSql('DROP INDEX IDX_C95F6AEED9ADE366 ON advertisement');
        $this->addSql('ALTER TABLE advertisement DROP related_category_id');
        $this->addSql('ALTER TABLE brand DROP featured_homepage, DROP featured_homepage_position');
        $this->addSql('ALTER TABLE category DROP featured_homepage_block, DROP featured_homepage_block_position');
        $this->addSql('ALTER TABLE product DROP featured');
    }
}
