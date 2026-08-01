<?php
// src/Service/ExchangeRateFetcher.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateFetcher
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey,
    ) {
    }

    public function fetchUsdToCdf(): ?string
    {
        $response = $this->httpClient->request(
            'GET',
            "https://v6.exchangerate-api.com/v6/{$this->apiKey}/pair/USD/CDF"
        );

        $data = $response->toArray(false);

        if (($data['result'] ?? null) !== 'success') {
            return null;
        }

        return (string) $data['conversion_rate'];
    }
}