<?

	/**
	 *  Note: Running from console or cron requires ini values to be set
	 */
	class cron {

		// database handler
		private $dbh;
		// cron script (full path)
		private $script;
		// flag: execute ok
		private $execute;
		// status message array
		private $statusMsgs;
		// cron log id
		private $logID = false;

		/**
		 *  Instantiate database handler, set script
		 *  Args: none
		 *  Return: none
		 */
		public function __construct($script) {
			$this->dbh = new database;
			$this->script = $script;
			$this->statusMsgs = array();
			if (!$this->script) {
				$this->execute = false;
				$this->statusMsgs[] = 'Invalid file name';
			} elseif (!file_exists($this->script)) {
				$this->execute = false;
				$this->statusMsgs[] = 'File does not exist';
			} else {
				$this->execute = true;
				$this->statusMsgs[] = 'File found';
			}
			$this->logStart();
		} // function __construct

		/**
		 *  Empty
		 *  Args: none
		 *  Return: none
		 */
		public function __destruct() {
		} // function __destruct()

		/**
		 *  Log cron data, automatically appending status messages
		 *  Args: (array) Query values
		 *  Return: none
		 */
		private function logData($queryVals = array(), $perform = 'update') {
			if (!is_array($queryVals)) {
				$queryVals = array();
			}
			$status = $this->statusMsgs;
			foreach ($status as $key => &$val) {
				$val = prepDB($val);
			}
			$queryVals['~status'] = implode(';;', $status);
			if ($perform == 'insert') {
				$this->dbh->perform('cronLog', $queryVals);
				$this->logID = $this->dbh->insertID;
			} elseif ($this->logID) {
				$where = "WHERE `cronLogID` = ".$this->logID;
				$this->dbh->perform('cronLog', $queryVals, $where, 'update');
			} else {
				trigger_error('Cron Error: Updating cron log without a log ID on '.prepDB($this->script), E_USER_WARNING);
				exit;
			}
		} // function logData

		/**
		 *  Log script begin
		 *  Args: none
		 *  Return: none
		 */
		private function logStart() {
			$queryVals = array(
				'~script' => prepDB($this->script),
				'start'   => 'NOW()'
			);
			$this->logData($queryVals, 'insert');
		} // function logStart

		/**
		 *  Log script end
		 *  Args: none
		 *  Return: none
		 */
		private function logEnd() {
			$queryVals = array(
				'end' => 'NOW()'
			);
			$this->logData($queryVals);
		} // function logEnd

		/**
		 *  Execute cron script
		 *  Args: none
		 *  Return: none
		 */
		public function executeScript() {
			if ($this->execute) {
				$this->statusMsgs[] = 'Execution begin';
				$this->logData();
				require $this->script;
				$this->statusMsgs[] = 'Execution End';
			} else {
				$this->statusMsgs[] = 'Script not executed';
			}
			$this->logEnd();
		} // function executeScript

	} // class cron

?>