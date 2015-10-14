<?php

require_once 'Utils.php';
require_once 'SlackModel.php';

class SlackController {

    private $model;
    private $workflows;
    private $results;
    private $isRendered = false;
    private $isNotified = false;
    
    public function __construct () {
        $this->workflows = Utils::getWorkflows();
        $this->model = new SlackModel();
        $this->results = [];
    }

    public function getChannelsAction ($search, $message = null) {
        $results = [];

        $auth = $this->model->getAuth();

        $channels = $this->model->getChannels(true);
        foreach ($channels as $channel) {
            $results[] = [
                'id' => $channel->id,
                'title' => '#'.$channel->name,
                'description' => 'Channel - ' . $channel->num_members . ' members - ' . ($channel->is_member ? 'Already a member' : 'Not a member'),
                'data' => Utils::extend($channel, [ 'type' => 'channel', 'auth' => $auth, 'message' => $message ])
            ];
        }

        $groups = $this->model->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->id,
                'title' => '#'.$group->name,
                'description' => 'Group - ' . count($group->members) . ' members',
                'data' => Utils::extend($group, [ 'type' => 'group', 'auth' => $auth, 'message' => $message ])
            ];
        }

        $users = $this->model->getUsers(true);
        foreach ($users as $user) {
            $icon = $this->model->getProfileIcon($user->id);
            $results[] = [
                'id' => $user->id,
                'title' => '@'.$user->name,
                'description' => 'User - ' . $user->profile->real_name,
                'icon' => $icon,
                'data' => Utils::extend($user, [ 'type' => 'user', 'auth' => $auth, 'message' => $message ])
            ];
        }

        $this->results = $this->filterResults($results, $search);
        if (!is_null($message)) {
            $firstResult = $this->results[0];
            $firstResult['title'] = 'Sends "'.$message.'" to ' . $firstResult['title'];
            $this->results = [$firstResult];
        }

        $this->render();
    }

    public function listConfigsAction ($action, $param = null) {
        
        $results = [
            [
                'id' => 'token',
                'title' => '--token',
                'description' => 'Set the Slack token',
                'data' => Utils::toObject([ 'type' => 'token', 'token' => $param ])
            ],
            [
                'id' => 'mark',
                'title' => '--mark',
                'description' => 'Mark all as read',
                'data' => Utils::toObject([ 'type' => 'mark' ])
            ],
            [
                'id' => 'refresh',
                'title' => '--refresh',
                'description' => 'Refresh the cache',
                'data' => Utils::toObject([ 'type' => 'refresh' ])
            ]
        ];

        $this->results = $this->filterResults($results, $action);
        $this->render();
    }

    public function getChannelHistoryAction ($search) {

        $results = [];

        $auth = $this->model->getAuth();

        $channels = $this->model->getChannels(true);
        foreach ($channels as $channel) {
            $results[] = [
                'id' => $channel->id,
                'title' => '#'.$channel->name,
                'description' => 'Channel - ' . $channel->num_members . ' members - ' . ($channel->is_member ? 'Already a member' : 'Not a member'),
                'type' => 'channel',
                'data' => Utils::extend($channel, [ 'type' => 'channel', 'auth' => $auth ])
            ];
        }

        $groups = $this->model->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->id,
                'title' => '#'.$group->name,
                'description' => 'Group - ' . count($group->members) . ' members',
                'type' => 'group',
                'data' => Utils::extend($group, [ 'type' => 'group', 'auth' => $auth ])
            ];
        }

        $users = $this->model->getUsers(true);
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id,
                'title' => '@'.$user->name,
                'description' => 'User - ' . $user->profile->real_name,
                'type' => 'user',
                'data' => Utils::extend($user, [ 'type' => 'user', 'auth' => $auth ])
            ];
        }

        $results = $this->filterResults($results, $search);

        $history = [];
        $icon = null;
        switch($results[0]['type']) {
            case 'channel':
                $history = $this->model->getChannelHistory($results[0]['id']);
                break;
            case 'group':
                $history = $this->model->getGroupHistory($results[0]['id']);
                break;
            case 'user':
                $history = $this->model->getImHistory($this->model->getImIdByUserId($results[0]['id']));
                break;
        }

        foreach ($history as $message) {
            $date = new DateTime();
            $date->setTimestamp($message['ts']);
            $this->results[] = [
                'id' => $message['ts'],
                'title' => $message['text'],
                'description' => $date->format('F jS - H:i'),
                'icon' => ($results[0]['type'] === 'user') ? $this->model->getProfileIcon($message['user']) : null,
                'data' => $results[0]['data']
            ];
        }
        
        $this->render();
    }

    public function openChannelAction ($data) {

        $id = $data->id;

        // Get the IM id if a user
        if ($data->type === 'user') {
            $id = $this->model->getImIdByUserId($data->id);
        }

        $url = 'slack://channel?id='.$id.'&team='.$data->auth->team_id;
        Utils::openUrl($url);
    }

    public function sendMessageAction ($data) {

        $id = $data->id;
        $title = $data->name;

        // Get the IM id if a user
        if ($data->type === 'user') {
            $id = $this->model->getImIdByUserId($data->id);
            $title = '@' . $title;
        } else {
            $title = '#' . $title;
        }

        $this->model->postMessage($id, $data->message);

        $this->notify('Message sent successfully to ' . $title);
    }

    public function saveTokenAction ($token) {
        $this->model->setToken($token);
        $this->notify('Token saved successfully');
    }

    public function refreshCacheAction () {
        $this->model->refreshCache();
        $this->notify('Cache refresh successfully');
    }

    public function markAllAsReadAction () {
        $this->model->markAllAsRead();
    }

    private function render () {
        if ($this->isNotified) {
            throw new Exception('Cannot use the method "render" if the method "notify" was called');
        }

        foreach ($this->results as $result) {
            $icon = isset($result['icon']) ? $result['icon'] : Utils::$icon;
            $this->workflows->result($result['id'], json_encode($result['data']), $result['title'], $result['description'], $icon, 'yes', $result['title'].' ');
        }

        $this->isRendered = true;

        echo $this->workflows->toxml();
    }

    private function notify ($message) {
        if ($this->isRendered) {
            throw new Exception('Cannot use the method "render" if the method "notify" was called');
        }

        $this->isNotified = true;

        echo $message;
    }

    private function filterResults ($array, $search) {
        $search = strtolower(trim($search));
        $found = [];
        $results = [];
        foreach ($array as $element) {
            $id = $element['id'];
            $title = strtolower(trim($element['title']));
            $description = strtolower(trim($element['description']));

            if ($title === $search) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $results[0][] = $element;
                }
            }
            else if (strpos($title, $search) === 0) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $results[1][] = $element;
                }
            }
            else if (strpos($title, $search) > 0) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $element['__searchIndex'] = strpos($title, $search);
                    $results[2][] = $element;
                }
            }
            else if (strpos($description, $search) !== false) {
                if (!isset($found[$id])) {
                    $found[$id] = true;
                    $element['__searchIndex'] = strpos($description, $search);
                    $results[3][] = $element;
                }
            }
        }

        if (isset($results[2])) {
            usort($results[2], function ($a, $b) {
                if ($a['__searchIndex'] === $b['__searchIndex']) {
                    $al = strlen($a['title']);
                    $bl = strlen($b['title']);
                    if ($al === $bl) {
                        return 0;
                    }
                    return ($al < $bl) ? -1 : 1;
                }
                return ($a['__searchIndex'] < $b['__searchIndex']) ? -1 : 1;
            });
        }

        if (isset($results[3])) {
            usort($results[3], function ($a, $b) {
                if ($a['__searchIndex'] === $b['__searchIndex']) {
                    $al = strlen($a['description']);
                    $bl = strlen($b['description']);
                    if ($al === $bl) {
                        return 0;
                    }
                    return ($al < $bl) ? -1 : 1;
                }
                return ($a['__searchIndex'] < $b['__searchIndex']) ? -1 : 1;
            });
        }

        ksort($results);

        $return = [];
        foreach ($results as $resultPerLevel) {
            $return = array_merge($return, $resultPerLevel);
        }
        return $return;
    }

}
