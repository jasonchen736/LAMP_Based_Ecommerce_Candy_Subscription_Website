<?

	class offersController extends controller {
		// controller for specified table
		protected $table = 'offers';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'offerID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'alphanum-search', 'range' => false),
			'payType' =>array('type' => 'alpha', 'range' => false),
			'payout' => array('type' => 'double', 'range' => true),
			'defaultPackage' => array('type' => 'integer', 'range' => false),
			'totalShipments' => array('type' => 'integer', 'range' => false),
			'availability' => array('type' => 'alpha', 'range' => false),
			'startDate' => array('type' => 'date', 'range' => true),
			'endDate' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'addTags' => 'Add Tags',
				'removeTags' => 'Remove Tags'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Update cost for offer packages
		 *  Args: (array) offer package data: array([offerID], [packageID], [offerCost])
		 *  Return: (boolean) all items updated successfully
		 */
		public function updateOfferPackages($offerPackageData) {
			$errors = array();
			assertArray($offerPackageData);
			foreach ($offerPackageData as $key => $data) {
				$offer = new offer($data['offerID']);
				if ($offer->exists()) {
					if (!$offer->setPackageCosts(array($data['packageID'] => $data['offerCost']))) {
						$errors[] = 'There was an error while updating Package ID '.$data['packageID'].' for Offer ID '.$data['offerID'];
					}
				}
			}
			if (!empty($errors)) {
				foreach ($errors as $error) {
					addError($error);
				}
				return false;
			} else {
				return true;
			}
		} // function updateOfferPackages

	} // class offersController

?>