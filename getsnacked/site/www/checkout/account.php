<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	// process input
	if (getRequest('submit')) {
		$user->processInput();
	}

	$member = customerCore::getMember();
	if ($member->exists()) {
		$memberBusinessInfo = membersController::getMemberBusinessInfo($member->get('memberID'));
		checkoutPath::setCompleted();
		checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));
		redirect(checkoutPath::$nextStep);
	} else {
		if (isset($_SESSION['checkout']['member'])) {
			$member = $_SESSION['checkout']['member'];
		}
		if (isset($_SESSION['checkout']['memberBusinessInfo'])) {
			$memberBusinessInfo = $_SESSION['checkout']['memberBusinessInfo'];
		} else {
			$memberBusinessInfo = new memberBusinessInfo;
		}
	}
	if (isset($_SESSION['checkout']['member'])) {
		$member = $_SESSION['checkout']['member'];
	}

	$login = getPost('login', 'email');
	$pass = getPost('pass', 'password');

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('member', $member->fetchArray());
	$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
	$template->assignClean('login', $login);
	$template->assignClean('pass', $pass);
	$template->assignClean('stateOptions', formObject::stateOptions());
	$template->assignClean('countryOptions', formObject::countryOptions());

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/accountForm.htm');

?>