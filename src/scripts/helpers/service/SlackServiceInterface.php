<?php

namespace AlfredSlack\Helpers\Service;

interface SlackServiceInterface {

  public function getProfileIcon($userId);

  public function getFileIcon($fileId);

  public function getChannels($excludeArchived);

  /** @deprecated  */
  public function getGroups($excludeArchived);

  /** @deprecated  */
  public function getIms($excludeDeleted);

  public function openIm(\AlfredSlack\Models\UserModel $user);

  public function getUsers($excludeDeleted);

  public function getFiles();

  public function getFile(\AlfredSlack\Models\FileModel $file);

  public function getStarredItems();

  /** @deprecated  */
  public function getImByUser(\AlfredSlack\Models\UserModel $user);

  public function setPresence($isAway);

  public function postMessage(\AlfredSlack\Models\ChatInterface $channel, $message, $asBot);

  public function getChannelHistory(\AlfredSlack\Models\ChannelModel $channel);

  public function getGroupHistory(\AlfredSlack\Models\GroupModel $group);

  public function getImHistory(\AlfredSlack\Models\ImModel $im);

  public function refreshCache();

  public function markGroupAsRead(\AlfredSlack\Models\GroupModel $group);

  public function markImAsRead(\AlfredSlack\Models\ImModel $im);

  public function markAllAsRead();

}
