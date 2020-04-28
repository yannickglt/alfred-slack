<?php

namespace AlfredSlack\Config;

use AlfredSlack\Libs\Router;

Router::define(function ($router) {

  $router
    ->route('--files :search?', [
      'controller' => 'config',
      'action' => 'getFiles'
    ])
    ->route('--stars :search?', [
      'controller' => 'config',
      'action' => 'getStarredItems'
    ])
    ->route('--search :search', [
      'controller' => 'config',
      'action' => 'search'
    ])
    ->route('--presence :presence?', [
      'controller' => 'config',
      'action' => 'listPresences'
    ])
    ->route('--status :search?', [
      'controller' => 'config',
      'action' => 'getStatuses'
    ])
    ->route('--remove-client :search?', [
      'controller' => 'config',
      'action' => 'getTeams'
    ])
    ->route('--:config :param', [
      'controller' => 'config',
      'action' => 'listConfigs'
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
