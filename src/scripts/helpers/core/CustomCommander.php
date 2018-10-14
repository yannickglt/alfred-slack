<?php

namespace AlfredSlack\Helpers\Core;

use Frlnc\Slack\Contracts\Http\Interactor;
use Frlnc\Slack\Core\Commander;
use InvalidArgumentException;

class CustomCommander extends Commander {

  protected static $commands = null;

  public function __construct($token, Interactor $interactor) {
    if (is_null(static::$commands)) {
      static::$commands = array_merge(Commander::$commands, [
        'users.profile.set' => [
          'token' => true,
          'endpoint' => '/users.profile.set'
        ]
      ]);
    }

    parent::__construct($token, $interactor);
  }

  /**
   * Executes a command.
   *
   * @param  string $command
   * @param  array $parameters
   * @return \Frlnc\Slack\Contracts\Http\Response
   */
  public function execute($command, array $parameters = []) {
    if (!isset(static::$commands[$command]))
      throw new InvalidArgumentException("The command '{$command}' is not currently supported");

    $command = static::$commands[$command];

    if ($command['token'])
      $parameters = array_merge($parameters, ['token' => $this->token]);

    if (isset($command['format']))
      foreach ($command['format'] as $format)
        if (isset($parameters[$format]))
          $parameters[$format] = static::format($parameters[$format]);

    $headers = [];
    if (isset($command['headers']))
      $headers = $command['headers'];

    $url = static::$baseUrl . $command['endpoint'];

    if (isset($command['post']) && $command['post'])
      return $this->interactor->post($url, [], $parameters, $headers);

    return $this->interactor->get($url, $parameters, $headers);
  }

  /**
   * Executes a command.
   *
   * @param  string $command
   * @param  array $parameters
   * @return \Frlnc\Slack\Contracts\Http\Response
   */
  public function executeAll($commandsWithParameters) {
    $requests = [];
    foreach ($commandsWithParameters as $commandWithParameters) {
      $command = $commandWithParameters['command'];
      if (!empty($commandWithParameters['parameters'])) {
        $parameters = $commandWithParameters['parameters'];
      } else {
        $parameters = [];

      }
      if (!isset(static::$commands[$command]))
        throw new InvalidArgumentException("The command '{$command}' is not currently supported");

      $command = static::$commands[$command];

      if ($command['token'])
        $parameters = array_merge($parameters, ['token' => $this->token]);

      if (isset($command['format']))
        foreach ($command['format'] as $format)
          if (isset($parameters[$format]))
            $parameters[$format] = static::format($parameters[$format]);

      $headers = [];
      if (isset($command['headers']))
        $headers = $command['headers'];

      $url = static::$baseUrl . $command['endpoint'];

      if (isset($command['post']) && $command['post'])
        return $this->interactor->post($url, [], $parameters, $headers);

      $requests[] = ['url' => $url, 'parameters' => $parameters, 'headers' => $headers];
    }

    return $this->interactor->getAll($requests);
  }

  /**
   * Executes a command.
   *
   * @param  string $command
   * @param  array $parameters
   * @return \Frlnc\Slack\Contracts\Http\Response
   */
  public function executeAsync($command, array $parameters = []) {
    if (!isset(static::$commands[$command])) {
      throw new InvalidArgumentException("The command '{$command}' is not currently supported");
    }

    $command = static::$commands[$command];

    if ($command['token']) {
      $parameters = array_merge($parameters, ['token' => $this->token]);
    }

    if (isset($command['format'])) {
      foreach ($command['format'] as $format) {
        if (isset($parameters[$format])) {
          $parameters[$format] = static::format($parameters[$format]);
        }
      }
    }

    $headers = [];
    if (isset($command['headers'])) {
      $headers = $command['headers'];
    }

    $url = static::$baseUrl . $command['endpoint'];

    if (isset($command['post']) && $command['post']) {
      $this->interactor->postAsync($url, [], $parameters, $headers);
    } else {
      $this->interactor->getAsync($url, $parameters, $headers);
    }
  }

}
