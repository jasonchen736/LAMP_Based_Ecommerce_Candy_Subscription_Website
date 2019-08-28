<?

	// include global functions and classes
	require_once 'functions.lib.php';
	require_once 'systemSettings.php';
	require_once 'errorHandler.php';
	require_once 'sessionHandler.php';
	require_once 'systemNotifications.php';

	// prepare web friendly url environment
	// process and inject friendly get request
	if ($_request = getRequest('_request')) {
		$_request = explode('/', $_request);
		$_request_key = false;
		unset($_GET['_request']);
		unset($_REQUEST['_request']);
		foreach ($_request as $key => $val) {
			// always decode incomming get parameters
			$val = urldecode($val);
			if ($key % 2) {
				$_GET[$_request_key] = $val;
				if (!isset($_POST[$_request_key])) {
					$_REQUEST[$_request_key] = $val;
				}
			} else {
				$_request_key = $val;
			}
		}
	}

	// process server php_self variable to web friendly version
	$_SERVER['PHP_SELF'] = rtrim($_SERVER['PHP_SELF'], '.php');

	// define system variables and constants
	systemSettings::configure();

	/**
	 *  This function helps to autoload a class without explicitly requiring it
	 *  Args: (str) class name
	 *  Return: none
	 */
	function __autoload($class_name) {
		require_once systemSettings::get('LIBRARYPATH').$class_name.'.php';
	} // function __autoload

	// set custom error handler
	errorHandler::setHandler();

	// debug output is not templated and immediate
	// allow redirecting with debug
	if (isDevEnvironment() && systemSettings::get('DEBUG')) {
		ob_start();
	}

	// initialize and set session handler
	sessionHandler::initialize();
	sessionHandler::setHandler();
	// start session
	session_start();

	// initialize system notifications
	systemNotifications::initialize();
	systemNotifications::retrieveRequestMessages();

?>