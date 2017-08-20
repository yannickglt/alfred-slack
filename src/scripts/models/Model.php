<?php

namespace AlfredSlack\Models;

use AlfredSlack\Libs\Utils;

class Model implements \JsonSerializable {

  public function __construct($object) {
    if (!empty($object)) {
      foreach ($object as $key => $value) {
        if (property_exists($this, $key)) {
          $this->$key = $value;
        }
      }
    }
  }

  public function __call($name, $arguments) {
    $action = substr($name, 0, 3);
    switch ($action) {
      case 'get':
        $property = Utils::snakeCase(substr($name, 3));
        if (property_exists($this, $property)) {
          return $this->$property;
        } else {
          throw new \Exception("Undefined property $property");
        }
        break;
      case 'set':
        $property = Utils::snakeCase(substr($name, 3));
        if (property_exists($this, $property)) {
          $this->$property = $arguments[0];
        } else {
          throw new \Exception("Undefined property $property");
        }

        break;
      default :
        return FALSE;
    }
  }

  public function __get($name) {
    $property = Utils::snakeCase($name);
    if (property_exists($this, $property) && ($name !== '_type')) {
      return $this->$property;
    } else {
      throw new \Exception("Undefined or inaccessible property $property");
    }
  }

  public function jsonSerialize() {
    $json = array();
    foreach ($this as $key => $value) {
      $json[$key] = $value;
    }
    return $json;
  }
}
