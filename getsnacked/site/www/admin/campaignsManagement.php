<?

	require_once 'admin.php';

	$actions = array(
		'campaignsAdmin',
		'addCampaign',
		'saveCampaign',
		'editCampaign',
		'updateCampaign',
		'quickUpdate'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		campaignsAdmin();
	}

	/**
	 *  Show the campaigns admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function campaignsAdmin() {
		$controller = new campaignsController;
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
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', campaignsController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/campaignsAdmin.htm');	
	} // function campaignsAdmin

	/**
	 *  Add campaign section
	 *  Args: none
	 *  Return: none
	 */
	function addCampaign() {
		$campaign = new campaign;
		$controller = new campaignsController;
		$template = new template;
		$template->assignClean('campaign', $campaign->fetchArray());
		$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
		$template->assignClean('typeOptions', $controller->getOptions('type'));
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/campaignEdit.htm');
	} // function addCampaign

	/**
	 *  Save a new campaign record
	 *  Args: none
	 *  Return: none
	 */
	function saveCampaign() {
		$campaign = new campaign;
		$campaign->set('type', getPost('type'));
		$campaign->set('name', getPost('name'));
		$campaign->set('availability', getPost('availability'));
		$campaign->set('subject', getPost('subject'));
		$campaign->set('html', getPost('html'));
		$campaign->set('text', getPost('text'));
		$campaign->set('fromEmail', getPost('fromEmail'));
		$campaign->set('linkedCampaign', getPost('linkedCampaign'));
		$campaign->set('sendInterval', getPost('sendInterval'));
		if ($campaign->save()) {
			addSuccess('Campaign '.$campaign->get('name').' added successfully');
			$campaignID = $campaign->get('campaignID');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			if (!empty($sites)) {
				if (!$campaign->addSites($sites)) {
					addError('There was an error while processing the email/campaign websites');
				}
			} else {
				addSuccess('There are no sites associated with the email/campaign');
			}
			if (haveErrors() || getRequest('submit') == 'Add and Edit') {
				editCampaign($campaignID);
			} else {
				addCampaign();
			}
		} else {
			addError('Save failed');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$controller = new campaignsController;
			$template = new template;
			$template->assignClean('campaign', $campaign->fetchArray());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('typeOptions', $controller->getOptions('type'));
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('sites', array(systemSettings::get('SITEID')));
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('mode', 'add');
			$template->getMessages();
			$template->display('admin/campaignEdit.htm');
		}
	} // function saveCampaign

	/**
	 *  Edit campaign section
	 *  Args: (int) campaign id
	 *  Return: none
	 */
	function editCampaign($campaignID = false) {
		if (!$campaignID) {
			$campaignID = getRequest('campaignID', 'integer');
		}
		$campaign = new campaign($campaignID);
		if ($campaign->exists()) {
			$controller = new campaignsController;
			$template = new template;
			$template->assignClean('campaign', $campaign->fetchArray());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('typeOptions', $controller->getOptions('type'));
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('sites', $campaign->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/campaignEdit.htm');
		} else {
			addError('The email/campaign was not found');
			campaignsAdmin();
		}
	} // function editCampaign

	/**
	 *  Update an existing campaign record
	 *  Args: none
	 *  Return: none
	 */
	function updateCampaign() {
		$campaign = new campaign(getRequest('campaignID', 'integer'));
		if ($campaign->exists()) {
			$campaignID = $campaign->get('campaignID');
			$campaign->set('type', getPost('type'));
			$campaign->set('name', getPost('name'));
			$campaign->set('availability', getPost('availability'));
			$campaign->set('subject', getPost('subject'));
			$campaign->set('html', getPost('html'));
			$campaign->set('text', getPost('text'));
			$campaign->set('fromEmail', getPost('fromEmail'));
			$campaign->set('linkedCampaign', getPost('linkedCampaign'));
			$campaign->set('sendInterval', getPost('sendInterval'));
			if ($campaign->update()) {
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				$existingSites = $campaign->getObjectSites();
				$addSites = array_diff($sites, $existingSites);
				if (!empty($addSites)) {
					if ($campaign->addSites($addSites)) {
						addSuccess('Websites have been added successfully');
					} else {
						addError('There was an error while adding email/campaign websites');
					}
				}
				$removeSites = array_diff($existingSites, $sites);
				if (!empty($removeSites)) {
					if ($campaign->removeSites($removeSites)) {
						addSuccess('Websites removed successfully');
					} else {
						addError('There was an error while removing email/campaign websites');
					}
				}
			} else {
				addError('There was an error while updating the email/campaign details');
			}
			if (!haveErrors()) {
				addSuccess($campaign->get('name').' was updated successfully');
				$campaignSites = $campaign->getObjectSites();
				if (empty($campaignSites)) {
					addSuccess('There are no sites associated with the email/campaign');
				}
				editCampaign($campaignID);
			} else {
				addError('Save failed');
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				$controller = new campaignsController;
				$template = new template;
				$template->assignClean('campaign', $campaign->fetchArray());
				$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
				$template->assignClean('typeOptions', $controller->getOptions('type'));
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->assignClean('sites', array(systemSettings::get('SITEID')));
				$template->assignClean('siteOptions', siteRegistryController::getSites());
				$template->assignClean('mode', 'edit');
				$template->getMessages();
				$template->display('admin/campaignEdit.htm');
			}
		} else {
			addError('Email/campaign does not exist');
			campaignsAdmin();
		}
	} // function updateCampaign

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
				case 'admin':
				case 'customer':
				case 'affiliate':
				case 'all':
				case 'none':
				case 'exclusive':
					foreach ($records as $campaignID) {
						$campaign = new campaign($campaignID);
						$campaign->set('availability', $action);
						if ($campaign->update()) {
							addSuccess('Campaign '.$campaign->get('name').' updated');
						} else {
							addError('There was an error while updating campaign '.$campaign->get('name'));
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
		campaignsAdmin();
	} // function quickUpdate

?>