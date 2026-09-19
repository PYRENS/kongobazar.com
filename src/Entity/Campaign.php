<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Une campagne d'accueil (Solde, Vente flash, Rentrée scolaire, Noël/Nouvel An).
 * S'active/se désactive automatiquement selon startAt/endAt (comparés à "maintenant"
 * à chaque requête, pas de tâche planifiée nécessaire) — ET doit être marquée
 * "active" par l'admin. La page par défaut reprend sa place dès qu'aucune
 * campagne n'est valide à l'instant présent.
 */
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[Vich\Uploadable]
class Campaign
{
    public const TYPES = [
        'solde' => 'Solde',
        'vente_flash' => 'Vente flash',
        'rentree_scolaire' => 'Rentrée scolaire',
        'noel_nouvel_an' => 'Noël / Nouvel An',
        'autre' => 'Autre',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $type = 'solde';

    #[ORM\Column(length: 150)]
    private string $title = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $customTypeLabel = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'bannerImageName')]
    private ?File $bannerImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bannerImageName = null;

    #[ORM\Column(options: ['default' => 12])]
    private int $batchSize = 12;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static
    {
        $this->type = array_key_exists($type, self::TYPES) ? $type : 'solde';
        return $this;
    }
    public function getTypeLabel(): string
    {
        if ('autre' === $this->type && $this->customTypeLabel) {
            return $this->customTypeLabel;
        }
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getCustomTypeLabel(): ?string { return $this->customTypeLabel; }
    public function setCustomTypeLabel(?string $v): static { $this->customTypeLabel = $v; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getStartAt(): ?\DateTimeImmutable { return $this->startAt; }
    public function setStartAt(?\DateTimeImmutable $v): static { $this->startAt = $v; return $this; }

    public function getEndAt(): ?\DateTimeImmutable { return $this->endAt; }
    public function setEndAt(?\DateTimeImmutable $v): static { $this->endAt = $v; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }

    public function setBannerImageFile(?File $file = null): static
    {
        $this->bannerImageFile = $file;
        return $this;
    }
    public function getBannerImageFile(): ?File { return $this->bannerImageFile; }

    public function getBannerImageName(): ?string { return $this->bannerImageName; }
    public function setBannerImageName(?string $name): static { $this->bannerImageName = $name; return $this; }

    public function getBatchSize(): int { return $this->batchSize; }
    public function setBatchSize(int $v): static { $this->batchSize = max(1, $v); return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }

    /** Vraie "en direct" : marquée active ET la date/heure actuelle tombe dans sa période. */
    public function isCurrentlyLive(): bool
    {
        if (!$this->active || !$this->startAt || !$this->endAt) {
            return false;
        }
        $now = new \DateTimeImmutable();
        return $this->startAt <= $now && $this->endAt > $now;
    }

    /** "à venir" | "en cours" | "terminée" | "désactivée" — pour l'affichage admin. */
    public function getStatusLabel(): string
    {
        if (!$this->active) {
            return 'Désactivée';
        }
        $now = new \DateTimeImmutable();
        if ($this->startAt && $now < $this->startAt) {
            return 'À venir';
        }
        if ($this->endAt && $now >= $this->endAt) {
            return 'Terminée';
        }
        return 'En cours';
    }
}
