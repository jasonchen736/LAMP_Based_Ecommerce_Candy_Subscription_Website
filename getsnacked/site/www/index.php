<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	$tags = array(
		'FEATURED'
	);
	$featured = productSearch::tagSearch($tags);

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());

	$template->assignClean('items', $featured);
	foreach ($featured as $key => $val) {
		$template->rebuildEntities('items > '.$key.' > description', 'all');
		$template->rebuildEntities('items > '.$key.' > shortDescription', 'all');
	}

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/home.htm');

?>