<?

	require_once 'admin.php';

	$pm = new packageManager;

	switch (getRequest('action')) {
		case 'add':
			$pageTemplate = 'admin/packageEdit.htm';
			$mode = 'add';
			break;
		case 'addPackage':
			if ($pm->addPackage()) {
				$packageID = $pm->getArrayData('record', 'packageID');
				$message = 'Package added (Package ID: '.$packageID.')';
				if (!$pm->uploadImage()) {
					$message .= '.  However, the package image has failed to upload.';
				}
				addSuccess($message);
				if (getRequest('submit') == 'Add and Edit') {
					redirect($_SERVER['PHP_SELF'].'/action/edit/packageID/'.$packageID);
				} else {
					redirect($_SERVER['PHP_SELF'].'/action/add');
				}
			} else {
				$pageTemplate = 'admin/packageEdit.htm';
				$mode = 'add';
			}
			break;
		case 'edit':
			if ($pm->loadID(getRequest('packageID'))) {
				$pageTemplate = 'admin/packageEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/packagesAdmin.htm';
			}
			break;
		case 'update':
			if ($pm->loadID(getPost('packageID'))) {
				if ($pm->updatePackage()) {
					$packageID = $pm->getArrayData('record', 'packageID');
					addSuccess('Package (Package ID: '.$packageID.') updated');
					redirect($_SERVER['PHP_SELF'].'/action/edit/packageID/'.$packageID);
				}
				$pageTemplate = 'admin/packageEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/packagesAdmin.htm';
			}
			break;
		case 'massAction':
			if ($pm->takeAction(getRequest('records'), getRequest('updateAction'))) {
				redirect($_SERVER['PHP_SELF'].$pm->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			} else {
				$pageTemplate = 'admin/packagesAdmin.htm';
			}
			break;
		default:
			$pageTemplate = 'admin/packagesAdmin.htm';
			break;
	}

	switch ($pageTemplate) {
		case 'admin/packagesAdmin.htm':
			list($start, $show, $page) = $pm->getTableLocation();
			list($search, $count) = $pm->getSearch($start, $show);
			$dbh = new database;
			$result = $dbh->query($search);
			$template->assignClean('records', $result->fetchAllAssoc());
			$result = $dbh->query($count);
			$row = $result->fetchAssoc();
			$totalRecords = $row['count'];
			$template->assignClean('totalRecords', $totalRecords);
			$template->assignClean('search', $pm->getSearchVars());
			$template->assignClean('updateActions', $pm->getActions());
			$template->assignClean('updateAction', getRequest('updateAction'));
			$template->assignClean('show', $show);
			$template->assignClean('page', $page);
			$template->assignClean('start', $start);
			$template->assignClean('pages', ceil($totalRecords / $show));
			$template->assignClean('querystring', $pm->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			break;
		case 'admin/packageEdit.htm':
			$template->assignClean('mode', $mode);
			$template->assignClean('package', $pm->get('record'));
			$packageTags = $pm->get('packageTags');
			assertArray($packageTags);
			$template->assignClean('tags', implode("\r\n", $packageTags));
			$template->assignClean('availabilityOptions', $pm->getArrayData('fields', 'availability'));
			break;
		default:
			break;
	}

	$template->assignClean('content', packageManager::getPackageContents($pm->getArrayData('record', 'packageID')));

	$template->getMessages();
	$template->display($pageTemplate);

?>