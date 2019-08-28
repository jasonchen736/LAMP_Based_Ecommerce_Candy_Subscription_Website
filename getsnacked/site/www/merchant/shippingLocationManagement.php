<?

	require_once 'merchant.php';

	$actions = array(
		'overview',
		'editLocation',
		'updateLocation'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		overview();
	}

	/**
	 *  Display shipping location overview
	 *  Args: none
	 *  Return: none
	 */
	function overview() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$memberShippingLocation = memberShippingLocation::retrieveMemberShippingLocation($merchantInfo['id']);
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('memberShippingLocation', $memberShippingLocation->fetchArray());
		$template->assignClean('country', formObject::translateCountryCode($memberShippingLocation->get('country')));
		$template->getMessages();
		$template->display('merchant/shippingLocationOverview.htm');
	} // function overview

	/**
	 *  Edit merchant account section
	 *  Args: none
	 *  Return: none
	 */
	function editLocation() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$memberShippingLocation = memberShippingLocation::retrieveMemberShippingLocation($merchantInfo['id']);
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('memberShippingLocation', $memberShippingLocation->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('merchant/shippingLocationEdit.htm');
	} // function editLocation

	/**
	 *  Update an existing merchant account record
	 *  Args: none
	 *  Return: none
	 */
	function updateLocation() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$memberShippingLocation = memberShippingLocation::retrieveMemberShippingLocation($merchantInfo['id']);
		$memberShippingLocation->set('postal', getPost('postal'));
		$memberShippingLocation->set('country', getPost('country'));
		$state = getPost('state', 'alphanum');
		if ($state) {
			$memberShippingLocation->set('state', getPost('state'));
		} else {
			$memberShippingLocation->set('state', getPost('province'));
		}
		if ($memberShippingLocation->exists()) {
			if ($memberShippingLocation->update()) {
				addSuccess('Shipping location saved');
				overview();
				exit;
			} else {
				addError('There was an error while updating the shipping location');
			}
		} else {
			$memberShippingLocation->set('memberID', $merchantInfo['id']);
			if ($memberShippingLocation->save()) {
				addSuccess('Shipping location updated');
				overview();
				exit;
			} else {
				addError('There was an error while saving the shipping location');
			}
		}
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('memberShippingLocation', $memberShippingLocation->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('merchant/shippingLocationEdit.htm');
	} // function updateLocation

?>