<?

	class memberBusinessInfo extends activeRecord {
		// active record table
		protected $table = 'memberBusinessInfo';
		// existing auto increment field
		protected $autoincrement = 'memberBusinessInfoID';
		// history table (optional)
		protected $historyTable = 'memberBusinessInfoHistory';
		// array unique id fields
		protected $idFields = array(
			'memberBusinessInfoID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'memberBusinessInfoID' => array('memberBusinessInfoID', 'integer', 0, 10),
			'memberID'             => array('memberID', 'integer', 0, 10),
			'company'              => array('company', 'alphanum', 1, 50),
			'fax'                  => array('fax', 'alphanum', 0, 25),
			'website'              => array('website', 'clean', 0, 255),
			'taxID'                => array('taxID', 'alphanum', 1, 20),
			'industry'             => array('industry', 'alphanum', 0, 25),
			'description'          => array('description', 'alphanum', 0, 255),
			'payTo'                => array('payTo', 'alpha', 1, 10),
			'im'                   => array('im', 'alphanum', 0, 25),
			'dateCreated'          => array('dateCreated', 'datetime', 0, 19),
			'lastModified'         => array('lastModified', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('memberBusinessInfoID', NULL, false);
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
		 *  Retrieve payTo enum options
		 *  Args: none
		 *  Return: (array) payTo options
		 */
		public static function payToOptions() {
			$sql = "DESC `memberBusinessInfo`";
			$result = query($sql);
			$options = array();
			while ($row = $result->fetchRow()) {
				if ($row['Field'] == 'payTo') {
					$values = explode(',', rtrim(ltrim($row['Type'], 'enum('), ')'));
					$values = preg_replace('/\'/', '', $values);
					$options = array();
					foreach ($values as $key => $val) {
						$options[$val] = $val;
					}
				}
			}
			return $options;
		} // function payToOptions
	} // class memberBusinessInfo

?>