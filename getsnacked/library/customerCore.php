<?

	class customerCore {
		// stores customer user and login info
		private static $customer = false;
		// global customer variables
		private static $startDate = false;
		private static $endDate = false;

		/**
		 *  Retrieve a customer variable
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
		 *  Validate and store customer login info
		 *  Args: (str) customer email, (str) password
		 *  Return: (boolean) successful login
		 */
		public static function login($email, $pass) {
			self::initialize();
			$email = clean($email, 'email');
			$pass = clean($pass, 'password');
			if ($email && $pass) {
				self::$customer['user'] = array();
				$result = query("SELECT `a`.`memberID`, `a`.`email`, `a`.`first`, 
										`a`.`last`, `a`.`status`, `b`.`memberGroupID`, `c`.`siteID` 
									FROM `members` `a` 
									LEFT JOIN `memberGroupMap` `b` ON (`a`.`memberID` = `b`.`memberID` AND `b`.`memberGroupID` = 1) 
									LEFT JOIN `memberSiteMap` `c` ON (`a`.`memberID` = `c`.`memberID` AND `c`.`siteID` = '".systemSettings::get('SITEID')."') 
									WHERE `a`.`email` = '".prep($email)."' 
									AND `a`.`password` = PASSWORD('".prep($pass)."')");
				if ($result->rowCount > 0) {
					$row = $result->fetchAssoc();
					if ($row['status'] == 'active') {
						if ($row['memberGroupID'] == 1) {
							if ($row['siteID'] == systemSettings::get('SITEID')) {
								self::$customer['user']['id'] = $row['memberID'];
								self::$customer['user']['email'] = $row['email'];
								self::$customer['user']['first'] = $row['first'];
								self::$customer['user']['last'] = $row['last'];
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
		 *  Set up core for a given member, bypass login, will log out previous user
		 *  Args: none
		 *  Return: none
		 */
		public static function setCore($memberID) {
			self::initialize();
			self::$customer['user'] = array();
			$memberID = clean($memberID, 'integer');
			if ($memberID) {
				$result = query("SELECT * FROM `members` WHERE memberID = '".$memberID."'");
				if ($result->rowCount > 0) {
					$row = $result->fetchRow();
					self::$customer['user']['id'] = $row['memberID'];
					self::$customer['user']['email'] = $row['email'];
					self::$customer['user']['first'] = $row['first'];
					self::$customer['user']['last'] = $row['last'];
				}
			}
		} // function setCore

		/**
		 *  Customer logout, redirect to customer index
		 *  Args: none
		 *  Return: none
		 */
		public static function logout() {
			self::initialize();
			self::$customer = NULL;
			// to prevent any shennanegans in case a user logs out in the middle of checking out
			//   need to update to properly transition into guest/anonymous checkout
			$_SESSION['user'] = NULL;
			unset($_SESSION['user']);
			redirect('/customer');
		} // function logout

		/**
		 *  Verify customer login
		 *  Args: none
		 *  Return: (boolean) valid customer
		 */
		public static function validate() {
			self::initialize();
			if (isset(self::$customer['user']['id']) && self::$customer['user']['id']) {
				return true;
			} else {
				self::$customer = array();
				self::$customer['loginPage'] = $_SERVER['REQUEST_URI'];
				return false;
			}
		} // function validate

		/**
		 *  Initialize global admin variables
		 *  Args: none
		 *  Return: none
		 */
		public static function initialize() {
			if (!isset($_SESSION['customer'])) {
				$_SESSION['customer'] = array();
			}
			self::$customer = &$_SESSION['customer'];
			$startDate = getRequest('startDate');
			self::$startDate = $startDate ? dateToSql(urldecode($startDate)) : date('Y-m-d', strtotime('last month'));
			$endDate = getRequest('endDate');
			self::$endDate = $endDate ? dateToSql(urldecode($endDate)) : date('Y-m-d');
		} // function initialize

		/**
		 *  Return sessioned customer info
		 *  Args: none
		 *  Return: (mixed) array customer info or NULL
		 */
		public static function getCustomerInfo() {
			if (isset(self::$customer['user'])) {
				return self::$customer['user'];
			}
			return NULL;
		} // function getCustomerInfo

		/**
		 *  Return sessioned customer's member object
		 *  Args: none
		 *  Return: (member) customer member object
		 */
		public static function getMember() {
			if (self::$customer === false) {
				self::initialize();
			}
			if (isset(self::$customer['user']['id'])) {
				$member = new member(self::$customer['user']['id']);
			} else {
				$member = new member;
			}
			return $member;
		} // function getMember
	} // class customerCore

?>