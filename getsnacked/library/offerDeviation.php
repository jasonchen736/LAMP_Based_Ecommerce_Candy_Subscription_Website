<?

	class offerDeviation extends activeRecord {
		// active record table
		protected $table = 'offerDeviations';
		// existing auto increment field
		protected $autoincrement = 'offerDeviationID';
		// array unique id fields
		protected $idFields = array(
			'offerDeviationID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'offerDeviationID'  => array('offerDeviationID', 'integer', 0, 10),
			'ID'                => array('ID', 'integer', 0, 11),
			'subID'             => array('subID', 'clean', 0, 100),
			'offerID'           => array('offerID', 'integer', 0, 11),
			'campaignID'        => array('campaignID', 'integer', 0, 11),
			'payoutID'          => array('payoutID', 'integer', 0, 11),
			'intendedPackageID' => array('intendedPackageID', 'integer', 0, 11),
			'intendedShipments' => array('intendedShipments', 'integer', 0, 6),
			'orderedPackageID'  => array('orderedPackageID', 'integer', 0, 11),
			'orderedShipments'  => array('orderedShipments', 'integer', 0, 6),
			'date'              => array('date', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('offerDeviationID', NULL, false);
		} // function assertSaveDefaults

	}

?>