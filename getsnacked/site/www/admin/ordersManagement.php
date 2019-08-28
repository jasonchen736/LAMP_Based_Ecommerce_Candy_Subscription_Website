<?

	require_once 'admin.php';

	$actions = array(
		'ordersAdmin',
		'viewPackingSlip',
		'quickUpdate'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		ordersAdmin();
	}

	/**
	 *  Show the orders admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function ordersAdmin() {
		$controller = new ordersController;
		$controller->setDefaultSearch('orderStatus', 'new');
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$updateOption = getRequest('updateOption', 'alphanum');
		$template = new template;
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', ordersController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/ordersAdmin.htm');
	} // function ordersAdmin

	/**
	 *  View packing slip for an order
	 *  Args: none
	 *  Return: none
	 */
	function viewPackingSlip() {
		require_once 'html2fpdf/html2fpdf.php';
		$order = new order(getRequest('orderID'));
		if ($order->exists()) {
			$shippingAddress = new address($order->get('shippingID'));
			$shippingMethod = new shippingOption($order->get('shippingArrangement'));
			$template = new template;
			$template->assignClean('order', $order->fetchArray());
			$template->assignClean('subOrder', array('subOrderID' => false));
			$template->assignClean('shippingMethod', $shippingMethod->get('name'));
			$template->assignClean('shippingAddress', $shippingAddress->fetchArray());
			$template->assign('items', ordersController::getOrderItems($order->get('orderID')));
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
				case 'processing':
				case 'attention':
					foreach ($records as $orderID) {
						$order = new order($orderID);
						$order->set('orderStatus', $action);
						switch ($action) {
							case 'fulfilled':
								$order->set('fulfillmentDate', 'NOW()', false);
								$order->enclose('fulfillmentDate', false);
								break;
							default:
								break;
						}
						if ($order->update()) {
							addSuccess('Order '.$orderID.' updated');
							if (!$order->updateSubOrderStatus()) {
								addError('There was an error while updating sub orders');
							}
						} else {
							addError('There was an error while updating order '.$orderID);
						}
					}
					break;
				case 'clearCheck':
					$processor = new orderProcessor;
					foreach ($records as $orderID) {
						if ($processor->clearCheckMoneyOrder($orderID, 'check')) {
							addSuccess('Order '.$orderID.' cleared');
						} else {
							addError('There was an error while clearing order '.$orderID);
						}
					}
					break;
				case 'clearMoneyOrder':
					$processor = new orderProcessor;
					foreach ($records as $orderID) {
						if ($processor->clearCheckMoneyOrder($orderID, 'moneyorder')) {
							addSuccess('Order '.$orderID.' cleared');
						} else {
							addError('There was an error while clearing order '.$orderID);
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
		ordersAdmin();
	} // function quickUpdate

?>