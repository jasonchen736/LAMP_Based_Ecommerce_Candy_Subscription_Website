<?

	require_once 'admin.php';

	$om = new offersManager;

	switch (getRequest('action')) {
		case 'add':
			$pageTemplate = 'admin/offerEdit.htm';
			$mode = 'add';
			break;
		case 'addOffer':
			if ($om->addOffer()) {
				$offerID = $om->getArrayData('record', 'offerID');
				$message = 'Offer added (Offer ID: '.$offerID.')';
				if (!$om->uploadImage()) {
					$message .= '.  However, the offer image has failed to upload.';
				} else {
					$om->update();
				}
				addSuccess($message);
				if (getRequest('submit') == 'Add and Edit') {
					redirect($_SERVER['PHP_SELF'].'/action/edit/offerID/'.$offerID);
				} else {
					redirect($_SERVER['PHP_SELF'].'/action/add');
				}
			} else {
				$pageTemplate = 'admin/offerEdit.htm';
				$mode = 'add';
			}
			break;
		case 'edit':
			if ($om->loadID(getRequest('offerID'))) {
				$pageTemplate = 'admin/offerEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/offersAdmin.htm';
			}
			break;
		case 'update':
			if ($om->loadID(getPost('offerID'))) {
				if ($om->updateOffer()) {
					$offerID = $om->getArrayData('record', 'offerID');
					addSuccess('Offer (Offer ID: '.$offerID.') updated');
					redirect($_SERVER['PHP_SELF'].'/action/edit/offerID/'.$offerID);
				}
				$pageTemplate = 'admin/offersEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/offersAdmin.htm';
			}
			break;
		case 'massAction':
			$om->takeAction(getRequest('records'), getRequest('updateAction'));
		default:
			$pageTemplate = 'admin/offersAdmin.htm';
			break;
	}

	switch ($pageTemplate) {
		case 'admin/offersAdmin.htm':
			list($start, $show, $page) = $om->getTableLocation();
			$search = $om->getSearchArray();
			if ($search) {
				$where = ' WHERE '.implode(' AND ', $search);
			} else {
				$where = '';
			}

			$result = query("SELECT * FROM `offers`".$where." LIMIT ".$start.", ".$show);
			$template->assignClean('records', $result->fetchAllAssoc());

			$result = query("SELECT COUNT(*) AS `count` FROM `offers`".$where);
			$row = $result->fetchAssoc();
			$totalRecords = $row['count'];
			$template->assignClean('totalRecords', $totalRecords);

			$template->assignClean('search', $om->getSearchVars());
			$template->assignClean('updateActions', $om->getActions());
			$template->assignClean('updateAction', getRequest('updateAction'));

			$template->assignClean('show', $show);
			$template->assignClean('page', $page);
			$template->assignClean('start', $start);
			$template->assignClean('pages', ceil($totalRecords / $show));
			$template->assignClean('querystring', $om->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			break;
		case 'admin/offerEdit.htm':
			$template->assignClean('mode', $mode);
			$template->assignClean('offer', $om->get('record'));
			$offerTags = $om->get('offerTags');
			assertArray($offerTags);
			$template->assignClean('tags', implode("\r\n", $offerTags));
			$template->assignClean('availabilityOptions', $om->getArrayData('fields', 'availability'));
			$template->assignClean('payTypeOptions', $om->getArrayData('fields', 'payType'));
			break;
		default:
			break;
	}

	$template->assignClean('packages', offersManager::getOfferPackages($om->getArrayData('record', 'offerID')));
	$template->assignClean('campaigns', offersManager::getOfferCampaigns($om->getArrayData('record', 'offerID')));

	$template->getMessages();
	$template->display($pageTemplate);

?>