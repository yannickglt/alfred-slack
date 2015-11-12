<?php

namespace AlfredSlack\Models;

class GroupModel extends Model {

	protected $_type = 'group';

	protected $id;
	protected $name;
	protected $created;
	protected $creator;
	protected $is_archived;
	protected $is_group;
	protected $is_mpim;
	protected $is_open;
	protected $latest;
	protected $last_read;
	protected $members;
	protected $purpose;
	protected $topic;
	protected $unread_count;
	protected $unread_count_display;
	
	protected $auth;
}
