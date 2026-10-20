<?php
// google-login.php
require_once 'vendor/autoload.php';

// Load local keys if they exist, otherwise load the template
if (file_exists(__DIR__ . '/google-config.local.php')) {
    require_once 'google-config.local.php';
} else {
    require_once 'google-config.php';
}

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// Redirect the user to Google's login page
$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;
?>