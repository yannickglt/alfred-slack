<?php
require_once 'workflows.php';

class Slack {

    private $workflows;
    private $results;
	private $conf;
    private static $cacheDirectory = 'cache/';
	private static $usersFile = 'users.list';
	private static $cacheTime = 604800; // 7 days

    public function __construct($query) {
    	$this->query = $query;
    	if (file_exists('config.json')) {
    		$conf = json_decode(file_get_contents('config.json'));
    		$this->conf = (object) array_merge((array) $this->conf, (array) $conf);
    	} else {
    		$this->conf = new stdClass();
    	}
    }

    public function input () {


        $this->workflows = new Workflows();
        if (strpos($this->query, '/') === 0) {
			$this->workflows->result( 'refresh', '/refresh', 'Refresh the cache', '', 'slack.png' );
			$this->workflows->result( 'token', $this->query, 'Set the token', '', 'slack.png' );
        } else {
        	$this->checkCache(self::$usersFile);
	        $this->results = array(
	            0 => array(),
	            1 => array(),
	            2 => array()
	        );
	        $this->process();
        	$this->render();
        }
        echo $this->workflows->toxml();
    }

    public function output () {
		if ($this->query === '/refresh') {
			$this->refreshCache();
		} elseif (strpos($this->query, '/token') === 0) {
			$queryParts = explode(' ', $this->query);
			$token = $queryParts[1];
			$this->conf->token = $token;
			file_put_contents('config.json', json_encode($this->conf));
		}
    }

	private function getPicture ($memberName, $imageUrl, $cacheTime = NULL) {
		if ($cacheTime === NULL) {
			$cacheTime = self::$cacheTime;
		}
		$this->checkCacheFolder();
		$imageUrl = stripslashes($imageUrl);
		$extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
		$fileName = self::$cacheDirectory."$memberName.$extension";
		if (!file_exists($fileName) || (filemtime($fileName) <= time() - $cacheTime)) {
	        file_put_contents($fileName, file_get_contents($imageUrl));
	    }
	    return $fileName;
	}

	private function checkCache ($file, $cacheTime = NULL) {
		if ($cacheTime === NULL) {
			$cacheTime = self::$cacheTime;
		}
		$this->checkCacheFolder();
		$fileName = self::$cacheDirectory.$file;
		if (!file_exists($fileName) || (filemtime($fileName) <= time() - $cacheTime)) {
			$content = file_get_contents('https://slack.com/api/'.$file.'?token='.$this->conf->token);
			$res = json_decode($content);
			if ($res->ok !== FALSE) {
		    	file_put_contents($fileName, $content);
			}
		}
	}

	private function checkCacheFolder () {
        if (!file_exists(self::$cacheDirectory)) {
            mkdir(self::$cacheDirectory);
        }
	}

	private function refreshCache () {
		$this->checkCacheFolder();
		$this->checkCache(self::$usersFile, -1);
		$users = json_decode(file_get_contents(self::$cacheDirectory.self::$usersFile));
		foreach ($users->members as $result) {
			$this->getPicture($result->name, $result->profile->image_24, -1);
		}
	}

	private function process () {
		$users = json_decode(file_get_contents(self::$cacheDirectory.self::$usersFile));
		foreach ($users->members as $result) {

			$value = strtolower(trim($result->name));
			$real_name = strtolower(utf8_decode(strip_tags($result->last_name)));

			if (strpos($value, $this->query) === 0) {
			    if (!isset($found[$value])) {
			        $found[$value] = true;
			        $this->results[0][] = $result;
			    }
			}
			else if (strpos($value, $this->query) > 0) {
			    if (!isset($found[$value])) {
			        $found[$value] = true;
			        $this->results[1][] = $result;
			    }
			}
			else if (strpos($real_name, $this->query) !== false) {
			    if (!isset($found[$value])) {
			        $found[$value] = true;
			        $this->results[2][] = $result;
			    }
			}
		}
	}

    private function render () {
        foreach ($this->results as $level => $results) {
            foreach ($results as $result) {
                $this->workflows->result( $result->name, $result->name, $result->real_name.' (@'.$result->name.')', $result->profile->email, $this->getPicture($result->name, $result->profile->image_24) );
            }
        }
    }
}

$slack = new Slack($query);
if ($position !== 'output') {
	$slack->input();
} else {
	$slack->output();
}
