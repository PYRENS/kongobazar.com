<?php

namespace App\Entity;

use App\Repository\HowItWorksSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Singleton — section "Comment ça marche" de l'accueil :
 * interrupteur Afficher/Masquer + emplacement choisi parmi des positions FIXES.
 */
#[ORM\Entity(repositoryClass: HowItWorksSettingRepository::class)]
#[ORM\Table(name: 'how_it_works_setting')]
class HowItWorksSetting
{
    /** Emplacements possibles, dans l'ordre de la page (clé enregistrée en base => libellé admin). */
    public const POSITIONS = [
        'after_hero' => 'Juste après le Hero (avant le Carrousel Catégorie)',
        'after_category_carousel' => 'Après le Carrousel Catégorie (par défaut)',
        'after_promo_strip' => 'Après le bandeau promo, avant les colonnes',
        'after_deals' => 'Colonne centrale — après Ventes flash',
        'after_trending' => 'Colonne centrale — après Articles tendances',
        'before_footer' => 'Tout en bas — juste avant le pied de page',
    ];

    public const DEFAULT_POSITION = 'after_category_carousel';

    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(length: 40, options: ['default' => self::DEFAULT_POSITION])]
    private string $position = self::DEFAULT_POSITION;

    public function getId(): int
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;
        return $this;
    }

    /** Toujours une position valide (repli sur la position par défaut si la valeur en base est inconnue). */
    public function getPosition(): string
    {
        return isset(self::POSITIONS[$this->position]) ? $this->position : self::DEFAULT_POSITION;
    }

    public function setPosition(string $position): static
    {
        if (!isset(self::POSITIONS[$position])) {
            throw new \InvalidArgumentException(sprintf('Emplacement inconnu : "%s".', $position));
        }
        $this->position = $position;
        return $this;
    }

    public function getPositionLabel(): string
    {
        return self::POSITIONS[$this->getPosition()];
    }
}