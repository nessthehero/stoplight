<?php

namespace Stoplight;

use Phpfastcache\Helper\Psr16Adapter;

class EventList
{

    private $eventList = [];

    private $workingFrom = '';

    private $calendar;

    private $calendarId;

    private $settings;

    private $formatter;

    private $cache;

    private $cacheEnabled = TRUE;

    const CACHE_EXPIRES = 300;

    public function __construct($calendar, Settings $settings)
    {

        $this->settings = $settings;
        $this->formatter = new Formatter($settings);
        $this->calendar = $calendar;

        $this->calendarId = $this->settings->get('calendarId');

        self::warmCache();

    }

    private function warmCache()
    {

        $defaultDriver = 'Files';
        $this->cache = new Psr16Adapter($defaultDriver);

    }

    private function populateList()
    {

        if (!empty($this->calendar)) {
            $time_begin = strtotime('today 12:00:00 AM');
            $time_end = strtotime('today 11:59:59 PM');
            $today = date('Y-m-d', strtotime('now'));

            if (!$this->isCached()) {
                $events = $this->calendar->events->listEvents($this->calendarId, [
                    'timeMin' => date('c', $time_begin),
                    'timeMax' => date('c', $time_end),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                ]);
                $this->cacheSave($events);
            } else {
                $events = $this->cacheGet();
            }

            foreach ($events['items'] as $event) {
                if ($event['eventType'] === 'workingLocation' && $event['start']['date'] === $today) {
                    $this->workingFrom = $event['summary'];
                } else {
                    if (isset($event['start']['dateTime'])) {
                        $this->eventList[] = [
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
        }

    }

    public function getEvents($upcomingOnly = FALSE) {

        self::populateList();

        $now = strtotime("now");
        $upcoming_events = [];

        // Meeting related
        if (!empty($this->eventList)) {
            foreach ($this->eventList as $event) {
                $start_diff = $now - $event['start_timestamp'];
                $end_diff = $event['end_timestamp'] - $now;

                $would_be_current = ($start_diff >= 0 && $end_diff >= 0);
                $would_be_expired = ($start_diff > 0 && $end_diff < 0);

                // If we only want upcoming events (current and future)...
                if ($upcomingOnly && $would_be_expired)
                    continue;

                $upcoming_events[] = [
                    'event' => $event,
                    'is_current' => $would_be_current,
                    'is_expired' => $would_be_expired,
                    'start_diff' => $start_diff,
                    'end_diff' => $end_diff,
                    'event_length' => $event['end_timestamp'] - $event['start_timestamp'],
                ];
            }
        }

        return $upcoming_events;

    }

    public function getCurrentEvent() {
        $upcoming_events = self::getEvents(TRUE);
        if (count($upcoming_events) > 0) {
            return array_filter($upcoming_events, function ($k) {
               return $k['is_current'] === TRUE;
            });
        }
        return [];
    }

    public function getUpcomingEvent() {
        $upcoming_events = self::getEvents(TRUE);
        if (count($upcoming_events) > 0) {
            return array_filter($upcoming_events, function ($k) {
                return ($k['start_diff'] <= $this->settings->get($this->settings::SETTINGS_KEY_ALERT_THRESHOLD, 600));
            });
        }
        return [];
    }

    public function getCurrentOrUpcomingEvent() {
        $current = $this->getCurrentEvent();
        $upcoming = $this->getUpcomingEvent();
        return !empty($current) ? $current : $upcoming;
    }

    public function formatNextEvent() {
        $this->formatter->setFormat($this->getCurrentOrUpcomingEvent());
        return $this->formatter;
    }

    public function getWorkingFrom()
    {
        return $this->workingFrom;
    }

    public function workInProgress()
    {
        $work_hours_start = (int) $this->settings->get('work_hours_start');
        $work_hours_end = (int) $this->settings->get('work_hours_end');

        $current_time = (int) date('Hi');

        if ($current_time > $work_hours_start && $current_time < $work_hours_end) {
            return 0;
        } elseif ($current_time > $work_hours_end) {
            return 1;
        } else {
            return -1;
        }
    }

    public function timeUntilWorkBegins()
    {
        if ($this->workInProgress() < 0) {

        }
    }

    public function isCached() {
        return $this->cache->has($this->cacheKey());
    }

    public function cacheDebug($showObj = false) {
        return [
            'key' => $this->cacheKey(),
            'hasIt?' => $this->isCached(),
            't' => $showObj ? $this->cacheGet() : 'object suppressed',
        ];
    }

    private function cacheKey() {
        return 'today_' . date('Y-m-d', strtotime('now'));
    }

    private function cacheSave($data) {
        $this->cache->set($this->cacheKey(), $data, self::CACHE_EXPIRES);
    }

    private function cacheGet() {
        return $this->cache->get($this->cacheKey());
    }

    public function enableCache() {
        $this->cacheEnabled = TRUE;
    }

    public function disableCache() {
        $this->cacheEnabled = FALSE;
    }

}