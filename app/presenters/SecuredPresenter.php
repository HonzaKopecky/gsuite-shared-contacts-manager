<?php

namespace App\Presenters;

use App\Model\OAuthService;
use Nette\Application\UI\Presenter;

/** Presenter that requires authenticated GSuite Administrator user
 */
class SecuredPresenter extends Presenter
{
	/** @var  OAuthService */
	protected $oauthService;

	public function __construct(OAuthService $os)
	{
		parent::__construct();
		$this->oauthService = $os;
	}

    /**
     * Require logged in user
     */
	public function startup()
	{
		parent::startup();

		if ($this->user == null || !$this->user->isLoggedIn() || !$this->oauthService->isAuthenticated()) {
			$this->flashMessage("Please log in.");
			$this->redirect("Login:default");
		}
	}
}