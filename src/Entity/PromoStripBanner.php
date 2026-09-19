<?php

namespace App\Entity;

use App\Repository\PromoStripBannerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Bannières multiples de la zone "Bandeau promo" (accueil, entre "Comment ça marche"
 * et le reste). Contrairement au système générique Advertisement/AdZone (qui tire
 * UNE annonce au hasard), ici toutes les bannières actives s'affichent en même temps,
 * côte à côte, dans l'ordre choisi par l'admin.
 */
#[ORM\Entity(repositoryClass: PromoStripBannerRepository::class)]
#[Vich\Uploadable]
class PromoStripBanner
{
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

    #[ORM\Column]
    private int $position = 0;

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

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
