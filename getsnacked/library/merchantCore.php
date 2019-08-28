<?

	class merchantCore {
		// stores merchant user and login info
		private static $merchant;
		// global merchant variables
		private static $startDate;
		private static $endDate;

		/**
		 *  Retrieve a mercahnt variable
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
		 *  Validate and store merchant login info
		 *  Args: (str) merchant email, (str) password
		 *  Return: (boolean) successful login
		 */
		public static function login($email, $pass) {
			self::initialize();
			$email = clean($email, 'email');
			$pass = clean($pass, 'password');
			if ($email && $pass) {
				self::$merchant['user'] = array();
				$result = query("SELECT `a`.`memberID`, `a`.`email`, `a`.`first`, 
										`a`.`last`, `a`.`status`, `b`.`memberGroupID`, `c`.`siteID` 
									FROM `members` `a` 
									LEFT JOIN `memberGroupMap` `b` ON (`a`.`memberID` = `b`.`memberID` AND `b`.`memberGroupID` = 2) 
									LEFT JOIN `memberSiteMap` `c` ON (`a`.`memberID` = `c`.`memberID` AND `c`.`siteID` = '".systemSettings::get('SITEID')."') 
									WHERE `a`.`email` = '".prep($email)."' 
									AND `a`.`password` = PASSWORD('".prep($pass)."')");
				if ($result->rowCount > 0) {
					$row = $result->fetchAssoc();
					if ($row['status'] == 'active') {
						if ($row['memberGroupID'] == 2) {
							if ($row['siteID'] == systemSettings::get('SITEID')) {
								self::$merchant['user']['id'] = $row['memberID'];
								self::$merchant['user']['email'] = $row['email'];
								self::$merchant['user']['first'] = $row['first'];
								self::$merchant['user']['last'] = $row['last'];
								return true;
							} else {
								addError('Your account does not have access to '.systemSettings::get('SITENAME'));
							}
						} else {
							addError('Your account does not have access to this area');
						}
					} else {
						addError('Your account is not active');
					}
				} else {
					addError('Bad login email and password combination');
				}
			} else {
				addError('Invalid email or password provided');
			}
			return false;
		} // function login

		/**
		 *  Merchant logout, redirect to merchant index
		 *  Args: none
		 *  Return: none
		 */
		public static function logout() {
			self::initialize();
			self::$merchant = NULL;
			redirect('/merchant');
		} // function logout

		/**
		 *  Verify merchant login
		 *  Args: none
		 *  Return: (boolean) valid merchant member
		 */
		public static function validate() {
			self::initialize();
			if (isset(self::$merchant['user']['id']) && self::$merchant['user']['id']) {
				return true;
			} else {
				self::$merchant = array();
				self::$merchant['loginPage'] = $_SERVER['REQUEST_URI'];
				return false;
			}
		} // function validate

		/**
		 *  Initialize global admin variables
		 *  Args: none
		 *  Return: none
		 */
		public static function initialize() {
			if (!isset($_SESSION['merchant'])) {
				$_SESSION['merchant'] = array();
			}
			self::$merchant = &$_SESSION['merchant'];
			$startDate = getRequest('startDate');
			self::$startDate = $startDate ? dateToSql(urldecode($startDate)) : date('Y-m-d', strtotime('last month'));
			$endDate = getRequest('endDate');
			self::$endDate = $endDate ? dateToSql(urldecode($endDate)) : date('Y-m-d');
		} // function initialize

		/**
		 *  Return sessioned merchant info
		 *  Args: none
		 *  Return: (mixed) array merchant info or NULL
		 */
		public static function getMerchantInfo() {
			if (isset(self::$merchant['user'])) {
				return self::$merchant['user'];
			}
			return NULL;
		} // function getMerchantInfo
	} // class merchantCore

?>