<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720191451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE negotiation_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, offer_amount NUMERIC(10, 2) DEFAULT NULL, sent_at DATETIME NOT NULL, thread_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_A095F861E2904019 (thread_id), INDEX IDX_A095F861F624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE negotiation_thread (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, buyer_id INT NOT NULL, seller_id INT NOT NULL, INDEX IDX_7527175C4584665A (product_id), INDEX IDX_7527175C6C755722 (buyer_id), INDEX IDX_7527175C8DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE payment_link (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used TINYINT NOT NULL, created_at DATETIME NOT NULL, thread_id INT NOT NULL, UNIQUE INDEX UNIQ_172F0E855F37A13B (token), UNIQUE INDEX UNIQ_172F0E85E2904019 (thread_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE negotiation_message ADD CONSTRAINT FK_A095F861E2904019 FOREIGN KEY (thread_id) REFERENCES negotiation_thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE negotiation_message ADD CONSTRAINT FK_A095F861F624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE negotiation_thread ADD CONSTRAINT FK_7527175C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE negotiation_thread ADD CONSTRAINT FK_7527175C6C755722 FOREIGN KEY (buyer_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE negotiation_thread ADD CONSTRAINT FK_7527175C8DE820D9 FOREIGN KEY (seller_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payment_link ADD CONSTRAINT FK_172F0E85E2904019 FOREIGN KEY (thread_id) REFERENCES negotiation_thread (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE negotiation_message DROP FOREIGN KEY FK_A095F861E2904019');
        $this->addSql('ALTER TABLE negotiation_message DROP FOREIGN KEY FK_A095F861F624B39D');
        $this->addSql('ALTER TABLE negotiation_thread DROP FOREIGN KEY FK_7527175C4584665A');
        $this->addSql('ALTER TABLE negotiation_thread DROP FOREIGN KEY FK_7527175C6C755722');
        $this->addSql('ALTER TABLE negotiation_thread DROP FOREIGN KEY FK_7527175C8DE820D9');
        $this->addSql('ALTER TABLE payment_link DROP FOREIGN KEY FK_172F0E85E2904019');
        $this->addSql('DROP TABLE negotiation_message');
        $this->addSql('DROP TABLE negotiation_thread');
        $this->addSql('DROP TABLE payment_link');
    }
}
