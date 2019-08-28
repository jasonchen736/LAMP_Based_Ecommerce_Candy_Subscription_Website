<?

	class campaign extends activeRecord {
		// active record table
		protected $table = 'campaigns';
		// existing auto increment field
		protected $autoincrement = 'campaignID';
		// history table (optional)
		protected $historyTable = 'campaignsHistory';
		// array unique id fields
		protected $idFields = array(
			'campaignID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'campaignID'     => array('campaignID', 'integer', 0, 11),
			'type'           => array('type', 'alpha', 1, 10),
			'name'           => array('name', 'alphanum', 1, 255),
			'availability'   => array('availability', 'alpha', 1, 25),
			'subject'        => array('subject', 'html-campaign', 1, 255),
			'html'           => array('html', 'html-campaign', 0, 999999),
			'text'           => array('text', 'html-campaign', 0, 999999),
			'fromEmail'      => array('fromEmail', 'email', 0, 100),
			'linkedCampaign' => array('linkedCampaign', 'integer', 0, 11),
			'sendInterval'   => array('sendInterval', 'integer', 0, 4),
			'dateAdded'      => array('dateAdded', 'datetime', 0, 19),
			'lastModified'   => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'campaignSiteMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('campaignID', NULL, false);
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

	} // class campaign

?>