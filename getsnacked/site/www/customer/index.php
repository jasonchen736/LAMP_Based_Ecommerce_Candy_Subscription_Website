<?

	require_once 'customer.php';

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
	 *  Display the customer homepage
	 *  Args: none
	 *  Return: none
	 */
	function home() {
		$customerInfo = customerCore::getCustomerInfo();
		$member = new member($customerInfo['id']);
		$__styles = array();
		$__styles[] = '<style type="text/css" media="all">@import url(/css/'.systemSettings::get('SOURCEDIR').'/customer/customer.css);</style>';
		$template = new template;
		$template->assignClean('__styles', $__styles);
		$template->assignClean('customerInfo', $customerInfo);
		$template->assignClean('member', $member->fetchArray());
		$template->assignClean('customerLoggedIn', customerCore::validate());
		$template->getMessages();
		$template->display('customer/home.htm');
	} // function home

?>