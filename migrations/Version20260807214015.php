<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807214015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Les feuilles Pièces détachées héritent visuellement de l\'icône de leur branche système';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE category child
            INNER JOIN category parent ON child.parent_id = parent.id
            SET child.icon = parent.icon
            WHERE child.icon = 'bi-square'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE category child
            INNER JOIN category parent ON child.parent_id = parent.id
            SET child.icon = 'bi-square'
            WHERE parent.slug IN (
                'moteur', 'freinage', 'suspension-direction', 'transmission-embrayage',
                'refroidissement', 'echappement', 'electrique', 'eclairage',
                'filtration', 'climatisation', 'carrosserie', 'transmission',
                'carrosserie-carenage', 'suspension'
            )
        ");
    }
}