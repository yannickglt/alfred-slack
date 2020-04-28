<?php

namespace AlfredSlack\Libs;

class SlackRouter {

  private static $routingOrder = [
    [
      'name' => 'cacheLocked',
      'type' => 'input'
    ],
    [
      'name' => 'getFiles',
      'type' => 'input'
    ],
    [
      'name' => 'getStarredItems',
      'type' => 'input'
    ],
    [
      'name' => 'search',
      'type' => 'input'
    ],
    [
      'name' => 'listPresences',
      'type' => 'input'
    ],
    [
      'name' => 'listConfigs',
      'type' => 'input'
    ],
    [
      'name' => 'listTeams',
      'type' => 'input'
    ],
    [
      'name' => 'getChannelsWithMessage',
      'type' => 'input'
    ],
    [
      'name' => 'getChannelHistory',
      'type' => 'input'
    ],
    [
      'name' => 'getChannels',
      'type' => 'input'
    ],
    [
      'name' => 'saveClient',
      'type' => 'output'
    ],
    [
      'name' => 'removeClient',
      'type' => 'output'
    ],
    [
      'name' => 'saveTokenUnsafe',
      'type' => 'output'
    ],
    [
      'name' => 'setPresence',
      'type' => 'output'
    ],
    [
      'name' => 'saveToken',
      'type' => 'output'
    ],
    [
      'name' => 'refreshCache',
      'type' => 'output'
    ],
    [
      'name' => 'markAllAsRead',
      'type' => 'output'
    ],
    [
      'name' => 'openFile',
      'type' => 'output'
    ],
    [
      'name' => 'sendMessage',
      'type' => 'output'
    ],
    [
      'name' => 'openChannel',
      'type' => 'output'
    ]
  ];

  public static function getAction($input, $query) {
    $router = new SlackRouter();
    $type = $input ? 'input' : 'output';
    foreach (static::$routingOrder as $action) {
      if ($action['type'] === $type) {
        $res = $router->{'check' . ucfirst($action['name'])}($query);
        if (!empty($res)) {
          return (object)$res;
        }
      }
    }
  }

  private function __construct() {

  }

  private function checkGetChannels($channel) {
    return [
      'action' => 'getChannels',
      'params' => [$channel]
    ];
  }

  private function checkCacheLocked($channel) {
    if (Utils::getWorkflows()->readPath('cache.lock') !== false) {
      return [
        'action' => 'getCacheLockedMessage'
      ];
    }
  }

  private function checkGetChannelsWithMessage($query) {
    $firstSpace = strpos($query, ' ');
    if (($firstSpace !== false) && ($firstSpace < strlen($query) - 1)) {
      $channel = substr($query, 0, $firstSpace);
      $message = substr($query, $firstSpace + 1);

      return [
        'action' => 'getChannels',
        'params' => [$channel, $message]
      ];
    }
  }

  private function checkGetChannelHistory($query) {
    $firstSpace = strpos($query, ' ');
    if (($firstSpace !== false) && ($firstSpace === strlen($query) - 1)) {
      $channel = substr($query, 0, $firstSpace);

      return [
        'action' => 'getChannelHistory',
        'params' => [$channel]
      ];
    }
  }

  private function checkGetFiles($query) {
    $firstSpace = strpos($query, ' ');
    if (substr($query, 0, $firstSpace) === '--files') {
      $search = substr($query, $firstSpace + 1);

      return [
        'action' => 'getFiles',
        'params' => [$search]
      ];
    }
  }

  private function checkGetStarredItems($query) {
    $firstSpace = strpos($query, ' ');
    if (substr($query, 0, $firstSpace) === '--stars') {
      $search = substr($query, $firstSpace + 1);

      return [
        'action' => 'getStarredItems',
        'params' => [$search]
      ];
    }
  }

  private function checkSearch($query) {
    $firstSpace = strpos($query, ' ');
    if (substr($query, 0, $firstSpace) === '--search') {
      $search = substr($query, $firstSpace + 1);

      return [
        'action' => 'search',
        'params' => [$search]
      ];
    }
  }

  private function checkListConfigs($query) {
    $arr = explode(' ', $query);
    $configAction = $arr[0];

    if (strpos($configAction, '--') === 0) {
      return [
        'action' => 'listConfigs',
        'params' => $arr
      ];
    }
  }

  private function checkListPresences($query) {
    $arr = explode(' ', $query);
    $configAction = $arr[0];

    if (strpos($configAction, '--presence') === 0) {
      return [
        'action' => 'listPresences',
        'params' => array_slice($arr, 1)
      ];
    }
  }

  private function checkOpenChannel($query) {
    $channelData = json_decode($query);

    return [
      'action' => 'openChannel',
      'params' => [$channelData]
    ];
  }

  private function checkSendMessage($query) {
    $channelData = json_decode($query);

    if (!empty($channelData->message)) {
      return [
        'action' => 'sendMessage',
        'params' => [$channelData]
      ];
    }
  }

  private function checkSaveClient($query) {
    $data = json_decode($query);

    if ($data->type === 'client') {
      return [
        'action' => 'saveClient',
        'params' => [$data->clientCredentials]
      ];
    }
  }

  private function checkRemoveClient($teamName) {
    return [
      'action' => 'removeClient',
      'params' => [$teamName]
    ];
  }

  private function checkSaveToken($query) {
    $data = json_decode($query);

    if ($data->type === 'token') {
      return [
        'action' => 'saveToken',
        'params' => [$data->token]
      ];
    }
  }

  private function checkSaveTokenUnsafe($query) {
    $data = json_decode($query);

    if ($data->type === 'token-unsafe') {
      return [
        'action' => 'saveTokenUnsafe',
        'params' => [$data->token]
      ];
    }
  }

  private function checkSetPresence($query) {
    $data = json_decode($query);

    if ($data->type === 'presence') {
      return [
        'action' => 'setPresence',
        'params' => [$data->presence]
      ];
    }
  }

  private function checkMarkAllAsRead($query) {
    $data = json_decode($query);

    if ($data->type === 'mark') {
      return [
        'action' => 'markAllAsRead'
      ];
    }
  }

  private function checkOpenFile($query) {
    $data = json_decode($query);

    if ($data->type === 'file') {
      return [
        'action' => 'openFile',
        'params' => [$data]
      ];
    }
  }

  private function checkRefreshCache($query) {
    $data = json_decode($query);

    if ($data->type === 'refresh') {
      return [
        'action' => 'refreshCache'
      ];
    }
  }

}
