<?

	class tracker {

		// possible referral types
		public static $referralTypes = array(
			'customer',
			'affiliate'
		);
		// fields mapped to request field names
		public static $requestFields = array(
			'ID'             => 'a',
			'subID'          => 's',
			'campaignID'     => 'c',
			'offerID'        => 'o',
			'passThroughVar' => 'info',
			'landingPackage' => 'p'
		);
		// fields stored in session, mapped to session field names
		public static $sessionFields = array(
			'ID'             => 'ID',
			'subID'          => 'subID',
			'campaignID'     => 'campaignID',
			'offerID'        => 'offerID',
			'payoutID'       => 'payoutID',
			'passThroughVar' => 'passThroughVar',
			'landingPackage' => 'landingPackage',
			'referralType'   => 'referralType'
		);
		// fields stored in cookie
		public static $cookieFields = array(
			'ID',
			'subID',
			'campaignID',
			'offerID',
			'passThroughVar',
			'landingPackage'
		);
		// fields tracked in tracking table (site id will be tracked by default)
		public static $trackingFields = array(
			'ID',
			'subID',
			'campaignID',
			'offerID',
			'payoutID'
		);
		public static $fieldData = array(
			'ID'             => array('ID', 'integer', 0, 11),
			'subID'          => array('subID', 'clean', 0, 100),
			'campaignID'     => array('campaignID', 'integer', 0, 11),
			'offerID'        => array('offerID', 'integer', 0, 11),
			'payoutID'       => array('payoutID', 'integer', 0, 11),
			'passThroughVar' => array('passThroughVar', 'clean', 0 , 75),
			'landingPackage' => array('landingPackage', 'integer', 0 , 11),
			'referralType'   => array('referralType', 'alpha', 0 , 25)
		);
		public static $trackingTables = array(
			'customer'  => 'tracking',
			'affiliate' => 'affiliateTracking',
			'invalid'   => 'invalidTracking'
		);

		/**
		 *  Clean and store a tracking value
		 *  Args: (string) field name, (mixed) value
		 *  Return: none
		 */
		public static function set($field, $value) {
			if (array_key_exists($field, self::$sessionFields)) {
				$fieldData = self::$fieldData[$field];
				$_SESSION['tracker::'.self::$sessionFields[$field]] = clean($value, $fieldData[1], $fieldData[3]);
			}
		} // function set

		/**
		 *  Retrieve a stored tracking value
		 *  Args: (string) field name
		 *  Return: (mixed) field value
		 */
		public static function get($field) {
			if (array_key_exists($field, self::$sessionFields)) {
				return getSession('tracker::'.self::$sessionFields[$field]);
			} else {
				return NULL;
			}
		} // function get

		/**
		 *  Detect if user has just landed on the site
		 *  Args: none
		 *  Return: (boolean) just landed
		 */
		public static function justLanded() {
			return !isset($_SESSION['tracker::'.self::$sessionFields['referralType']]);
		} // function justLanded

		/**
		 *  Detect if a visitor was referred
		 *  Args: none
		 *  Return: (boolean) was referred
		 */
		public static function wasReferred() {
			if (self::justLanded()) {
				if (getRequest(self::$requestFields['ID']) || getRequest(self::$requestFields['subID'])) {
					return true;
				} else {
					return false;
				}
			} else {
				if (getSession('tracker::'.self::$sessionFields['ID']) || getSession('tracker::'.self::$sessionFields['subID'])) {
					return true;
				} else {
					return false;
				}
			}
		} // function wasReferred

		/**
		 *  On user landing, retrieve reference data (including offer payout id), track data, set cookie
		 *  Args: none
		 *  Return: none
		 */
		public static function trackLanding() {
			if (self::justLanded()) {
				// establish reference data
				$isUniqueVisitor = self::retrieveReferral();
				// record reference data
				self::logReference($isUniqueVisitor);
				// set reference cookie
				self::dropReferenceCookie();
			}
		} // function trackLanding

		/**
		 *  Retrieve reference data from the query string
		 *    set affiliate id, referral type, sub id, pass through variable, campaign id, offer id
		 *  Args: none
		 *  Return: (boolean) unique visitor
		 */
		public static function retrieveReferral() {
			$isUnique = true;
			// check request parameters first
			$referralType = 'customer';
			$referrer = getRequest(self::$requestFields['ID']);
			if (validNumber($referrer, 'integer')) {
				$ID = $referrer;
			} elseif ($referrer{0} == 'a') {
				// affiliates have an id prefix of "a"
				$ID = substr($referrer, 1, strlen($referrer) - 1);
				if (validNumber($ID, 'integer')) {
					$referralType = 'affiliate';
				} else {
					$ID = 0;
				}
			} else {
				$ID = 0;
			}
			$subID = getRequest(self::$requestFields['subID'], 'clean');
			$campaignID = getRequest(self::$requestFields['campaignID']);
			$campaignID = validNumber($campaignID, 'integer') ? $campaignID : 0;
			$offerID = getRequest(self::$requestFields['offerID']);
			$offerID = validNumber($offerID, 'integer') ? $offerID : 0;
			$passThroughVar = getRequest(self::$requestFields['passThroughVar'], 'alphanum');
			$landingPackage = getRequest(self::$requestFields['landingPackage']);
			$landingPackage = validNumber($landingPackage, 'integer') ? $landingPackage : 0;
			// check if user has visted before
			$cookie = getCookie(systemSettings::get('MAINCOOKIE'));
			if ($cookie) {
				// if user already had a cookie,
				//   not a unique visitor
				$isUnique = false;
				//   but came back to the site without referral id, the previous affiliate will be credited
				//   ID;subID;campaignID;offerID;passThroughVar
				if ($ID == 0) {
					$referralType = 'customer';
					$cookieData = explode(';', $cookie);
					$ID = isset($cookieData[0]) ? $cookieData[0] : 0;
					if (!validNumber($ID, 'integer')) {
						if ($ID{0} == 'a') {
							$ID = substr($ID, 1, strlen($ID) - 1);
							if (validNumber($ID, 'integer')) {
								$referralType = 'affiliate';
							} else {
								$ID = 0;
							}
						} else {
							$ID = 0;
						}
					}
					$subID = isset($cookieData[1]) ? $cookieData[1] : '';
					$campaignID = isset($cookieData[2]) ? $cookieData[2] : 0;
					if (!validNumber($campaignID, 'integer')) {
						$campaignID = 0;
					}
					$offerID = isset($cookieData[3]) ? $cookieData[3] : 0;
					if (!validNumber($offerID, 'integer')) {
						$offerID = 0;
					}
					$passThroughVar = isset($cookieData[4]) ? $cookieData[4] : '';
					$landingPackage = isset($cookieData[5]) ? $cookieData[5] : '';
					if (!validNumber($landingPackage, 'integer')) {
						$landingPackage = 0;
					}
				}
			}
			// determine tracking table
			if ($ID) {
				if (in_array($referralType, self::$referralTypes)) {
					$result = query("SELECT `memberID` FROM `members` WHERE `memberID` = '".$ID."'");
					if (!$result->rowCount) {
						$referralType = 'invalid';
					}
				} else {
					$referralType = 'invalid';
				}
			}
			// establish offer data
			$payoutID = 0;
			if ($offerID) {
				$offer = new offer($offerID);
				if ($offer->exists() && $offer->isAvailable($referralType, $ID)) {
					// set landing package
					$availablePackages = $offer->get('availablePackages');
					if (!$landingPackage || !preg_match('/'.$landingPackage.'/', $avaialbePackages)) {
						$landingPackage = $offer->get('defaultPackage');
					}
					// set payout id, payout id of 0 indicates no payment
					$payoutID = $offer->getPayout($referralType, $ID);
				}
			}
			// cleanse and set sessions
			foreach (self::$sessionFields as $field => $val) {
				self::set($field, $$field);
			}
			return $isUnique;
		} // function retrieveReferral

		/**
		 *  Enter tracking information into table (will track site id by default)
		 *  Args: (boolean) unique visitor
		 *  Return: none
		 */
		public static function logReference($isUnique) {
			$insertFields = '';
			$insertValues = '';
			foreach (self::$trackingFields as $field) {
				if ($field != 'passThroughVar') {
					$insertFields .= '`'.self::$fieldData[$field][0].'`, ';
					$insertValues .= "'".prep(getSession('tracker::'.self::$sessionFields[$field]))."', ";
				}
			}
			$insertFields .= '`date`, `hits`, `uniqueHits`, `conversions`';
			$insertValues .= 'CURDATE(), 1, 1, 0';
			$referralType = getSession('tracker::'.self::$sessionFields['referralType']);
			if (!$referralType || !array_key_exists($referralType, self::$trackingTables)) {
				$referralType = 'invalid';
			}
			$sql = "INSERT INTO `".self::$trackingTables[$referralType]."` 
					(`siteID`, ".$insertFields.") 
					VALUES ('".systemSettings::get('SITEID')."', ".$insertValues.") 
					ON DUPLICATE KEY UPDATE `hits` = `hits` + 1";
			if ($isUnique) {
				$sql .= ', `uniqueHits` = `uniqueHits` + 1';
			}
			query($sql);
		} // function logReference

		/**
		 *  Enter tracking information into table (will track site id by default)
		 *  Args: none
		 *  Return: none
		 */
		public static function logConversion() {
			$insertFields = '';
			$insertValues = '';
			foreach (self::$trackingFields as $field) {
				if ($field != 'passThroughVar') {
					$insertFields .= '`'.self::$fieldData[$field][0].'`, ';
					$insertValues .= "'".prep(getSession('tracker::'.self::$sessionFields[$field]))."', ";
				}
			}
			$insertFields .= '`date`, `hits`, `uniqueHits`, `conversions`';
			$insertValues .= 'CURDATE(), 0, 0, 1';
			$referralType = getSession('tracker::'.self::$sessionFields['referralType']);
			if (!$referralType || !array_key_exists($referralType, self::$trackingTables)) {
				$referralType = 'invalid';
			}
			$sql = "INSERT INTO `".self::$trackingTables[$referralType]."` 
					(`siteID`, ".$insertFields.") 
					VALUES ('".systemSettings::get('SITEID')."', ".$insertValues.") 
					ON DUPLICATE KEY UPDATE `conversions` = `conversions` + 1";
			query($sql);
		} // function logConversion

		/**
		 *  Write reference cookie, set time to expire in 30 days available to all pages in default domain
		 *    cookie: ID;subID;campaignID;offerID;passThroughVar;landingPackage
		 *  Args: none
		 *  Return: none
		 */
		public static function dropReferenceCookie() {
			$cookie = '';
			foreach (self::$cookieFields as $field) {
				$cookie .= getSession('tracker::'.self::$sessionFields[$field]).';';
			}
			$cookie = substr($cookie, 0, -1);
			if (getSession('tracker::'.self::$sessionFields['referralType']) == 'affiliate') {
				$cookie = 'a'.$cookie;
			}
			setcookie(systemSettings::get('MAINCOOKIE'), $cookie, time() + 60 * 60 * 24 * 30, '/', systemSettings::get('COOKIEDOMAIN'));
		} // function dropReferenceCookie

		/**
		 *  Clear all tracked offer data
		 *  Args: none
		 *  Return: none
		 */
		public static function clearOfferData() {
			self::set('offerID', 0);
			self::set('payoutID', 0);
			self::set('landingPackage', 0);
		} // function clearOfferData

	} // class tracker

?>