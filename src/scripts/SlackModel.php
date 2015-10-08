<?php

require_once 'vendor/autoload.php';

use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;

class SlackModel {

    private static $token = 'xoxp-2443699468-2467108799-2498781179-cf1538';
	private $commander;

	public function __construct () {
        $this->initCommander();
	}

    private function initCommander () {
        $interactor = new CurlInteractor;
        $interactor->setResponseFactory(new SlackResponseFactory);
        $this->commander = new Commander(self::$token, $interactor);
    }

    public function getProfileIcon ($user) {
    	$workflows = Utils::getWorkflows();
        $icon = $workflows->readPath('user.image.'.$user->id);
        if ($icon === false) {
            $workflows->write(file_get_contents($user->profile->image_24), 'user.image.'.$user->id);
            $icon = $workflows->readPath('user.image.'.$user->id);
        }
        return $icon;
    }

    public function getAuth () {
    	$workflows = Utils::getWorkflows();
        $auth = $workflows->read('auth');
        if ($auth === false) {
            $auth = $this->commander->execute('auth.test')->getBody();
            $workflows->write($auth, 'auth');
        }
        return $auth;
    }

    public function getChannels () {
    	$workflows = Utils::getWorkflows();
        $channels = $workflows->read('channels');
        if ($channels === false) {
            $channels = $this->commander->execute('channels.list')->getBody()['channels'];
            $workflows->write($channels, 'channels');
        }
        return $channels;
    }

    public function getGroups () {
    	$workflows = Utils::getWorkflows();
        $groups = $workflows->read('groups');
        if ($groups === false) {
            $groups = $this->commander->execute('groups.list')->getBody()['groups'];
            $workflows->write($groups, 'groups');
        }
        return $groups;
    }

    public function getUsers () {
    	$workflows = Utils::getWorkflows();
        $users = $workflows->read('users');
        if ($users === false) {
            $users = $this->commander->execute('users.list')->getBody()['members'];
            $workflows->write($users, 'users');
        }
        return $users;
    }
}