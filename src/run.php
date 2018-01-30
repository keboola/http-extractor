<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use Keboola\HttpExtractor\Exception\UserException;
use Keboola\HttpExtractor\HttpExtractorApplication;

require __DIR__ . '/../vendor/autoload.php';

$dataDir = getenv('KBC_DATADIR') === false ? '/data/' : getenv('KBC_DATADIR');
$configPath = $dataDir . 'config.json';
$config = \Keboola\HttpExtractor\Config::fromFile($configPath);

try {
    $client = new Client();
    $httpExtractor = new Keboola\HttpExtractor\HttpExtractor($client);
    $app = new HttpExtractorApplication($config, $httpExtractor, $dataDir);
    $app->extract();
    exit(0);
} catch (UserException $e) {
    echo $e->getMessage();
    exit(1);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(2);
}
