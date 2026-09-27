<?php

namespace App\Entity;

use App\Repository\LoginBackgroundImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Photo de fond de la page de connexion (Acheteur). Une image active est tirée au
 * hasard à chaque affichage de la page ; elle n'est jamais cliquable.
 */
#[ORM\Entity(repositoryClass: LoginBackgroundImageRepository::class)]
#[Vich\Uploadable]
class LoginBackgroundImage
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

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    /**
     * Pages publiques sur lesquelles cette image peut être utilisée. Ajouter une page ici
     * (ex. "forgot_password" => "Mot de passe oublié") suffit à la proposer dans l'admin —
     * il faut ensuite appeler pickActiveRandom() avec cette clé sur la page concernée.
     */
    public const PAGES = [
        'login' => 'Page de connexion',
        'register' => 'Page d\'inscription',
        'complete_profile' => 'Compléter le profil',
    ];

    /** @var string[] */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $usedOnPages = ['login'];

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

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $v): static { $this->active = $v; return $this; }

    /** @return string[] */
    public function getUsedOnPages(): array { return $this->usedOnPages ?? ['login']; }

    /** @param string[] $pages */
    public function setUsedOnPages(array $pages): static
    {
        $this->usedOnPages = array_values(array_intersect($pages, array_keys(self::PAGES)));
        return $this;
    }

    public function isUsedOnPage(string $page): bool
    {
        return in_array($page, $this->usedOnPages, true);
    }
}