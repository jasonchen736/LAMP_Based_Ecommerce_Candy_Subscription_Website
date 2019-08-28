<?

	class siteRegistryController extends controller {
		// controller for specified table
		protected $table = 'siteRegistry';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'siteID' => array('type' => 'integer', 'range' => false),
			'siteName' => array('type' => 'clean-search', 'range' => false),
			'dateCreated' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return an array of registered sites
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getSites() {
			$dbh = database::getInstance();
			$sql = "SELECT `siteID`, `siteName` FROM `siteRegistry`";
			$result = $dbh->query($sql);
			$sites = array();
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$sites[$row['siteID']] = $row['siteName'];
				}
			}
			return $sites;
		} // function getSites

	} // class siteRegistryController

?>