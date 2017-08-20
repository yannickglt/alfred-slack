<?php

namespace AlfredSlack\Helpers\Http;

use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Contracts\Http\ResponseFactory;

class MultiCurlInteractor extends \Frlnc\Slack\Http\CurlInteractor {

  /**
   * {@inheritdoc}
   */
  public function getAll($urlsWithParameters) {
    $requests = $this->prepareRequests($urlsWithParameters);
    return $this->executeMultiRequest($requests['multiRequest'], $requests['singleRequests']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAsync($url, array $parameters = [], array $headers = []) {
    $request = $this->prepareAsyncRequest($url, $parameters, $headers);
    $this->executeAsyncRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function postAsync($url, array $urlParameters = [], array $postParameters = [], array $headers = []) {
    $request = $this->prepareAsyncRequest($url, $urlParameters, $headers);

    curl_setopt($request, CURLOPT_POST, count($postParameters));
    curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($postParameters));

    $this->executeAsyncRequest($request);
  }

  /**
   * Prepares a request using curl.
   *
   * @param  string $url [description]
   * @param  array $parameters [description]
   * @param  array $headers [description]
   * @return resource
   */
  protected function prepareRequests($urlsWithParameters) {
    $multiRequest = curl_multi_init();
    $singleRequest = [];
    foreach ($urlsWithParameters as $urlWithParameters) {
      $singleRequest = curl_init();

      $url = $urlWithParameters['url'];
      if ($query = http_build_query($urlWithParameters['parameters'])) {
        $url .= '?' . $query;
      }
      curl_setopt($singleRequest, CURLOPT_URL, $url);
      curl_setopt($singleRequest, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($singleRequest, CURLOPT_HTTPHEADER, $urlWithParameters['headers']);
      curl_setopt($singleRequest, CURLINFO_HEADER_OUT, true);
      curl_setopt($singleRequest, CURLOPT_SSL_VERIFYPEER, false);

      curl_multi_add_handle($multiRequest, $singleRequest);

      $singleRequests[] = $singleRequest;
    }

    return ['multiRequest' => $multiRequest, 'singleRequests' => $singleRequests];
  }

  /**
   * Executes a curl request.
   *
   * @param  resource $request
   * @return \Frlnc\Slack\Contracts\Http\Response
   */
  public function executeMultiRequest($multiRequest, $singleRequests) {
    $responses = [];
    $infos = [];
    $active = null;
    do {
      $status = curl_multi_exec($multiRequest, $active);
      $infos[] = curl_multi_info_read($multiRequest);
    } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

    foreach ($singleRequests as $index => $singleRequest) {
      $body = curl_multi_getcontent($singleRequest);
      curl_multi_remove_handle($multiRequest, $singleRequest);
      curl_close($singleRequest);

      $info = $infos[$index];

      $statusCode = $info['http_code'];
      $headers = $info['request_header'];

      if (function_exists('http_parse_headers')) {
        $headers = http_parse_headers($headers);
      } else {
        $header_text = substr($headers, 0, strpos($headers, "\r\n\r\n"));
        $headers = [];

        foreach (explode("\r\n", $header_text) as $i => $line) {
          if ($i !== 0) {
            list ($key, $value) = explode(': ', $line);
            $headers[$key] = $value;
          }
        }
      }

      $responses[] = $this->factory->build($body, $headers, $statusCode);
    }

    curl_multi_close($multiRequest);

    return $responses;
  }

  /**
   * Prepares a request using curl.
   *
   * @param  string $url [description]
   * @param  array $parameters [description]
   * @param  array $headers [description]
   * @return resource
   */
  protected static function prepareAsyncRequest($url, $parameters = [], $headers = []) {
    $multiRequest = curl_multi_init();
    $singleRequest = curl_init();

    if ($query = http_build_query($parameters)) {
      $url .= '?' . $query;
    }
    curl_setopt($singleRequest, CURLOPT_URL, $url);
    curl_setopt($singleRequest, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($singleRequest, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($singleRequest, CURLINFO_HEADER_OUT, true);
    curl_setopt($singleRequest, CURLOPT_SSL_VERIFYPEER, false);

    curl_multi_add_handle($multiRequest, $singleRequest);

    return $multiRequest;
  }

  /**
   * Executes a curl request.
   *
   * @param  resource $request
   * @return \Frlnc\Slack\Contracts\Http\Response
   */
  public function executeAsyncRequest($request) {
    $active = null;
    do {
      //ob_start();
      $status = curl_multi_exec($request, $active);
      //ob_end_clean();
    } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

    curl_multi_close($request);
  }
}
