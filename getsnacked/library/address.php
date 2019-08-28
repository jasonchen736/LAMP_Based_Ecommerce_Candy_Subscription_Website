<?

	class address extends activeRecord {
		// active record table
		protected $table = 'addresses';
		// existing auto increment field
		protected $autoincrement = 'addressID';
		// array unique id fields
		protected $idFields = array(
			'addressID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'addressID' => array('addressID', 'integer', 0, 11),
			'first' => array('first', 'alphanum', 1, 50),
			'last' => array('last', 'alphanum', 1, 50),
			'email' => array('email', 'email', 0, 255),
			'phone' => array('phone', 'integer', 0, 20),
			'address1' => array('address1', 'alphanum', 1, 100),
			'address2' => array('address2', 'alphanum', 0, 100),
			'city' => array('city', 'alphanum', 1, 50),
			'state' => array('state', 'alphanum', 1, 50),
			'postal' => array('postal', 'alphanum', 1, 10),
			'country' => array('country', 'alphanum', 1, 3),
			'entryDate' => array('entryDate', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('addressID', NULL, false);
			$this->set('entryDate', 'NOW()', false);
			$this->enclose('entryDate', false);
		} // function assertSaveDefaults
	} // class content

?>