<?php

    require_once('api.php');

    $client = init_client();

    if (!isset($_GET['code'])) {
        $auth_url = $client->createAuthUrl();
        header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    } else {
        $_SESSION['access_token'] = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        if (!empty($_SESSION['BASENAME'])) {
            $redirect_uri .= trim($_SESSION['BASENAME'], '/');
            unset($_SESSION['BASENAME']);
        }
        if (!empty($_SESSION['QUERY_STRING'])) {
            $redirect_uri .= '?' . $_SERVER['QUERY_STRING'];
            unset($_SESSION['QUERY_STRING']);
        }
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
    }