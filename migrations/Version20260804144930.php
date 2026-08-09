<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260804144930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_product_status ON product (status)');
        $this->addSql('CREATE INDEX idx_product_condition ON product (`condition`)');
        $this->addSql('CREATE INDEX idx_product_created_at ON product (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_product_status ON product');
        $this->addSql('DROP INDEX idx_product_condition ON product');
        $this->addSql('DROP INDEX idx_product_created_at ON product');
    }
}
