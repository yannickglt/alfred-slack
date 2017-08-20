<?php

namespace AlfredSlack\Models;

class UserModel extends Model implements ChatInterface {

  protected $_type = 'user';

  protected $id;
  protected $team_id;
  protected $name;
  protected $deleted;
  protected $status;
  protected $color;
  protected $real_name;
  protected $tz;
  protected $tz_label;
  protected $tz_offset;
  protected $profile;
  protected $is_admin;
  protected $is_owner;
  protected $is_primary_owner;
  protected $is_restricted;
  protected $is_ultra_restricted;
  protected $is_bot;

  protected $auth;

  public function __construct($object) {
    parent::__construct($object);
    $this->auth = new AuthModel($this->auth);
  }

  public function __toString() {
    return $this->auth->getTeam() . ' - User - ' . $this->profile->real_name;
  }

}
