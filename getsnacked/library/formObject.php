<?

	class formObject {

		/**
		 *  Initiate object
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
		} // function __construct()

		/**
		 *  Construct and return an input field
		 *  Args: (str) field name, (str) field value, (str) input type, (array) select options, (str) additional attributes, (boolean) include id tag
		 *  Return: (str) input field
		 */
		public static function inputField($name, $value, $type = 'text', $options = null, $extras = null, $id = true) {
			if (preg_match('/\[\]$/', $name)) {
				$id = false;
			}
			switch ($type) {
				case 'text':
					$return = '<input type="text" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').' value="'.$value.'"'.($extras ? ' '.$extras : '').'>';
					break;
				case 'select':
					if (is_array($options) && !empty($options)) {
						$return = '<select name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').($extras ? ' '.$extras : '').'>';
						foreach ($options as $val => $display) {
							$return .= '<option value="'.$val.'"';
							if ($val == $value) $return .= ' selected';
							$return .= '>'.$display.'</option>';
						}
						$return .= '</select>';
					} else {
						$return = 'Invalid Select Options';
					}
					break;
				case 'checkbox':
					$return = '<input type="checkbox" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').' value="1"'.($extras ? ' '.$extras : '').($value ? ' checked' : '').'>';
					break;
				case 'radio':
					$return = '<input type="radio" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').' value="'.$value.'"'.($extras ? ' '.$extras : '').'>';
					break;
				case 'hidden':
					$return = '<input type="hidden" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').' value="'.$value.'"'.($extras ? ' '.$extras : '').'>';
					break;
				case 'textarea':
					$return = '<textarea name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').($extras ? ' '.$extras : '').'>'.$value.'</textarea>';
					break;
				case 'password':
					$return = '<input type="password" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').' value="'.$value.'"'.($extras ? ' '.$extras : '').'>';
					break;
				case 'file':
					$return = '<input type="file" name="'.$name.'"'.($id ? ' id="'.$name.'"' : '').($extras ? ' '.$extras : '').'>';
					break;
				default:
					$return = $value ? $value : $name;
					break;
			} // switch ($inputType)
			return $return;
		} // function inputField

		/**
		 *  Returns country select options
		 *  Args: (str) mode
		 *  Return: (array) country select options
		 */
		public static function countryOptions($mode = false) {
			$result = query("SELECT * FROM `countryCodes` ORDER BY `name` ASC");
			$options = array();
			switch ($mode) {
				case 'abbreviated':
					$options[''] = '';
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$options[$row['A2']] = $row['A3'];
						}
					}
					break;
				default:
					$options[''] = 'Select a country';
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$options[$row['A2']] = $row['name'];
						}
					}
				break;
			}
			return $options;
		} // function countryOptions

		/**
		 *  Returns state select options
		 *  Args: (str) mode
		 *  Return: (array) state select options
		 */
		public static function stateOptions($mode = false) {
			$result = query("SELECT * FROM `stateCodes` ORDER BY `stateName` ASC");
			$options = array();
			switch ($mode) {
				case 'abbreviated':
					$options[''] = '';
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$options[$row['stateCode']] = $row['stateCode'];
						}
					}
					break;
				default:
					$options[''] = 'Select a state';
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$options[$row['stateCode']] = $row['stateName'];
						}
					}
					break;
			}
			return $options;
		} // function stateOptions

		/**
		 *  Translate state code to name
		 *  Args: (str) state code
		 *  Return: (str) state name
		 */
		public static function translateStateCode($stateCode) {
			$clean = clean($stateCode, 'alphanum');
			$result = query("SELECT `stateName` FROM `stateCodes` WHERE `stateCode` = '".prep($clean)."'");
			if ($result->rowCount) {
				$row = $result->fetchRow();
				return $row['stateName'];
			} else {
				return $stateCode;
			}
		} // function translateStateCode

		/**
		 *  Translate country code to name
		 *  Args: (str) country code, (int) abbreviation format
		 *  Return: (str) country name
		 */
		public static function translateCountryCode($countryCode, $mode = 'A2') {
			$clean = clean($countryCode, 'alphanum');
			switch ($mode) {
				case 'number':
					$result = query("SELECT `name` FROM `countryCodes` WHERE `number` = '".prep($clean)."'");
					break;
				case 'A3':
					$result = query("SELECT `name` FROM `countryCodes` WHERE `A3` = '".prep($clean)."'");
					break;
				case 'A2':
				default:
					$result = query("SELECT `name` FROM `countryCodes` WHERE `A2` = '".prep($clean)."'");
					break;
			}
			if ($result->rowCount) {
				$row = $result->fetchRow();
				return $row['name'];
			} else {
				return $countryCode;
			}
		} // function translateCountryCode
	} // class formObject

?>