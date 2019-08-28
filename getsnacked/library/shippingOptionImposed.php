<?

	class shippingOptionImposed extends activeRecord {
		// active record table
		protected $table = 'shippingOptionsImposed';
		// existing auto increment field
		protected $autoincrement = 'shippingOptionsImposedID';
		// array unique id fields
		protected $idFields = array(
			'shippingOptionsImposedID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'shippingOptionsImposedID' => array('shippingOptionsImposedID', 'integer', 0, 10),
			'shippingOptionID'         => array('shippingOptionID', 'integer', 0, 10),
			'imposedOn'                => array('imposedOn', 'alphanum', 0, 20)
		);
		// object site mapping table
		protected $siteMappingTable = 'shippingOptionsImposedSiteMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('shippingOptionImposedID', NULL, false);
		} // function assertSaveDefaults

		/**
		 *  Retrieve shipment category options (international, domestic, etc)
		 *  Args: none
		 *  Return: (array) shipment category options
		 */
		public static function getOptions() {
			$options = array(
				'' => 'None',
				'all' => 'All Shipments',
				'domestic' => 'Domestic Shipments',
				'international' => 'International Shipments'
			);
			return $options;
		} // function getOptions

		/**
		 *  Retrieve the shippingOptionImposed object by a shipping option id
		 *  Args: (int) shipping option id
		 *  Return: (shippingOptionImposed) shippingOptionImposed object
		 */		
		public static function retrieveShippingOptionImposed($shippingOptionID) {
			if (validNumber($shippingOptionID, 'integer')) {
				$sql = "SELECT `shippingOptionsImposedID` FROM `shippingOptionsImposed` WHERE `shippingOptionID` = '".$shippingOptionID."'";
				$result = query($sql);
				if ($result->rowCount > 0) {
					$row = $result->fetchRow();
					$shippingOptionImposed = new shippingOptionImposed($row['shippingOptionsImposedID']);
				} else {
					$shippingOptionImposed = new shippingOptionImposed;
				}
			} else {
				$shippingOptionImposed = new shippingOptionImposed;
			}
			return $shippingOptionImposed;
		} // function retrieveShippingOptionImposed
	} // class shippingOptionImposed

?>