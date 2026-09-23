<?php

namespace App\Service;

use App\Repository\ExchangeRateRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Conversion et affichage des montants dans la devise choisie par le visiteur (sélecteur USD/CDF du header).
 * Utilisé par le filtre Twig |price et par les réponses JSON du panier (mêmes chaînes affichées partout).
 */
class PriceFormatter
{
    private bool $rateLoaded = false;
    private ?string $rateUsdToCdf = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ExchangeRateRepository $exchangeRateRepository,
    ) {
    }

    /** Convertit dans la devise choisie par le visiteur (si un taux existe), puis formate. */
    public function display(mixed $amount, string $fromCurrency = 'USD'): string
    {
        if (null === $amount || '' === $amount) {
            return '';
        }

        $amount = (string) $amount;
        $from = 'CDF' === $fromCurrency ? 'CDF' : 'USD';
        $target = $this->getTargetCurrency();

        // Même devise, ou aucun taux exploitable : on garde la devise d'origine (jamais de mélange)
        $rate = $this->getRate();
        if ($from === $target || null === $rate) {
            return $this->format($amount, $from);
        }

        $converted = 'CDF' === $target
            ? bcmul($amount, $rate, 4)   // USD -> CDF
            : bcdiv($amount, $rate, 4);  // CDF -> USD

        return $this->format($converted, $target);
    }

    /** Formate sans convertir : "232 955 CDF" ou "1 250.00 USD" (espaces insécables). */
    public function format(mixed $amount, string $currency): string
    {
        if (null === $amount || '' === $amount) {
            return '';
        }

        $nbsp = "\u{00A0}";

        return 'CDF' === $currency
            ? number_format(round((float) $amount), 0, '', $nbsp) . $nbsp . 'CDF'
            : number_format((float) $amount, 2, '.', $nbsp) . $nbsp . 'USD';
    }

    private function getTargetCurrency(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $currency = ($request && $request->hasSession()) ? $request->getSession()->get('_currency', 'USD') : 'USD';

        return 'CDF' === $currency ? 'CDF' : 'USD';
    }

    /** Taux courant (1 USD = X CDF), chargé une seule fois par requête ; null si absent ou invalide. */
    private function getRate(): ?string
    {
        if (!$this->rateLoaded) {
            $this->rateLoaded = true;
            $value = $this->exchangeRateRepository->findCurrentRate()?->getRateUsdToCdf();
            $this->rateUsdToCdf = ($value && (float) $value > 0) ? $value : null;
        }

        return $this->rateUsdToCdf;
    }
}