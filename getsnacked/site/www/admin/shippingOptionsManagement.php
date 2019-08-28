<?

	require_once 'admin.php';

	$actions = array(
		'shippingOptionsAdmin',
		'addShippingOption',
		'saveShippingOption',
		'editShippingOption',
		'updateShippingOption',
		'quickUpdate',
		'getHelp'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		shippingOptionsAdmin();
	}

	/**
	 *  Show the shipping options admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function shippingOptionsAdmin() {
		$controller = new shippingOptionsController;
		$controller->setDefaultSearch('status', 'active');
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$updateOption = getRequest('updateOption', 'alphanum');
		$template = new template;
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('statusOptions', $controller->getOptions('status'));
		$template->assignClean('rateTypeOptions', $controller->getOptions('rateType'));
		$template->assignClean('ruleOptions', $controller->getOptions('rule'));
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', shippingOptionsController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/shippingOptionsAdmin.htm');
	} // function shippingOptionsAdmin

	/**
	 *  Add shipping option section
	 *  Args: none
	 *  Return: none
	 */
	function addShippingOption() {
		$shippingOption = new shippingOption;
		$shippingOption->set('site', systemSettings::get('SITENAME'));
		$shippingOptionImposed = new shippingOptionImposed();
		$controller = new shippingOptionsController;
		$template = new template;
		$template->assignClean('shippingOption', $shippingOption->fetchArray());
		$template->assignClean('shippingOptionImposed', $shippingOptionImposed->fetchArray());
		$template->assignClean('statusOptions', $controller->getOptions('status'));
		$template->assignClean('rateTypeOptions', $controller->getOptions('rateType'));
		$template->assignClean('ruleOptions', $controller->getOptions('rule'));
		$template->assignClean('countryOptions', formObject::countryOptions());
		$template->assignClean('rules', array());
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('imposedSites', $shippingOptionImposed->getObjectSites());
		$template->assignClean('imposedOnOptions', shippingOptionImposed::getOptions());
		$template->assignClean('mode', 'add');
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->getMessages();
		$template->display('admin/shippingOptionEdit.htm');
	} // function addShippingOption

	/**
	 *  Save a new shipping option record
	 *  Args: none
	 *  Return: none
	 */
	function saveShippingOption() {
		$shippingOption = new shippingOption;
		$shippingOption->set('name', getPost('name'));
		$shippingOption->set('rate', getPost('rate'));
		$shippingOption->set('rateType', getPost('rateType'));
		$shippingOption->set('provider', getPost('provider'));
		$shippingOption->set('externalValue', getPost('externalValue'));
		$shippingOption->set('modifier', getPost('modifier'));
		$shippingOption->set('modifierType', getPost('modifierType'));
		$shippingOption->set('status', getPost('status'));
		$shippingOption->set('rule', getPost('rule'));
		$shippingOptionImposed = new shippingOptionImposed();
		$shippingOptionImposed->set('imposedOn', getRequest('imposedOn', 'alphanum'));
		if ($shippingOption->save()) {
			addSuccess('Shipping option '.$shippingOption->get('name').' saved successfully');
			$shippingOptionID = $shippingOption->get('shippingOptionID');
			$rules = getRulesFromRequest();
			$optionRules = shippingOptionsController::compareRules($shippingOptionID, $rules);
			if (!empty($optionRules)) {
				$assertResults = shippingOptionsController::assertRules($shippingOptionID, $optionRules);
				foreach ($assertResults as $key => $val) {
					$action = $val['action'];
					$success = $val['success'];
					unset($val['action']);
					unset($val['success']);
					$notification = '';
					foreach ($val as $field => $value) {
						$notification .= '['.$field.'] '.$value.', ';
					}
					$notification = substr($notification, 0, -2);
					if ($success) {
						addSuccess('Rule '.$action.' success: '.$notification);
					} else {
						addError('Rule '.$action.' fail: '.$notification);
					}
				}
			}
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			if (!empty($sites)) {
				if (!$shippingOption->addSites($sites)) {
					addError('There was an error while processing the shipping option websites');
				}
			} else {
				addSuccess('There are no sites associated with the shipping option');
			}
			$shippingOptionImposed->set('shippingOptionID', $shippingOptionID);
			if (!$shippingOptionImposed->save()) {
				addError('There was an error while setting the forced option rules');
			} else {
				$imposedSites = getPost('imposedSites');
				assertArray($imposedSites);
				foreach ($imposedSites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($imposedSites[$key]);
					}
				}
				if (!empty($imposedSites)) {
					if (!$shippingOptionImposed->addSites($imposedSites)) {
						addError('There was an error while processing the forced option websites');
					}
				}
			}
			if (haveErrors() || getRequest('submit') == 'Add and Edit') {
				editShippingOption($shippingOption->get('shippingOptionID'));
			} else {
				addShippingOption();
			}
		} else {
			addError('There was an error while saving the shipping option');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$controller = new shippingOptionsController;
			$template = new template;
			$template->assignClean('shippingOption', $shippingOption->fetchArray());
			$template->assignClean('shippingOptionImposed', $shippingOptionImposed->fetchArray());
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('rateTypeOptions', $controller->getOptions('rateType'));
			$template->assignClean('ruleOptions', $controller->getOptions('rule'));
			$template->assignClean('countryOptions', formObject::countryOptions());
			$template->assignClean('rules', $rules);
			$template->assignClean('sites', $sites);
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('imposedSites', $shippingOptionImposed->getObjectSites());
			$template->assignClean('imposedOnOptions', shippingOptionImposed::getOptions());
			$template->assignClean('mode', 'add');
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->getMessages();
			$template->display('admin/shippingOptionEdit.htm');
		}
	} // function saveShippingOption

	/**
	 *  Edit shipping option section
	 *  Args: (int) shipping option id
	 *  Return: none
	 */
	function editShippingOption($shippingOptionID = false) {
		if (!$shippingOptionID) {
			$shippingOptionID = getRequest('shippingOptionID', 'integer');
		}
		$shippingOption = new shippingOption($shippingOptionID);
		if ($shippingOption->exists()) {
			$shippingOptionImposed = shippingOptionImposed::retrieveShippingOptionImposed($shippingOption->get('shippingOptionID'));
			$controller = new shippingOptionsController;
			$template = new template;
			$template->assignClean('shippingOption', $shippingOption->fetchArray());
			$template->assignClean('shippingOptionImposed', $shippingOptionImposed->fetchArray());
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('rateTypeOptions', $controller->getOptions('rateType'));
			$template->assignClean('ruleOptions', $controller->getOptions('rule'));
			$template->assignClean('countryOptions', formObject::countryOptions());
			$template->assignClean('rules', $shippingOption->getShippingOptionRules());
			$template->assignClean('sites', $shippingOption->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('imposedSites', $shippingOptionImposed->getObjectSites());
			$template->assignClean('imposedOnOptions', shippingOptionImposed::getOptions());
			$template->assignClean('mode', 'edit');
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->getMessages();
			$template->display('admin/shippingOptionEdit.htm');
		} else {
			addError('Shipping option does not exist');
			shippingOptionsAdmin();
		}
	} // function editShippingOption

	/**
	 *  Update an existing shipping option record
	 *  Args: none
	 *  Return: none
	 */
	function updateShippingOption() {
		$shippingOption = new shippingOption(getRequest('shippingOptionID', 'integer'));
		if ($shippingOption->exists()) {
			$shippingOption->set('name', getPost('name'));
			$shippingOption->set('rate', getPost('rate'));
			$shippingOption->set('rateType', getPost('rateType'));
			$shippingOption->set('provider', getPost('provider'));
			$shippingOption->set('externalValue', getPost('externalValue'));
			$shippingOption->set('modifier', getPost('modifier'));
			$shippingOption->set('modifierType', getPost('modifierType'));
			$shippingOption->set('status', getPost('status'));
			$shippingOption->set('rule', getPost('rule'));
			$shippingOptionID = $shippingOption->get('shippingOptionID');
			$shippingOptionImposed = shippingOptionImposed::retrieveShippingOptionImposed($shippingOptionID);
			$shippingOptionImposed->set('imposedOn', getRequest('imposedOn', 'alphanum'));
			$rules = getRulesFromRequest();
			$ruleDiffs = shippingOptionsController::compareRules($shippingOptionID, $rules);
			if (($updated = $shippingOption->update()) || !empty($ruleDiffs)) {
				if ($updated) {
					addSuccess('Shipping option '.$shippingOption->get('name').' updated successfully');
				} else {
					addError('Shipping option details were not updated');
				}
				if (!empty($ruleDiffs)) {
					$assertResults = shippingOptionsController::assertRules($shippingOptionID, $ruleDiffs);
					foreach ($assertResults as $key => $val) {
						$action = $val['action'];
						$success = $val['success'];
						unset($val['action']);
						unset($val['success']);
						$notification = '';
						foreach ($val as $field => $value) {
							$notification .= '['.$field.'] '.$value.', ';
						}
						$notification = substr($notification, 0, -2);
						if ($success) {
							addSuccess('Rule '.$action.' success: '.$notification);
						} else {
							addError('Rule '.$action.' fail: '.$notification);
						}
					}
				}
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				$existingSites = $shippingOption->getObjectSites();
				$addSites = array_diff($sites, $existingSites);
				if (!empty($addSites)) {
					if ($shippingOption->addSites($addSites)) {
						addSuccess('Websites have been added successfully');
					} else {
						addError('There was an error while adding shipping option websites');
					}
				}
				$removeSites = array_diff($existingSites, $sites);
				if (!empty($removeSites)) {
					if ($shippingOption->removeSites($removeSites)) {
						addSuccess('Websites removed successfully');
					} else {
						addError('There was an error while removing shipping option websites');
					}
				}
				$existingSites = $shippingOption->getObjectSites();
				if (empty($existingSites)) {
					addSuccess('There are no sites associated with the shipping option');
				}
				if ($shippingOptionImposed->exists()) {
					if (!$shippingOptionImposed->update()) {
						addError('There was an error while updating the forced option rule');
					}
					$imposedSites = getPost('imposedSites');
					assertArray($imposedSites);
					foreach ($imposedSites as $key => $val) {
						if (!validNumber($val, 'integer')) {
							unset($imposedSites[$key]);
						}
					}
					$existingSites = $shippingOptionImposed->getObjectSites();
					$addSites = array_diff($imposedSites, $existingSites);
					if (!empty($addSites)) {
						if ($shippingOptionImposed->addSites($addSites)) {
							addSuccess('Forced option websites added successfully');
						} else {
							addError('There was an error while adding forced option websites');
						}
					}
					$removeSites = array_diff($existingSites, $imposedSites);
					if (!empty($removeSites)) {
						if ($shippingOptionImposed->removeSites($removeSites)) {
							addSuccess('Forced option websites removed successfully');
						} else {
							addError('There was an error while removing forced option websites');
						}
					}
				} else {
					$shippingOptionImposed->set('shippingOptionID', $shippingOptionID);
					if (!$shippingOptionImposed->save()) {
						addError('There was an error while setting the forced option rules');
					} else {
						$imposedSites = getPost('imposedSites');
						assertArray($imposedSites);
						foreach ($imposedSites as $key => $val) {
							if (!validNumber($val, 'integer')) {
								unset($imposedSites[$key]);
							}
						}
						if (!empty($imposedSites)) {
							if (!$shippingOptionImposed->addSites($imposedSites)) {
								addError('There was an error while processing the forced option websites');
							}
						} else {
							addSuccess('There are no sites associated with the forced option rule');
						}
					}
				}
				editShippingOption($shippingOptionID);
			} else {
				addError('There was an error while updating the shipping option');
				$controller = new shippingOptionsController;
				$template = new template;
				$template->assignClean('shippingOption', $shippingOption->fetchArray());
				$template->assignClean('shippingOptionImposed', $shippingOptionImposed->fetchArray());
				$template->assignClean('statusOptions', $controller->getOptions('status'));
				$template->assignClean('rateTypeOptions', $controller->getOptions('rateType'));
				$template->assignClean('ruleOptions', $controller->getOptions('rule'));
				$template->assignClean('countryOptions', formObject::countryOptions());
				$template->assignClean('rules', $shippingOption->getShippingOptionRules());
				$template->assignClean('imposedSites', $shippingOptionImposed->getObjectSites());
				$template->assignClean('imposedOnOptions', shippingOptionImposed::getOptions());
				$template->assignClean('mode', 'edit');
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->getMessages();
				$template->display('admin/shippingOptionEdit.htm');
			}
		} else {
			addError('Shipping option does not exist');
			shippingOptionsAdmin();
		}
	} // function updateShippingOption

	/**
	 *  Perform specific update on multiple records
	 *  Args: none
	 *  Return: none
	 */
	function quickUpdate() {
		$records = getRequest('records');
		foreach ($records as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($records[$key]);
				addError('One or more record IDs was invalid');
			}
		}
		if ($records) {
			$action = getRequest('updateOption');
			switch ($action) {
				case 'Activate':
					foreach ($records as $id) {
						$shippingOption = new shippingOption($id);
						if ($shippingOption->exists()) {
							if ($shippingOption->get('status') != 'active') {
								$shippingOption->set('status', 'active');
								if ($shippingOption->update()) {
									addSuccess('Shipping option '.$id.' ('.$shippingOption->get('name').') has been activated');
								} else {
									addError('Unable to activate shipping option '.$id.' ('.$shippingOption->get('name').')');
								}
							} else {
								addError('Shipping option '.$id.' ('.$shippingOption->get('name').') is already active');
							}
						} else {
							addError('Shipping option ID '.$id.' is not a valid record');
						}
					}
					break;
				case 'Deactivate':
					foreach ($records as $id) {
						$shippingOption = new shippingOption($id);
						if ($shippingOption->exists()) {
							if ($shippingOption->get('status') != 'inactive') {
								$shippingOption->set('status', 'inactive');
								if ($shippingOption->update()) {
									addSuccess('Shipping option '.$id.' ('.$shippingOption->get('name').') has been deactivated');
								} else {
									addError('Unable to deactivate shipping option '.$id.' ('.$shippingOption->get('name').')');
								}
							} else {
								addError('Shipping option '.$id.' ('.$shippingOption->get('name').') is already inactive');
							}
						} else {
							addError('Shipping option ID '.$id.' is not a valid record');
						}
					}
					break;
				default:
					addError('Invalid action');
					break;
			}
		} else {
			addError('There are no records to update');
		}
		shippingOptionsAdmin();
	} // function quickUpdate

	/**
	 *  Retrieve help tip
	 *  Args: none
	 *  Return: none
	 */
	function getHelp() {
		$item = getRequest('item');
		switch ($item) {
			case 'rules':
				$page = 'admin/shippingOptionRulesHelp.htm';
				break;
			case 'ratesandproviders':
				echo '<p>';
				echo 'For each shipping option, you may either set a custom rate or use a provider.';
				echo '<br /><br />';
				echo 'A custom rate will apply only when there is no set provider. This rate may be calculated by a flat rate, by the number of packages or by weight.';
				echo '<br /><br />';
				echo 'When a provider is set, shipping costs are caculated by the provider. The value that the shipping provider uses to reference their shipping method must be entered in the provider value field.';
				echo '<br /><br />';
				echo 'A provider\'s rate may be modified by setting the provider rate modifier. This allows a provider generated rate to be either marked up or marked down by a flat rate or percent.';
				echo '</p>';
				exit;
				break;
			default:
				echo '<p><em>Help not found</em></p>';
				exit;
				break;
		}
		$template = new Template;
		$template->display($page);
	} // function getHelp

	/**
	 *  Retrieve and organize shipping option rules from request
	 *  Args: none
	 *  Return: (array) shipping option rules
	 */
	function getRulesFromRequest() {
		$cityRules = getRequest('ruleCity');
		$stateRules = getRequest('ruleState');
		$postalRules = getRequest('rulePostal');
		$countryRules = getRequest('ruleCountry');
		$weightConditions = getRequest('ruleWeightCondition');
		$weightValues = getRequest('ruleWeightValue');
		$packageCondtions = getRequest('rulePackageCondition');
		$packageValues = getRequest('rulePackageValue');
		$costConditions = getRequest('ruleCostCondition');
		$costValues = getRequest('ruleCostValue');
		$rules = array();
		assertArray($cityRules);
		foreach ($cityRules as $key => $val) {
			$rules[$key]['city'] = clean($val, 'alphanum');
			$rules[$key]['state'] = isset($stateRules[$key]) ? clean($stateRules[$key], 'alphanum') : '';
			$rules[$key]['postal'] = isset($postalRules[$key]) ? clean($postalRules[$key], 'alphanum') : '';
			$rules[$key]['country'] = isset($countryRules[$key]) ? clean($countryRules[$key], 'alphanum') : '';
			$rules[$key]['weightCondition'] = isset($weightConditions[$key]) ? clean($weightConditions[$key], 'alpha') : '';
			$rules[$key]['weightValue'] = isset($weightValues[$key]) ? clean($weightValues[$key], 'double') : '';
			$rules[$key]['packageCondition'] = isset($packageCondtions[$key]) ? clean($packageCondtions[$key], 'alpha') : '';
			$rules[$key]['packageValue'] = isset($packageValues[$key]) ? clean($packageValues[$key], 'integer') : '';
			$rules[$key]['costCondition'] = isset($costConditions[$key]) ? clean($costConditions[$key], 'alpha') : '';
			$rules[$key]['costValue'] = isset($costValues[$key]) ? clean($costValues[$key], 'double') : '';
		}
		return $rules;
	} // function getRulesFromRequest

?>