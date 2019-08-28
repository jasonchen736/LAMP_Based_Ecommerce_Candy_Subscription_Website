<?

	require_once 'admin.php';

	$aum = new adminUserManager;

	switch (getRequest('action')) {
		case 'add':
			if ($aum->addRecord(array('userID'))) {
				$userID = $aum->getArrayData('record', 'userID');
				addSuccess('User added (User ID: '.$userID.')');
				redirect($_SERVER['PHP_SELF']);
			}
			break;
		case 'updatePassword':
			$aum->takeAction(getRequest('records'), 'updatePassword');
			break;
		case 'delete':
			$aum->takeAction(getRequest('records'), 'delete');
			break;
		default:
			break;
	}

	list($start, $show, $page) = $aum->getTableLocation();
	$search = $aum->getSearchArray();
	if ($search) {
		$where = ' WHERE '.implode(' AND ', $search);
	} else {
		$where = '';
	}

	$result = query("SELECT * FROM `adminUser`".$where." LIMIT ".$start.", ".$show);
	$template->assignClean('records', $result->fetchAllAssoc());

	$result = query("SELECT COUNT(*) AS `count` FROM `adminUser`".$where);
	$row = $result->fetchAssoc();
	$totalRecords = $row['count'];
	$template->assignClean('totalRecords', $totalRecords);

	$template->assignClean('search', $aum->getSearchVars());

	$template->assignClean('show', $show);
	$template->assignClean('page', $page);
	$template->assignClean('start', $start);
	$template->assignClean('pages', ceil($totalRecords / $show));
	$template->assignClean('querystring', $aum->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action')));

	$template->getMessages();
	$template->display('admin/adminUsersAdmin.htm');

?>