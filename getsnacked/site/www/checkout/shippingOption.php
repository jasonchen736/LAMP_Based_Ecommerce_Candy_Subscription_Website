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

	$orderForm = $user->getObjectData('order', 'form');

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('orderForm', $orderForm);
	$template->assignClean('shippingOptions', shippingOptionsController::getAvailableShippingOptions($user->get('shippingAddress'), $user->get('package')));

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/shippingOption.htm');

?>