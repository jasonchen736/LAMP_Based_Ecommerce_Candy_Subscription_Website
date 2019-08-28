<?

	class memberShippingLocation extends activeRecord {
		// active record table
		protected $table = 'memberShippingLocations';
		// existing auto increment field
		protected $autoincrement = 'memberShippingLocationID';
		// array unique id fields
		protected $idFields = array(
			'memberShippingLocationID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'memberShippingLocationID' => array('memberShippingLocationID', 'integer', 0, 10),
			'memberID'                 => array('memberID', 'integer', 1, 10),
			'state'                    => array('state', 'alphanum', 1, 50),
			'postal'                   => array('postal', 'alphanum', 1, 10),
			'country'                  => array('country', 'alphanum', 1, 3)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('memberShippingLocationID', NULL, false);
		} // function assertSaveDefaults

		/**
		 *  Retrieve the memberShippingLocation object by a member id
		 *  Args: (int) member id
		 *  Return: (memberShippingLocation) memberShippingLocation object
		 */		
		public static function retrieveMemberShippingLocation($memberID) {
			if (validNumber($memberID, 'integer')) {
				$sql = "SELECT `memberShippingLocationID` FROM `memberShippingLocations` WHERE `memberID` = '".$memberID."'";
				$result = query($sql);
				if ($result->rowCount > 0) {
					$row = $result->fetchRow();
					$memberShippingLocation = new memberShippingLocation($row['memberShippingLocationID']);
				} else {
					$memberShippingLocation = new memberShippingLocation;
				}
			} else {
				$memberShippingLocation = new memberShippingLocation;
			}
			return $memberShippingLocation;
		} // function retrieveMemberShippingLocation
	} // class memberShippingLocation

?>