<?php

namespace AlfredSlack\Libs;

use AlfredSlack\Controllers\SlackController;

class Bootstrap {

	public function run (Array $config) {
		$route = SlackRouter::getAction($config['input'], $config['query']);
		$controller = new SlackController();
		$params = empty($route->params) ? [] : $route->params;
		call_user_func_array(array($controller, $route->action.'Action'), $params);
	}
}
