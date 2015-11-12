<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;

class SlackController {

    private $service;
    private $workflows;
    private $results;
    private $isRendered = false;
    private $isNotified = false;
    
    public function __construct () {
        Utils::defineTimeZone();
        $this->workflows = Utils::getWorkflows();
        $this->service = new MultiTeamSlackService();
        $this->results = [];
    }

    public function getChannelsAction ($search, $message = null) {
        $results = [];

        $channels = $this->service->getChannels(true);
        foreach ($channels as $channel) {
            $results[] = [
                'title' => '#'.$channel->getName(),
                'description' => 'Channel - ' . $channel->getNumMembers() . ' members - ' . ($channel->getIsMember() ? 'Already a member' : 'Not a member'),
                'data' => Utils::extend($channel, [ 'type' => 'channel', 'message' => $message ]),
                'autocomplete' => '#'.$channel->getName().' '
            ];
        }

        $groups = $this->service->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'title' => '#'.$group->getName(),
                'description' => 'Group - ' . count($group->getMembers()) . ' members',
                'data' => Utils::extend($group, [ 'type' => 'group', 'message' => $message ]),
                'autocomplete' => '#'.$group->getName().' '
            ];
        }

        $users = $this->getUsers();
        foreach ($users as $user) {
            $icon = $this->service->getProfileIcon($user);
            $results[] = [
                'title' => '@'.$user->getName(),
                'description' => 'User - ' . $user->getProfile()->real_name,
                'icon' => $icon,
                'data' => Utils::extend($user, [ 'type' => 'user', 'message' => $message ]),
                'autocomplete' => '@'.$user->getName().' '
            ];
        }

        $this->results = $this->filterResults($results, $search);
        if (!is_null($message)) {
            $firstResult = $this->results[0];
            $firstResult['title'] = 'Send "'.$message.'" to ' . $firstResult['title'];
            $firstResult['autocomplete'] .= $message;
            $this->results = [$firstResult];
        }

        $this->render();
    }

    public function listConfigsAction ($action, $param = null) {
        
        $results = [
            [
                'title' => '--token',
                'description' => 'Set the Slack token in the keychain (recommended)',
                'data' => Utils::toObject([ 'type' => 'token', 'token' => $param ]),
                'autocomplete' => '--token '
            ],
            [
                'title' => '--token-unsafe',
                'description' => 'Set the Slack token in the cache instead of the keychain (not recommended)',
                'data' => Utils::toObject([ 'type' => 'token-unsafe', 'token' => $param ]),
                'autocomplete' => '--token-unsage '
            ],
            [
                'title' => '--mark',
                'description' => 'Mark all as read',
                'data' => Utils::toObject([ 'type' => 'mark' ]),
                'autocomplete' => '--mark '
            ],
            [
                'title' => '--files',
                'description' => 'List the files within the team',
                'data' => Utils::toObject([ 'type' => 'files' ]),
                'autocomplete' => '--files '
            ],
            [
                'title' => '--search',
                'description' => 'Search both messages and files',
                'data' => Utils::toObject([ 'type' => 'search' ]),
                'autocomplete' => '--search '
            ],
            [
                'title' => '--stars',
                'description' => 'List the items starred',
                'data' => Utils::toObject([ 'type' => 'stars' ]),
                'autocomplete' => '--stars '
            ],
            [
                'title' => '--presence',
                'description' => 'Set the user presence (either active or away)',
                'data' => Utils::toObject([ 'type' => 'presence', 'presence' => $param ]),
                'autocomplete' => '--presence '
            ],
            [
                'title' => '--refresh',
                'description' => 'Refresh the cache',
                'data' => Utils::toObject([ 'type' => 'refresh' ]),
                'autocomplete' => '--refresh '
            ]
        ];

        $this->results = $this->filterResults($results, $action);
        $this->render();
    }

    public function listPresencesAction ($presence) {
        
        $results = [
            [
                'title' => '--presence away',
                'description' => 'Set the presence as away',
                'data' => Utils::toObject([ 'type' => 'presence', 'presence' => 'away' ]),
                'autocomplete' => '--presence away '
            ],
            [
                'title' => '--presence active',
                'description' => 'Set the presence as active',
                'data' => Utils::toObject([ 'type' => 'presence', 'presence' => 'active' ]),
                'autocomplete' => '--presence active '
            ],
        ];

        if (empty($presence)) {
            $this->results = $results;
        } else {
            $this->results = $this->filterResults($results, $presence);
        }
        $this->render();
    }

    public function getChannelHistoryAction ($search) {

        $results = [];

        $channels = $this->service->getChannels(true);
        foreach ($channels as $channel) {
            $results[] = [
                'id' => $channel->id,
                'title' => '#'.$channel->name,
                'description' => 'Channel - ' . $channel->num_members . ' members - ' . ($channel->is_member ? 'Already a member' : 'Not a member'),
                'type' => 'channel',
                'data' => Utils::extend($channel, [ 'type' => 'channel' ])
            ];
        }

        $groups = $this->service->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->id,
                'title' => '#'.$group->name,
                'description' => 'Group - ' . count($group->members) . ' members',
                'type' => 'group',
                'data' => Utils::extend($group, [ 'type' => 'group' ])
            ];
        }

        $users = $this->getUsers();
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->id,
                'title' => '@'.$user->name,
                'description' => 'User - ' . $user->profile->real_name,
                'type' => 'user',
                'data' => Utils::extend($user, [ 'type' => 'user' ])
            ];
        }

        $results = $this->filterResults($results, $search);

        if (count($results) === 0) {
            return;
        }

        $history = [];
        $icon = null;
        $firstResult = $results[0];
        $teamId = $firstResult['data']->auth->team_id;
        switch($firstResult['type']) {
            case 'channel':
                $history = $this->service->getChannelHistory($teamId, $firstResult['id']);
                $this->service->markChannelAsRead($teamId, $firstResult['id']);
                break;
            case 'group':
                $history = $this->service->getGroupHistory($teamId, $firstResult['id']);
                $this->service->markGroupAsRead($teamId, $firstResult['id']);
                break;
            case 'user':
                $imId = $this->service->getImIdByUserId($teamId, $firstResult['id']);
                $history = $this->service->getImHistory($teamId, $imId);
                $this->service->markImAsRead($imId);
                break;
        }

        foreach ($history as $message) {
            $date = new DateTime();
            $date->setTimestamp($message['ts']);
            $this->results[] = [
                'title' => $message['text'],
                'description' => $date->format('F jS - H:i'),
                'icon' => $this->service->getProfileIcon($message['user']),
                'data' => $firstResult['data'],
                'autocomplete' => $firstResult['title'].' '
            ];
        }

        $this->render();
    }

    public function getFilesAction ($search) {
        $files = $this->service->getFiles();

        $results = [];
        foreach ($files as $file) {
            $icon = !is_null($file->thumb_64) ? $this->service->getFileIcon($file->id) : null;
            $results[] = [
                'id' => $file->id,
                'title' => $file->name,
                'description' => $file->title,
                'icon' => $icon,
                'data' => Utils::extend($file, [ 'type' => 'file' ]),
                'autocomplete' => '--files ' . $file->name
            ];
        }

        if (empty($search)) {
            $this->results = $results;
        } else {
            $this->results = $this->filterResults($results, $search);
        }
        
        $this->render();
    }

    public function getStarredItemsAction ($search) {
        $items = $this->service->getStarredItems();

        $results = [];
        foreach ($items as $item) {
            switch ($item->type) {
                case 'message':
                    $date = new DateTime();
                    $date->setTimestamp($item->message->ts);
                    $results[] = [
                        'title' => $item->message->text,
                        'description' => $date->format('F jS - H:i'),
                        'icon' => $this->service->getProfileIcon($item->message->user),
                        'data' => Utils::extend($item->message, [ 'type' => 'file' ]),
                        'autocomplete' => '--stars ' . $item->message->text
                    ];
                    break;
                case 'file':
                    $icon = !is_null($item->file->thumb_64) ? $this->service->getFileIcon($item->file->id) : null;
                    $results[] = [
                        'id' => $item->file->id,
                        'title' => $item->file->name,
                        'description' => $item->file->title,
                        'icon' => $icon,
                        'data' => Utils::extend($item->file, [ 'type' => 'file' ]),
                        'autocomplete' => '--stars ' . $item->file->name
                    ];
                    break;
            }
        }

        if (empty($search)) {
            $this->results = $results;
        } else {
            $this->results = $this->filterResults($results, $search);
        }
        
        $this->render();
    }

    public function searchAction ($query) {
        $searchResults = $this->service->search($query);

        $results = [];
        foreach ($searchResults['messages'] as $message) {
            $date = new DateTime();
            $date->setTimestamp($message['ts']);
            $results[] = [
                'title' => $message['text'],
                'description' => $date->format('F jS - H:i'),
                'icon' => $this->service->getProfileIcon($message['user']),
                'data' => Utils::extend($message, [ 'type' => 'file' ])
            ];
        }
        foreach ($searchResults['files'] as $file) {
            $icon = !is_null($file->thumb_64) ? $this->service->getFileIcon($file->id) : null;
            $results[] = [
                'id' => $file->id,
                'title' => $file->name,
                'description' => $file->title,
                'icon' => $icon,
                'data' => Utils::extend($file, [ 'type' => 'file' ])
            ];
        }

        $this->results = $results;
        
        $this->render();
    }

    public function getCacheLockedMessageAction () {
        $this->results = [
            [
                'id' => '',
                'title' => 'Refresh still in progress',
                'description' => 'Please wait the end of cache refresh...'
            ]
        ];

        $this->render();
    }

    public function openChannelAction ($data) {

        $id = $data->id;

        // Get the IM id if a user
        if ($data->type === 'user') {
            $id = $this->service->getImIdByUserId($data->id);
        }

        $url = 'slack://channel?id='.$id.'&team='.$data->auth->team_id;
        Utils::openUrl($url);
        Utils::openApp('Slack');
    }

    public function openFileAction ($file) {
        Utils::openUrl($file->permalink);
    }

    public function sendMessageAction ($data) {

        $id = $data->id;
        $title = $data->name;

        // Get the IM id if a user
        if ($data->type === 'user') {
            $id = $this->service->getImIdByUserId($data->id);
            $title = '@' . $title;
        } else {
            $title = '#' . $title;
        }

        $this->service->postMessage($id, $data->message);

        $this->notify('Message sent successfully to ' . $title);
    }

    public function saveTokenAction ($token) {
        $this->service->addToken($token);
        $this->notify('Token saved successfully');
    }

    public function saveTokenUnsafeAction ($token) {
        $this->service->addTokenUnsafe($token);
        $this->notify('Token saved successfully');
    }

    public function setPresenceAction ($presence) {
        $isAway = (strtolower($presence) === 'away');
        $this->service->setPresence($isAway);
        $this->notify('You are now marked as ​"' . ($isAway ? 'away' : 'active') . '"');
    }

    public function refreshCacheAction () {
        $this->service->refreshCache();
        $this->notify('Cache refresh successfully');
    }

    public function markAllAsReadAction () {
        $this->service->markAllAsRead();
    }

    private function render () {
        if ($this->isNotified) {
            throw new Exception('Cannot use the method "render" if the method "notify" was called');
        }

        $i = 0;
        foreach ($this->results as $result) {
            $icon = isset($result['icon']) ? $result['icon'] : Utils::$icon;
            $this->workflows->result($i++, json_encode($result['data']), $result['title'], $result['description'], $icon, 'yes', $result['autocomplete']);
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

    private function getUsers ($excludeSlackBot = false) {
        $users = $this->service->getUsers(true);
        if ($excludeSlackBot !== false) {
            $users = Utils::filter($users, function ($user) {
                return $user->getId() !== $user->getAuth()->user_id;
            });
        } else {
            $meInTeams = Utils::filter($users, function ($user) {
                return $user->getId() === $user->getAuth()->user_id;
            });
            foreach ($meInTeams as $me) {
                $me->setName('slackbot');
                $me->getProfile()->real_name = 'slackbot';
            }
        }
        return $users;
    }

    private function filterResults ($array, $search) {
        $search = strtolower(trim($search));
        $found = [];
        $results = [];
        foreach ($array as $id => $element) {
            
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
