<?

	class order extends activeRecord {
		// active record table
		protected $table = 'orders';
		// existing auto increment field
		protected $autoincrement = 'orderID';
		// history table (optional)
		protected $historyTable = 'ordersHistory';
		// array unique id fields
		protected $idFields = array(
			'orderID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'orderID' => array('orderID', 'integer', 0, 11),
			'memberID' => array('memberID', 'integer', 0, 10),
			'subscriptionID' => array('subscriptionID', 'integer', 0, 11),
			'packageID' => array('packageID', 'integer', 1, 11),
			'quantity' => array('quantity', 'integer', 1, 10),
			'totalCost' => array('totalCost', 'double', 0, 10),
			'shippingArrangement' => array('shippingArrangement', 'integer', 0, 10),
			'shippingCost' => array('shippingCost', 'double', 0, 8),
			'promotionComboID' => array('promotionComboID', 'integer', 0, 10),
			'discount' => array('discount', 'double', 0, 10),
			'shippingID' => array('shippingID', 'integer', 0, 11),
			'fulfillBy' => array('fulfillBy', 'date', 0, 10),
			'fulfillmentDate' => array('fulfillmentDate', 'date', 0, 10),
			'billingID' => array('billingID', 'integer', 0, 11),
			'paymentMethod' => array('paymentMethod', 'alphanum', 1, 20),
			'paymentCleared' => array('paymentCleared', 'alphanum', 1, 10),
			'orderDate' => array('orderDate', 'datetime', 0, 19),
			'orderStatus' => array('orderStatus', 'alphanum', 1, 20),
			'siteID' => array('siteID', 'integer', 1, 10),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('orderID', NULL, false);
			$this->set('orderDate', 'NOW()', false);
			$this->enclose('orderDate', false);
			$this->set('lastModified', 'NOW()', false);
			$this->enclose('lastModified', false);
		} // function assertSaveDefaults

		/**
		 *  Set defaults for updating
		 *  Args: none
		 *  Return: none
		 */
		public function assertUpdateDefaults() {
			$this->set('lastModified', 'NOW()', false);
			$this->enclose('lastModified', false);
		} // function assertUpdateDefaults

		/**
		 *  Divide package into sub orders by grouping products from different merchants
		 *  Record sub order records
		 *  Args: (array) sub order shipping costs
		 *  Return: (boolean) sub orders entered successfully
		 */
		public function enterSubOrders($shippingCosts) {
			if ($this->exists() && $packageID = $this->get('packageID')) {
				assertArray($shippingCosts);
				$orderID = $this->get('orderID');
				$shippingArrangement = $this->get('shippingArrangement');
				$paymentCleared = $this->get('paymentCleared');
				$orderDate = $this->get('orderDate');
				$fulfillBy = $this->get('fulfillBy');
				$content = packagesController::getPackageContents($packageID, true);
				if (!empty($content)) {
					$orderQuantity = $this->get('quantity');
					$subOrder = array();
					foreach ($content as $product) {
						if (!isset($subOrder[$product['memberID']])) {
							$subOrder[$product['memberID']] = array(
								'totalCost' => 0
							);
						}
						$subOrder[$product['memberID']]['totalCost'] += $product['packageQuantity'] * $product['cost'] * $orderQuantity;
					}
					// enter sub orders
					foreach ($subOrder as $memberID => $details) {
						$shippingCost = isset($shippingCosts[$memberID]) && $shippingCosts[$memberID] ? $shippingCosts[$memberID] : 0;
						$subOrder = new subOrder;
						$subOrder->set('orderID', $orderID);
						$subOrder->set('memberID', $memberID);
						$subOrder->set('totalCost', $details['totalCost'] + $shippingCost);
						$subOrder->set('shippingArrangement', $shippingArrangement);
						$subOrder->set('shippingCost', $shippingCost);
						$subOrder->set('paymentCleared', $paymentCleared);
						$subOrder->set('orderDate', $orderDate);
						$subOrder->set('fulfillBy', $fulfillBy);
						if (!$subOrder->save()) {
							trigger_error('There was an error while entering sub order for order #'.$orderID.', package #'.$packageID.', member #'.$memberID, E_USER_WARNING);
						}
					}
					return true;
				} else {
					trigger_error('Package content not found while entering sub orders for order #'.$orderID.', package #'.$packageID, E_USER_WARNING);
				}
			}
			return false;
		} // function enterSubOrders

		/**
		 *  Retrieve sub orders associated with the current order
		 *  Args: none
		 *  Return: (array) sub order records
		 */
		public function getSubOrders() {
			if ($this->exists()) {
				$sql = "SELECT * FROM `subOrders` WHERE `orderID` = '".$this->get('orderID')."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					return $result->fetchAll();
				}
			}
			return array();
		} // function getSubOrders

		/**
		 *  Retrieve sub orders associated with the current order
		 *  Clear existing sub order payments, optionally clear only specified member suborders
		 *  Args: (array) member ids
		 *  Return: (boolean) sub orders cleared successfully
		 */
		public function clearSubOrders($memberIDs = array()) {
			if ($this->exists()) {
				$sql = "SELECT `subOrderID` FROM `subOrders` WHERE `orderID` = '".$this->get('orderID')."' AND `paymentCleared` = 'no'";
				assertArray($memberIDs);
				if (!empty($memberIDs)) {
					foreach ($memberIDs as $key => $val) {
						if (!validNumber($val)) {
							unset($memberIDs[$key]);
						}
					}
					if (!empty($memberIDs)) {
						$sql .= " AND `memberID` IN ('".implode("', '", $memberIDs)."')";
					}
				}
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					while ($row = $result->fetchRow()) {
						$subOrder = new subOrder($row['subOrderID']);
						if ($subOrder->exists()) {
							$subOrder->set('paymentCleared', 'cleared');
							if (!$subOrder->update()) {
								trigger_error('Error clearing sub order: update failed while clearing sub order #'.$row['subOrderID'], E_USER_WARNING);
							}
						} else {
							trigger_error('Error clearing sub order: sub order #'.$row['subOrderID'].' could not be found', E_USER_WARNING);
						}
					}
					return true;
				}
			}
			return false;
		} // function clearSubOrders

		/**
		 *  Update sub order status with current order status
		 *  Args: none
		 *  Return: (boolean) sub orders updated successfully
		 */
		public function updateSubOrderStatus() {
			if ($this->exists()) {
				$sql = "SELECT `subOrderID` FROM `subOrders` WHERE `orderID` = '".$this->get('orderID')."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					$status = $this->get('orderStatus');
					while ($row = $result->fetchRow()) {
						$subOrder = new subOrder($row['subOrderID']);
						if ($subOrder->exists()) {
							$subOrder->set('status', $status);
							if (!$subOrder->update()) {
								trigger_error('Error updating sub order: sub order status update failed for sub order #'.$row['subOrderID'].', status '.$status, E_USER_WARNING);
							}
						} else {
							trigger_error('Error updating sub order: sub order #'.$row['subOrderID'].' could not be found', E_USER_WARNING);
						}
					}
					return true;
				}
			}
			return false;
		} // function updateSubOrderStatus
	} // class order

?>