<?

	class shippingContainer extends activeRecord {
		// active record table
		protected $table = 'shippingContainers';
		// existing auto increment field
		protected $autoincrement = 'shippingContainerID';
		// history table (optional)
		protected $historyTable = 'shippingContainersHistory';
		// array unique id fields
		protected $idFields = array(
			'shippingContainerID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'shippingContainerID' => array('shippingContainerID', 'integer', 0, 10),
			'name'                => array('name', 'alphanum', 1, 45),
			'length'              => array('length', 'decimal', 1, 7),
			'width'               => array('width', 'decimal', 1, 7),
			'height'              => array('height', 'decimal', 1, 7),
			'maxWeight'           => array('maxWeight', 'decimal', 1, 9),
			'status'              => array('status', 'alphanum', 0, 10),
			'dateAdded'           => array('dateAdded', 'datetime', 0, 19),
			'lastModified'        => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'shippingContainerSiteMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('shippingContainerID', NULL, false);
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

	} // class shippingContainer

?>