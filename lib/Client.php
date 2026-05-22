<?php

namespace Stoplight;

use Google_Client;
use Google\Service\Calendar;

class Client {

  const string CLIENT_CALLBACK_FILE = 'oauth2callback.php';

  private array $credentials;

  private string $credentialFile = '';

  private array $settings = [];

  private Google_Client $client;

  private $valid = FALSE;

  public function __construct($credentialFile) {
    $this->credentialFile = $credentialFile;

    $this->settings = [
      'access_type'            => 'offline',
      'approval_prompt'        => 'force',
      'include_granted_scopes' => TRUE,
      'scopes'                 => [
        Calendar::CALENDAR_READONLY,
        Calendar::CALENDAR_EVENTS_READONLY,
      ],
      'credentials'            => __DIR__ . '/../client_secret.json',
      'redirect_uri'           => self::getRedirectUri(),
    ];

    self::buildClient();
  }

  public function getClient() : Google_Client|bool {
    if ($this->valid()) {
      if ($this->isAccessTokenExpired()) {
        $this->client->fetchAccessTokenWithRefreshToken(
          $this->client->getRefreshToken()
        );
        self::writeCredentials();
      }

      return $this->client;
    }
    else {
      return FALSE;
    }
  }

  private function buildClient() : Google_Client {
    try {
      $this->client = new Google_Client($this->settings);

      // If the user has already authorized this app then get an access token
      // else redirect to ask the user to authorize access to Google Analytics.
      if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        // Set the access token on the client.
        $this->setAccessToken($_SESSION['access_token']);
      }
      else {
        $this->credentials = json_decode(
          @file_get_contents($this->credentialFile),
          TRUE
        );

        if (!empty($this->credentials['access_token'])) {
          $this->setAccessToken($this->credentials);
        }
        else {
          // We do not have access request access.
          header('Location: ' . filter_var($this->client->getRedirectUri(), FILTER_SANITIZE_URL));
        }
      }
    }
    catch (Exception $e) {
      error_log('[StopLight] ' . $e->getMessage());
    }

    return $this->client;
  }

  public function getRedirectUri() : string {
    //Building Redirect URI
    //returns the current URL
    $url = $_SERVER['REQUEST_URI'];
    if (strrpos($url, '?') > 0) {
      $url = substr($url, 0, strrpos($url, '?'));
    }  // Removing any parameters.
    $folder = substr($url, 0, strrpos($url, '/'));   // Removing current file.
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'] . $folder . self::CLIENT_CALLBACK_FILE;
  }

  private function setAccessToken($token) : void {
    $this->client->setAccessToken($token);
    $this->writeCredentials();
  }

  public function isAccessTokenExpired() : bool {
    return $this->client->isAccessTokenExpired() ?? TRUE;
  }

  private function writeCredentials() {
    unset($_SESSION['access_token']);

    if (file_exists($this->credentialFile)) {
      unlink($this->credentialFile);
    }

    file_put_contents(
      $this->credentialFile,
      json_encode($this->client->getAccessToken())
    );

    // Add access token and refresh token to session.
    $_SESSION['access_token'] = $this->client->getAccessToken();
    $this->credentials = $this->client->getAccessToken();

    if (!file_exists($this->credentialFile)) {
      error_log('[StopLight] Unable to create credentials file.');
    }
    else {
      $this->valid = TRUE;
    }
  }

  public function valid() {
    return $this->valid;
  }
}
