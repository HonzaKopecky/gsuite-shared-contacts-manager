<?php

namespace App\Presenters;

use App\Model\OAuthService;
use App\Model\User;
use Tracy\Debugger;

/** Presenter that displays dashboard and user info
 */
class DashboardPresenter extends SecuredPresenter
{
    /**
     * @var OAuthService
     * @inject
     */
    private $oauth = null;

    public function __construct(OAuthService $os) {
		parent::__construct($os);
		$this->oauth = $os;
    }

    /**
     * URL = /dashboard
     * Show information about logged user
     */
    public function renderDefault() {
        /** @var User $userData */
        $userData = $this->user->getIdentity()->userData;
        $this->template->token = $this->oauth->getAccessToken();
        $this->template->userData = $userData;

        $us = new \Google_Service_Directory($this->oauth->getClient());
        $s = $us->users->get($userData->getID());
        Debugger::barDump($s);
    }
}