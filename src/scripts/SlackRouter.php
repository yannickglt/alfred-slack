<?php

class SlackRouter {

	private static $routingOrder = [
		[
			'name' => 'setToken',
			'type' => 'input'
		],
		[
			'name' => 'getChannelsWithMessage',
			'type' => 'input'
		],
		[
			'name' => 'getChannels',
			'type' => 'input'
		],
		[
			'name' => 'saveToken',
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

	public static function getAction ($input, $query) {
		$type =	$input ? 'input' : 'output';
		foreach (self::$routingOrder as $action) {
			if ($action['type'] === $type) {
				$res = self::{'check'.ucfirst($action['name'])}($query);
				if (!empty($res)) {
					return (object) $res;
				}
			}
		}
	}

	private static function checkGetChannels ($channel) {
		return [
			'action' => 'getChannels',
			'params' => [$channel]
		];
	}
	
	private static function checkGetChannelsWithMessage ($query) {
		$firstSpace = strpos($query, ' ');
		if ($firstSpace !== false) {
			$channel = substr($query, 0, $firstSpace);
			$message = substr($query, $firstSpace + 1);

			return [
				'action' => 'getChannels',
				'params' => [$channel, $message]
			];
		}
		return false;
	}

	private static function checkSetToken ($query) {
		$arr = explode(' ', $query);
		$configAction = $arr[0];

		if (strpos($configAction, '--token') === 0) {
			$params = [];
			array_shift($arr);
			return [
				'action' => 'setToken',
				'params' => $arr
			];
		}
		return false;
	}

	private static function checkOpenChannel ($query) {
		$channelData = json_decode($query);

		return [
			'action' => 'openChannel',
    		'params' => [$channelData]
		];
	}

	private static function checkSendMessage ($query) {
		$channelData = json_decode($query);

	    if (!empty($channelData->message)) {
			return [
				'action' => 'sendMessage',
	    		'params' => [$channelData]
    		];
	    }
	    return false;
	}

	private static function checkSaveToken ($query) {
		$tokenData = json_decode($query);

		if ($tokenData->type === 'token') {
			return [
				'action' => 'saveToken',
				'params' => [$tokenData->token]
			];
		}

		return false;
	}

}