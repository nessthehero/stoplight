<?php

require_once('api.php');

use \StopLight\EventList;
use \StopLight\Settings;
use \StopLight\Formatter;
use Phpfastcache\Helper\Psr16Adapter;

$defaultDriver = 'Files';
$cache = new Psr16Adapter($defaultDriver);

$lost_connection = false;

//$settings = init_settings();
$client = init_client();

$settings = new Settings(__DIR__ . '/settings.json');

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

$calendarList = get_calendar_list($calendar);
if (!empty($settings->get('calendarId'))) {
    $calendarId = $settings->get('calendarId');
} else {
    $calendarId = $calendarList[0];
}

if (!isset($_GET['c'])) {
    header('Location: ?c=' . $calendarId);
} else {
    $calendarId = $_GET['c'];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>Stoplight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body,
        main {
            padding: 0;
            margin: 0;
        }

        main {
            position: fixed;
            width: 100%;
            height: 100%;
            font-weight: 900;
            display: flex;
            flex-direction: column;
            text-align: center;
            justify-content: center;
            align-items: center;
        }

        .navbar--open {
            #open {
                display: none;
            }
        }

        .navbar--closed {
            form,
            #close {
                display: none;
            }
        }
    </style>
</head>
<body>
<?php

if (!empty($calendar)) {
    $eventList = new EventList(
        $calendar,
        $settings
    );

    // Default Event Formatting
    $current_event = $eventList->formatNextEvent();

    // Working From Home override
    if ($eventList->getWorkingFrom() === 'Home') {
        $current_event->setClassList('dnd');
        $current_event->setStatus('Working Remotely');
        $current_event->setSubtitle('');
    }

    // Pre/Post Work Day override
    $workInProgress = $eventList->workInProgress();
    if ($workInProgress !== 0) {
        if ($workInProgress > 0) {
            // After work day
        } else {
            // Before work day
        }
    }

    $current_event_format = $current_event->getFormat();
} else {
    if ($lost_connection) {
        $current_event = [
            'class' => [
                'lostconnection',
                'text-bg-secondary',
            ],
            'status' => 'Lost Connection',
            'subtitle' => 'Please Reload the Page',
            'event' => []
        ];
    }
}

?>

<main class="<?php print implode(' ', $current_event_format['class']); ?>">
    <h1 class="w-100 fs-1"><?php print $current_event_format['status']; ?></h1>
    <?php if (!empty($current_event_format['subtitle'])): ?>
        <h2 class="w-100 fs-6"><?php print $current_event_format['subtitle']; ?></h2>
    <?php endif; ?>
    <footer class="fw-lighter fs-6 opacity-25 p-1 d-inline position-fixed bottom-0 start-0"><?php print $calendarId; ?></footer>
</main>
<nav class="navbar fixed-top bg-transparent navbar--closed" data-bs-theme="dark" id="navbar">
    <div class="container-fluid">
        <form class="" id="form">
            <label class="text-light" for="calendar">Calendar:</label>
            <select id="calendar" name="c">
                <?php print_calendar_options($calendarList, $calendarId); ?>
            </select>
        </form>
        <button type="button" class="btn-close" aria-label="Close Settings" id="close"></button>
        <a class="btn btn-light btn-sm" id="open">
            <span class="visually-hidden">Settings</span>
            <i class="bi-gear"></i>
        </a>
    </div>
</nav>

<?php if ($current_event->minutesUntilMeetingBegins() === 1): ?>
<style>
    .bar {
        height: 4px;
        position: fixed;
        bottom: 0;
        left: 0;
        background-color: black;
        animation-duration: var(--animation-length, 60s);
        animation-name: progress;
        animation-timing-function: linear;
        animation-fill-mode: forwards;
        opacity: 0.2;
    }

    @keyframes progress {
        from { width: 0; }
        to { width: 100%; }
    }
</style>
<span class="bar" style="--animation-length: <?php print $current_event->secondsUntilMeetingBegins(); ?>s;"></span>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script type="text/javascript">

    window.addEventListener('DOMContentLoaded', () => {

        const navBar = document.querySelector('#navbar');

        document.querySelector('#open').addEventListener('click', () => {
            navBar.classList.replace('navbar--closed', 'navbar--open');
        });

        document.querySelector('#close').addEventListener('click', () => {
            navBar.classList.replace('navbar--open', 'navbar--closed');
        });

        document.querySelector('#calendar').addEventListener('change', (event) => {
            const select = event.target;
            if (typeof select.value !== 'undefined' && select.value !== null) {
                window.location.href = '?c=' + select.value;
            }
        });

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
</body>
</html>
