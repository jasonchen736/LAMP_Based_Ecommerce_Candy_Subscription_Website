<?

	class subOrdersController extends controller {
		// controller for specified table
		protected $table = 'subOrders';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'subOrderID' => array('type' => 'integer', 'range' => false),
			'orderID' => array('type' => 'integer', 'range' => false),
			'memberID' => array('type' => 'integer', 'range' => false),
			'totalCost' => array('type' => 'double', 'range' => true),
			'shippingArrangement' => array('type' => 'integer', 'range' => false),
			'shippingCost' => array('type' => 'double', 'range' => true),
			'fulfillBy' => array('type' => 'date', 'range' => true),
			'fulfillmentDate' => array('type' => 'date', 'range' => true),
			'paymentCleared' => array('type' => 'alpha', 'range' => false),
			'status' => array('type' => 'alpha', 'range' => false),
			'orderDate' => array('type' => 'date', 'range' => true)
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
				'paymentdeclined' => 'Set status: Payment Declined',
				'attention' => 'Set status: Requires Attention'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Return an array of items in the sub order
		 *  array(productID => array(name, cost, weight, length, width, height, quantity) ... )
		 *  Args: (int) sub order id
		 *  Return: (array) sub order items
		 */
		public static function getSubOrderItems($subOrderID) {
			$items = array();
			if (validNumber($subOrderID, 'integer')) {
				$sql = "SELECT `d`.`productID`, `d`.`name`, `d`.`cost`, `d`.`weight`, `d`.`length`, 
							`d`.`width`, `d`.`height`, `c`.`quantity` * `b`.`quantity` AS `quantity` 
						FROM `subOrders` `a` 
						JOIN `orders` `b` USING (`orderID`) 
						JOIN `productToPackage` `c` USING (`packageID`) 
						JOIN `products` `d` USING (`productID`) 
						WHERE `a`.`subOrderID` = '".$subOrderID."' 
						AND `d`.`memberID` = `a`.`memberID`";
				$result = query($sql);
				if ($result->rowCount > 0) {
					while ($row = $result->fetchRow()) {
						$items[$row['productID']] = array(
							'name' => $row['name'],
							'cost' => $row['cost'],
							'weight' => $row['weight'],
							'length' => $row['length'],
							'width' => $row['width'],
							'height' => $row['height'],
							'quantity' => $row['quantity']
						);
					}
				}
			}
			return $items;
		} // function getSubOrderItems

		/**
		 *  Return array of search sql components
		 *  Rearranges and prepares additional sub order search components to work with native search method
		 *    Override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchComponents() {
			$search = parent::getSearchComponents();
			$search['select'] = "`a`.*, `b`.`paymentMethod`, `c`.`name` AS `shippingMethod`, `d`.`email`, `e`.`company`";
			$search['tables'][0] = '`'.$this->table.'` `a`';
			$search['tables'][] = 'JOIN `orders` `b` ON (`a`.`orderID` = `b`.`orderID`)';
			$search['tables'][] = 'JOIN `shippingOptions` `c` ON (`a`.`shippingArrangement` = `c`.`shippingOptionID`)';
			$search['tables'][] = 'LEFT JOIN `members` `d` ON (`a`.`memberID` = `d`.`memberID`)';
			$search['tables'][] = 'LEFT JOIN `memberBusinessInfo` `e` ON (`a`.`memberID` = `e`.`memberID`)';
			foreach ($search['where'] as $field => &$val) {
				$val = preg_replace('/^(AND |OR )?/', '$1`a`.', $val);
			}
			return $search;
		} // function getSearchComponents
	} // class subOrdersController

?>