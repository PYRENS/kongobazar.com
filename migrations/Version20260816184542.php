<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816184542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE social_float_setting (id INT NOT NULL, show_on_desktop TINYINT DEFAULT 1 NOT NULL, show_on_tablet TINYINT DEFAULT 1 NOT NULL, show_on_mobile TINYINT DEFAULT 0 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE social_link (id INT AUTO_INCREMENT NOT NULL, platform VARCHAR(40) NOT NULL, icon_class VARCHAR(60) NOT NULL, color_hex VARCHAR(20) NOT NULL, url VARCHAR(255) NOT NULL, position INT NOT NULL, active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE social_float_setting');
        $this->addSql('DROP TABLE social_link');
    }
}
