<?php

require_once 'workflows.php';

class Utils {

    public static $icon = 'slack.png';

	private static $_workflows = null;

	public static function extend ($a, $b) {
		return (object) array_merge((array) $a, (array) $b);
	}

	public static function debug ($var) {
        ob_start();
        var_dump($var);
        $trace = ob_get_contents();
        ob_end_clean();
        error_log($trace);
	}

	public static function openUrl ($url) {
		exec('open "'.$url.'"');
	}

	public static function getWorkflows () {
		if (self::$_workflows === null) {
			self::$_workflows = new Workflows();
		}
		return self::$_workflows;
	}
}