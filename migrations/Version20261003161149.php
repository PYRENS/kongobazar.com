<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003161149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sidebar_filler_banner_category (sidebar_filler_banner_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_A89FE2B895B6FFF6 (sidebar_filler_banner_id), INDEX IDX_A89FE2B812469DE2 (category_id), PRIMARY KEY (sidebar_filler_banner_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE sidebar_filler_banner_category ADD CONSTRAINT FK_A89FE2B895B6FFF6 FOREIGN KEY (sidebar_filler_banner_id) REFERENCES sidebar_filler_banner (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sidebar_filler_banner_category ADD CONSTRAINT FK_A89FE2B812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sidebar_filler_banner_category DROP FOREIGN KEY FK_A89FE2B895B6FFF6');
        $this->addSql('ALTER TABLE sidebar_filler_banner_category DROP FOREIGN KEY FK_A89FE2B812469DE2');
        $this->addSql('DROP TABLE sidebar_filler_banner_category');
    }
}
