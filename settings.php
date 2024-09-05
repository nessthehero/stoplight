<?php

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
    <title>Settings</title>

    <!-- Font Awesome -->
    <link
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
            rel="stylesheet"
    />
    <!-- Google Fonts -->
    <link
            href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap"
            rel="stylesheet"
    />
    <!-- MDB -->
    <link
            href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.2.0/mdb.min.css"
            rel="stylesheet"
    />

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/vue-axios@2.1.4/dist/vue-axios.min.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
</head>
<body>

<div id="app" class="app">

    <div class="row mb-4 p-3">
        <div class="col">

            <form id="settingsform" class="settingsform">
                <div class="row mb-4">
                    <div class="col">

                        <fieldset>
                            <legend>Override Status</legend>

                            <div class="btn-group w-100">
                                <input class="btn-check" type="radio" id="override-none" name="override"
                                       v-model="settings.override_status" value=""/>
                                <label class="btn btn-secondary" for="override-none">None</label>
                                <input class="btn-check" type="radio" id="override-away" name="override"
                                       v-model="settings.override_status" value="away"/>
                                <label class="btn btn-secondary" for="override-away">Away</label>
                                <input class="btn-check" type="radio" id="override-busy" name="override"
                                       v-model="settings.override_status" value="busy"/>
                                <label class="btn btn-secondary" for="override-busy">Busy</label>
                                <input class="btn-check" type="radio" id="override-free" name="override"
                                       v-model="settings.override_status" value="free"/>
                                <label class="btn btn-secondary" for="override-free">Free</label>
                            </div

                        </fieldset>

                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col">
                        <input type="text" id="work-hours-start" class="form-control" v-model="settings.work_hours_start">
                        <label for="work-hours-start" class="form-label">Work Hours Start</label>
                    </div>
                    <div class="col">
                        <input type="text" id="work-hours-end" class="form-control" v-model="settings.work_hours_end">
                        <label for="work-hours-end" class="form-label">Work Hours End</label>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col">

                        <fieldset class="form-group">

                            <select class="form-control" id="calendar" name="c" v-model="settings.calendarId">
                                <?php print_calendar_options($calendarList, $calendarId); ?>
                            </select>
                            <label class="form-label" for="calendar">Calendar</label>

                        </fieldset>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>

    const {createApp} = Vue;

    const app = createApp({
        data() {
            return {
                settings: []
            }
        },
        watch: {
            override: function (newValue, oldValue) {
                console.log('Input changed! New value:',
                    newValue);
            }
        },
        mounted() {
            axios
                .get('./settings.json')
                .then(response => {
                    console.log(response);
                    this.settings = response.data
                });
        },
        computed: {
            override() {
                return this.settings.override_status;
            }
        }
    });

    app.mount('#app');

</script>

<!-- MDB -->
<script
        type="text/javascript"
        src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.2.0/mdb.umd.min.js"
></script>

</body>
</html>
