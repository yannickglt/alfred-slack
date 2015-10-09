<?php

require_once 'vendor/autoload.php';

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

class SlackModel {

    private $commander;
	private $workflows;

	public function __construct () {
        $this->workflows = Utils::getWorkflows();
        $this->initCommander();
	}

    private function initCommander () {
        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $token = $this->getToken();
        if (!empty($token)) {
            $this->commander = new Commander($token, $interactor);
        }
    }

    public function getProfileIcon ($user) {
        $icon = $this->workflows->readPath('user.image.'.$user->id);
        if ($icon === false) {
            $this->workflows->write(file_get_contents($user->profile->image_24), 'user.image.'.$user->id);
            $icon = $this->workflows->readPath('user.image.'.$user->id);
        }
        return $icon;
    }

    public function getAuth () {
        $auth = $this->workflows->read('auth');
        if ($auth === false) {
            $auth = $this->commander->execute('auth.test')->getBody();
            $this->workflows->write($auth, 'auth');
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
}