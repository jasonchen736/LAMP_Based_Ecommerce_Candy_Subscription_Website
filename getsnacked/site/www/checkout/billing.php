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

	$addressForm = $user->getObjectData('billingAddress', 'form');
	if (!$addressForm['country']) {
		$addressForm['country'] = 'USA';
	}
	$saveAddress = $user->getObjectData('billingAddress', 'saveAddress');
	$defaultAddress = $user->getObjectData('billingAddress', 'defaultAddress');

	$paymentMethod = $user->getObjectData('paymentMethod', 'form');

	$expYears = array();
	for ($i = 0; $i <= 10; $i++) {
		$expYears[] = date('Y', strtotime('+ '.$i.' year'));
	}

	$expMonths = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('billingAddress', $addressForm);
	$template->assignClean('saveAddress', $saveAddress);
	$template->assignClean('defaultAddress', $defaultAddress);
	$template->assignClean('stateOptions', formObject::stateOptions());
	$template->assignClean('countryOptions', formObject::countryOptions());
	$template->assignClean('paymentMethod', $paymentMethod);
	$template->assignClean('expMonths', $expMonths);
	$template->assignClean('expYears', $expYears);

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/billingForm.htm');

?>