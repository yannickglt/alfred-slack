<?php

namespace AlfredSlack\Libs;

class Emoji {

  private static $_instance;

  private $emojisByCode;

  private function __construct() {
    $this->emojisByCode = json_decode(file_get_contents(__DIR__ . '/../config/emojis.json'), true);
  }

  public static function getInstance() {
    if (is_null(static::$_instance)) {
      static::$_instance = new static();
    }
    return static::$_instance;
  }

  public function fromCode($code) {
    return isset($this->emojisByCode[$code]) ? $this->emojisByCode[$code] : $code;
  }

}
