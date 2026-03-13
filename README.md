# viavario/compsyclient

A lightweight PHP client for looking up psychologists on [compsy.be](https://www.compsy.be) by their Compsy registration number.

## Requirements

- PHP `^7.4 | ^8.0`
- Extensions: `ext-curl`, `ext-dom`

## Installation

```bash
composer require viavario/compsyclient
```

## Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use viavario\compsyclient\CompsyClient;

$client = new CompsyClient();

// Search by Compsy registration number
$result = $client->searchByRegistrationNumber('731106598');

if ($result !== null) {
    echo "Name:       " . $result->name      . PHP_EOL;
    echo "Detail URL: " . $result->detailUrl . PHP_EOL;
    echo "Status:     " . $result->status    . PHP_EOL;
    echo "Is active:  " . ($result->isActive() ? 'Yes' : 'No') . PHP_EOL;
    echo str_repeat('-', 40) . PHP_EOL;

    echo "Registration periods:" . PHP_EOL;
    foreach ($result->getRegistrationPeriods() as $period) {
        $start = $period->getStartDate()->format('Y-m-d');
        $end   = $period->getEndDate()->modify('-1 day')->format('Y-m-d'); // adjust for DatePeriod exclusive end
        echo "  {$start} - {$end}" . PHP_EOL;
    }

    $lastEnd = $result->getLastRegistrationEndDate();
    echo "Last registration end: " . ($lastEnd ? $lastEnd->format('Y-m-d') : 'N/A') . PHP_EOL;
} else {
    echo "No psychologist found for this registration number." . PHP_EOL;
}
```

## API

### `CompsyClient`

#### `searchByRegistrationNumber(string $registrationNumber): ?CompsyResult`

Searches compsy.be for a psychologist by their registration number. Returns a `CompsyResult` on success, or `null` if no match was found. Throws a `\RuntimeException` if the HTTP request fails.

#### `fetchDetail(CompsyResult $result): CompsyResult`

Fetches the detail page for a result and populates its registration periods. Returns the same result object, enriched with registration data. Throws a `\RuntimeException` if the HTTP request fails.

---

### `CompsyResult`

The result object returned by a successful search.

| Property | Type | Description |
|---|---|---|
| `$registration_number` | `string` | The Compsy registration number |
| `$name` | `string` | The psychologist's full name |
| `$detailUrl` | `string` | Absolute URL to the psychologist's detail page on compsy.be |
| `$status` | `string` | Registration status as returned by the Compsy website |

#### `isActive(): bool`

Returns `true` if `$status` equals `"active"` (case-insensitive).

#### `getRegistrationPeriods(): \DatePeriod[]`

Returns all registration periods for the psychologist. These are only populated after calling `CompsyClient::fetchDetail()`. Each `\DatePeriod` represents a date range during which the person was a registered counselor.

#### `getLastRegistrationEndDate(): ?\DateTimeImmutable`

Returns the end date of the most recent registration period, or `null` if no periods are available. Only populated after calling `CompsyClient::fetchDetail()`.

#### `toArray(): array`

Returns the result as an associative array:

```php
[
    'registration_number' => '99999999',
    'name'                => 'Dr. Jane Doe',
    'detail_url'          => 'https://www.compsy.be/nl_BE/psychologist/jane-doe',
    'status'              => 'Active',
    'is_active'           => true,
]
```

## Development

Install dependencies:

```bash
composer install
```

Run the test suite:

```bash
./vendor/bin/phpunit
```

## License

MIT — see [LICENSE](LICENSE) for details.
