<?

	class adminCore {
		// stores admin user and login info
		private static $admin;
		// global admin variables
		private static $startDate;
		private static $endDate;

		/**
		 *  Retrieve an admin variable
		 *  Args: (str) variable name
		 *  Return: (mixed) variable value
		 */
		public static function get($variable) {
			if (isset(self::$$variable)) {
				return self::$$variable;
			}
			return NULL;
		} // function get

		/**
		 *  Validate and store admin login info
		 *  Args: (str) admin login, (str) password
		 *  Return: (boolean) successful login
		 */
		public static function login($login, $pass) {
			self::initialize();
			$login = clean($login, 'alphanum');
			$pass = clean($pass, 'password');
			if ($login && $pass) {
				self::$admin['user'] = array();
				$result = query("SELECT * FROM `adminUser` WHERE `login` = '".prep($login)."' AND `password` = OLD_PASSWORD('".prep($pass)."')");
				if ($result->rowCount > 0) {
					$row = $result->fetchAssoc();
					self::$admin['user']['id'] = $row['userID'];
					self::$admin['user']['login'] = $row['login'];
					self::$admin['user']['name'] = $row['name'];
					return true;
				} else {
					addError('Login/Password combination does not match');
				}
			} else {
				addError('Invalid login or password provided');
			}
			return false;
		} // function login

		/**
		 *  Admin logout, redirect to admin index
		 *  Args: none
		 *  Return: none
		 */
		public static function logout() {
			self::initialize();
			self::$admin = NULL;
			redirect('/admin');
		} // function logout

		/**
		 *  Verify admin login
		 *  Args: none
		 *  Return: (boolean) valid admin user
		 */
		public static function validate() {
			self::initialize();
			if (isset(self::$admin['user']['id']) && self::$admin['user']['id']) {
				return true;
			} else {
				self::$admin = array();
				self::$admin['loginPage'] = $_SERVER['REQUEST_URI'];
				return false;
			}
		} // function validate

		/**
		 *  Initialize global admin variables
		 *  Args: none
		 *  Return: none
		 */
		public static function initialize() {
			if (!isset($_SESSION['admin'])) {
				$_SESSION['admin'] = array();
			}
			self::$admin = &$_SESSION['admin'];
			$startDate = getRequest('startDate');
			self::$startDate = $startDate ? dateToSql(urldecode($startDate)) : date('Y-m-d', strtotime('last month'));
			$endDate = getRequest('endDate');
			self::$endDate = $endDate ? dateToSql(urldecode($endDate)) : date('Y-m-d');
		} // function initialize

		/**
		 *  Check site registry against current site name
		 *  Args: none
		 *  Return: (boolean) site is registered
		 */
		public static function checkSiteRegistry() {
			$siteName = systemSettings::get('SITENAME');
			$siteID = systemSettings::get('SITEID');
			$sql = "SELECT `siteID` FROM `siteRegistry` WHERE `siteName` = '".prep($siteName)."'";
			$result = query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				if ($row['siteID'] == $siteID) {
					return true;
				}
			}
			return false;
		} // function checkSiteRegistry

		/**
		 *  Return sessioned admin info
		 *  Args: none
		 *  Return: (mixed) array admin info or NULL
		 */
		public static function getAdminInfo() {
			if (isset(self::$admin['user'])) {
				return self::$admin['user'];
			}
			return NULL;
		} // function getAdminInfo
	} // class adminCore

?>