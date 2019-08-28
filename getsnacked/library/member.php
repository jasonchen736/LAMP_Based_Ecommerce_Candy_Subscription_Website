<?

	class member extends activeRecord {
		// active record table
		protected $table = 'members';
		// existing auto increment field
		protected $autoincrement = 'memberID';
		// history table (optional)
		protected $historyTable = 'membersHistory';
		// array unique id fields
		protected $idFields = array(
			'memberID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'memberID'     => array('memberID', 'integer', 0, 10),
			'first'        => array('first', 'alphanum', 0, 50),
			'last'         => array('last', 'alphanum', 0, 50),
			'phone'        => array('phone', 'integer', 0, 20),
			'email'        => array('email', 'email', 1, 255),
			'password'     => array('password', 'password', 1, 255),
			'address1'     => array('address1', 'alphanum', 0, 255),
			'address2'     => array('address2', 'alphanum', 0, 255),
			'city'         => array('city', 'alphanum', 0, 50),
			'state'        => array('state', 'alphanum', 0, 50),
			'postal'       => array('postal', 'alphanum', 0, 10),
			'country'      => array('country', 'alpha', 0, 3),
			'status'       => array('status', 'alpha', 0, 15),
			'dateCreated'  => array('dateCreated', 'datetime', 0, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'memberSiteMap';
		// member group mapping table
		protected $groupMappingTable = 'memberGroupMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('memberID', NULL, false);
			$this->set('password', "PASSWORD('".$this->get('password')."')", false);
			$this->enclose('password', false);
			$this->set('dateCreated', 'NOW()', false);
			$this->enclose('dateCreated', false);
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
		 *  Retrieve groups associated with the member
		 *  Args: none
		 *  Return: (array) site ids
		 */
		public function getMemberGroups() {
			$groups = array();
			if ($this->exists()) {
				$result = $this->dbh->query("SELECT `memberGroupID` FROM `".$this->groupMappingTable."` WHERE `memberID` = '".$this->get('memberID')."'");
				if ($result->rowCount) {
					while ($row = $result->fetchRow()) {
						$groups[] = $row['memberGroupID'];
					}
				}
			}
			return $groups;
		} // function getMemberGroups

		/**
		 *  Associate member with groups
		 *  Args: (array) array of group ids
		 *  Return: (boolean) success
		 */
		public function addGroups($groups) {
			if ($this->exists()) {
				$sql = "INSERT IGNORE INTO `".$this->groupMappingTable."` (`memberID`, `memberGroupID`) SELECT '".$this->get('memberID')."' AS `memberID`, `memberGroupID` FROM `memberGroups` WHERE `memberGroupID` IN ('".implode("', '", $groups)."')";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function addGroups

		/**
		 *  Disassociate member with groups
		 *  Args: (array) array of group ids
		 *  Return: (boolean) success
		 */
		public function removeGroups($groups) {
			if ($this->exists()) {
				$sql = "DELETE FROM `".$this->groupMappingTable."` WHERE `memberID` = '".$this->get('memberID')."' AND `memberGroupID` IN ('".implode("', '", $groups)."')";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function removeGroups

		/**
		 *  Matches argument password with existing
		 *  Args: (str) password
		 *  Return: (boolean) match
		 */
		public function verifyExistingPassword($password) {
			$result = $this->dbh->query("SELECT PASSWORD('".prep($password)."') AS `password`");
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				if ($row['password'] == $this->get('password')) {
					return true;
				}
			}
			return false;
		} // function verifyExistingPassword
	} // class member

?>