<?php

$phar = new Phar('rconverter.phar');
$phar->buildFromDirectory(__DIR__ . '/../');
$phar->setStub($phar->createDefaultStub('app/index.php'));
