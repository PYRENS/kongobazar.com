<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803125955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE negotiation_thread ADD order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE negotiation_thread ADD CONSTRAINT FK_7527175C8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7527175C8D9F6D38 ON negotiation_thread (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE negotiation_thread DROP FOREIGN KEY FK_7527175C8D9F6D38');
        $this->addSql('DROP INDEX UNIQ_7527175C8D9F6D38 ON negotiation_thread');
        $this->addSql('ALTER TABLE negotiation_thread DROP order_id');
    }
}
