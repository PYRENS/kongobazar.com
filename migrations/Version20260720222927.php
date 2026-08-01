<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720222927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE wishlist_alert (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, sent_at DATETIME NOT NULL, wishlist_item_id INT NOT NULL, INDEX IDX_43582E398D638424 (wishlist_item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE wishlist_item (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, variant_id INT NOT NULL, INDEX IDX_6424F4E8A76ED395 (user_id), INDEX IDX_6424F4E83B69A9AF (variant_id), UNIQUE INDEX UNIQ_WISHLIST_USER_VARIANT (user_id, variant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE wishlist_alert ADD CONSTRAINT FK_43582E398D638424 FOREIGN KEY (wishlist_item_id) REFERENCES wishlist_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist_item ADD CONSTRAINT FK_6424F4E83B69A9AF FOREIGN KEY (variant_id) REFERENCES product_variant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wishlist_alert DROP FOREIGN KEY FK_43582E398D638424');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E8A76ED395');
        $this->addSql('ALTER TABLE wishlist_item DROP FOREIGN KEY FK_6424F4E83B69A9AF');
        $this->addSql('DROP TABLE wishlist_alert');
        $this->addSql('DROP TABLE wishlist_item');
    }
}
