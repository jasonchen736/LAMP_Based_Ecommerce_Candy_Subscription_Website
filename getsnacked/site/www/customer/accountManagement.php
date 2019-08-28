<?

	require_once 'customer.php';

	$actions = array(
		'displayAccountInfo',
		'editAccount',
		'updateAccount'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		displayAccountInfo();
	}

	/**
	 *  Display customer account info
	 *  Args: none
	 *  Return: none
	 */
	function displayAccountInfo() {
		$customerInfo = customerCore::getCustomerInfo();
		$member = new member($customerInfo['id']);
		$template = new template;
		$template->assignClean('customerInfo', $customerInfo);
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('state', formObject::translateStateCode($member->get('state')));
		$template->assignClean('country', formObject::translateCountryCode($member->get('country')));
		$template->getMessages();
		$template->display('customer/accountInfo.htm');
	} // function displayAccountInfo

	/**
	 *  Edit customer account section
	 *  Args: none
	 *  Return: none
	 */
	function editAccount() {
		$customerInfo = customerCore::getCustomerInfo();
		$member = new member($customerInfo['id']);
		$controller = new membersController;
		$template = new template;
		$template->assignClean('customerInfo', $customerInfo);
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('customer/accountEdit.htm');
	} // function editAccount

	/**
	 *  Update an existing customer account record
	 *  Args: none
	 *  Return: none
	 */
	function updateAccount() {
		$customerInfo = customerCore::getCustomerInfo();
		$member = new member($customerInfo['id']);
		$member->set('first', getPost('first'));
		$member->set('last', getPost('last'));
		$member->set('phone', getPost('phone'));
		$member->set('address1', getPost('address1'));
		$member->set('address2', getPost('address2'));
		$member->set('city', getPost('city'));
		$member->set('postal', getPost('postal'));
		$member->set('country', getPost('country'));
		$state = getPost('state', 'alphanum');
		if ($state) {
			$member->set('state', getPost('state'));
		} else {
			$member->set('state', getPost('province'));
		}
		$currentPassword = getPost('currentPassword');
		$newPassword = getPost('newPassword');
		$confirmPassword = getPost('confirmPassword');
		if ($currentPassword || $newPassword || $confirmPassword) {
			$currentPassword = clean($currentPassword, 'password');
			$newPassword = clean($newPassword, 'password');
			$confirmPassword = clean($confirmPassword, 'password');
			if ($newPassword && $newPassword == $confirmPassword) {
				if ($member->verifyExistingPassword($currentPassword)) {
					$member->set('password', "PASSWORD('".$newPassword."')", false);
					$member->enclose('password', false);
				} else {
					$member->set('password', '');
					addError('Current password does not match');
				}
			} else {
				$member->set('password', '');
				addError('New password does not match confirmation');
			}
		}
		$existingEmail = $member->get('email');
		$email = getPost('email');
		if (validEmail($email)) {
			$member->set('email', getPost('email'));
		} else {
			$member->set('email', '');
		}
		if ($email == $existingEmail || ($email != $existingEmail && !membersController::memberExists($email))) {
			if ($member->update()) {
				addSuccess('Your account information was updated successfully');
				editAccount();
				exit;
			}
		} else {
			addError('There is an existing account registered under this email address');
		}
		addError('There was an error while updating your account information');
		$member->set('email', getPost('email'));
		$controller = new membersController;
		$template = new template;
		$template->assignClean('customerInfo', $customerInfo);
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('customer/accountEdit.htm');
	} // function updateAccount

?>