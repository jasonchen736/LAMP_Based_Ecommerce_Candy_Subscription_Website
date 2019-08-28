<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/construction.htm');

?>