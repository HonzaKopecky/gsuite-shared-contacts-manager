<?php
namespace App\Model;

use Nette\Security\Identity;
use Nette\Security\IIdentity;
use Nette\SmartObject;

/** Class implements IIdentity returned by GoogleOAuth2Authenticator. It also contains data of the currently logger in user.
 */
class UserIdentity implements IIdentity {
	use SmartObject;

    /** @var User */
    public $userData;

    /**
     * UserIdentity constructor.
     * @param User $user
     */
    public function __construct(User $user) {
        $this->userData = $user;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->userData->getID();
    }

    /**
     * @return null
     */
    public function getRoles()
    {
        return null;
    }

    /**
     * @return User
     */
    public function getData() {
        return $this->userData;
    }

}