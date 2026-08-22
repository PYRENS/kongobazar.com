<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[Vich\Uploadable]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 20)]
    private string $status = 'active'; // active | pending | suspended | banned

    #[ORM\Column(length: 5, options: ['default' => 'fr'])]
    private string $preferredLocale = 'fr';

    #[ORM\ManyToOne(targetEntity: AdministrativeUnit::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdministrativeUnit $administrativeUnit = null;

    #[ORM\Column(length: 3, options: ['default' => 'USD'])]
    private string $preferredCurrency = 'USD'; // USD | CDF

    #[ORM\Column(nullable: true)]
    private ?float $gpsLat = null;

    #[ORM\Column(nullable: true)]
    private ?float $gpsLng = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $adminSidebarColor = null;

    #[ORM\ManyToOne(targetEntity: AdminSidebarTheme::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AdminSidebarTheme $adminSidebarTheme = null;

    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'avatarName')]
    private ?File $avatarFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $avatarName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
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

    public function getAdministrativeUnit(): ?AdministrativeUnit
    {
        return $this->administrativeUnit;
    }

    public function setAdministrativeUnit(?AdministrativeUnit $administrativeUnit): static
    {
        $this->administrativeUnit = $administrativeUnit;
        return $this;
    }

    public function getGpsLat(): ?float
    {
        return $this->gpsLat;
    }

    public function setGpsLat(?float $gpsLat): static
    {
        $this->gpsLat = $gpsLat;
        return $this;
    }

    public function getGpsLng(): ?float
    {
        return $this->gpsLng;
    }

    public function setGpsLng(?float $gpsLng): static
    {
        $this->gpsLng = $gpsLng;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPreferredCurrency(): string
    {
        return $this->preferredCurrency;
    }

    public function setPreferredCurrency(string $preferredCurrency): static
    {
        $this->preferredCurrency = $preferredCurrency;
        return $this;
    }

    public function getPreferredLocale(): string
    {
        return $this->preferredLocale;
    }

    public function setPreferredLocale(string $preferredLocale): static
    {
        $this->preferredLocale = $preferredLocale;
        return $this;
    }

    public function getAdminSidebarColor(): ?string
    {
        return $this->adminSidebarColor;
    }

    public function setAdminSidebarColor(?string $color): static
    {
        $this->adminSidebarColor = $color;
        return $this;
    }

    public function getAdminSidebarTheme(): ?AdminSidebarTheme
    {
        return $this->adminSidebarTheme;
    }

    public function setAdminSidebarTheme(?AdminSidebarTheme $theme): static
    {
        $this->adminSidebarTheme = $theme;
        return $this;
    }

    public function setAvatarFile(?File $file = null): void
    {
        $this->avatarFile = $file;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarName(?string $name): void
    {
        $this->avatarName = $name;
    }

    public function getAvatarName(): ?string
    {
        return $this->avatarName;
    }

    public function getInitials(): string
    {
        if ($this->firstName && $this->lastName) {
            return strtoupper(substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1));
        }
        return strtoupper(substr($this->email ?? '?', 0, 2));
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): ?string
    {
        if (!$this->firstName && !$this->lastName) {
            return null;
        }
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

}
