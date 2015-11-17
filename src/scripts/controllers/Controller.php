<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;

class Controller {

    protected $workflows;
    protected $results;
    private $isRendered = false;
    private $isNotified = false;
    
    public function __construct () {
        $this->workflows = Utils::getWorkflows();
        $this->results = [];
    }

    public function preDispatch ($action, $params) {
        
    }

    public function dispatch ($action, $params) {
        return call_user_func_array(array($this, $action), $params);
    }

    public function postDispatch ($action, $params, $result) {

    }

    protected function render () {
        if ($this->isNotified) {
            throw new Exception('Cannot use the method "render" if the method "notify" was called');
        }

        $i = 0;
        foreach ($this->results as $result) {
            $icon = isset($result['icon']) ? $result['icon'] : Utils::$icon;
            $route = isset($result['route']) ? json_encode($result['route']) : null;
            $autocomplete = isset($result['autocomplete']) ? $result['autocomplete'] : null;
            $this->workflows->result($i++, $route, $result['title'], $result['description'], $icon, 'yes', $autocomplete);
        }

        $this->isRendered = true;

        echo $this->workflows->toxml();
    }

    protected function notify ($message) {
        if ($this->isRendered) {
            throw new Exception('Cannot use the method "render" if the method "notify" was called');
        }

        $this->isNotified = true;

        echo $message;
    }

    protected function filterResults ($array, $search) {
        $search = strtolower(trim($search));
        $found = [];
        $results = [];
        foreach ($array as $id => $element) {
            
            $title = strtolower(trim($element['title']));
            $description = strtolower(trim($element['description']));

            if ($title === $search) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $results[0][] = $element;
                }
            }
            else if (strpos($title, $search) === 0) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $results[1][] = $element;
                }
            }
            else if (strpos($title, $search) > 0) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $element['__searchIndex'] = strpos($title, $search);
                    $results[2][] = $element;
                }
            }
            else if (strpos($description, $search) !== false) {
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