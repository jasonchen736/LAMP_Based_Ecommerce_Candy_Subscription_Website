<?

	class baseException extends Exception {
	
		// file, line, function that triggered the exception (surface of trace)
		protected $sFile, $sLine, $sFunction;
		// associate error level values with names
		protected $errorTypes = array (
			E_USER_ERROR        => 'Fatal',
			E_USER_WARNING      => 'Warning',
			E_USER_NOTICE       => 'Notice'
		);
	
		/**
		 *  Set exception values, particularly the details that initiated the exception trace
		 *  Args: none
		 *  Return: none
		 */
		public function __construct($message, $code = 0) {
			parent::__construct($message, $code);
			$trace = $this->getTrace();
			if (!empty($trace)) {
				$surface = count($trace) - 1;
				$this->sFile = $trace[$surface]['file'];
				$this->sLine = $trace[$surface]['line'];
				$this->sFunction = $trace[$surface]['function'];
			} else {
				$this->sFile = $this->file;
				$this->sLine = $this->line;
				$this->sFunction = isset($trace[0]['function']) ? $trace[0]['function'] : '';
			}
		} // function __construct
	
		/**
		 *  Returns internal variable
		 *  Args: (str) variable name
		 *  Returns: (mixed) internal variable
		 */
		public function get($data) {
			return $this->$data;
		} // function get
	
		/**
		 *  Logs exception into database, can distinguish between uncaught exceptions
		 *    preventive against excessive exceptions logging
		 *  Args: none
		 *  Return: none
		 */
		public function logException($uncaught = false) {
			$uniqueEx = array('exception', $this->code, $this->sFile, $this->sLine, $this->sFunction);
			// prevent from excessive logging of any particular exception
			if (!isset($_SESSION['errorOverflow']) || !is_array($_SESSION['errorOverflow']) || !in_array($uniqueEx, $_SESSION['errorOverflow'])) {
				$dbh = new database;
				$queryVals = array(
					'~class'             => 'exception',
					'code'               => $this->code,
					'~type'              => $this->errorTypes[$this->code],
					'~file'              => $this->sFile,
					'line'               => $this->sLine,
					'~function'          => $this->sFunction,
					'~initialErrorTrace' => $this->getTraceAsString(),
					'~message'           => $this->message,
					'date'               => 'CURDATE()',
				);
				if ($uncaught) {
					$queryVals['~status'] = 'uncaught';
					$overflow = 'uncaughtoverflow';
				} else {
					$overflow = 'overflow';
				}
				$tail = "ON DUPLICATE KEY UPDATE `errorCount` = `errorCount` + 1, `status` = IF(`errorCount` > 100, '".$overflow."', `status`)";
				$dbh->perform('errorTracking', $queryVals, $tail);
			}
		} // function logException
	
		/**
		 *  Display exception, uses debug (will only print on development with debug mode)
		 *  Args: none
		 *  Return: none
		 */
		public function printException() {
			printError('Exception', $this->code, $this->errorTypes[$this->code], $this->sFile, $this->sLine, $this->message, $this->getTraceAsString());
		} // function printException
	
		/**
		 *  Stores exception into session array debugErrors
		 *  Args: none
		 *  Return: none
		 */
		public function storeException() {
			$_SESSION['debugErrors'] = array();
			$_SESSION['debugErrors'][] = array('Error', $errno, $errorTypes[$errno], $errfile, $errline, $errstr, $traceString);
		} // function storeException
	
	} // class baseException

?>