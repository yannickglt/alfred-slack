<?php

require_once 'SlackController.php';

$controller = new SlackController();
$controller->{$action.'Action'}($query);