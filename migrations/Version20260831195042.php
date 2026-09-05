<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260831195042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rayon_flyout_column (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, rayon_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_1F7F2305D3202E52 (rayon_id), INDEX IDX_1F7F230512469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rayon_flyout_column ADD CONSTRAINT FK_1F7F2305D3202E52 FOREIGN KEY (rayon_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rayon_flyout_column ADD CONSTRAINT FK_1F7F230512469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rayon_flyout_column DROP FOREIGN KEY FK_1F7F2305D3202E52');
        $this->addSql('ALTER TABLE rayon_flyout_column DROP FOREIGN KEY FK_1F7F230512469DE2');
        $this->addSql('DROP TABLE rayon_flyout_column');
    }
}
