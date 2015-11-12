<?php

namespace AlfredSlack\Models;

use AlfredSlack\Libs\Utils;

class Model {

	public function __construct ($object) {
		foreach ($object as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			}
		}
	}
 	
 	public function __call ($name, $arguments) {
 		$action = substr($name, 0, 3);
        switch ($action) {
            case 'get':
                $property = Utils::snakeCase(substr($name, 3));
                //if (property_exists($this, $property)) {
                    return $this->$property;
                //} else {
                //    throw new Exception("Undefined property $name");
                //}
                break;
            case 'set':
                $property = Utils::snakeCase(substr($name, 3));
                // if (property_exists($this, $property)) {
                    $this->$property = $arguments[0];
                // } else {
                //     throw new Exception("Undefined property $name");
                // }
                
                break;
            default :
                return FALSE;
        }
    }

}
