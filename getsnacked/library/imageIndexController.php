<?

	class imageIndexController extends controller {
		// controller for specified table
		protected $table;
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'imageID' => array('type' => 'integer', 'range' => false),
			'image' => array('type' => 'filename-search', 'range' => false),
			'size' => array('type' => 'integer', 'range' => true),
			'width' => array('type' => 'integer', 'range' => true),
			'height' => array('type' => 'integer', 'range' => true),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'date', 'range' => true)
		);
	} // class imageIndexController

?>