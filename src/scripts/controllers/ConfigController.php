<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;
use AlfredSlack\Models\ModelFactory;
use AlfredSlack\Models\StatusModel;

class ConfigController extends SlackController {

  public function listConfigsAction($action, $param = null) {

    $results = [
      [
        'title' => '--add-client',
        'description' => 'Add a Slack client',
        'autocomplete' => '--add-client ',
        'route' => new Route('config', 'saveClient', ['clientCredentials' => $param])
      ],
      [
        'title' => '--remove-client',
        'description' => 'Remove a Slack client',
        'autocomplete' => '--remove-client ',
        'route' => new Route('config', 'removeClient', ['team' => $param])
      ],
      [
        'title' => '--add-token',
        'description' => 'Add a Slack token in the keychain (deprecated)',
        'autocomplete' => '--add-token ',
        'route' => new Route('config', 'saveToken', ['token' => $param])
      ],
      [
        'title' => '--add-token-unsafe',
        'description' => 'Add a Slack token in the cache instead of the keychain (deprecated)',
        'autocomplete' => '--add-token-unsafe ',
        'route' => new Route('config', 'saveTokenUnsafe', ['token' => $param])
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
        'route' => new Route('config', 'getFiles', ['search' => $param])
      ],
      [
        'title' => '--search',
        'description' => 'Search both messages and files',
        'autocomplete' => '--search ',
        'route' => new Route('config', 'search', ['search' => $param])
      ],
      [
        'title' => '--stars',
        'description' => 'List the items starred',
        'autocomplete' => '--stars ',
        'route' => new Route('config', 'getStarredItems', ['search' => $param])
      ],
      [
        'title' => '--status',
        'description' => 'List the custom statuses',
        'autocomplete' => '--status ',
        'route' => new Route('config', 'getStatuses', ['status' => $param])
      ],
      [
        'title' => '--presence',
        'description' => 'Set the user presence (either active or away)',
        'autocomplete' => '--presence ',
        'route' => new Route('config', 'listPresences', ['presence' => $param])
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

  public function listPresencesAction($presence) {

    $results = [
      [
        'title' => '--presence away',
        'description' => 'Set the presence as away',
        'icon' => 'images/circle_grey.png',
        'autocomplete' => '--presence away ',
        'route' => new Route('config', 'setPresence', ['presence' => 'away'])
      ],
      [
        'title' => '--presence active',
        'description' => 'Set the presence as active',
        'icon' => 'images/circle_green.png',
        'autocomplete' => '--presence active ',
        'route' => new Route('config', 'setPresence', ['presence' => 'active'])
      ],
    ];

    if (empty($presence)) {
      $this->results = $results;
    } else {
      $this->results = $this->filterResults($results, $presence);
    }

    $this->render();
  }

  public function getFilesAction($search) {
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
        'route' => new Route('config', 'openFile', ['file' => $file])
      ];
    }

    if (empty($search)) {
      $this->results = $results;
    } else {
      $this->results = $this->filterResults($results, $search);
    }

    $this->render(false);
  }

  public function getTeamsAction($search) {
    $teams = $this->service->getTeams();

    $results = [];
    foreach ($teams as $team) {
      $results[] = [
        'id' => $team->team_id,
        'title' => $team->team,
        'description' => 'Remove the Slack client "'.$team->team.'"',
        'autocomplete' => '--remove-client ' . $team->team,
        'route' => new Route('config', 'removeClient', ['team' => $team->team])
      ];
    }

    if (empty($search)) {
      $this->results = $results;
    } else {
      $this->results = $this->filterResults($results, $search);
    }

    $this->render(false);
  }

  public function getStarredItemsAction($search) {
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
          'route' => new Route('config', 'openFile', ['file' => $item]) // open the message like a file (redirect to the slack history)
        ];
      } elseif ($item instanceof \AlfredSlack\Models\FileModel) {
        $icon = !is_null($item->getThumb64()) ? $this->service->getFileIcon($item->getId()) : null;
        $results[] = [
          'id' => $item->getId(),
          'title' => $item->getName(),
          'description' => $item->getTitle(),
          'icon' => $icon,
          'autocomplete' => '--stars ' . $item->getName(),
          'route' => new Route('config', 'openFile', ['file' => $item])
        ];
      }
    }

    if (empty($search)) {
      $this->results = $results;
    } else {
      $this->results = $this->filterResults($results, $search);
    }

    $this->render(false);
  }

  public function getStatusesAction($status) {
    $results = [
      [
        'title' => '--status in a meeting',
        'description' => 'Set the custom status as "In a meeting" - 1 hour',
        'icon' => 'images/spiral_calendar.png',
        'autocomplete' => '--status meeting ',
        'route' => new Route('config', 'setStatus', ['status' => 'meeting'])
      ],
      [
        'title' => '--status commuting',
        'description' => 'Set the custom status as "Commuting" - 30 minutes',
        'icon' => 'images/bus.png',
        'autocomplete' => '--status commuting ',
        'route' => new Route('config', 'setStatus', ['status' => 'commuting'])
      ],
      [
        'title' => '--status out sick',
        'description' => 'Set the custom status as "Out sick" - Today',
        'icon' => 'images/face_with_thermometer.png',
        'autocomplete' => '--status sick ',
        'route' => new Route('config', 'setStatus', ['status' => 'sick'])
      ],
      [
        'title' => '--status vacationing',
        'description' => 'Set the custom status as "Vacationing" - Don\'t clear',
        'icon' => 'images/palm_tree.png',
        'autocomplete' => '--status vacationing ',
        'route' => new Route('config', 'setStatus', ['status' => 'vacationing'])
      ],
      [
        'title' => '--status working remotely',
        'description' => 'Set the custom status as "Working remotely" - Today',
        'icon' => 'images/house_with_garden.png',
        'autocomplete' => '--status remote ',
        'route' => new Route('config', 'setStatus', ['status' => 'remote'])
      ],
      [
        'title' => '--status clear',
        'description' => 'Clear the current custom status',
        'icon' => 'images/cross_mark.png',
        'autocomplete' => '--status clear ',
        'route' => new Route('config', 'setStatus', ['status' => 'clear'])
      ]
    ];

    if (empty($status)) {
      $this->results = $results;
    } else {
      $this->results = $this->filterResults($results, $status);
    }

    $this->render();
  }

  public function searchAction($query) {
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
          'route' => new Route('config', 'openFile', ['file' => $item]) // open the message like a file (redirect to the slack history)
        ];
      } elseif ($item instanceof \AlfredSlack\Models\FileModel) {
        $icon = !is_null($item->getThumb64()) ? $this->service->getFileIcon($item->getId()) : null;
        $results[] = [
          'id' => $item->getId(),
          'title' => $item->getName(),
          'description' => $item->getTitle(),
          'icon' => $icon,
          'route' => new Route('config', 'openFile', ['file' => $item])
        ];
      }
    }
    $this->results = $results;

    $this->render(false);
  }

  public function openFileAction($file) {
    Utils::openUrl($file->getPermalink());
  }

  public function saveClientAction($clientCredentials) {
    try {
      $this->service->addClient($clientCredentials);
      $this->notify('Client saved successfully');
    } catch (\Exception $e) {
      $this->notify($e->getMessage());
    }
  }

  public function removeClientAction($team) {
    try {
      $this->service->removeTeam($team);
      $this->notify('Client removed successfully');
    } catch (\Exception $e) {
      $this->notify($e->getMessage());
    }
  }

  public function saveTokenAction($token) {
    $this->service->addToken($token);
    $this->notify('Token saved successfully');
  }

  public function saveTokenUnsafeAction($token) {
    $this->service->addTokenUnsafe($token);
    $this->notify('Token saved successfully');
  }

  public function setPresenceAction($presence) {
    $isAway = (strtolower($presence) === 'away');
    $this->service->setPresence($isAway);
    $this->notify('You are now marked as ​"' . ($isAway ? 'away' : 'active') . '"');
  }

  public function setStatusAction($statusName) {
    $status = new StatusModel([
      'status_text' => '',
      'status_emoji' => '',
      'status_expiration' => 0
    ]);
    switch ($statusName) {
      case 'meeting':
        $status = new StatusModel([
          'status_text' => 'In a meeting',
          'status_emoji' => ':spiral_calendar_pad:',
          'status_expiration' => time() + 3600
        ]);
        break;
      case 'commuting':
        $status = new StatusModel([
          'status_text' => 'Commuting',
          'status_emoji' => ':bus:',
          'status_expiration' => time() + 1800
        ]);
        break;
      case 'sick':
        $status = new StatusModel([
          'status_text' => 'Out sick',
          'status_emoji' => ':face_with_thermometer:',
          'status_expiration' => strtotime(date('Y-m-d 23:59:59', time()))
        ]);
        break;
      case 'vacationing':
        $status = new StatusModel([
          'status_text' => 'Vacationing',
          'status_emoji' => ':palm_tree:',
          'status_expiration' => 0
        ]);
        break;
      case 'remote':
        $status = new StatusModel([
          'status_text' => 'Working remotely',
          'status_emoji' => ':house_with_garden:',
          'status_expiration' => strtotime(date('Y-m-d 23:59:59', time()))
        ]);
        break;
    }

    $this->service->setStatus($status);

    if (empty($status->status_text)) {
      $this->notify('Your custom status was cleared');
    } else {
      $this->notify('You are now marked as "' . $status->status_text . '"');
    }
  }

  public function refreshCacheAction() {
    $this->service->setCacheLock(true);
    try {
      $this->service->refreshCache();
      $this->notify('Cache refresh successfully');
    } catch (Exception $e) {
      $this->notify('An error occured during refresh');
    }
    $this->service->setCacheLock(false);
  }

  public function markAllAsReadAction() {
    $this->service->markAllAsRead();
  }

}
