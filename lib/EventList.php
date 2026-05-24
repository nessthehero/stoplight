<?php

namespace Stoplight;

use Phpfastcache\Helper\Psr16Adapter;
use Stoplight\Meeting;

class EventList {

  private $eventList = [];

  private $workingFrom = '';

  private $calendar;

  private $calendarId;

  private $settings;

  private $formatter;

  private $cache;

  private $cacheEnabled = TRUE;

  private $cachedAt = 0;

  const CACHE_EXPIRES = 300;

  const CACHE_TIME_FORMAT = 'Y-m-d';

  public function __construct($calendar, Settings $settings) {
    $this->settings = $settings;
    $this->formatter = new Formatter($settings);
    $this->calendar = $calendar;

    $this->calendarId = $this->settings->get('calendarId');

    self::warmCache();
  }

  private function warmCache() {
    $defaultDriver = 'Files';
    $this->cache = new Psr16Adapter($defaultDriver);
  }

  private function loadEvents($begin, $end) {
    if (!empty($this->calendar)) {
      $this->eventList = [];
      if (!$this->isCached()) {
        $events = $this->calendar->events->listEvents($this->calendarId, [
          'timeMin'      => date('c', $begin),
          'timeMax'      => date('c', $end),
          'singleEvents' => TRUE,
          'orderBy'      => 'startTime',
        ]);
        $this->cacheSave($events);
      }
      else {
        $events = $this->cacheGet();
      }

      foreach ($events as $event) {
        $this->eventList[] = new Meeting($event);
      }
    }
  }

  public function getEvents($upcomingOnly = FALSE) {
    self::loadEvents(
      strtotime('today 12:00:00 AM'),
      strtotime('today 11:59:59 PM')
    );

    $upcoming_events = [];

    // Meeting related
    if (!empty($this->eventList)) {
      foreach ($this->eventList as $event) {
        // If we only want upcoming events (current and future)...
        if ($upcomingOnly && $event->isExpired()) {
          continue;
        }

        $upcoming_events[] = $event;
      }
    }

    return $upcoming_events;
  }

  public function getCurrentEvent() {
    $upcoming_events = self::getEvents(TRUE);
    return array_filter($upcoming_events, function($event) {
      return $event->isCurrent();
    });
  }

  public function getUpcomingEvent() {
    $upcoming_events = self::getEvents(TRUE);
    return array_filter($upcoming_events, function($event) {
      return $event->willStartIn(
        $this->settings->get($this->settings::SETTINGS_KEY_ALERT_THRESHOLD, 600)
      );
    });
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

  public function getWorkingFrom() {
    return $this->workingFrom;
  }

  public function workInProgress() {
    $work_hours_start = (int) $this->settings->get('work_hours_start');
    $work_hours_end = (int) $this->settings->get('work_hours_end');

    $current_time = (int) date('Hi');

    if ($current_time > $work_hours_start && $current_time < $work_hours_end) {
      return 0;
    }
    elseif ($current_time > $work_hours_end) {
      return 1;
    }
    else {
      return -1;
    }
  }

  public function timeUntilWorkBegins() {
    if ($this->workInProgress() < 0) {
    }
  }

  public function isCached() {
    return $this->cacheEnabled && $this->cache->has($this->cacheKey());
  }

  public function cacheDebug($showObj = FALSE
  ) {
    return [
      'key'    => $this->cacheKey(),
      'hasIt?' => $this->isCached(),
      't'      => $showObj ? $this->cacheGet() : 'object suppressed',
    ];
  }

  private function cacheKey() {
    return 'today_' . date(self::CACHE_TIME_FORMAT, strtotime('now'));
  }

  private function cacheSave($data
  ) {
    $this->cache->set($this->cacheKey(), $data, self::CACHE_EXPIRES);
    $this->cachedAt = time();
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
