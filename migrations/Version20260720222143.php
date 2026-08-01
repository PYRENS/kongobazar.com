<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720222143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_blocked TINYINT NOT NULL, blocked_reason VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL, thread_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_B6BD307FE2904019 (thread_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE message_thread (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, buyer_id INT NOT NULL, seller_id INT NOT NULL, INDEX IDX_607D18C4584665A (product_id), INDEX IDX_607D18C6C755722 (buyer_id), INDEX IDX_607D18C8DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE2904019 FOREIGN KEY (thread_id) REFERENCES message_thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18C4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18C6C755722 FOREIGN KEY (buyer_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message_thread ADD CONSTRAINT FK_607D18C8DE820D9 FOREIGN KEY (seller_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE2904019');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18C4584665A');
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18C6C755722');
        $this->addSql('ALTER TABLE message_thread DROP FOREIGN KEY FK_607D18C8DE820D9');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_thread');
    }
}
