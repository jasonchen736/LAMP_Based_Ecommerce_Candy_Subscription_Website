<?

	require_once 'merchant.php';

	$actions = array(
		'home'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		home();
	}

	/**
	 *  Display the merchant homepage
	 *  Args: none
	 *  Return: none
	 */
	function home() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$controller = new subOrdersController;
		$controller->imposeSearch('memberID', $merchantInfo['id']);
		$controller->setDefaultSearch('status', 'new');
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$updateOption = getRequest('updateOption', 'alphanum');
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', subOrdersController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('merchant/subOrdersAdmin.htm');
	} // function home

?>