<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830194204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE home_category_block_sort_tab (id INT AUTO_INCREMENT NOT NULL, sort_key VARCHAR(20) NOT NULL, position INT NOT NULL, product_count INT DEFAULT 4 NOT NULL, block_id INT NOT NULL, INDEX IDX_4282DC0BE9ED820C (block_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE home_category_block_sort_tab ADD CONSTRAINT FK_4282DC0BE9ED820C FOREIGN KEY (block_id) REFERENCES home_category_block_setting (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE home_category_block_sort_tab DROP FOREIGN KEY FK_4282DC0BE9ED820C');
        $this->addSql('DROP TABLE home_category_block_sort_tab');
    }
}
