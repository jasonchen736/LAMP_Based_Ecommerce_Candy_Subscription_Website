<?

	class offer extends activeRecord {
		// active record table
		protected $table = 'offers';
		// existing auto increment field
		protected $autoincrement = 'offerID';
		// history table (optional)
		protected $historyTable = 'offersHistory';
		// array unique id fields
		protected $idFields = array(
			'offerID'
		);
		// array of valid availability types
		protected $requestorTypes = array(
			'customer',
			'affiliate'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'offerID'            => array('offerID', 'integer', 0, 11),
			'name'               => array('name', 'alphanum', 1, 255),
			'description'        => array('description', 'alphanum', 0, 255),
			'longDescription'    => array('longDescription', 'alphanum', 0, 999999),
			'link'               => array('link', 'url', 0, 255),
			'payType'            => array('payType', 'alpha', 1, 25),
			'payout'             => array('payout', 'float', 1, 9),
			'defaultPackage'     => array('defaultPackage', 'integer', 1, 11),
			'totalShipments'     => array('totalShipments', 'integer', 1, 11),
			'availability'       => array('availability', 'alpha', 1, 25),
			'startDate'          => array('startDate', 'datetime', 1, 19),
			'endDate'            => array('endDate', 'datetime', 1, 19),
			'terms'              => array('terms', 'alphanum', 0, 999999),
			'image'              => array('image', 'integer', 0, 1),
			'unsubLink'          => array('unsubLink', 'url', 0, 255),
			'unsubFile'          => array('unsubFile', 'filename', 0, 255),
			'availablePackages'  => array('availablePackages', 'clean', 0, 999999),
			'availableCampaigns' => array('availableCampaigns', 'clean', 0, 999999),
			'dateAdded'          => array('dateAdded', 'datetime', 0, 19),
			'lastModified'       => array('lastModified', 'datetime', 0, 19)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('offerID', NULL, false);
			// handle available packages
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
			// handle available packages
			$this->set('lastModified', 'NOW()', false);
			$this->enclose('lastModified', false);
		} // function assertUpdateDefaults

		/**
		 *  Check whether offer is active and available to a user
		 *  Args: (str) requestor type - customer, affiliate, etc., (int) requestor id
		 *  Return: (boolean) offer available
		 */
		public function isAvailable($requestor, $requestorID) {
			$start = $this->get('startDate');
			$end = $this->get('endDate');
			if ($start && $end) {
				$start = strtotime($start);
				$end = strtotime($end);
				$now = time();
				if ($start <= $now && $now <= $end) {
					$availability = $this->get('availability');
					if ($availability == 'all' || $availability == $requestor) {
						return true;
					} elseif ($availability == 'exclusive') {
						if (in_array($requestor, $this->requestorTypes)) {
							$sql = "SELECT `exclusiveOfferID` 
									FROM `exclusiveOffers` 
									WHERE `offerID` = '".$this->get('offerID')."' 
									AND `type` = '".$requestor."' 
									AND `ID` = '".prep($requestorID)."'";
							$result = $this->dbh->query($sql);
							if ($result->rowCount) {
								return true;
							}
						}
					}
				}
			}
			return false;
		} // function isAvailable

		/**
		 *  Retrieve offer payout information
		 *  Args: (str) requestor type - customer, affiliate, etc., (int) requestor id
		 *  Return: (int) payout id
		 */
		public function getPayout($requestor, $requestorID) {
			$payoutID = 1;
			if (in_array($requestor, $this->requestorTypes)) {
				$sql = "SELECT `payoutID` FROM `customPayouts` WHERE `type` = '".$requestor."' AND `offerID` = '".$this->get('offerID')."' AND `ID` = '".prep($requestorID)."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					$payoutID = $result->fetchRow();
					$payoutID = $payoutID['payoutID'];
				}
			}
			return $payoutID;
		} // function getPayout

		/**
		 *  Return array of existing offer package id and cost: array(packageID => cost)
		 *  Args: none
		 *  Return: (array) packages and costs
		 */
		public function getPackages() {
			$packageData = array();
			$sql = "SELECT `packageID`, `cost` FROM `packageToOffer` WHERE `offerID` = '".$this->get('offerID')."'";
			$result = $this->dbh->query($sql);
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$packageData[$row['packageID']] = $row['cost'];
				}
			}
			return $packageData;
		} // function getPackages

		/**
		 *  Set costs for existing offer packages
		 *  Args: (array) package/cost data: array(packageID => cost);
		 *  Return: (boolean) offer package cost updated
		 */
		public function setPackageCosts($packageData) {
			assertArray($packageData);
			$errors = array();
			if (!empty($packageData)) {
				$existingPackageData = $this->getPackages();
				$newPackageData = $existingPackageData;
				$update = array();
				foreach ($packageData as $key => $val) {
					if (array_key_exists($key, $existingPackageData) && $val != $existingPackageData[$key]) {
						$update[$key] = $val;
					}
				}
				if (!empty($update)) {
					$offerID = $this->get('offerID');
					foreach ($update as $key => $val) {
						$sql = "UPDATE `packageToOffer` SET `cost` = '".prep($val)."' WHERE `offerID` = '".$offerID."' AND `packageID` = '".prep($key)."'";
						$result = $this->dbh->query($sql);
						if (!$result->rowCount) {
							$errors[] = 'There was an error while updating Package ID '.$key.' for Offer ID '.$offerID;
						}
					}
				}
			}
			$newData = $this->getPackages();
			ksort($newData);
			$availablePackages = '';
			foreach ($newData as $package => $cost) {
				$availablePackages .= $package.'-'.number_format($cost, 2, '.', '').';';
			}
			$availablePackages = rtrim($availablePackages, ';');
			$this->set('availablePackages', $availablePackages);
			if (!$this->update()) {
				$errors[] = 'There was an error while updating the offer data';
			}
			if (empty($errors)) {
				return true;
			} else {
				foreach ($errors as $error) {
					addError($error);
				}
				return false;
			}
		} // function setPackageCosts

	} // class offer

?>