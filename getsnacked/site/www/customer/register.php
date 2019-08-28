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
		if (customerCore::validate()) {
			redirect('/error/status/code/404');
		}
		$template = new template;
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('customer/registrationForm.htm');
	} // function registrationForm

	/**
	 *  Register and save a customer member
	 *  Args: none
	 *  Return: none
	 */
	function register() {
		if (customerCore::validate()) {
			redirect('/error/status/code/404');
		}
		$member = new member;
		$member->set('first', getPost('first'));
		$member->set('last', getPost('last'));
		$member->set('phone', getPost('phone'));
		$member->set('address1', getPost('address1'));
		$member->set('address2', getPost('address2'));
		$member->set('city', getPost('city'));
		$member->set('postal', getPost('postal'));
		$member->set('country', getPost('country'));
		$member->set('status', 'active');
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
		if (!membersController::memberExists($email)) {
			$customerAgreement = getPost('customerAgreement');
			if ($customerAgreement) {
				if ($passwordMatch && $member->save()) {
					$_SESSION['customerSignupSuccess'] = true;
					customerCore::setCore($member->get('memberID'));
					if (!$member->addSites(array(systemSettings::get('SITEID')))) {
						addError('There was an error while saving your account');
					}
					if (!$member->addGroups(array('1'))) {
						addError('There was an error while saving your account');
					}
					addSuccess('Thank you for signing up with '.systemSettings::get('SITENAME'));
					addSuccess('You may now access your account home');
					$template = new template;
					$template->assign('member', $member->fetchArray());
					$template->assign('password', $password);
					$template->registerCampaignResource();
					$mailer = new mailer;
					$mailer->setMessage('subject', $template->fetch('campaign:customerSignupAcknowledgement:subject'));
					$mailer->setMessage('from', $template->fetch('campaign:customerSignupAcknowledgement:from'));
					$mailer->setMessage('html', $template->fetch('campaign:customerSignupAcknowledgement:html'));
					$mailer->setMessage('text', $template->fetch('campaign:customerSignupAcknowledgement:text'));
					if ($mailer->composeMessage()) {
						$merchantEmail = $member->get('email');
						if ($merchantEmail) {
							$mailer->addRecipient($merchantEmail);
						}
						$mailer->send();
					}
					$mailer = new mailer;
					$mailer->setMessage('subject', $template->fetch('campaign:customerSignupNotification:subject'));
					$mailer->setMessage('from', $template->fetch('campaign:customerSignupNotification:from'));
					$mailer->setMessage('html', $template->fetch('campaign:customerSignupNotification:html'));
					$mailer->setMessage('text', $template->fetch('campaign:customerSignupNotification:text'));
					if ($mailer->composeMessage()) {
						$adminEmails = systemSettings::get('ADMINEMAILS');
						foreach ($adminEmails as $email) {
							$mailer->addRecipient($email);
						}
						$mailer->send();
					}
					redirect('/customer/register/action/success');
				}
			} else {
				addError('You must accept the customer agreement conditions in order to complete registration');
				addErrorField('customerAgreement');
			}
		} else {
			addError('There is an existing account registered under your email address');
		}
		addError('There was an error while saving your account');
		$member->set('email', getPost('email'));
		$member->assertRequired();
		$template = new template;
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->getMessages();
		$template->display('customer/registrationForm.htm');
	} // function register

	/**
	 *  Successful customer registration, display success page
	 *  Args: none
	 *  Return: none
	 */
	function success() {
		if (!getSession('customerSignupSuccess')) {
			redirect('/error/status/code/404');
		}
		unset($_SESSION['customerSignupSuccess']);
		$template = new template;
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->setCartData();
		$template->getMessages();
		$template->display('customer/registrationSuccess.htm');
	} // function success

?>