<?php

require_once 'api.php';

use \StopLight\EventList;
use \StopLight\Settings;
use Phpfastcache\Helper\Psr16Adapter;

$defaultDriver = 'Files';
$cache = new Psr16Adapter($defaultDriver);

$lost_connection = false;
$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json';

$client = getGoogleClient($credentialsPath);

$settings = new Settings(__DIR__ . '/settings.json');

$calendar = null;
$calendarId = null;

try {
    $calendar = new Google\Service\Calendar($client);
} catch (Exception $e) {
    $lost_connection = true;
}

$calendarList = get_calendar_list($calendar);

if (!isset($_GET['c'])) {
    if (!empty($settings->get('calendarId'))) {
        $calendarId = $settings->get('calendarId');
    } else {
        $calendarId = $calendarList[0];
    }

    header('Location: ?c=' . $calendarId);
} else {
    $calendarId = $_GET['c'];
}

$stoplight = new EventList($calendar, new Settings(__DIR__ . '/settings.json'));
$events = $stoplight->getEvents();
$currentEvent = $stoplight->getCurrentOrUpcomingEvent();

print_r($_SESSION);

?>
<pre>
    <?php print_r($stoplight->cacheDebug()); ?>
</pre>
<pre>
    <?php print_r(date('Y-m-d-i', strtotime('now'))); ?>
</pre>
<pre><?php print_r($currentEvent); ?></pre>

<pre><?php print_r($events); ?></pre>
<!--<pre>--><?php //print_r($settings); ?><!--</pre>-->
<!--<pre>--><?php //print_r($event_data); ?><!--</pre>-->
<!--<pre>--><?php //print_r($upcoming_events); ?><!--</pre>-->

<script type="text/javascript">

    window.addEventListener('DOMContentLoaded', () => {

        // Refresher
        function secondsUntilNextMinute() {
            var now = new Date();
            var seconds = 60 - now.getSeconds();
            return seconds;
        }
        setTimeout(function () {
            window.location.reload();
        }, (secondsUntilNextMinute() + 1) * 1000);

    }, false);

</script>
