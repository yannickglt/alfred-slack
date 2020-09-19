<?php

namespace AlfredSlack\Helpers\Service;

use AlfredSlack\Libs\Utils;

use AlfredSlack\Models\ModelFactory;

use AlfredSlack\Helpers\Core\CustomCommander;
use AlfredSlack\Helpers\Http\MultiCurlInteractor;

use Frlnc\Slack\Http\SlackResponseFactory;

class SingleTeamSlackService implements SlackServiceInterface {

  private $commander;
  public $teamId;

  public function __construct($teamId) {
    $this->teamId = $teamId;
    $this->initCommander();
  }

  private function initCommander() {
    $interactor = new MultiCurlInteractor;
    $interactor->setResponseFactory(new SlackResponseFactory);
    $token = $this->getToken($this->teamId);
    if (!empty($token)) {
      $this->commander = new CustomCommander($token, $interactor);
    }
  }

  public function getProfileIcon($userId) {
    $icon = Utils::getWorkflows()->readPath('user.image.' . $userId);
    if ($icon === false) {
      $users = $this->getUsers(true);
      $user = Utils::find($users, ['id' => $userId]);
      if (!is_null($user)) {
        Utils::getWorkflows()->write(file_get_contents($user->profile->image_24), 'user.image.' . $userId);
        $icon = Utils::getWorkflows()->readPath('user.image.' . $userId);
      }
    }
    return $icon;
  }

  public function getFileIcon($fileId) {
    $icon = Utils::getWorkflows()->readPath('file.image.' . $fileId);
    if ($icon === false) {
      $files = $this->getFiles();
      $file = Utils::find($files, ['id' => $fileId]);
      if (is_null($file)) {
        $file = $this->getFile($fileId);
      }
      if (!is_null($file) && property_exists($file, 'thumb_64') && !empty($file->thumb_64)) {
        Utils::getWorkflows()->write(file_get_contents($file->thumb_64), 'file.image.' . $fileId);
        $icon = Utils::getWorkflows()->readPath('file.image.' . $fileId);
      }
    }
    return $icon;
  }

  private function getAuth() {
    $auth = Utils::getWorkflows()->read('auth.' . $this->teamId);
    if ($auth === false) {
      $auth = $this->commander->execute('auth.test')->getBody();
      Utils::getWorkflows()->write($auth, 'auth.' . $this->teamId);
      $auth = Utils::getWorkflows()->read('auth.' . $this->teamId);
    }
    return $auth;
  }

  public function getChannels($excludeArchived = false) {
    $channels = Utils::getWorkflows()->read('channels.' . $this->teamId);
    if ($channels === false) {
      $params = [];
      if ($excludeArchived === true) {
        $params['exclude_archived'] = '1';
      }
      $channels = $this->commander->execute('conversations.list', $params)->getBody()['channels'];
      $auth = $this->getAuth();
      foreach ($channels as $index => $channel) {
        $channels[$index] = Utils::extend($channel, ['auth' => $auth]);
      }
      Utils::getWorkflows()->write($channels, 'channels.' . $this->teamId);
      $channels = Utils::getWorkflows()->read('channels.' . $this->teamId);
    }
    return ModelFactory::getModels($channels, '\AlfredSlack\Models\ChannelModel');
  }

  /** @deprecated  */
  public function getGroups($excludeArchived = false) {
    $groups = Utils::getWorkflows()->read('groups.' . $this->teamId);
    if ($groups === false) {
      $params = [];
      if ($excludeArchived === true) {
        $params['exclude_archived'] = '1';
      }
      $groups = $this->commander->execute('groups.list', $params)->getBody()['groups'];
      $auth = $this->getAuth();
      foreach ($groups as $index => $group) {
        $groups[$index] = Utils::extend($group, ['auth' => $auth]);
      }
      Utils::getWorkflows()->write($groups, 'groups.' . $this->teamId);
      $groups = Utils::getWorkflows()->read('groups.' . $this->teamId);
    }
    return ModelFactory::getModels($groups, '\AlfredSlack\Models\GroupModel');
  }

