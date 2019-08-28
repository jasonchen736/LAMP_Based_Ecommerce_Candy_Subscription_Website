<?

	class orderProcessor extends dataObject {

		/* DATABASE VARS - This data should always be validated (assumed valid) */
		// order fields array
		protected $record = false;

		// package object
		protected $package = false;
		// billing address
		protected $billingAddress = false;
		// payment method
		protected $paymentMethod = false;
		// shipping address
		protected $shippingAddress = false;

		// customer id
		protected $memberID = false;
		// completed order flag
		protected $completedOrder = false;
		// shipping cost
		protected $subShippingCosts = false;
		protected $shippingCost = false;
		// final order cost (after shipping, discounts and other factors)
		protected $finalOrderCost = false;

		/* USER INPUT */
		// order form
		protected $form = array(
			'quantity'        => false,
			'orderType'       => false,
			'shipArrangement' => false,
			'payArrangement'  => false,
			'startDate'       => false,
			'endDate'         => false,
			'intervalMethod'  => false,
			'dayInterval'     => false,
			'weekInterval'    => false,
			'monthInterval'   => false,
			'months'          => array(
									'January'   => 0,
									'February'  => 0,
									'March'     => 0,
									'April'     => 0,
									'May'       => 0,
									'June'      => 0,
									'July'      => 0,
									'August'    => 0,
									'September' => 0,
									'October'   => 0,
									'November'  => 0,
									'December'  => 0
                                 ),
			'weeks'           => array(
									'First'  => 0,
									'Second' => 0,
									'Third'  => 0,
									'Fourth' => 0,
									'Last'   => 0,
                                 ),
			'days'            => array(
									'Sunday'    => 0,
									'Monday'    => 0,
									'Tuesday'   => 0,
									'Wednesday' => 0,
									'Thursday'  => 0,
									'Friday'    => 0,
									'Saturday'  => 0
                                 ),
			'dates'           => array(
									'01' => 0,
									'02' => 0,
									'03' => 0,
									'04' => 0,
									'05' => 0,
									'06' => 0,
									'07' => 0,
									'08' => 0,
									'09' => 0,
									'10' => 0,
									'11' => 0,
									'12' => 0,
									'13' => 0,
									'14' => 0,
									'15' => 0,
									'16' => 0,
									'17' => 0,
									'18' => 0,
									'19' => 0,
									'20' => 0,
									'21' => 0,
									'22' => 0,
									'23' => 0,
									'24' => 0,
									'25' => 0,
									'26' => 0,
									'27' => 0,
									'28' => 0,
									'29' => 0,
									'30' => 0,
									'31' => 0
                                 )
		);

		/**
		 *  Initialize data object vars
		 *  Args: none
		 *  Regurn: none
		 */
		public function __construct() {
			parent::__construct();
		} // function __construct

		/**
		 *  Associate order with required references
		 *  Args: (int) customer id, (address) shipping address, (address) billing address, (paymentMethod) payment method, (package) package object
		 *  Return: none
		 */
		public function associateOrder(&$memberID, &$shippingAddress, &$billingAddress, &$paymentMethod, &$package) {
			$this->memberID = $memberID;
			$this->shippingAddress = $shippingAddress;
			$this->billingAddress = $billingAddress;
			$this->paymentMethod = $paymentMethod;
			$this->package = $package;
		} // function associateOrder

		/**
		 *  Set user input into orderForm array (override)
		 *    unset any unneeded values per configuration
		 *  Args: none
		 *  Return: none
		 */
		public function processForm() {
			$unset = array();
			if (!systemSettings::get('SUBSCRIPTIONS')) {
				$unset = array_merge($unset, array(
					'orderType',
					'payArrangement',
					'endDate',
					'intervalMethod',
					'dayInterval',
					'weekInterval',
					'monthInterval',
					'months',
					'weeks',
					'days',
					'dates'
				));
			}
			if (!systemSettings::get('SHIPPINGDATES')) {
				$unset = array_merge($unset, array(
					'startDate'
				));
			}
			if (!systemSettings::get('PACKAGEQUANTITY')) {
				$unset = array_merge($unset, array(
					'quantity'
				));
			}
			foreach ($unset as $key) {
				unset($this->form[$key]);
			}
			processForm($this->form);
			if (!systemSettings::get('SUBSCRIPTIONS')) {
				$this->form['orderType'] = 'order';
			}
			if (!systemSettings::get('SHIPPINGDATES')) {
				$this->form['startDate'] = date('Y-m-d');
			}
			if (!systemSettings::get('PACKAGEQUANTITY')) {
				$this->form['quantity'] = 1;
			}
			$this->generateShippingCost();
		} // function processForm

		/**
		 *  Calculate and set shipping cost
		 *  Args: none
		 *  Return: none
		 */
		public function generateShippingCost() {
			if ($this->form['shipArrangement']) {
				$shippingCosts = shippingOptionsController::generateShippingCost($this->form['shipArrangement'], $this->shippingAddress, $this->package);
				if (!empty($shippingCosts)) {
					$finalShippingCost = 0;
					foreach ($shippingCosts as $memberID => $cost) {
						$finalShippingCost += $cost;
					}
					$this->subShippingCosts = $shippingCosts;
					$this->shippingCost = $finalShippingCost;
				}
			}
		} // function generateShippingCost

		/**
		 *  Validate current form requirements
		 *  Args: none
		 *  Regurn: (boolean) valid
		 */
		public function validFormValues() {
			$errors = array();
			$errorFields = array();
			if (systemSettings::get('SUBSCRIPTIONS')) {
				// check subscription form values
			}
			if (!between($this->form['quantity'], 1, 100)) {
				$errors[] = 'Invalid order quantity';
				$errorFields[] = 'quantity';
			}
			if (!validSqlDate($this->form['startDate'])) {
				$errors[] = 'Invalid order date';
				$errorFields[] = 'startDate';
			} elseif (strtotime($this->form['startDate']) < strtotime(date('Y-m-d'))) {
				$errors[] = 'Invalid order date';
				$errorFields[] = 'startDate';
			}
			if (!shippingOptionsController::validOption($this->form['shipArrangement'])) {
				$errors[] = 'Invalid shipping method';
				$errorFields[] = 'shipArrangement';
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
		} // function validFormValues

		/**
		 *  Checks that all required info is set and valid
		 *  Args: none
		 *  Return: (boolean) valid
		 */
		public function validOrder() {
			clearAllMessages();
			$this->validFormValues();
			if (!$this->billingAddress) {
				addError('Invalid billing address');
			} else {
				$this->billingAddress->validAddress();
			}
			if (!$this->shippingAddress) {
				addError('Invalid shipping address');
			} else {
				$this->shippingAddress->validAddress();
			}
			if (!$this->paymentMethod) {
				addError('Invalid payment method');
			} else {
				$this->paymentMethod->validMethod();
			}
			if (!$this->package) {
				addError('Invalid order');
			} else {
				$this->package->validPackage();
			}
			if ($this->completedOrder) {
				addError('Order has already been completed');
			}
			if (!haveErrors()) {
				$this->generateFinalCost();
				return true;
			} else {
				return false;
			}
		} // function validOrder

		/**
		 *  Enter valid order into the database
		 *  Args: none
		 *  Return: (boolean) successful order insert or update
		 */
		public function enterOrder() {
			// existing record indicates order has already been entered
			//   payment may not have been cleared
			if (!$this->record) {
				$order = new order;
				customerCore::initialize();
				$customerInfo = customerCore::getCustomerInfo();
				if (!empty($customerInfo)) {
					$order->set('memberID', $customerInfo['id']);
				}
				$order->set('packageID', $this->package->get('packageID'));
				$order->set('quantity', $this->form['quantity']);
				$order->set('totalCost', $this->finalOrderCost);
				$order->set('shippingArrangement', $this->form['shipArrangement']);
				$order->set('shippingCost', $this->shippingCost);
				$order->set('fulfillBy', $this->form['startDate']);
				$order->set('siteID', systemSettings::get('SITEID'));
				$order->set('paymentCleared', 'no');
				$order->set('discount', 0);
				$order->set('orderStatus', 'new');
				$order->set('orderDate', 'NOW()', false);
				$order->enclose('orderDate', false);
				$paymentMethod = $this->paymentMethod->getArrayData('form', 'paymentMethod');
				if ($paymentMethod == 'cc') {
					$order->set('paymentMethod', $this->paymentMethod->getArrayData('form', 'ccType'));
				} else {
					$order->set('paymentMethod', $paymentMethod);
				}
				$this->billingAddress->saveAddress(0);
				if (!$this->billingAddress->getArrayData('record', 'addressID')) {
					addError('Billing info could not be saved');
					return false;
				} else {
					$order->set('billingID', $this->billingAddress->getArrayData('record', 'addressID'));
				}
				$this->paymentMethod->saveMethod(0, $order->get('billingID'));
				if (!$this->paymentMethod->getArrayData('record', 'paymentID')) {
					addError('Billing info could not be saved');
					return false;
				}
				$this->shippingAddress->saveAddress(0);
				if (!$this->shippingAddress->getArrayData('record', 'addressID')) {
					addError('Shipping info could not be saved');
					return false;
				} else {
					$order->set('shippingID', $this->shippingAddress->getArrayData('record', 'addressID'));
				}
				if ($order->save()) {
					$order->enterSubOrders($this->subShippingCosts);
					$orderID = $order->get('orderID');
					$orderReference = new orderReference;
					$orderReference->set('type', tracker::get('referralType'));
					$orderReference->set('ID', tracker::get('ID'));
					$orderReference->set('subID', tracker::get('subID'));
					$orderReference->set('campaignID', tracker::get('campaignID'));
					$orderReference->set('offerID', tracker::get('offerID'));
					$orderReference->set('payoutID', tracker::get('payoutID'));
					$orderReference->set('passThroughVariable', tracker::get('passThroughVar'));
					$orderReference->set('orderID', $orderID);
					$orderReference->set('orderDate', $order->get('orderDate'));
					if (!$orderReference->save()) {
						$referenceData = $orderReference->fetchArray();
						$referenceString = '';
						foreach ($referenceData as $field => $data) {
							$referenceString .= '['.$field.':'.$data.'], ';
						}
						$referenceString = substr($referenceString, 0, -2);
						trigger_error('There was an error while trying to save an order reference: '.$referenceString, E_USER_WARNING);
					}
					return $this->loadOrder($orderID);
				} else {
					addError('There was an error while saving the order');
					return false;
				}
			} else {
				// order record exists
				//   try to update record, if success update history
				$order = new order($this->record['orderID']);
				if ($order->exists()) {
					$order->set('packageID', $this->package->get('packageID'));
					$order->set('quantity', $this->form['quantity']);
					$order->set('totalCost', $this->finalOrderCost);
					$order->set('shippingArrangement', $this->form['shipArrangement']);
					$order->set('shippingCost', $this->shippingCost);
					$order->set('fulfillBy', $this->form['startDate']);
					$order->set('orderDate', 'NOW()', false);
					$order->enclose('orderDate', false);
					$paymentMethod = $this->paymentMethod->getArrayData('form', 'paymentMethod');
					if ($paymentMethod == 'cc') {
						$order->set('paymentMethod', $this->paymentMethod->getArrayData('form', 'ccType'));
					} else {
						$order->set('paymentMethod', $paymentMethod);
					}
					$this->billingAddress->updateAddress();
					$order->set('billingID', $this->billingAddress->getArrayData('record', 'addressID'));
					$this->shippingAddress->updateAddress();
					$order->set('shippingID', $this->shippingAddress->getArrayData('record', 'addressID'));
					$this->paymentMethod->updateMethod();
					if ($order->update()) {
						return $this->loadOrder($this->record['orderID']);
					}
				}
				addError('There was an error while updating the order');
			}
			return false;
		} // function enterOrder

		/**
		 *  Calculates and returns final cost of purchase
		 *  Args: none
		 *  Return: none
		 */
		private function generateFinalCost() {
			// single order
			$this->finalOrderCost = ($this->package->get('totalCost') * $this->form['quantity']) + $this->shippingCost;
		} // function generateFinalCost

		/**
		 *  Process current order payment transaction
		 *  Args: none
		 *  Return: (array) authorization gateway response
		 */
		public function authorizeTransaction() {
			// this transaction type will always be an auth_capture
			if (preg_match('/^echeck|cc$/i', $this->paymentMethod->getArrayData('form', 'paymentMethod'))) {
				$order = new order($this->record['orderID']);
				$subOrders = $order->getSubOrders();
				$cleared = array();
				$errored = array();
				$errorMessages = array();
				$totalCharged = 0;
				$members = array();
				foreach ($subOrders as $subOrder) {
					$members[] = $subOrder['memberID'];
				}
				$memberGateways = membersController::getMemberGateways($members);
				$certificatesRoot = systemSettings::get('CERTIFICATESDIR');
				authorize::setup();
				foreach ($subOrders as $subOrder) {
					$transaction = array(
						'type'           => 'CHARGE',
						'customer_id'    => $this->memberID,
						'description'    => 'ORDER '.$this->record['orderID'].' SUBORDER '.$subOrder['subOrderID'],
						'method'         => strtoupper($this->paymentMethod->getArrayData('form', 'paymentMethod')),
						'invoice_num'    => $subOrder['subOrderID'],
						'card_type'      => $this->paymentMethod->getArrayData('form', 'ccType'),
						'card_num'       => $this->paymentMethod->getArrayData('form', 'ccNum'),
						'exp_month'      => $this->paymentMethod->getArrayData('form', 'expMonth'),
						'exp_year'       => $this->paymentMethod->getArrayData('form', 'expYear'),
						'card_code'      => $this->paymentMethod->getArrayData('form', 'cvv'),
						'amount'         => $subOrder['totalCost'],
						'first_name'     => $this->billingAddress->getArrayData('form', 'first'),
						'last_name'      => $this->billingAddress->getArrayData('form', 'last'),
						'address'        => $this->billingAddress->getArrayData('form', 'address1'),
						'city'           => $this->billingAddress->getArrayData('form', 'city'),
						'state'          => $this->billingAddress->getArrayData('form', 'state'),
						'zip'            => $this->billingAddress->getArrayData('form', 'postal'),
						'country'        => $this->billingAddress->getArrayData('form', 'country'),
						'bank_aba_code'  => $this->paymentMethod->getArrayData('form', 'aba'),
						'bank_acct_num'  => $this->paymentMethod->getArrayData('form', 'bAccName'),
						'bank_acct_type' => $this->paymentMethod->getArrayData('form', 'accType'),
						'bank_name'      => $this->paymentMethod->getArrayData('form', 'bName'),
						'bank_acct_name' => $this->paymentMethod->getArrayData('form', 'accNum')
					);
					$transaction['member_id'] = $subOrder['memberID'];
					$transaction['gateway'] = $memberGateways[$subOrder['memberID']]['gateway'];
					$transaction['host'] = $memberGateways[$subOrder['memberID']]['url'];
					switch ($memberGateways[$subOrder['memberID']]['gateway']) {
						case 'authorize':
							$transaction['login'] = $memberGateways[$subOrder['memberID']]['login'];
							$transaction['key'] = $memberGateways[$subOrder['memberID']]['decrypted'];
							$transaction['hashKey'] = $memberGateways[$subOrder['memberID']]['key'];
							break;
						case 'linkpoint':
							$transaction['port'] = $memberGateways[$subOrder['memberID']]['port'];
							$transaction['key'] = $memberGateways[$subOrder['memberID']]['key'];
							if ($subOrder['memberID']) {
								$transaction['certificate'] = $certificatesRoot.$subOrder['memberID'].'.cert';
							} else {
								$transaction['certificate'] = $certificatesRoot.'linkpoint.cert';
							}
							break;
						default:
							// this should never happen
							break;
					}
					$response = authorize::authorizeTransaction($transaction);
					if ($response && isset($response['responseText']) && $response['responseText'] == 'Approved') {
						$cleared[$subOrder['subOrderID']] = $subOrder['memberID'];
						$totalCharged += $subOrder['totalCost'];
						$this->logPayment($response['transactionRecordID']);
					} else {
						if (!$response || !isset($response['responseText']) || isset($response['deactivate'])) {
							if ($subOrder['memberID']) {
								membersController::deactivateGateway($subOrder['memberID']);
							}
							if (isset($response['reasonText'])) {
								$error = $response['reasonText'] ? $response['reasonText'] : 'unspecified gateway error';
							} else {
								$error = 'unspecified gateway error';
							}
							trigger_error('Gateway error while processing payment for member '.$subOrder['memberID'].', sub order '.$subOrder['subOrderID'].', reason: '.$error, E_USER_WARNING);
						}
						$errored[$subOrder['subOrderID']] = $subOrder['memberID'];
						$errorMessages[] = 'Transaction Declined'.(isset($response['reasonText']) && $response['reasonText'] ? ': '.$response['reasonText'] : '');
					}
				}
				clearAllMessages();
				if (empty($errored)) {
					// transaction approved
					$this->completedOrder = true;
					$this->clearPayment();
					$this->logConversion();
					return true;
				} else {
					if (!empty($cleared)) {
						// there were errors processing one or more sub order transactions
						addError('There was an error with one of our merchant payment gateways');
						addError('As a result, only part of your order was charged successfully');
						addError('The total amount charged successfully was $'.number_format($totalCharged, 2));
						addError('The items that were paid for will be processed normally');
						addError('You may contact customer service regarding your payment error');
						$order->clearSubOrders($cleared);
						return true;
					} else {
						// general transaction error
						addError('There was an error with your order payment');
						foreach ($errorMessages as $errorMessage) {
							addError($errorMessage);
						}
						addError('Please check your payment information');
						addError('If this problem persists, please contact customer service');
					}
					return false;
				}
			} else {
				// check/money order sales do not log as conversions until payment is received
				$this->completedOrder = true;
				return true;
			}
		} // function authorizeTransaction

		/**
		 *  Set order payment status to cleared
		 *  Args: none
		 *  Return: (boolean) order updated
		 */
		private function clearPayment() {
			if (validNumber($this->record['orderID'], 'integer')) {
				$order = new order($this->record['orderID']);
				if ($order->exists()) {
					$order->set('paymentCleared', 'cleared');
					if ($order->update()) {
						if ($order->clearSubOrders()) {
							return true;
						}
					}
				}
			}
			return false;
		} // function clearPayment

		/**
		 *  Log payment record
		 *  Args: (int) transaction id, (str) payment method
		 *  Return: none
		 */
		private function logPayment($transactionID, $paymentMethod = false) {
			if (validNumber($transactionID, 'integer')) {
				if (!$paymentMethod) {
					$paymentMethod = strtolower($this->paymentMethod->getArrayData('form', 'paymentMethod'));
				} else {
					$paymentMethod = $paymentMethod == 'moneyorder' ? 'moneyorder' : 'check';
				}
				$queryVals = array(
					'~orderID'     => prepDB($this->record['orderID']),
					'~method'      => $paymentMethod,
					'datePosted'   => 'NOW()'
				);
				if ($transactionID) {
					$queryVals['~transactionRecordID'] = $transactionID;
				}
				$this->dbh->perform('paymentLog', $queryVals);
			}
		} // function logPayment

		/**
		 *  Log conversion into tracking tables if everything checks out
		 *    Log into offer deviation table if something off
		 *  Args: none
		 *  Return: none
		 */
		private function logConversion() {
			if ($this->completedOrder && $this->record) {
				// valid conversions are logged when an offer has been completed correctly
				//   or there was no offer id
				$offerID = tracker::get('offerID');
				if ($offerID) {
					$offer = new offer($offerID);
					if ($offer->exists()) {
						// currenly only single orders allowed
						$totalShipments = 1;
						// package and shipments must match
						if (preg_match('/'.$this->record['packageID'].'/', $offer->get('availablePackages')) && $totalShipments >= $offer->get('totalShipments')) {
							$offerCompleted = true;
						} else {
							$offerCompleted = false;
						}
					} else {
						$offerCompleted = false;
					}
				} else {
					$offerCompleted = true;
				}
				if ($offerCompleted) {
					tracker::logConversion();
				} else {
					$intendedPackage = tracker::get('landingPackage');
					if (!$intendedPackage) {
						$intendedPackage = $offer->get('defaultPackage');
					}
					$offerDeviation = new offerDeviation;
					$offerDeviation->set('ID', tracker::get('ID'));
					$offerDeviation->set('subID', tracker::get('subID'));
					$offerDeviation->set('campaignID', tracker::get('campaignID'));
					$offerDeviation->set('offerID', $offerID);
					$offerDeviation->set('payoutID', tracker::get('payoutID'));
					$offerDeviation->set('intendedPackageID', $intendedPackage);
					$offerDeviation->set('intendedShipments', $offer->get('totalShipments'));
					$offerDeviation->set('orderedPackageID', $this->record['packageID']);
					$offerDeviation->set('orderedShipments', $totalShipments);
					$offerDeviation->set('date', 'NOW()', false);
					$offerDeviation->enclose('date', false);
					$offerDeviation->save();
				}
				// update product and package inventory and availability
				$this->updateInventory();
				// log package/product order counts and promotion usage
				$this->logCounts();
			}
		} // function logConversion

		/**
		 *  Updates inventory data after an order
		 *    Updates availability of product and packages if needed
		 *  Args: none
		 *  Return: none
		 */
		private function updateInventory() {
			// start with updating product inventory
			$items = '';
			$updateVals = '';
			foreach ($this->package->get('contents') as $itemID => $val) {
				$updateVals .= "('".prepDB($itemID)."', '".($val['Q'] * $this->record['quantity'])."'), ";
				$items .= "'".prepDB($itemID)."', ";
			}
			$updateVals = rtrim($updateVals, ', ');
			$items = rtrim($items, ', ');
			// update product inventory
			$this->dbh->query("INSERT INTO `productInventory` (`productID`, `quantity`) VALUES ".$updateVals."
								ON DUPLICATE KEY UPDATE `quantity` = `quantity` - VALUES(`quantity`)");
			// retrieve updated inventory count
			$result = $this->dbh->query("SELECT `a`.`productID` FROM `productInventory` `a` JOIN `products` `b` USING (`productID`) WHERE `a`.`productID` IN (".$items.") AND `a`.`quantity` <= 0 AND `b`.`availability` NOT IN ('alwaysavailable', 'outofstock')");
			if ($result->rowCount) {
				// switch items off available
				$productsOut = array();
				while ($row = $result->fetchAssoc()) {
					$productsOut[] = $row['productID'];
				}
				$productsManager = new productsManager;
				$productsManager->takeAction($productsOut, 'outofstock');
				// switch packages off available if need
				$result = query("SELECT `a`.`packageID` FROM `productToPackage` `a` JOIN `packages` `b` USING (`packageID`) WHERE `a`.`productID` IN ('".implode("', '", $itemsOut)."') AND `b`.`availability` != 'alwaysavailable'");
				if ($result->rowCount) {
					$packagesOut = array();
					while ($row = $result->fetchAssoc()) {
						$packagesOut[] = $row['packageID'];
					}
					$packageManager = new packageManager;
					$packageManager->takeAction($packagesOut, 'outofstock');
				}
			}
		} // function updateInventory

		/**
		 *  Log package/product order counts
		 *  Args: none
		 *  Return: none
		 */
		private function logCounts() {
			if ($this->completedOrder) {
				// package tracking
				$this->dbh->query("INSERT INTO `packageTrack` (`packageID`, `orders`, `dateOrdered`) VALUES ('".prepDB($this->package->get('packageID'))."', '".prepDB($this->record['quantity'])."', CURDATE())
									ON DUPLICATE KEY UPDATE `orders` = `orders` + VALUES(`orders`)");
				$insertVals = '';
				foreach ($this->package->get('contents') as $itemID => $val) {
					$insertVals .= "('".prepDB($itemID)."', '".($val['Q'] * $this->record['quantity'])."', CURDATE()), ";
				}
				$insertVals = rtrim($insertVals, ', ');
				// product tracking
				$this->dbh->query("INSERT INTO `productTrack` (`productID`, `orders`, `dateOrdered`) VALUES ".$insertVals."
									ON DUPLICATE KEY UPDATE `orders` = `orders` + VALUES(`orders`)");
			}
		} // function logCounts

		/**
		 *  Reset order references
		 *  Args: (boolean) reset object forms as well
		 *  Return: none
		 */
		public function clearOrder($resetForms = true) {
			if ($this->shippingAddress) {
				if ($resetForms) {
					$this->shippingAddress->resetAddressForm();
				}
				$this->shippingAddress->resetAddress();
			}
			if ($this->billingAddress) {
				if ($resetForms) {
					$this->billingAddress->resetAddressForm();
				}
				$this->billingAddress->resetAddress();
			}
			if ($this->paymentMethod) {
				if ($resetForms) {
					$this->paymentMethod->resetMethodForm();
				}
				$this->paymentMethod->resetMethod();
			}
			if ($this->package) {
				$this->package->resetPackage();
			}
			$this->completedOrder = false;
			$this->shippingCost = false;
			$this->finalOrderCost = false;
			$this->record = false;
			if ($resetForms) {
				foreach ($this->form as $key => &$val) {
					if (!is_array($val)) {
						$val = false;
					} else {
						foreach ($val as $index => &$value) {
							$value = 0;
						}
						unset($value);
					}
				}
			}
			unset($val);
		} // function clearOrder

		/**
		 *  Load order by order ID
		 *  Args: (int) order id
		 *  Regurn: none
		 */
		public function loadOrder($orderID) {
			$this->clearOrder(false);
			if (validNumber($orderID, 'integer')) {
				$result = $this->dbh->query("SELECT * FROM `orders` WHERE `orderID` = '".$orderID."'");
				if ($result->rowCount) {
					$this->record = $result->fetchAssoc();
					if (!$this->package) {
						$this->package = new package_old;
					}
					$this->package->setPackageDate($this->record['orderDate']);
					$this->package->loadPackage($this->record['packageID']);
					if (!$this->billingAddress) {
						$this->billingAddress = new address_old;
					}
					$this->billingAddress->loadAddress($this->record['billingID']);
					if (!$this->paymentMethod) {
						$this->paymentMethod = new paymentMethod;
					}
					$this->paymentMethod->loadMethodByBilling($this->record['billingID']);
					if (!$this->shippingAddress) {
						$this->shippingAddress = new address_old;
					}
					$this->shippingAddress->loadAddress($this->record['shippingID']);
					$this->memberID = $this->record['memberID'];
					$this->generateShippingCost();
					$this->generateFinalCost();
					return true;
				} else {
					addError('Order could not be found');
				}
			} else {
				addError('Invalid order ID');
			}
			return false;
		} // function loadOrder

		/**
		 *  Clear an existing order made by check/money order
		 *    Will log payment, log conversion (attribute affiliate), update inventory
		 *  Args: (int) order id, (str) payment method
		 *  Return: (boolean) success
		 */
		public function clearCheckMoneyOrder($orderID, $type) {
			if ($this->loadOrder($orderID)) {
				$this->completedOrder = true;
				if ($this->record['paymentCleared'] == 'no' && $this->record['paymentMethod'] == 'checkmoneyorder') {
					$this->logPayment(0, $type);
					$this->logConversion();
					$order = new order($orderID);
					if ($order->exists()) {
						$order->set('paymentCleared', 'cleared');
						if ($order->update()) {
							if ($order->clearSubOrders()) {
								return true;
							}
						}
					}
				}
			}
			return false;
		} // function clearCheckMoneyOrder

	} // class orderProcessor

?>