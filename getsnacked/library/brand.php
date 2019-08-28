<?

	class brand extends activeRecord {
		// active record table
		protected $table = 'brands';
		// existing auto increment field
		protected $autoincrement = 'brandID';
		// array unique id fields
		protected $idFields = array(
			'brandID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'brandID' => array('brandID', 'integer', 0, 10),
			'brand' => array('brand', 'name', 1, 100)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('brandID', NULL, false);
		} // function assertSaveDefaults

		/**
		 *  Check if brand name already exists in the database
		 *    OVERRIDE
		 *  Args: none
		 *  Return: (boolean) existing brand
		 */
		public function isDuplicate() {
			$brand = $this->get('brand');
			if ($brand)  {
				$sql = "SELECT `brand` FROM `brands` WHERE `brand` = '".prep($brand)."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					return true;
				}
			}
			return false;
		} // function isDuplidate
	} // class brand

?>