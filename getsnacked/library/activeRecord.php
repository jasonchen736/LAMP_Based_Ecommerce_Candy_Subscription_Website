<?

	/**
	 *  Active record for a database table
	 *    record values are arrays as: field name = array(value, enclose in quotes [update/insert])
	 */
	class activeRecord {
		// database handler
		protected $dbh;
		// active record table
		protected $table;
		// existing auto increment field, this should be a field's friendly name
		protected $autoincrement = false;
		// history table (optional)
		protected $historyTable = false;
		// history record time location fields, all history tables must have these two fields
		//   values (not index) must match the history table fields
		//   lastModified must also be the same as the last modified field for the main table
		protected $historyDateTimeFields = array(
			'lastModified' => 'lastModified',
			'effectiveThrough' => 'effectiveThrough'
		);
		// array: unique id fields
		protected $idFields;
		// field array
		//   array(external name => array(field name, field type, min chars, max chars))
		protected $fields;
		// object site mapping table
		protected $siteMappingTable = false;
		// object tagging tables: array(object tag table, object tag mapping table)
		protected $tagTables = array(
			'tags' => false,
			'mapping' => false
		);

		/**
		 *  Constructor
		 *  Args: (mixed) id fields (construct new record if empty)
		 *  Return: none
		 */
		public function __construct($id = NULL) {
			$this->dbh = database::getInstance();
			if ($id) {
				$this->load($id);
			} else {
				$this->reset();
			}
			$this->initialize();
		} // function __construct

		/**
		 *  Reset field values
		 *  Args: none
		 *  Return: none
		 */
		public function reset() {
			foreach ($this->fields as $key => $vals) {
				$this->$vals[0] = array(NULL, true);
			}
		} // function reset

		/**
		 *  Load record by unique id
		 *  Args: (mixed) id can be array/str/int
		 *  Return: (boolean) success
		 */
		public function load($id) {
			$this->reset();
			if (!is_array($id)) {
				$id = array($id);
			}
			if (!array_diff(array_keys($this->idFields), array_keys($id))) {
				$identifier = array();
				foreach ($this->idFields as $key => $val) {
					$identifier[] = "`".$this->fields[$val][0]."` = '".prep($id[$key])."'";
				}
				$fields = array();
				foreach ($this->fields as $vals) {
					$fields[] = $vals[0];
				}
				$sql = "SELECT `".implode('`, `', $fields)."` 
						FROM `".$this->table."` 
						WHERE ".implode(' AND ', $identifier);
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					$row = $result->fetchRow();
					foreach ($row as $key => $val) {
						$this->$key = array($val, true);
					}
					return true;
				}
			}
			return false;
		} // function load

		/**
		 *  Save new record
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function save() {
			if (!$this->assertRequired()) {
				return false;
			}
			if ($this->isDuplicate()) {
				return false;
			}
			$this->assertSaveDefaults();
			$insertFields = array();
			$insertValues = array();
			foreach ($this->fields as $key => $vals) {
				if (isset($this->{$vals[0]}[0])) {
					$insertFields[] = '`'.$vals[0].'`';
					if ($this->{$vals[0]}[1]) {
						$insertValues[] = "'".prep($this->{$vals[0]}[0])."'";
					} else {
						$insertValues[] = $this->{$vals[0]}[0];
					}
				}
			}
			$sql = "INSERT INTO `".$this->table."` (".implode(', ', $insertFields).") 
					VALUES (".implode(', ', $insertValues).")";
			$result = $this->dbh->query($sql);
			if ($result->rowCount) {
				if ($this->autoincrement) {
					if ($result->insertID) {
						$this->set($this->autoincrement, $result->insertID);
					} else {
						trigger_error('Active Record Error: Unable to retrieve autoincrement from '.$this->table.' insert [sql: '.$sql.'] [error: '.$result->sqlError.']', E_USER_ERROR);
						return false;
					}
				}
				$id = array();
				foreach ($this->idFields as $key => $val) {
					$id[$key] = $this->get($val);
				}
				if ($this->load($id)) {
					$this->logHistory('save');
					return true;
				}
			} else {
				trigger_error('Active Record Error: Save fail for '.$this->table.' [sql: '.$sql.'] [error: '.$result->sqlError.']', E_USER_ERROR);
			}
			return false;
		} // function save

		/**
		 *  Update current record
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function update() {
			if (!$this->assertRequired()) {
				return false;
			}
			$tableFields = array();
			foreach ($this->fields as $key => $vals) {
				$tableFields[] = '`'.$vals[0].'`';
			}
			$identifier = array();
			foreach ($this->idFields as $field) {
				$identifier[] = "`".$this->fields[$field][0]."` = '".prep($this->get($field))."'";
			}
			$sql = "SELECT ".implode(', ', $tableFields)." 
					FROM `".$this->table."` 
					WHERE ".implode(' AND ', $identifier);
			$result = $this->dbh->query($sql);
			if ($result->rowCount) {
				$row = $result->fetchRow();
				$change = false;
				foreach ($this->fields as $key => $vals) {
					if ($row[$vals[0]] != $this->{$vals[0]}[0]) {
						if (!in_array($key, $this->idFields)) {
							$change = true;
						} else {
							return false;
						}
					}
				}
				if ($change) {
					if ($this->isDuplicate()) {
						return false;
					}
					$this->assertUpdateDefaults();
					$updates = array();
					foreach ($this->fields as $key => $vals) {
						if ($row[$vals[0]] != $this->{$vals[0]}[0]) {
							$updates[] = '`'.$vals[0].'` = '.($this->{$vals[0]}[1] ? "'".prep($this->{$vals[0]}[0])."'" : $this->{$vals[0]}[0]);
						}
					}
					$sql = "UPDATE `".$this->table."` 
							SET ".implode(', ', $updates)." 
							WHERE ".implode(' AND ', $identifier);
					$result = $this->dbh->query($sql);
					if ($result->rowCount) {
						$id = array();
						foreach ($this->idFields as $key => $val) {
							$id[$key] = $this->get($val);
						}
						if ($this->load($id)) {
							$this->logHistory('update');
							return true;
						}
					} else {
						trigger_error('Active Record Error: Update fail for '.$this->table.' [sql: '.$sql.'] [error: '.$result->sqlError.']', E_USER_ERROR);
					}
				} else {
					return true;
				}
			}
			return false;
		} // function update

		/**
		 *  Log to history table if applicable
		 *    history tables must have the date time fields specified in the object vars
		 *  Args: (str) save or update type logging
		 *  Return: (boolean) success
		 */
		public function logHistory($type) {
			if ($this->historyTable) {
				$tableFields = array();
				foreach ($this->fields as $key => $vals) {
					if (isset($this->{$vals[0]}[0])) {
						$tableFields[] = '`'.$vals[0].'`';
					}
				}
				$identifier = array();
				foreach ($this->idFields as $field) {
					$identifier[] = "`".$this->fields[$field][0]."` = '".prep($this->get($field))."'";
				}
				if ($type == 'update') {
					$effectiveThrough = date('Y-m-d H:i:s', strtotime('-1 second', strtotime($this->get($this->historyDateTimeFields['lastModified']))));
					$sql = "UPDATE `".$this->historyTable."` 
							SET `".$this->historyDateTimeFields['effectiveThrough']."` = IF(
								'".$effectiveThrough."' < `".$this->historyDateTimeFields['lastModified']."`, 
								`".$this->historyDateTimeFields['lastModified']."`, 
								'".$effectiveThrough."'
							) WHERE ".implode(' AND ', $identifier)." 
							AND `".$this->historyDateTimeFields['effectiveThrough']."` = '9999-12-31 23:59:59'";
					$result = $this->dbh->query($sql);
					if (!$result->rowCount) {
						trigger_error('Active Record History Error: History log update failed for '.$this->table.' [sql: '.$sql.'] [error: '.$result->sqlError.']', E_USER_WARNING);
					}
				}
				$sql = "INSERT INTO `".$this->historyTable."` (".implode(', ', $tableFields).", `".$this->historyDateTimeFields['effectiveThrough']."`) 
						SELECT ".implode(', ', $tableFields).", '9999-12-31 23:59:59' 
						FROM `".$this->table."` 
						WHERE ".implode(' AND ', $identifier);
				$result = $this->dbh->query($sql);
				if (!$result->rowCount) {
					trigger_error('Active Record History Error: History log failed for '.$this->table.' [sql: '.$sql.'] [error: '.$result->sqlError.']', E_USER_WARNING);
					return false;
				}
			}
			return true;
		} // function logHistory

		/**
		 *  Retrieve a field value
		 *  Args: (str) field name
		 *  Return: (mixed) value
		 */
		public function get($fieldName) {
			if (isset($this->fields[$fieldName])) {
				return $this->{$this->fields[$fieldName][0]}[0];
			} else {
				return NULL;
			}
		} // function get

		/**
		 *  Set a field value, optionally clean according to field type
		 *  Args: (str) field name, (mixed) value, (boolean) clean field type
		 *  Return: none
		 */
		public function set($fieldName, $value, $clean = true) {
			if (isset($this->fields[$fieldName])) {
				if ($clean) {
					$this->{$this->fields[$fieldName][0]}[0] = clean($value, $this->fields[$fieldName][1]);
				} else {
					$this->{$this->fields[$fieldName][0]}[0] = $value;
				}
			}
		} // function set

		/**
		 *  Load record by unique id
		 *  Args: (str) field name, (boolean) enclose in quotes for database
		 *  Return: none
		 */
		public function enclose($fieldName, $enclose) {
			if (isset($this->fields[$fieldName])) {
				if ($enclose) {
					$this->{$this->fields[$fieldName][0]}[1] = true;
				} else {
					$this->{$this->fields[$fieldName][0]}[1] = false;
				}
			}
		} // function enclose

		/**
		 *  Return array of id field values
		 *  Args: none
		 *  Return: (array) id values
		 */
		public function getID() {
			if ($this->exists()) {
				$id = array();
				foreach ($this->idFields as $key => $field) {
					$id[$key] = $this->get($field);
				}
				return $id;
			} else {
				return NULL;
			}
		} // function getID

		/**
		 *  Return field/value pairs for the active record
		 *  Args: none
		 *  Return: (array) active record field value pairs
		 */
		public function fetchArray() {
			$record = array();
			foreach ($this->fields as $key => $vals) {
				$record[$key] = $this->get($key);
			}
			return $record;
		} // function fetchArray

		/**
		 *  Return true if record exists (id values are set)
		 *  Args: none
		 *  Return: (boolean) existing record
		 */
		public function exists() {
			$exists = true;
			foreach ($this->idFields as $field) {
				if (!$this->get($field)) {
					$exists = false;
				}
			}
			return $exists;
		} // function exists

		/**
		 *  Perform any object setup on construct
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: none
		 */
		public function initialize() {
		} // function initialize

		/**
		 *  Set defaults for saving
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
		} // function assertSaveDefaults

		/**
		 *  Set defaults for updating
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: none
		 */
		public function assertUpdateDefaults() {
		} // function assertUpdateDefaults

		/**
		 *  Assert all requirements are met for save/update
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: (boolean) validation result
		 */
		public function assertRequired() {
			$invalid = array();
			foreach ($this->fields as $key => $vals) {
				if ($vals[2]) {
					if (isset($this->{$vals[0]}[0])) {
						$length = strlen($this->{$vals[0]}[0]);
						if ($length < $vals[2] || $length > $vals[3]) {
							$invalid[] = $key;
						}
					} else {
						$invalid[] = $key;
					}
				}
			}
			if (empty($invalid)) {
				return true;
			} else {
				$this->assertInvalidFields($invalid);
				return false;
			}
		} // function assertRequired

		/**
		 *  Make a field required
		 *    OVERRIDE AS NEEDED
		 *  Args: (str) field name
		 *  Return: none
		 */
		public function makeRequired($field) {
			if (isset($this->fields[$field])) {
				$this->fields[$field][2] = 1;
			}
		} // function makeRequired

		/**
		 *  Make a field not required
		 *    OVERRIDE AS NEEDED
		 *  Args: (str) field name
		 *  Return: none
		 */
		public function unRequire($field) {
			if (isset($this->fields[$field])) {
				$this->fields[$field][2] = 0;
			}
		} // function unRequire

		/**
		 *  Perform action for an array of invalid fields
		 *    Intended to be called when invalid fields are found
		 *    OVERRIDE AS NEEDED
		 *  Args: (array) invalid fields
		 *  Return: none
		 */
		public function assertInvalidFields($invalid) {
			assertArray($invalid);
			foreach ($invalid as $field) {
				addErrorField($field);
				addError(ucwords($field).' is invalid');
			}
		} // function assertInvalidFields

		/**
		 *  Override with appropriate method if duplicate needs to be checked before save or update
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: (boolean) duplicate record found
		 */
		public function isDuplicate() {
			return false;
		} // function isDuplicate

		/**
		 *  Retrieve tags associated to the object
		 *  Args: none
		 *  Return: (array) tags array(tagID => tag)
		 */
		public function getObjectTags() {
			$tags = array();
			if ($this->tagTables['tags'] && $this->exists()) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "`a`.`".$field."` = '".$objectID[$key]."'";
				}
				$result = $this->dbh->query("SELECT `b`.`tagID`, `b`.`tag` FROM `".$this->tagTables['mapping']."` `a` JOIN `".$this->tagTables['tags']."` `b` ON (`a`.`tagID` = `b`.`tagID`) WHERE ".implode(' AND ', $idClause));
				if ($result->rowCount) {
					while ($row = $result->fetchAssoc()) {
						$tags[$row['tagID']] = $row['tag'];
					}
				}
			}
			return $tags;
		} // function getObjectTags

		/**
		 *  Map tags to object
		 *  Args: (array) array of tags
		 *  Return: (boolean) success
		 */
		public function addTags($tags) {
			if ($this->tagTables['tags'] && $this->tagTables['mapping'] && !empty($tags)) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "'".$objectID[$key]."' AS `".$field."`";
				}
				$sql = "INSERT IGNORE INTO `".$this->tagTables['mapping']."` (`tagID`, `".implode('`, `', $this->idFields)."`) SELECT `tagID`, ".implode(', ', $idClause)." FROM `".$this->tagTables['tags']."` WHERE `tag` IN ('".implode("', '", $tags)."')";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function addTags

		/**
		 *  Remove tags mapped to object
		 *  Args: (array) array of tags
		 *  Return: (boolean) success
		 */
		public function removeTags($tags) {
			if ($this->tagTables['tags'] && $this->tagTables['mapping'] && !empty($tags)) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "`".$field."` = '".$objectID[$key]."'";
				}
				$sql = "DELETE FROM `".$this->tagTables['mapping']."` WHERE ".implode(' AND ', $idClause)." AND `tagID` IN (SELECT `tagID` FROM `".$this->tagTables['tags']."` WHERE `tag` IN ('".implode("', '", $tags)."'))";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function removeTags

		/**
		 *  Retrieve sites associated with the object
		 *  Args: none
		 *  Return: (array) site ids
		 */
		public function getObjectSites() {
			$sites = array();
			if ($this->siteMappingTable && $this->exists()) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "`".$field."` = '".$objectID[$key]."'";
				}
				$result = $this->dbh->query("SELECT `siteID` FROM `".$this->siteMappingTable."` WHERE ".implode(' AND ', $idClause));
				if ($result->rowCount) {
					while ($row = $result->fetchRow()) {
						$sites[] = $row['siteID'];
					}
				}
			}
			return $sites;
		} // function getObjectSites

		/**
		 *  Map tags to object
		 *  Args: (array) array of site ids
		 *  Return: (boolean) success
		 */
		public function addSites($sites) {
			if ($this->siteMappingTable && $this->exists()) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "'".$objectID[$key]."' AS `".$field."`";
				}
				$sql = "INSERT IGNORE INTO `".$this->siteMappingTable."` (`".implode('`, `', $this->idFields)."`, `siteID`) SELECT ".implode(', ', $idClause).", `siteID` FROM `siteRegistry` WHERE `siteID` IN ('".implode("', '", $sites)."')";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function addSites

		/**
		 *  Remove tags mapped to object
		 *  Args: (array) array of site ids
		 *  Return: (boolean) success
		 */
		public function removeSites($sites) {
			if ($this->siteMappingTable && $this->exists()) {
				$objectID = $this->getID();
				$idClause = array();
				foreach ($this->idFields as $key => $field) {
					$idClause[] = "`".$field."` = '".$objectID[$key]."'";
				}
				$sql = "DELETE FROM `".$this->siteMappingTable."` WHERE ".implode(' AND ', $idClause)." AND `siteID` IN ('".implode("', '", $sites)."')";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function removeSites

	} // class activeRecord

?>