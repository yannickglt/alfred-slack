<?php

namespace AlfredSlack\Libs;

class Query {

  private $query;
  private $input;
  private $modifier;

  public function __construct($query, $input, $modifier) {
    $this->query = $query;
    $this->input = $input;
    $this->modifier = $modifier;
  }

  public function getQuery() {
    return $this->query;
  }

  public function isInput() {
    return ($this->input === true);
  }

  public function getModifier() {
    return $this->query;
  }
}
