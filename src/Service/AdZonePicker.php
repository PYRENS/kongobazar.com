<?php

namespace App\Service;

use App\Entity\Advertisement;
use App\Entity\Campaign;
use App\Entity\Category;
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

    /**
     * Bannière à intercaler avant le lot n° $batchIndex (1 = deuxième lot de la page) selon les
     * réglages de la campagne : interrupteur, fréquence, bannières choisies, mode aléatoire/ordre.
     * L'interrupteur de la zone dans Publicités reste prioritaire (zone désactivée = aucune bannière).
     */
    public function pickForCampaign(Campaign $campaign, string $zoneKey, int $batchIndex): ?Advertisement
    {
        if (!$campaign->isBatchBannersEnabled()) {
            return null;
        }

        $every = max(1, $campaign->getBatchBannerEvery());
        if ($batchIndex < 1 || 0 !== $batchIndex % $every) {
            return null;
        }

        $setting = $this->settingRepository->findOneByZoneKey($zoneKey);
        if ($setting && !$setting->isEnabled()) {
            return null;
        }

        // Toujours limité aux pubs actives et dans leur période, puis à la sélection de la campagne.
        $candidates = $this->advertisementRepository->findActiveByZone($zoneKey, 'public');
        $selected = $campaign->getBatchBannerAdIds();
        if ($selected) {
            $candidates = array_values(array_filter(
                $candidates,
                static fn (Advertisement $a) => in_array($a->getId(), $selected, true)
            ));
        }
        if (!$candidates) {
            return null;
        }

        $ad = 'order' === $campaign->getBatchBannerMode()
            ? $candidates[(intdiv($batchIndex, $every) - 1) % count($candidates)]
            : $candidates[array_rand($candidates)];

        $this->recordImpression($ad, $zoneKey);

        return $ad;
    }

    /**
     * Pub d'une zone liée à une catégorie, AVEC héritage : la catégorie elle-même, puis ses parentes
     * en remontant jusqu'au rayon, puis une pub généraliste (sans catégorie). Null si rien : la zone
     * disparaît sans laisser de trou. $excludeIds évite d'afficher 2 fois la même pub sur une page.
     */
    public function pickForCategory(string $zoneKey, Category $category, string $targetSpace = 'public', array $excludeIds = []): ?Advertisement
    {
        $setting = $this->settingRepository->findOneByZoneKey($zoneKey);
        if ($setting && !$setting->isEnabled()) {
            return null;
        }

        $levels = array_reverse($category->getAncestors()); // catégorie courante d'abord, puis ses parentes
        $levels[] = null;                                    // enfin, les pubs généralistes

        foreach ($levels as $level) {
            $candidates = array_values(array_filter(
                $this->advertisementRepository->findActiveByZoneAndRelatedCategory($zoneKey, $targetSpace, $level),
                static fn (Advertisement $a) => !in_array($a->getId(), $excludeIds, true)
            ));
            if (!$candidates) {
                continue;
            }

            $selected = null;
            if ($setting && 'fixed' === $setting->getMode() && $setting->getFixedAdvertisement()) {
                foreach ($candidates as $candidate) {
                    if ($candidate->getId() === $setting->getFixedAdvertisement()->getId()) {
                        $selected = $candidate;
                        break;
                    }
                }
            }
            $selected ??= $candidates[array_rand($candidates)];

            $this->recordImpression($selected, $zoneKey);

            return $selected;
        }

        return null;
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