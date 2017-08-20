<?php

namespace AlfredSlack\Models;

class ModelFactory {

  public static function isModel($object) {
    if (is_array($object)) {
      return isset($object['_type']);
    }
    if (is_object($object)) {
      return property_exists($object, '_type');
    }
    return false;
  }

  public static function getModel($object, $type = null) {
    if (is_null($type)) {
      $type = is_array($object) ? $object['_type'] : $object->_type;
    }

    if (is_subclass_of($type, __NAMESPACE__ . '\\' . 'Model')) {
      $modelName = $type;
    } else {
      $modelName = __NAMESPACE__ . '\\' . ucfirst($type) . 'Model';
    }

    return new $modelName($object);
  }

  public static function getModels($objects, $type = null) {
    return array_map(function ($object) use ($type) {
      return static::getModel($object, $type);
    }, $objects);
  }
}
