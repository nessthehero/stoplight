<?php

// https://github.com/LindaLawton/Google-APIs-PHP-Samples/tree/master/Samples/Calendar%20API

require_once('api.php');

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
    <link rel="stylesheet" href="./css/main.css"/>
</head>
<body>
<main id="frame">
    <h1 class="w-100 fs-1" id="status">Loading</h1>
    <h2 class="w-100 fs-6 m-0" id="subtitle"></h2>
    <footer class="fw-lighter fs-6 opacity-25">
        <div class="p-1 d-inline position-fixed bottom-0 start-0" id="calendar-id"><?php print $calendarId; ?></div>
        <div class="p-1 d-inline position-fixed bottom-0 end-0" id="event-type"></div>
    </footer>
</main>
<nav class="navbar fixed-top bg-transparent navbar--closed" data-bs-theme="dark" id="navbar">
    <div class="container-fluid">
        <div class="d-flex">
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
        <div class="d-flex">
            <a href="#" id="fs" class="btn btn-light btn-sm">
                <span class="visually-hidden">Fullscreen</span>
                <i class="bi bi-arrows-fullscreen"></i>
            </a>
        </div>
    </div>
</nav>
<span id="bar" class="bar"></span>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script type="text/javascript">
    import('./js/main.js').then((module) => {
        module.main.init('/endpoint.php?c=<?php print $calendarId; ?>');
    });
</script>
</body>
</html>
