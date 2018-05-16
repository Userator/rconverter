<?php

require __DIR__ . '/../vendor/autoload.php';

$application = new \Symfony\Component\Console\Application('rconverter');
$application->add(new \Command\Convert());
$application->run();
