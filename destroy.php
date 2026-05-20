<?php

require_once('api.php');

session_destroy();

$credentialsPath = __DIR__ . DIRECTORY_SEPARATOR . 'credentials' . DIRECTORY_SEPARATOR . 'credentials.json';
unlink($credentialsPath);

header('Location: /');
