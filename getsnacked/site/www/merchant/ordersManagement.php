<?

	require_once 'merchant.php';

	$actions = array(
		'subOrdersAdmin',
		'viewPackingSlip',
		'quickUpdate'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		subOrdersAdmin();
	}

	/**
	 *  Show the sub orders admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function subOrdersAdmin() {
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
	} // function subOrdersAdmin

	/**
	 *  View packing slip for a sub order
	 *  Args: none
	 *  Return: none
	 */
	function viewPackingSlip() {
		require_once 'html2fpdf/html2fpdf.php';
		$subOrder = new subOrder(getRequest('subOrderID'));
		if ($subOrder->exists()) {
			$order = new order($subOrder->get('orderID'));
			$shippingAddress = new address($order->get('shippingID'));
			$shippingMethod = new shippingOption($order->get('shippingArrangement'));
			$template = new template;
			$template->assignClean('order', $order->fetchArray());
			$template->assignClean('subOrder', $subOrder->fetchArray());
			$template->assignClean('shippingMethod', $shippingMethod->get('name'));
			$template->assignClean('shippingAddress', $shippingAddress->fetchArray());
			$template->assign('items', subOrdersController::getSubOrderItems($subOrder->get('subOrderID')));
			ob_end_clean();
			ob_start();
			$template->display('site/packingSlip.htm');
			$html = ob_get_contents();
			ob_end_clean();
			$html2pdf = new HTML2FPDF();
			$html2pdf->AddPage();
			$html2pdf->WriteHTML($html);
			$html2pdf->Output('doc.pdf', 'I');
		} else {
			addError('The sub order was not found');
			subOrdersAdmin();
		}
	} // function viewPackingSlip

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
				case 'new':
				case 'fulfilled':
				case 'backordered':
				case 'cancelled':
				case 'paymentdeclined':
				case 'returned':
					foreach ($records as $subOrderID) {
						$subOrder = new subOrder($subOrderID);
						$subOrder->set('status', $action);
						switch ($action) {
							case 'new':
								$subOrder->set('fulfillmentDate', 'NULL', false);
								$subOrder->enclose('fulfillmentDate', false);
								break;
							case 'fulfilled':
								$subOrder->set('fulfillmentDate', 'NOW()', false);
								$subOrder->enclose('fulfillmentDate', false);
								break;
							default:
								break;
						}
						if ($subOrder->update()) {
							addSuccess('Sub order '.$subOrderID.' updated');
						} else {
							addError('There was an error while updating order '.$subOrderID);
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
		subOrdersAdmin();
	} // function quickUpdate

?>