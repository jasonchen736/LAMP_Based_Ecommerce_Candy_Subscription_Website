<?

	class siteRegistry extends activeRecord {
		// active record table
		protected $table = 'siteRegistry';
		// existing auto increment field
		protected $autoincrement = 'siteID';
		// array unique id fields
		protected $idFields = array(
			'siteID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'siteID'      => array('siteID', 'integer', 0, 10),
			'siteName'    => array('siteName', 'clean', 0, 45),
			'dateCreated' => array('dateCreated', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('siteID', NULL, false);
			$this->set('dateCreated', 'NOW()', false);
			$this->enclose('dateCreated', false);
		} // function assertSaveDefaults

		/**
		 *  Load registry by site name
		 *  Args: (str) site name
		 *  Return: (boolean) successful load
		 */
		public function loadSiteName($siteName) {
			$siteName = clean($siteName, 'clean');
			if ($siteName) {
				$sql = "SELECT `siteID` FROM `siteRegistry` WHERE `siteName` = '".prep($siteName)."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					$row = $result->fetchRow();
					if ($this->load($row['siteID'])) {
						return true;
					}
				}
			}
			return false;
		} // function loadSiteName

	} // class siteRegistry

?>