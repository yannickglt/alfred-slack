<?php

namespace AlfredSlack\Config;

use AlfredSlack\Libs\Router;

Router::define(function ($router) {

	$router
		->route('--files :search', [
			'controller' => 'slack',
			'action' => 'getFiles'
		])
		->route('--stars :search', [
			'controller' => 'slack',
			'action' => 'getStarredItems'
		])
		->route('--search :search', [
			'controller' => 'slack',
			'action' => 'search'
		])
		->route('--presence :presence', [
			'controller' => 'slack',
			'action' => 'listPresences'
		])
		->route('--:config :param', [
			'controller' => 'slack',
			'action' => 'listConfigs'
		])
		->route('--:config', [
			'controller' => 'slack',
			'action' => 'listConfigs'
		])
		->route(':channel :message', [
			'controller' => 'slack',
			'action' => 'getChannels'
		])
		->route(':channel ', [
			'controller' => 'slack',
			'action' => 'getChannelHistory'
		])
		->route(':channel', [
			'controller' => 'slack',
			'action' => 'getChannels'
		]);
});
