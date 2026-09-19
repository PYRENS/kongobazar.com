<?php

namespace App\Entity;

use App\Repository\SidebarFillerBannerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Bannières "bouche-trou" de la colonne gauche de l'accueil — empilées en bas de
 * colonne, dans un ordre aléatoire, jusqu'à combler l'écart de hauteur avec la
 * colonne centrale (plus haute). Largeur fixe imposée à l'upload (270px, comme
 * les autres bannières de cette colonne) ; la hauteur, elle, est libre.
 */
#[ORM\Entity(repositoryClass: SidebarFillerBannerRepository::class)]
#[Vich\Uploadable]
class SidebarFillerBanner
{
    public const REQUIRED_WIDTH = 270;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $title = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $targetUrl = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $openInNewTab = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function setImageFile(?File $imageFile = null): static
    {
        $this->imageFile = $imageFile;
        return $this;
    }
    public function getImageFile(): ?File { return $this->imageFile; }

    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): static { $this->imageName = $imageName; return $this; }

    public function getTargetUrl(): ?string { return $this->targetUrl; }
    public function setTargetUrl(?string $targetUrl): static { $this->targetUrl = $targetUrl; return $this; }

    public function isOpenInNewTab(): bool { return $this->openInNewTab; }
    public function setOpenInNewTab(bool $v): static { $this->openInNewTab = $v; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }
}
