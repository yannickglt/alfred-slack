<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;

class ConfigController extends SlackController {

    public function listConfigsAction ($action, $param = null) {
        
        $results = [
            [
                'title' => '--token',
                'description' => 'Set the Slack token in the keychain (recommended)',
                'autocomplete' => '--token ',
                'route' => new Route('config', 'saveToken', [ 'token' => $param ])
            ],
            [
                'title' => '--add-token',
                'description' => 'Add a Slack token in the keychain (recommended)',
                'autocomplete' => '--add-token ',
                'route' => new Route('config', 'saveToken', [ 'token' => $param ])
            ],
            [
                'title' => '--add-token-unsafe',
                'description' => 'Add a Slack token in the cache instead of the keychain (not recommended)',
                'autocomplete' => '--add-token-unsafe ',
                'route' => new Route('config', 'saveTokenUnsafe', [ 'token' => $param ])
            ],
            [
                'title' => '--token-unsafe',
                'description' => 'Set the Slack token in the cache instead of the keychain (not recommended)',
                'autocomplete' => '--token-unsafe ',
                'route' => new Route('config', 'saveTokenUnsafe', [ 'token' => $param ])
            ],
            [
                'title' => '--mark',
                'description' => 'Mark all as read',
                'autocomplete' => '--mark ',
                'route' => new Route('config', 'markAllAsRead')
            ],
            [
                'title' => '--files',
                'description' => 'List the files within the team',
                'autocomplete' => '--files ',
                'route' => new Route('config', 'getFiles', [ 'search' => $param ])
            ],
            [
                'title' => '--search',
                'description' => 'Search both messages and files',
                'autocomplete' => '--search ',
                'route' => new Route('config', 'search', [ 'search' => $param ])
            ],
            [
                'title' => '--stars',
                'description' => 'List the items starred',
                'autocomplete' => '--stars ',
                'route' => new Route('config', 'getStarredItems', [ 'search' => $param ])
            ],
            [
                'title' => '--presence',
                'description' => 'Set the user presence (either active or away)',
                'autocomplete' => '--presence ',
                'route' => new Route('config', 'listPresences', [ 'presence' => $param ])
            ],
            [
                'title' => '--refresh',
                'description' => 'Refresh the cache',
                'autocomplete' => '--refresh ',
                'route' => new Route('config', 'refreshCache')
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
                'route' => new Route('config', 'setPresence', [ 'presence' => 'away' ])
            ],
            [
                'title' => '--presence active',
                'description' => 'Set the presence as active',
                'autocomplete' => '--presence active ',
                'route' => new Route('config', 'setPresence', [ 'presence' => 'active' ])
            ],
        ];

        if (empty($presence)) {
            $this->results = $results;
        } else {
            $this->results = $this->filterResults($results, $presence);
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
                'route' => new Route('config', 'openFile', [ 'file' => $file ])
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
            if ($item instanceof \AlfredSlack\Models\MessageModel) {
                    $date = new \DateTime();
                    $date->setTimestamp($item->getTs());
                    $results[] = [
                        'title' => $item->getText(),
                        'description' => $date->format('F jS - H:i'),
                        'icon' => $this->service->getProfileIcon($item->getUser()),
                        'autocomplete' => '--stars ' . $item->getText(),
                        'route' => new Route('config', 'openFile', [ 'file' => $item ]) // open the message like a file (redirect to the slack history)
                    ];
            }
            elseif ($item instanceof \AlfredSlack\Models\FileModel) {
                    $icon = !is_null($item->getThumb64()) ? $this->service->getFileIcon($item->getId()) : null;
                    $results[] = [
                        'id' => $item->getId(),
                        'title' => $item->getName(),
                        'description' => $item->getTitle(),
                        'icon' => $icon,
                        'autocomplete' => '--stars ' . $item->getName(),
                        'route' => new Route('config', 'openFile', [ 'file' => $item ])
                    ];
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
        $items = $this->service->search($query);

        $results = [];
        foreach ($items as $item) {
            if ($item instanceof \AlfredSlack\Models\MessageModel) {
                $date = new \DateTime();
                $date->setTimestamp($item->getTs());
                $results[] = [
                    'title' => $item->getText(),
                    'description' => $date->format('F jS - H:i'),
                    'icon' => $this->service->getProfileIcon($item->getUser()),
                    'route' => new Route('config', 'openFile', [ 'file' => $item ]) // open the message like a file (redirect to the slack history)
                ];
            }
            elseif ($item instanceof \AlfredSlack\Models\FileModel) {
                $icon = !is_null($item->getThumb64()) ? $this->service->getFileIcon($item->getId()) : null;
                $results[] = [
                    'id' => $item->getId(),
                    'title' => $item->getName(),
                    'description' => $item->getTitle(),
                    'icon' => $icon,
                    'route' => new Route('config', 'openFile', [ 'file' => $item ])
                ];
            }
        }
        $this->results = $results;
        
        $this->render();
    }

    public function openFileAction ($file) {
        Utils::openUrl($file->getPermalink());
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
        $this->service->setCacheLock(true);
        try {
        	$this->service->refreshCache();
	        $this->notify('Cache refresh successfully');
        } catch (Exception $e) {
	        $this->notify('An error occured during refresh');
        }
        $this->service->setCacheLock(false);
    }

    public function markAllAsReadAction () {
        $this->service->markAllAsRead();
    }

}
