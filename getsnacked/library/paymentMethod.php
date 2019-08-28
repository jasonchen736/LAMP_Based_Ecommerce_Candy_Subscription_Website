<?

	class paymentMethod extends dataObject {

		/* DATABASE VALUES - This data should always be validated (assumed valid) */
		// payment record from database
		protected $record = false;

		/* USER INPUT VALUES */
		// payment method record from user input
		protected $form = array(
			'paymentMethod' => false,
			'ccType'        => false,
			'expMonth'      => false,
			'expYear'       => false,
			'ccNum'         => false,
			'cvv'           => false,
			'bAccName'	    => false,
			'aba'           => false,
			'bName'         => false,
			'accType'       => false,
			'accNum'        => false
		);

		/**
		 *  Initialize vars
		 *  Args: none
		 *  Regurn: none
		 */
		public function __construct() {
			parent::__construct();
		} // function __construct

		/**
		 *  Reset payment method form
		 *  Args: none
		 *  Return: none
		 */
		public function resetMethodForm() {
			foreach ($this->form as $key => &$val) {
				$val = false;
			}
			unset($val);
		} // function resetMethodForm

		/**
		 *  Process method form (override)
		 *  Args: none
		 *  Return: none
		 */
		public function processForm() {
			$this->resetMethodForm();
			processForm($this->form);
		} // function processForm

		/**
		 *  Determine if user submitted payment method is valid
		 *  Args: none
		 *  Return: (boolean) valid method
		 */
		public function validMethod() {
			$errors = array();
			$errorFields = array();
			$require = array();
			if ($this->form['paymentMethod'] == 'cc') {
				$require[] = 'ccType';
				$require[] = 'expMonth';
				$require[] = 'expYear';
				$require[] = 'ccNum';
				$require[] = 'cvv';
			} elseif ($this->form['paymentMethod'] == 'echeck') {
				$require[] = 'bAccName';
				$require[] = 'aba';
				$require[] = 'bName';
				$require[] = 'accType';
				$require[] = 'accNum';
			} elseif ($this->form['paymentMethod'] != 'checkmoneyorder') {
				addError('Invalid Payment Method');
			}
			// check for missing required fields
			$errorFields = checkRequired($this->form, $require);
			if ($errorFields) {
				$errors[] = 'Missing required information';
			}
			// credit card validation
			if ($this->form['paymentMethod'] == 'cc') {
				// validate credit card number
				if (!validNumber($this->form['ccNum'], 'integer')) {
					$errors[] = 'Invalid credit card number';
					$errorFields[] = 'ccNum';
				} else {
					// determine credit card type
					if (preg_match('/^american *express$/i', $this->form['ccType'])) {
						$this->form['ccType'] = 'AMERICANEXPRESS';
					} elseif (preg_match('/^discover$/i', $this->form['ccType'])) {
						$this->form['ccType'] = 'DISCOVER';
					} elseif (preg_match('/^master *card$/i', $this->form['ccType'])) {
						$this->form['ccType'] = 'MASTERCARD';
					} elseif (preg_match('/^visa$/i', $this->form['ccType'])) {
						$this->form['ccType'] = 'VISA';
					}
					switch ($this->form['ccType']) {
						case 'AMERICANEXPRESS':
							if (strlen($this->form['ccNum']) != 15) {
								$errors[] = 'Invalid credit card number';
								$errorFields[] = 'ccNum';
							}
							break;
						case 'DISCOVER':
						case 'MASTERCARD':
							if (strlen($this->form['ccNum']) != 16) {
								$errors[] = 'Invalid credit card number';
								$errorFields[] = 'ccNum';
							}
							break;
						case 'VISA':
							if (strlen($this->form['ccNum']) != 16 && strlen($this->form['ccNum']) != 13) {
								$errors[] = 'Invalid credit card number';
								$errorFields[] = 'ccNum';
							}
							break;
						default:
							$errors[] = 'Unsupported credit card';
							$errorFields[] = 'ccType';
							$errorFields[] = 'ccNum';
							break;
					}
					if (!in_array('ccNum', getErrorFields())) {
						if (!$this->validateCard($this->form['ccNum'])) {
							$errors[] = 'Invalid credit card number';
							$errorFields[] = 'ccNum';
						}
					}
				}
				// validate security code (cvv)
				if (!validNumber($this->form['cvv'], 'integer')) {
					$errors[] = 'Invalid security code';
					$errorFields[] = 'cvv';
				} else {
					switch ($this->form['ccType']) {
						case 'AMERICANEXPRESS':
							if (strlen($this->form['cvv']) != 4) {
								$errors[] = 'Invalid security code';
								$errorFields[] = 'cvv';
							}
							break;
						default:
							if (strlen($this->form['cvv']) != 3) {
								$errors[] = 'Invalid security code';
								$errorFields[] = 'cvv';
							}
							break;
					}
				}
				// expiration date check
				if (!validNumber($this->form['expMonth'], 'integer') || !validNumber($this->form['expYear'], 'integer')) {
					$errors[] = 'Invalid expiration date';
					$errorFields[] = 'expMonth';
					$errorFields[] = 'expYear';
				} elseif (strtotime($this->form['expYear'].'-'.$this->form['expMonth'].'-01') < strtotime(date('Y-m-01'))) {
					$errors[] = 'Expiration date cannot be in the past';
					$errorFields[] = 'expMonth';
					$errorFields[] = 'expYear';
				} elseif (strlen($this->form['expMonth']) < 2) {
					$this->form['expMonth'] = '0'.$this->form['expMonth'];
				}
			}
			if (empty($errors) && empty($errorFields)) {
				// clear other payment fields
				switch ($this->form['paymentMethod']) {
					case 'cc':
						$this->form['bAccName'] = '';
						$this->form['aba'] = '';
						$this->form['bName'] = '';
						$this->form['accType'] = '';
						$this->form['accNum'] = '';
						break;
					case 'echeck':
						$this->form['ccType'] = '';
						$this->form['expMonth'] = '';
						$this->form['expYear'] = '';
						$this->form['ccNum'] = '';
						$this->form['cvv'] = '';
						break;
					case 'checkmoneyorder':
					default:
						$this->form['bAccName'] = '';
						$this->form['aba'] = '';
						$this->form['bName'] = '';
						$this->form['accType'] = '';
						$this->form['accNum'] = '';
						$this->form['ccType'] = '';
						$this->form['expMonth'] = '';
						$this->form['expYear'] = '';
						$this->form['ccNum'] = '';
						$this->form['cvv'] = '';
						break;
				}
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
		} // function validMethod

		/**
		 *  Reset payment method array and id
		 *  Args: none
		 *  Return: none
		 */
		public function resetMethod() {
			$this->record = false;
		} // function resetMethod

		/**
		 *  Save a payment method
		 *  Args: (int) customer id, (int) address id
		 *  Return: (boolean) success
		 */
		public function saveMethod($memberID, $addressID) {
			if (validNumber($memberID, 'integer') && validNumber($addressID, 'integer') && $this->validMethod()) {
				$queryVals = array (
					'~addressID'     => prepDB($addressID),
					'~paymentMethod' => prepDB($this->form['paymentMethod']),
					'~ccType'        => prepDB($this->form['ccType']),
					'~expMonth'      => prepDB($this->form['expMonth']),
					'~expYear'       => prepDB($this->form['expYear']),
					'~bAccName'      => prepDB($this->form['bAccName']),
					'~aba'           => prepDB($this->form['aba']),
					'~bName'         => prepDB($this->form['bName']),
					'~accType'       => prepDB($this->form['accType']),
					'entryDate'      => 'NOW()',
					'modifiedDate'   => 'NOW()'
				);
				$accNum = $this->form['paymentMethod'] == 'cc' ? prepDB($this->form['ccNum']) : prepDB($this->form['accNum']);
				$accNum = strlen($accNum) > 0 ? substr($accNum, -4) : '';
				$queryVals['~accNum_LastFour'] = $accNum;
				$this->dbh->perform('paymentMethods', $queryVals);
				$this->paymentMethodID = $this->dbh->insertID;
				if ($this->paymentMethodID) {
					$this->loadMethod($this->paymentMethodID);
					return true;
				}
			}
			return false;
		} // function saveMethod

		/**
		 *  Load a payment method by id
		 *  Args: (int) method id
		 *  Return: (boolean) successful load
		 */
		public function loadMethod($methodID) {
			$this->resetMethod();
			if (validNumber($methodID, 'integer')) {
				$result = $this->dbh->query("SELECT * FROM `paymentMethods` WHERE `paymentID` = '".$methodID."'");
				if ($result->rowCount) {
					$this->record = $result->fetchAssoc();
					$this->paymentMethodID = $this->record['paymentID'];
					return true;
				}
			}
			return false;
		} // function loadMethod

		/**
		 *  Load a payment method by billing id
		 *  Args: (int) method id
		 *  Return: (boolean) successful load
		 */
		public function loadMethodByBilling($billingID) {
			$this->resetMethod();
			if (validNumber($billingID, 'integer')) {
				$result = $this->dbh->query("SELECT * FROM `paymentMethods` WHERE `addressID` = '".$billingID."'");
				if ($result->rowCount) {
					$this->record = $result->fetchAssoc();
					$this->paymentMethodID = $this->record['paymentID'];
					return true;
				}
			}
			return false;
		} // function loadMethodByBilling

##### THE OLD STUFF #####

		/**
		 *  Check if payment record exists for current customer
		 *  Args: (int) customer id
		 *  Return: (boolean) exists
		 */
		public function existsFor($memberID) {
			if (validNumber($memberID, 'integer')) {
				$sql = "SELECT *
						FROM `paymentMethods`
						WHERE `memberID` = '".$memberID."'
						AND `paymentMethod` = '".prepDB($this->form['paymentMethod'])."'";
				if ($this->form['paymentMethod'] != 'checkmoneyorder') {
					$sql .= " AND `accNum` = '".($this->form['paymentMethod'] == 'cc' ? prepDB($this->form['ccNum']) : prepDB($this->form['accNum']))."'";
				}
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function existsFor

		/**
		 *  Check if the current payment method contained in methodForm (user input)
		 *    matches current payment method contained in paymentMethod (database)
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function isCurrentMethod() {
			if ($this->paymentMethodID && $this->form['paymentMethod'] != 'checkmoneyorder') {
				$accNum = $this->form['paymentMethod'] == 'cc' ? $this->form['ccNum'] : $this->form['accNum'];
				if ($accNum == $this->record['accNum']) {
					return true;
				}
			}
			return false;
		} // function isCurrentMethod

		/**
		 *  Update existing database payment method with user input payment method
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateMethod() {
			if ($this->paymentMethodID) {
				// determine if any fields are different
				$update = false;
				$ignore = array(
					'paymentID',
					'memberID',
					'addressID',
					'accNum',
					'entryDate',
					'modifiedDate',
				);
				foreach ($this->record as $key => $val) {
					if (!in_array($key, $ignore)) {
						if ($key != 'accNum_LastFour') {
							if ($val != $this->form[$key]) {
								$update = true;
								break;
							}
						} else {
							$accNum = $this->form['paymentMethod'] == 'cc' ? $this->form['ccNum'] : $this->form['accNum'];
							$accNum = substr($accNum, -4);
							if ($accNum != $val) {
								$update = true;
								break;
							}
						}
					} elseif ($key == 'accNum') {
						$accNum = $this->form['paymentMethod'] == 'cc' ? $this->form['ccNum'] : $this->form['accNum'];
						if ($accNum != $val) {
							$update = true;
							break;
						}
					}
				}
				if ($update && $this->validMethod()) {
					$queryVals = array (
						'~paymentMethod' => prepDB($this->form['paymentMethod']),
						'~ccType'        => prepDB($this->form['ccType']),
						'~expMonth'      => prepDB($this->form['expMonth']),
						'~expYear'       => prepDB($this->form['expYear']),
						'~bAccName'      => prepDB($this->form['bAccName']),
						'~aba'           => prepDB($this->form['aba']),
						'~bName'         => prepDB($this->form['bName']),
						'~accType'       => prepDB($this->form['accType']),
						'modifiedDate'   => 'NOW()'
					);
					$accNum = $this->form['paymentMethod'] == 'cc' ? prepDB($this->form['ccNum']) : prepDB($this->form['accNum']);
					$accNum = strlen($accNum) > 0 ? substr($accNum, -4) : '';
					$queryVals['~accNum_LastFour'] = $accNum;
					$where = "WHERE `paymentID` = '".$this->paymentMethodID."'";
					$this->dbh->perform('paymentMethods', $queryVals, $where, 'update');
					$this->loadMethod($this->paymentMethodID);
					return true;
				}
			}
			return false;
		} // function updateMethod

		/**
		 *  Credit card LUHN validation - coded '05 shaman - www.planzero.org
		 *  Validates credit card number with LUNH/MOD 10 algorithm
		 *  Args: (str) card number
		 *  Return: (boolean) validation result
		 */
		public function validateCard($cardnumber) {
			// strip any non-digits
			$cardnumber = preg_replace('/\D|\s/', '', $cardnumber);
			$cardlength = strlen($cardnumber);
			$parity = $cardlength % 2;
			$sum = 0;
			for ($i = 0; $i < $cardlength; $i++) {
				$digit = $cardnumber[$i];
				if ($i % 2 == $parity) {
					$digit = $digit * 2;
				}
				if ($digit > 9) {
					$digit = $digit - 9;
				}
				$sum = $sum + $digit;
			}
			$valid = ($sum % 10 == 0);
			return $valid;
		} // function validateCard

	} // class paymentMethod

?>
