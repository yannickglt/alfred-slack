<?php

namespace AlfredSlack\Models;

use AlfredSlack\Libs\Emoji;
use AlfredSlack\Libs\Utils;

class MessageModel extends Model {

  protected $_type = 'message';

  protected $channel;
  protected $is_starred;
  protected $permalink;
  protected $text;
  protected $ts;
  protected $type;
  protected $user;
  protected $username;

  public function getText() {
    return static::replaceEmojis($this->text);
  }

  private static function replaceEmojis($str) {
    return preg_replace_callback('/(:[^\\s:]*:)/', function ($matches) {
      return Emoji::getInstance()->fromCode($matches[0]);
    }, $str);
  }

}
