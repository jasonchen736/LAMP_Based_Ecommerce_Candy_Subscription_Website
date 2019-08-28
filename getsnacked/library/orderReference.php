<?

	class orderReference extends activeRecord {
		// active record table
		protected $table = 'orderReference';
		// existing auto increment field
		protected $autoincrement = 'orderReferenceID';
		// array unique id fields
		protected $idFields = array(
			'orderReferenceID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'orderReferenceID'    => array('orderReferenceID', 'integer', 0, 11),
			'type'                => array('type', 'alpha', 1, 25),
			'ID'                  => array('ID', 'integer', 1, 11),
			'subID'               => array('subID', 'clean', 0, 100),
			'offerID'             => array('offerID', 'integer', 0, 11),
			'campaignID'          => array('campaignID', 'integer', 0, 11),
			'passThroughVariable' => array('passThroughVariable', 'clean', 0, 75),
			'orderID'             => array('orderID', 'integer', 1, 11),
			'subscriptionID'      => array('subscriptionID', 'integer', 0, 11),
			'payoutID'            => array('payoutID', 'integer', 0, 11),
			'IP'                  => array('IP', 'integer', 0, 10),
			'orderDate'           => array('orderDate', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('orderReferenceID', NULL, false);
			$this->set('IP', "INET_ATON('".clean($_SERVER['REMOTE_ADDR'], 'ip')."')", false);
			$this->enclose('IP', false);
		} // function assertSaveDefaults

	}

?>