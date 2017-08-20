<?php

namespace AlfredSlack\Models;

class ChannelModel extends Model implements ChatInterface {

  protected $_type = 'channel';

  protected $id;
  protected $name;
  protected $created;
  protected $creator;
  protected $is_archived;
  protected $is_channel;
  protected $is_general;
  protected $is_member;
  protected $last_read;
  protected $latest;
  protected $members;
  protected $num_members;
  protected $purpose;
  protected $topic;
  protected $unread_count;
  protected $unread_count_display;

  protected $auth;

  public function __construct($object) {
    parent::__construct($object);
    $this->auth = new AuthModel($this->auth);
  }

  public function __toString() {
    return $this->auth->getTeam() . ' - Channel - ' . ($this->num_members === 0 ? 'No' : $this->num_members) . ' members - ' . ($this->is_member ? 'Already a member' : 'Not a member');
  }

}
