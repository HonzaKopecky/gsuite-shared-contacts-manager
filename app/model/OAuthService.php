<?php

namespace App\Model;

use Nette\FileNotFoundException;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\InvalidArgumentException;
use Nette\SmartObject;
use Tracy\Debugger;

/** Class handles process of creation a Google Client, authenticates user and handles access token operations.
 */
class OAuthService {
	use SmartObject;

    const CLIENT_SECRET_PATH = __DIR__."/../config/google-client-secret.json";
    const SESSION_SECTION = "oauth2-google";
    const ERROR_NO_DOMAIN = 1;
    const ERROR_NOT_ADMIN = 2;

    /** @var \Google_Client  */
    private $client = null;

    /** @var SessionSection */
    private $sessionSection;

    /** Array of permissions that user has to grunt to the app
     * @var array
     */
    private $scopes = [
        "http://www.google.com/m8/feeds/contacts/",
        "https://www.googleapis.com/auth/userinfo.profile",
        "https://www.googleapis.com/auth/userinfo.email",
        "https://www.googleapis.com/auth/admin.directory.user.readonly"
    ];

    /**
     * OAuthService constructor.
     * @param Session $session
     */
    public function __construct(Session $session) {
      if($session == null)
          throw new InvalidArgumentException("Session service empty.");
      $this->sessionSection = $session->getSection(OAuthService::SESSION_SECTION);
      $this->setupClient();
      if($this->sessionSection->accessToken != null)
        $this->client->setAccessToken($this->sessionSection->accessToken);
    }

    /** Method prepares Google Client and sets the callback URL
     */
    private function setupClient() {
      if(!file_exists(OAuthService::CLIENT_SECRET_PATH))
          throw new FileNotFoundException("Client secret file at " . OAuthService::CLIENT_SECRET_PATH . " not found!");
      $this->client = new \Google_Client();
      $this->client->setAuthConfig(OAuthService::CLIENT_SECRET_PATH);
      $this->client->addScope($this->scopes);
      $callbackURL = "http://" . $_SERVER['HTTP_HOST'] . "/contact-manager/login/oauth2callback";
      $this->client->setRedirectUri($callbackURL);
    }

    /** Get Google Authentication link that user will be redirected to once he click Login button
     * @return string
     */
    public function getGoogleLink() {
      if($this->client == null)
          throw new \LogicException("Client is not initialized yet.");
      return $this->client->createAuthUrl();
    }

    /** Receive Google Access Token based on authentication code
     * @param string $authCode
     */
    public function handleGoogleResponse($authCode) {
      if($this->client == null)
          throw new \LogicException("Client is not initialized yet.");
      $this->client->fetchAccessTokenWithAuthCode($authCode);
      $this->sessionSection->accessToken = $this->client->getAccessToken();
    }

    /** Get currently stored access token
     * @return string
     */
    public function getAccessToken() {
      if($this->client == null)
          throw new \LogicException("Client is not initialized yet.");
      if($this->client->getAccessToken() == "")
          throw new \LogicException("Access token is not set.");
      return $this->client->getAccessToken()['access_token'];
    }

    /** Determine whether app still has a valid access token
     * @return bool
     */
    public function isAuthenticated() {
      if($this->client == null)
          throw new \LogicException("Client is not initialized yet.");
      try {
          return !$this->client->isAccessTokenExpired();
      } catch (\LogicException $ex) {
          return false;
      }
    }

    /** Get Google Client Object
     * @return \Google_Client
     */
    public function getClient() {
      if($this->client == null)
          throw new \LogicException("Client is not initialized yet.");
      return $this->client;
    }

}