  /** @deprecated  */
  public function getIms($excludeDeleted = false) {
    $ims = Utils::getWorkflows()->read('ims.' . $this->teamId);
    if ($ims === false) {
      $ims = $this->commander->execute('im.list')->getBody()['ims'];
      $auth = $this->getAuth();
      foreach ($ims as $index => $im) {
        $ims[$index] = Utils::extend($im, ['auth' => $auth]);
      }
      if ($excludeDeleted === true) {
        $ims = Utils::filter($ims, ['is_user_deleted' => false]);
      }
      Utils::getWorkflows()->write($ims, 'ims.' . $this->teamId);
      $ims = Utils::getWorkflows()->read('ims.' . $this->teamId);
    }
    return ModelFactory::getModels($ims, '\AlfredSlack\Models\ImModel');
  }

  public function openIm(\AlfredSlack\Models\UserModel $user) {
    $userId = $user->getId();
    if (!isset($userId)) {
      throw new Exception('The parameter "userId" is mandatory.');
    }
    return Utils::toObject($this->commander->execute('im.open', ['user' => $userId])->getBody());
  }

  public function getUsers($excludeDeleted = false) {
    $users = Utils::getWorkflows()->read('users.' . $this->teamId);
    if ($users === false) {
      $users = $this->commander->execute('users.list')->getBody()['members'];
      $auth = $this->getAuth();
      foreach ($users as $index => $user) {
        $users[$index] = Utils::extend($user, ['auth' => $auth]);
      }
      Utils::getWorkflows()->write($users, 'users.' . $this->teamId);
      $users = Utils::getWorkflows()->read('users.' . $this->teamId);
    }
    if ($excludeDeleted === true) {
      $users = Utils::filter($users, ['deleted' => false]);
    }
    return ModelFactory::getModels($users, '\AlfredSlack\Models\UserModel');
  }

  public function getFiles() {
    $files = Utils::getWorkflows()->read('files.' . $this->teamId);
    if ($files === false) {
      $files = $this->commander->execute('files.list')->getBody()['files'];
      Utils::getWorkflows()->write($files, 'files.' . $this->teamId);
      $files = Utils::getWorkflows()->read('files.' . $this->teamId);
    }
    return ModelFactory::getModels($files, '\AlfredSlack\Models\FileModel');
  }

  public function getFile(\AlfredSlack\Models\FileModel $file) {
    return ModelFactory::getModel($this->commander->execute('files.info', ['file' => $file->getId()])->getBody()['file'], '\AlfredSlack\Models\FileModel');
  }

  public function getStarredItems() {
    $stars = Utils::getWorkflows()->read('stars.' . $this->teamId);
    if ($stars === false) {
      $stars = $this->commander->execute('stars.list')->getBody()['items'];
      Utils::getWorkflows()->write($stars, 'stars.' . $this->teamId);
      $stars = Utils::getWorkflows()->read('stars.' . $this->teamId);
    }
    return array_map(function ($star) {
      switch ($star->type) {
        case 'message':
          return ModelFactory::getModel($star->message, '\AlfredSlack\Models\MessageModel');
        case 'file':
        case 'file_comment':
          return ModelFactory::getModel($star->file, '\AlfredSlack\Models\FileModel');
        case 'channel':
          return new \AlfredSlack\Models\ChannelModel(['id' => $star->channel]);
      }
    }, $stars);
  }

  public function search($query) {
    $items = $this->commander->execute('search.all', ['query' => $query])->getBody();
    $messages = ModelFactory::getModels($items['messages']['matches'], '\AlfredSlack\Models\MessageModel');
    $files = ModelFactory::getModels($items['files']['matches'], '\AlfredSlack\Models\FileModel');
    return $messages + $files;
  }

  /** @deprecated */
  public function getImByUser(\AlfredSlack\Models\UserModel $user) {
    $userId = $user->getId();
    // Get the IM id if a user
    $ims = $this->getIms(true);
    $im = Utils::find($ims, ['user' => $userId]);
    if (empty($im)) {
      $im = $this->openIm($user);
    }
    return ModelFactory::getModel($im, '\AlfredSlack\Models\ImModel');
  }

