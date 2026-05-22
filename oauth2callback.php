<?php
require_once('api.php');

use \Stoplight\Client;

// Start a session to persist credentials.
session_start();

$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json';

$client = new Client($credentialsPath);

// Handle authorization flow from the server.
if (!isset($_GET['code'])) {
	header('Location: ' . filter_var($client->createAuthUrl(), FILTER_SANITIZE_URL));
} else {
	$client->fetchAccessTokenWithAuthCode($_GET['code']); // Exchange the authentication code for a refresh token and access token.

  //Redirect back to main script
  $redirect_uri = str_replace("oauth2callback.php", $_SESSION['mainScript'] ?? '', $client->getRedirectUri());

  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
