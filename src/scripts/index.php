<?php

//error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$config = [
	'query' => $query,
	'input' => isset($input) ? ($input === true) : false,
	'modifier' => isset($modifier) ? $modifier : null
];

$bs = new AlfredSlack\Libs\Bootstrap();
$bs->run($config);
