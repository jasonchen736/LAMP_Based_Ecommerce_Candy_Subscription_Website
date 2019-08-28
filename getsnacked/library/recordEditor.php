<?

	class recordEditor extends dataObject {

		protected $table = false;
		// will log a history record on update if history table exists
		protected $historyTable = false;
		// table fields and details
		protected $fields = false;
		// table auto increment field
		protected $autoIncrement = false;
		// active record id
		protected $id = false;
		// static active record clone maintaining originally loaded record values
		protected $original = false;
		// active record
		protected $record = false;
		// required fields
		protected $required = false;
		// these values have a default value and cannot be edited
		protected $default = false;
		// treat these fields as functions and will not encapsulate in query
		protected $function = false;

		// search vars
		//   array([str] field => array([str] type, [boolean] range))
		protected $searchFields = array();
		// search operators can be passed in the form of "XXXX_operator"
		//    where XXXX is the name of the search field; search queries will then be built accordingly
		protected $searchOperators = array(
			'equal' => '=',
			'not equal' => '!=',
			'greater than' => '>',
			'greater than or equal' => '>=',
			'less than' => '<',
			'less than or equal' => '<=',
			'contains' => 'LIKE',
		);

		/**
		 *  Initialize object, detect a table and set table record identifiers
		 *  Args: (str) table name, (array) id fields
		 *  Return: (boolean) success
		 */
		public function __construct($table, $id) {
			parent::__construct();
			assertArray($this->default);
			assertArray($this->function);
			$table = clean($table);
			if ($table && is_array($id) && !empty($id)) {
				$result = $this->dbh->query('DESC `'.prepDB($table).'`');
				if ($result->rowCount) {
					$this->table = $table;
					$this->fields = array();
					while ($row = $result->fetchAssoc()) {
						$this->fields[$row['Field']] = '';
						if (preg_match('/auto_increment/', $row['Extra'])) {
							$this->autoIncrement = $row['Field'];
						}
						if (preg_match('/^enum\(/', $row['Type'])) {
							$enumVals = explode(',', rtrim(ltrim($row['Type'], 'enum('), ')'));
							$enumVals = preg_replace('/\'/', '', $enumVals);
							$options = array();
							foreach ($enumVals as $key => $val) {
								$options[$val] = $val;
							}
							$this->fields[$row['Field']] = $options;
						}
					}
					$this->id = array();
					foreach ($id as $field) {
						if (array_key_exists($field, $this->fields)) {
							$this->id[] = $field;
						}
					}
					if (count($this->id) == count($id)) {
						return true;
					}
				}
			}
			$this->resetEditor();
			return false;
		} // function __construct

		/**
		 *  Reset editor variables
		 *  Args: none
		 *  Return: none
		 */
		public function resetEditor() {
			$this->tableName = false;
			$this->fields = false;
			$this->id = false;
			$this->record = false;
		} // function resetEditor

		/**
		 *  Return a record array with all field values empty
		 *  Args: none
		 *  Return: (array) empty record array
		 */
		 public function getEmptyRecord() {
		 	$record = array();
		 	foreach ($this->fields as $key => $val) {
		 		$record[$key] = '';
		 	}
		 	return $record;
		 } // function getEmptyRecord

		/**
		 *  Load a table record
		 *  Args: (array) record id values
		 *  Return: (boolean) success
		 */
		public function load($id) {
			if (is_array($id) && $id) {
				$load = array();
				foreach ($id as $key => $val) {
					if (in_array($key, $this->id)) {
						$load[] = '`'.prepDB(clean($key))."` = '".prepDB(clean($val))."'";
					} else {
						addError('Invalid identifier');
						return false;
					}
				}
				if (count($load) == count($this->id)) {
					$result = $this->dbh->query("SELECT * FROM `".$this->table."` WHERE ".implode(' AND ', $load));
					if ($result->rowCount) {
						$this->record = $result->fetchAssoc();
						$this->original = $this->record;
						return true;
					}
				}
			}
			addError('Unable to load record');
			return false;
		} // function load

		/**
		 *  Load a table record by id
		 *  Args: (str) record id value
		 *  Return: (boolean) success
		 */
		public function loadID($id) {
			return $this->load(array($this->id[0] => $id));
		} // function loadID

		/**
		 *  Set a record field
		 *  Args: (str) field name, (str) field value
		 *  Return: (boolean) success
		 */
		public function setField($field, $value) {
			if ($this->record) {
				if (isset($this->record[$field])) {
					$this->record[$field] = $value;
				}
			}
			return false;
		} // function setField

		/**
		 *  Update a record
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function update() {
			if ($this->original) {
				$change = array();
				foreach ($this->function as $val) {
					if (array_key_exists($val, $this->fields)) {
						$result = query("SELECT ".$this->record[$val]." AS `result`");
						$result = $result->fetchAssoc();
						$this->record[$val] = $result['result'];
					}
				}
				foreach ($this->record as $key => $val) {
					if ($this->original[$key] != $val) {
						if (substr($val, 0, 10) != 'function::') {
							$change['~'.$key] = prepDB($val);
						} else {
							$change[$key] = substr($val, 10);
						}
					}
				}
				if (!empty($change)) {
					if ($this->historyTable) {
						$result = $this->dbh->query("SELECT NOW() AS `timestamp`");
						$modified = $result->fetchAssoc();
						$modified = $modified['timestamp'];
					}
					foreach ($this->default as $key => $val) {
						if ($val['update']) {
							if ($this->historyTable && $val['value'] == 'NOW()') {
								if ($val['key']{0} != '~') {
									$val['key'] = '~'.$val['key'];
								}
								$change[$val['key']] = $modified;
							} else {
								$change[$val['key']] = $val['value'];
							}
						}
					}
					$id = array();
					foreach ($this->id as $field) {
						$id[] = '`'.$field."` = '".prepDB($this->original[$field])."'";
					}
					$idClause = 'WHERE '.implode(' AND ', $id);
					$this->dbh->perform($this->table, $change, $idClause, 'update');
					if ($this->historyTable) {
						$effectiveThrough = date('Y-m-d H:i:s', strtotime('-1 second', strtotime($modified)));
						$sql = "UPDATE `".$this->historyTable."` 
								SET `effectiveThrough` = IF(
									'".$effectiveThrough."' < `lastModified`, 
									`lastModified`, 
									'".$effectiveThrough."'
								) ".$idClause." 
								AND `effectiveThrough` = '9999-12-31 23:59:59'";
						query($sql);
						$sql = "INSERT INTO `".$this->historyTable."`
								(`".implode('`, `', array_keys($this->fields))."`, `effectiveThrough`)
								SELECT *, '9999-12-31 23:59:59'
								FROM `".$this->table."` ".$idClause;
						$this->dbh->query($sql);
					}
					return true;
				} else {
					addError('Record not updated: no change');
					return false;
				}
			}
			addError('Unable to update record');
			return false;
		} // function update

		/**
		 *  Save a record
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function save() {
			if ($this->required) {
				foreach ($this->required as $field) {
					if (!array_key_exists($field, $this->record) || $this->record[$field] == '') {
						addErrorField($field);
						addError('Missing required field');
					}
				}
			}
			if (haveErrorFields()) {
				return false;
			}
			if ($this->record) {
				$queryVals = array();
				foreach ($this->record as $key => $val) {
					if (array_key_exists($key, $this->default)) {
						$queryVals[$this->default[$key]['key']] = $this->default[$key]['value'];
					} elseif (in_array($key, $this->function)) {
						$queryVals[$key] = $val;
					} else {
						$queryVals['~'.$key] = prepDB($val);
					}
				}
				$this->dbh->perform($this->table, $queryVals);
				if (!$this->dbh->sqlError) {
					$loaded = false;
					if ($this->dbh->insertID && count($this->id) == 1 && $this->id[0] == $this->autoIncrement) {
						$loaded = $this->loadID($this->dbh->insertID);
					} else {
						$id = array();
						foreach ($this->id as $key => $val) {
							$id[$key] = $this->record[$key];
						}
						$loaded = $this->load($id);
					}
					if ($this->historyTable) {
						$id = array();
						foreach ($this->id as $field) {
							$id[] = '`'.$field."` = '".prepDB($this->original[$field])."'";
						}
						$idClause = 'WHERE '.implode(' AND ', $id);
						$sql = "INSERT INTO `".$this->historyTable."`
								(`".implode('`, `', array_keys($this->fields))."`, `effectiveThrough`)
								SELECT *, '9999-12-31 23:59:59'
								FROM `".$this->table."` ".$idClause;
						$this->dbh->query($sql);
					}
					return $loaded;
				} else {
					if ($this->dbh->sqlErrorNumber == 1062) {
						addError('Duplicate Entry');
					} else {
						addError('There has been a database error');
					}
					return false;
				}
			}
			addError('Invalid information');
			return false;
		} // function save

		/**
		 *  Process, validate and add a record from post input
		 *  Args: (array) fields to ignore
		 *  Return: (boolean) success
		 */
		public function addRecord($ignore = false) {
			$this->record = array();
			if (!$ignore || is_array($ignore)) {
				$ignore = array();
			}
			foreach ($this->fields as $key => $val) {
				if (!in_array($key, $ignore)) {
					$this->record[$key] = clean(getPost($key));
				}
			}
			return $this->save();
		} // function addRecord

		/**
		 *  Delete a record
		 *  Args: (array) record identifier
		 *  Return: (boolean) success
		 */
		public function delete($id) {
			if (is_array($id) && $id) {
				$delete = array();
				foreach ($id as $key => $val) {
					if (in_array($key, $this->id)) {
						$delete[] = '`'.prepDB(clean($key))."` = '".prepDB(clean($val))."'";
					} else {
						return false;
					}
				}
				if (count($delete) == count($this->id)) {
					$this->dbh->query("DELETE FROM `".$this->table."` WHERE ".implode(' AND ', $delete));
					if ($this->dbh->rowCount) {
						addSuccess('Record deleted');
						return true;
					}
				}
			}
			addError('Record could not be deleted');
			return false;
		} // function delete

		/**
		 *  Construct a get query string from get and post requests formatted for web friendly urls
		 *  Args: none
		 *  Return: (str) query string
		 */
		public function getQueryString($ignore = array()) {
			$querystring = array();
			assertArray($ignore);
			$request = array_merge($_GET, $_POST);
			foreach ($request as $key => $val) {
				if (!in_array($key, $ignore) && $val !== '') {
					// triple encode:
					//   double encode to compensate for mod rewrite's handling of auto (double) decoding encoded character
					//   extra encode for friendly urls (handle forward slashes)
					$querystring[] = $key.'/'.urlencode(urlencode(urlencode($val)));
				}
			}
			if ($querystring) {
				return '/'.implode('/', $querystring);
			} else {
				return '';
			}
		} // function getQueryString

		/**
		 *  Return a current table location coordinates made from request
		 *  Args: none
		 *  Return: (array) start record, number of records, current page
		 */
		public function getTableLocation() {
			$start = (int) getRequest('start');
			$show = (int) getRequest('show') ? (int) getRequest('show') : 100;
			$page = (int) getRequest('page');
			if (!getRequest('search') || getRequest('nextPage') || getRequest('previousPage') || getPost('page')) {
				if (getRequest('nextPage')) {
					$start += $show;
				} elseif (getRequest('previousPage')) {
					$start -= $show;
					if ($start < 0) {
						$start = 0;
					}
				} elseif ($page) {
					$start = ($page - 1) * $show;
				}
				$page = floor(($start + $show) / $show);
			} else {
				$page = 1;
				$start = 0;
			}
			// for function getQueryString
			$_POST['page'] = $page;
			$_POST['start'] = $start;
			$_POST['show'] = $show;
			return array($start, $show, $page);
		} // function getTableLocation

		/**
		 *  Return array of search operator keys
		 *  Args: none
		 *  Return: (array) search operator key => key map
		 */
		public function getSearchOperators() {
			$searchOperators = array();
			foreach ($this->searchOperators as $key => $val) {
				$searchOperators[$key] = $key;
			}
			return $searchOperators;
		} // function getSearchOperators

		/**
		 *  Return array of search variables, should be overridden per object
		 *  Args: none
		 *  Return: (array) search vars
		 */
		public function getSearchVars() {
			$search = array();
			foreach ($this->searchFields as $field => $val) {
				if (isset($this->fields[$field]) && is_array($this->fields[$field])) {
					$fieldOptions = array_merge(array('' => 'All'), $this->fields[$field]);
				} else {
					$fieldOptions = false;
				}
				if (!$val['range']) {
					$search[$field] = array();
					$search[$field]['value'] = clean(getRequest($field), $val['type']);
					$search[$field]['operator'] = clean(getRequest($field.'_operator'), 'alphanum');
					$search[$field]['options'] = $fieldOptions;
				} else {
					$search[$field.'From'] = array();
					$search[$field.'From']['value'] = clean(getRequest($field.'From'), $val['type']);
					$search[$field.'From']['options'] = $fieldOptions;
					$search[$field.'To'] = array();
					$search[$field.'To']['value'] = clean(getRequest($field.'To'), $val['type']);
					$search[$field.'To']['options'] = $fieldOptions;
				}
			}
			return $search;
		} // function getSearchVars

		/**
		 *  Return array of search sql where clause components, should be overridden per object
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchArray() {
			$search = array();
			if (getRequest('search')) {
				foreach ($this->searchFields as $field => $val) {
					if (!$val['range']) {
						$value = clean(urldecode(getRequest($field)), $val['type']);
						$operator = getRequest($field.'_operator');
						$operator = isset($this->searchOperators[$operator]) ? $this->searchOperators[$operator] : false;
						if ($value) {
							if ($operator) {
								if ($operator == 'LIKE') {
									$value = preg_replace('/\*/', '%', prepDB($value));
								} else {
									$value = prepDB($value);
								}
							} else {
								$operator = '=';
								$value = prepDB($value);
							}
							$search[$field] = '`'.$field.'` '.$operator." '".$value."'";
						}
					} else {
						$valueFrom = clean(getRequest($field.'From'), $val['type']);
						$valueTo = clean(getRequest($field.'To'), $val['type']);
						if ($valueFrom != '' || $valueTo != '') {
							if ($val['type'] == 'date') {
								$valueFrom = $valueFrom ? dateToSql($valueFrom) : $valueFrom;
								$valueTo = $valueTo ? dateToSql($valueTo) : $valueTo;
							}
							if ($valueFrom != '' && $valueTo != '') {
								$search[] = "`".$field."` BETWEEN '".$valueFrom."' AND '".$valueTo."'";
							} elseif ($valueFrom != '') {
								$search[] = "`".$field."` >= '".$valueFrom."'";
							} else {
								$search[] = "`".$field."` <= '".$valueTo."'";
							}
						}
					}
				}
			}
			return $search;
		} // function getSearchArray

	} // class dbEditor

?>