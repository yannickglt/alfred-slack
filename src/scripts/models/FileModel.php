<?php

namespace AlfredSlack\Models;

class FileModel extends Model {

  protected $_type = 'file';

  protected $id;
  protected $created;
  protected $timestamp;
  protected $name;
  protected $title;
  protected $mimetype;
  protected $filetype;
  protected $pretty_type;
  protected $user;
  protected $editable;
  protected $size;
  protected $mode;
  protected $is_external;
  protected $external_type;
  protected $is_public;
  protected $public_url_shared;
  protected $display_as_bot;
  protected $username;
  protected $url;
  protected $url_download;
  protected $url_protected;
  protected $url_protected_download;
  protected $thumb_64;
  protected $thumb_80;
  protected $thumb_360;
  protected $thumb_360_w;
  protected $thumb_360_h;
  protected $thumb_480;
  protected $thumb_480_w;
  protected $thumb_480_h;
  protected $thumb_160;
  protected $image_exif_rotation;
  protected $original_w;
  protected $original_h;
  protected $permalink;
  protected $permalink_public;
  protected $channels;
  protected $groups;
  protected $ims;
  protected $comments_count;

  public function getThumb64() {
    return $this->thumb_64;
  }

}
