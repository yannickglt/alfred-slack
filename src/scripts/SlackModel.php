<?php

require_once 'vendor/autoload.php';
require_once 'CustomCommander.php';
require_once 'MultiCurlInteractor.php';

use Frlnc\Slack\Core\CustomCommander;
use Frlnc\Slack\Http\MultiCurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

class SlackModel {

    private $commander;
	private $workflows;

	public function __construct () {
        $this->workflows = Utils::getWorkflows();
        $this->initCommander();
	}

    private function initCommander () {
        $interactor = new MultiCurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $token = $this->getToken();
        if (!empty($token)) {
            $this->commander = new CustomCommander($token, $interactor);
        }
    }

    public function getProfileIcon ($userId) {
        $icon = $this->workflows->readPath('user.image.'.$userId);
        if ($icon === false) {
            $users = $this->getUsers(true);
            $users = Utils::filter($users, [ 'id' => $userId ]);
            $this->workflows->write(file_get_contents($users[0]->profile->image_24), 'user.image.'.$userId);
            $icon = $this->workflows->readPath('user.image.'.$userId);
        }
        return $icon;
    }

    public function getAuth () {
        $auth = $this->workflows->read('auth');
        if ($auth === false) {
            $auth = $this->commander->execute('auth.test')->getBody();
            $this->workflows->write($auth, 'auth');
            $auth = $this->workflows->read('auth');
        }
        return $auth;
    }

    public function getChannels ($excludeArchived = false) {
        $channels = $this->workflows->read('channels');
        if ($channels === false) {
            $params = [];
            if ($excludeArchived === true) {
                $params['exclude_archived'] = '1';
            }
            $channels = $this->commander->execute('channels.list', $params)->getBody()['channels'];
            $this->workflows->write($channels, 'channels');
            $channels = $this->workflows->read('channels');
        }
        return $channels;
    }

    public function getGroups ($excludeArchived = false) {
        $groups = $this->workflows->read('groups');
        if ($groups === false) {
            $params = [];
            if ($excludeArchived === true) {
                $params['exclude_archived'] = '1';
            }
            $groups = $this->commander->execute('groups.list', $params)->getBody()['groups'];
            $this->workflows->write($groups, 'groups');
            $groups = $this->workflows->read('groups');
        }
        return $groups;
    }

    public function getIms ($excludeDeleted = false) {
        $ims = $this->workflows->read('ims');
        if ($ims === false) {
            $ims = $this->commander->execute('im.list')->getBody()['ims'];
            if ($excludeDeleted === true) {
                $ims = Utils::filter($ims, [ 'is_user_deleted' => false ]);
            }
            $this->workflows->write($ims, 'ims');
            $ims = $this->workflows->read('ims');
        }
        return $ims;
    }

    public function openIm ($userId) {
        if (!isset($userId)) {
            throw new Exception('The parameter "userId" is mandatory.');
        }
        return Utils::toObject($this->commander->execute('im.open', [ 'user' => $userId ])->getBody());
    }

    public function getUsers ($excludeDeleted = false) {
        $users = $this->workflows->read('users');
        if ($users === false) {
            $users = $this->commander->execute('users.list')->getBody()['members'];
            if ($excludeDeleted === true) {
                $users = Utils::filter($users, [ 'deleted' => false ]);
            }
            $this->workflows->write($users, 'users');
            $users = $this->workflows->read('users');
        }
        return $users;
    }

    public function getImIdByUserId ($userId) {
        // Get the IM id if a user
        $ims = $this->getIms(true);
        $im = Utils::find($ims, [ 'user' => $userId ]);
        if (!empty($im)) {
            return $im->id;
        } else {
            $im = $this->openIm($userId);
            return $im->channel->id;
        }
    }

    public function getToken () {
        return $this->workflows->read('token');
    }

    public function setToken ($token) {
        $this->workflows->write($token, 'token');
        $this->initCommander();
    }

    public function postMessage ($channel, $message, $asBot = false) {
        return $this->commander->execute('chat.postMessage', [ 'channel' => $channel, 'text' => $message, 'as_user' => !$asBot ])->getBody();
    }

    public function getChannelHistory ($channelId) {
        return $this->commander->execute('channels.history', [ 'channel' => $channelId ])->getBody()['messages'];
    }

    public function getGroupHistory ($channelId) {
        return $this->commander->execute('groups.history', [ 'channel' => $groupId ])->getBody()['messages'];
    }

    public function getImHistory ($imId) {
        return $this->commander->execute('im.history', [ 'channel' => $imId ])->getBody()['messages'];
    }

    public function markAllAsRead () {
        $now = time();
        $responses = [];

        function getBody ($e) {
            return $e->getBody();
        }

        $channelParams = [];
        $channels = $this->getChannels();
        foreach ($channels as $channel) {
            $channelParams[] = [ 'command' => 'channels.mark', 'parameters' => [ 'channel' => $channel->id, 'ts' => $now ] ];
        }
        $responses['channels'] = array_map('getBody', $this->commander->executeAll($channelParams));

        $groupParams = [];
        $groups = $this->getGroups();
        foreach ($groups as $group) {
            $groupParams[] = [ 'command' => 'groups.mark', 'parameters' => [ 'channel' => $group->id, 'ts' => $now ] ];
        }
        $responses['groups'] = array_map('getBody', $this->commander->executeAll($groupParams));

        $imParams = [];
        $ims = $this->getIms();
        foreach ($ims as $im) {
            $imParams[] = [ 'command' => 'im.mark', 'parameters' => [ 'channel' => $im->id, 'ts' => $now ] ];
        }
        $responses['ims'] = array_map('getBody', $this->commander->executeAll($imParams));

        return $responses;
    }
}