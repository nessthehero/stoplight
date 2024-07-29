<?php

function init_client() {
    $client = new Google\Client();
    $client->setAuthConfig('client_secret.json');
    $client->addScope(Google\Service\Calendar::CALENDAR_READONLY);
    $client->addScope(Google\Service\Calendar::CALENDAR_EVENTS_READONLY);
    $client->setAccessType("offline");

    return $client;
}

function init_settings() {
    $settings_file = file_get_contents(__DIR__ . '/settings.json');
    return json_decode($settings_file, TRUE);
}

function renew_token($basename) {
    $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/google-login.php';

    $_SESSION['BASENAME'] = trim($basename, '/');
    $_SESSION['QUERY_STRING'] = $_SERVER['QUERY_STRING'];

    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    exit();
}

function get_calendar_list($calendar, $include_google = FALSE) {
    $calendars = array();
    if (!empty($calendar) && !empty($calendar->calendarList)) {
        $calendarList = $calendar->calendarList->listCalendarList();

        foreach ($calendarList->items as $item) {

            if (!$include_google) {
                if (!str_contains($item->id, 'calendar.google.com')) {
                    array_push($calendars, $item->id);
                }
            } else {
                array_push($calendars, $item->id);
            }
        }
    }

    return $calendars;
}

function print_calendar_options($calendarList, $calendarId) {
    foreach ($calendarList as $item) {
        echo '<option value="' . $item . '"' . (($item === $calendarId) ? ' selected' : '') . '>' . $item . '</option>';
    }
}

function get_event_data($calendar, $calendarId, $cache)
{


    $json_events = [];

    $time_begin = strtotime('today 12:00:00 AM');
    $time_end = strtotime('today 11:59:59 PM');
    $today = date('Y-m-d', strtotime('today 12:00:00 AM'));
    $cache_key = 'today_' . $today;

//    if ($cache->has($cache_key) && !empty($cache->get($cache_key))) {
//        $events = $cache->get($cache_key);
//    } else {
        $events = $calendar->events->listEvents($calendarId, [
            'timeMin' => date('c', $time_begin),
            'timeMax' => date('c', $time_end),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);
//        $cache->set($cache_key, $events, 120);
//    }

    foreach ($events as $event) {

        if ($event['eventType'] === 'workingLocation' && $event['start']['date'] === $today) {
            $json_events['working_from'] = $event['summary'];
        } else {
            if (isset($event['start']['dateTime'])) {
                $json_events['events'][] = [
                    'start' => $event['start'],
                    'start_timestamp' => strtotime($event['start']['dateTime']),
                    'end' => $event['end'],
                    'end_timestamp' => strtotime($event['end']['dateTime']),
                    'summary' => $event['summary'],
                    'type' => $event['eventType'],
                ];
            }
        }


    }

    return $json_events;

}

function get_upcoming_events($event_data) {

    $now = strtotime("now");
    $upcoming_events = [];

    // Meeting related
    if (isset($event_data['events'])) {
        foreach ($event_data['events'] as $event) {
            $upcoming_events[] = [
                'event' => $event,
                'start_diff' => $now - $event['start_timestamp'],
                'end_diff' => $event['end_timestamp'] - $now,
                'event_length' => $event['end_timestamp'] - $event['start_timestamp'],
            ];
        }
    }

    return $upcoming_events;

}

function get_current_event($event_data) {

    $now = strtotime("now");
    $upcoming_events = get_upcoming_events($event_data);
    $current_event = [
        'class' => [
            'available',
            'text-bg-success',
        ],
        'status' => 'Available',
        'subtitle' => '',
        'event' => []
    ];

    if (count($upcoming_events) > 0) {
        // Meeting related
        foreach ($upcoming_events as $event) {
            $current_event['event'] = $event['event'];

            if ($event['end_diff'] > 0) { // Upcoming events
                if ($event['event']['type'] === 'default') { // Normal Events
                    if ($event['start_diff'] > 0 && $event['start_diff'] <= $event['event_length']) { // Event is ongoing
                        $current_event['status'] = 'In A Meeting';
                        $current_event['class'] = ['busy', 'text-bg-danger'];
                        break;
                    } elseif ($event['start_diff'] >= -1200 && $event['start_diff'] < 0) { // Event is 10 minutes away
                        $current_event['status'] = 'Meeting Soon';
                        $current_event['subtitle'] = ceil(abs($event['start_diff']) / 60) . ' minutes';
                        $current_event['class'] = ['pending', 'text-bg-info'];
                        break;
                    }
                } elseif ($event['event']['type'] === 'focusTime') {
                    if ($event['start_diff'] > 0 && $event['start_diff'] <= $event['event_length']) { // Event is ongoing
                        $current_event['status'] = $event['event']['summary'];
                        $current_event['class'] = ['dnd', 'text-bg-warning'];
                        break;
                    } elseif ($event['start_diff'] >= -1200 && $event['start_diff'] < 0) { // Event is 10 minutes away
                        $current_event['status'] = 'Leaving Soon';
                        $current_event['subtitle'] = ceil(abs($event['start_diff']) / 60) . ' minutes';
                        $current_event['class'] = ['pending', 'text-bg-info'];
                        break;
                    }
                }
            }
        }
    }

    return $current_event;

}

function gen_slug($str)
{
    # special accents
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'Ð', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', '?', '?', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', '?', '?', 'L', 'l', 'N', 'n', 'N', 'n', 'N', 'n', '?', 'O', 'o', 'O', 'o', 'O', 'o', 'Œ', 'œ', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'Š', 'š', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Ÿ', 'Z', 'z', 'Z', 'z', 'Ž', 'ž', '?', 'ƒ', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', '?', '?', '?', '?', '?', '?');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');

    return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '-', ''), str_replace($a, $b, $str)));
}

function minutesLeftInHour() {
    // Get the current time as timestamp
    $currentTime = time();

    // Get the current minute
    $currentMinute = date('i', $currentTime);

    // Calculate the minutes left in the hour
    $minutesLeft = 60 - $currentMinute;

    return $minutesLeft;
}

function secondsLeftInMinute() {
    // Get the current time as timestamp
    $currentTime = time();

    // Get the current second
    $currentSecond = date('s', $currentTime);

    // Calculate the seconds left in the minute
    $secondsLeft = 60 - $currentSecond;

    return $secondsLeft;
}