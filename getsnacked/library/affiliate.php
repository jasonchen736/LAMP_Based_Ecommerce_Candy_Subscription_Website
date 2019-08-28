<?

	require_once 'SwiftMailer/lib/Swift.php';
	require_once 'SwiftMailer/lib/Swift/Connection/SMTP.php';
	require_once 'SwiftMailer/lib/Swift/Plugin/Decorator.php';

	class affiliate extends dataObject {

		// reference vars
		protected $currentPage, $lastPage;
		// offer var
		protected $availableOffers;
		// email object
		protected $mailer;
		// account vars
		protected $affiliateID;
		// forms
		protected $registrationForm = array(
			'company'      => false,
			'first'        => false,
			'last'         => false,
			'phone'        => false,
			'fax'          => false,
			'email'        => false,
			'website'      => false,
			'password'     => false,
			'confPassword' => false,
			'address1'     => false,
			'address2'     => false,
			'city'         => false,
			'state'        => false,
			'postal'       => false,
			'country'      => false,
			'agreeTerms'   => false,
			'agreePolicy'  => false,
			'isOverAge'    => false
		);
		protected $requiredRegistrationFields = array(
			'first',
			'last',
			'phone',
			'email',
			'password',
			'confPassword',
			'address1',
			'city',
			'state',
			'postal',
			'country',
			'agreeTerms',
			'agreePolicy',
			'isOverAge'
		);

		/**
		 *  Initiate resources and references
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			// retrieve and store into session any overflowed errors to prevent excessive logging
			errorHandler::retrieveErrorOverflow();
			$this->affiliateID = false;
			$this->availableOffers = false;
			parent::__construct();
		} // function __construct
		
		/**
		 *  Debug info
		 *  Args: none
		 *  Return: none
		 */
		public function __destruct() {
			$_SESSION['affiliate'] = &$this;
			debug(&$this, 'AFFILIATE OBJECT');
			parent::__destruct();
		} // function __destruct()

		/**
		 *  Prep for object serialization
		 *  Args: none
		 *  Return: none
		 */
		public function __sleep() {
			return parent::__sleep();
		} // function __sleep

		/**
		 *  Restore objects, set page reference, clear errors
		 *  Args: none
		 *  Return: none
		 */
		public function __wakeup() {
			parent::__wakeup();
			$this->lastPage = $this->currentPage;
			$this->currentPage = $_SERVER['PHP_SELF'];
		} // function __wakeup

		/**
		 *  Clean/populate registration form array with request
		 *  Args: none
		 *  Return: none
		 */
		public function processRegistrationForm() {
			processForm($this->registrationForm);
			$this->validateRegistrationForm();
			if (empty($this->errorMsgs)) {
				$this->insertNewAffiliate();
			}
		} // funciton processAffiliateRegistrationForm

		/**
		 *  Validate registration form input
		 *  Args: none
		 *  Return: none
		 */
		private function validateRegistrationForm() {
			$require = $this->requiredRegistrationFields;
			$this->checkRequired($require);
			// country/state/postal info
			//   this removes state from missing array if international
			if ($this->registrationForm['country'] == 'USA') {
				// US postal code incorrect length
				if (!is_numeric($this->registrationForm['postal']) || strlen($this->registrationForm['postal']) < 5) {
					$this->errorMsgs[] = 'Invalid zip code';
					addErrorField('postal');
				}
				// Valid state code abbreviation
				if (!($this->registrationForm['state']) || strlen($this->registrationForm['state']) > 2) {
					$this->errorMsgs[] = 'Invalid state';
					addErrorField('state');
				}
			} else {
				removeFromArray($this->missing, 'state');
				$this->registrationForm['state'] = '';
			}
			// check email
			if (!validEmail($this->registrationForm['email'])) {
				$this->errorMsgs[] = 'Please enter a valid email address';
				addErrorField('email');
			} else {
				// check for existing email
				$result = $this->dbh->query("SELECT * FROM `affiliates` WHERE `email` = '".prepDB($this->registrationForm['email'])."'");
				if ($result->rowCount) {
					$this->errorMsgs[] = 'The email address already exists, please enter a new one or sign in';
					addErrorField('email');
				} else {
					// login defaults to email
					if (!$this->registrationForm['login']) {
						$this->registrationForm['login'] = $this->registrationForm['email'];
					}
				}
			}
			// password confirmation
			if (!$this->registrationForm['password'] || $this->registrationForm['password'] != $this->registrationForm['confPassword']) {
				if ($this->registrationForm['password'] != $this->registrationForm['confPassword']) $this->errorMsgs[] = 'Password confirmation does not match';
				addErrorField('password');
				addErrorField('confirmPassword');
			}
			if (!empty($this->missing)) $this->errorMsgs[] = 'Missing required information';
		} // function validateRegistrationForm

		/**
		 *  Insert a new valid affiliate into the database
		 *  Args: none
		 *  Return: none
		 */
		private function insertNewAffiliate() {
			$queryVals = array(
				'~company'    => prepDB($this->registrationForm['company']),
				'~first'      => prepDB($this->registrationForm['first']),
				'~last'       => prepDB($this->registrationForm['last']),
				'~phone'      => prepDB($this->registrationForm['phone']),
				'~fax'        => prepDB($this->registrationForm['fax']),
				'~email'      => prepDB($this->registrationForm['email']),
				'~website'    => prepDB($this->registrationForm['website']),
				'~password'   => prepDB($this->registrationForm['password']),
				'~address1'   => prepDB($this->registrationForm['address1']),
				'~address2'   => prepDB($this->registrationForm['address2']),
				'~city'       => prepDB($this->registrationForm['city']),
				'~state'      => prepDB($this->registrationForm['state']),
				'~postal'     => prepDB($this->registrationForm['postal']),
				'~country'    => prepDB($this->registrationForm['country']),
				'agreeTerms'  => prepDB($this->registrationForm['agreeTerms']),
				'agreePolicy' => prepDB($this->registrationForm['agreePolicy']),
				'isOverAge'   => prepDB($this->registrationForm['isOverAge']),
				'entryDate'   => 'NOW()'
				
			);
			$this->dbh->perform('affiliates', $queryVals);
		} // function insertNewAffiliate

		/**
		 *  Clean/populate login form array with request
		 *  Args: none
		 *  Return: none
		 */
		public function processLoginForm() {
			processForm($this->registrationForm);
			// check email
			if (!validEmail($this->registrationForm['email'])) {
				$this->errorMsgs[] = 'Please enter a valid email address';
				addErrorField('email');
			}
			// check password
			if (!$this->registrationForm['password']) {
				$this->errorMsgs[] = 'Please enter your password';
				addErrorField('password');
			}
			if (empty($this->errorMsgs)) {
				$this->findAccount();
				if ($this->affiliateID) {
					// post validation - success actions
				}
			}
		} // funciton processAffiliateRegistrationForm

		/**
		 *  Checks email and password submitted from submit
		 *    if email exists and password match, log user in by setting session vars
		 *    if email exists password not matching, return bad login
		 *    if email doesnt exist, let pass
		 *  Args: none
		 *  Return: none
		 */
		private function findAccount() {
			assertArray($this->errorMsgs);
			// find existing email/login
			$result = $this->dbh->query("SELECT `affiliateID`, `email`, `password` FROM `affiliates` WHERE `email` = '".prepDB($this->registrationForm['email'])."'");
			if (isset($_REQUEST['affiliateLogin'])) {
				// login
				if ($this->affiliateID) {
					// already logged in
					$this->errorMsgs[] = 'You are already logged in, please log out first';
				} elseif ($result->rowCount) {
					$row = $result->fetchAssoc();
					if ($row['password'] != $this->registrationForm['password']) {
						// login, incorrect password
						$this->errorMsgs[] = 'Incorrect password for '.$this->registrationForm['email'];
						addErrorField('password');
					} elseif (!$this->affiliateID) {
						// affiliate id is the logged in flag
						//   the only way affiliate id can be set is if user successfully creates a new account or logs in
						$this->affiliateID = $row['affiliateID'];
						// update login count
						$this->dbh->query("UPDATE `affiliates` SET `totalLogins` = `totalLogins` + 1 WHERE `affiliateID` = '".prepDB($this->affiliateID)."'");
					} else {
						$this->errorMsgs[] = 'You are already logged in, please log out first';
					}
				} else {
					// no email found
					$this->errorMsgs[] = 'Email not found';
				}
			} elseif ($this->affiliateID) {
				// affiliate changing account info
				if ($result->rowCount) {
					$row = $result->fetchAssoc();
					if ($row['affiliateID'] != $this->affiliateID) {
						// user entered an existing address/login that belongs to another account
						$this->errorMsgs[] = $this->registrationForm['email'].' has an existing account, please enter a different email address';
						addErrorField('email');
					} else {
						$this->errorMsgs[] = 'You are currently using this email address';
						addErrorField('email');
					}
				}
			} elseif ($result->rowCount) {
				// new account
				$row = $result=>fetchAssoc();
				if ($row['email'] == prepDB($this->registrationForm['email'])) {
					$this->errorMsgs[] = $this->registrationForm['email'].' has an existing account, please log in or enter a different email address';
					addErrorField('email');
				}
			}
		} // function findAccount

		/**
		 *  Populate offers data array with availabe offers
		 *  Args: none
		 *  Return: none
		 */
		public function retrieveOfferData() {
			if (!$this->availableOffers) {
				// affiliate has to be logged in
				if ($this->affiliateID) {
					### IMPLEMENT AFFILIATE EXCLUSIVE OFFERS ###
					$result = $this->dbh->query("SELECT * 
													FROM `offers` 
													WHERE `availability` IN ('affiliate', 'all') 
													AND `startDate` <= NOW() 
													AND `endDate` > NOW()");
					if ($result->rowCount) {
						$this->availableOffers = array();
						while ($row = $result->fetchAssoc()) {
							$this->availableOffers[] = $row;
						}
					}
				}
			}
		} // function retrieveOfferData

	} // class affiliate

?>