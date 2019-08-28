<?

	/**
	 *  Return DEVENVIRONMENT constant
	 *  Args: none
	 *  Return: (boolean) true if dev environment
	 */
	function isDevEnvironment() {
		return DEVENVIRONMENT;
	} // function isDevEnvironment

	/**
	 *  Displays data with pre tags and header if debug mode is on and in dev environment
	 *    default display with print_r
	 *  Args: (mixed) debug data (str) header, output type
	 *  Return: none
	 */
	function debug($data, $header = '', $type = 'print_r', $class = 'debug') {
		if (isDevEnvironment() && systemSettings::get('DEBUG')) {
			switch ($class) {
				case 'error':
					$textColor = 'FFFFFF';
					$bgColor = 'FF0000';
					$borderColor = 'FFFFFF';
					break;
				case 'sql':
					$textColor = 'FFFFFF';
					$bgColor = '7784FF';
					$borderColor = 'FFFFFF';
					break;
				case 'debug':
				default:
					$textColor = '000000';
					$bgColor = 'E1E1E1';
					$borderColor = 'FFFFFF';
					break;
			}
			echo '<div style="color: #'.$textColor.';background-color: #'.$bgColor.'; border: 1px solid #'.$borderColor.'; padding: 10px; text-align: left">';
			if ($header) {
				echo '<b><u>'.strtoupper($header).'</u></b><br>';
				if ($type == 'echo') echo '<br>';
			}
			if ($type == 'echo') {
				echo $data;
			} else {
				echo '<pre>';
				print_r($data);
				echo '</pre>';
			}
			echo '</div>';
		}
	} // function debug

	/**
	 *  Strips html tags and characters pertaining to data type
	 *  Args: (str) value, (str) data type, (integer) max length
	 *  Return: (str) cleansed value
	 */
	function clean($value, $type = false, $maxLength = false) {
		// handle suffix type
		if (preg_match('/\-search$/', $type)) {
			$search = true;
			$type = preg_replace('/\-search$/', '', $type);
		} else {
			$search = false;
		}
		// set data type
		$html = false;
		switch ($type) {
			case 'integer':
				$allow = '\d\-';
				break;
			case 'word':
				$allow = '\w\s';
				break;
			case 'alpha':
				$allow = '\w';
				break;
			case 'decimal':
			case 'money':
			case 'double':
			case 'float':
				$allow = '\d\.\-';
				break;
			case 'html':
				$allow = '\w\d\*\.\s_\-\$&\(\)\[\]=+%#@!;:\'"\?\<\>,{}\^~`|\/\\\\';
				$html = '<a><p><div><table><td><th><thead><tbody><tr><strong><span><style><br><h1><h2><h3><h4><h5><h6><form><img><input><select><option><ul><ol><li><dt><dd><b><strong><i><button><em><iframe><textarea>';
				break;
			case 'html-campaign':
				$allow = '\w\d\*\.\s_\-\$&\(\)\[\]=+%#@!;:\'"\?\<\>,{}\^~`|\/\\\\';
				$html = '<a><p><div><table><td><tr><th><strong><span><style><html><head><body><meta><title><br><h1><h2><h3><h4><h5><h6><form><img><input><select><option><ul><ol><li><dt><dd><b><strong><i><button><em><iframe><textarea>';
				break;
			case 'date':
				$allow = '\d\-\/';
				break;
			case 'datetime':
				$allow = '\d\-:\/ ';
				break;
			case 'filename':
				$allow = '\w\d\.\-_';
				break;
			case 'url':
				$allow = '\w\d\.\-_:\/\?=&';
				break;
			case 'email':
				$allow = '\w\d\.\-_@';
				break;
			case 'name':
				$allow = '\w\d\s\.\-:\'\(\)\/&\*\+';
				break;
			case 'alphanum':
				$allow = '\w\d\s';
				break;
			case 'password':
				$allow = '\w\d\*\.\s_\-\$&\(\)\[\]=+%#@!;:\'\?,{}\^~`|\/\\\\';
				break;
			case 'clean':
			default:
				$allow = '\w\d\s\.\-_\'@';
				break;
		}
		// append suffix attributes
		if ($search) {
			$allow .= '\* ';
		}
		// strip html tags
		if (!$html) {
			$value = strip_tags($value);
		} else {
			$value = strip_tags($value, $html);
		}
		// clean value
		$value = trim(preg_replace('/[^'.$allow.']/', '', $value));
		// enforce max length
		if ($maxLength !== false) {
			if (strlen($value) > $maxLength) {
				$value = substr($value, 0, $maxLength);
			}
		}
		return $value;
	} // function clean

	/**
	 *  Array walk function for method clean
	 *  Args: (mixed) array parameter value, (str) key, (str) data type
	 *  Returns: none
	 */
	function cleanWalk(&$value, $key, $type) {
		$value = clean($value, $type);
	} // function cleanWalk

	/**
	 *  Prepares value for database query
	 *  Args: (str) value
	 *  Return: (str) quote escaped value
	 */
	function prep($str) {
		if (get_magic_quotes_gpc()) {
			return mysql_real_escape_string(stripslashes($str));
		} else {
			return mysql_real_escape_string($str);
		}
	} // function prep

	/**
	 *  Retrieves a request field value
	 *  Args: (str) request field, (str) clean type
	 *  Return: (str) request field value
	 */
	function getRequest($field, $type = false) {
		if (isset($_REQUEST[$field])) {
			if (!$type) {
				return $_REQUEST[$field];
			} else {
				return clean($_REQUEST[$field], $type);
			}
		}
		return null;
	} // function getRequest

	/**
	 *  Retrieves a post field value
	 *  Args: (str) post field, (str) clean type
	 *  Return: (str) post field value
	 */
	function getPost($field, $type = false) {
		if (isset($_POST[$field])) {
			if (!$type) {
				return $_POST[$field];
			} else {
				return clean($_POST[$field], $type);
			}
		}
		return null;
	} // function getPost

	/**
	 *  Retrieves a get field value
	 *  Args: (str) get field, (str) clean type
	 *  Return: (str) get field value
	 */
	function getGet($field, $type = false) {
		if (isset($_GET[$field])) {
			if (!$type) {
				return $_GET[$field];
			} else {
				return clean($_GET[$field], $type);
			}
		}
		return null;
	} // function getGet

	/**
	 *  Retrieves a cookie field value
	 *  Args: (str) cookie field, (str) clean type
	 *  Return: (str) cookie field value
	 */
	function getCookie($field, $type = false) {
		if (isset($_COOKIE[$field])) {
			if (!$type) {
				return $_COOKIE[$field];
			} else {
				return clean($_COOKIE[$field], $type);
			}
		}
		return null;
	} // function getCookie

	/**
	 *  Retrieves a session field value
	 *  Args: (str) session field, (str) clean type
	 *  Return: (str) session field value
	 */
	function getSession($field, $type = false) {
		if (isset($_SESSION[$field])) {
			if (!$type) {
				return $_SESSION[$field];
			} else {
				return clean($_SESSION[$field], $type);
			}
		}
		return null;
	} // function getSession

	/**
	 *  Redirects to page passed
	 *  Args: (str) page url
	 *  Returns: none
	 */
	function redirect($page) {
		header("Location: ".$page);
		exit();
	} // function redirect

	/**
	 *  Ensure argument is an array
	 *  Args: (array) array
	 *  Return: none
	 */
	function assertArray(&$array) {
		if (!is_array($array)) {
			$array = array();
		}
	} // function assertArray

	/**
	 *  Adds identifier to array only once, will not add if already existing
	 *  Args: (array) array to edit (str) value to add
	 *  Return: none
	 */
	function addToArray(&$array, $value) {
		if (!is_array($array)) $array = array();
		if (!in_array($value, $array)) $array[] = $value;
	} // function addToArray

	/**
	 *  Removes index/value from array if existing
	 *  Args: (array) array edit (str) value to remove
	 *  Return: none
	 */
	function removeFromArray(&$array, $value) {
		if (!is_array($array)) $array = array();
		if (in_array($value, $array)) {
			$key = array_search($value, $array);
			unset($array[$key]);
			if (!is_array($array)) $array = array();
		}
	} // function removeFromArray

	/**
	 *  Validate argument for sql date/time format
	 *  Args: (str) date, (boolean) validate time format
	 *  Return: (boolean) valid
	 */
	function validSqlDate($date, $time = false) {
		if (!$time) {
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
				return true;
			}
		} else {
			if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date)) {
				return true;
			}
		}
		return false;
	} // function validSqlDate

	/**
	 *  Converts any format date/time to sql format Y-m-d HH:MM:SS
	 *  Args: (str) any format date, (boolean) return time as well
	 *  Return: (str) sql formatted date/time
	 */
	function dateToSql($date, $time = false) {
		if (!validSqlDate($date, $time)) {
			if (!$time) {
				return date('Y-m-d', strtotime($date));
			} else {
				return date('Y-m-d H:i:s', strtotime($date));
			}
		} else {
			return $date;
		}
	} // function dateToSql

	/**
	 *  Confirms that a number is set and is a number
	 *  Args: (mixed) number, (str) numeric data type
	 *  Return: (boolean) valid/invalid number
	 */
	function validNumber($number, $type = 'double') {
		switch ($type) {
			case 'phone':
				$regex = '/^[\d]{10,11}$/';
				break;
			case 'zip':
				$regex = '/^[0-9]{5}([- ]?[0-9]{4})?$/';
				break;
			case 'integer':
			case 'int':
				$regex = '/^[\d\-]+$/';
				break;
			case 'double':
			case 'float':
			default:
				$regex = '/^[\d\.\-]+$/';
				break;
		}
		if (isset($number) && preg_match($regex, $number)) {
			return true;
		}
		return false;
	} // function validNumber

	/**
	 *  Check data for valid number, and between min/max boundries (inclusive)
	 *  Args: (mixed) number, (mixed) minimum boundry, (mixed) maximum boundry, (str) numeric type
	 *  Return: (boolean) true/false
	 */
	function between($num, $min, $max, $type = 'integer') {
		if (!validNumber($num, $type) || $num < $min || $num > $max) {
			return false;
		} else {
			return true;
		}
	} // function between

	/**
	 *  Validates email address
	 *  Args: (str) email
	 *  Return: (boolean) valid/invalid
	 */
	function validEmail($email) {
		if (!systemSettings::get('OFFLINE')) {
			list($userName, $mailDomain) = split("@", $email);
			if (!checkdnsrr($mailDomain, "MX")) {
				return false;
			}
		}
		if (preg_match('/^[^0-9][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/', $email)) {
			return true;
		} else {
			return false;
		}
	} // function validEmail

	/**
	 *  Execute a query with global database object and return result object
	 *  Args: (str) query
	 *  Returns: (result) result object
	 */
	function query($query) {
		$dbh = database::getInstance();
		$result = $dbh->query($query);
		return $result;
	} // function query

	/**
	 *  Set user object from session or new
	 *  Args: none
	 *  Return: (user) user object
	 */
	function setUser() {
		$user = isset($_SESSION['user']) ? $_SESSION['user'] : new user;
		return $user;
	} // function setUser

	/**
	 *  Populates form array with user input from request
	 *  Args: (array) form array
	 *  Returns: none
	 */
	function processForm(&$formArray) {
		foreach ($formArray as $field => &$val) {
			// if the input field is also an array, populate array vars
			if (is_array($val)) {
				foreach ($val as $key => &$value) {
					if (isset($_REQUEST[$key])) {
						$value = clean($_REQUEST[$key]);
					}
				}
				unset($value);
			} elseif (isset($_REQUEST[$field])) {
				$val = clean($_REQUEST[$field]);
			}
		}
	} // function processForm

	/**
	 *  Check required field request inputs, updates internal variable $missing
	 *  Args: (array) input array, (array) required fields
	 *  Returns: (array) missing fields
	 */
	function checkRequired($input, $required) {
		assertArray($input);
		assertArray($required);
		$missingFields = array();
		foreach ($required as $key => $val) {
			if (!isset($input[$val]) || clean($input[$val]) == '') {
				$missingFields[] = $val;
			}
		}
		return array_unique($missingFields);
	} // function checkRequired

	/**
	 *  Prepares value for database query
	 *  Args: (str) value
	 *  Return: (str) quote escaped value
	 */
	function prepDB($str) {
		if (get_magic_quotes_gpc()) {
			return mysql_real_escape_string(stripslashes($str));
		} else {
			return mysql_real_escape_string($str);
		}
	} // function prepDB

?>