  private function getToken($teamId) {
    $token = Utils::getWorkflows()->read('token.' . $teamId);
    if ($token === false) {
      return Utils::getWorkflows()->getPassword('token.' . $teamId);
    } else {
      return $token;
    }
  }

  public function setPresence($isAway = false) {
    $this->commander->execute('users.setPresence', ['presence' => $isAway ? 'away' : 'auto'])->getBody();
  }

  public function setStatus(\AlfredSlack\Models\StatusModel $status) {
    $this->commander->execute('users.profile.set', ['profile' => json_encode($status) ]) ->getBody();
  }

  public function postMessage(\AlfredSlack\Models\ChatInterface $channel, $message, $asBot = false) {
    Utils::debug("channel: {$channel->getId()}, message: $message, asBot: $asBot");

    $id = $channel->getId();
//    if ($channel instanceof \AlfredSlack\Models\UserModel) {
//      $id = $this->getImByUser($channel)->getId();
//    }

    return $this->commander->execute('chat.postMessage', [
      'channel' => $id,
      'text' => $message,
      'as_user' => !$asBot,
      'parse' => 'full',
      'link_names' => 1,
      'unfurl_links' => true,
      'unfurl_media' => true
    ])->getBody();
  }

  public function getChannelHistory(\AlfredSlack\Models\ChannelModel $channel) {
    return ModelFactory::getModels($this->commander->execute('conversations.history', ['channel' => $channel->getId()])->getBody()['messages'], '\AlfredSlack\Models\MessageModel');
  }

  public function getGroupHistory(\AlfredSlack\Models\GroupModel $group) {
    return ModelFactory::getModels($this->commander->execute('groups.history', ['channel' => $group->getId()])->getBody()['messages'], '\AlfredSlack\Models\MessageModel');
  }

  public function getImHistory(\AlfredSlack\Models\ImModel $im) {
    return ModelFactory::getModels($this->commander->execute('im.history', ['channel' => $im->getId()])->getBody()['messages'], '\AlfredSlack\Models\MessageModel');
  }

  public function refreshCache() {

    // Refresh auth
    Utils::getWorkflows()->delete('auth.' . $this->teamId);
    $teamName = $this->getAuth()->team;
    Utils::log("Auth refreshed for team $teamName");

    // Refresh channels
    Utils::getWorkflows()->delete('channels.' . $this->teamId);
    $channels = $this->getChannels();
    $channels = null;
    Utils::log("Channels refreshed for team $teamName");

    // Refresh groups
    Utils::getWorkflows()->delete('groups.' . $this->teamId);

    // Refresh user icons
    $users = $this->getUsers();
    foreach ($users as $user) {
      Utils::getWorkflows()->delete('user.image.' . $user->getId());
    }
    $users = null;

    // Refresh users
    Utils::getWorkflows()->delete('users.' . $this->teamId);
    $users = $this->getUsers();
    Utils::log("Users refreshed for team $teamName");

    foreach ($users as $user) {
      $this->getProfileIcon($user->getId());
    }
    $users = null;
    Utils::log("Profile icons refreshed for team $teamName");

    // Refresh file icons
    $files = $this->getFiles();
    foreach ($files as $file) {
      Utils::getWorkflows()->delete('file.image.' . $file->getId());
      $this->getFileIcon($file->getId());
    }
    $files = null;
    Utils::log("File icons refreshed for team $teamName");

    // Refresh ims
    Utils::getWorkflows()->delete('ims.' . $this->teamId);
  }

  public function markGroupAsRead(\AlfredSlack\Models\GroupModel $group) {
    $now = time();
    $this->commander->executeAsync('groups.mark', ['channel' => $group->getId(), 'ts' => $now]);
  }

  public function markImAsRead(\AlfredSlack\Models\ImModel $im) {
    $now = time();
    $this->commander->executeAsync('im.mark', ['channel' => $im->getId(), 'ts' => $now]);
  }

  public function markAllAsRead() {
    $now = time();
    $requests = [];

    return array_map(function ($e) {
      return $e->getBody();
    }, $this->commander->executeAll($requests));
  }
}
