<?

	require_once 'merchant.php';

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
	 *  Display merchant account info
	 *  Args: none
	 *  Return: none
	 */
	function displayAccountInfo() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$member = new member($merchantInfo['id']);
		$memberBusinessInfo = membersController::getMemberBusinessInfo($merchantInfo['id']);
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('state', formObject::translateStateCode($member->get('state')));
		$template->assignClean('country', formObject::translateCountryCode($member->get('country')));
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->getMessages();
		$template->display('merchant/accountInfo.htm');
	} // function displayAccountInfo

	/**
	 *  Edit merchant account section
	 *  Args: none
	 *  Return: none
	 */
	function editAccount() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$member = new member($merchantInfo['id']);
		$memberBusinessInfo = membersController::getMemberBusinessInfo($member->get('memberID'));
		$controller = new membersController;
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->getMessages();
		$template->display('merchant/accountEdit.htm');
	} // function editAccount

	/**
	 *  Update an existing merchant account record
	 *  Args: none
	 *  Return: none
	 */
	function updateAccount() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$member = new member($merchantInfo['id']);
		$member->makeRequired('first');
		$member->makeRequired('last');
		$member->makeRequired('address1');
		$member->makeRequired('city');
		$member->makeRequired('state');
		$member->makeRequired('country');
		$member->makeRequired('postal');
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
		$memberBusinessInfo = membersController::getMemberBusinessInfo($member->get('memberID'));
		$memberBusinessInfo->set('company', getPost('company'));
		$memberBusinessInfo->set('fax', getPost('fax'));
		$memberBusinessInfo->set('website', getPost('website'));
		$memberBusinessInfo->set('taxID', getPost('taxID'));
		$memberBusinessInfo->set('industry', getPost('industry'));
		$memberBusinessInfo->set('description', getPost('description'));
		$memberBusinessInfo->set('payTo', getPost('payTo'));
		$memberBusinessInfo->set('im', getPost('im'));
		$existingEmail = $member->get('email');
		$email = getPost('email');
		if (validEmail($email)) {
			$member->set('email', getPost('email'));
		} else {
			$member->set('email', '');
		}
		if ($email == $existingEmail || ($email != $existingEmail && !membersController::memberExists($email))) {
			if ($member->update()) {
				addSuccess('Contact information updated successfully');
				if ($memberBusinessInfo->exists()) {
					if (!$memberBusinessInfoSaved = $memberBusinessInfo->update()) {
						addError('There was an error while updating your business information');
					}
				} else {
					$memberBusinessInfo->set('memberID', $member->get('memberID'));
					if (!$memberBusinessInfoSaved = $memberBusinessInfo->save()) {
						addError('There was an error while saving your business information');
					}
				}
				if ($memberBusinessInfoSaved) {
					editAccount();
					exit;
				}
			}
		} else {
			addError('There is an existing account registered under the email address');
		}
		$memberBusinessInfo->assertRequired();
		addError('There was an error while updating your information');
		$member->set('email', getPost('email'));
		$controller = new membersController;
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->getMessages();
		$template->display('merchant/accountEdit.htm');
	} // function updateAccount

?>