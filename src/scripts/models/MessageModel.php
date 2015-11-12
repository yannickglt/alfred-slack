<?php

namespace AlfredSlack\Models;

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

}
