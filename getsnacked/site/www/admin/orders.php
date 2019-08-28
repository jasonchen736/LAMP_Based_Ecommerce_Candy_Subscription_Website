<?

	require_once 'admin.php';

	$om = new ordersManager;

	switch (getRequest('action')) {
		case 'packingSlip':
			require_once 'html2fpdf/html2fpdf.php';
			$order = new orderProcessor;
			$order->loadOrder(getRequest('orderID'));
			$template->assignClean('companyName', systemSettings::get('COMPANYNAME'));
			$template->assignClean('slogan', systemSettings::get('SLOGAN'));
			$template->assignClean('mainAddress1', systemSettings::get('MAINADDRESS1'));
			$template->assignClean('mainAddress2', systemSettings::get('MAINADDRESS2'));
			$template->assignClean('mainAddress3', systemSettings::get('MAINADDRESS3'));
			$template->assignClean('mainCityStatePostal', systemSettings::get('MAINCITY').', '.systemSettings::get('MAINSTATE').' '.systemSettings::get('MAINPOSTAL'));
			$template->assignClean('mainPhone', systemSettings::get('MAINPHONE'));
			$template->assignClean('mainFax', systemSettings::get('MAINFAX'));
			$template->assignClean('siteURL', systemSettings::get('SITEURL'));
			$template->assignClean('order', $order->get('record'));
			$template->assignClean('orderDate', strtotime($order->getArrayData('record', 'orderDate')));
			$template->assignClean('shippingAddress', $order->getObjectData('shippingAddress', 'record'));
			$template->assignClean('package', $order->getObjectData('package', 'contents'));
			ob_end_clean();
			ob_start();
			$template->display('orderPackingSlip.htm');
			$html = ob_get_contents();
			ob_end_clean();
			$html2pdf = new HTML2FPDF();
			$html2pdf->AddPage();
			$html2pdf->WriteHTML($html);
			$html2pdf->Output('doc.pdf', 'I');
			exit;
			break;
		case 'massAction':
			$om->takeAction(getRequest('records'), getRequest('updateAction'));
			break;
		default:
			break;
	}

	list($start, $show, $page) = $om->getTableLocation();
	$search = $om->getSearchArray();
	if ($search) {
		$where = ' WHERE '.implode(' AND ', $search);
	} else {
		$where = '';
	}

	$dbh = new database;
	$result = $dbh->query("SELECT * FROM `orders`".$where." LIMIT ".$start.", ".$show);
	$template->assignClean('records', $result->fetchAllAssoc());

	$result = $dbh->query("SELECT COUNT(*) AS `count` FROM `orders`".$where);
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

	$template->getMessages();
	$template->display('admin/ordersAdmin.htm');

?>