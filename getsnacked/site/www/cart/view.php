<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	// temporarily hack add item url to work
	$action = getRequest('action');
	$type = getRequest('type');
	$id = getRequest('id', 'integer');
	if ($action == 'add') {
		$_REQUEST['createPackage'] = 1;
		if ($type == 'package') {
			$_REQUEST[$type.$id] = 1;
		} else {
			$_REQUEST['add'.$id] = 1;
		}
	}

	// process input
	if (getRequest('submit') || getRequest('createPackage')) {
		$user->processInput();
	}

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/cart.htm');

?>