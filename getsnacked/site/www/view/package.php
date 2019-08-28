<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	$packageID = getRequest('id');

	if (validNumber($packageID, 'integer') && $packageID) {
		$package = new package($packageID);
		if ($package->exists()) {
			$sites = $package->getObjectSites();
			if (in_array(systemSettings::get('SITEID'), $sites)) {
				$item = $package->fetchArray();
				$item['itemType'] = 'package';
				$item['itemID'] = $item['packageID'];
				if ($item['availability'] == 'available' || $item['availability'] == 'alwaysavailable') {
					// initialize template
					$template = new template;
					$template->assignClean('customerLoggedIn', customerCore::validate());
					$template->assignClean('itemCount', $user->getObjectData('package', 'itemCount'));
					$template->assignClean('subtotal', $user->getObjectData('package', 'totalCost'));
					$template->assignClean('item', $item);
					$template->rebuildEntities('item > description', 'all');
					$template->rebuildEntities('item > shortDescription', 'all');
					$template->assignClean('itemType', 'package');
					$template->assignClean('itemID', $item['packageID']);
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
