<?

	$actions = array(
		'displayLogin',
		'login'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		displayLogin();
	}

	/**
	 *  Show the login form
	 *  Args: (str) login name, (str) password
	 *  Return: none
	 */
	function displayLogin($login = false, $pass = false) {
		$template = new template;
		$template->assignClean('login', $login);
		$template->assignClean('pass', $pass);
		$template->getMessages();
		$template->display('customer/login.htm');
	} // function displayLogin

	/**
	 *  Attempt to log in
	 *  Args: none
	 *  Return: none
	 */
	function login() {
		$login = getPost('login', 'email');
		$pass = getPost('pass', 'password');
		if (customerCore::login($login, $pass)) {
			$customer = customerCore::get('member');
			if (isset($customer['loginPage']) && $customer['loginPage']) {
				redirect($customer['loginPage']);
			} else {
				redirect('/customer');
			}
		} else {
			displayLogin($login, $pass);
		}
	} // function login

?>