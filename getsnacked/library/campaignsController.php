<?

	class campaignsController extends controller {
		// controller for specified table
		protected $table = 'campaigns';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'campaignID' => array('type' => 'integer', 'range' => false),
			'type' => array('type' => 'alpha', 'range' => false),
			'name' => array('type' => 'alphanum-search', 'range' => false),
			'availability' => array('type' => 'alpha', 'range' => false),
			'subject' => array('type' => 'name-search', 'range' => false),
			'linkedCampaign' => array('type' => 'integer', 'range' => false),
			'sendInterval' => array('sendInterval' => 'integer', 'range' => false),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'admin' => 'Set Availability: Admin',
				'customer' => 'Set Availability: Customer',
				'affiliate' => 'Set Availability: Affiliate',
				'all' => 'Set Availability: All',
				'none' => 'Set Availability: None',
				'exclusive' => 'Set Availability: Exclusive'
			);
			return $options;
		} // function getQuickUpdateOptions

	} // class campaignsController

?>