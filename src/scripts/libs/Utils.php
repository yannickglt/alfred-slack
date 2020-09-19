<?php

namespace AlfredSlack\Libs;

class Utils {

  public static $icon = 'icon.png';

  private static $_workflows = null;

  private static function matches($predicate) {
    if (is_callable($predicate)) {
      return $predicate;
    } else {
      return function ($element) use ($predicate) {
        if (is_object($predicate) || is_array($predicate)) {
          foreach ($predicate as $key => $value) {
            if (is_array($element) && ($element[$key] !== $value)) {
              return false;
            } elseif (is_object($element) && ($element->$key !== $value)) {
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

  public static function extend($a, $b) {
    return (object)array_merge((array)$a, (array)$b);
  }

  public static function find($array, $predicate) {
    if (empty($array)) {
      return;
    }

    $fn = static::matches($predicate);
    foreach ($array as $value) {
      if ($fn($value)) {
        return $value;
      }
    }
    return;
  }

  public static function pluck($collection, $prop) {
    return array_map(function ($v) use ($prop) {
      return $v[$prop];
    }, $collection);
  }

  public static function filter($array, $predicate) {
    return array_values(array_filter($array, static::matches($predicate)));
  }

  public static function groupBy($collection = null, $iterator = null) {
    $result = [];
    $collection = (array)$collection;
    foreach ($collection as $k => $v) {
      $key = is_callable($iterator) ? $iterator($v, $k) : $v[$iterator];
      if (!array_key_exists($key, $result)) {
        $result[$key] = [];
      }
      $result[$key][] = $v;
    }
    return $result;
  }

  public static function flatten(array $array) {
    $return = [];
    array_walk_recursive($array, function ($a, $k) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

  public static function toArray($d) {
    if (is_object($d)) {
      $d = get_object_vars($d);
    }
    return is_array($d) ? array_map(__METHOD__, $d) : $d;
  }

  public static function toObject($d) {
    return is_array($d) ? (object)array_map(__METHOD__, $d) : $d;
  }

  public static function snakeCase($content, $separator = '_') {
    return strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1$separator$2", $content));
  }

  public static function camelCase($content) {
    return preg_replace('/_(.?)/', function ($m) {
      return strtoupper($m[1]);
    }, $content);
  }

  public static function pascalCase($content) {
    return preg_replace('/(?:^|_)(.?)/', function ($m) {
      return strtoupper($m[1]);
    }, $content);
  }

  public static function log($str) {
    error_log($str);
  }

  public static function deburr($str) {
    return str_replace("'", '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str));
  }

  public static function debug($var) {
    ob_start();
    var_dump($var);
    $trace = ob_get_contents();
    ob_end_clean();
    error_log($trace);
  }

  public static function openApp($appName) {
    exec('open -a ' . $appName);
  }

  public static function openUrl($url) {
    exec('open "' . $url . '"');
  }

  public static function getWorkflows() {
    if (static::$_workflows === null) {
      static::$_workflows = new Workflows();
    }
    return static::$_workflows;
  }

  public static function defineTimeZone() {
    $tz = exec('tz=`ls -l /etc/localtime` && echo ${tz#*/zoneinfo/}');
    date_default_timezone_set($tz);
  }

}
