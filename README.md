# Stoplight

## Getting started

`ddev start`

`ddev composer install`

Log into Google Cloud Console and get Credentials Client Secret JSON file.

Rename to `client_secret.json`

Rename `settings.example.json` to `settings.json`

* `calendarId`: The calendar email or ID (e.g., "example@gmail.com").
* `wfh`: Boolean for "working from home" (e.g., false).
* `work_hours_start`: Start of work hours in 24h format, as a string or
  integer (e.g., "0900" or 900).
* `work_hours_end`: End of work hours in 24h format, as a string or integer
  (e.g., "1700" or 1700).
* `alert_threshold`: Number of seconds before an event to trigger an alert (
  e.g., 600).

## Resources

https://github.com/googleapis/google-api-php-client/blob/main/src/Client.php

https://github.com/googleapis/google-api-php-client

https://github.com/LindaLawton/Google-APIs-PHP-Samples/blob/master/Samples/Calendar%20API/v3/oauth2callback.php

https://console.cloud.google.com/apis/credentials?project=scotty-spotlight
