<?

	require_once 'admin.php';

	$cm = new campaignsManager;

	switch (getRequest('action')) {
		case 'add':
			$pageTemplate = 'admin/campaignEdit.htm';
			$mode = 'add';
			break;
		case 'addCampaign':
			if ($cm->addRecord(array('campaignID'))) {
				$campaignID = $cm->getArrayData('record', 'campaignID');
				addSuccess('Campaign added (Campaign ID: '.$campaignID.')');
				if (getRequest('submit') == 'Add and Edit') {
					redirect($_SERVER['PHP_SELF'].'/action/edit/campaignID/'.$campaignID);
				} else {
					redirect($_SERVER['PHP_SELF'].'/action/add');
				}
			} else {
				$pageTemplate = 'admin/campaignEdit.htm';
				$mode = 'add';
			}
			break;
		case 'edit':
			if ($cm->loadID(getRequest('campaignID'))) {
				$pageTemplate = 'admin/campaignEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/campaignsAdmin.htm';
			}
			break;
		case 'update':
			if ($cm->loadID(getPost('campaignID'))) {
				if ($cm->updateCampaign()) {
					$campaignID = $cm->getArrayData('record', 'campaignID');
					addSuccess('Campaign (Campaign ID: '.$campaignID.') updated');
					redirect($_SERVER['PHP_SELF'].'/action/edit/campaignID/'.$campaignID);
				}
				$pageTemplate = 'admin/campaignEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/campaignsAdmin.htm';
			}
			break;
		case 'massAction':
			$cm->takeAction(getRequest('records'), getRequest('updateAction'));
		default:
			$pageTemplate = 'admin/campaignsAdmin.htm';
			break;
	}

	switch ($pageTemplate) {
		case 'admin/campaignsAdmin.htm':
			list($start, $show, $page) = $cm->getTableLocation();
			$search = $cm->getSearchArray();
			if ($search) {
				$where = ' WHERE '.implode(' AND ', $search);
			} else {
				$where = '';
			}

			$result = query("SELECT * FROM `campaigns`".$where." LIMIT ".$start.", ".$show);
			$template->assignClean('records', $result->fetchAllAssoc());

			$result = query("SELECT COUNT(*) AS `count` FROM `campaigns`".$where);
			$row = $result->fetchAssoc();
			$totalRecords = $row['count'];
			$template->assignClean('totalRecords', $totalRecords);

			$template->assignClean('search', $cm->getSearchVars());
			$template->assignClean('updateActions', $cm->getActions());
			$template->assignClean('updateAction', getRequest('updateAction'));

			$template->assignClean('show', $show);
			$template->assignClean('page', $page);
			$template->assignClean('start', $start);
			$template->assignClean('pages', ceil($totalRecords / $show));
			$template->assignClean('querystring', $cm->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			break;
		case 'admin/campaignEdit.htm':
			$template->assignClean('mode', $mode);
			$template->assignClean('campaign', $cm->get('record'));
			$template->assignClean('availabilityOptions', $cm->getArrayData('fields', 'availability'));
			$template->assignClean('typeOptions', $cm->getArrayData('fields', 'type'));
			break;
		default:
			break;
	}

	$template->getMessages();
	$template->display($pageTemplate);

?>