<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;

class Controller {

  protected $workflows;
  protected $results;
  private $isRendered = false;
  private $isNotified = false;

  public function __construct() {
    $this->workflows = Utils::getWorkflows();
    $this->results = [];
  }

  public function preDispatch($action, $params) {

  }

  public function dispatch($action, $params) {
    return call_user_func_array(array($this, $action), $params);
  }

  public function postDispatch($action, $params, $result) {

  }

  protected function render($sorted = true) {
    if ($this->isNotified) {
      throw new Exception('Cannot use the method "render" if the method "notify" was called');
    }

    $i = 0;
    foreach ($this->results as $result) {
      $uid = $sorted ? $i++ : null;
      $icon = isset($result['icon']) ? $result['icon'] : Utils::$icon;
      $route = isset($result['route']) ? json_encode($result['route']) : null;
      $autocomplete = isset($result['autocomplete']) ? $result['autocomplete'] : null;
      $description = isset($result['description']) ? $result['description'] : null;
      $this->workflows->result($uid, $route, $result['title'], $description, $icon, 'yes', $autocomplete);
    }

    $this->isRendered = true;

    echo $this->workflows->toxml();
  }

  protected function notify($message) {
    if ($this->isRendered) {
      throw new Exception('Cannot use the method "render" if the method "notify" was called');
    }

    $this->isNotified = true;

    echo $message;
  }

  protected function deduplicateChannels(array $results) {
    $resultsByAutocomplete = Utils::groupBy($results, 'autocomplete');
    $resultsByAutocomplete = Utils::filter($resultsByAutocomplete, function ($v) {
      return count($v) > 1;
    });

    $autocompletes = [];
    foreach ($resultsByAutocomplete as $res) {
      foreach ($res as $result) {
        $autocompletes[] = $result['autocomplete'];
      }
    }

    foreach ($results as &$result) {
      if (in_array($result['autocomplete'], $autocompletes)) {
        $channel = $result['route']->getParams()['channel'];
        $autocomplete = $channel->getAuth()->getTeam();
        $autocomplete .= ($channel instanceof \AlfredSlack\Models\UserModel) ? '@' : '#';
        $autocomplete .= $channel->getName() . ' ';
        $result['autocomplete'] = $autocomplete;
      }
    }

    return $results;
  }

  protected function filterResults(array $array, $search) {
    $search = strtolower(Utils::deburr(trim($search)));

    $found = [];
    $results = [];
    foreach ($array as $id => $element) {

      $title = strtolower(Utils::deburr(trim($element['title'])));
      $autocomplete = strtolower(Utils::deburr(trim($element['autocomplete'])));
      $description = strtolower(Utils::deburr(trim($element['description'])));

      if ($autocomplete === $search) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $results[0][] = $element;
        }
      } else if ($title === $search) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $results[0][] = $element;
        }
      } else if (strpos($autocomplete, $search) === 0) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $results[1][] = $element;
        }
      } else if (strpos($title, $search) === 0) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $results[1][] = $element;
        }
      } else if (strpos($title, $search) > 0) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $element['__searchIndex'] = strpos($title, $search);
          $results[2][] = $element;
        }
      } else if (strpos($autocomplete, $search) > 0) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $element['__searchIndex'] = strpos($autocomplete, $search);
          $results[2][] = $element;
        }
      } else if (strpos($description, $search) !== false) {
        if (!isset($found[$id])) {
          $found[$id] = true;
          $element['__searchIndex'] = strpos($description, $search);
          $results[3][] = $element;
        }
      }
    }

    if (isset($results[2])) {
      usort($results[2], function ($a, $b) {
        if ($a['__searchIndex'] === $b['__searchIndex']) {
          $al = strlen($a['title']);
          $bl = strlen($b['title']);
          if ($al === $bl) {
            return 0;
          }
          return ($al < $bl) ? -1 : 1;
        }
        return ($a['__searchIndex'] < $b['__searchIndex']) ? -1 : 1;
      });
    }

    if (isset($results[3])) {
      usort($results[3], function ($a, $b) {
        if ($a['__searchIndex'] === $b['__searchIndex']) {
          $al = strlen($a['description']);
          $bl = strlen($b['description']);
          if ($al === $bl) {
            return 0;
          }
          return ($al < $bl) ? -1 : 1;
        }
        return ($a['__searchIndex'] < $b['__searchIndex']) ? -1 : 1;
      });
    }

    ksort($results);

    $return = [];
    foreach ($results as $resultPerLevel) {
      $return = array_merge($return, $resultPerLevel);
    }
    return $return;
  }
}
