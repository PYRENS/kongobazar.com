<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816121609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD mega_menu_visible TINYINT DEFAULT 1 NOT NULL, ADD mega_menu_position INT DEFAULT NULL, ADD mega_menu_child_featured TINYINT DEFAULT 0 NOT NULL, ADD mega_menu_child_position INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP mega_menu_visible, DROP mega_menu_position, DROP mega_menu_child_featured, DROP mega_menu_child_position');
    }
}
