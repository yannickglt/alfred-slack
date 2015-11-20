<?php

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$bs = new AlfredSlack\Libs\Bootstrap();
$bs->run();
