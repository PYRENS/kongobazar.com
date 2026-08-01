<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721202734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE custom_menu_item (id INT AUTO_INCREMENT NOT NULL, location VARCHAR(50) NOT NULL, label VARCHAR(100) NOT NULL, url VARCHAR(255) DEFAULT NULL, internal_route VARCHAR(100) DEFAULT NULL, position INT DEFAULT NULL, target_space VARCHAR(20) NOT NULL, open_in_new_tab TINYINT NOT NULL, active TINYINT NOT NULL, parent_id INT DEFAULT NULL, INDEX IDX_9D6EA2F6727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE custom_menu_item ADD CONSTRAINT FK_9D6EA2F6727ACA70 FOREIGN KEY (parent_id) REFERENCES custom_menu_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE custom_menu_item DROP FOREIGN KEY FK_9D6EA2F6727ACA70');
        $this->addSql('DROP TABLE custom_menu_item');
    }
}
