<?php

namespace App\Repository;

use App\Entity\FooterSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FooterSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FooterSetting::class);
    }

    public function getSingleton(): FooterSetting
    {
        $setting = $this->find(1);
        if (!$setting) {
            $setting = new FooterSetting();
            $setting->setCompanyDescription('KongoBazar, la marketplace de référence en RDC. Achetez et vendez en toute confiance, partout dans le pays.');
            $setting->setPhone('+243 000 000 000');
            $setting->setPhoneAvailability('24h/24, 7j/7');
            $setting->setAddress('Kinshasa, Gombe — RDC');
            $em = $this->getEntityManager();
            $em->persist($setting);
            $em->flush();
        }
        return $setting;
    }
}
