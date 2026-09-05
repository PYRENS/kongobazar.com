<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902183031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE coming_soon_section_setting (id INT NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, title VARCHAR(100) DEFAULT \'Prochainement\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE coming_soon_tab (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, category_id INT NOT NULL, INDEX IDX_A1C28CBD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE coming_soon_tab_product (id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, tab_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_B420AFB98D0C9323 (tab_id), INDEX IDX_B420AFB94584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE coming_soon_tab ADD CONSTRAINT FK_A1C28CBD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE coming_soon_tab_product ADD CONSTRAINT FK_B420AFB98D0C9323 FOREIGN KEY (tab_id) REFERENCES coming_soon_tab (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE coming_soon_tab_product ADD CONSTRAINT FK_B420AFB94584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coming_soon_tab DROP FOREIGN KEY FK_A1C28CBD12469DE2');
        $this->addSql('ALTER TABLE coming_soon_tab_product DROP FOREIGN KEY FK_B420AFB98D0C9323');
        $this->addSql('ALTER TABLE coming_soon_tab_product DROP FOREIGN KEY FK_B420AFB94584665A');
        $this->addSql('DROP TABLE coming_soon_section_setting');
        $this->addSql('DROP TABLE coming_soon_tab');
        $this->addSql('DROP TABLE coming_soon_tab_product');
    }
}
