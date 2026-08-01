<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProProfile extends SellerProfile
{
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $serviceDescription = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $professionalDocumentNumber = null;

    public function getServiceDescription(): ?string
    {
        return $this->serviceDescription;
    }

    public function setServiceDescription(?string $serviceDescription): static
    {
        $this->serviceDescription = $serviceDescription;
        return $this;
    }

    public function getProfessionalDocumentNumber(): ?string
    {
        return $this->professionalDocumentNumber;
    }

    public function setProfessionalDocumentNumber(?string $number): static
    {
        $this->professionalDocumentNumber = $number;
        return $this;
    }
}