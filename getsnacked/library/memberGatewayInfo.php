<?

	class memberGatewayInfo extends activeRecord {
		// active record table
		protected $table = 'memberGatewayInfo';
		// existing auto increment field
		protected $autoincrement = 'memberGatewayInfoID';
		// history table (optional)
		protected $historyTable = 'memberGatewayInfoHistory';
		// array unique id fields
		protected $idFields = array(
			'memberGatewayInfoID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'memberGatewayInfoID' => array('memberGatewayInfoID', 'integer', 0, 10),
			'memberID'            => array('memberID', 'integer', 1, 10),
			'gateway'             => array('gateway', 'alpha', 1, 20),
			'url'                 => array('url', 'url', 1, 255),
			'port'                => array('port', 'integer', 0, 10),
			'login'               => array('login', 'alphanum', 0, 45),
			'key'                 => array('key', 'alphanum', 0, 45),
			'hash'                => array('hash', 'alphanum', 0, 255),
			'status'              => array('status', 'alpha', 1, 20),
			'dateAdded'           => array('dateAdded', 'datetime', 0, 19),
			'lastModified'        => array('lastModified', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('memberGatewayInfoID', NULL, false);
			$this->set('status', 'inactive');
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
		 *  Retrieve gateway enum options
		 *  Args: none
		 *  Return: (array) gateway options
		 */
		public static function gatewayOptions() {
			$sql = "DESC `memberGatewayInfo`";
			$result = query($sql);
			$options = array();
			while ($row = $result->fetchRow()) {
				if ($row['Field'] == 'gateway') {
					$values = explode(',', rtrim(ltrim($row['Type'], 'enum('), ')'));
					$values = preg_replace('/\'/', '', $values);
					$options = array();
					foreach ($values as $key => $val) {
						$options[$val] = $val;
					}
				}
			}
			return $options;
		} // function gatewayOptions

		/**
		 *  Write merchant certificate
		 *  Args: (str) certificate data
		 *  Return: (boolean) success
		 */
		public function saveCertificate($certificate) {
			if ($certificate && $this->exists()) {
				$root = systemSettings::get('CERTIFICATESDIR');
				if (file_put_contents($root.$this->get('memberID').'.cert', $certificate)) {
					return true;
				}
			}
			return false;
		} // function savevCertificate
	} // class memberGatewayInfo

?>