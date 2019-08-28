<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	$productID = getRequest('id');

	if (validNumber($productID, 'integer') && $productID) {
		$product = new product($productID);
		if ($product->exists()) {
			$sites = $product->getObjectSites();
			if (in_array(systemSettings::get('SITEID'), $sites)) {
				$item = $product->fetchArray();
				$item['itemType'] = 'product';
				$item['itemID'] = $item['productID'];
				if ($item['availability'] == 'available' || $item['availability'] == 'alwaysavailable') {
					// initialize template
					$template = new template;
					$template->assignClean('customerLoggedIn', customerCore::validate());
					$template->assignClean('item', $item);
					$template->rebuildEntities('item > description', 'all');
					$template->rebuildEntities('item > shortDescription', 'all');
					$template->assignClean('itemType', 'product');
					$template->assignClean('itemID', $item['productID']);
					$template->setCheckoutData();
					$template->setCartData();
					$template->setProductsGateway();
					$template->getMessages();
					$template->display('site/viewItem.htm');
					exit;
				}
			}
		}
	}
	// item not found
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('errorMessage', 'The item you were looking for could not be found.');
	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/error.htm');

?>
