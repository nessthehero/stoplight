<?php

require_once 'api.php';

use \StopLight\EventList;
use \StopLight\Settings;

$lost_connection = false;

$settings = init_settings();
$client = init_client();

$calendar = null;
$calendarId = null;

try {
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client->setAccessToken($_SESSION['access_token']);
        if ($client->isAccessTokenExpired()) {
            renew_token(basename(__FILE__));
        }
    } else {
        renew_token(basename(__FILE__));
    }
    $calendar = new Google\Service\Calendar($client);
} catch (Exception $e) {
    $lost_connection = true;
}

$stoplight = new EventList($calendar, new Settings(__DIR__ . '/settings.json'));
$events = $stoplight->getEvents();
$currentEvent = $stoplight->getCurrentOrUpcomingEvent();

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