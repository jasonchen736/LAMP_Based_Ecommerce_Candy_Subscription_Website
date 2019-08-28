<?

	require_once 'SwiftMailer/lib/Swift.php';
	require_once 'SwiftMailer/lib/Swift/Connection/SMTP.php';
	require_once 'SwiftMailer/lib/Swift/Plugin/Decorator.php';

	class subscription {

		// database object
		private $dbh;
		// email object
		private $mailer;
		// authorize object
		private $authorize;
		// shipment date entering reorder
		private $shipmentDate;
		// array of items ordered array([item id] => [name] [cost])
		private $orderItems;
		// associative array of index -> days mainly used in calculating next date
		private $dayNames = array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday'
		);
		// email messages
		private $messages;
		// log data
		private $logData = array(
			'ordersEntered'           => 0,
			'ccOrdersFound'           => 0,
			'ccOrdersCleared'         => 0,
			'ccTotalEmails'           => 0,
			'ccClearedEmailsSent'     => 0,
			'ccFailedEmailsSent'      => 0,
			'eCheckOrdersFound'       => 0,
			'eCheckOrdersCleared'     => 0,
			'eCheckTotalEmails'       => 0,
			'eCheckClearedEmailsSent' => 0,
			'eCheckFailedEmailsSent'  => 0,
			'checkOrdersFound'        => 0,
			'checkTotalEmails'        => 0,
			'checkEmailsSent'         => 0,
			'paidOrdersFound'         => 0,
			'paidTotalEmails'         => 0,
			'paidEmailsSent'          => 0,
			'conversionsCredited'     => 0,
			'subscriptionsUpdated'    => 0
		);

		/**
		 *  Instantiate database and authorize objects, and reorder shipment date
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			ini_set('memory_limit', '50M');
			$this->authorize = new authorize;
			$this->dbh = new database;
			$this->mailer = new Swift(new Swift_Connection_SMTP("localhost"));
			// shipment date entering reorder
			$this->shipmentDate = date('Y-m-d');
			// retrieve email messages
			$this->messages = array();
			$result = $this->dbh->query("SELECT `subject`, `html`, `text` FROM `campaigns` WHERE `campaignID` IN (0, 1, 2)");
			if (!$result->rowCount) {
				trigger_error('Error: Unable to retrieve subscription emails', E_USER_WARNING);
			} else {
				$emails = array();
				while ($row = $result->fetchAssoc()) {
					$emails[] = array('subject' => $row['subject'], 'html' => $row['html'], 'text' => $row['text']);
				}
				$this->messages['reorderSuccess'] = new Swift_Message($emails[0]['subject']);
				$this->messages['reorderSuccess']->attach(new Swift_Message_Part($emails[0]['text']));
				$this->messages['reorderSuccess']->attach(new Swift_Message_Part($emails[0]['html'], "text/html"));
				$this->messages['paymentDeclined'] = new Swift_Message($emails[1]['subject']);
				$this->messages['paymentDeclined']->attach(new Swift_Message_Part($emails[1]['text']));
				$this->messages['paymentDeclined']->attach(new Swift_Message_Part($emails[1]['html'], "text/html"));
				$this->messages['mailPayment'] = new Swift_Message($emails[2]['subject']);
				$this->messages['mailPayment']->attach(new Swift_Message_Part($emails[2]['text']));
				$this->messages['mailPayment']->attach(new Swift_Message_Part($emails[2]['html'], "text/html"));
			}
			// initialize log data
			foreach ($this->logData as $key => &$val) {
				$val = 0;
			}
			unset($val);
		} // class __construct

		/**
		 *  Log subscription data and disconnect mailer
		 *  Args: none
		 *  Return: none
		 */
		public function __destruct() {
			$this->logSubscriptionData();
			$this->mailer->disconnect();
		} // function __destruct

		/**
		 *  Logs current subscription data
		 *  Args: none
		 *  Return: none
		 */
		private function logSubscriptionData() {
			$queryVals = $this->logData;
			$queryVals['date'] = 'NOW()';
			$this->dbh->perform('subscriptionDataLog', $queryVals);
		} // function logSubscriptionData

		/**
		 *  Generate reorder receipt details
		 *  Args: (array) order record (from orders table)
		 *  Return: (array) [receipt details html, receipt details text]
		 */
		public function generateReceipt(&$orderArray) {
			if (is_array($orderArray) && !empty($orderArray)) {
				$receipt = '';
				$items = explode(';', $orderArray['content']);
				foreach ($items as $key => $val) {
					list($itemID, $quantity) = explode('-', $val);
					$receipt .= $this->orderItems[$itemID]['name'].' x '.$quantity.' ... $'.($quantity * $this->orderItems[$itemID]['cost']).'<br>';
				}
				$receipt .= 'Package Total: $'.$orderArray['totalCost'].'<br>';
				$totalCost = $orderArray['totalCost'];
				if ($orderArray['quantity'] > 1) {
					$receipt .= 'Packages Ordered: '.$orderArray['quantity'].'<br>';
					$totalCost *= $orderArray['quantity'];
				}
				if ($orderArray['shippingCost'] > 0) {
					$receipt .= 'Shipping Cost: $'.$orderArray['shippingCost'].'<br>';
					$totalCost += $orderArray['shippingCost'];
				}
				if ($orderArray['discount'] > 0) {
					$receipt .= 'Discount: $'.$orderArray['discount'].'<br>';
					$totalCost -= $orderArray['discount'];
				}
				$receipt .= 'Final Total: $'.$totalCost.'<br>';
				$receiptText = str_replace('<br>', "\r\n", $receipt);
				return array($receipt, $receiptText);
			} else {
				trigger_error('Reorder Error: Receipt components empty', E_USER_WARNING);
				return array(false, false);
			}
		} // function generateReceipt

		/**
		 *  Enters all reorders/reorder tracking records for subscriptions entering reorder
		 *    Generate array of all items ordered
		 *  Args: none
		 *  Return: none
		 */
		public function enterReorders() {
			// reorder query
			$sql = "INSERT INTO `orders` (`customerID`, `subscriptionID`, `packageID`, 
						`quantity`, `totalCost`, `shippingArrangement`, `shippingCost`, `promotionComboID`, 
						`discount`, `shippingID`, `shippingDate`, `billingID`, `paymentMethod`, `paymentCleared`, `orderDate`, 
						`orderStatus`, `lastModified`) 
					SELECT `customerID`, `subscriptionID`, `packageID`, `quantity`, `totalCost`, 
						`shippingArrangement`, `shippingCost`, `promotionComboID`, `subscriptionDiscount`, 
						`shippingID`, `nextShipmentDate`, `billingID`, `paymentMethod`, 
						IF(`payArrangement` = 'full' AND `paymentCleared` = 'cleared', 'cleared', 'no'), 
						NOW(), 'reorder', NOW() 
					FROM `subscriptions` 
					WHERE `subscriptionStatus` IN ('new', 'active') 
					AND `nextShipmentDate` = '".$this->shipmentDate."'
					AND `paymentCleared` != 'no'";
			$this->dbh->query($sql);
			$this->logData['ordersEntered'] += $this->dbh->rowCount;
			// tracking query
			$trackSql = "INSERT INTO `subscriptionReorderTracking` (`orderID`, `subscriptionID`, `orderDate`) 
							SELECT `orderID`, `subscriptionID`, `orderDate` 
							FROM `orders` 
							WHERE `orderStatus` = 'reorder' 
							AND `shippingDate` = '".$this->shipmentDate."'";
			$this->dbh->query($trackSql);
			// items query
			$itemSql = "SELECT `o`.`orderID`, `o`.`packageID`, `p`.`content` 
						FROM `orders` AS `o` 
						LEFT JOIN `packages` AS `p` USING (`packageID`) 
						WHERE `o`.`orderStatus` = 'reorder' 
						AND `shippingDate` = '".$this->shipmentDate."'";
			$result = $this->dbh->query($itemSql);
			if ($result->rowCount) {
				// populate array with item id keys
				$this->orderItems = array();
				while ($row = $result->fetchAssoc()) {
					if (!$row['content'] || !$row['packageID']) {
						// report errors
						if ($row['packageID']) {
							trigger_error('Reorder Error: Package information not found for '.$row['packageID'], E_USER_WARNING);
						} else {
							trigger_error('Reorder Error: Package missing for order '.$row['orderID'], E_USER_WARNING);
						}
					} else {
						// create id keys
						$content = explode(';', $row['content']);
						foreach ($content as $key => $val) {
							list($itemID, $quantity) = explode('-', $val);
							$this->orderItems[$itemID] = false;
						}
					}
				}
				// populate item names and cost
				if (!empty($this->orderItems)) {
					// retrieve item info for all items
					$items = '';
					foreach ($this->orderItems as $key => $val) {
						$items .= "'".prepDB($key)."', ";
					}
					$items = rtrim($items, ', ');
					$nameSql = "SELECT `productID`, `name`, `cost` FROM `products` WHERE `productID` IN (".$items.")";
					$itemResult = $this->dbh->query($nameSql);
					if ($itemResult->rowCount) {
						while ($row = $itemResult->fetchAssoc()) {
							// report errors
							if (!$row['name'] || !$row['cost']) {
								$msg = '';
								if (!$row['name']) $msg .= '[Name]';
								if (!$row['cost']) $msg .= '[Cost]';
								trigger_error('Product Error: Missing '.$msg.' for product '.$row['productID'], E_USER_WARNING);
							} else {
								// populate array
								$this->orderItems[$row['productID']] = array();
								$this->orderItems[$row['productID']]['name'] = $row['name'];
								$this->orderItems[$row['productID']]['cost'] = $row['cost'];
							}
						}
						// detect and handle unfound items
						foreach ($this->orderItems as $key => $val) {
							if (!$val) {
								trigger_error('Reorder Error: Product '.$key.' not found', E_USER_WARNING);
								unset($this->orderItems[$key]);
							}
						}
					} else {
						trigger_error('Reorder Error: Item results empty', E_USER_WARNING);
					}
				}
			}
		} // function enterReorders

		/**
		 *  Authorizes and clears current subscription reorders that pay via credit card
		 *  Args: none
		 *  Return: none
		 */
		public function clearCCReOrders() {
			$sql = "SELECT `s`.`subscriptionID`, `s`.`paymentMethod`, `s`.`customerID`, `o`.`orderID`, 
						`s`.`totalCost`, `s`.`subscriptionDiscount`, `b`.`billingID`, `b`.`cc`, 
						`b`.`expDate`, `b`.`first`, `b`.`last`, `b`.`address1`, 
						`b`.`city`, `b`.`state`, `b`.`postal`, `b`.`country`, 
						`p`.`content`, `p`.`packageID`, 
						IF(`b`.`email` != '', `b`.`email`, `c`.`email`) AS `email` 
					FROM `subscriptions` AS `s` 
					LEFT JOIN `orders` AS `o` ON (`o`.`subscriptionID` = `s`.`subscriptionID` 
										AND `o`.`shippingDate` = `s`.`nextShipmentDate` 
										AND `o`.`orderStatus` = 'reorder' 
										AND `o`.`paymentCleared` = 'no') 
					LEFT JOIN `customerBilling` AS `b` ON (`b`.`billingID` = `s`.`billingID`) 
					LEFT JOIN `packages` AS `p` ON (`o`.`packageID` = `p`.`packageID`) 
					LEFT JOIN `customers` AS `c` ON (`o`.`customerID` = `c`.`customerID`) 
					WHERE `s`.`paymentMethod` IN ('AMERICANEXPRESS','MASTERCARD','DISCOVER','VISA') 
					AND `s`.`nextShipmentDate` = '".$this->shipmentDate."' 
					AND `s`.`subscriptionStatus` IN ('new', 'active') 
					AND `s`.`payArrangement` = 'per shipment' 
					AND `s`.`paymentCleared` != 'no'";
			$result = $this->dbh->query($sql);
			$this->logData['ccOrdersFound'] += $this->dbh->rowCount;
			if ($result->rowCount) {
				// email lists, replacements arrays, duplicate email arrays
				// duplicate array structure: array(index => array(0 => [email], 1 => [replacements array]))
				$success = new Swift_RecipientList();
				$successReplacements = array();
				$duplicateSuccess = array();
				$declined = new Swift_RecipientList();
				$declinedReplacements = array();
				$duplicateDeclined = array();
				while ($row = $result->fetchAssoc()) {
					if (!$row['orderID'] || !$row['billingID'] || !$row['customerID'] || !$row['packageID']) {
						$msg = '';
						// process corrupted, no customer id
						if (!$row['customerID']) $msg .= '[Customer ID]';
						// process corrupted, no order id
						if (!$row['orderID']) $msg .= '[Order ID]';
						// process corrupted, no package id
						if (!$row['packageID']) $msg .= '[Package ID]';
						// process was corrupted, no billing id
						if (!$row['billingID']) $msg .= '[Billing ID]';
						trigger_error('Reorder Clearance Failed: Missing '.$msg.' for subscription '.$row['subscriptionID'].' on '.$this->shipmentDate, E_USER_WARNING);
					} else {
						$transaction = array(
							'x_type'           => 'AUTH_CAPTURE',
							'x_cust_id'        => $row['customerID'],
							'x_description'    => 'REORDER',
							'method'           => 'CC',
							'x_invoice_num'    => $row['orderID'],
							'x_card_num'       => $row['cc'],
							'x_exp_date'       => $row['expDate'],
							'x_card_code'      => '',
							'x_amount'         => $row['totalCost'] - $row['subscriptionDiscount'],
							'x_first_name'     => $row['first'],
							'x_last_name'      => $row['last'],
							'x_address'        => $row['address1'],
							'x_city'           => $row['city'],
							'x_state'          => $row['state'],
							'x_zip'            => $row['postal'],
							'x_country'        => $row['country'],
							'x_bank_aba_code'  => '',
							'x_bank_acct_num'  => '',
							'x_bank_acct_type' => '',
							'x_bank_name'      => '',
							'x_bank_acct_name' => ''
						);
						$response = $this->authorize->authorizeTransaction($transaction);
						if ($response['responseCode'] == 'Approved') {
							// log payment
							$this->logPayment($this->authorize->transactionRecordID, $row['subscriptionID'], $row['orderID'], $row['paymentMethod']);
							// clear order
							$this->clearOrderPayment($row['orderID']);
							if ($row['email']) {
								list($html, $text) = $this->generateReceipt(&$row);
								if (!array_key_exists($row['email'], $successReplacements)) {
									// add success replacement
									$successReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
									// add to success email list
									$success->addTo($row['email']);
								} else {
									// add to duplicate success array
									$duplicateSuccess[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
								}
								$this->logData['ccTotalEmails']++;
							}
							$this->logData['ccOrdersCleared']++;
						} else {
							// update subscription info
							$this->declineSubscriptionPayment($row['subscriptionID']);
							if ($row['email']) {
								list($html, $text) = $this->generateReceipt(&$row);
								if (!array_key_exists($row['email'], $declinedReplacements)) {
									// add declined replacement
									$declinedReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
									// add to declined email list
									$declined->addTo($row['email']);
								} else {
									// add to duplicate declined array
									$duplicateDeclined[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
								}
								$this->logData['ccTotalEmails']++;
							}
						}
					}
				}
				// send batch emails
				// successful reorders
				if (!empty($successReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($successReplacements), 'macros');
					$this->logData['ccClearedEmailsSent'] += $this->mailer->batchSend($this->messages['reorderSuccess'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateSuccess)) {
					foreach ($duplicateSuccess as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['ccClearedEmailsSent'] += $this->mailer->send($this->messages['reorderSuccess'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
				// declined payments
				if (!empty($declinedReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($declinedReplacements), 'macros');
					$this->logData['ccFailedEmailsSent'] += $this->mailer->batchSend($this->messages['paymentDeclined'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateDeclined)) {
					foreach ($duplicateDeclined as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['ccFailedEmailsSent'] += $this->mailer->send($this->messages['paymentDeclined'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
			}
		} // function clearCCReOrders

		/**
		 *  Authorizes and clears current subscription reorders that pay via eCheck
		 *  Args: none
		 *  Return: none
		 */
		public function cleareCheckReOrders() {
			$sql = "SELECT `s`.`subscriptionID`, `s`.`paymentMethod`, `s`.`customerID`, `o`.`orderID`, 
						`s`.`totalCost`, `s`.`subscriptionDiscount`, `b`.`billingID`, 
						`b`.`first`, `b`.`last`, `b`.`address1`, `b`.`city`, `b`.`state`, 
						`b`.`postal`, `b`.`country`, `b`.`bAccName`, `b`.`bAcc`, 
						`b`.`aba`, `b`.`bName`, `b`.`accType`, `p`.`content`, `p`.`packageID`, 
						IF(`b`.`email` != '', `b`.`email`, `c`.`email`) AS `email` 
					FROM `subscriptions` AS `s` 
					LEFT JOIN `orders` AS `o` ON (`o`.`subscriptionID` = `s`.`subscriptionID` 
										AND `o`.`shippingDate` = `s`.`nextShipmentDate` 
										AND `o`.`orderStatus` = 'reorder' 
										AND `o`.`paymentCleared` = 'no') 
					LEFT JOIN `customerBilling` AS `b` ON (`b`.`billingID` = `s`.`billingID`) 
					LEFT JOIN `packages` AS `p` ON (`o`.`packageID` = `p`.`packageID`) 
					LEFT JOIN `customers` AS `c` ON (`o`.`customerID` = `c`.`customerID`) 
					WHERE `s`.`paymentMethod` = 'echeck' 
					AND `s`.`nextShipmentDate` = '".$this->shipmentDate."' 
					AND `s`.`subscriptionStatus` IN ('new', 'active') 
					AND `s`.`payArrangement` = 'per shipment' 
					AND `s`.`paymentCleared` != 'no'";
			$result = $this->dbh->query($sql);
			$this->logData['eCheckOrdersFound'] += $this->dbh->rowCount;
			if ($result->rowCount) {
				// email lists, replacements arrays, duplicate email arrays
				// duplicate array structure: array(index => array(0 => [email], 1 => [replacements array]))
				$success = new Swift_RecipientList();
				$successReplacements = array();
				$duplicateSuccess = array();
				$declined = new Swift_RecipientList();
				$declinedReplacements = array();
				$duplicateDeclined = array();
				while ($row = $result->fetchAssoc()) {
					if (!$row['orderID'] || !$row['billingID']) {
						$msg = '';
						// process corrupted, no customer id
						if (!$row['customerID']) $msg .= '[Customer ID]';
						// process corrupted, no order id
						if (!$row['orderID']) $msg .= '[Order ID]';
						// process corrupted, no package id
						if (!$row['packageID']) $msg .= '[Package ID]';
						// process was corrupted, no billing id
						if (!$row['billingID']) $msg .= '[Billing ID]';
						trigger_error('Reorder Clearance Failed: Missing '.$msg.' for subscription '.$row['subscriptionID'].' on '.$this->shipmentDate, E_USER_WARNING);
					} else {
						$transaction = array(
							'x_type'           => 'AUTH_CAPTURE',
							'x_cust_id'        => $row['customerID'],
							'x_description'    => 'REORDER',
							'method'           => 'ECHECK',
							'x_invoice_num'    => $row['orderID'],
							'x_card_num'       => '',
							'x_exp_date'       => '',
							'x_card_code'      => '',
							'x_amount'         => $row['totalCost'] - $row['subscriptionDiscount'],
							'x_first_name'     => $row['first'],
							'x_last_name'      => $row['last'],
							'x_address'        => $row['address1'],
							'x_city'           => $row['city'],
							'x_state'          => $row['state'],
							'x_zip'            => $row['postal'],
							'x_country'        => $row['country'],
							'x_bank_aba_code'  => $row['aba'],
							'x_bank_acct_num'  => $row['bAcc'],
							'x_bank_acct_type' => $row['accType'],
							'x_bank_name'      => $row['bName'],
							'x_bank_acct_name' => $row['bAccName']
						);
						$response = $this->authorize->authorizeTransaction($transaction);
						if ($response['responseCode'] == 'Approved') {
							// log payment
							$this->logPayment($authorize->transactionRecordID, $row['subscriptionID'], $row['orderID'], $row['paymentMethod']);
							// clear order
							$this->clearOrderPayment($row['orderID']);
							if ($row['email']) {
								list($html, $text) = $this->generateReceipt(&$row);
								if (!array_key_exists($row['email'], $successReplacements)) {
									// add success replacement
									$successReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
									// add to success email list
									$success->addTo($row['email']);
								} else {
									// add to duplicate success array
									$duplicateSuccess[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
								}
								$this->logData['eCheckTotalEmails']++;
							}
							$this->logData['eCheckOrdersCleared']++;
						} else {
							// update subscription info
							$this->declineSubscriptionPayment($row['subscriptionID']);
							if ($row['email']) {
								list($html, $text) = $this->generateReceipt(&$row);
								if (!array_key_exists($row['email'], $declinedReplacements)) {
									// add declined replacement
									$declinedReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
									// add to declined email list
									$declined->addTo($row['email']);
								} else {
									// add to duplicate declined array
									$duplicateDeclined[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
								}
								$this->logData['eCheckTotalEmails']++;
							}
						}
					}
				}
				// send batch emails
				// successful reorders
				if (!empty($successReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($successReplacements), 'macros');
					$this->logData['eCheckClearedEmailsSent'] += $this->mailer->batchSend($this->messages['reorderSuccess'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateSuccess)) {
					foreach ($duplicateSuccess as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['eCheckClearedEmailsSent'] += $this->mailer->send($this->messages['reorderSuccess'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
				// declined payments
				if (!empty($declinedReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($declinedReplacements), 'macros');
					$this->logData['eCheckFailedEmailsSent'] += $this->mailer->batchSend($this->messages['paymentDeclined'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateDeclined)) {
					foreach ($duplicateDeclined as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['eCheckFailedEmailsSent'] += $this->mailer->send($this->messages['paymentDeclined'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
			}
		} // function cleareCheckReOrders

		/**
		 *  Sends notification to customers who opted to pay with check/money order
		 *    and have not paid in full
		 *  Args: none
		 *  Return: none
		 */
		public function notifyCheckSubscriptions() {
			$sql = "SELECT `o`.`subscriptionID`, `o`.`orderID`, `o`.`totalCost`, `o`.`discount`, 
						IF(`o`.`billingID` != 0, `b`.`first`, `c`.`first`) AS `first`, 
						IF(`o`.`billingID` != 0 AND `b`.`email` != '', `b`.`email`, `c`.`email`) AS `email`, 
						`p`.`content`, `p`.`packageID`, `c`.`customerID`, `b`.`billingID` 
					FROM `orders` AS `o` 
					LEFT JOIN `customers` AS `c` ON (`o`.`customerID` = `c`.`customerID`) 
					LEFT JOIN `customerBilling` AS `b` ON (`o`.`billingID` = `b`.`billingID`) 
					LEFT JOIN `packages` AS `p` ON (`o`.`packageID` = `p`.`packageID`) 
					WHERE `o`.`paymentMethod` IN ('checkmoneyorder') 
					AND `o`.`shippingDate` = '".$this->shipmentDate."' 
					AND `o`.`orderStatus` = 'reorder' 
					AND `o`.`paymentCleared` != 'no'";
			$result = $this->dbh->query($sql);
			$this->logData['checkOrdersFound'] += $this->dbh->rowCount;
			if ($result->rowCount) {
				// email lists, replacements arrays, duplicate email arrays
				// duplicate array structure: array(index => array(0 => [email], 1 => [replacements array]))
				$success = new Swift_RecipientList();
				$successReplacements = array();
				$duplicateSuccess = array();
				while ($row = $result->fetchAssoc()) {
					if ((systemSettings::get('FORCESAVEBILLING') && !$row['billingID']) || !$row['customerID'] || !$row['packageID'] || !$row['orderID']){
						$msg = '';
						// process corrupted, no customer id
						if (!$row['customerID']) $msg .= '[Customer ID]';
						// process corrupted, no order id
						if (!$row['orderID']) $msg .= '[Order ID]';
						// process corrupted, no package id
						if (!$row['packageID']) $msg .= '[Package ID]';
						// process corrupted, no billing id
						if(systemSettings::get('FORCESAVEBILLING') && !$row['billingID']) $msg .= '[Billing ID]';
						trigger_error('Reorder Payment Notification Failed: Missing '.$msg.' for subscription '.$row['subscriptionID'].' on '.$this->shipmentDate, E_USER_WARNING);
					} else {
						if ($row['email']) {
							// create receipt and email to customer
							list($html, $text) = $this->generateReceipt(&$row);
							if (!array_key_exists($row['email'], $successReplacements)) {
								// add success replacement
								$successReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
								// add to success email list
								$success->addTo($row['email']);
							} else {
								// add to duplicate success array
								$duplicateSuccess[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
							}
							$this->logData['checkTotalEmails']++;
						}
					}
				}
				// send batch emails
				// reorder payment notices
				if (!empty($successReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($successReplacements), 'macros');
					$this->logData['checkEmailsSent'] += $this->mailer->batchSend($this->messages['mailPayment'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateSuccess)) {
					foreach ($duplicateSuccess as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['checkEmailsSent'] += $this->mailer->send($this->messages['mailPayment'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
			}
		} // function notifyCheckSubscriptions


		/**
		 *  Creates reorder receipt email for paid in full subscriptions
		 *  Args: none
		 *  Return: none
		 */
		public function notifyPaidInFull() {
			$sql = "SELECT `o`.`subscriptionID`, `o`.`orderID`, `c`.`first`, 
						`c`.`email`, `p`.`content`, `p`.`packageID`, `c`.`customerID` 
					FROM `subscriptions` AS `s` 
					LEFT JOIN `orders` AS `o` ON (`o`.`subscriptionID` = `s`.`subscriptionID` 
						AND `o`.`shippingDate` = `s`.`nextShipmentDate` 
						AND `o`.`orderStatus` = 'reorder' 
						AND `o`.`paymentCleared` = 'cleared') 
					LEFT JOIN `customers` AS `c` ON (`o`.`customerID` = `c`.`customerID`) 
					LEFT JOIN `packages` AS `p` ON (`o`.`packageID` = `p`.`packageID`) 
					WHERE `s`.`payArrangement` = 'full' 
					AND `s`.`nextShipmentDate` = '".$this->shipmentDate."' 
					AND `s`.`subscriptionStatus` IN ('new', 'active') 
					AND `s`.`paymentCleared` = 'cleared'";
			$result = $this->dbh->query($sql);
			$this->logData['paidOrdersFound'] += $this->dbh->rowCount;
			if ($result->rowCount) {
				// email lists, replacements arrays, duplicate email arrays
				// duplicate array structure: array(index => array(0 => [email], 1 => [replacements array]))
				$success = new Swift_RecipientList();
				$successReplacements = array();
				$duplicateSuccess = array();
				while ($row = $result->fetchAssoc()) {
					if (!$row['customerID'] || !$row['packageID'] || !$row['orderID']){
						$msg = '';
						// process corrupted, no customer id
						if (!$row['customerID']) $msg .= '[Customer ID]';
						// process corrupted, no order id
						if (!$row['orderID']) $msg .= '[Order ID]';
						// process corrupted, no package id
						if (!$row['packageID']) $msg .= '[Package ID]';
						trigger_error('Reorder Payment Notification Failed: Missing '.$msg.' for subscription '.$row['subscriptionID'].' on '.$this->shipmentDate, E_USER_WARNING);
					} else {
						if ($row['email']) {
							// create receipt and email to customer
							list($html, $text) = $this->generateReceipt(&$row);
							if (!array_key_exists($row['email'], $successReplacements)) {
								// add success replacement
								$successReplacements[$row['email']] = array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text);
								// add to success email list
								$success->addTo($row['email']);
							} else {
								// add to duplicate success array
								$duplicateSuccess[] = array($row['email'] => array('~~RECEIPTHTML~~' => $html, '~~RECEIPTTEXT~~' => $text));
							}
							$this->logData['paidTotalEmails']++;
						}
					}
				}
				// send batch emails
				// successful reorders
				if (!empty($successReplacements)) {
					$this->mailer->attachPlugin(new Swift_Plugin_Decorator($successReplacements), 'macros');
					$this->logData['paidEmailsSent'] += $this->mailer->batchSend($this->messages['reorderSuccess'], $success, SUBSCRIPTIONEMAIL);
				}
				if (!empty($duplicateSuccess)) {
					foreach ($duplicateSuccess as $key => $val) {
						$this->mailer->attachPlugin(new Swift_Plugin_Decorator($val), 'macros');
						$this->logData['paidEmailsSent'] += $this->mailer->send($this->messages['reorderSuccess'], key($val), SUBSCRIPTIONEMAIL);
					}
				}
			}
		} // function notifyPaidInFull

		/**
		 *  Set order payment status to cleared
		 *  Args: (int) Order id
		 *  Return: none
		 */
		private function clearOrderPayment($id) {
			$this->dbh->query("UPDATE `orders` SET `paymentCleared` = 'cleared', `lastModified` = NOW() WHERE `orderID` = '".prepDB($id)."'");
		} // function clearOrderPayment

		/**
		 *  Set subscrition payment status to no, payment declined
		 *  Args: (int) Subscription id
		 *  Return: none
		 */
		private function declineSubscriptionPayment($id) {
			$this->dbh->query("UPDATE `subscriptions` SET `paymentCleared` = 'no', `subscriptionStatus` = 'paymentdeclined', `lastModified` = NOW() WHERE `subscriptionID` = '".prepDB($id)."'");
			$this->logData['subscriptionsUpdated']++;
		} // function declineSubscriptionPayment

		/**
		 *  Log payment record
		 *  Args: (str) transaction id, subscription id, order id, payment method
		 *  Return: none
		 */
		private function logPayment($transactionID, $subscriptionID, $orderID, $paymentMethod) {
			$queryVals = array(
				'subscriptionID'      => prepDB($subscriptionID),
				'orderID'             => prepDB($orderID),
				'transactionRecordID' => prepDB($transactionID),
				'~method'             => prepDB($paymentMethod),
				'datePosted'          => 'NOW()',
				'lastModified'        => 'NOW()'
			);
			$this->dbh->perform('paymentLog', $queryVals);
		} // function logPayment

		/**
		 *  Enter cleared orders into order reference record
		 *  Args: (str) Referral type
		 *  Return: none
		 */
		private function logOrderReference($type) {
			// all order for which a recurring payout exists
			//   from (affiliate/customer recurring payout table)
			//   to (affiliate/customer order reference table)
			//     all non affiliate referrals go to customer table
			if ($type == 'affiliate') {
				$payoutTable = 'affiliateRecurringPayouts';
				$refTable = 'affiliateOrderReference';
			} elseif ($type == 'customer') {
				$payoutTable = 'recurringPayouts';
				$refTable = 'orderReference';
			} else {
				trigger_error('Subscription Object - logOrderReference: Invalid referral type', E_USER_NOTICE);
				return;
			}
			$sql = "INSERT INTO `".$refTable."` (`ID`, `subID`, `offerID`, `campaignID`, 
						`passThroughVariable`, `orderID`, `subscriptionID`, `payoutID`, `IP`, `orderDate`) 
					SELECT `p`.`ID`, `p`.`subID`, `p`.`offerID`, `p`.`campaignID`, 
						`p`.`passThroughVariable`, `o`.`orderID`, `o`.`subscriptionID`, 
						`p`.`payoutID`, '".$REMOTE_ADDR."', `o`.`orderDate` 
					FROM `orders` AS `o` 
					JOIN `".$payoutTable."` AS `p` ON (`o`.`subscriptionID` = `p`.`subscriptionID`) 
					WHERE `o`.`shippingDate` = '".$this->shipmentDate."' 
					AND `o`.`orderStatus` = 'reorder' 
					AND `o`.`paymentCleared` = 'cleared'";
			$this->dbh->query($sql);
		} // function logOrderReference

		/**
		 *  Log cleared order conversions into tracking tables
		 *  Args: (str) Referral type
		 *  Return: none
		 */
		private function logConversion($type) {
			// all order for which a recurring payout exists
			//   from (affiliate/customer recurring payout table)
			//   to (affiliate/customer tracking table)
			//     all non affiliate referrals go to customer table
			if ($type == 'affiliate') {
				$payoutTable = 'affiliateRecurringPayouts';
				$table = 'affiliateTracking';
			} elseif ($type == 'customer') {
				$payoutTable = 'recurringPayouts';
				$table = 'tracking';
			} else {
				trigger_error('Subscription Object - logConversion: Invalid referral type', E_USER_NOTICE);
				return;
			}
			$sql = "INSERT INTO `".$table."` (`ID`, `subID`, `campaignID`, `offerID`, 
						`payoutID`, `date`, `conversions`) 
					SELECT `p`.`ID`, `p`.`subID`, `p`.`campaignID`, `p`.`offerID`, 
						`p`.`payoutID`, CURDATE(), 1 
					FROM `orders` AS `o` 
					JOIN `".$payoutTable."` AS `p` ON (`o`.`subscriptionID` = `p`.`subscriptionID`) 
					WHERE `o`.`shippingDate` = '".$this->shipmentDate."' 
					AND `o`.`orderStatus` = 'reorder' 
					AND `o`.`paymentCleared` = 'cleared' 
					ON DUPLICATE KEY UPDATE `conversions` = `conversions` + 1";
			$this->dbh->query($sql);
			// count conversions
			$countSQL = "SELECT COUNT(*) AS `conversions` 
						FROM `orders` AS `o` 
						JOIN `".$payoutTable."` AS `p` ON (`o`.`subscriptionID` = `p`.`subscriptionID`) 
						WHERE `o`.`shippingDate` = '".$this->shipmentDate."' 
						AND `o`.`orderStatus` = 'reorder' 
						AND `o`.`paymentCleared` = 'cleared'";
			$result = $this->dbh->query($countSQL);
			$conversions = $result->fetchAssoc();
			$conversions = $conversions['conversions'];
			$this->logData['conversionsCredited'] += $conversions;
		} // function logConversion

		/**
		 *  Update successfully reordered subscriptions with next reorder date, remaining reorders, 
		 *    last modified date
		 *  Performs inventory accounting and package/product tracking
		 *  Args: none
		 *  Return: none
		 */
		public function updateReorderedSubscriptions() {
			$sql = "SELECT `s`.`subscriptionID`, `s`.`remainingShipments` - 1 AS `remainingShipments`, 
					`s`.`shippingInterval`, `s`.`terminationDate` 
					FROM `subscriptions` AS `s` 
					JOIN `orders` AS `o` ON (`s`.`subscriptionID` = `o`.`subscriptionID`
												AND `o`.`shippingDate` = `s`.`nextShipmentDate` 
												AND `o`.`orderStatus` = 'reorder' 
												AND `o`.`paymentCleared` = 'cleared') 
					WHERE `s`.`nextShipmentDate` = '".$this->shipmentDate."' 
					AND `s`.`subscriptionStatus` IN ('new', 'active')";
			$result = $this->dbh->query($sql);
			if ($result->rowCount) {
				while ($row = $result->fetchAssoc()) {
					$nextDate = $this->generateNextDate($this->shipmentDate, $row['terminationDate'], $row['shippingInterval']);
					if ($nextDate) {
						$this->dbh->query("UPDATE `subscriptions` SET `remainingShipments` = '".$row['remainingShipments']."', `nextShipmentDate` = '".$nextDate."', `lastModified` = NOW() WHERE `subscriptionID` = '".$row['subscriptionID']."'");
						$this->logData['subscriptionsUpdated']++;
					} else {
						$this->dbh->query("UPDATE `subscriptions` SET `remainingShipments` = '".$row['remainingShipments']."', `subscriptionStatus` = 'expired', `lastModified` = NOW() WHERE `subscriptionID` = '".$row['subscriptionID']."'");
						$this->logData['subscriptionsUpdated']++;
					}
				}
			}
			// update package order counts
			$packageSql = "INSERT INTO `packageTrack` (`packageID`, `orders`, `dateOrdered`) 
							SELECT `packageID`, `quantity`, '".$this->shipmentDate."' 
							FROM `orders` 
							WHERE `shippingDate` = '".$this->shipmentDate."' 
							AND `orderStatus` = 'reorder' 
							AND `paymentCleared` = 'cleared' 
							ON DUPLICATE KEY UPDATE `orders` = `orders` + VALUES(`orders`)";
			$this->dbh->query($packageSql);
			// update product order counts and product and package inventory and availability
			// items query
			$itemSql = "SELECT `o`.`packageID`, `o`.`quantity`, `p`.`content` 
						FROM `orders` AS `o` 
						LEFT JOIN `packages` AS `p` USING (`packageID`) 
						WHERE `o`.`orderStatus` = 'reorder' 
						AND `shippingDate` = '".$this->shipmentDate."' 
						AND `paymentCleared` = 'cleared'";
			$result = $this->dbh->query($itemSql);
			if ($result->rowCount) {
				// populate array with item ids and quantities cleared
				$clearedItems = array();
				while ($row = $result->fetchAssoc()) {
					$content = explode(';', $row['content']);
					foreach ($content as $key => $val) {
						list($itemID, $contentQuantity) = explode('-', $val);
						if (!array_key_exists($itemID, $clearedItems)) {
							$clearedItems[$itemID] = 0;
						}
						$clearedItems[$itemID] += $row['quantity'] * $contentQuantity;
					}
				}
				// database insert values
				$inventoryTrack = '';
				$clearedInventory = '';
				$items = '';
				foreach ($clearedItems as $key => $val) {
					$inventoryTrack .= "('".$key."', '".$val."', '".$this->shipmentDate."'), ";
					$clearedInventory .= "('".$key."', '".$val."'), ";
					$items .= "'".$key."', ";
				}
				$inventoryTrack = rtrim($inventoryTrack, ', ');
				$clearedInventory = rtrim($clearedInventory, ', ');
				$items = rtrim($items, ', ');
				// update product order counts
				$this->dbh->query("INSERT INTO `productTrack` (`productID`, `orders`, `dateOrdered`) VALUES ".$inventoryTrack." 
									ON DUPLICATE KEY UPDATE `orders` = `orders` + VALUES(`orders`)");
				// update product inventory
				$this->dbh->query("INSERT INTO `products` (`productID`, `quantity`) VALUES ".$clearedInventory." 
									ON DUPLICATE KEY UPDATE `quantity` = `quantity` - VALUES(`quantity`)");
				// retrieve updated inventory count
				$result = $this->dbh->query("SELECT `productID` FROM `products` 
												WHERE `productID` IN (".$items.") 
												AND `quantity` <= 0 
												AND `availability` NOT IN ('alwaysavailable', 'outofstock')");
				if ($result->rowCount) {
					$itemsOut = '';
					while ($row = $result->fetchAssoc()) {
						$itemsOut .= "'".$row['productID']."', ";
					}
					$itemsOut = rtrim($itemsOut, ', ');
					// switch items off available
					if ($itemsOut) {
						$this->dbh->query("UPDATE `products` SET `availability` = 'outofstock', `lastModified` = NOW() 
											WHERE `productID` IN (".$itemsOut.") 
											AND `availability` != 'alwaysavailable'");
						// switch packages off available if need
						$this->dbh->query("UPDATE `packages` SET `availability` = 'outofstock', `lastModified` = NOW() 
											WHERE `packageID` IN (SELECT `packageID` FROM `productToPackage` WHERE `productID` IN (".$itemsOut.")) 
											AND `availability` != 'alwaysavailable'");
					}
				}
			}
		} // function updateReorderedSubscriptions

		/**
		 *  Parses subscription delivery interval string
		 *    and generates the next shipping date
		 *  Args: (str) start date, end date, interval storage string from subscriptions table
		 *  Regurn: (str) next shipment date or false
		 */
		function generateNextDate($startDate, $endDate, $intervalStr) {
			$method = substr($intervalStr, 0, 1);
			$endDate = strtotime($endDate);
			switch ($method) {
				case 1:
					$monthToggles = substr($intervalStr, 7, 12);
					if ($monthToggles > 0) {
						$weekToggles = substr($intervalStr, 19, 5);
						if ($weekToggles > 0) {
							$dayToggles = substr($intervalStr, 24, 7);
							if ($dayToggles > 0) {
								// specific days of specific weeks of specific months
								$nextDate      = strtotime('1 day', strtotime($startDate));
								$firstOf       = strtotime(date('Y-m-01', $nextDate));
								$firstDay      = date('w', $firstOf);
								$startMonth    = date('m', $nextDate);
								$startWeek     = 1;
								$startWeekCalc = strtotime('next '.$this->dayNames[$firstDay], $firstOf);
								while ($startWeekCalc <= $nextDate) {
									$startWeek++;
									$startWeekCalc = strtotime('next '.$this->dayNames[$firstDay], $startWeekCalc);
								}
								$startDay = date('w', $nextDate);
								while ($nextDate <= $endDate) {
									// subtract 1 to sync index with month
									for ($i = $startMonth - 1; $i < 12; $i++) {
										if ($monthToggles[$i]) {
											for ($j = $startWeek - 1; $j < 5; $j++) {
												if ($weekToggles[$j]) {
													// does not actually break on 7
													//    stops when reaches again the 1st day of month to correctly calculate 1st _ day of month, etc.
													//    note: 1st day of month (sat, sun) is different from 1st of the month
													for ($k = $startDay; $k < 7; $k++) {
														if ($dayToggles[$k] && $nextDate <= $endDate) {
															return date('Y-m-d', $nextDate);
														} elseif ($nextDate > $endDate) return false;
														$nextDate = strtotime('1 day', $nextDate);
														// three conditions to break - different month, exceed end date, reaches 1st day of starting week again
														if (date('m', $nextDate) != $i + 1 || $nextDate > $endDate || date('w', $nextDate) == $firstDay) break;
														elseif ($k == 6) $k = -1; // -1 to negate increment at head of loop
													}
													// begin again on the day we left off
													$startDay = date('w', $nextDate);
												} else {
													$nextDate = strtotime('next '.$this->dayNames[$firstDay], $nextDate);
													// start date must be reset each time to coordinate correct starting position
													$startDay = $firstDay;
												}
												if (date('m', $nextDate) != $i + 1) {
													// set to first of next month to set accurate start date and first day
													$nextDate = strtotime(date('Y-m-01', $nextDate));
													break;
												} elseif ($nextDate > $endDate) break;
											}
											// if last of month selected, backtrack to 1 week before month ends
											//   to ensure all last dates are added
											if ($weekToggles[4] && $nextDate <= $endDate) {
												// make sure this is the beginning of the month, so that we can backtrack to previous month
												if (date('j', $nextDate) != 1) {
													$nextDate = strtotime('next month', strtotime(date('Y-m-01', $nextDate)));
												}
												// set start day of backtrack to one after the second to last occurrence of last day in month
												$backTrackStart = date('w', strtotime('-1 day', $nextDate)) + 1;
												// if the previous was saturday (6), next day is sunday (0), not 7
												if ($backTrackStart == 7) $backTrackStart = 0;
												// backtrack to last occurrence of last day of month
												$backTrack = strtotime('last '.$this->dayNames[$backTrackStart], $nextDate);
												// actually breaks when back track date reaches next date
												for ($l = $backTrackStart; $l < 7; $l++) {
													// if day is registered
													if ($dayToggles[$l] && $backTrack <= $endDate && $backTrack > strtotime($startDate)) {
														return date('Y-m-d', $backTrack);
													} elseif ($backTrack > $endDate) return false;
													$backTrack = strtotime('1 day', $backTrack);
													if ($backTrack >= $nextDate) break;
													elseif ($l == 6) $l = -1; // -1 to negate increment at head of loop
												}
											}
										} else {
											$nextDate = strtotime(date('Y-m-01', strtotime('next month', $nextDate)));
										}
										if ($nextDate > $endDate) break;
										else {
											$startWeek = 1;
											// start day and first day must be recalculated to correct first day of month
											//   note first day of month is not the 1st, but rahter a day (sat, sun)
											$startDay  = date('w', $nextDate);
											$firstDay  = date('w', $nextDate);
										}
									}
									$startMonth = 1;
								}
							} else {
								// sundays of specific weeks of specific months
								$nextDate = strtotime('next sunday', strtotime($startDate));
								$startMonth = date('m', $nextDate);
								$startWeek = 1;
								$startWeekCalc = date('w', strtotime(date('Y-m-01', $nextDate))) == 0 ? strtotime('next sunday', strtotime(date('Y-m-01', $nextDate))) : strtotime('next sunday', strtotime('next sunday', strtotime(date('Y-m-01', $nextDate))));
								while ($startWeekCalc <= $nextDate) {
									$startWeek++;
									$startWeekCalc = strtotime('next sunday', $startWeekCalc);
								}
								while ($nextDate <= $endDate) {
									// subtract 1 to sync index with month
									for ($i = $startMonth - 1; $i < 12; $i++) {
										if ($monthToggles[$i]) {
											for ($j = $startWeek - 1; $j < 5; $j++) {
												if ($weekToggles[$j] && $nextDate <= $endDate) {
													return date('Y-m-d', $nextDate);
												} elseif ($nextDate > $endDate) return false;
												$nextDate = strtotime('next sunday', $nextDate);
												if (date('m', $nextDate) != $i + 1) {
													// for months with only 4 sundays, this counts as last week of the month
													// disregard if 4th week of month is active as well
													if (!$weekToggles[3] && $weekToggles[4]) {
														if ($nextDate <= $endDate) {
															return date('Y-m-d', strtotime('last sunday', $nextDate));
														} else return false;
													}
													break;
												}
											}
										} else {
											$nextDate = date('w', strtotime(date('Y-m-01', strtotime('next month', $nextDate)))) == 0 ? strtotime(date('Y-m-01', strtotime('next month', $nextDate))) : strtotime('next sunday', strtotime(date('Y-m-01', strtotime('next month', $nextDate))));
										}
										$startWeek = 1;
										if ($nextDate > $endDate) break;
									}
									$startMonth = 1;
								}
							}
						} else {
							// beginning of specific months only
							$nextDate = strtotime(date('Y-m-01', strtotime('next month', strtotime($startDate))));
							$startMonth = date('m', strtotime('next month', strtotime($startDate)));
							while ($nextDate <= $endDate) {
								// subtract 1 to sync index with month
								for ($i = $startMonth - 1; $i < 12; $i++) {
									if ($monthToggles[$i] && $nextDate <= $endDate) {
										return date('Y-m-d', $nextDate);
									} elseif ($nextDate > $endDate) return false;
									$nextDate = strtotime(date('Y-m-01', strtotime('next month', $nextDate)));
								}
								$startMonth = 1;
							}
						}
					}
					break;
				case 2:
					$monthToggles = substr($intervalStr, 7, 12);
					if ($monthToggles > 0) {
						$dateToggles = substr($intervalStr, 31, 31);
						if ($dateToggles > 0) {
							// specific dates of specific months
							$nextDate = strtotime('1 day', strtotime($startDate));
							$startMonth = date('m', strtotime('1 day', strtotime($startDate)));
							$startDate = date('d', strtotime('1 day', strtotime($startDate)));
							while ($nextDate <= $endDate) {
								// subtract 1 to sync index with month
								for ($i = $startMonth - 1; $i < 12; $i++) {
									if ($monthToggles[$i]) {
										// subtract 1 to sync index with date
										for ($j = $startDate - 1; $j < 31; $j++) {
											if ($dateToggles[$j] && $nextDate <= $endDate) {
												return date('Y-m-d', $nextDate);
											} elseif ($nextDate > $endDate) return false;
											$nextDate = strtotime('1 day', $nextDate);
											if (date('m', $nextDate) != $i + 1 || $nextDate > $endDate) break;
										}
									} else {
										$nextDate = strtotime(date('Y-m-01', strtotime('next month', $nextDate)));
									}
									$startDate = 1;
									if ($nextDate > $endDate) break;
								}
								$startMonth = 1;
							}
						} else {
							// beginning of specific months only
							$nextDate = strtotime(date('Y-m-01', strtotime('next month', strtotime($startDate))));
							$startMonth = date('m', strtotime('next month', strtotime($startDate)));
							while ($nextDate <= $endDate) {
								// subtract 1 to sync index with month
								for ($i = $startMonth - 1; $i < 12; $i++) {
									if ($monthToggles[$i] && $nextDate <= $endDate) {
										return date('Y-m-d', $nextDate);
									} elseif ($nextDate > $endDate) return false;
									$nextDate = strtotime(date('Y-m-01', strtotime('next month', $nextDate)));
								}
								$startMonth = 1;
							}
						}
					}
					break;
				default:
					$shipInterval = '';
					if (substr($intervalStr, 1, 2) > 0) $shipInterval .= substr($intervalStr, 1, 2).' month ';
					if (substr($intervalStr, 3, 2) > 0) $shipInterval .= substr($intervalStr, 3, 2).' week ';
					if (substr($intervalStr, 5, 2) > 0) $shipInterval .= substr($intervalStr, 5, 2).' day ';
					$shipInterval = trim($shipInterval);
					if ($shipInterval) {
						$nextDate = strtotime($shipInterval, strtotime($startDate));
						if ($nextDate <= $endDate) {
							return date('Y-m-d', $nextDate);
						} else return false;
					}
					break;
			}
			return false;
		} // function generateNextDate

	} // class subscription

?>