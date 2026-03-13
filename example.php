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
        $end   = $period->getEndDate()->format('Y-m-d');
        echo "  {$start} - {$end}" . PHP_EOL;
    }

    $lastEnd = $result->getLastRegistrationEndDate();
    echo "Last registration end: " . ($lastEnd ? $lastEnd->format('Y-m-d') : 'N/A') . PHP_EOL;
} else {
    echo "No psychologist found with the given registration number." . PHP_EOL;
}