<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /** @var Collection<int, CampaignPriorityProduct> */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CampaignPriorityProduct::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $priorityProducts;

    /** @var Collection<int, CampaignPrioritySeller> */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CampaignPrioritySeller::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $prioritySellers;

    #[ORM\Column(length: 20, options: ['default' => 'middle-right'])]
    private string $badgePosition = 'middle-right';

    #[ORM\Column(options: ['default' => true])]
    private bool $badgeVisible = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $bannerEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $textsEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $themeTopEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $themeEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $priorityProductsEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $prioritySellersEnabled = true;

    /** Affiche ou non la section "Top Catégories" sur la page de la campagne. */
    #[ORM\Column(options: ['default' => true])]
    private bool $topCategoriesEnabled = true;

    /** Bannières publicitaires intercalées entre les lots de produits (zone "solde_between_batches"). */
    #[ORM\Column(options: ['default' => true])]
    private bool $batchBannersEnabled = true;

    /** Une bannière tous les N lots (1 = avant chaque lot chargé). */
    #[ORM\Column(options: ['default' => 1])]
    private int $batchBannerEvery = 1;

    /** random = au hasard, order = rotation dans l'ordre des positions de la zone. */
    #[ORM\Column(length: 10, options: ['default' => 'random'])]
    private string $batchBannerMode = 'random';

    /** IDs des publicités choisies ; vide = toutes les bannières actives de la zone. */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $batchBannerAdIds = null;

    /** Textes libres sur la bannière : [{text, pos, size, color, bg}, ...] */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bannerTexts = null;

    /** Décor de fond sur les côtés de la page (guirlandes, thème Noël, etc.) */
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'themeImageName')]
    private ?File $themeImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $themeImageName = null;

    /** Décor du haut : affiché une seule fois, pleine largeur, sans répétition. */
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'themeTopImageName')]
    private ?File $themeTopImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $themeTopImageName = null;

    #[ORM\Column(length: 20, options: ['default' => 'width-repeat'])]
    private string $themeMode = 'width-repeat';

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $themeBgColor = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->priorityProducts = new ArrayCollection();
        $this->prioritySellers = new ArrayCollection();
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

    /** @return Collection<int, CampaignPriorityProduct> */
    public function getPriorityProducts(): Collection { return $this->priorityProducts; }
    public function addPriorityProduct(CampaignPriorityProduct $item): static
    {
        $item->setCampaign($this);
        $this->priorityProducts->add($item);
        return $this;
    }

    /** @return Collection<int, CampaignPrioritySeller> */
    public function getPrioritySellers(): Collection { return $this->prioritySellers; }
    public function addPrioritySeller(CampaignPrioritySeller $item): static
    {
        $item->setCampaign($this);
        $this->prioritySellers->add($item);
        return $this;
    }

    public const BADGE_POSITIONS = [
        'top-left', 'top-center', 'top-right',
        'middle-left', 'middle-center', 'middle-right',
        'bottom-left', 'bottom-center', 'bottom-right',
    ];

    public function getBadgePosition(): string { return $this->badgePosition; }
    public function setBadgePosition(string $v): static
    {
        $this->badgePosition = in_array($v, self::BADGE_POSITIONS, true) ? $v : 'middle-right';
        return $this;
    }

    public function isBadgeVisible(): bool { return $this->badgeVisible; }
    public function setBadgeVisible(bool $v): static { $this->badgeVisible = $v; return $this; }

    public function isBannerEnabled(): bool { return $this->bannerEnabled; }
    public function setBannerEnabled(bool $v): static { $this->bannerEnabled = $v; return $this; }

    public function isTextsEnabled(): bool { return $this->textsEnabled; }
    public function setTextsEnabled(bool $v): static { $this->textsEnabled = $v; return $this; }

    public function isThemeTopEnabled(): bool { return $this->themeTopEnabled; }
    public function setThemeTopEnabled(bool $v): static { $this->themeTopEnabled = $v; return $this; }

    public function isThemeEnabled(): bool { return $this->themeEnabled; }
    public function setThemeEnabled(bool $v): static { $this->themeEnabled = $v; return $this; }

    public function isPriorityProductsEnabled(): bool { return $this->priorityProductsEnabled; }
    public function setPriorityProductsEnabled(bool $v): static { $this->priorityProductsEnabled = $v; return $this; }

    public function isPrioritySellersEnabled(): bool { return $this->prioritySellersEnabled; }
    public function setPrioritySellersEnabled(bool $v): static { $this->prioritySellersEnabled = $v; return $this; }

    public function isTopCategoriesEnabled(): bool { return $this->topCategoriesEnabled; }
    public function setTopCategoriesEnabled(bool $v): static { $this->topCategoriesEnabled = $v; return $this; }

    public function isBatchBannersEnabled(): bool { return $this->batchBannersEnabled; }
    public function setBatchBannersEnabled(bool $v): static { $this->batchBannersEnabled = $v; return $this; }

    public function getBatchBannerEvery(): int { return $this->batchBannerEvery; }
    public function setBatchBannerEvery(int $v): static { $this->batchBannerEvery = max(1, min(5, $v)); return $this; }

    public function getBatchBannerMode(): string { return $this->batchBannerMode; }
    public function setBatchBannerMode(string $v): static { $this->batchBannerMode = 'order' === $v ? 'order' : 'random'; return $this; }

    /** @return int[] */
    public function getBatchBannerAdIds(): array { return array_map('intval', $this->batchBannerAdIds ?? []); }
    /** @param int[] $ids */
    public function setBatchBannerAdIds(array $ids): static
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0)));
        $this->batchBannerAdIds = $ids ?: null;
        return $this;
    }

    public function getBannerTexts(): array { return $this->bannerTexts ?? []; }
    public function setBannerTexts(?array $v): static { $this->bannerTexts = $v ?: null; return $this; }

    public const THEME_MODES = [
        'width-repeat' => 'Pleine largeur, répétée vers le bas',
        'top' => 'Une seule fois, en haut (pleine largeur)',
        'tile' => 'Motif répété (mosaïque)',
        'fixed' => 'Plein écran, fixe pendant le défilement',
    ];

    public function setThemeImageFile(?File $file = null): static { $this->themeImageFile = $file; return $this; }
    public function getThemeImageFile(): ?File { return $this->themeImageFile; }
    public function getThemeImageName(): ?string { return $this->themeImageName; }

    public function setThemeTopImageFile(?File $file = null): static { $this->themeTopImageFile = $file; return $this; }
    public function getThemeTopImageFile(): ?File { return $this->themeTopImageFile; }
    public function getThemeTopImageName(): ?string { return $this->themeTopImageName; }
    public function setThemeTopImageName(?string $name): static { $this->themeTopImageName = $name; return $this; }
    public function setThemeImageName(?string $name): static { $this->themeImageName = $name; return $this; }

    public function getThemeMode(): string { return $this->themeMode; }
    public function setThemeMode(string $v): static
    {
        $this->themeMode = array_key_exists($v, self::THEME_MODES) ? $v : 'width-repeat';
        return $this;
    }

    public function getThemeBgColor(): ?string { return $this->themeBgColor; }
    public function setThemeBgColor(?string $v): static { $this->themeBgColor = $v ?: null; return $this; }

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
