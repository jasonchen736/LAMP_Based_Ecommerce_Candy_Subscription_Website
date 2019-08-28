<?

	class contentController extends controller {
		// controller for specified table
		protected $table = 'content';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'contentID' => array('type' => 'integer', 'range' => false),
			'site' => array('type' => 'alphanum-search', 'range' => false),
			'name' => array('type' => 'alphanum-search', 'range' => false),
			'content' => array('type' => 'alphanum-search', 'range' => false),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'date', 'range' => true)
		);
	} // class contentController

?>