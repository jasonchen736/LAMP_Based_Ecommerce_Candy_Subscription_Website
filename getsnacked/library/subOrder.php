<?

	class subOrder extends activeRecord {
		// active record table
		protected $table = 'subOrders';
		// existing auto increment field
		protected $autoincrement = 'subOrderID';
		// history table (optional)
		protected $historyTable = 'subOrdersHistory';
		// array unique id fields
		protected $idFields = array(
			'subOrderID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'subOrderID' => array('subOrderID', 'integer', 0, 11),
			'orderID' => array('orderID', 'integer', 1, 11),
			'memberID' => array('memberID', 'integer', 1, 10),
			'totalCost' => array('totalCost', 'double', 0, 10),
			'shippingArrangement' => array('shippingArrangement', 'integer', 0, 10),
			'shippingCost' => array('shippingCost', 'double', 0, 8),
			'fulfillBy' => array('fulfillBy', 'date', 0, 10),
			'fulfillmentDate' => array('fulfillmentDate', 'date', 0, 10),
			'paymentCleared' => array('paymentCleared', 'alphanum', 1, 10),
			'status' => array('status', 'alphanum', 0, 20),
			'orderDate' => array('orderDate', 'datetime', 1, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('subOrderID', NULL, false);
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
		 *  Update order status
		 *    If all suborders are fulfilled, update order to fulfilled
		 *    If status is processing, update order status to processing
		 *    All other update status to attention
		 *  Args: none
		 *  Return: (boolean) sub orders updated successfully
		 */
		public function updateOrderStatus() {
			if ($this->exists()) {
				$orderID = $this->get('orderID');
				$status = $this->get('status');
				$order = new order($orderID);
				if (!$order->exists()) {
					trigger_error('Error updating order: order #'.$orderID.' could not be found', E_USER_WARNING);
					return false;
				}
				$orderStatus = $order->get('orderStatus');
				switch ($status) {
					case 'fulfilled':
						$sql = "SELECT `status` FROM `subOrders` WHERE `orderID` = '".$orderID."'";
						$result = $this->dbh->query($sql);
						$allFulfilled = true;
						while ($row = $result->fetchRow()) {
							if ($row['status'] != 'fulfilled') {
								$allFulfilled = false;
							}
						}
						if ($allFulfilled) {
							$orderStatus = 'fulfilled';
						}
						break;
					case 'processing':
						if ($orderStatus == 'new') {
							$orderStatus = 'processing';
						} elseif ($orderStatus == 'fulfilled') {
							$orderStatus = 'attention';
						}
						break;
					default:
						if ($orderStatus == 'fulfilled') {
							$orderStatus = 'attention';
						}
						break;
				}
				if ($orderStatus != $order->get('orderStatus')) {
					$order->set('orderStatus', $orderStatus);
					if (!$order->update()) {
						trigger_error('Error updating order: order status updated failed for order #'.$orderID.', status '.$orderStatus, E_USER_WARNING);
					}
				}
				return true;
			}
			return false;
		} // function updateOrderStatus
	} // class subOrder

?>