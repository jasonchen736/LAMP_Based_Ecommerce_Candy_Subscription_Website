<?

	require_once 'customer.php';

	$actions = array(
		'ordersAdmin',
		'orderDetails',
		'quickUpdate'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		ordersAdmin();
	}

	/**
	 *  Show the customer orders admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function ordersAdmin() {
		$customerInfo = customerCore::getCustomerInfo();
		$controller = new ordersController;
		$controller->imposeSearch('memberID', $customerInfo['id']);
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$template = new template;
		$template->assignClean('customerInfo', $customerInfo);
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->getMessages();
		$template->display('customer/customerOrdersAdmin.htm');
	} // function ordersAdmin

	/**
	 *  View order details
	 *  Args: none
	 *  Return: none
	 */
	function orderDetails() {
		$customerInfo = customerCore::getCustomerInfo();
		$order = new order(getRequest('orderID'));
		if ($order->exists()) {
			if ($order->get('memberID') == $customerInfo['id']) {
				$shippingAddress = new address($order->get('shippingID'));
				$billingAddress = new address($order->get('billingID'));
				$shippingMethod = new shippingOption($order->get('shippingArrangement'));
				$paymentMethod = ordersController::getPaymentMethod($order->get('orderID'));
				$template = new template;
				$template->assignClean('customerInfo', $customerInfo);
				$template->assignClean('customerLoggedIn', customerCore::validate());
				$template->assignClean('order', $order->fetchArray());
				$template->assignClean('shippingMethod', $shippingMethod->get('name'));
				$template->assignClean('shippingAddress', $shippingAddress->fetchArray());
				$template->assignClean('billingAddress', $billingAddress->fetchArray());
				$template->assignClean('paymentMethod', $paymentMethod);
				$template->assignClean('items', ordersController::getOrderItems($order->get('orderID')));
				$template->display('customer/orderDetails.htm');
			} else {
				addError('The order was not found');
				ordersAdmin();
			}
		} else {
			addError('The order was not found');
			ordersAdmin();
		}
	} // function orderDetails

?>