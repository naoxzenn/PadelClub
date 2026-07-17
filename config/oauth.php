<?php
// config/oauth.php

require_once __DIR__ . '/../config/koneksi.php';

// Setup Google API Client
use Google\Client as GoogleClient;

/**
 * Returns a configured Google Client instance.
 * @return GoogleClient
 */
function getGoogleClient()
{
    $client = new GoogleClient();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID'] ?? '');
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

    // Request profile and email access
    $client->addScope("email");
    $client->addScope("profile");

    return $client;
}