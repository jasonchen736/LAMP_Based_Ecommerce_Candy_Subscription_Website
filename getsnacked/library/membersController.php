<?

	class membersController extends controller {
		// controller for specified table
		protected $table = 'members';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'memberID' => array('type' => 'integer', 'range' => false),
			'company' => array('type' => 'alphanum-search', 'range' => false),
			'first' => array('type' => 'alphanum-search', 'range' => false),
			'last' => array('type' => 'alphanum-search', 'range' => false),
			'email' => array('type' => 'email-search', 'range' => false),
			'city' => array('type' => 'alphanum', 'range' => false),
			'state' => array('type' => 'alphanum', 'range' => false),
			'postal' => array('type' => 'alphanum', 'range' => false),
			'country' => array('type' => 'alpha', 'range' => false),
			'status' => array('type' => 'alpha', 'range' => false),
			'dateCreated' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return array of search values
		 *  Adds additional member search values
		 *    Override
		 *  Args: none
		 *  Return: (array) search values
		 */
		public function getSearchValues() {
			$search = parent::getSearchValues();
			$search['company'] = array();
			$search['company']['value'] = getRequest('company');
			return $search;
		} // function getSearchValues

		/**
		 *  Return array of search sql components
		 *  Rearranges and prepares additional member search components to work with native search method
		 *    Override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchComponents() {
			$search = parent::getSearchComponents();
			$search['select'] = "a.*, `b`.`company`, `b`.`fax`, `b`.`website`, `b`.`taxid`, `b`.`industry`, `b`.`description`, `b`.`payTo`, `b`.`im`, `b`.`dateCreated` AS `businessDateCreated`, `b`.`lastModified` AS `businessLastModified`, `c`.`memberGroupID`";
			$search['tables'][0] = '`'.$this->table.'` `a`';
			$search['tables'][] = 'LEFT JOIN `memberBusinessInfo` `b` ON (`a`.`memberID` = `b`.`memberID`)';
			$search['tables'][] = 'LEFT JOIN `memberGroupMap` `c` ON (`a`.`memberID` = `c`.`memberID` AND `memberGroupID` = 2)';
			foreach ($search['where'] as $field => &$val) {
				switch ($field) {
					case 'company':
						$val = preg_replace('/^(AND |OR )?/', '$1`b`.', $val);
						break;
					default:
						$val = preg_replace('/^(AND |OR )?/', '$1`a`.', $val);
						break;
				}
			}
			if (!empty($search['where'])) {
				reset($search['where']);
				$key = key($search['where']);
				$search['where'][$key] = preg_replace('/^(AND|OR) /', '', $search['where'][$key]);
			}
			$search['order'][] = '`a`.`memberID` ASC';
			return $search;
		} // function getSearchComponents

		/**
		 *  Return array of member group options
		 *  Args: none
		 *  Return: (array) member group options
		 */
		public static function groupOptions() {
			$options = array();
			$sql = "SELECT `memberGroupID`, `group` FROM `memberGroups`";
			$result = query($sql);
			while ($row = $result->fetchRow()) {
				$options[$row['memberGroupID']] = ucfirst($row['group']);
			}
			return $options;
		} // function groupOptions


		/**
		 *  Retrieve an array of business groups ids
		 *  These would impose memberBusinessInfo requirements
		 *  Args: none
		 *  Return: (array) business group ids
		 */
		public static function businessGroupIDs() {
			$ids = array();
			$sql = "SELECT `memberGroupID` FROM `memberGroups` WHERE `type` = 'business'";
			$result = query($sql);
			while ($row = $result->fetchRow()) {
				$ids[] = $row['memberGroupID'];
			}
			return $ids;
		} // function businessGroupIDs

		/**
		 *  Check if member exists by unique email identifier
		 *  Args: (str) email
		 *  Return: (boolean) exists
		 */
		public static function memberExists($email) {
			$result = query("SELECT `memberID` FROM `members` WHERE `email` = '".prep(clean($email, 'email'))."'");
			if ($result->rowCount > 0) {
				return true;
			}
			return false;
		} // function memberExists

		/**
		 *  Retrieve a member's business info
		 *  Args: (int) member id
		 *  Return: (memberBusinessInfo) member business info object
		 */
		public static function getMemberBusinessInfo($memberID) {
			$memberID = clean($memberID, 'integer');
			$sql = "SELECT `memberBusinessInfoID` FROM `memberBusinessInfo` WHERE `memberID` = '".$memberID."'";
			$result = query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				$memberBusinessInfo = new memberBusinessInfo($row['memberBusinessInfoID']);
			} else {
				$memberBusinessInfo = new memberBusinessInfo;
			}
			return $memberBusinessInfo;
		} // function getMemberBusinessInfo

		/**
		 *  Retrieve a member's gateway info
		 *  Args: (int) member id
		 *  Return: (memberGatewayInfo) member gateway info object
		 */
		public static function getMemberGatewayInfo($memberID) {
			$memberID = clean($memberID, 'integer');
			$sql = "SELECT `memberGatewayInfoID` FROM `memberGatewayInfo` WHERE `memberID` = '".$memberID."'";
			$result = query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				$memberGatewayInfo = new memberGatewayInfo($row['memberGatewayInfoID']);
			} else {
				$memberGatewayInfo = new memberGatewayInfo;
			}
			return $memberGatewayInfo;
		} // function getMemberGatewayInfo

		/**
		 *  Retrieve a member's gateway info
		 *  Args: (array) member ids
		 *  Return: (array) array of member gateway info objects
		 */
		public static function getMemberGateways($members) {
			$memberGateways = array();
			$memberGateways[0] = array(
				'memberGatewayInfoID' => 0,
				'memberID' => 0,
				'gateway' => systemSettings::get('GATEWAY'),
				'url' => false,
				'port' => false,
				'login' => false,
				'key' => false,
				'hash' => false,
				'dateAdded' => '0000-00-00 00:00:00',
				'lastModified' => '0000-00-00 00:00:00',
				'decrypted' => false
			);
			switch ($memberGateways[0]['gateway']) {
				case 'authorize':
					$memberGateways[0]['url'] = systemSettings::get('AUTHNETURL');
					$memberGateways[0]['login'] = systemSettings::get('AUTHNETLOGINID');
					$memberGateways[0]['key'] = systemSettings::get('AUTHNETHASHKEY');
					$memberGateways[0]['decrypted'] = systemSettings::get('AUTHNETTRANSACTIONKEY');
					break;
				case 'linkpoint':
					$memberGateways[0]['url'] = systemSettings::get('LINKPOINTHOST');
					$memberGateways[0]['port'] = systemSettings::get('LINKPOINTPORT');
					$memberGateways[0]['key'] = systemSettings::get('LINKPOINTCONFIGFILE');
					break;
				default:
					break;
			}
			$sql = "SELECT `a`.*, AES_DECRYPT(`a`.`hash`, CONCAT(`a`.`dateAdded`, ':', `b`.`dateCreated`)) AS `decrypted` FROM `memberGatewayInfo` `a` JOIN `members` `b` USING (`memberID`) WHERE `a`.`memberID` IN ('".implode("', '", $members)."')";
			$result = query($sql);
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$memberGateways[$row['memberID']] = $row;
				}
			}
			return $memberGateways;
		} // function getMemberGateways

		public static function activateGateway($memberID) {
			$memberID = clean($memberID, 'integer');
			$memberGatewayInfo = self::getMemberGatewayInfo($memberID);
			$memberGatewayInfo->set('status', 'active');
			if ($memberGatewayInfo->exists()) {
				if ($memberGatewayInfo->update()) {
					return true;
				} else {
					addError('There was an error while activating the gateway');
				}
			} else {
				addError('Gateway information was not be found');
			}
			return false;
		} // function activateGateway

		public static function deactivateGateway($memberID) {
			$memberID = clean($memberID, 'integer');
			$memberGatewayInfo = self::getMemberGatewayInfo($memberID);
			$memberGatewayInfo->set('status', 'deactivated');
			if ($memberGatewayInfo->exists()) {
				if ($memberGatewayInfo->update()) {
					return true;
				} else {
					addError('There was an error while deactivating the gateway');
				}
			} else {
				addError('Gateway information was not be found');
			}
			return false;
		} // function deactivateGateway
	} // class membersController

?>