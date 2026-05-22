<?php

function get_calendar_list($calendar, $include_google = FALSE) {
    $calendars = array();
    if (!empty($calendar) && !empty($calendar->calendarList)) {
        try {
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
        } catch (Exception $e) {

        }
    }

    return $calendars;
}

function print_calendar_options($calendarList, $calendarId) {
    foreach ($calendarList as $item) {
        echo '<option value="' . $item . '"' . (($item === $calendarId) ? ' selected' : '') . '>' . $item . '</option>';
    }
}

function show_debug_info($array) {
  foreach ($array as $item) {
    [$heading, $value] = $item;
    if (!empty($heading) && !empty($value)) {
      print "<h2>$heading</h2>";
      print "<pre>";
      print_r($value);
      print "</pre>";
    }
  }
}
