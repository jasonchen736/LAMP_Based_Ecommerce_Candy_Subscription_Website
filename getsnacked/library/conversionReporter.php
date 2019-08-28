<?

	class conversionReporter extends recordEditor {

		protected $searchFields = array(
			'ID' => array('type' => 'integer', 'range' => false),
			'subID' => array('type' => 'alphanum', 'range' => false),
			'offerID' => array('type' => 'integer', 'range' => false),
			'campaignID' => array('type' => 'integer', 'range' => false),
			'payoutID' => array('type' => 'integer', 'range' => false),
			'hits' => array('type' => 'integer', 'range' => true),
			'uniqueHits' => array('type' => 'integer', 'range' => true),
			'conversions' => array('type' => 'integer', 'range' => true),
			'date' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct($table) {
			parent::__construct($table, array('trackingID'));
		} // function __construct

		/**
		 *  Return array of search sql where clause components, override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchArray() {
			$search = parent::getSearchArray();
			if (!getRequest('dateFrom') && !getRequest('dateTo')) {
				$search[] = "`date` BETWEEN '".$GLOBALS['_startDate']."' AND '".$GLOBALS['_endDate']."'";
			}
			return $search;
		} // function getSearchArray

	} // class conversionReporter

?>