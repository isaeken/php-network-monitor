#!/usr/bin/php
<?php

use Illuminate\Support\Str;
use IsaEken\NetworkMonitor\NetworkInterface;
use IsaEken\NetworkMonitor\NetworkMonitor;

require_once __DIR__ . '/../vendor/autoload.php';

if (php_sapi_name() !== 'cli') {
    throw new Exception('This file only runs in cli mode.');
}

if (! function_exists('__print')) {
    function __print(string $line = ''): string {
        print_r($line . PHP_EOL);
        return $line;
    }
}

if (! isset($argv[1])) {
    $interfaces = implode(', ', array_map(function(NetworkInterface $interface) {
        return $interface->getName();
    }, NetworkMonitor::getInterfaces()->toArray()));

    __print('-- Network Monitor --');
    __print('Usage: php ' . __FILE__ . ' <interface> <?refresh>');
    __print(PHP_EOL . 'Interfaces: ' . $interfaces);
    __print();
    exit;
}

if (! NetworkMonitor::hasInterface($argv[1])) {
    throw new Exception(sprintf('The interface "%s" is not exists.', $argv[1]));
}

$refresh = isset($argv[2]) && $argv[2] === 'refresh';
$interface = NetworkMonitor::findInterface($argv[1]);

function print_statistics(NetworkInterface $interface) {
    foreach ($interface->statistics() as $key => $value) {
        __print(sprintf('%s~%s', Str::of($key)->padRight(40), $value));
    }
}

if ($refresh) {
    while (true) {
        system('clear');
        print_statistics($interface);
        __print(PHP_EOL . 'Waiting 1 seconds...');
        sleep(1);
    }
}
else {
    print_statistics($interface);
}
