<?php

namespace AlfredSlack\Config;

use AlfredSlack\Libs\Router;

Router::define(function ($router) {

	$router
		->route('--files :search', [
			'description' => 'List the files within the team',
			'controller' => 'config',
			'action' => 'getFiles'
		])
		->route('--stars :search', [
			'description' => 'List the items starred',
			'controller' => 'config',
			'action' => 'getStarredItems'
		])
		->route('--search :search', [
			'description' => 'Search both messages and files',
			'controller' => 'config',
			'action' => 'search'
		])
		->route('--presence :presence?', [
			'description' => 'Set the user presence (either active or away)',
			'controller' => 'config',
			'action' => 'listPresences'
		])
		->route('--mark', [
            'description' => 'Mark all as read',
			'controller' => 'config',
			'action' => 'markAllAsRead'
		])
		->route('--refresh', [
            'description' => 'Refresh the cache',
			'controller' => 'config',
			'action' => 'refreshCache'
		])
		->route('--token :token', [
            'description' => 'Set the Slack token in the keychain (recommended)',
            'controller' => 'config',
            'action' => 'saveToken'
        ])
		->route('--token-unsafe :token', [
            'description' => 'Set the Slack token in the cache instead of the keychain (not recommended)',
            'controller' => 'config',
            'action' => 'saveTokenUnsafe'
        ])
		->route('--:config?', [
			'controller' => 'config',
			'action' => 'listConfigs'
		])
		->route(':channel :message', [
			'controller' => 'channel',
			'action' => 'getChannels'
		])
		->route(':channel ', [
			'controller' => 'channel',
			'action' => 'getChannelHistory'
		])
		->route(':channel', [
			'controller' => 'channel',
			'action' => 'getChannels'
		]);
});
