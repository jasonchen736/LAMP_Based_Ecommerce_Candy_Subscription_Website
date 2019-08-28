<?

	class productSearchController extends controller {
		// controller for specified table
		protected $table = 'searchTrack';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'searchID' => array('type' => 'integer', 'range' => false),
			'siteID' => array('type' => 'integer', 'range' => false),
			'searchTerm' => array('type' => 'alphanum-search', 'range' => false),
			'searches' => array('type' => 'integer', 'range' => true),
			'date' => array('type' => 'date', 'range' => true)
		);
	} // class productSearchController

?>