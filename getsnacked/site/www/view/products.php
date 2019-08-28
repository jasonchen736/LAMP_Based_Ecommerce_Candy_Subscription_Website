<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	$productInfo = array();

	switch (getRequest('action')) {
		case 'skuSearch':
			$sku = getRequest('sku', 'alphanum');
			if ($sku) {
				$items = productSearch::skuSearch(array($sku));
			}
			break;
		case 'brandSearch':
			$brand = getRequest('brand', 'name');
			if ($brand) {
				$items = productSearch::brandSearch(array($brand));
			}
			break;
		case 'keywordSearch':
		case 'search':
			$keyword = getRequest('search', 'alphanum');
			if ($keyword) {
				$keywords = explode(' ', $keyword);
				foreach ($keywords as $key => $val) {
					if (!$val) {
						unset($keywords[$key]);
					}
				}
				if (count($keywords) > 1) {
					$keywords[] = $keyword;
				}
				if ($keywords) {
					$items = productSearch::keywordSearch($keywords);
				}
			}
			break;
		case 'tagSearch':
		default:
			$category = getRequest('category', 'word');
			$subcategory = getRequest('subcategory', 'word');
			if ($category == 'all') {
				$tags = array();
			} elseif ($category) {
				$tags = array(
					$category
				);
			}
			if ($subcategory) {
				$tags[] = $subcategory;
			}
			if (isset($tags)) {
				$items = productSearch::tagSearch($tags);
			}
			break;
	}

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());
	$template->assignClean('items', $items);
	foreach ($items as $key => $val) {
		$template->rebuildEntities('items > '.$key.' > description', 'all');
		$template->rebuildEntities('items > '.$key.' > shortDescription', 'all');
	}
	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/viewItems.htm');

?>