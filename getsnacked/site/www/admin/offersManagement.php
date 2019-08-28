<?

	require_once 'admin.php';

	$actions = array(
		'offersAdmin',
		'addOffer',
		'saveOffer',
		'editOffer',
		'updateOffer',
		'quickUpdate',
		'viewOffers',
		'updateOffers'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		offersAdmin();
	}

	/**
	 *  Show the offers admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function offersAdmin() {
	} // function offersAdmin

	/**
	 *  View a set of offers with limited update options
	 *  Args: none
	 *  Return: none
	 */
	function viewOffers() {
		$dbh = database::getInstance();
		$item = getRequest('item');
		$itemID = getRequest('itemID', 'integer');
		$offers = array();
		if ($item == 'product') {
			$product = new product($itemID);
			if ($product->exists()) {
				$sql = "SELECT `d`.`offerID`, `d`.`name` AS `offerName`, `d`.`payType`, `d`.`payOut`, 
							`d`.`availability` AS `offerAvailability`, `b`.`cost` AS `offerCost`, 
							`c`.`packageID`, `c`.`name` AS `packageName`, `c`.`cost` AS `packageCost`, 
							`c`.`weight`, `c`.`availability` AS `packageAvailability` 
						FROM `productToPackage` `a` 
						JOIN `packageToOffer` `b` USING (`packageID`) 
						JOIN `packages` `c` USING (`packageID`) 
						JOIN `offers` `d` USING (`offerID`) 
						WHERE `a`.`productID` = '".$product->get('productID')."'";
				$result = $dbh->query($sql);
				if ($result->rowCount > 0) {
					$offers = $result->fetchAll();
				}
			}
		} else {
			$package = new package($itemID);
			if ($package->exists()) {
				$sql = "SELECT `c`.`offerID`, `c`.`name` AS `offerName`, `c`.`payType`, `c`.`payOut`, 
							`c`.`availability` AS `offerAvailability`, `a`.`cost` AS `offerCost`, 
							`b`.`packageID`, `b`.`name` AS `packageName`, `b`.`cost` AS `packageCost`, 
							`b`.`weight`, `b`.`availability` AS `packageAvailability` 
						FROM `packageToOffer` `a` 
						JOIN `packages` `b` USING (`packageID`) 
						JOIN `offers` `c` USING (`offerID`) 
						WHERE `packageID` = '".$package->get('packageID')."'";
				$result = $dbh->query($sql);
				if ($result->rowCount > 0) {
					$offers = $result->fetchAll();
				}
			}
		}
		if (empty($offers)) {
			addMessage('There were no offers found');
		}
		$template = new template;
		$template->assignClean('offers', $offers);
		$template->getMessages();
		$template->display('admin/viewOffers.htm');
	} // function viewOffers

	/**
	 *  Perform limited updates to a set of offers
	 *  Args: none
	 *  Return: none
	 */
	function updateOffers() {
		$records = getRequest('records');
		assertArray($records);
		$offerCosts = getRequest('offerCosts');
		assertArray($offerCosts);
		$offerData = array();
		foreach ($records as $key => $val) {
			$items = explode(';', $val);
			if (validNumber($items[0]) && validNumber($items[1])) {
				if (isset($offerCosts[$key]) && validNumber($offerCosts[$key], 'double')) {
					$offerData[$key]['offerID'] = $items[0];
					$offerData[$key]['packageID'] = $items[1];
					$offerData[$key]['offerCost'] = $offerCosts[$key];
				} else {
					addError('Invalid data was submitted for offer ID '.$items[0].', package ID '.$items[1]);
				}
			} else {
				addError('One or more offer packages was invalid');
				addError('All offer packages should be checked');
			}
		}
		if (!empty($offerData)) {
			if (offersController::updateOfferPackages($offerData)) {
				addSuccess('Offer data has been updated successfully');
			}
		}
		redirect('/admin/offers');
	} // function updateOffers

?>