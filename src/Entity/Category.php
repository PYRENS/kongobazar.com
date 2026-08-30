<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CATEGORY_PARENT_SLUG', columns: ['parent_id', 'slug'])]
#[Vich\Uploadable]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 180)]
    private ?string $slug = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    #[Vich\UploadableField(mapping: 'category_illustrations', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)]
    private Collection $products;

    // Nouveau champ, à ajouter avec les autres
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $themeColor = null; // ex. #2FA8E0 — si null, hérite du parent, jusqu'au rayon

    #[ORM\Column(options: ['default' => false])]
    private bool $featuredHomepageTab = false;

    #[ORM\Column(nullable: true)]
    private ?int $featuredHomepagePosition = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $featuredHomepageBlock = false;

    #[ORM\Column(nullable: true)]
    private ?int $featuredHomepageBlockPosition = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null; // classe Bootstrap Icons, ex. "bi-bag-heart"

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null; // couleur hex propre à cette catégorie (optionnel — voir getEffectiveColor())

    #[ORM\Column(options: ['default' => false])]
    private bool $topRayon = false;

    #[ORM\Column(nullable: true)]
    private ?int $topRayonPosition = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $trendingPinned = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $requiresModel = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $authenticityRelevant = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVehiclePart = false;

    // Marque le nœud "racine" des pièces détachées côté Auto (ex: catégorie "Pièce de rechange" sous Auto).
    #[ORM\Column(options: ['default' => false])]
    private bool $isAutoPartRoot = false;

    // Idem côté Moto.
    #[ORM\Column(options: ['default' => false])]
    private bool $isMotoPartRoot = false;

    // Marque le nœud racine "Accessoire" sous Auto / Moto — sert au libellé affiché dans la liste Produits.
    #[ORM\Column(options: ['default' => false])]
    private bool $isAutoAccessoryRoot = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isMotoAccessoryRoot = false;

    // Marque le nœud racine "Offres" sous Auto / Moto (annonces de vente du véhicule lui-même).
    #[ORM\Column(options: ['default' => false])]
    private bool $isAutoOfferRoot = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isMotoOfferRoot = false;

    #[ORM\Column(nullable: true)]
    private ?int $trendingPinnedPosition = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $megaMenuVisible = true;

    #[ORM\Column(nullable: true)]
    private ?int $megaMenuPosition = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $megaMenuChildFeatured = false;

    #[ORM\Column(nullable: true)]
    private ?int $megaMenuChildPosition = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $flyoutColumnFeatured = false;

    #[ORM\Column(nullable: true)]
    private ?int $flyoutColumnPosition = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $flyoutAdPosition = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /** @return Collection<int, self> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    /**
     * Remonte la chaîne complète jusqu'à la racine (fil d'ariane, construction d'URL).
     * @return self[] du rayon racine jusqu'à la catégorie courante
     */
    public function getAncestors(): array
    {
        $path = [];
        $node = $this;
        while (null !== $node) {
            array_unshift($path, $node);
            $node = $node->getParent();
        }
        return $path;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            // Force Doctrine à détecter un changement réel sur l'entité,
            // sans quoi Vich ne déclenche jamais le traitement du fichier
            // si aucun autre champ n'a été modifié en même temps.
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function getThemeColor(): ?string
    {
        return $this->themeColor;
    }

    public function setThemeColor(?string $themeColor): static
    {
        $this->themeColor = $themeColor;
        return $this;
    }

    /**
     * Remonte l'arbre jusqu'à trouver une couleur définie.
     * Retourne null si même le rayon racine n'a pas de couleur (le template appliquera alors la couleur par défaut du site).
     */
    public function getEffectiveThemeColor(): ?string
    {
        $node = $this;
        while (null !== $node) {
            if (null !== $node->themeColor) {
                return $node->themeColor;
            }
            $node = $node->getParent();
        }
        return null;
    }

    public function isFeaturedHomepageTab(): bool
    {
        return $this->featuredHomepageTab;
    }

    public function setFeaturedHomepageTab(bool $featuredHomepageTab): static
    {
        $this->featuredHomepageTab = $featuredHomepageTab;
        return $this;
    }

    public function getFeaturedHomepagePosition(): ?int
    {
        return $this->featuredHomepagePosition;
    }

    public function setFeaturedHomepagePosition(?int $position): static
    {
        $this->featuredHomepagePosition = $position;
        return $this;
    }

    public function isFeaturedHomepageBlock(): bool
    {
        return $this->featuredHomepageBlock;
    }

    public function setFeaturedHomepageBlock(bool $featuredHomepageBlock): static
    {
        $this->featuredHomepageBlock = $featuredHomepageBlock;
        return $this;
    }

    public function getFeaturedHomepageBlockPosition(): ?int
    {
        return $this->featuredHomepageBlockPosition;
    }

    public function setFeaturedHomepageBlockPosition(?int $position): static
    {
        $this->featuredHomepageBlockPosition = $position;
        return $this;
    }

    /**
     * Retourne cette catégorie et toutes ses descendantes (récursif),
     * utile pour chercher les produits d'un rayon entier, pas juste
     * ceux rattachés exactement à ce nœud.
     *
     * @return Category[]
     */
    public function getDescendantCategories(): array
    {
        $all = [$this];
        foreach ($this->children as $child) {
            $all = array_merge($all, $child->getDescendantCategories());
        }
        return $all;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;
        return $this;
    }

    /** Couleur à utiliser réellement : la sienne, sinon celle du parent direct, sinon en remontant jusqu'à la racine, sinon le bleu du site par défaut. */
    public function getEffectiveColor(): string
    {
        $category = $this;
        while ($category) {
            if ($category->getColor()) {
                return $category->getColor();
            }
            $category = $category->getParent();
        }

        return '#2FA8E0';
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function isTopRayon(): bool
    {
        return $this->topRayon;
    }

    public function setTopRayon(bool $topRayon): static
    {
        $this->topRayon = $topRayon;
        return $this;
    }

    public function getTopRayonPosition(): ?int
    {
        return $this->topRayonPosition;
    }

    public function setTopRayonPosition(?int $position): static
    {
        $this->topRayonPosition = $position;
        return $this;
    }

    public function getFullPath(): string
    {
        $parts = [$this->name];
        $walker = $this->parent;
        while ($walker) {
            array_unshift($parts, $walker->getName());
            $walker = $walker->getParent();
        }
        return implode(' / ', $parts);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isTrendingPinned(): bool
    {
        return $this->trendingPinned;
    }

    public function setTrendingPinned(bool $trendingPinned): static
    {
        $this->trendingPinned = $trendingPinned;
        return $this;
    }

    public function isRequiresModel(): bool
    {
        return $this->requiresModel;
    }

    public function setRequiresModel(bool $requiresModel): static
    {
        $this->requiresModel = $requiresModel;
        return $this;
    }

    public function isAuthenticityRelevant(): bool
    {
        return $this->authenticityRelevant;
    }

    public function setAuthenticityRelevant(bool $authenticityRelevant): static
    {
        $this->authenticityRelevant = $authenticityRelevant;
        return $this;
    }

    public function isVehiclePart(): bool
    {
        return $this->isVehiclePart;
    }

    public function setIsVehiclePart(bool $isVehiclePart): static
    {
        $this->isVehiclePart = $isVehiclePart;
        return $this;
    }

    public function isAutoPartRoot(): bool
    {
        return $this->isAutoPartRoot;
    }

    public function setIsAutoPartRoot(bool $isAutoPartRoot): static
    {
        $this->isAutoPartRoot = $isAutoPartRoot;
        return $this;
    }

    public function isMotoPartRoot(): bool
    {
        return $this->isMotoPartRoot;
    }

    public function setIsMotoPartRoot(bool $isMotoPartRoot): static
    {
        $this->isMotoPartRoot = $isMotoPartRoot;
        return $this;
    }

    public function isAutoAccessoryRoot(): bool
    {
        return $this->isAutoAccessoryRoot;
    }

    public function setIsAutoAccessoryRoot(bool $isAutoAccessoryRoot): static
    {
        $this->isAutoAccessoryRoot = $isAutoAccessoryRoot;
        return $this;
    }

    public function isMotoAccessoryRoot(): bool
    {
        return $this->isMotoAccessoryRoot;
    }

    public function setIsMotoAccessoryRoot(bool $isMotoAccessoryRoot): static
    {
        $this->isMotoAccessoryRoot = $isMotoAccessoryRoot;
        return $this;
    }

    public function isAutoOfferRoot(): bool
    {
        return $this->isAutoOfferRoot;
    }

    public function setIsAutoOfferRoot(bool $isAutoOfferRoot): static
    {
        $this->isAutoOfferRoot = $isAutoOfferRoot;
        return $this;
    }

    public function isMotoOfferRoot(): bool
    {
        return $this->isMotoOfferRoot;
    }

    public function setIsMotoOfferRoot(bool $isMotoOfferRoot): static
    {
        $this->isMotoOfferRoot = $isMotoOfferRoot;
        return $this;
    }

    public function getTrendingPinnedPosition(): ?int
    {
        return $this->trendingPinnedPosition;
    }

    public function setTrendingPinnedPosition(?int $position): static
    {
        $this->trendingPinnedPosition = $position;
        return $this;
    }

    public function isMegaMenuVisible(): bool
    {
        return $this->megaMenuVisible;
    }

    public function setMegaMenuVisible(bool $visible): static
    {
        $this->megaMenuVisible = $visible;
        return $this;
    }

    public function getMegaMenuPosition(): ?int
    {
        return $this->megaMenuPosition;
    }

    public function setMegaMenuPosition(?int $position): static
    {
        $this->megaMenuPosition = $position;
        return $this;
    }

    public function isMegaMenuChildFeatured(): bool
    {
        return $this->megaMenuChildFeatured;
    }

    public function setMegaMenuChildFeatured(bool $featured): static
    {
        $this->megaMenuChildFeatured = $featured;
        return $this;
    }

    public function getMegaMenuChildPosition(): ?int
    {
        return $this->megaMenuChildPosition;
    }

    public function setMegaMenuChildPosition(?int $position): static
    {
        $this->megaMenuChildPosition = $position;
        return $this;
    }

    public function isFlyoutColumnFeatured(): bool
    {
        return $this->flyoutColumnFeatured;
    }

    public function setFlyoutColumnFeatured(bool $featured): static
    {
        $this->flyoutColumnFeatured = $featured;
        return $this;
    }

    public function getFlyoutColumnPosition(): ?int
    {
        return $this->flyoutColumnPosition;
    }

    public function setFlyoutColumnPosition(?int $position): static
    {
        $this->flyoutColumnPosition = $position;
        return $this;
    }

    public function getFlyoutAdPosition(): ?string
    {
        return $this->flyoutAdPosition;
    }

    public function setFlyoutAdPosition(?string $position): static
    {
        $this->flyoutAdPosition = $position;
        return $this;
    }

}
