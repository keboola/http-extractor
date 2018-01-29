<?php

require __DIR__ . '/../vendor/autoload.php';


$arguments = getopt("d::", ["data:"]);
if (!isset($arguments['data'])) {
    echo 'Data folder not set.' . "\n";
    exit(1);
}

try {
    exit(0);
} catch (Exception $e) {
    echo $e->getMessage();
    exit(1);
}
