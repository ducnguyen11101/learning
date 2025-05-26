<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class GoogleAuth {
    private $client;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setClientId(GOOGLE_CLIENT_ID);
        $this->client->setClientSecret(GOOGLE_CLIENT_SECRET);
        $this->client->setRedirectUri(GOOGLE_REDIRECT_URI);
        $this->client->addScope('email profile');
    }

    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }

    public function getUserInfo($code) {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new Exception('Google Auth failed: ' . $token['error']);
        }
        $this->client->setAccessToken($token);
        $oauth = new Google_Service_Oauth2($this->client);
        return $oauth->userinfo->get();
    }
}
?>