<?

	class user extends dataObject {
		// member id
		protected $memberID;
		// package (shopping cart) object
		protected $package;
		// shipping address
		protected $shippingAddress;
		// billing address
		protected $billingAddress;
		// payment method
		protected $paymentMethod;
		// order object
		protected $order;

		/**
		 *  Initialize user
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			// retrieve and store into session any overflowed errors to prevent excessive logging
			errorHandler::retrieveErrorOverflow();
			parent::__construct();
			// instantiate shopping cart
			$this->package = new package_old;
			// set up package via offer request
			if ($landingPackage = tracker::get('landingPackage')) {
				$this->package->setOfferID(tracker::get('offerID'));
				$this->package->setPackageID($landingPackage);
				$this->package->getPackageArray();
				if (!$this->package->validPackage()) {
					$this->package->resetPackage();
				}
			} else {
				// set up package via direct url request
				$this->package->retrievePackage();
			}
			// initialize address objects
			$this->shippingAddress = new address_old;
			$this->billingAddress = new address_old;
			// payment method object
			$this->paymentMethod = new paymentMethod;
			// order object
			$this->order = new orderProcessor;
			$this->order->associateOrder($this->memberID, $this->shippingAddress, $this->billingAddress, $this->paymentMethod, $this->package);
		} // function __construct

		/**
		 *  Unset uneeded data, set object to session, output debug info
		 *  Args: none
		 *  Return: none
		 */
		public function __destruct() {
			$_SESSION['user'] = &$this;
		} // function __destruct

		/**
		 *  Prep for object serialization
		 *  Args: none
		 *  Return: none
		 */
		public function __sleep() {
			return parent::__sleep();
		} // function __sleep

		/**
		 *  Restore objects, set page reference, clear temporary data on new page
		 *  Args: none
		 *  Return: none
		 */
		public function __wakeup() {
			parent::__wakeup();
		} // function __wakeup

		/**
		 *  Processes user input from submit action
		 *  Args: none
		 *  Returns: (boolean) input accepted
		 */
		public function processInput() {
			clearAllMessages();
			if (getRequest('accountInfo')) {
				if (getRequest('action') == 'login') {
					$login = getPost('login');
					$pass = getPost('pass');
					customerCore::initialize();
					customerCore::login($login, $pass);
				} else {
					$member = new member;
					$member->set('first', getPost('first'));
					$member->set('last', getPost('last'));
					$member->set('phone', getPost('phone'));
					$member->set('address1', getPost('address1'));
					$member->set('address2', getPost('address2'));
					$member->set('city', getPost('city'));
					$member->set('postal', getPost('postal'));
					$member->set('country', getPost('country'));
					$member->set('status', 'active');
					$member->set('password', getPost('password'));
					$password = $member->get('password');
					$confirmPassword = getPost('passwordConfirm');
					if ($password == $confirmPassword) {
						$passwordMatch = true;
					} else {
						$passwordMatch = false;
						addError('Your password confirmation does not match');
						addErrorField('password');
						addErrorField('passwordConfirm');
					}
					$state = getPost('state', 'alphanum');
					if ($state) {
						$member->set('state', getPost('state'));
					} else {
						$member->set('state', getPost('province'));
					}
					$email = getPost('email');
					if (validEmail($email)) {
						$member->set('email', getPost('email'));
					}
					if (!membersController::memberExists($email)) {
						$customerAgreement = getPost('customerAgreement');
						if ($customerAgreement) {
							if ($passwordMatch && $member->save()) {
								customerCore::setCore($member->get('memberID'));
								if (!$member->addSites(array(systemSettings::get('SITEID')))) {
									addError('There was an error while saving your account');
								}
								if (!$member->addGroups(array('1'))) {
									addError('There was an error while saving your account');
								}
								addSuccess('Thank you for signing up with '.systemSettings::get('SITENAME'));
								addSuccess('Your registration has been received and we will contact you shortly to complete the signup process');
								addSuccess('You can complete your current order and track it under this account');
								$template = new template;
								$template->assign('member', $member->fetchArray());
								$template->assign('password', $password);
								$template->registerCampaignResource();
								$mailer = new mailer;
								$mailer->setMessage('subject', $template->fetch('campaign:customerSignupAcknowledgement:subject'));
								$mailer->setMessage('from', $template->fetch('campaign:customerSignupAcknowledgement:from'));
								$mailer->setMessage('html', $template->fetch('campaign:customerSignupAcknowledgement:html'));
								$mailer->setMessage('text', $template->fetch('campaign:customerSignupAcknowledgement:text'));
								if ($mailer->composeMessage()) {
									$customerEmail = $member->get('email');
									if ($customerEmail) {
										$mailer->addRecipient($customerEmail);
									}
									$mailer->send();
								}
								$mailer = new mailer;
								$mailer->setMessage('subject', $template->fetch('campaign:customerSignupNotification:subject'));
								$mailer->setMessage('from', $template->fetch('campaign:customerSignupNotification:from'));
								$mailer->setMessage('html', $template->fetch('campaign:customerSignupNotification:html'));
								$mailer->setMessage('text', $template->fetch('campaign:customerSignupNotification:text'));
								if ($mailer->composeMessage()) {
									$adminEmails = systemSettings::get('ADMINEMAILS');
									foreach ($adminEmails as $email) {
										$mailer->addRecipient($email);
									}
									$mailer->send();
								}
							}
						} else {
							$member->assertRequired();
							addError('You must accept the customer agreement conditions in order to complete registration');
							addErrorField('customerAgreement');
						}
					} else {
						$member->assertRequired();
						addError('There is an existing account registered under your email address');
						$member->set('email', getPost('email'));
					}
					$_SESSION['checkout'] = array();
					$_SESSION['checkout']['member'] = $member;
					$_SESSION['checkout']['originalPassword'] = $originalPassword;
				}
			}
			if (getRequest('billInfo')) {
				$this->billingAddress->processForm('billing');
				if ($this->billingAddress->validAddress()) {
					if (getRequest('useForShipping')) {
						$this->shippingAddress->copyAddress($this->billingAddress);
						$this->shippingAddress->setAddressType('shipping');
						$this->shippingAddress->removeType('billing');
					}
				}
			}
			if (getRequest('paymentMethod')) {
				$this->paymentMethod->processForm();
				$this->paymentMethod->validMethod();
			}
			// shipping form must be present AND
			//   billing form not present OR billing form is present, but is not set as shipping address
			if (getRequest('shipInfo') && (!getRequest('billInfo') || (getRequest('billInfo') && !getRequest('useForShipping')))) {
				$this->shippingAddress->processForm('shipping');
				$this->shippingAddress->validAddress();
			}
			if (getRequest('orderInfo')) {
				$this->order->processForm();
				$this->order->validFormValues();
			}
			if (getRequest('createPackage')) {
				$this->package->createPackage();
				$this->order->generateShippingCost();
			} elseif (getRequest('p')) {
				$this->package->retrievePackage();
				$this->order->generateShippingCost();
			}
			// redirects failed checks
			if (haveErrorFields() || haveErrors()) {
				checkoutPath::removeCompleted($_SERVER['PHP_SELF']);
				return false;
			} else {
				$onPath = checkoutPath::onPath();
				if ($onPath){
					checkoutPath::setCompleted();
					checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));
					redirect(checkoutPath::$nextStep);
				}
				return true;
			}
		} // function processInput

		/**
		 *  Create a new package based on package content if one does not exist
		 *  Args: none
		 *  Return: none
		 */
		public function logPackage() {
			$this->package->logPackage();
		} // function logPackage

		/**
		 *  Return order object valid order check
		 *  Args: none
		 *  Return: (boolean) valid order
		 */
		public function validOrder() {
			if ($this->order->validOrder()) {
				return true;
			} else {
				return false;
			}
		} // function validOrder

		/**
		 *  Enter customer order
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function enterOrder() {
			if ($this->order->enterOrder()) {
				return true;
			} else {
				return false;
			}
		} // function enterOrder

		/**
		 *  Process user payment for an order
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function processPayment() {
			if ($this->order->authorizeTransaction()) {
				return true;
			} else {
				return false;
			}
		} // function processPayment

		/**
		 *  Clear order form
		 *  Args: none
		 *  Return: none
		 */
		public function clearOrder() {
			$this->order->clearOrder();
		} // function clearOrder

		/**
		 *  Save/update addresses
		 *  Args: none
		 *  Return: (boolean) success
		 */
		private function saveAddresses() {
			$billingSaved = false;
			$shippingSaved = false;
			if ($this->memberID && $this->billingAddress->validAddress() && $this->shippingAddress->validAddress()) {
				// compare billing and shipping addresses
				$billingIsShipping = true;
				foreach ($this->billingAddress->get('addressForm') as $key => $val) {
					if ($val != $this->shippingAddress->getArrayData('addressForm', $key)) {
						$billingIsShipping = false;
					}
				}
				if ($billingIsShipping) {
					// if the billing address is the same as the shipping address
					//   address record will retain the shipping address name
					//   billing address does not have a name
					//   (payment method name will be in the payment record)
					$this->shippingAddress->addType('billing');
					if ($this->shippingAddress->get('saveAddress') || systemSettings::get('FORCESAVESHIPPING')) {
						if (!$this->shippingAddress->get('addressID')) {
							if ($this->shippingAddress->saveAddress($this->memberID)) {
								$shippingSaved = true;
							}
						} else {
							if ($this->shippingAddress->updateAddress()) {
								$shippingSaved = true;
							}
						}
						if ($this->shippingAddress->get('defaultAddress')) {
							$this->dbh->query("UPDATE `customers` SET `shippingID` = '".$this->shippingAddress->get('addressID')."' WHERE `memberID` = '".$this->memberID."'");
						}
						$billingIsDefault = false;
						if ($this->billingAddress->get('defaultAddress')) {
							$billingIsDefault = true;
						}
						if ($this->billingAddress->loadAddress($this->shippingAddress->get('addressID'))) {
							$billingSaved = true;
						}
					}
				} else {
					if ($this->billingAddress->get('saveAddress') || systemSettings::get('FORCESAVEBILLING')) {
						if (!$this->billingAddress->get('addressID')) {
							if ($this->billingAddress->saveAddress($this->memberID)) {
								$billingSaved = true;
							}
						} else {
							if ($this->billingAddress->updateAddress()) {
								$billingSaved = true;
							}
						}
						if ($this->billingAddress->get('defaultAddress')) {
							$this->dbh->query("UPDATE `customers` SET `billingID` = '".$this->billingAddress->get('addressID')."' WHERE `memberID` = '".$this->memberID."'");
						}
					} else {
						$billingSaved = true;
					}
					if ($this->shippingAddress->get('saveAddress') || systemSettings::get('FORCESAVESHIPPING')) {
						if (!$this->shippingAddress->get('addressID')) {
							if ($this->shippingAddress->saveAddress($this->memberID)) {
								$shippingSaved = true;
							}
						} else {
							if ($this->shippingAddress->updateAddress()) {
								$shippingSaved = true;
							}
						}
						if ($this->shippingAddress->get('defaultAddress')) {
							$this->dbh->query("UPDATE `customers` SET `shippingID` = '".$this->shippingAddress->get('addressID')."' WHERE `memberID` = '".$this->memberID."'");
						}
					} else {
						$shippingSaved = true;
					}
				}
			}
			return ($billingSaved && $shippingSaved);
		} // function saveAddresses

	} // class user

?>
