<?

	require_once 'admin.php';

	$actions = array(
		'shippingContainersAdmin',
		'addShippingContainer',
		'saveShippingContainer',
		'editShippingContainer',
		'updateShippingContainer',
		'quickUpdate'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		shippingContainersAdmin();
	}

	/**
	 *  Show the shipping containers admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function shippingContainersAdmin() {
		$controller = new shippingContainersController;
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
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', shippingContainersController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/shippingContainersAdmin.htm');
	} // function shippingContainersAdmin

	/**
	 *  Add shipping option section
	 *  Args: none
	 *  Return: none
	 */
	function addShippingContainer() {
		$shippingContainer = new shippingContainer;
		$shippingContainer->set('site', systemSettings::get('SITENAME'));
		$controller = new shippingContainersController;
		$template = new template;
		$template->assignClean('shippingContainer', $shippingContainer->fetchArray());
		$template->assignClean('statusOptions', $controller->getOptions('status'));
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('mode', 'add');
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->getMessages();
		$template->display('admin/shippingContainerEdit.htm');
	} // function addShippingContainer

	/**
	 *  Save a new shipping option record
	 *  Args: none
	 *  Return: none
	 */
	function saveShippingContainer() {
		$shippingContainer = new shippingContainer;
		$shippingContainer->set('name', getPost('name'));
		$shippingContainer->set('length', getPost('length'));
		$shippingContainer->set('width', getPost('width'));
		$shippingContainer->set('height', getPost('height'));
		$shippingContainer->set('maxWeight', getPost('maxWeight'));
		$shippingContainer->set('status', getPost('status'));
		if ($shippingContainer->save()) {
			addSuccess('Shipping container '.$shippingContainer->get('name').' saved successfully');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			if (!empty($sites)) {
				if (!$shippingContainer->addSites($sites)) {
					addError('There was an error while processing the shipping container websites');
				}
			} else {
				addSuccess('There are no sites associated with the shipping container');
			}
			if (haveErrors() || getRequest('submit') == 'Add and Edit') {
				editShippingContainer($shippingContainer->get('shippingContainerID'));
			} else {
				addShippingContainer();
			}
		} else {
			addError('There was an error while saving the shipping container');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$controller = new shippingContainersController;
			$template = new template;
			$template->assignClean('shippingContainer', $shippingContainer->fetchArray());
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('sites', $sites);
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('mode', 'add');
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->getMessages();
			$template->display('admin/shippingContainerEdit.htm');
		}
	} // function saveShippingContainer

	/**
	 *  Edit shipping option section
	 *  Args: (int) shipping option id
	 *  Return: none
	 */
	function editShippingContainer($shippingContainerID = false) {
		if (!$shippingContainerID) {
			$shippingContainerID = getRequest('shippingContainerID', 'integer');
		}
		$shippingContainer = new shippingContainer($shippingContainerID);
		if ($shippingContainer->exists()) {
			$controller = new shippingContainersController;
			$template = new template;
			$template->assignClean('shippingContainer', $shippingContainer->fetchArray());
			$template->assignClean('statusOptions', $controller->getOptions('status'));
			$template->assignClean('sites', $shippingContainer->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('mode', 'edit');
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->getMessages();
			$template->display('admin/shippingContainerEdit.htm');
		} else {
			addError('Shipping container does not exist');
			shippingContainersAdmin();
		}
	} // function editShippingContainer

	/**
	 *  Update an existing shipping option record
	 *  Args: none
	 *  Return: none
	 */
	function updateShippingContainer() {
		$shippingContainer = new shippingContainer(getRequest('shippingContainerID', 'integer'));
		if ($shippingContainer->exists()) {
			$shippingContainer->set('name', getPost('name'));
			$shippingContainer->set('length', getPost('length'));
			$shippingContainer->set('width', getPost('width'));
			$shippingContainer->set('height', getPost('height'));
			$shippingContainer->set('maxWeight', getPost('maxWeight'));
			$shippingContainer->set('status', getPost('status'));
			if ($shippingContainer->update()) {
				addSuccess('Shipping container '.$shippingContainer->get('name').' updated successfully');
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				$existingSites = $shippingContainer->getObjectSites();
				$addSites = array_diff($sites, $existingSites);
				if (!empty($addSites)) {
					if ($shippingContainer->addSites($addSites)) {
						addSuccess('Websites have been added successfully');
					} else {
						addError('There was an error while adding shipping container websites');
					}
				}
				$removeSites = array_diff($existingSites, $sites);
				if (!empty($removeSites)) {
					if ($shippingContainer->removeSites($removeSites)) {
						addSuccess('Websites removed successfully');
					} else {
						addError('There was an error while removing shipping container websites');
					}
				}
				$existingSites = $shippingContainer->getObjectSites();
				if (empty($existingSites)) {
					addSuccess('There are no sites associated with the shipping container');
				}
				editShippingContainer($shippingContainer->get('shippingContainerID'));
			} else {
				addError('There was an error while updating the shipping container');
				$controller = new shippingContainersController;
				$template = new template;
				$template->assignClean('shippingContainer', $shippingContainer->fetchArray());
				$template->assignClean('statusOptions', $controller->getOptions('status'));
				$template->assignClean('mode', 'edit');
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->getMessages();
				$template->display('admin/shippingContainerEdit.htm');
			}
		} else {
			addError('Shipping container does not exist');
			shippingContainersAdmin();
		}
	} // function updateShippingContainer

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
						$shippingContainer = new shippingContainer($id);
						if ($shippingContainer->exists()) {
							if ($shippingContainer->get('status') != 'active') {
								$shippingContainer->set('status', 'active');
								if ($shippingContainer->update()) {
									addSuccess('Shipping container '.$id.' ('.$shippingContainer->get('name').') has been activated');
								} else {
									addError('Unable to activate shipping container '.$id.' ('.$shippingContainer->get('name').')');
								}
							} else {
								addError('Shipping container '.$id.' ('.$shippingContainer->get('name').') is already active');
							}
						} else {
							addError('Shipping container ID '.$id.' is not a valid record');
						}
					}
					break;
				case 'Deactivate':
					foreach ($records as $id) {
						$shippingContainer = new shippingContainer($id);
						if ($shippingContainer->exists()) {
							if ($shippingContainer->get('status') != 'inactive') {
								$shippingContainer->set('status', 'inactive');
								if ($shippingContainer->update()) {
									addSuccess('Shipping container '.$id.' ('.$shippingContainer->get('name').') has been deactivated');
								} else {
									addError('Unable to deactivate shipping container '.$id.' ('.$shippingContainer->get('name').')');
								}
							} else {
								addError('Shipping container '.$id.' ('.$shippingContainer->get('name').') is already inactive');
							}
						} else {
							addError('Shipping container ID '.$id.' is not a valid record');
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
		shippingContainersAdmin();
	} // function quickUpdate

?>