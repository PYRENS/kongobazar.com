<?php

namespace App\Entity;

use App\Repository\HomeCategoryBlockSettingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne = un bloc catégorie de l'accueil (ex: "Mode", "Électronique"...).
 * La catégorie elle-même ET ses sous-catégories affichées dans le menu de gauche
 * sont choisies par cascade — n'importe quel niveau de l'arbre, pas juste les racines.
 */
#[ORM\Entity(repositoryClass: HomeCategoryBlockSettingRepository::class)]
#[ORM\Table(name: 'home_category_block_setting')]
class HomeCategoryBlockSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private int $position = 0;

    /** Sous-catégories affichées dans le menu de gauche de ce bloc, réordonnables une par une (voir HomeCategoryBlockSubcategory). */
    #[ORM\OneToMany(mappedBy: 'block', targetEntity: HomeCategoryBlockSubcategory::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $subcategoryItems;

    /** Onglets de tri (Meilleures ventes / Nouveaux articles / Vedettes / Tendance), ordre et nombre de produits réglables par bloc. */
    #[ORM\OneToMany(mappedBy: 'block', targetEntity: HomeCategoryBlockSortTab::class, orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sortTabs;

    public function __construct()
    {
        $this->subcategoryItems = new ArrayCollection();
        $this->sortTabs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    /** @return Collection<int, HomeCategoryBlockSubcategory> */
    public function getSubcategoryItems(): Collection
    {
        return $this->subcategoryItems;
    }

    /** @return Collection<int, HomeCategoryBlockSortTab> */
    public function getSortTabs(): Collection
    {
        return $this->sortTabs;
    }
}
