<?

	$actions = array(
		'registrationForm',
		'register',
		'success'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		registrationForm();
	}

	/**
	 *  Show the registration form
	 *  Args: none
	 *  Return: none
	 */
	function registrationForm() {
		$template = new template;
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->getMessages();
		$template->display('merchant/registrationForm.htm');
	} // function registrationForm

	/**
	 *  Register and save a merchant member
	 *  Args: none
	 *  Return: none
	 */
	function register() {
		$member = new member;
		$member->makeRequired('first');
		$member->makeRequired('last');
		$member->makeRequired('phone');
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
		$member->set('status', 'new');
		$member->set('password', getPost('password'));
		$password = $member->get('password');
		$confirmPassword = getPost('passwordConfirm');
		if ($password == $confirmPassword) {
			$passwordMatch = true;
		} else {
			$passwordMatch = false;
			addError('Your password confirmation does not match');
			addErrorField('password');
			addErrorField('passwordConfirm');
		}
		$memberBusinessInfo = new memberBusinessInfo;
		$memberBusinessInfo->set('company', getPost('company'));
		$memberBusinessInfo->set('fax', getPost('fax'));
		$memberBusinessInfo->set('website', getPost('website'));
		$memberBusinessInfo->set('taxID', getPost('taxID'));
		$memberBusinessInfo->set('industry', getPost('industry'));
		$memberBusinessInfo->set('description', getPost('description'));
		$memberBusinessInfo->set('payTo', getPost('payTo'));
		$memberBusinessInfo->set('im', getPost('im'));
		$state = getPost('state', 'alphanum');
		if ($state) {
			$member->set('state', getPost('state'));
		} else {
			$member->set('state', getPost('province'));
		}
		$email = getPost('email');
		if (validEmail($email)) {
			$member->set('email', getPost('email'));
		}
		$businessCheck = $memberBusinessInfo->assertRequired();
		if (!membersController::memberExists($email)) {
			$merchantAgreement = getPost('merchantAgreement');
			if ($merchantAgreement) {
				if ($businessCheck && $passwordMatch && $member->save()) {
					if (!$member->addSites(array(systemSettings::get('SITEID')))) {
						addError('There was an error while saving your merchant registration');
					}
					if (!$member->addGroups(array('2'))) {
						addError('There was an error while saving your merchant registration');
					}
					$memberBusinessInfo->set('memberID', $member->get('memberID'));
					if (!$memberBusinessInfo->save()) {
						addError('There was an error while saving your merchant registation');
					} else {
						addSuccess('Thank you for registering with '.systemSettings::get('SITENAME'));
						addSuccess('Your registration has been received and we will contact you shortly to complete your registration');
						$template = new template;
						$template->assign('member', $member->fetchArray());
						$template->assign('memberBusinessInfo', $memberBusinessInfo->fetchArray());
						$template->assign('password', $password);
						$template->registerCampaignResource();
						$mailer = new mailer;
						$mailer->setMessage('subject', $template->fetch('campaign:merchantSignupAcknowledgement:subject'));
						$mailer->setMessage('from', $template->fetch('campaign:merchantSignupAcknowledgement:from'));
						$mailer->setMessage('html', $template->fetch('campaign:merchantSignupAcknowledgement:html'));
						$mailer->setMessage('text', $template->fetch('campaign:merchantSignupAcknowledgement:text'));
						if ($mailer->composeMessage()) {
							$merchantEmail = $member->get('email');
							if ($merchantEmail) {
								$mailer->addRecipient($merchantEmail);
							}
							$mailer->send();
						}
						$mailer = new mailer;
						$mailer->setMessage('subject', $template->fetch('campaign:merchantSignupNotification:subject'));
						$mailer->setMessage('from', $template->fetch('campaign:merchantSignupNotification:from'));
						$mailer->setMessage('html', $template->fetch('campaign:merchantSignupNotification:html'));
						$mailer->setMessage('text', $template->fetch('campaign:merchantSignupNotification:text'));
						if ($mailer->composeMessage()) {
							$adminEmails = systemSettings::get('ADMINEMAILS');
							foreach ($adminEmails as $email) {
								$mailer->addRecipient($email);
							}
							$mailer->send();
						}
						redirect('/merchant/register/action/success');
					}
				}
			} else {
				addError('You must accept the merchant agreement conditions in order to complete registration');
				addErrorField('merchantAgreement');
			}
		} else {
			addError('There is an existing account registered under your email address');
		}
		addError('There was an error while saving your merchant registation');
		$member->set('email', getPost('email'));
		$member->assertRequired();
		$controller = new membersController;
		$template = new template;
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->getMessages();
		$template->display('merchant/registrationForm.htm');
	} // function register

	/**
	 *  Successful merchant registration, display success page
	 *  Args: none
	 *  Return: none
	 */
	function success() {
		$template = new template;
		$template->getMessages();
		$template->display('merchant/registrationSuccess.htm');
	} // function success

?>