<?

	require_once 'admin.php';

	$actions = array(
		'contentAdmin',
		'addContent',
		'saveContent',
		'editContent',
		'updateContent'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		contentAdmin();
	}

	/**
	 *  Show the content admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function contentAdmin() {
		$controller = new contentController;
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
		$template->getMessages();
		$template->display('admin/contentAdmin.htm');	
	} // function contentAdmin

	/**
	 *  Add content section
	 *  Args: none
	 *  Return: none
	 */
	function addContent() {
		$content = new content;
		$content->set('site', systemSettings::get('SITENAME'));
		$template = new template;
		$template->assignClean('content', $content->fetchArray());
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/contentEdit.htm');
	} // function addContent

	/**
	 *  Save a new content record
	 *  Args: none
	 *  Return: none
	 */
	function saveContent() {
		$content = new content;
		$content->set('name', getPost('name'));
		$content->set('content', getPost('content'));
		if ($content->save()) {
			addSuccess('Content page '.$content->get('name').' saved successfully');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			if (!empty($sites)) {
				if (!$content->addSites($sites)) {
					addError('There was an error while processing the content websites');
				}
			} else {
				addSuccess('There are no sites associated with the content');
			}
			if (haveErrors() || getRequest('submit') == 'Add and Edit') {
				editContent($content->get('contentID'));
			} else {
				addContent();
			}
		} else {
			addError('There was an error while saving the content page');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$template = new template;
			$template->assignClean('content', $content->fetchArray());
			$template->assignClean('sites', $sites);
			$template->assignClean('mode', 'add');
			$template->getMessages();
			$template->display('admin/contentEdit.htm');
		}
	} // function saveContent

	/**
	 *  Edit content section
	 *  Args: (int) content id
	 *  Return: none
	 */
	function editContent($contentID = false) {
		if (!$contentID) {
			$contentID = getRequest('contentID', 'integer');
		}
		$content = new content($contentID);
		if ($content->exists()) {
			$template = new template;
			$template->assignClean('content', $content->fetchArray());
			$template->assignClean('sites', $content->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/contentEdit.htm');
		} else {
			addError('Content page does not exist');
			contentAdmin();
		}
	} // function editContent

	/**
	 *  Update an existing content record
	 *  Args: none
	 *  Return: none
	 */
	function updateContent() {
		$content = new content(getRequest('contentID', 'integer'));
		if ($content->exists()) {
			$content->set('name', getPost('name'));
			$content->set('content', getPost('content'));
			if ($content->update()) {
				addSuccess('Content page '.$content->get('name').' updated successfully');
				$sites = getPost('sites');
				assertArray($sites);
				foreach ($sites as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($sites[$key]);
					}
				}
				$existingSites = $content->getObjectSites();
				$addSites = array_diff($sites, $existingSites);
				if (!empty($addSites)) {
					if ($content->addSites($addSites)) {
						addSuccess('Websites have been added successfully');
					} else {
						addError('There was an error while adding content websites');
					}
				}
				$removeSites = array_diff($existingSites, $sites);
				if (!empty($removeSites)) {
					if ($content->removeSites($removeSites)) {
						addSuccess('Websites removed successfully');
					} else {
						addError('There was an error while removing content websites');
					}
				}
				editContent($content->get('contentID'));
			} else {
				addError('There was an error while updating the content page');
				$template = new template;
				$template->assignClean('content', $content->fetchArray());
				$template->assignClean('mode', 'edit');
				$template->getMessages();
				$template->display('admin/contentEdit.htm');
			}
		} else {
			addError('Content page does not exist');
			contentAdmin();
		}
	} // function updateContent

?>