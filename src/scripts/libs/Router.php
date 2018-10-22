<?php

namespace AlfredSlack\Libs;

use AlfredSlack\Models\ModelFactory;

class Router {

  private static $_instance;

  private $routes = [];

  private function __construct() {
  }

  public function route($path, $definition) {
    $this->routes[] = [
      'path' => $path,
      'definition' => $definition
    ];
    return $this;
  }

  private static function getInstance() {
    if (is_null(static::$_instance)) {
      static::$_instance = new static();
    }
    return static::$_instance;
  }

  /**
   * Test if subject match the pattern, and returns the extracted parameters
   * @example urlMatch(':action :param', 'alarm 01:00')
   * @example urlMatch('sms :who :message', 'sms mommy see you sunday')
   * @return {bool|array} false If the subject does not match the pattern, or the array
   * of parameters if the subject matches it.
   */
  private static function urlMatch($pattern, $subject) {
    $pattern = preg_quote($pattern);

    $regexPattern = '/^' . preg_replace(['/\\\\:([^: ]+)\\? /', '/\\\\:([^: ]+) /', '/\\\\:([^:]+)\\?$/', '/\\\\:([^:]+)$/'], ['([^ ]*) ', '([^ ]+) ', '(.*)', '(.+)'], $pattern) . '/';

    if (is_null($regexPattern)) {
      return false;
    }

    $paramNames = null;
    if (preg_match_all('/\\\\:([^ ]*)/', $pattern, $paramNames)) {
      if (count($paramNames) === 2) {
        $paramNames = $paramNames[1];
      } else {
        $paramNames = [];
      }
    } else {
      $paramNames = [];
    }

    $matches = null;
    if (preg_match($regexPattern, $subject, $matches)) {
      array_shift($matches);
      $params = [];
      foreach ($paramNames as $index => $value) {
        $params[$value] = $matches[$index];
      }
      return $params;
    } else {
      return false;
    }
  }

  public static function getRoute(Query $config) {
    // Get the route unserializing the query (if possible)
    $serializedRoute = json_decode($config->getQuery(), true);
    if ($serializedRoute !== null) {
      $params = [];
      if (isset($serializedRoute['params']) && is_array($serializedRoute['params'])) {
        // Unserialize models if there are ones
        foreach ($serializedRoute['params'] as $key => $value) {
          $params[$key] = ModelFactory::isModel($value) ? ModelFactory::getModel($value) : $value;
        }
      }
      return new Route($serializedRoute['controller'], $serializedRoute['action'], $params);
    } // Build route from the query as string
    else {
      $routes = static::getInstance()->routes;
      foreach ($routes as $route) {
        $params = static::urlMatch($route['path'], $config->getQuery());
        if ($params !== false) {
          return new Route($route['definition']['controller'], $route['definition']['action'], $params);
        }
      }
    }
    return false;
  }

  public static function define($fn) {
    if (!is_callable($fn)) {
      throw new Exception('Parameter of Router::define must be a function');
    }
    $fn(static::getInstance());
  }
}
