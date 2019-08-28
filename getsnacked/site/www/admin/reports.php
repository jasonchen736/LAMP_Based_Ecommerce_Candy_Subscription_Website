<?

	require_once 'admin.php';

	$actions = array(
		'dashboard',
		'generalTraffic',
		'affiliateTraffic',
		'unregisteredTraffic',
		'siteSearches'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		dashboard();
	}

	/**
	 *  Display overview of website sales
	 *  Args: none
	 *  Return: none
	 */
	function dashboard() {
		$startDate = adminCore::get('startDate');
		$endDate = adminCore::get('endDate');
		websiteStatistics::initialize($startDate, $endDate);
		$template = new template;
		$template->assignClean('bestAffiliates', websiteStatistics::getBestAffiliates());
		$template->assignClean('bestCustomers', websiteStatistics::getBestCustomers());
		$template->assignClean('bestProducts', websiteStatistics::getBestProducts());
		$template->assignClean('bestPackages', websiteStatistics::getBestPackages());
		$template->assignClean('startDate', $startDate);
		$template->assignClean('endDate', $endDate);
		$template->getMessages();
		$template->display('admin/dashBoard.htm');
	} // function dashboard

	/**
	 *  Report of general website traffic
	 *  Args: none
	 *  Return: none
	 */
	function generalTraffic() {
		trafficReport('general');
	} // function generalTraffic

	/**
	 *  Report of affiliate website traffic
	 *  Args: none
	 *  Return: none
	 */
	function affiliateTraffic() {
		trafficReport('affiliate');
	} // function affiliateTraffic

	/**
	 *  Report of unregistered website traffic
	 *  Args: none
	 *  Return: none
	 */
	function unregisteredTraffic() {
		trafficReport('unregistered');
	} // function unregisteredTraffic

	/**
	 *  Report of website searches
	 *  Args: none
	 *  Return: none
	 */
	function siteSearches() {
		$controller = new productSearchController;
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
		$template->display('admin/searchReport.htm');	
	} // function siteSearches

	/**
	 *  Report of website traffic
	 *  Args: (str) traffic type
	 *  Return: none
	 */
	function trafficReport($type) {
		switch ($type) {
			case 'affiliate':
				$table = 'affiliateTracking';
				break;
			case 'unregistered':
				$table = 'invalidTracking';
				break;
			case 'general':
			default:
				$table = 'tracking';
				break;
		}
		$cr = new conversionReporter($table);
		list($start, $show, $page) = $cr->getTableLocation();
		$search = $cr->getSearchArray();
		if ($search) {
			$where = ' WHERE '.implode(' AND ', $search);
		} else {
			$where = '';
		}
		$dbh = database::getInstance();
		$template = new template;
		$template->assignClean('type', $table);
		$result = $dbh->query("SELECT * FROM `".$table."`".$where." LIMIT ".$start.", ".$show);
		$template->assignClean('records', $result->fetchAllAssoc());
		$result = $dbh->query("SELECT COUNT(*) AS `count` FROM `".$table."`".$where);
		$row = $result->fetchAssoc();
		$totalRecords = $row['count'];
		$template->assignClean('totalRecords', $totalRecords);
		$template->assignClean('search', $cr->getSearchVars());
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($totalRecords / $show));
		$template->assignClean('querystring', $cr->getQueryString(array('submit', 'nextPage', 'previousPage')));
		$template->display('admin/conversion.htm');
	} // function trafficReport

?>