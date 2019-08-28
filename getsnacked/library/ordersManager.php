<?

	class ordersManager extends recordEditor {

		protected $historyTable = 'ordersHistory';

		protected $required = array(
			'packageID',
			'quantity',
			'shippingArrangement',
			'shippingCost',
			'shippingDate',
			'shippingID',
			'billingID',
			'paymentMethod'
		);

		protected $default = array(
			'orderDate' => array('key' => 'orderDate', 'value' => 'NOW()', 'update' => false),
			'lastModified' => array('key' => 'lastModified', 'value' => 'NOW()', 'update' => true)
		);

		protected $searchFields = array(
			'orderID' => array('type' => 'integer', 'range' => false),
			'customerID' => array('type' => 'integer', 'range' => false),
			'packageID' => array('type' => 'integer', 'range' => false),
			'totalCost' => array('type' => 'money', 'range' => true),
			'orderStatus' => array('type' => 'alphanum', 'range' => false),
			'shippingArrangement' => array('type' => 'alphanum', 'range' => false),
			'paymentMethod' => array('type' => 'alphanum', 'range' => false),
			'paymentCleared' => array('type' => 'alphanum', 'range' => false),
			'shippingDate' => array('type' => 'date', 'range' => true),
			'shippedOn' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			parent::__construct('orders', array('orderID'));
		} // function __construct

		/**
		 *  Return an array of available actions
		 *  Args: none
		 *  Return: (array) actions
		 */
		public function getActions() {
			$actions = $this->fields['orderStatus'];
			$actions['Clear Payment (Check)'] = 'Clear Payment (Check)';
			$actions['Clear Payment (Money Order)'] = 'Clear Payment (Money Order)';
			return $actions;
		} // function getActions

		/**
		 *  Update a set of given records
		 *  Args: (array) record ids, (str) action
		 *  Return: none
		 */
		public function takeAction($recordIDs, $action) {
			if(in_array($action, $this->fields['orderStatus']) && is_array($recordIDs)) {
				$records = array();
				foreach ($recordIDs as $val) {
					if (validNumber($val, 'integer')) {
						$this->loadID($val);
						$this->record['orderStatus'] = $action;
						if ($action == 'shipped') {
							$this->record['shippedOn'] = 'function::NOW()';
						}
						$this->update();
					}
				}
			} elseif (is_array($recordIDs)) {
				$records = array();
				foreach ($recordIDs as $val) {
					if (validNumber($val, 'integer')) {
						$records[] = $val;
					}
				}
				if (!empty($records)) {
					switch ($action) {
						case 'Clear Payment (Check)';
							$order = new orderProcessor;
							foreach ($records as $val) {
								$order->clearCheckMoneyOrder($val, 'check');
								$this->loadID($val);
								$this->record['paymentCleared'] = 'cleared';
								$this->update();
							}
							break;
						case 'Clear Payment (Money Order)';
							$order = new orderProcessor;
							foreach ($records as $val) {
								$order->clearCheckMoneyOrder($val, 'moneyorder');
								$this->loadID($val);
								$this->record['paymentCleared'] = 'cleared';
								$this->update();
							}
							break;
						default:
							break;
					}
				}
			}
		} // function takeAction

		/**
		 *  Return array of search sql where clause components, override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchArray() {
			$search = parent::getSearchArray();
			$search[] = "`orderDate` BETWEEN '".$GLOBALS['_startDate']." 00:00:00' AND '".$GLOBALS['_endDate']." 23:59:59'";
			if (!getRequest('search')) {
				$search[] = "`orderStatus` IN ('new', 'reorder', 'processing')";
			}
			return $search;
		} // function getSearchArray

	} // class productsManager

?>