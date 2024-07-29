<?php

namespace Stoplight;

class Formatter
{

    private $classList = [
        'available',
        'text-bg-success'
    ];

    // Uses bootstrap text classes.
    // Docs here: https://getbootstrap.com/docs/5.3/helpers/color-background/
    private $classOptions = [
        'available' => [
            'available',
            'text-bg-success'
        ],
        'busy' => [
            'busy',
            'text-bg-danger'
        ],
        'dnd' => [
            'dnd',
            'text-bg-warning'
        ],
        'pending' => [
            'pending',
            'text-bg-info'
        ]
    ];

    private $showNameAsStatusKeys = [
        'focusTime'
    ];

    private $showBusyAsDnDKeys = [
        'focusTime'
    ];

    private $status = 'Available';

    private $subtitle = '';

    private $event;

    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function setFormat($event) {
        if (!empty($event)) {
            $this->event = current($event);
            $sum = $this->event['event']['summary'];

            if (!empty($this->event)) {
                if (!$this->event['is_current']) {
                    // Upcoming Event
                    if (
                        abs($this->event['start_diff']) <= abs($this->settings->get('alert_threshold'))
                        && $this->event['start_diff'] < 0
                    ) {
                        // Event is starting soon
                        $this->setClassList('pending');

                        // How many minutes til meeting
                        $this->setSubtitle(ceil(abs($this->event['start_diff']) / 60) . ' minutes');

                        // Meeting soon
                        $this->setStatus('Meeting Soon');
                    } elseif (
                        $this->event['start_diff'] > 0
                        && $this->event['start_diff'] <= $this->event['event_length']
                    ) {
                        // I'm busy
                        $this->meetingInProgress();
                    }
                } else {
                    // Current Event
                    $this->meetingInProgress();
                }
            }
        }
    }

    public function getFormat() {
        return [
            'class' => self::getClassList(),
            'status' => self::getStatus(),
            'subtitle' => self::getSubtitle(),
            'event' => self::getEvent()
        ];
    }

    public function workingFromHomeOverride() {



    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event): void
    {
        $this->event = $event;
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getClassList(): array
    {
        return $this->classList;
    }

    public function setClassList(string $classKey): void
    {
        $this->classList = $this->classOptions[$classKey];
    }

    public function meetingInProgress() {
        if (!empty($this->event)) {
            if (in_array($this->event['event']['type'], $this->showBusyAsDnDKeys)) {
                $this->setClassList('dnd');
            } else {
                $this->setClassList('busy');
            }

            if (in_array($this->event['event']['type'], $this->showNameAsStatusKeys)) {
                $this->setStatus($this->event['event']['summary']);
            } else {
                $this->setStatus('In A Meeting');
            }
        }
    }

    public function secondsUntilMeetingBegins() {
        if (!empty($this->event)) {
            return ($this->event['start_diff'] < 0) ? abs($this->event['start_diff']) : 0;
        } else {
            return 0;
        }
    }

    public function minutesUntilMeetingBegins() {
        return ceil($this->secondsUntilMeetingBegins() / 60);
    }

}