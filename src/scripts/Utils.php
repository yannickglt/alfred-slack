<?php

require_once 'workflows.php';

class Utils {

    public static $icon = 'icon.png';

	private static $_workflows = null;

	private static function matches ($predicate) {
		if (is_callable($predicate)) {
			return $predicate;
		} else {
			return function ($element) use ($predicate) {
				if (is_object($predicate) || is_array($predicate)) {
					$element = (array) $element;
					foreach ($predicate as $key => $value) {
						if ($element[$key] !== $value) {
							return false;
						}
					}
					return true;
				} else {
					return ($element === $predicate);
				}
			};
		}
	}

	public static function extend ($a, $b) {
		return (object) array_merge((array) $a, (array) $b);
	}

	public static function find ($array, $predicate) {
		$fn = self::matches($predicate);
		foreach ($array as $value) {
			if ($fn($value)) {
				return $value;
			}
		}
		return;
	}

	public static function filter ($array, $predicate) {
		return array_values(array_filter($array, self::matches($predicate)));
	}
	
	public static function toArray ($d) {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }
        return is_array($d) ? array_map(__METHOD__, $d) : $d;
    }

    public static function toObject ($d) {
        return is_array($d) ? (object) array_map(__METHOD__, $d) : $d;
    }

	public static function debug ($var) {
        ob_start();
        var_dump($var);
        $trace = ob_get_contents();
        ob_end_clean();
        error_log($trace);
	}

	public static function openApp ($appName) {
		exec('open -a '.$appName);
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