<?php

require_once('api.php');

use \StopLight\EventList;
use \StopLight\Settings;
use \Stoplight\Client;
use Phpfastcache\Helper\Psr16Adapter;

$defaultDriver = 'Files';
$cache = new Psr16Adapter($defaultDriver);

$lost_connection = false;
$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json';

$client = new Client($credentialsPath);
if (!$client->valid()) {
  die('No client');
}

$settings = new Settings(__DIR__ . '/settings.json');

$calendar = null;
$calendarId = null;

try {
  if ($client->getClient() !== FALSE) {
    $calendar = new Google\Service\Calendar($client->getClient());
  } else {
    throw new Exception('No Google client');
  }
} catch (Exception $e) {
  $lost_connection = true;
  error_log('[StopLight] ' . $e->getMessage());
}

$calendarList = get_calendar_list($calendar);
if (!empty($settings->get('calendarId'))) {
    $calendarId = $settings->get('calendarId');
} else {
    $calendarId = $calendarList[0];
}

if (isset($_GET['c'])) {
    $calendarId = $_GET['c'];
}

if (!empty($calendar)) {
    $eventList = new EventList(
        $calendar,
        $settings
    );

    // Default Event Formatting
    $current_event = $eventList->formatNextEvent();

    // Working From Home override
    if ($eventList->getWorkingFrom() === 'Home' && $settings->get('wfh')) {
        $current_event->setClassList('dnd');
        $current_event->setStatus('Working Remotely');
        $current_event->setSubtitle('');
        $current_event->setType('');
    }

    // Pre/Post Work Day override
    $workInProgress = $eventList->workInProgress();
    if ($workInProgress !== 0) {
        if ($workInProgress > 0) {
            // After work day
        } else {
            // Before work day
        }
    }

    $current_event_format = $current_event->getFormat();
} else {
    if ($lost_connection) {
        $current_event_format = [
            'class' => [
                'lostconnection',
                'text-bg-secondary',
            ],
            'status' => 'Lost Connection',
            'subtitle' => 'Please Reload',
            'type' => '',
            'event' => []
        ];
    }
}

if (!empty($current_event_format)) {
    $response = [
        "success" => !$lost_connection,
        "calendarId" => $calendarId,
        "format" => $current_event_format,
        "remaining" => $current_event->minutesUntilMeetingBegins()
    ];
} else {
    $response = [
        "success" => false,
        "calendarId" => $calendarId,
        "format" => [
            'class' => [
                'lostconnection',
                'text-bg-secondary',
            ],
            'status' => 'Error',
            'subtitle' => 'Please Reload',
            'type' => '',
            'event' => []
        ],
        "remaining" => 0
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
