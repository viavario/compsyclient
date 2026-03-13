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
}