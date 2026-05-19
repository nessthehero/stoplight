<?php

    $mock = [
        "test" => "test2",
        "override" => "away"
    ];

    file_put_contents("settings.json", json_encode($mock, JSON_PRETTY_PRINT));

    echo 'done';