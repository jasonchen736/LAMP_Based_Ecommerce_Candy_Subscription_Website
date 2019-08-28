<?

	require_once 'admin.php';

	$actions = array(
		'siteRegistryAdmin',
		'autoRegisterPrompt',
		'autoRegister',
//		'registerSite',
//		'saveRegistry',
//		'editRegistry',
//		'updateRegistry',
//		'getHelp'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		siteRegistryAdmin();
	}

	/**
	 *  Prompt user to register new site with current settings
	 *  Args: (boolean) display registration button
	 *  Return: none
	 */
	function autoRegisterPrompt($displayButton = true) {
		$template = new template;
		$template->assignClean('displayButton', $displayButton);
		$template->getMessages();
		$template->display('admin/autoRegisterPrompt.htm');
	} // function autoRegisterPrompt

	/**
	 *  Register new site with current settings
	 *  Args: none
	 *  Return: none
	 */
	function autoRegister() {
		if (!adminCore::checkSiteRegistry()) {
			$siteName = systemSettings::get('SITENAME');
			$siteID = systemSettings::get('SITEID');
			$registry = new siteRegistry;
			$registry->set('siteName', $siteName);
			if ($siteName == $registry->get('siteName')) {
				if ($registry->save()) {
					// write site id to config
					if (systemSettings::writeSiteID()) {
						addSuccess('Site '.$siteName.' registered successfully');
						$admin = adminCore::get('admin');
						if (isset($admin['loginPage']) && $admin['loginPage']) {
							redirect($admin['loginPage']);
						} else {
							redirect('/admin');
						}
					} else {
						addError('Failed to write Site ID to configuration file');
						addError('Please check with your systems administrator for proper registration of your site');
					}
				} else {
					addError('There was an error while saving the registry for '.$siteName);
					addError('Please check with your systems administrator for proper registration of your site');
				}
			} else {
				addError('Site name contains invalid characters');
			}
			autoRegisterPrompt(false);
		}
	} // function autoRegister

	/**
	 *  Show the shipping options admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function siteRegistryAdmin() {
		$controller = new siteRegistryController;
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
		$template->display('admin/siteRegistryAdmin.htm');
	} // function siteRegistryAdmin

?>