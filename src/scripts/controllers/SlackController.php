<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;

abstract class SlackController extends Controller {

  protected $service;

  public function __construct() {
    parent::__construct();
    $this->service = new MultiTeamSlackService();
  }

  public function preDispatch($action, $params) {

    // Interrupt the action if the cache is currently refreshing
    if ($this->service->isCacheLocked()) {
      $this->results = [
        [
          'id' => '',
          'title' => 'Refresh still in progress',
          'description' => 'Please wait the end of cache refresh...'
        ]
      ];
      $this->render();
      return false;
    }
  }

}
