<?

	/**
	 *  This class provides an interface to access and manipulate all system messages and errored fields
	 *  This class is essentially a wrapper for the following session arrays:
	 *    main notifications array
	 *      $_SESSION['_systemNotifications']
	 *    messages passed in from request
	 *      $_SESSION['_systemNotifications']['r_errorMessages']
	 *      $_SESSION['_systemNotifications']['r_successMessages']
	 *      $_SESSION['_systemNotifications']['r_generalMessages']
	 *    messages generated at run time
	 *      $_SESSION['_systemNotifications']['errorMessages']
	 *      $_SESSION['_systemNotifications']['successMessages']
	 *      $_SESSION['_systemNotifications']['generalMessages']
	 *    errored field names
	 *      $_SESSION['_systemNotifications']['errorFields']
	 */
	class systemNotifications {

		/**
		 *  Initialize message arrays
		 *  Args: none
		 *  Return: none
		 */
		public static function initialize() {
			assertArray($_SESSION['_systemNotifications']);
			assertArray($_SESSION['_systemNotifications']['r_errorMessages']);
			assertArray($_SESSION['_systemNotifications']['r_successMessages']);
			assertArray($_SESSION['_systemNotifications']['r_generalMessages']);
			assertArray($_SESSION['_systemNotifications']['errorMessages']);
			assertArray($_SESSION['_systemNotifications']['successMessages']);
			assertArray($_SESSION['_systemNotifications']['generalMessages']);
			assertArray($_SESSION['_systemNotifications']['errorFields']);
		} // function initialize

		/**
		 *  Parse request array for messages (including sessions)
		 *    errorMessage, successMessage, generalMessage
		 *  Args: none
		 *  Return: none
		 */
		public static function retrieveRequestMessages() {
			$errorMessage = getRequest('error', 'word');
			$successMessage = getRequest('success', 'word');
			$generalMessage = getRequest('message', 'word');
			if ($errorMessage) {
				$errorMessage = explode(';', $errorMessage);
				$_SESSION['_systemNotifications']['r_errorMessages'] = array_unique(array_merge($_SESSION['_systemNotifications']['r_errorMessages'], $errorMessages));
			}
			if ($successMessage) {
				$successMessage = explode(';', $successMessage);
				$_SESSION['_systemNotifications']['r_successMessages'] = array_unique(array_merge($_SESSION['_systemNotifications']['r_successMessages'], $successMessage));
			}
			if ($generalMessage) {
				$generalMessage = explode(';', $generalMessage);
				$_SESSION['_systemNotifications']['r_generalMessages'] = array_unique(array_merge($_SESSION['_systemNotifications']['r_generalMessages'], $generalMessage));
			}
		} // function retrieveRequestMessages

		/**
		 *  Returns true if requested message array is not empty
		 *  Args: (str) type of message
		 *  Return: (boolean) error array is not empty
		 */
		public static function haveMessages($type) {
			switch ($type) {
				case 'error':
					if (!empty($_SESSION['_systemNotifications']['r_errorMessages']) || !empty($_SESSION['_systemNotifications']['errorMessages'])) {
						return true;
					}
					break;
				case 'success':
					if (!empty($_SESSION['_systemNotifications']['r_successMessages']) || !empty($_SESSION['_systemNotifications']['successMessages'])) {
						return true;
					}
					break;
				case 'general':
					if (!empty($_SESSION['_systemNotifications']['r_generalMessages']) || !empty($_SESSION['_systemNotifications']['generalMessages'])) {
						return true;
					}
					break;
				default:
					break;
			}
			return false;
		} // function haveErrors

		/**
		 *  Retrieve messages of requested type
		 *  Args: (string) type of message
		 *  Return: (array) requested messages array
		 */
		public static function getMessages($type) {
			$messages = array();
			switch ($type) {
				case 'error':
					$messages = array_merge($_SESSION['_systemNotifications']['r_errorMessages'], $_SESSION['_systemNotifications']['errorMessages']);
					break;
				case 'success':
					$messages = array_merge($_SESSION['_systemNotifications']['r_successMessages'], $_SESSION['_systemNotifications']['successMessages']);
					break;
				case 'general':
					$messages = array_merge($_SESSION['_systemNotifications']['r_generalMessages'], $_SESSION['_systemNotifications']['generalMessages']);
					break;
				default:
					break;
			}
			return array_unique($messages);
		} // function getMessages

		/**
		 *  Clear the requested messages array
		 *  Args: (string) type of message
		 *  Return: none
		 */
		public static function clearMessages($type) {
			switch ($type) {
				case 'error':
					$_SESSION['_systemNotifications']['r_errorMessages'] = array();
					$_SESSION['_systemNotifications']['errorMessages'] = array();
					break;
				case 'success':
					$_SESSION['_systemNotifications']['r_successMessages'] = array();
					$_SESSION['_systemNotifications']['successMessages'] = array();
					break;
				case 'general':
					$_SESSION['_systemNotifications']['r_generalMessages'] = array();
					$_SESSION['_systemNotifications']['generalMessages'] = array();
					break;
				default:
					return false;
					break;
			}
		} // function clearMessages

		/**
		 *  Add a message to the requested messages array
		 *  Args: (string) type of message, (string) message
		 *  Return: none
		 */
		public static function addMessage($type, $message) {
			switch($type) {
				case 'error':
					addToArray($_SESSION['_systemNotifications']['errorMessages'], (string) $message);
					break;
				case 'success':
					addToArray($_SESSION['_systemNotifications']['successMessages'], (string) $message);
					break;
				case 'general':
					addToArray($_SESSION['_systemNotifications']['generalMessages'], (string) $message);
					break;
				default:
					break;
			}
		} // function addMessage

		/**
		 *  Remove a message from the requested messages array
		 *  Args: (string) type of message, (string) message
		 *  Return: none
		 */
		public static function removeMessage($type, $message) {
			switch($type) {
				case 'error':
					removeFromArray($_SESSION['_systemNotifications']['errorMessages'], (string) $message);
					break;
				case 'success':
					removeFromArray($_SESSION['_systemNotifications']['successMessages'], (string) $message);
					break;
				case 'general':
					removeFromArray($_SESSION['_systemNotifications']['generalMessages'], (string) $message);
					break;
				default:
					break;
			}
		} // function removeMessage

		/**
		 *  Returns whether the error field var is empty
		 *  Args: none
		 *  Return: (boolean) true if !empty
		 */
		public static function haveErrorFields() {
			return !empty($_SESSION['_systemNotifications']['errorFields']);
		} // function haveErrorFields

		/**
		 *  Return the error field array
		 *  Args: none
		 *  Return: (array) error fields
		 */
		public static function getErrorFields() {
			return array_unique($_SESSION['_systemNotifications']['errorFields']);
		} // function getErrorFields

		/**
		 *  Clear the error fields array
		 *  Args: none
		 *  Return: none
		 */
		public static function clearErrorFields() {
			$_SESSION['_systemNotifications']['errorFields'] = array();
		} // function clearErrorFields

		/**
		 *  Add an error field
		 *  Args: (str) field name
		 *  Return: none
		 */
		public static function addErrorField($field) {
			addToArray($_SESSION['_systemNotifications']['errorFields'], (string) $field);
		} // function addErrorField

		/**
		 *  Remove an error field
		 *  Args: (str) field name
		 *  Return: none
		 */
		public static function removeErrorField($field) {
			removeFromArray($_SESSION['_systemNotifications']['errorFields'], (string) $field);
		} // function removeErrorField

	} // class systemNotifications

	/**
	 *  Wrapper for systemNotifications::haveMessages('error')
	 *  Args: none
	 *  Return: (boolean) have error messages
	 */
	function haveErrors() {
		return systemNotifications::haveMessages('error');
	} // function haveErrors

	/**
	 *  Wrapper for systemNotifications::getMessages('error')
	 *  Args: none
	 *  Return: (array) error messages array
	 */
	function getErrors() {
		return systemNotifications::getMessages('error');
	} // function getErrors

	/**
	 *  Wrapper for systemNotifications::clearMessages('error')
	 *  Args: none
	 *  Return: none
	 */
	function clearErrors() {
		systemNotifications::clearMessages('error');
	} // function clearErrors

	/**
	 *  Wrapper for systemNotifications:addMessage('error', $message)
	 *  Args: (string) error message
	 *  Return: none
	 */
	function addError($message) {
		systemNotifications::addMessage('error', $message);
	} // function addError

	/**
	 *  Wrapper for systemNotifications::removeMessage('error', $message)
	 *  Args: (string) error message
	 *  Return: none
	 */
	function removeError($message) {
		systemNotifications::removeMessage('error', $message);
	} // function removeError

	/**
	 *  Wrapper for systemNotifications::haveMessages('success')
	 *  Args: none
	 *  Return: (boolean) have success messages
	 */
	function haveSuccess() {
		return systemNotifications::haveMessages('success');
	} // function haveSuccess

	/**
	 *  Wrapper for systemNotifications::getMessages('success')
	 *  Args: none
	 *  Return: (array) success messages array
	 */
	function getSuccess() {
		return systemNotifications::getMessages('success');
	} // function getSuccess

	/**
	 *  Wrapper for systemNotifications::clearMessages('success')
	 *  Args: none
	 *  Return: none
	 */
	function clearSuccess() {
		systemNotifications::clearMessages('success');
	} // function clearSuccess

	/**
	 *  Wrapper for systemNotifications:addMessage('success', $message)
	 *  Args: (string) success message
	 *  Return: none
	 */
	function addSuccess($message) {
		systemNotifications::addMessage('success', $message);
	} // function addSuccess

	/**
	 *  Wrapper for systemNotifications::removeMessage('success', $message)
	 *  Args: (string) success message
	 *  Return: none
	 */
	function removeSuccess($message) {
		systemNotifications::removeMessage('success', $message);
	} // function removeSucces

	/**
	 *  Wrapper for systemNotifications::haveMessages('general')
	 *  Args: none
	 *  Return: (boolean) have genteral messages
	 */
	function haveMessages() {
		return systemNotifications::haveMessages('general');
	} // function haveMessages

	/**
	 *  Wrapper for systemNotifications::getMessages('general')
	 *  Args: none
	 *  Return: (array) general messages array
	 */
	function getMessages() {
		return systemNotifications::getMessages('general');
	} // function getMessages

	/**
	 *  Wrapper for systemNotifications::clearMessages('general')
	 *  Args: none
	 *  Return: none
	 */
	function clearMessages() {
		systemNotifications::clearMessages('general');
	} // function clearMessages

	/**
	 *  Wrapper for systemNotifications:addMessage('general', $message)
	 *  Args: (string) general message
	 *  Return: none
	 */
	function addMessage($message) {
		systemNotifications::addMessage('general', $message);
	} // function addMessage

	/**
	 *  Wrapper for systemNotifications::removeMessage('general', $message)
	 *  Args: (string) general message
	 *  Return: none
	 */
	function removeMessage($message) {
		systemNotifications::removeMessage('general', $message);
	} // function removeMessage

	/**
	 *  Wrapper for systemNotifications::haveErrorFields()
	 *  Args: none
	 *  Return: (boolean) true if !empty
	 */
	function haveErrorFields() {
		return systemNotifications::haveErrorFields();
	} // function haveErrorFields

	/**
	 *  Wrapper for systemNotifications::getErrorFields()
	 *  Args: none
	 *  Return: (array) error fields
	 */
	function getErrorFields() {
		return systemNotifications::getErrorFields();
	} // function getErrorFields

	/**
	 *  Wrapper for systemNotifications::clearErrorFields()
	 *  Args: none
	 *  Return: none
	 */
	function clearErrorFields() {
		systemNotifications::clearErrorFields();
	} // function clearErrorFields

	/**
	 *  Wrapper for systemNotifications::addErrorField($field)
	 *  Args: (str) field name
	 *  Return: none
	 */
	function addErrorField($field) {
		systemNotifications::addErrorField($field);
	} // function addErrorField

	/**
	 *  Wrapper for systemNotifications::removeErrorField($field)
	 *  Args: (str) field name
	 *  Return: none
	 */
	function removeErrorField($field) {
		systemNotifications::removeErrorField($field);
	} // function removeErrorField

	/**
	 *  Clears all error, success, general messages as well as error fields
	 *  Args: none
	 *  Return: none
	 */
	function clearAllMessages() {
		clearErrors();
		clearSuccess();
		clearMessages();
		clearErrorFields();
	} // function clearAllMessages

?>