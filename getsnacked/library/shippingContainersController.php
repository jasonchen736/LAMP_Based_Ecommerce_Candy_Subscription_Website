<?

	class shippingContainersController extends controller {
		// controller for specified table
		protected $table = 'shippingContainers';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'shippingContainerID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'alphanum', 'range' => false),
			'length' => array('type' => 'decimal', 'range' => true),
			'width' => array('type' => 'decimal', 'range' => true),
			'height' => array('type' => 'decimal', 'range' => true),
			'maxWeight' => array('type' => 'decimal', 'range' => true),
			'status' => array('type' => 'alphanum', 'range' => false),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'Activate' => 'Activate',
				'Deactivate' => 'Deactivate'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Return an array of available shipping containers
		 *    array(array(length, width, height), max weight)
		 *  Args: none
		 *  Return: (array) available shipping containers
		 */
		public static function getAvailableContainers() {
			$sql = "SELECT `a`.`length`, `a`.`width`, `a`.`height`, `a`.`maxWeight` FROM `shippingContainers` `a` JOIN `shippingContainerSiteMap` `b` USING (`shippingContainerID`) WHERE `a`.`status` = 'active' AND `b`.`siteID` = '".systemSettings::get('SITEID')."'";
			$result = query($sql);
			$containers = array();
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$containers[] = array(
						array($row['length'], $row['width'], $row['height']),
						$row['maxWeight']
					);
				}
			}
			return $containers;
		} // function getAvailableContainers

	} // class shippingContainersController

?>