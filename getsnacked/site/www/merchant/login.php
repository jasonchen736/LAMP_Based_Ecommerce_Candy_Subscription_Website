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
		$template->display('merchant/login.htm');
	} // function displayLogin

	/**
	 *  Attempt to log in
	 *  Args: none
	 *  Return: none
	 */
	function login() {
		$login = getPost('login', 'email');
		$pass = getPost('pass', 'password');
		if (merchantCore::login($login, $pass)) {
			$merchant = merchantCore::get('member');
			if (isset($merchant['loginPage']) && $merchant['loginPage']) {
				redirect($merchant['loginPage']);
			} else {
				redirect('/merchant');
			}
		} else {
			displayLogin($login, $pass);
		}
	} // function login

?>