<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720212152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discount_campaign (id INT AUTO_INCREMENT NOT NULL, mode VARCHAR(20) NOT NULL, discounted_price NUMERIC(10, 2) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, variant_id INT DEFAULT NULL, INDEX IDX_75133A424584665A (product_id), INDEX IDX_75133A423B69A9AF (variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE discount_campaign ADD CONSTRAINT FK_75133A424584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_campaign ADD CONSTRAINT FK_75133A423B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount_campaign DROP FOREIGN KEY FK_75133A424584665A');
        $this->addSql('ALTER TABLE discount_campaign DROP FOREIGN KEY FK_75133A423B69A9AF');
        $this->addSql('DROP TABLE discount_campaign');
    }
}
