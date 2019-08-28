<?

	class result {

		// database result
		private $result = false;
		public $rowCount;
		// when using SQL_CALC_FOUND_ROWS
		public $foundRows;
		public $insertID;
		public $sqlError;
		public $sqlErrorNumber;

		/**
		 *  Set database results
		 *  Args: none
		 *  Return: none
		 */
		public function __construct($result, $rowCount, $foundRows, $insertID, $sqlError, $sqlErrorNumber) {
			$this->result = $result;
			$this->rowCount = $rowCount;
			$this->foundRows = $foundRows;
			$this->insertID = $insertID;
			$this->sqlError = $sqlError;
			$this->sqlErrorNumber = $sqlErrorNumber;
		} // function __construct

		/**
		 *  Return row as associative array from current result index
		 *  Args: none
		 *  Return: (array) current result row
		 */
		public function fetchRow() {
			return mysql_fetch_assoc($this->result);
		} // function fetchRow

		/**
		 *  Return all rows from current result resource as associative array
		 *  Args: none
		 *  Return: (array) all rows from result
		 */
		public function fetchAll() {
			$return = array();
			while ($row = mysql_fetch_assoc($this->result)) {
				$return[] = $row;
			}
			return $return;
		} // function fetchAll

		/**
		 *  Return row as associative array from current result index
		 *  Args: none
		 *  Return: (array) current result row
		 */
		public function fetchAssoc() {
			return $this->fetchRow();
		} // function fetchAssoc

		/**
		 *  Return all rows from current result resource as associative array
		 *  Args: none
		 *  Return: (array) all rows from result
		 */
		public function fetchAllAssoc() {
			return $this->fetchAll();
		} // function fetchAllAssoc

	} // class result

?>