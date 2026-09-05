<?php

namespace App\Service;

use App\Entity\Advertisement;
use App\Repository\AdvertisementRepository;
use App\Repository\AdZoneSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

/** Choisit quelle pub afficher pour une zone à emplacement unique, selon le réglage Aléatoire/Fixe de l'admin. */
class AdZonePicker
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly AdZoneSettingRepository $settingRepository,
        private readonly \App\Repository\AdvertisementZonePlacementRepository $placementRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function pick(string $zoneKey, string $targetSpace = 'public'): ?Advertisement
    {
        $setting = $this->settingRepository->findOneByZoneKey($zoneKey);
        if ($setting && !$setting->isEnabled()) {
            return null;
        }

        // Toujours limité aux pubs actives et dans leur période — quel que soit le mode.
        $activeCandidates = $this->advertisementRepository->findActiveByZone($zoneKey, $targetSpace);
        if (empty($activeCandidates)) {
            return null;
        }

        if ($setting && 'fixed' === $setting->getMode() && $setting->getFixedAdvertisement()) {
            $fixed = $setting->getFixedAdvertisement();
            foreach ($activeCandidates as $candidate) {
                if ($candidate->getId() === $fixed->getId()) {
                    $this->recordImpression($candidate, $zoneKey);
                    return $candidate;
                }
            }
            $fallback = $activeCandidates[0];
            $this->recordImpression($fallback, $zoneKey);
            return $fallback;
        }

        $selected = $activeCandidates[array_rand($activeCandidates)];
        $this->recordImpression($selected, $zoneKey);
        return $selected;
    }

    private function recordImpression(Advertisement $ad, string $zoneKey): void
    {
        $placement = $this->placementRepository->findOneByAdvertisementAndZone($ad->getId(), $zoneKey);
        if ($placement) {
            $placement->incrementImpressionCount();
            $this->em->flush();
        }
    }

    /** @param Advertisement[] $ads Enregistre un affichage pour chaque pub d'une zone qui en montre plusieurs en même temps (carrousel, mosaïque, méga-menu). */
    public function recordImpressions(array $ads, string $zoneKey): void
    {
        foreach ($ads as $ad) {
            $placement = $this->placementRepository->findOneByAdvertisementAndZone($ad->getId(), $zoneKey);
            if ($placement) {
                $placement->incrementImpressionCount();
            }
        }
        $this->em->flush();
    }
}