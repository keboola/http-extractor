<?php

use Keboola\HttpExtractor\Exception\UserException;

require __DIR__ . '/../vendor/autoload.php';


$dataDir = getenv('KBC_DATADIR') === false ? '/data/' : getenv('KBC_DATADIR');
$configPath = $dataDir . '/config.json';
$config = \Keboola\HttpExtractor\Config::fromFile($configPath);

try {
    exit(0);
} catch (UserException $e) {
    echo $e->getMessage();
    exit(1);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(2);
}
