<?php

class SlackRouter {

	private static $routingOrder = [
		[
			'name' => 'listConfigs',
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
		if (($firstSpace !== false) && ($firstSpace < strlen($query) - 1)) {
			$channel = substr($query, 0, $firstSpace);
			$message = substr($query, $firstSpace + 1);

			return [
				'action' => 'getChannels',
				'params' => [$channel, $message]
			];
		}
		return false;
	}
	
	private static function checkGetChannelHistory ($query) {
		$firstSpace = strpos($query, ' ');
		if (($firstSpace !== false) && ($firstSpace === strlen($query) - 1)) {
			$channel = substr($query, 0, $firstSpace);

			return [
				'action' => 'getChannelHistory',
				'params' => [$channel]
			];
		}
		return false;
	}

	private static function checkListConfigs ($query) {
		$arr = explode(' ', $query);
		$configAction = $arr[0];

		if (strpos($configAction, '--') === 0) {
			return [
				'action' => 'listConfigs',
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
		$data = json_decode($query);

		if ($data->type === 'token') {
			return [
				'action' => 'saveToken',
				'params' => [$data->token]
			];
		}

		return false;
	}

	private static function checkMarkAllAsRead ($query) {
		$data = json_decode($query);

		if ($data->type === 'mark') {
			return [
				'action' => 'markAllAsRead'
			];
		}

		return false;
	}

	private static function checkRefreshCache ($query) {
		$data = json_decode($query);

		if ($data->type === 'refresh') {
			return [
				'action' => 'refreshCache'
			];
		}

		return false;
	}

}