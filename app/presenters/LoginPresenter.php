<?php

namespace App\Presenters;

use App\Model\OAuthService;
use Nette;
use Tracy\Debugger;

/** Presenter that handles User authentication
 */
class LoginPresenter extends Nette\Application\UI\Presenter
{
    const PARAM_ERROR = "message";
    const PARAM_ERROR_CODE = "error_code";
    const PARAM_CODE = "code";

    /** @var  OAuthService */
    private $oauth;

    /**
     * LoginPresenter constructor.
     * @param OAuthService $os
     */
    public function __construct(OAuthService $os) {
        parent::__construct();
        $this->oauth = $os;
    }

    /**
     * Display login page with Google login button
     */
    public function renderDefault() {
        if($this->user->isLoggedIn() && $this->oauth->isAuthenticated())
            $this->redirect("Dashboard:default");
        $this->user->logout();
        $this->template->loginLink = $this->oauth->getGoogleLink();
    }

    /**
     * Handle callback from Google
     */
    public function actionOauth2callback() {
        if($this->getParameter(LoginPresenter::PARAM_ERROR) != null) {
            Debugger::log("Google returned error when authenticating user: " . $this->getParameter(LoginPresenter::PARAM_ERROR));
            $this->flashMessage("There was a problem with your login. We keep track of this error and will address it asap.");
            $this->redirect("Homepage:default");
        }
        $authCode = $this->getParameter(LoginPresenter::PARAM_CODE);
        try {
            $this->user->login($authCode);
        } catch (Nette\Security\AuthenticationException $ex) {
            if($ex->getCode() == OAuthService::ERROR_NO_DOMAIN)
                $this->flashMessage("Ooops! This account is not member of any GSuite domain. Did you choose the right account?");
            elseif ($ex->getCode() == OAuthService::ERROR_NOT_ADMIN)
                $this->flashMessage("Ooops! It looks you are not admin of your GSuite domain. Did you choose the right account?");
            else {
                $this->flashMessage("Ooops! This is pretty strange. We bumped to an error during your authentication. Try again please or contact us.");
                Debugger::log($ex);
            }
            $this->redirect("Homepage:default");
        }

        $this->redirect("Dashboard:default");
    }

    /**
     * Display page that informs user about failure durong login
     */
    public function renderError() {
        $this->template->error = $this->getParameter(LoginPresenter::PARAM_ERROR);
    }

    /**
     * Logout user
     */
    public function actionLogout() {
        $this->user->logout();
        $this->redirect("Homepage:default");
    }

    /**
     * Redirect user to Google so he refreshes access token
     */
    public function actionReauth() {
        $this->redirectUrl($this->oauth->getGoogleLink());
    }
}
