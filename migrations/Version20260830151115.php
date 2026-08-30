<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830151115 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE trending_section_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE trending_tab_setting (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, mode VARCHAR(20) DEFAULT \'recent\' NOT NULL, product_count INT DEFAULT 5 NOT NULL, category_id INT NOT NULL, INDEX IDX_9C30BBCB12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE trending_tab_targeted_product (trending_tab_setting_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_9D43C7F6DA53FBD2 (trending_tab_setting_id), INDEX IDX_9D43C7F64584665A (product_id), PRIMARY KEY (trending_tab_setting_id, product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE trending_tab_setting ADD CONSTRAINT FK_9C30BBCB12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE trending_tab_targeted_product ADD CONSTRAINT FK_9D43C7F6DA53FBD2 FOREIGN KEY (trending_tab_setting_id) REFERENCES trending_tab_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trending_tab_targeted_product ADD CONSTRAINT FK_9D43C7F64584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trending_tab_setting DROP FOREIGN KEY FK_9C30BBCB12469DE2');
        $this->addSql('ALTER TABLE trending_tab_targeted_product DROP FOREIGN KEY FK_9D43C7F6DA53FBD2');
        $this->addSql('ALTER TABLE trending_tab_targeted_product DROP FOREIGN KEY FK_9D43C7F64584665A');
        $this->addSql('DROP TABLE trending_section_setting');
        $this->addSql('DROP TABLE trending_tab_setting');
        $this->addSql('DROP TABLE trending_tab_targeted_product');
    }
}
