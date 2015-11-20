<?php

namespace AlfredSlack\Libs;

use AlfredSlack\Libs\Query;

class Bootstrap {

	public function run () {

		// Set the timezone for the user using the system one
        Utils::defineTimeZone();

        $config = $this->getConfig();

        // Retrieve the route from the given parameters
		$route = Router::getRoute($config);
		if ($route !== false) {
			$this->invoke($route);
		}
	}

	private function invoke (Route $route) {
		$className = 'AlfredSlack\Controllers\\'.ucfirst($route->getController()).'Controller';
		$actionName = $route->getAction().'Action';
		$controller = new $className();

		if (!($controller instanceof \AlfredSlack\Controllers\Controller)) {
			throw new \Exception("$className must inherits from AlfredSlack\Controllers\Controller");
		}

		Utils::log('ACTION: '.$className.'::'.$actionName.'()');
		Utils::log('SIMULATE: php -r \'$query="'.addslashes(json_encode($route)).'";include "scripts/index.php";\';');
		
		$interruptAction = ($controller->preDispatch($actionName, $route->getParams()) === false);
		if (!$interruptAction) {
			$actionResult = $controller->dispatch($actionName, $route->getParams());
			$controller->postDispatch($actionName, $route->getParams(), $actionResult);
		}
	}

	private function getConfig () {
		$query = isset($GLOBALS['query']) ? $GLOBALS['query'] : null;
		$input = isset($GLOBALS['input']) ? ($GLOBALS['input'] === true) : false;
		$modifier = isset($GLOBALS['modifier']) ? $GLOBALS['modifier'] : null;
		return new Query($query, $input, $modifier);
	}

}
