<?php

namespace AlfredSlack\Controllers;

use AlfredSlack\Libs\Utils;
use AlfredSlack\Libs\Route;
use AlfredSlack\Helpers\Service\MultiTeamSlackService;

class ChannelController extends SlackController {

  public function getChannelsAction($search, $message = null) {
    $results = [];

    $channels = $this->service->getChannels(true);
    foreach ($channels as $channel) {
      $results[] = [
        'title' => '#' . $channel->getName(),
        'description' => $channel . '',
        'autocomplete' => '#' . $channel->getName() . ' ',
        'route' => new Route('channel', 'openChannel', ['channel' => $channel])
      ];
    }

    $users = $this->getUsers();
    foreach ($users as $user) {
      $icon = $this->service->getProfileIcon($user->getId());
      $results[] = [
        'title' => '@' . $user->getName(),
        'description' => $user . '',
        'icon' => $icon,
        'autocomplete' => '@' . $user->getName() . ' ',
        'route' => new Route('channel', 'openChannel', ['channel' => $user])
      ];
    }

    $this->results = $this->deduplicateChannels($results);

    $this->results = $this->filterResults($this->results, $search);

    if (!empty($message) && (count($this->results) > 0)) {
      $firstResult = $this->results[0];
      $firstResult['title'] = 'Send "' . $message . '" to ' . $firstResult['title'];
      $firstResult['autocomplete'] .= $message;
      $firstResult['route'] = new Route('channel', 'sendMessage', ['channel' => $firstResult['route']->getParams()['channel'], 'message' => $message]);
      $this->results = [$firstResult];
    }

    $this->render();
  }

  public function getChannelHistoryAction($search) {

    $results = [];

    $channels = $this->service->getChannels(true);
    foreach ($channels as $channel) {
      $results[] = [
        'id' => $channel->getId(),
        'title' => '#' . $channel->getName(),
        'description' => $channel . '',
        'autocomplete' => '#' . $channel->getName() . ' ',
        'route' => new Route('channel', 'openChannel', ['channel' => $channel])
      ];
    }

    $users = $this->getUsers();
    foreach ($users as $user) {
      $results[] = [
        'id' => $user->getId(),
        'title' => '@' . $user->getName(),
        'description' => $user . '',
        'autocomplete' => '@' . $user->getName() . ' ',
        'route' => new Route('channel', 'openChannel', ['channel' => $user])
      ];
    }

    $results = $this->deduplicateChannels($results);

    $results = $this->filterResults($results, $search);

    if (count($results) === 0) {
      return;
    }

    $history = [];
    $firstResult = $results[0];
    $data = $firstResult['route']->getParams()['channel'];
    $icon = null;
    if ($data instanceof \AlfredSlack\Models\ChannelModel) {
      $history = $this->service->getChannelHistory($data);
    } elseif ($data instanceof \AlfredSlack\Models\GroupModel) {
      $history = $this->service->getGroupHistory($data);
      $this->service->markGroupAsRead($data);
    } elseif ($data instanceof \AlfredSlack\Models\UserModel) {
      $im = $this->service->getImByUser($data);
      $history = $this->service->getImHistory($im);
      $icon = $this->service->getProfileIcon($data->getId());
      $this->service->markImAsRead($im);
    }

    if (empty($history)) {
      $this->results[] = [
        'title' => 'No history',
        'icon' => $icon,
        'autocomplete' => $firstResult['title'] . ' ',
        'route' => $firstResult['route']
      ];
    } else {
      foreach ($history as $message) {
        $date = new \DateTime();
        $date->setTimestamp($message->getTs());
        $this->results[] = [
          'title' => $message->getText(),
          'description' => $date->format('F jS - H:i'),
          'icon' => $icon,
          'autocomplete' => $firstResult['title'] . ' ',
          'route' => $firstResult['route']
        ];
      }
    }

    $this->render(false);
  }

  public function openChannelAction(\AlfredSlack\Models\ChatInterface $channel) {

    $id = $channel->getId();

    $url = 'slack://channel?id=' . $id . '&team=' . $channel->getAuth()->getTeamId();

    Utils::openUrl($url);
    Utils::openApp('Slack');
  }

  public function sendMessageAction($channel, $message) {

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

  private function getUsers($excludeSlackBot = false) {
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
        $me->getProfile()->real_name = "{$me->getProfile()->real_name} (you)";
      }
    }
    return $users;
  }
}
