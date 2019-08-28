<?

	class product extends activeRecord {
		// active record table
		protected $table = 'products';
		// existing auto increment field
		protected $autoincrement = 'productID';
		// history table (optional)
		protected $historyTable = 'productsHistory';
		// array unique id fields
		protected $idFields = array(
			'productID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'productID' => array('productID', 'integer', 0, 11),
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
			'length' => array('length', 'double', 0, 7),
			'width' => array('width', 'double', 0, 7),
			'height' => array('height', 'double', 0, 7),
			'sortWeight' => array('sortWeight', 'integer', 0, 6),
			'memberID' => array('memberID', 'integer', 0, 10),
			'dateAdded' => array('dateAdded', 'datetime', 0, 19),
			'lastModified' => array('lastModified', 'datetime', 0, 19)
		);
		// object site mapping table
		protected $siteMappingTable = 'productSiteMap';
		// object tagging tables: array(object tag table, object tag mapping table)
		protected $tagTables = array(
			'tags' => 'productTags',
			'mapping' => 'productTagMap'
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('productID', NULL, false);
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

		/**
		 *  Retrieve product inventory count
		 *  Args: none
		 *  Return: (int) product inventory count
		 */
		public function getInventory() {
			$inventory = 0;
			$sql = "SELECT `quantity` FROM `productInventory` WHERE `productID` = '".$this->get('productID')."'";
			$result = $this->dbh->query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				$inventory = $row['quantity'];
			}
			return $inventory;
		} // function getInventory

		/**
		 *  Set product inventory count
		 *  Args: (int) inventory count
		 *  Return: (boolean) set successful
		 */
		public function setInventory($inventory) {
			if ($this->exists() && validNumber($inventory, 'integer')) {
				if ($inventory != $this->getInventory()) {
					$sql = "INSERT INTO `productInventory` (`productID`, `quantity`) VALUES ('".$this->get('productID')."', '".$inventory."') ON DUPLICATE KEY UPDATE `quantity` = '".$inventory."'";
					$result = $this->dbh->query($sql);
					if ($result->rowCount > 0) {
						return true;
					}
				} else {
					return true;
				}
			}
			return false;
		} // function setInventory

	} // class product

?>