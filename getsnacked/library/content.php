<?

	class content extends activeRecord {
		// active record table
		protected $table = 'content';
		// existing auto increment field
		protected $autoincrement = 'contentID';
		// history table (optional)
		protected $historyTable = 'contentHistory';
		// array unique id fields
		protected $idFields = array(
			'contentID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'contentID'    => array('contentID', 'integer', 0, 10),
			'name'         => array('name', 'alphanum', 1, 45),
			'content'      => array('content', 'html', 1, 1000000),
			'dateAdded'    => array('dateAdded', 'datetime', 0, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'contentSiteMap';

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('contentID', NULL, false);
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

	} // class content

?>