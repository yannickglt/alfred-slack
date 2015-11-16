<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
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
                'autocomplete' => '#'.$channel->getName().' ',
                'route' => new Route('slack', 'openChannel', [ 'channel' => $channel ])
            ];
        }

        $groups = $this->service->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'title' => '#'.$group->getName(),
                'description' => 'Group - ' . count($group->getMembers()) . ' members',
                'autocomplete' => '#'.$group->getName().' ',
                'route' => new Route('slack', 'openChannel', [ 'channel' => $group ])
            ];
        }

        $users = $this->getUsers();
        foreach ($users as $user) {
            $icon = $this->service->getProfileIcon($user);
            $results[] = [
                'title' => '@'.$user->getName(),
                'description' => 'User - ' . $user->getProfile()->real_name,
                'icon' => $icon,
                'autocomplete' => '@'.$user->getName().' ',
                'route' => new Route('slack', 'openChannel', [ 'channel' => $user ])
            ];
        }

        $this->results = $this->filterResults($results, $search);

        if (!empty($message) && (count($this->results) > 0)) {
            $firstResult = $this->results[0];
            $firstResult['title'] = 'Send "'.$message.'" to ' . $firstResult['title'];
            $firstResult['autocomplete'] .= $message;
            $firstResult['route'] = new Route('slack', 'sendMessage', [ 'channel' => $firstResult['route']->getParams()['channel'], 'message' => $message ]);
            $this->results = [$firstResult];
        }

        $this->render();
    }

    public function listConfigsAction ($action, $param = null) {
        
        $results = [
            [
                'title' => '--token',
                'description' => 'Set the Slack token in the keychain (recommended)',
                'autocomplete' => '--token ',
                'route' => new Route('slack', 'saveToken', [ 'token' => $param ])
            ],
            [
                'title' => '--token-unsafe',
                'description' => 'Set the Slack token in the cache instead of the keychain (not recommended)',
                'autocomplete' => '--token-unsafe ',
                'route' => new Route('slack', 'saveTokenUnsafe', [ 'token' => $param ])
            ],
            [
                'title' => '--mark',
                'description' => 'Mark all as read',
                'autocomplete' => '--mark ',
                'route' => new Route('slack', 'markAllAsRead')
            ],
            [
                'title' => '--files',
                'description' => 'List the files within the team',
                'autocomplete' => '--files ',
                'route' => new Route('slack', 'getFiles', [ 'search' => $param ])
            ],
            [
                'title' => '--search',
                'description' => 'Search both messages and files',
                'autocomplete' => '--search ',
                'route' => new Route('slack', 'search', [ 'search' => $param ])
            ],
            [
                'title' => '--stars',
                'description' => 'List the items starred',
                'autocomplete' => '--stars ',
                'route' => new Route('slack', 'getStarredItems', [ 'search' => $param ])
            ],
            [
                'title' => '--presence',
                'description' => 'Set the user presence (either active or away)',
                'autocomplete' => '--presence ',
                'route' => new Route('slack', 'listPresences', [ 'presence' => $param ])
            ],
            [
                'title' => '--refresh',
                'description' => 'Refresh the cache',
                'autocomplete' => '--refresh ',
                'route' => new Route('slack', 'refreshCache')
            ]
        ];

        if (!empty($action)) {
            $this->results = $this->filterResults($results, $action);
        } else {
            $this->results = $results;
        }

        $this->render();
    }

    public function listPresencesAction ($presence) {
        
        $results = [
            [
                'title' => '--presence away',
                'description' => 'Set the presence as away',
                'autocomplete' => '--presence away ',
                'route' => new Route('slack', 'setPresence', [ 'presence' => 'away' ])
            ],
            [
                'title' => '--presence active',
                'description' => 'Set the presence as active',
                'autocomplete' => '--presence active ',
                'route' => new Route('slack', 'setPresence', [ 'presence' => 'active' ])
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
                'id' => $channel->getId(),
                'title' => '#'.$channel->getName(),
                'description' => 'Channel - ' . $channel->getNumMembers() . ' members - ' . ($channel->getIsMember() ? 'Already a member' : 'Not a member'),
                'route' => new Route('slack', 'getChannelHistory', [ 'channel' => $channel ])
            ];
        }

        $groups = $this->service->getGroups(true);
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->getId(),
                'title' => '#'.$group->getName(),
                'description' => 'Group - ' . count($group->getMembers()) . ' members',
                'route' => new Route('slack', 'getChannelHistory', [ 'channel' => $group ])
            ];
        }

        $users = $this->getUsers();
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'title' => '@'.$user->getName(),
                'description' => 'User - ' . $user->profile->real_name,
                'route' => new Route('slack', 'getChannelHistory', [ 'channel' => $user ])
            ];
        }

        $results = $this->filterResults($results, $search);

        if (count($results) === 0) {
            return;
        }

        $history = [];
        $firstResult = $results[0];
        $data = $firstResult['route']->getParams()['channel'];
        $teamId = $data->auth->team_id;
        $icon = null;
        if ($data instanceof \AlfredSlack\Models\ChannelModel) {
            $history = $this->service->getChannelHistory($data);
            $this->service->markChannelAsRead($data);
        }
        elseif ($data instanceof \AlfredSlack\Models\GroupModel) {
            $history = $this->service->getGroupHistory($data);
            $this->service->markGroupAsRead($data);
        }
        elseif ($data instanceof \AlfredSlack\Models\UserModel) {
            $im = $this->service->getImByUser($data);
            $history = $this->service->getImHistory($im);
            $icon = $this->service->getProfileIcon($data);
            $this->service->markImAsRead($im);
        }

        foreach ($history as $message) {
            $date = new \DateTime();
            $date->setTimestamp($message->getTs());
            $this->results[] = [
                'title' => $message->getText(),
                'description' => $date->format('F jS - H:i'),
                'icon' => $icon,
                'autocomplete' => $firstResult['title'].' ',
                'route' => $firstResult['route']
            ];
        }

        $this->render();
    }

    public function getFilesAction ($search) {
        $files = $this->service->getFiles();

        $results = [];
        foreach ($files as $file) {
            $icon = !is_null($file->getThumb64()) ? $this->service->getFileIcon($file->getId()) : null;
            $results[] = [
                'id' => $file->getId(),
                'title' => $file->getName(),
                'description' => $file->getTitle(),
                'icon' => $icon,
                'autocomplete' => '--files ' . $file->getName(),
                'route' => new Route('slack', 'openFile', [ 'file' => $file ])
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
                    $date = new \DateTime();
                    $date->setTimestamp($item->message->getTs());
                    $results[] = [
                        'title' => $item->message->getText(),
                        'description' => $date->format('F jS - H:i'),
                        'icon' => $this->service->getProfileIcon($item->message->getUser()),
                        'autocomplete' => '--stars ' . $item->message->getText(),
                        'route' => new Route('slack', 'openFile', [ 'file' => $item->message ]) // open the message like a file (redirect to the slack history)
                    ];
                    break;
                case 'file':
                    $icon = !is_null($item->file->getThumb64()) ? $this->service->getFileIcon($item->file->getId()) : null;
                    $results[] = [
                        'id' => $item->file->getId(),
                        'title' => $item->file->getName(),
                        'description' => $item->file->getTitle(),
                        'icon' => $icon,
                        'autocomplete' => '--stars ' . $item->file->getName(),
                        'route' => new Route('slack', 'openFile', [ 'file' => $item->file ])
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
            $date = new \DateTime();
            $date->setTimestamp($message->getTs());
            $results[] = [
                'title' => $message->getText(),
                'description' => $date->format('F jS - H:i'),
                'icon' => $this->service->getProfileIcon($message->getUser()),
                'route' => new Route('slack', 'openFile', [ 'file' => $message ]) // open the message like a file (redirect to the slack history)
            ];
        }
        foreach ($searchResults['files'] as $file) {
            $icon = !is_null($file->getThumb64()) ? $this->service->getFileIcon($file->getId()) : null;
            $results[] = [
                'id' => $file->getId(),
                'title' => $file->getName(),
                'description' => $file->getTitle(),
                'icon' => $icon,
                'route' => new Route('slack', 'openFile', [ 'file' => $file ])
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

    public function openChannelAction (\AlfredSlack\Models\ChatInterface $channel) {

        $id = $channel->getId();

        // Get the IM id if a user
        if ($channel instanceof \AlfredSlack\Models\UserModel) {
            $im = $this->service->getImByUser($channel);
            $id = $im->getId();
            //$id = $this->service->getImIdByUserId($id);
        }

        $url = 'slack://channel?id='.$id.'&team='.$channel->getAuth()->getTeamId();
        Utils::openUrl($url);
        Utils::openApp('Slack');
    }

    public function openFileAction ($file) {
        Utils::openUrl($file->getPermalink());
    }

    public function sendMessageAction ($channel, $message) {

        $this->service->postMessage($channel, $message);

        // Get the IM id if a user
        $title = $channel->getName();
        if ($channel instanceof \AlfredSlack\Models\UserModel) {
            $title = '@' . $title;
        } else {
            $title = '#' . $title;
        }

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
            $this->workflows->result($i++, json_encode($result['route']), $result['title'], $result['description'], $icon, 'yes', $result['autocomplete']);
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
