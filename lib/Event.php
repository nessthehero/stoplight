<?php

namespace Stoplight;

use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;

class Meeting {

  private Event $event;

  private int $start_diff = 0;

  private int $start_timestamp = 0;

  private int $end_diff = 0;

  private int $end_timestamp = 0;

  public function __construct($event) {
    $this->event = $event;

    if ($this->event instanceof Event) {
      $now = strtotime("now");
      $this->start_timestamp = strtotime($this->event->getStart()
        ->getDateTime());
      $this->end_timestamp = strtotime($this->event->getEnd()->getDateTime());

      $this->start_diff = abs($now - $this->start_timestamp);
      $this->end_diff = $this->end_timestamp - $now;
    }
  }

  public function isCurrent() : bool {
    return ($this->start_diff >= 0 && $this->end_diff >= 0);
  }

  public function isExpired() : bool {
    return ($this->start_diff > 0 && $this->end_diff < 0);
  }

  public function willStartIn($threshold) : bool {
    return ($this->start_diff <= $threshold);
  }

  public function timeTillStart() {
    return max($this->start_diff, 0);
  }

  public function length() : int {
    return $this->end_timestamp - $this->start_timestamp;
  }

  public function type() : string {
    return $this->event->getEventType() ?? '';
  }

  public function summary() : string {
    return $this->event->getSummary();
  }

  public function start() : EventDateTime {
    return $this->event->getStart();
  }

  public function end() : EventDateTime {
    return $this->event->getEnd();
  }

  public function isToday() : bool {
    return (date('Y-m-d', $this->start()) === date('Y-m-d', strtotime('now')));
  }

  /**
   * @return EventAttendee[]
   */
  public function getAttendees() : array {
    return $this->event->getAttendees() ?? [];
  }

  public function declined() : bool {
    foreach ($this->getAttendees() as $attendee) {
      if ($attendee->getSelf() && $attendee->getResponseStatus() === 'declined') {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function accepted() : bool {
    return !$this->declined();
  }
}
