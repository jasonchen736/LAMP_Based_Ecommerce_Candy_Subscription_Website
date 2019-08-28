<?

	class campaignsManager extends recordEditor {

		protected $required = array(
			'type',
			'name',
			'availability',
			'subject',
			'html',
			'text',
			'fromEmail'
		);

		protected $default = array(
			'dateAdded' => array('key' => 'dateAdded', 'value' => 'NOW()', 'update' => false),
			'lastModified' => array('key' => 'lastModified', 'value' => 'NOW()', 'update' => true)
		);

		protected $searchFields = array(
			'campaignID' => array('type' => 'integer', 'range' => false),
			'type' => array('type' => 'alphanum', 'range' => false),
			'name' => array('type' => 'alphanum', 'range' => false),
			'availability' => array('type' => 'alphanum', 'range' => false),
			'subject' => array('type' => 'alphanum', 'range' => false),
			'html' => array('type' => 'alphanum', 'range' => false),
			'text' => array('type' => 'alphanum', 'range' => false),
			'fromEmail' => array('type' => 'alphanum', 'range' => false),
			'linkedCampaign' => array('type' => 'integer', 'range' => false),
			'sendInterval' => array('type' => 'integer', 'range' => false),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'dagte', 'range' => true)
		);

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			parent::__construct('campaigns', array('campaignID'));
		} // function __construct

		/**
		 *  Return an array of available actions
		 *  Args: none
		 *  Return: (array) actions
		 */
		public function getActions() {
			$actions = array();
			$actions = array_merge($actions, $this->fields['availability']);
			return $actions;
		} // function getActions

		/**
		 *  Update a set of given records
		 *  Args: (array) record ids, (str) action
		 *  Return: none
		 */
		public function takeAction($recordIDs, $action) {
			$updated = array();
			if (is_array($recordIDs) && $recordIDs) {
				if(in_array($action, $this->fields['availability'])) {
					foreach ($recordIDs as $val) {
						if (validNumber($val, 'integer')) {
							$this->loadID($val);
							$this->record['availability'] = $action;
							$this->update();
							$updated[] = $val;
						}
					}
					if ($updated) {
						addSuccess('Campaigns (id) '.implode(', ', $updated).' updated');
					}
				}
			} // if (is_array($recordIDs) && $recordIDs)
			if ($updated) {
				return true;
			} else {
				addError('Unable to update campaigns');
				return false;
			}
		} // function takeAction

		/**
		 *  Process, validate and add a record from post input, override
		 *  Args: (array) fields to ignore
		 *  Return: (boolean) success
		 */
		public function addRecord($ignore = false) {
			$this->record = array();
			if (!$ignore || !is_array($ignore)) {
				$ignore = array();
			}
			foreach ($this->fields as $key => $val) {
				if (!in_array($key, $ignore)) {
					switch ($key) {
						case 'html':
						case 'text':
							$this->record[$key] = clean(getPost($key), 'html-campaign');
							break;
						default:
							$this->record[$key] = clean(getPost($key));
							break;
					}
				}
			}
			return $this->save();
		} // function addRecord

		/**
		 *  Process, validate and update a campaign record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateCampaign() {
			if ($this->record) {
				foreach ($_POST as $key => $val) {
					if ($key != 'campaignID' && array_key_exists($key, $this->record)) {
						switch ($key) {
							case 'html':
							case 'text':
								$this->record[$key] = clean(getPost($key), 'html-campaign');
								break;
							default:
								$this->record[$key] = clean(getPost($key));
								break;
						}
					}
				}
				$this->update();
				if (haveErrors()) {
					return false;
				} else {
					return true;
				}
			}
			addError('Unable to update campaign');
			return false;
		} // function updateCampaign

	} // class campaignsManager

?>
