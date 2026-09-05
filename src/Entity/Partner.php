<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/** Un partenaire de l'accueil (logo + lien) — affichage distinct des marques sponsors. */
#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[Vich\Uploadable]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[Vich\UploadableField(mapping: 'partner_logos', fileNameProperty: 'logoName')]
    private ?File $logoFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $logoName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    public function setLogoFile(?File $file = null): void
    {
        $this->logoFile = $file;
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLogoFile(): ?File { return $this->logoFile; }

    public function setLogoName(?string $name): void { $this->logoName = $name; }
    public function getLogoName(): ?string { return $this->logoName; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}
