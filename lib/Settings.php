<?php

namespace Stoplight;

class Settings
{
    private $settings = [];

    const SETTINGS_KEY_ALERT_THRESHOLD = 'alert_threshold';

    public function __construct($file = __DIR__ . '/../settings.json')
    {
        self::load($file);
    }

    public function load($file = __DIR__ . '/../settings.json')
    {
        $settings_file = file_get_contents($file);
        $this->settings = json_decode($settings_file, TRUE);
    }

    public function get($key, $fallback = '') {
        return (array_key_exists($key, $this->settings)) ? $this->settings[$key] : $fallback;
    }

}