<?php

namespace App\Entity;

use App\Repository\LicenseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
#[Vich\Uploadable]
class License
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: SellerProfile::class, inversedBy: 'license')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SellerProfile $sellerProfile = null;

    #[ORM\Column(length: 20)]
    private string $status = 'active'; // active | expiring | expired | deserteur

    #[ORM\Column]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endDate = null;

    // Le PDF officiel de la licence, généré à l'activation
    #[Vich\UploadableField(mapping: 'private_documents', fileNameProperty: 'pdfFileName')]
    private ?File $pdfFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $pdfFileName = null;

    // Traçabilité des alertes déjà envoyées, pour éviter les doublons (cron J-30/15/7/1)
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastAlertSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $lastAlertDaysBefore = null; // 30 | 15 | 7 | 1

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSellerProfile(): ?SellerProfile
    {
        return $this->sellerProfile;
    }

    public function setSellerProfile(?SellerProfile $sellerProfile): static
    {
        $this->sellerProfile = $sellerProfile;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function setPdfFile(?File $file = null): void
    {
        $this->pdfFile = $file;
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function setPdfFileName(?string $name): void
    {
        $this->pdfFileName = $name;
    }

    public function getPdfFileName(): ?string
    {
        return $this->pdfFileName;
    }

    public function getLastAlertSentAt(): ?\DateTimeImmutable
    {
        return $this->lastAlertSentAt;
    }

    public function setLastAlertSentAt(?\DateTimeImmutable $date): static
    {
        $this->lastAlertSentAt = $date;
        return $this;
    }

    public function getLastAlertDaysBefore(): ?int
    {
        return $this->lastAlertDaysBefore;
    }

    public function setLastAlertDaysBefore(?int $days): static
    {
        $this->lastAlertDaysBefore = $days;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}