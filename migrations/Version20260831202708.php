<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260831202708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rayon_flyout_column_item (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, column_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_AAAEFB56BE8E8ED5 (column_id), INDEX IDX_AAAEFB5612469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE rayon_flyout_column_item ADD CONSTRAINT FK_AAAEFB56BE8E8ED5 FOREIGN KEY (column_id) REFERENCES rayon_flyout_column (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rayon_flyout_column_item ADD CONSTRAINT FK_AAAEFB5612469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rayon_flyout_column_item DROP FOREIGN KEY FK_AAAEFB56BE8E8ED5');
        $this->addSql('ALTER TABLE rayon_flyout_column_item DROP FOREIGN KEY FK_AAAEFB5612469DE2');
        $this->addSql('DROP TABLE rayon_flyout_column_item');
    }
}
