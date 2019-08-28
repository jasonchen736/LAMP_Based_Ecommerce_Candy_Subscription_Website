<?

	require_once 'admin.php';

	$actions = array(
		'membersAdmin',
		'addMember',
		'saveMember',
		'editMember',
		'updateMember'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		membersAdmin();
	}

	/**
	 *  Show the members admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function membersAdmin() {
		$controller = new membersController;
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$template = new template;
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('countryOptions', formObject::countryOptions('abbreviated'));
		$template->getMessages();
		$template->display('admin/membersAdmin.htm');	
	} // function membersAdmin

	/**
	 *  Add member section
	 *  Args: none
	 *  Return: none
	 */
	function addMember() {
		$member = new member;
		$member->set('site', systemSettings::get('SITENAME'));
		$memberBusinessInfo = new memberBusinessInfo;
		$controller = new membersController;
		$template = new template;
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->assignClean('statusOptions', $controller->getOptions('status'));
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('groups', array());
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->assignClean('groupOptions', membersController::groupOptions());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/memberEdit.htm');
	} // function addMember

	/**
	 *  Save a new member record
	 *  Args: none
	 *  Return: none
	 */
	function saveMember() {
		$member = new member;
		$member->set('first', getPost('first'));
		$member->set('last', getPost('last'));
		$member->set('phone', getPost('phone'));
		$member->set('address1', getPost('address1'));
		$member->set('address2', getPost('address2'));
		$member->set('city', getPost('city'));
		$member->set('postal', getPost('postal'));
		$member->set('country', getPost('country'));
		$member->set('status', getPost('status'));
		$member->set('password', getPost('password'));
		$password = $member->get('password');
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
		if (!membersController::memberExists($email)) {
			if ($member->save()) {
				addSuccess('Member details '.$member->get('name').' saved successfully');
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				if (!empty($sites)) {
					if (!$member->addSites($sites)) {
						addError('There was an error while processing the member access websites');
					}
				} else {
					addSuccess('There are no sites associated with the member');
				}
				$groups = getPost('groups');
				assertArray($groups);
				foreach ($groups as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($groups[$key]);
					}
				}
				if (!empty($groups)) {
					if (!$member->addGroups($groups)) {
						addError('There was an error while processing the member groups');
					}
				} else {
					addSuccess('There are no groups associated with the member');
				}
				$groups = $member->getMemberGroups();
				$businessGroupIDs = membersController::businessGroupIDs();
				$businessMember = array_intersect($groups, $businessGroupIDs);
				if (empty($businessMember)) {
					$memberBusinessInfo->unRequire('company');
					$memberBusinessInfo->unRequire('taxID');
					$memberBusinessInfo->unRequire('payTo');
				}
				$memberBusinessInfo->set('memberID', $member->get('memberID'));
				if (!$memberBusinessInfo->save()) {
					addError('There was an error while saving the member\'s business information');
				} else {
					if (!empty($businessMember) && $member->get('status') == 'active') {
						$template = new template;
						$template->assign('member', $member->fetchArray());
						$template->assign('memberBusinessInfo', $memberBusinessInfo->fetchArray());
						$template->registerCampaignResource();
						$mailer->setMessage('subject', $template->fetch('campaign:businessAccountActivation:subject'));
						$mailer->setMessage('from', $template->fetch('campaign:businessAccountActivation:from'));
						$mailer->setMessage('html', $template->fetch('campaign:businessAccountActivation:html'));
						$mailer->setMessage('text', $template->fetch('campaign:businessAccountActivation:text'));
						if ($mailer->composeMessage()) {
							$merchantEmail = $member->get('email');
							if ($merchantEmail) {
								$mailer->addRecipient($merchantEmail);
							}
							if ($mailer->send()) {
								addSuccess('A notification email for business account activation has been sent to the member');
							} else {
								addError('There was an error while sending the business account activation notification');
							}
						}
					}
					if (haveErrors() || getRequest('submit') == 'Add and Edit') {
						editMember($member->get('memberID'));
					} else {
						addMember();
					}
					exit;
				}
			}
		} else {
			addError('There is an existing account registered under the email address');
		}
		$memberBusinessInfo->assertRequired();
		addError('There was an error while saving the member');
		$member->set('email', getPost('email'));
		$sites = getPost('sites');
		assertArray($sites);
		foreach ($sites as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($sites[$key]);
			}
		}
		$groups = getPost('groups');
		assertArray($groups);
		foreach ($groups as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($groups[$key]);
			}
		}
		$controller = new membersController;
		$template = new template;
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
		$template->assignClean('sites', $sites);
		$template->assignClean('groups', $groups);
		$template->assignClean('statusOptions', $controller->getOptions('status'));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
		$template->assignClean('groupOptions', membersController::groupOptions());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/memberEdit.htm');
	} // function saveMember

	/**
	 *  Edit member section
	 *  Args: (int) member id
	 *  Return: none
	 */
	function editMember($memberID = false) {
		if (!$memberID) {
			$memberID = getRequest('memberID', 'integer');
		}
		$member = new member($memberID);
		if ($member->exists()) {
			$memberBusinessInfo = membersController::getMemberBusinessInfo($member->get('memberID'));
			$controller = new membersController;
			$template = new template;
			$template->assignClean('member', $member->fetchArray());
			$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
			$template->assignClean('sites', $member->getObjectSites());
			$template->assignClean('groups', $member->getMemberGroups());
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('stateOptions', formObject::stateOptions());
			$template->assignClean('countryOptions', formObject::countryOptions());
			$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
			$template->assignClean('groupOptions', membersController::groupOptions());
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/memberEdit.htm');
		} else {
			addError('Member does not exist');
			membersAdmin();
		}
	} // function editMember

	/**
	 *  Update an existing member record
	 *  Args: none
	 *  Return: none
	 */
	function updateMember() {
		$member = new member(getRequest('memberID', 'integer'));
		if ($member->exists()) {
			if (getPost('status') == 'active' && $member->get('status') != 'active') {
				$activated = true;
			} else {
				$activated = false;
			}
			$member->set('first', getPost('first'));
			$member->set('last', getPost('last'));
			$member->set('phone', getPost('phone'));
			$member->set('address1', getPost('address1'));
			$member->set('address2', getPost('address2'));
			$member->set('city', getPost('city'));
			$member->set('postal', getPost('postal'));
			$member->set('country', getPost('country'));
			$member->set('status', getPost('status'));
			$password = getPost('password');
			if ($password != $member->get('password')) {
				$member->set('password', $password);
				if ($member->get('password')) {
					$member->set('password', "PASSWORD('".$member->get('password')."')", false);
					$member->enclose('password', false);
				}
			}
			$state = getPost('state', 'alphanum');
			if ($state) {
				$member->set('state', getPost('state'));
			} else {
				$member->set('state', getPost('province'));
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
					addSuccess('Member details '.$member->get('name').' updated successfully');
					$sites = getPost('sites');
					assertArray($sites);
					foreach ($sites as $key => $val) {
						if (!validNumber($val, 'integer')) {
							unset($sites[$key]);
						}
					}
					$existingSites = $member->getObjectSites();
					$addSites = array_diff($sites, $existingSites);
					if (!empty($addSites)) {
						if ($member->addSites($addSites)) {
							addSuccess('Access websites have been added successfully');
						} else {
							addError('There was an error while adding member websites');
						}
					}
					$removeSites = array_diff($existingSites, $sites);
					if (!empty($removeSites)) {
						if ($member->removeSites($removeSites)) {
							addSuccess('Access websites removed successfully');
						} else {
							addError('There was an error while removing member websites');
						}
					}
					$businessGroupIDs = membersController::businessGroupIDs();
					$groups = getPost('groups');
					assertArray($groups);
					foreach ($groups as $key => $val) {
						if (!validNumber($val, 'integer')) {
							unset($groups[$key]);
						}
					}
					$existingGroups = $member->getMemberGroups();
					$addGroups = array_diff($groups, $existingGroups);
					if (!empty($addGroups)) {
						$businessEnabled = array_intersect($addGroups, $businessGroupIDs);
						if (!empty($businessEnabled)) {
							$businessEnabled = true;
						} else {
							$businessEnabled = false;
						}
						if ($member->addGroups($addGroups)) {
							addSuccess('Member groups have been added successfully');
						} else {
							addError('There was an error while adding member groups');
						}
					}
					$removeGroups = array_diff($existingGroups, $groups);
					if (!empty($removeGroups)) {
						if ($member->removeGroups($removeGroups)) {
							addSuccess('Member groups removed successfully');
						} else {
							addError('There was an error while removing member groups');
						}
					}
					$groups = $member->getMemberGroups();
					$businessMember = array_intersect($groups, $businessGroupIDs);
					if (empty($businessMember)) {
						$memberBusinessInfo->unRequire('company');
						$memberBusinessInfo->unRequire('taxID');
						$memberBusinessInfo->unRequire('payTo');
					}
					if ($memberBusinessInfo->exists()) {
						if (!$memberBusinessInfoSaved = $memberBusinessInfo->update()) {
							addError('There was an error while updating the member\'s business information');
						}
					} else {
						$memberBusinessInfo->set('memberID', $member->get('memberID'));
						if (!$memberBusinessInfoSaved = $memberBusinessInfo->save()) {
							addError('There was an error while saving the member\'s business information');
						}
					}
					if ($memberBusinessInfoSaved) {
						// if activating a business account
						//   or enabling business group for an active account
						if (($activated && !empty($businessMember)) || ($businessEnabled && $member->get('status') == 'active')) {
							$template = new template;
							$template->assign('member', $member->fetchArray());
							$template->assign('memberBusinessInfo', $memberBusinessInfo->fetchArray());
							$template->registerCampaignResource();
							$mailer->setMessage('subject', $template->fetch('campaign:businessAccountActivation:subject'));
							$mailer->setMessage('from', $template->fetch('campaign:businessAccountActivation:from'));
							$mailer->setMessage('html', $template->fetch('campaign:businessAccountActivation:html'));
							$mailer->setMessage('text', $template->fetch('campaign:businessAccountActivation:text'));
							if ($mailer->composeMessage()) {
								$merchantEmail = $member->get('email');
								if ($merchantEmail) {
									$mailer->addRecipient($merchantEmail);
								}
								if ($mailer->send()) {
									addSuccess('A notification email for business account activation has been sent to the member');
								} else {
									addError('There was an error while sending the business account activation notification');
								}
							}
						}
						editMember($member->get('memberID'));
						exit;
					}
				}
			} else {
				addError('There is an existing account registered under the email address');
			}
			$memberBusinessInfo->assertRequired();
			addError('There was an error while updating the member');
			$member->set('email', getPost('email'));
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$groups = getPost('groups');
			assertArray($groups);
			foreach ($groups as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($groups[$key]);
				}
			}
			$controller = new membersController;
			$template = new template;
			$template->assignClean('member', $member->fetchArray());
			$template->assignClean('memberBusinessInfo', $memberBusinessInfo->fetchArray());
			$template->assignClean('sites', $sites);
			$template->assignClean('groups', $groups);
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('stateOptions', formObject::stateOptions());
			$template->assignClean('countryOptions', formObject::countryOptions());
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('payToOptions', memberBusinessInfo::payToOptions());
			$template->assignClean('groupOptions', membersController::groupOptions());
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/memberEdit.htm');
		} else {
			addError('Member does not exist');
			membersAdmin();
		}
	} // function updateMember

?>