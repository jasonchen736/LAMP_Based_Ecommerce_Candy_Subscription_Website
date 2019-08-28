<?

	class address_old extends dataObject {

		/* DATABASE VALUES - This data should always be validated (assumed valid) */
		// address record from database
		protected $record = false;
		// address type bit
		protected $addressBit = false;

		// address type bitmap
		protected $addressTypeBitmap = array(
			'account' => 1,
			'billing' => 2,
			'shipping' => 4,
		);

		/* USER INPUT VALUES */
		// address record from user input
		//   shipping and billing forms prepend "s" and "b" to these field names
		protected $form = array(
			'first'    => false,
			'last'     => false,
			'address1' => false,
			'address2' => false,
			'city'	   => false,
			'state'    => false,
			'postal'   => false,
			'province' => false,
			'country'  => false,
			'phone'    => false,
			'email'    => false
		);

		// processing indicators
		// address type
		protected $addressType = false;
		// save this address
		protected $saveAddress = false;
		// use as customer default address
		protected $defaultAddress = false;
		// flag that indicates if the form has ever been successfully submitted
		protected $formSubmitted = false;

		/**
		 *  Initialize vars
		 *  Args: none
		 *  Regurn: none
		 */
		public function __construct() {
			parent::__construct();
		} // function __construct

		/**
		 *  Reset address form
		 *  Args: none
		 *  Return: none
		 */
		public function resetAddressForm() {
			foreach ($this->form as $key => &$val) {
				$val = false;
			}
			unset($val);
			$this->addressType = false;
			$this->saveAddress = false;
			$this->defaultAddress = false;
		} // function resetAddressForm

		/**
		 *  Process address form (override)
		 *  Args: (str) type of form
		 *  Return: none
		 */
		public function processForm($type = 'account') {
			$this->resetAddressForm();
			switch ($type) {
				case 'shipping':
					$shippingForm = array();
					foreach ($this->form as $key => $val) {
						$shippingForm['s'.$key] = $val;
					}
					processForm($shippingForm);
					foreach ($this->form as $key => &$val) {
						$val = $shippingForm['s'.$key];
					}
					unset($val);
					$this->addressType = 'shipping';
					if (getRequest('saveShipping')) {
						$this->saveAddress = true;
					}
					if (getRequest('defaultShipping')) {
						$this->defaultAddress = true;
					}
					break;
				case 'billing':
					$billingForm = array();
					foreach ($this->form as $key => $val) {
						$billingForm['b'.$key] = $val;
					}
					processForm($billingForm);
					foreach ($this->form as $key => &$val) {
						$val = $billingForm['b'.$key];
					}
					unset($val);
					$this->addressType = 'billing';
					if (getRequest('saveBilling')) {
						$this->saveAddress = true;
					}
					if (getRequest('defaultBilling')) {
						$this->defaultAddress = true;
					}
					break;
				case 'account':
				default:
					processForm($this->form);
					$this->addressType = 'account';
					break;
			}
			$this->addType($this->addressType);
			$this->setFormSubmitted();
		} // function processForm

		/**
		 *  Determine if user submitted address is valid
		 *  Args: none
		 *  Return: (boolean) valid address
		 */
		public function validAddress() {
			$errors = array();
			$errorFields = array();
			switch ($this->addressType) {
				case 'shipping':
					$prefix = 's';
					break;
				case 'billing':
					$prefix = 'b';
					break;
				default:
					$prefix = '';
					break;
			}
			// set required fields
			$require = array(
				$prefix.'first',
				$prefix.'last',
				$prefix.'address1',
				$prefix.'city',
				$prefix.'postal',
				$prefix.'country'
			);
			if ($this->form['country'] == 'USA' || $this->form['country'] == 'US') {
				$require[] = $prefix.'state';
			} else {
				$require[] = $prefix.'province';
			}
			$checkForm = array();
			foreach ($this->form as $key => $val) {
				$checkForm[$prefix.$key] = $val;
			}
			// check for missing required fields
			$errorFields = checkRequired($checkForm, $require);
			if ($errorFields) {
				$errors[] = 'Missing required information';
			}
			// check email
			if($this->form['email'] && !validEmail($this->form['email'])) {
				$errors[] = 'Invalid email address';
				$errorFields[] = $prefix.'email';
			}
			if ($this->form['country'] == 'USA' || $this->form['country'] == 'US') {
				// US postal code incorrect length
				if (!validNumber($this->form['postal'], 'zip')) {
					$errors[] = 'Invalid zip code';
					$errorFields[] = $prefix.'postal';
				}
				// Valid state code abbreviation
				if (!preg_match('/^[A-Z]{2}$/', $this->form['state'])) {
					$errors[] = 'Invalid state';
					$errorFields[] = $prefix.'state';
				}
			}
			if ($this->form['phone']) {
				$this->form['phone'] = clean($this->form['phone'], 'integer');
				if ($this->form['country'] == 'USA' || $this->form['country'] == 'US') {
					if (!validNumber($this->form['phone'], 'phone')) {
						$errors[] = 'Invalid phone';
						$errorFields[] = $prefix.'phone';
					}
				} elseif (!preg_match('/^[\d]{9,20}$/', $this->form['phone'])) {
					$errors[] = 'Invalid phone';
					$errorFields[] = $prefix.'phone';
				}
			}
			if (empty($errors) && empty($errorFields)) {
				return true;
			} else {
				foreach ($errors as $error) {
					addError($error);
				}
				foreach ($errorFields as $errorField) {
					addErrorField($errorField);
				}
				return false;
			}
		} // function validAddress

		/**
		 *  Copy address variables
		 *  Args: (address) address to copy
		 *  Return: none
		 */
		public function copyAddress(address_old $address) {
			$this->record = $address->get('record');
			$this->addressBit = $address->get('addressBit');
			$this->form = $address->get('form');
			$this->addressType = $address->get('addressType');
			$this->saveAddress = $address->get('saveAddress');
			$this->defaultAddress = $address->get('defaultAddress');
			$this->formSubmitted = $address->get('formSubmitted');
		} // function copyAddress

		/**
		 *  Reset address array and id
		 *  Args: none
		 *  Return: none
		 */
		public function resetAddress() {
			$this->record = false;
			$this->addressBit = false;
		} // function resetAddress

		/**
		 *  Load an address by address id
		 *  Args: (int) address id
		 *  Return: (boolean) successful load
		 */
		public function loadAddress($addressID, $hasAccount = false) {
			$this->resetAddress();
			if (validNumber($addressID, 'integer')) {
				$table = $hasAccount ? 'savedAddresses' : 'addresses';
				$result = $this->dbh->query("SELECT * FROM `".$table."` WHERE `addressID` = '".$addressID."'");
				if ($result->rowCount) {
					$this->record = $result->fetchAssoc();
					$this->addressID = $this->record['addressID'];
					if ($hasAccount) {
						$result = $this->dbh->query("SELECT `addressBit` FROM `addressToType` WHERE `addressID` = '".$this->addressID."'");
						if ($result->rowCount) {
							$row = $result->fetchAssoc();
							$this->addressBit = $row['addressBit'];
						}
					}
					return true;
				}
			}
			return false;
		} // function loadAddress

		/**
		 *  Save an address record
		 *  Args: (int) customer id
		 *  Return: (boolean) success
		 */
		public function saveAddress($memberID) {
			if (validNumber($memberID, 'integer')) {
				$queryVals = array (
					'~first'       => prepDB($this->form['first']),
					'~last'        => prepDB($this->form['last']),
					'~address1'    => prepDB($this->form['address1']),
					'~address2'    => prepDB($this->form['address2']),
					'~city'        => prepDB($this->form['city']),
					'~state'       => $this->form['country'] == 'USA' || $this->form['country'] == 'US' ? prepDB($this->form['state']) : prepDB($this->form['province']),
					'~postal'      => prepDB($this->form['postal']),
					'~country'     => prepDB($this->form['country']),
					'~email'       => prepDB($this->form['email']),
					'~phone'       => prepDB($this->form['phone']),
					'entryDate'    => 'NOW()'
				);
				$this->dbh->perform('addresses', $queryVals);
				$this->addressID = $this->dbh->insertID;
				if ($this->addressID) {
					$this->loadAddress($this->addressID);
					return true;
				}
			}
			return false;
		} // function saveAddress

		/**
		 *  Update existing database address with user input address
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateAddress() {
			if ($this->addressID) {
				// determine if any fields are different
				$update = false;
				foreach ($this->form as $key => $val) {
					if ($key != 'state' && $key != 'province') {
						if ($val != $this->record[$key]) {
							$update = true;
							break;
						}
					} else {
						$state = $this->form['country'] == 'USA' || $this->form['country'] == 'US' ? $this->form['state'] : $this->form['province'];
						if ($state != $this->record['state']) {
							$update = true;
							break;
						}
					}
				}
				if ($update) {
					$queryVals = array (
						'~first'       => prepDB($this->form['first']),
						'~last'        => prepDB($this->form['last']),
						'~address1'    => prepDB($this->form['address1']),
						'~address2'    => prepDB($this->form['address2']),
						'~city'        => prepDB($this->form['city']),
						'~state'       => $this->form['country'] == 'USA' || $this->form['country'] == 'US' ? prepDB($this->form['state']) : prepDB($this->form['province']),
						'~postal'      => prepDB($this->form['postal']),
						'~country'     => prepDB($this->form['country']),
						'~email'       => prepDB($this->form['email']),
						'~phone'       => prepDB($this->form['phone'])
					);
					$where = "WHERE `addressID` = '".$this->addressID."'";
					if (isset($this->record['memberID'])) {
						$table = 'savedAddresses';
					} else {
						$table = 'addresses';
					}
					$this->dbh->perform($table, $queryVals, $where, 'update');
					if ($this->dbh->rowCount) {
						if (isset($this->record['memberID'])) {
							$this->saveAddressBit();
						}
						$this->loadAddress($this->addressID);
						return true;
					}
				}
			}
			return false;
		} // function updateAddress

		/**
		 *  Set address type
		 *  Args: (str) address type
		 *  Return: none
		 */
		public function setAddressType($type) {
			if (array_key_exists($type, $this->addressTypeBitmap)) {
				$this->addressType = $type;
				$this->addType($type);
			}
		} // function setAddressType

		/**
		 *  Add type map to current address
		 *  Args: (str) address type
		 *  Return: none
		 */
		public function addType($type) {
			if (array_key_exists($type, $this->addressTypeBitmap)) {
				if (!($this->addressTypeBitmap[$type] & $this->addressBit)) {
					$this->addressBit += $this->addressTypeBitmap[$type];
				}
			}
		} // function addType

		/**
		 *  Remove type map from current address
		 *  Args: (str) address type
		 *  Return: none
		 */
		public function removeType($type) {
			if (array_key_exists($type, $this->addressTypeBitmap)) {
				if ($this->addressTypeBitmap[$type] & $this->addressBit) {
					$this->addressBit -= $this->addressTypeBitmap[$type];
				}
			}
		} // function removeType

		/**
		 *  Save existing database address type bit
		 *  Args: none
		 *  Return: none
		 */
		public function saveAddressBit() {
			if ($this->addressID) {
				$this->dbh->query("INSERT INTO `addressToType` (`addressID`, `addressBit`) VALUES ('".$this->addressID."', '".$this->addressBit."') ON DUPLICATE KEY UPDATE `addressBit` = '".$this->addressBit."'");
			}
		} // function saveAddressBit

		/**
		 *  Check if address record exists for current customer
		 *  Args: (int) customer id
		 *  Return: (boolean) exists
		 */
		public function existsFor($memberID) {
			if (validNumber($memberID, 'integer')) {
				$result = $this->dbh->query("SELECT *
									FROM `customerAddresses`
									WHERE `memberID` = '".$memberID."'
									AND `first` = '".prepDB($this->form['first'])."'
									AND `last` = '".prepDB($this->form['last'])."'
									AND `address1` = '".prepDB($this->form['address1'])."'
									AND `address2` = '".prepDB($this->form['address2'])."'
									AND `city` = '".prepDB($this->form['city'])."'
									AND `state` = '".($this->form['country'] == 'USA' || $this->form['country'] == 'US' ? prepDB($this->form['state']) : prepDB($this->form['province']))."'
									AND `postal` = '".prepDB($this->form['postal'])."'
									AND `country` = '".prepDB($this->form['country'])."'
									AND `email` = '".prepDB($this->form['email'])."'
									AND `phone` = '".prepDB($this->form['phone'])."'");
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function existsFor

		/**
		 *  Check if the current address contained in form (user input)
		 *    matches current address contained in record (database)
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function isCurrentAddress() {
			if ($this->addressID) {
				$state = $this->form['country'] == 'USA' || $this->form['country'] == 'US' ? $this->form['state'] : $this->form['province'];
				if (
					$this->record['first'] == $this->form['first'] &&
					$this->record['last'] == $this->form['last'] &&
					$this->record['address1'] == $this->form['address1'] &&
					$this->record['address2'] == $this->form['address2'] &&
					$this->record['city'] == $this->form['city'] &&
					$this->record['state'] == $state &&
					$this->record['postal'] == $this->form['postal'] &&
					$this->record['country'] == $this->form['country'] &&
					$this->record['email'] == $this->form['email'] &&
					$this->record['phone'] == $this->form['phone']
				) {
					return true;
				}
			}
			return false;
		} // function isCurrentAddress

		/**
		 *  Return the form submitted flag
		 *  Args: none
		 *  Return: (boolean) form submitted
		 */
		public function formSubmitted() {
			return $this->formSubmitted;
		} // function formSubmitted

		/**
		 *  Set the form submitted flag true
		 *  Args: none
		 *  Return: none
		 */
		public function setFormSubmitted() {
			$this->formSubmitted = true;
		} // function setFormSubmitted
	} // class address_old

?>