<?php

declare(strict_types=1);

namespace viavario\compsyclient;

/**
 * Client for searching psychologists on www.compsy.be
 * by Compsy registration number.
 */
class CompsyClient
{
    private const BASE_URL    = 'https://www.compsy.be';
    private const SEARCH_PATH = '/nl_BE/search';
    private const TIMEOUT     = 15;

    /**
     * Search by Compsy registration number.
     *
     * @param  string $registrationNumber  E.g. "731106598"
     * @return CompsyResult|null
     */
    public function searchByRegistrationNumber(string $registrationNumber): ?CompsyResult
    {
        return $this->search([
            'search_term'         => '',
            'registration_number' => $registrationNumber,
            'postal_code'         => '',
        ]);
    }

    /**
     * Perform the POST request and parse results.
     *
     * @param  array<string, string> $params
     * @return CompsyResult|null
     *
     * @throws RuntimeException on request failure
     */
    private function search(array $params): ?CompsyResult
    {
        $html = $this->post(self::BASE_URL . self::SEARCH_PATH, $params);

        return $this->parseHtml($html);
    }

    /**
     * Execute a POST request and return the response body.
     *
     * @param  string                $url
     * @param  array<string, string> $fields
     * @return string
     *
     * @throws RuntimeException
     */
    private function post(string $url, array $fields): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; CompsyClient/1.0)',
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("cURL request failed: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Unexpected HTTP status code: {$httpCode}");
        }

        return (string) $response;
    }

    /**
     * Parse the HTML response and extract psychologist results.
     *
     * @param  string $html
     * @return CompsyResult|null
     */
    private function parseHtml(string $html): ?CompsyResult
    {
        $dom = new \DOMDocument();

        // Suppress warnings from malformed HTML and load with UTF-8 encoding hint
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Each result card is an element containing .psy-name, .psy-detail-link, and .psy-item-infos
        $items = $xpath->query('//*[contains(@class, "psy-detail-link")]');

        if ($items === false || $items->length === 0) {
            return null;
        }

        /** @var \DOMNodeList<\DOMElement> $items */
        foreach ($items as $linkNode) {
            // 1. Detail URL from the <a class="psy-detail-link"> href attribute
            $relativeUrl = $linkNode->getAttribute('href');
            $detailUrl   = $relativeUrl ? self::BASE_URL . '/' . ltrim($relativeUrl, '/') : '';

            // Walk up to the result card container (common ancestor)
            $card = $linkNode->parentNode;
            while ($card !== null && $card->nodeName !== 'body') {
                /** @var \DOMElement $card */
                $cardClass = $card->getAttribute('class') ?? '';
                if (strpos($cardClass, 'psy-item') !== false) {
                    break;
                }
                $card = $card->parentNode;
            }

            if ($card === null) {
                continue;
            }

            // 2. Name from .psy-name inside the card
            $nameNodes = $xpath->query('.//*[contains(@class, "psy-name")]', $card);
            $name      = $nameNodes && $nameNodes->length > 0 ? trim($nameNodes->item(0)->textContent) : '';

            // 3. Status from .psy-item-infos inside the card
            $statusNodes = $xpath->query('.//*[contains(@class, "psy-item-infos")]', $card);
            $status      = $statusNodes && $statusNodes->length > 0 ? trim($statusNodes->item(0)->textContent) : '';

            return new CompsyResult(
                $name,
                $detailUrl,
                $status
            );
        }

        return null;
    }
}
