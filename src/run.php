<?php

declare(strict_types=1);

use Keboola\Component\UserException;
use Keboola\HttpExtractor\HttpExtractorComponent;

require __DIR__ . '/../vendor/autoload.php';

try {
    $app = new HttpExtractorComponent();
    $app->run();
    exit(0);
} catch (UserException $e) {
    echo $e->getMessage();
    exit(1);
}
