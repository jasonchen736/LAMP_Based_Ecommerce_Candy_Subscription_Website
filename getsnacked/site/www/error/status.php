<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	switch(getRequest('code')) {
		// forbidden
		case '403':
			$errorMessage = 'Access to this page is forbidden.';
			break;
		// unauthorized access
		case '401':
			$errorMessage = 'Unauthorized page access.';
			break;
		// internal server error
		case '500':
			$errorMessage = 'The server encountered an unexpected condition which prevented it from fulfilling the request.';
			break;
		// file not found
		case '404':
		default:
			$errorMessage = 'The page you are looking for does not exist.';
			break;
	}

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('errorMessage', $errorMessage);
	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/error.htm');

?>