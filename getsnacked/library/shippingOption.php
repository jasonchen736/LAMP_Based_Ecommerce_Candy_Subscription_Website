<?

	class shippingOption extends activeRecord {
		// active record table
		protected $table = 'shippingOptions';
		// existing auto increment field
		protected $autoincrement = 'shippingOptionID';
		// history table (optional)
		protected $historyTable = 'shippingOptionsHistory';
		// array unique id fields
		protected $idFields = array(
			'shippingOptionID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'shippingOptionID' => array('shippingOptionID', 'integer', 0, 10),
			'provider'         => array('provider', 'alphanum', 0, 10),
			'externalValue'    => array('externalValue', 'alphanum', 0, 10),
			'modifier'         => array('modifier', 'double', 0, 6),
			'modifierType'     => array('modifierType', 'alphanum', 0, 15),
			'name'             => array('name', 'alphanum', 1, 45),
			'rate'             => array('rate', 'double', 0, 8),
			'rateType'         => array('rateType', 'alphanum', 0, 20),
			'status'           => array('status', 'alphanum', 0, 10),
			'rule'             => array('rule', 'alpha', 0, 10),
			'dateAdded'        => array('dateAdded', 'datetime', 0, 19),
			'lastModified'     => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'shippingOptionSiteMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('shippingOptionID', NULL, false);
			$this->set('dateAdded', 'NOW()', false);
			$this->enclose('dateAdded', false);
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
		 *  Retrieve shipping option rules
		 *  Args: none
		 *  Return: (array) shipping option rules
		 */
		public function getShippingOptionRules() {
			$sql = "SELECT * FROM `shippingOptionRules` WHERE `shippingOptionID` = '".$this->get('shippingOptionID')."'";
			$result = $this->dbh->query($sql);
			if ($result->rowCount > 0) {
				$shippingOptionRules = $result->fetchAll();
			} else {
				$shippingOptionRules = array();
			}
			return $shippingOptionRules;
		} // function getShippingOptionRules

	} // class shippingOption

?>