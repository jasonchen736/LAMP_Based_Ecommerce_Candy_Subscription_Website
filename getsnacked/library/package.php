<?

	class package extends activeRecord {
		// active record table
		protected $table = 'packages';
		// existing auto increment field
		protected $autoincrement = 'packageID';
		// history table (optional)
		protected $historyTable = 'packagesHistory';
		// array unique id fields
		protected $idFields = array(
			'packageID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'packageID' => array('packageID', 'integer', 0, 11),
			'name' => array('name', 'name', 1, 100),
			'sku' => array('sku', 'alphanum', 0, 25),
			'brand' => array('brand', 'name', 0, 100),
			'description' => array('description', 'html', 0, 999999),
			'shortDescription' => array('shortDescription', 'html', 0, 999999),
			'imagesSmall' => array('imagesSmall', 'integer', 0, 10),
			'imagesMedium' => array('imagesMedium', 'integer', 0, 10),
			'imagesLarge' => array('imagesLarge', 'integer', 0, 10),
			'availability' => array('availability', 'alpha', 1, 20),
			'cost' => array('cost', 'double', 0, 10),
			'weight' => array('weight', 'double', 0, 9),
			'sortWeight' => array('sortWeight', 'integer', 0, 6),
			'dateCreated' => array('dateCreated', 'datetime', 0, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'packageSiteMap';
		// object tagging tables: array(object tag table, object tag mapping table)
		protected $tagTables = array(
			'tags' => 'productTags',
			'mapping' => 'packageTagMap'
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('packageID', NULL, false);
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
		 *  Create product to package associations
		 *  Args: (array) array of products: array(productID => quantity, ...)
		 *  Return: (boolean) package content added successfully
		 */
		public function addContent($content) {
			if ($this->exists()) {
				$packageID = $this->get('packageID');
				$insertVals = '';
				foreach ($content as $key => $val) {
					$insertVals .= "('".$key."', '".$val['quantity']."', '".$packageID."'), ";
				}
				$insertVals = rtrim($insertVals, ', ');
				$sql = "INSERT INTO `productToPackage` (`productID`, `quantity`, `packageID`) VALUES ".$insertVals;
				$result = $this->dbh->query($sql);
				if ($result->rowCount > 0) {
					return true;
				}
			}
			return false;
		} // function addContent
	} // class package

?>