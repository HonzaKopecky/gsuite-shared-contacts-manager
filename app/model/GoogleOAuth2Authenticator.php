<?php

namespace App\Model;

use Nette\InvalidArgumentException;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\SmartObject;

/** Authenticator that validates whether user is GSuite administrator so he can use this app.
 */
class GoogleOAuth2Authenticator implements IAuthenticator {
	use SmartObject;

    const NO_DOMAIN_FOUND = 404;
    /**
     * @var  OAuthService
     * @inject
     */
    private $oauth;

    function __construct(OAuthService $srvc) {
        if($srvc == null)
            throw new InvalidArgumentException("Google OAuth2 not found.");
        $this->oauth = $srvc;
    }

    /** Method will validate that user is properly authenticated with google and he is a GSuite Administrator
     * @param array $credentials Array that carry Google Oauth code
     * @return UserIdentity User Identity is returned if user is properly authenticated Admin
     * @throws AuthenticationException Exception is thrown when user is not properly authenticated or he is not an Admin
     */
    function authenticate(array $credentials)
    {
        if(!isset($credentials[0]) || $credentials[0] == null)
            throw new AuthenticationException("Google code not specified.");
        $this->oauth->handleGoogleResponse($credentials[0]);
        $user = $this->getUserFromGoogle();
        $this->validateAdmin($user);

        return new UserIdentity($user);
    }

    /** Ask Google for more details about authenticated user
     * @return User
     * @throws AuthenticationException Exception is thrown whenever Google does not provide information about user
     */
    public function getUserFromGoogle() {
        try {
            $oa = new \Google_Service_Oauth2($this->oauth->getClient());
            $ui = $oa->userinfo_v2_me->get();
        } catch (\Google_Exception $ex) {
            throw new AuthenticationException($ex->getMessage(), $ex->getCode());
        }
        $user = new User();
        $user->setAccessToken($this->oauth->getClient()->getAccessToken()['access_token']);
        $user->setID($ui['id']);
        $user->setFirstName($ui['givenName']);
        $user->setLastName($ui['familyName']);
        $user->setEmail($ui['email']);
        $user->setGender($ui['gender']);
        $user->setProfilePhoto($ui['picture']);

        return $user;
    }

    /** Check whether authenticated user is an GSuite Administrator
     * @param User $user
     * @return bool True when user is GSuite Admin
     * @throws AuthenticationException Thrown when user is not member of domain or he is not an Admin.
     */
    private function validateAdmin(User $user) {
        try {
            $sd = new \Google_Service_Directory($this->oauth->getClient());
            $s = $sd->users->get($user->getID());
        } catch (\Google_Exception $ex) {
            if($ex->getCode() == self::NO_DOMAIN_FOUND)
                throw new AuthenticationException("User is not under domain.", OAuthService::ERROR_NO_DOMAIN);
            else
                throw new AuthenticationException($ex->getMessage(), $ex->getCode());
        }

        if(!$s->isAdmin)
            throw new AuthenticationException("User is not admin.", OAuthService::ERROR_NOT_ADMIN);

        return true;
    }

}