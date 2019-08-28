<?

	class searchReporter extends recordEditor {

		protected $searchFields = array(
			'searchTerm' => array('type' => 'alphanum-search', 'range' => false),
			'searches' => array('type' => 'integer', 'range' => true),
			'date' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			parent::__construct('searchTrack', array('searchID'));
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