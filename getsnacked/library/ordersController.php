<?

	class ordersController extends controller {
		// controller for specified table
		protected $table = 'orders';
		// fields available to search: array(external name => array(field name, type, range))
		protected $searchFields = array(
			'orderID' => array('type' => 'integer', 'range' => false),
			'siteID' => array('type' => 'integer', 'range' => false),
			'packageID' => array('type' => 'integer', 'range' => false),
			'memberID' => array('type' => 'integer', 'range' => false),
			'totalCost' => array('type' => 'decimal', 'range' => true),
			'shippingArrangement' => array('type' => 'alphanum', 'range' => false),
			'fulfillBy' => array('type' => 'date', 'range' => true),
			'fulfillmentDate' => array('type' => 'date', 'range' => true),
			'paymentMethod' => array('type' => 'alphanum', 'range' => false),
			'paymentCleared' => array('type' => 'alphanum', 'range' => false),
			'orderDate' => array('type' => 'date', 'range' => true),
			'orderStatus' => array('type' => 'alphanum', 'range' => false)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'new' => 'Set status: New',
				'processing' => 'Set status: Processing',
				'fulfilled' => 'Set status: Fulfilled',
				'backordered' => 'Set status: Backordered',
				'cancelled' => 'Set status: Cancelled',
				'returned' => 'Set status: Returned',
				'attention' => 'Set status: Requires Attention',
				'paymentdeclined' => 'Set status: Payment Declined',
				'clearCheck' => 'Clear Payment (Check)',
				'clearMoneyOrder' => 'Clear Payment (Money Order)'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Return an array of items in the order
		 *  array(productID => array(name, cost, weight, length, width, height, quantity) ... )
		 *  Args: (int) order id
		 *  Return: (array) order items
		 */
		public static function getOrderItems($orderID) {
			$items = array();
			if (validNumber($orderID, 'integer')) {
				$sql = "SELECT `c`.*, `b`.`quantity` * `a`.`quantity` AS `quantity` 
						FROM `orders` `a` 
						JOIN `productToPackage` `b` USING (`packageID`) 
						JOIN `productsHistory` `c` ON (`b`.`productID` = `c`.`productID` AND `c`.`lastModified` <= `a`.`orderDate` AND `c`.`effectiveThrough` >= `a`.`orderDate`) 
						WHERE `a`.`orderID` = '".$orderID."'";
				$result = query($sql);
				if ($result->rowCount > 0) {
					while ($row = $result->fetchRow()) {
						$items[$row['productID']] = $row;
					}
				}
			}
			return $items;
		} // function getOrderItems

		/**
		 *  Return payment method record for an order
		 *  Args: (int) order id
		 *  Return: (array) payment method record
		 */
		public static function getPaymentMethod($orderID) {
			$sql = "SELECT `b`.* FROM `orders` `a` JOIN `paymentMethods` `b` ON (`a`.`billingID` = `b`.`addressID`) WHERE `a`.`orderID` = '".clean($orderID, 'integer')."'";
			$dbh = database::getInstance();
			$result = $dbh->query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				$paymentMethod = $row;
			} else {
				$paymentMethod = array();
			}
			return $paymentMethod;
		} // function getPaymentMethod

		/**
		 *  Return array of search sql components
		 *  Rearranges and prepares additional order search components to work with native search method
		 *    Override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchComponents() {
			$search = parent::getSearchComponents();
			$search['select'] = "`a`.*, `b`.`name` AS `package`, `c`.`name` AS `shippingMethod`, `d`.`email`, `e`.`siteName`";
			$search['tables'][0] = '`'.$this->table.'` `a`';
			$search['tables'][] = 'JOIN `packages` `b` ON (`a`.`packageID` = `b`.`packageID`)';
			$search['tables'][] = 'JOIN `shippingOptions` `c` ON (`a`.`shippingArrangement` = `c`.`shippingOptionID`)';
			$search['tables'][] = 'LEFT JOIN `members` `d` ON (`a`.`memberID` = `d`.`memberID`)';
			$search['tables'][] = 'JOIN `siteRegistry` `e` ON (`a`.`siteID` = `e`.`siteID`)';
			foreach ($search['where'] as $field => &$val) {
				$val = preg_replace('/^(AND |OR )?/', '$1`a`.', $val);
			}
			return $search;
		} // function getSearchComponents
	} // class ordersController

?>