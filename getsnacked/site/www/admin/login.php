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
		$template->display('admin/login.htm');
	} // function displayLogin

	/**
	 *  Attempt to log in
	 *  Args: none
	 *  Return: none
	 */
	function login() {
		$login = getPost('login', 'alphanum');
		$pass = getPost('pass', 'password');
		if (adminCore::login($login, $pass)) {
			if (adminCore::checkSiteRegistry()) {
				$admin = adminCore::get('admin');
				if (isset($admin['loginPage']) && $admin['loginPage']) {
					redirect($admin['loginPage']);
				} else {
					redirect('/admin');
				}
			} else {
				redirect('/admin/siteManagement/action/autoRegisterSite');
			}
		} else {
			displayLogin($login, $pass);
		}
	} // function login

?>