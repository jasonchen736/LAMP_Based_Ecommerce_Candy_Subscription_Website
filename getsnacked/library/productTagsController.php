<?

	class productTagsController extends controller {
		// controller for specified table
		protected $table = 'productTags';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'tagID' => array('type' => 'integer', 'range' => false),
			'tag' => array('type' => 'alphanum-search', 'range' => false)
		);
	} // class productTagsController

?>