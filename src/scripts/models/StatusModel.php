<?php

namespace AlfredSlack\Models;

class StatusModel extends Model {

  protected $_type = 'status';

  protected $status_text;
  protected $status_emoji;
  protected $status_expiration;

}
