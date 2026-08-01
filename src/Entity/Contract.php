<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[Vich\Uploadable]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SellerProfile::class, inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?SellerProfile $sellerProfile = null;

    #[ORM\Column(length: 20)]
    private string $type = 'partnership'; // partnership (seul type signé, cf. cadrage 3.2)

    #[ORM\Column(length: 20)]
    private string $status = 'sent'; // sent | signed | validated | rejected

    // Le PDF généré par la plateforme (contrat pré-rempli, envoyé au partenaire)
    #[Vich\UploadableField(mapping: 'private_documents', fileNameProperty: 'generatedFileName')]
    private ?File $generatedFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $generatedFileName = null;

    // Le PDF signé, retourné par le partenaire
    #[Vich\UploadableField(mapping: 'private_documents', fileNameProperty: 'signedFileName')]
    private ?File $signedFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $signedFileName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $signedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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

    public function setGeneratedFile(?File $file = null): void
    {
        $this->generatedFile = $file;
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getGeneratedFile(): ?File
    {
        return $this->generatedFile;
    }

    public function setGeneratedFileName(?string $name): void
    {
        $this->generatedFileName = $name;
    }

    public function getGeneratedFileName(): ?string
    {
        return $this->generatedFileName;
    }

    public function setSignedFile(?File $file = null): void
    {
        $this->signedFile = $file;
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getSignedFile(): ?File
    {
        return $this->signedFile;
    }

    public function setSignedFileName(?string $name): void
    {
        $this->signedFileName = $name;
    }

    public function getSignedFileName(): ?string
    {
        return $this->signedFileName;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}