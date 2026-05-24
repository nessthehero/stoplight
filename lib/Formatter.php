<?php

namespace Stoplight;

class Formatter {

  private $classList = [
    'available',
    'text-bg-success',
  ];

  // Uses bootstrap text classes.
  // Docs here: https://getbootstrap.com/docs/5.3/helpers/color-background/
  private $classOptions = [
    'available' => [
      'available',
      'text-bg-success',
    ],
    'busy'      => [
      'busy',
      'text-bg-danger',
    ],
    'dnd'       => [
      'dnd',
      'text-bg-warning',
    ],
    'pending'   => [
      'pending',
      'text-bg-info',
    ],
    'ooo'       => [
      'ooo',
      'text-bg-dark',
    ],
  ];

  private $showNameAsStatusKeys = [
    'focusTime',
  ];

  private $showBusyAsDnDKeys = [
    'focusTime',
  ];

  private $typeTranslation = [
    'default'   => 'Meeting',
    'focusTime' => 'Focus Time',
  ];

  private $status = 'Available';

  private $subtitle = '';

  private $type = '';

  private $event;

  private $settings;

  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  public function setFormat($event) {
    if (!empty($event)) {
      $this->event = current($event);

      if (!empty($this->event)) {
        if (!$this->event->isCurrent()) {
          // Upcoming Event
          if ($this->event->willStartIn($this->settings->get('alert_threshold'))) {
            // Event is starting soon
            $this->setClassList('pending');

            // How many minutes til meeting
            $this->setSubtitle(ceil(abs($this->event['start_diff']) / 60) . ' minutes');

            // Meeting soon
            $this->setStatus('Meeting Soon');
          }
        }
        else {
          // Current Event
          $this->meetingInProgress();
        }
      }
    }
  }

  public function getFormat() {
    return [
      'class'    => self::getClassList(),
      'status'   => self::getStatus(),
      'subtitle' => self::getSubtitle(),
      'type'     => self::getType(),
      'event'    => self::getEvent(),
    ];
  }

  public function workingFromHomeOverride() {}

  /**
   * @return mixed
   */
  public function getEvent() {
    return $this->event;
  }

  /**
   * @param mixed $event
   */
  public function setEvent($event) : void {
    $this->event = $event;
  }

  public function getSubtitle() : string {
    return $this->subtitle;
  }

  public function setSubtitle(string $subtitle) : void {
    $this->subtitle = $subtitle;
  }

  public function getType() : string {
    return !empty($this->typeTranslation[$this->type]) ? $this->typeTranslation[$this->type] : $this->type;
  }

  public function setType(string $type) : void {
    $this->type = $type;
  }

  public function getStatus() : string {
    return $this->status;
  }

  public function setStatus(string $status) : void {
    $this->status = $status;
  }

  public function getClassList() : array {
    return $this->classList;
  }

  public function setClassList(string $classKey) : void {
    $this->classList = $this->classOptions[$classKey];
  }

  public function meetingInProgress() {
    if ($this->event instanceof Meeting && !empty($this->event->type())) {
      $this->setType($this->event->type());
      if (in_array($this->event->type(), $this->showBusyAsDnDKeys)) {
        $this->setClassList('dnd');
      }
      else {
        $this->setClassList('busy');
      }

      if (in_array($this->event->type(), $this->showNameAsStatusKeys)) {
        $this->setStatus($this->event['event']['summary']);
      }
      else {
        $this->setStatus('In A Meeting');
      }
    }
    else {
      $this->setClassList('busy');
      $this->setStatus('Busy');
    }
  }

  public function secondsUntilMeetingBegins() {
    if ($this->event instanceof Meeting) {
      return $this->event->timeTillStart();
    }
    return 0;
  }

  public function minutesUntilMeetingBegins() {
    return ceil($this->secondsUntilMeetingBegins() / 60);
  }

}
