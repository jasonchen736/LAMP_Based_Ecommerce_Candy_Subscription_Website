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

	$addressForm = $user->getObjectData('shippingAddress', 'form');
	if (!$user->getObjectData('shippingAddress', 'formSubmitted')) {
		$member = customerCore::getMember();
		$addressForm = array(
			'first' => $member->get('first'),
			'last' => $member->get('last'),
			'address1' => $member->get('address1'),
			'address2' => $member->get('address2'),
			'city' => $member->get('city'),
			'state' => $member->get('state'),
			'postal' => $member->get('postal'),
			'email' => $member->get('email'),
			'country' => $member->get('country')
		);
	}
	if (!$addressForm['country']) {
		$addressForm['country'] = 'US';
	}
	$saveAddress = $user->getObjectData('shippingAddress', 'saveAddress');
	$defaultAddress = $user->getObjectData('shippingAddress', 'defaultAddress');

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('shippingAddress', $addressForm);
	$template->assignClean('saveAddress', $saveAddress);
	$template->assignClean('defaultAddress', $defaultAddress);
	$template->assignClean('stateOptions', formObject::stateOptions());
	$template->assignClean('countryOptions', formObject::countryOptions());

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/shippingForm.htm');

?>