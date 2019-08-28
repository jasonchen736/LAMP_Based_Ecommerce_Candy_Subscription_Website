<?

	class errorHandler {

		/**
		 *  Default error handler, only user errors may be triggered, adheres to error reporting ini settings
		 *  Args: none
		 *  Return: none
		 */
		public static function handleError($errno, $errstr, $errfile, $errline, $errcontext) {
			// adhere to error reporting settings
			if (!($errno & error_reporting())) return;
			$errorTypes = array (
				E_ERROR             => 'Error',
				E_WARNING           => 'Warning',
				E_PARSE             => 'Parsing Error',
				E_NOTICE            => 'Notice',
				E_CORE_ERROR        => 'Core Error',
				E_CORE_WARNING      => 'Core Warning',
				E_COMPILE_ERROR     => 'Compile Error',
				E_COMPILE_WARNING   => 'Compile Warning',
				E_USER_ERROR        => 'User Error',
				E_USER_WARNING      => 'User Warning',
				E_USER_NOTICE       => 'User Notice',
				E_STRICT            => 'Runtime Notice',
				E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
			);
			// generate backtrace string
			$backTrace = debug_backtrace();
			$surface = count($backTrace);
			if ($surface) {
				$surface--;
				krsort($backTrace);
				$traceString = '';
				foreach ($backTrace as $key => &$val) {
					if ($val['function'] == 'handleError') {
						unset($val['args']);
					}
					$traceString .= '#'.($surface - $key).' '.(isset($val['file']) && $val['file'] ? $val['file'].'('.$val['line'].')' : '{main}').': '.$val['function'].'()
		';
				}
			} else {
				$traceString = 'unknown';
			}
			$uniqueError = array('error', $errno, $errfile, $errline, isset($backTrace[$surface]['function']) ? $backTrace[$surface]['function'] : '');
			// prevent from excessive logging of any particular error
			$excessiveErrors = getSession('_errorOverflow');
			assertArray($excessiveErrors);
			if (!in_array($uniqueError, $excessiveErrors)) {
				$dbh = database::getInstance();
				$queryVals = array(
					'~class'             => 'error',
					'code'               => prepDB($errno),
					'~type'              => prepDB($errorTypes[$errno]),
					'~file'              => prepDB($errfile),
					'line'               => prepDB($errline),
					'~function'          => isset($backTrace[$surface]['function']) ? prepDB($backTrace[$surface]['function']) : '',
					'~initialErrorTrace' => prepDB($traceString),
					'~message'           => prepDB($errstr),
					'date'               => 'CURDATE()',
				);
				$tail = "ON DUPLICATE KEY UPDATE `errorCount` = `errorCount` + 1, `status` = IF(`errorCount` > 100, 'overflow', `status`)";
				$dbh->perform('errorTracking', $queryVals, $tail);
			}
			// retrieve and store into session any overflowed errors to prevent excessive logging
			self::retrieveErrorOverflow();
			// handle flow
			switch ($errno) {
				case E_USER_ERROR:
					if (isDevEnvironment()) {
						self::printError('Error', $errno, $errorTypes[$errno], $errfile, $errline, $errstr, $traceString);
					} else {
						redirect(systemSettings::get('ERRORPAGE'));
					}
					break;
				case E_USER_WARNING:
					self::printError('Error', $errno, $errorTypes[$errno], $errfile, $errline, $errstr, $traceString);
					break;
				case E_USER_NOTICE:
					self::printError('Error', $errno, $errorTypes[$errno], $errfile, $errline, $errstr, $traceString);
					break;
				default:
					// unknown error
					self::printError('Error', $errno, $errorTypes[$errno], $errfile, $errline, $errstr, $traceString);
					break;
			}
		} // function handleError
	
		/**
		 *  Retrieves and store any error overflows into session
		 *    this is a protective function to prevent error logging from overloading the database
		 *    if any error is recorded too many times, this will retrieve that error
		 *      and stop further database logging for it
		 *  Args: none
		 *  Return: none
		 */
		 static function retrieveErrorOverflow() {
			$result = query("SELECT `class`, `code`, `file`, `line`, `function` FROM `errorTracking` WHERE `status` IN ('overflow', 'uncaughtoverflow')");
			if ($result->rowCount) {
				$_SESSION['_errorOverflow'] = array();
				while ($row = $result->fetchAssoc()) {
					if (!in_array($row, $_SESSION['_errorOverflow'])) {
						$_SESSION['_errorOverflow'][] = $row;
					}
				}
			}
		 } // function retrieveErrorOverflow
	
		/**
		 *  Displays error/exception details only on development environment with debug mode
		 *  Args: (str) error code, error type, error file, error line, error message, error trace
		 *  Return: none
		 */
		public static function printError($errclass, $errno, $errtype, $errfile, $errline, $errmsg, $errtrace) {
			$error = 'Code: '.$errno.'<br>Type: '.$errtype.'<br>File: '.$errfile.'<br>Line: '.$errline.'<br>Message: '.$errmsg.'<br>Trace:<br>'.$errtrace;
			debug($error, $errclass, '', 'error');
		} // function printError

		public static function setHandler() {
			set_error_handler(array('errorHandler', 'handleError'));
		} // function setHandler

	} // class errorHandler

?>