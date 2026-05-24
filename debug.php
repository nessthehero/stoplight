<?php

require_once 'api.php';

use \StopLight\EventList;
use \StopLight\Settings;
use \Stoplight\Client;
use Phpfastcache\Helper\Psr16Adapter;

$defaultDriver = 'Files';
$cache = new Psr16Adapter($defaultDriver);

$lost_connection = FALSE;
$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json';

$client = new Client($credentialsPath);
if (!$client->valid()) {
  die('No client');
}

$settings = new Settings(__DIR__ . '/settings.json');

$calendar = NULL;
$calendarId = NULL;

try {
  if ($client->getClient() !== FALSE) {
    $calendar = new Google\Service\Calendar($client->getClient());
  }
  else {
    throw new Exception('No Google client');
  }
}
catch (Exception $e) {
  $lost_connection = TRUE;
  error_log('[StopLight] ' . $e->getMessage());
}

$calendarList = get_calendar_list($calendar);

if (!isset($_GET['c'])) {
  if (!empty($settings->get('calendarId'))) {
    $calendarId = $settings->get('calendarId');
  }
  else {
    $calendarId = $calendarList[0];
  }

  header('Location: ?c=' . $calendarId);
}
else {
  $calendarId = $_GET['c'];
}

$stoplight = new EventList($calendar, new Settings(__DIR__ . '/settings.json'));
$events = $stoplight->getEvents();
$currentEvent = $stoplight->getCurrentOrUpcomingEvent();

$debug = [
  ['Session', $_SESSION],
  ['Cache Debug', $stoplight->cacheDebug()],
  ['Current Date', date('Y-m-d-i', strtotime('now'))],
  ['Current Event', $currentEvent],
  ['All Events', $events],
  ['Settings', $settings],
];

show_debug_info($debug);
