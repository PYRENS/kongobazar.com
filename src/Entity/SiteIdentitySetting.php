<?php

namespace App\Entity;

use App\Repository\SiteIdentitySettingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/** Singleton — l'image source du favicon/logo d'appli, à partir de laquelle toutes les tailles sont générées. */
#[ORM\Entity(repositoryClass: SiteIdentitySettingRepository::class)]
#[Vich\Uploadable]
class SiteIdentitySetting
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 1; // singleton : une seule ligne, id toujours 1

    #[Vich\UploadableField(mapping: 'site_identity_source', fileNameProperty: 'sourceImageName')]
    private ?File $sourceImageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $sourceImageName = null;

    #[ORM\Column(length: 7, options: ['default' => '#2FA8E0'])]
    private string $themeColor = '#2FA8E0';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $generatedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setSourceImageFile(?File $sourceImageFile = null): static
    {
        $this->sourceImageFile = $sourceImageFile;
        if ($sourceImageFile) {
            $this->generatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getSourceImageFile(): ?File
    {
        return $this->sourceImageFile;
    }

    public function getSourceImageName(): ?string
    {
        return $this->sourceImageName;
    }

    public function setSourceImageName(?string $sourceImageName): static
    {
        $this->sourceImageName = $sourceImageName;
        return $this;
    }

    public function getThemeColor(): string
    {
        return $this->themeColor;
    }

    public function setThemeColor(string $themeColor): static
    {
        $this->themeColor = $themeColor;
        return $this;
    }

    public function getGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(?\DateTimeImmutable $generatedAt): static
    {
        $this->generatedAt = $generatedAt;
        return $this;
    }
}
