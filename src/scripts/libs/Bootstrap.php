<?php

namespace AlfredSlack\Libs;

class Bootstrap {

	public function run (Query $config) {
		$route = Router::getRoute($config);
		if ($route !== false) {
			$this->invoke($route);
		}
	}

	private function invoke (Route $route) {
		$className = 'AlfredSlack\Controllers\\'.ucfirst($route->getController()).'Controller';
		$controller = new $className();

		error_log($className . '::' . $route->getAction() . 'Action()'.PHP_EOL);
		
		call_user_func_array(array($controller, $route->getAction().'Action'), $route->getParams());
	}

}
