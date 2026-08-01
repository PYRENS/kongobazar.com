<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727200537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_recommendation (id INT AUTO_INCREMENT NOT NULL, position INT DEFAULT NULL, product_id INT NOT NULL, recommended_product_id INT NOT NULL, INDEX IDX_105B5AFE4584665A (product_id), INDEX IDX_105B5AFE52343A6A (recommended_product_id), UNIQUE INDEX UNIQ_PRODUCT_RECOMMENDATION (product_id, recommended_product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE product_recommendation ADD CONSTRAINT FK_105B5AFE4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_recommendation ADD CONSTRAINT FK_105B5AFE52343A6A FOREIGN KEY (recommended_product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_recommendation DROP FOREIGN KEY FK_105B5AFE4584665A');
        $this->addSql('ALTER TABLE product_recommendation DROP FOREIGN KEY FK_105B5AFE52343A6A');
        $this->addSql('DROP TABLE product_recommendation');
    }
}
