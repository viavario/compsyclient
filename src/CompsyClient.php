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

        return $this->parseHtml($html, $params['registration_number'] ?? null);
    }

    /**
     * Fetch the detail page for a result and populate its registration periods.
     *
     * @param  CompsyResult $result  A result obtained from searchByRegistrationNumber().
     * @return CompsyResult          The same result, enriched with registration periods.
     *
     * @throws \RuntimeException on request failure
     */
    public function fetchDetail(CompsyResult $result): CompsyResult
    {
        $html = $this->get($result->detailUrl);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath   = new \DOMXPath($dom);
        $listing = $xpath->query('//*[contains(@class, "year-listing-text")]');

        $periods = [];

        if ($listing !== false && $listing->length > 0) {
            foreach ($listing as $node) {
                /** @var \DOMElement $node */
                $spans = $xpath->query('.//span', $node);

                if ($spans === false || $spans->length < 2) {
                    continue;
                }

                $rawStart = trim($spans->item(0)->textContent);
                $rawEnd   = trim($spans->item(1)->textContent);

                $start = \DateTimeImmutable::createFromFormat('d-m-Y', $rawStart);
                $end   = \DateTimeImmutable::createFromFormat('d-m-Y', $rawEnd);

                if ($start === false || $end === false) {
                    continue;
                }

                // DatePeriod's end is exclusive, so add 1 day to include the actual end date
                $periods[] = new \DatePeriod($start, new \DateInterval('P1D'), $end->modify('+1 day'));
            }
        }

        $result->setRegistrationPeriods($periods);

        return $result;
    }

    /**
     * Execute a GET request and return the response body.
     *
     * @param  string $url
     * @return string
     *
     * @throws \RuntimeException
     */
    private function get(string $url): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
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
     * @param  string|null $registrationNumber  Optional registration number to match in results.
     * @return CompsyResult|null
     */
    private function parseHtml(string $html, ?string $registrationNumber = null): ?CompsyResult
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
                $registrationNumber ?? '',
                $name,
                $detailUrl,
                $status
            );
        }

        return null;
    }
}
