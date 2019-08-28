<?

	class adminUserManager extends recordEditor {

		protected $required = array(
			'login',
			'password',
			'name'
		);

		protected $default = array(
			'created' => array('key' => 'created', 'value' => 'NOW()', 'update' => false)
		);

		protected $function = array(
			'password'
		);

		protected $searchFields = array(
			'userID' => array('type' => 'integer', 'range' => false),
			'login' => array('type' => 'alphanum', 'range' => false),
			'name' => array('type' => 'alphanum', 'range' => false),
			'created' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			parent::__construct('adminUser', array('userID'));
		} // function __construct

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
					if ($key != 'password') {
						$this->record[$key] = clean(getPost($key));
					} else {
						$this->record[$key] = "OLD_PASSWORD('".prepDB(clean(getPost($key)))."')";
					}
				}
			}
			return $this->save();
		} // function addRecord

		/**
		 *  Update a set of given records
		 *  Args: (array) record ids, (str) action
		 *  Return: none
		 */
		public function takeAction($recordIDs, $action) {
			$updated = array();
			if (is_array($recordIDs) && $recordIDs) {
				switch ($action) {
					case 'updatePassword':
						foreach ($recordIDs as $val) {
							if (validNumber($val, 'integer')) {
								$this->loadID($val);
								$this->record['password'] = "OLD_PASSWORD('".prep(getPost('password_'.$val, 'password'))."')";
								$this->update();
								$updated[] = $val;
							}
						}
						if ($updated) {
							addSuccess('Password updated for user(s) (id) '.implode(', ', $updated));
						}
						break;
					case 'delete':
						foreach ($recordIDs as $val) {
							if (validNumber($val, 'integer')) {
								$this->delete(array('userID' => $val));
								$updated[] = $val;
							}
						}
						if ($updated) {
							addSuccess('Deleted user(s) (id) '.implode(', ', $updated));
						}
						break;
					default:
						break;
				}
			} // if (is_array($recordIDs) && $recordIDs)
			if ($updated) {
				return true;
			} else {
				addError('Unable to update users');
				return false;
			}
		} // function takeAction

		/**
		 *  Process, validate and update a user record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateUser() {
			if ($this->record) {
				foreach ($_POST as $key => $val) {
					if ($key != 'userID' && array_key_exists($key, $this->record)) {
						$this->record[$key] = clean(getPost($key));
					}
				}
				$this->update();
				if (haveErrors()) {
					return false;
				} else {
					return true;
				}
			}
			addError('Unable to update user');
			return false;
		} // function updateUser

	} // class adminUserManager

?>