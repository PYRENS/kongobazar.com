<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Un article d'une version de document légal, avec son statut de changement par rapport à la version précédente. */
#[ORM\Entity]
class LegalArticle
{
    public const CHANGE_UNCHANGED = 'unchanged';
    public const CHANGE_ADDED = 'added';
    public const CHANGE_MODIFIED = 'modified';
    public const CHANGE_REMOVED = 'removed';

    public const CHANGE_LABELS = [
        self::CHANGE_UNCHANGED => 'Inchangé',
        self::CHANGE_ADDED => 'Nouveau',
        self::CHANGE_MODIFIED => 'Modifié',
        self::CHANGE_REMOVED => 'Supprimé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LegalDocumentVersion::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?LegalDocumentVersion $version = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(length: 200)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $body = '';

    #[ORM\Column(length: 20, options: ['default' => self::CHANGE_UNCHANGED])]
    private string $changeType = self::CHANGE_UNCHANGED;

    public function getId(): ?int { return $this->id; }

    public function getVersion(): ?LegalDocumentVersion { return $this->version; }
    public function setVersion(?LegalDocumentVersion $v): static { $this->version = $v; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $v): static { $this->position = $v; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }

    public function getBody(): string { return $this->body; }
    public function setBody(string $v): static { $this->body = $v; return $this; }

    public function getChangeType(): string { return $this->changeType; }
    public function setChangeType(string $v): static
    {
        $this->changeType = array_key_exists($v, self::CHANGE_LABELS) ? $v : self::CHANGE_UNCHANGED;
        return $this;
    }
}