<?

	class dataObject {

		// database handler
		protected $dbh;

		/**
		 *  Constructor - construct database object, initialize message variables
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			// instantiate database handler
			$this->dbh = new database;
		} // function __construct

		/**
		 *  Prep for object serialization
		 *  Args: none
		 *  Return: (array) object variable names
		 */
		public function __sleep() {
			$this->dbh = serialize($this->dbh);
			return array_keys(get_object_vars(&$this));
		} // function __sleep

		/**
		 *  Restore objects
		 *  Args: none
		 *  Return: none
		 */
		public function __wakeup() {
			$this->dbh = unserialize($this->dbh);
		} // function __wakeup

		/**
		 *  Retrieve protected data
		 *  Args: (str) data
		 *  Return: (mixed) data
		 */
		public function get($data) {
			return $this->$data;
		} // function get

		/**
		 *  Retrieve an index value from protected array data
		 *  Args: (str) data, index
		 *  Return: (mixed) data
		 */
		public function getArrayData($data, $index) {
			if (!is_array($this->$data)) {
				return null;
			} else {
				if (isset($this->{$data}[$index])) {
					return $this->{$data}[$index];
				} else {
					return null;
				}
			}
		} // function getArrayData

		/**
		 *  Retrieve a value from protected object data
		 *  Args: (str) object, data
		 *  Return: (mixed) data
		 */
		public function getObjectData($object, $data) {
			if (!is_object($this->$object)) {
				return null;
			} else {
				return $this->$object->get($data);
			}
		} // function getObjectData

		/**
		 *  Insert email, opt in date, opt in site to mailing list
		 *  Args: (str) list name, email
		 *  Return: none
		 */
		public function addToList($list, $email) {
			$queryVals = array (
				'~email'     => prepDB($email),
				'optInDate'  => 'NOW()',
				'~optInSite' => prepDB($_SERVER['HTTP_HOST'])
			);
			$this->dbh->perform($list, $queryVals);
		} // function addToList

		/**
		 *  Remove email from mailing list
		 *  Args: (str) list name, email
		 *  Return: none
		 */
		public function removeFromList($list, $email) {
			$this->dbh->query("DELETE FROM `".$list."` WHERE `email` = '".prepDB($email)."'");
		} // function removeFromList

		/**
		 *  Check if email exists in mailing list
		 *  Args: (str) list name, email
		 *  Return: none
		 */
		public function existsInList($list, $email) {
			$this->dbh->query("SELECT `email` FROM `".$list."` WHERE `email` = '".prepDB($email)."'");
			if ($this->dbh->rowCount > 0) return true;
			else return false;
		} // function existsInList

	} // class dataObject

?>