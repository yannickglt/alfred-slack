<?php

namespace AlfredSlack\Libs;

class Route implements \JsonSerializable {

  private $controller;
  private $action;
  private $params;

  public function __construct($controller, $action, $params = null) {
    $this->controller = $controller;
    $this->action = $action;
    $this->params = $params;
  }

  public function getController() {
    return $this->controller;
  }

  public function getAction() {
    return $this->action;
  }

  public function getParams() {
    return $this->params;
  }

  public function jsonSerialize() {
    return [
      'controller' => $this->controller,
      'action' => $this->action,
      'params' => $this->params
    ];
  }
}
