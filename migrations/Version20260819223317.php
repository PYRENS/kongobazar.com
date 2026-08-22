<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819223317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_sidebar_theme (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, bg_color VARCHAR(30) NOT NULL, text_color VARCHAR(30) NOT NULL, hover_bg_color VARCHAR(30) NOT NULL, hover_text_color VARCHAR(30) NOT NULL, active_bg_color VARCHAR(30) NOT NULL, active_text_color VARCHAR(30) NOT NULL, icon_color VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D1C978A5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user ADD admin_sidebar_theme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A0E21BF9 FOREIGN KEY (admin_sidebar_theme_id) REFERENCES admin_sidebar_theme (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649A0E21BF9 ON user (admin_sidebar_theme_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin_sidebar_theme');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A0E21BF9');
        $this->addSql('DROP INDEX IDX_8D93D649A0E21BF9 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP admin_sidebar_theme_id');
    }
}
