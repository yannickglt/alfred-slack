<?php

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$input = isset($input) ? ($input === true) : false;
$modifier = isset($modifier) ? $modifier : null;
$query = isset($query) ? $query : null;

$config = new AlfredSlack\Libs\Query($query, $input, $modifier);
$bs = new AlfredSlack\Libs\Bootstrap();
$bs->run($config);
