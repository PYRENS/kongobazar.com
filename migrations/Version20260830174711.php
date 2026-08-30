<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830174711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE home_category_block_section_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE home_category_block_setting (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, category_id INT NOT NULL, INDEX IDX_685D076712469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE home_category_block_subcategory (home_category_block_setting_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_7A44E3C07142A373 (home_category_block_setting_id), INDEX IDX_7A44E3C012469DE2 (category_id), PRIMARY KEY (home_category_block_setting_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE home_category_block_setting ADD CONSTRAINT FK_685D076712469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE home_category_block_subcategory ADD CONSTRAINT FK_7A44E3C07142A373 FOREIGN KEY (home_category_block_setting_id) REFERENCES home_category_block_setting (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE home_category_block_subcategory ADD CONSTRAINT FK_7A44E3C012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category ADD color VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_category_block_setting DROP FOREIGN KEY FK_685D076712469DE2');
        $this->addSql('ALTER TABLE home_category_block_subcategory DROP FOREIGN KEY FK_7A44E3C07142A373');
        $this->addSql('ALTER TABLE home_category_block_subcategory DROP FOREIGN KEY FK_7A44E3C012469DE2');
        $this->addSql('DROP TABLE home_category_block_section_setting');
        $this->addSql('DROP TABLE home_category_block_setting');
        $this->addSql('DROP TABLE home_category_block_subcategory');
        $this->addSql('ALTER TABLE category DROP color');
    }
}
