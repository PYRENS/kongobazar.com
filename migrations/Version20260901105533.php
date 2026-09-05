<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260901105533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE new_items_section_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE new_items_tab (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, mode VARCHAR(20) DEFAULT \'auto\' NOT NULL, big_card_mode VARCHAR(20) DEFAULT \'random\' NOT NULL, product_count INT DEFAULT 7 NOT NULL, category_id INT NOT NULL, big_card_product_id INT DEFAULT NULL, INDEX IDX_C016D96712469DE2 (category_id), INDEX IDX_C016D9673119F9B7 (big_card_product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE new_items_tab_targeted_product (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, tab_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_79F8B8738D0C9323 (tab_id), INDEX IDX_79F8B8734584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE new_items_tab ADD CONSTRAINT FK_C016D96712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE new_items_tab ADD CONSTRAINT FK_C016D9673119F9B7 FOREIGN KEY (big_card_product_id) REFERENCES product (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE new_items_tab_targeted_product ADD CONSTRAINT FK_79F8B8738D0C9323 FOREIGN KEY (tab_id) REFERENCES new_items_tab (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE new_items_tab_targeted_product ADD CONSTRAINT FK_79F8B8734584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE new_items_tab DROP FOREIGN KEY FK_C016D96712469DE2');
        $this->addSql('ALTER TABLE new_items_tab DROP FOREIGN KEY FK_C016D9673119F9B7');
        $this->addSql('ALTER TABLE new_items_tab_targeted_product DROP FOREIGN KEY FK_79F8B8738D0C9323');
        $this->addSql('ALTER TABLE new_items_tab_targeted_product DROP FOREIGN KEY FK_79F8B8734584665A');
        $this->addSql('DROP TABLE new_items_section_setting');
        $this->addSql('DROP TABLE new_items_tab');
        $this->addSql('DROP TABLE new_items_tab_targeted_product');
    }
}
