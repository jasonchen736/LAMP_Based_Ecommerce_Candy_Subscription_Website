<?

	class controller {
		// database handler
		protected $dbh;
		// controller for specified table
		protected $table;
		// db fields that map to select inputs (enum db fields), these are automatically detected and set
		protected $selectInputs;
		// search operators can be passed in the form of "[search field]_operator"
		//    search criteria will then be built according to operator type if specified
		protected $searchOperators = array(
			'equal' => '=',
			'not equal' => '!=',
			'greater than' => '>',
			'greater than or equal' => '>=',
			'less than' => '<',
			'less than or equal' => '<=',
			'contains' => 'LIKE',
		);
		// requests to disregard when constructing a query string
		protected $ignoreRequests = array(
			'submit', 
			'_nextPage', 
			'_previousPage', 
			'records', 
			'action', 
			'updateAction'
		);
		// fields available to search: array(field name => array(type, range))
		protected $searchFields;
		// holds search criteria: array(field name => array(value, imposed)
		//   index corresponds to a search field, while the value is an array where:
		//     0 is the search value
		//     1 is the search operator
		//     2 denotes whether the search value is used only as default (no search action: false) 
		//       or to impose it (always use: true)
		protected $searchValues = array();

		/**
		 *  Detect and set select input options for enum fields
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			$this->dbh = database::getInstance();
			$this->selectInputs = array();
			$result = $this->dbh->query('DESC `'.$this->table.'`');
			if ($result->rowCount) {
				while ($row = $result->fetchRow()) {
					if (preg_match('/^enum\(/', $row['Type'])) {
						$values = explode(',', rtrim(ltrim($row['Type'], 'enum('), ')'));
						$values = preg_replace('/\'/', '', $values);
						$options = array();
						foreach ($values as $key => $val) {
							$options[$val] = $val;
						}
						$this->selectInputs[$row['Field']] = $options;
					}
				}
			}
			$this->initialize();
		} // function __construct

		/**
		 *  Perform any controller specific initialization actions
		 *    OVERRIDE AS NEEDED
		 *  Args: none
		 *  Return: none
		 */
		public function initialize() {
		} // function initialize

		/**
		 *  Retrieve options from an enum data field
		 *  Args: (str) field name
		 *  Return: (array) value options
		 */
		public function getOptions($field) {
			if (array_key_exists($field, $this->selectInputs)) {
				return $this->selectInputs[$field];
			} else {
				return NULL;
			}
		} // function getOptions

		/**
		 *  Construct a get query string from get and post requests formatted for web friendly urls
		 *  Args: none
		 *  Return: (str) query string
		 */
		public function retrieveQueryString($ignore = array()) {
			assertArray($ignore);
			$ignore = array_merge($ignore, $this->ignoreRequests);
			$querystring = array();
			$request = array_merge($_GET, $_POST);
			foreach ($request as $key => $val) {
				if (!in_array($key, $ignore) && $val !== '') {
					// triple encode:
					//   double encode to compensate for mod rewrite's handling of auto (double) decoding encoded character
					//   extra encode for friendly urls (handle forward slashes)
					$querystring[] = $key.'/'.urlencode(urlencode(urlencode($val)));
				}
			}
			return implode('/', $querystring);
		} // function retrieveQueryString

		/**
		 *  Return the current table location coordinates made from request
		 *  Args: none
		 *  Return: (array) start record, number of records shown, current page
		 */
		public function getTableLocation() {
			$start = getRequest('_start', 'integer');
			$show = getRequest('_show', 'integer') ? getRequest('_show', 'integer') : 100;
			$page = getRequest('_page', 'integer');
			if (!getRequest('search') || getRequest('_nextPage') || getRequest('_previousPage') || getRequest('_page')) {
				if (is_null($start)) {
					$start = 0;
				}
				if (getRequest('_nextPage')) {
					$start += $show;
				} elseif (getRequest('_previousPage')) {
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
			// update post request array for function retrieveQueryString
			$_POST['_page'] = $page;
			$_POST['_start'] = $start;
			$_POST['_show'] = $show;
			return array($start, $show, $page);
		} // function getTableLocation

		/**
		 *  Set default search criteria (used only when there is no search action)
		 *  Args: (str) field name, (mixed) default value - if ranged, used array with index 0 and 1
		 *  Args: (str) search operator
		 *  Return: none
		 */
		public function setDefaultSearch($field, $value, $operator = false) {
			if (array_key_exists($field, $this->searchFields)) {
				if (!isset($this->searchValues[$field])) {
					$this->searchValues[$field] = array();
				}
				if (!is_array($value)) {
					$value = clean($value, $this->searchFields[$field]['type']);
				} else {
					array_walk_recursive($value, 'cleanWalk', $this->searchFields[$field]['type']);
				}
				$this->searchValues[$field][0] = $value;
				if ($operator && array_key_exists($operator, $this->searchOperators)) {
					$this->searchValues[$field][1] = $this->searchOperators[$operator];
				} else {
					$this->searchValues[$field][1] = false;
				}
				$this->searchValues[$field][2] = false;
			}
		} // function setDefaultSearch

		/**
		 *  Impose a search criteria, will always be used
		 *  Args: (str) field name, (mixed) default value - if ranged, used array with index 0 and 1
		 *  Args: (str) search operator
		 *  Return: none
		 */
		public function imposeSearch($field, $value, $operator = false) {
			if (array_key_exists($field, $this->searchFields)) {
				if (!isset($this->searchValues[$field])) {
					$this->searchValues[$field] = array();
				}
				if (!is_array($value)) {
					$value = clean($value, $this->searchFields[$field]['type']);
				} else {
					array_walk_recursive($value, 'cleanWalk', $this->searchFields[$field]['type']);
				}
				$this->searchValues[$field][0] = $value;
				if ($operator && array_key_exists($operator, $this->searchOperators)) {
					$this->searchValues[$field][1] = $this->searchOperators[$operator];
				} else {
					$this->searchValues[$field][1] = false;
				}
				$this->searchValues[$field][2] = true;
			}
		} // function imposeSearch

		/**
		 *  Return array of search values
		 *    Override as needed
		 *  Args: none
		 *  Return: (array) search values
		 */
		public function getSearchValues() {
			$search = array();
			$searchAction = getRequest('search');
			if ($searchAction) {
				$defaultSearch = false;
			} else {
				$defaultSearch = true;
			}
			foreach ($this->searchFields as $field => $vals) {
				if (isset($this->selectInputs[$field])) {
					$fieldOptions = array_merge(array('' => 'All'), $this->selectInputs[$field]);
				} else {
					$fieldOptions = false;
				}
				if (!$vals['range']) {
					$search[$field] = array();
					if ($defaultSearch) {
						if (isset($this->searchValues[$field])) {
							$search[$field]['value'] = $this->searchValues[$field][0];
							$search[$field]['operator'] = $this->searchValues[$field][1];
						} else {
							$search[$field]['value'] = '';
							$search[$field]['operator'] = false;
						}
					} else {
						if (isset($this->searchValues[$field]) && $this->searchValues[$field][2]) {
							$search[$field]['value'] = $this->searchValues[$field][0];
							$search[$field]['operator'] = $this->searchValues[$field][1];
						} else {
							$search[$field]['value'] = clean(getRequest($field), $vals['type']);
							$search[$field]['operator'] = clean(getRequest($field.'_operator'), 'alphanum');
						}
					}
					$search[$field]['options'] = $fieldOptions;
				} else {
					$search[$field.'From'] = array();
					$search[$field.'To'] = array();
					if ($defaultSearch) {
						if (isset($this->searchValues[$field])) {
							$search[$field.'From']['value'] = isset($this->searchValues[$field][0][0]) ? $this->searchValues[$field][0][0] : '';
							$search[$field.'To']['value'] = isset($this->searchValues[$field][0][1]) ? $this->searchValues[$field][0][1] : '';
						} else {
							$search[$field.'From']['value'] = '';
							$search[$field.'To']['value'] = '';
						}
					} else {
						if (isset($this->searchValues[$field]) && $this->searchValues[$field][2]) {
							$search[$field.'From']['value'] = isset($this->searchValues[$field][0][0]) ? $this->searchValues[$field][0][0] : '';
							$search[$field.'To']['value'] = isset($this->searchValues[$field][0][1]) ? $this->searchValues[$field][0][1] : '';
						} else {
							$search[$field.'From']['value'] = clean(getRequest($field.'From'), $vals['type']);
							$search[$field.'To']['value'] = clean(getRequest($field.'To'), $vals['type']);
						}
					}
					$search[$field.'From']['options'] = $fieldOptions;
					$search[$field.'To']['options'] = $fieldOptions;
				}
			}
			return $search;
		} // function getSearchValues

		/**
		 *  Return array of search components
		 *    Override as needed
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchComponents() {
			$search = array();
			list($start, $show, $page) = $this->getTableLocation();
			$search['select'] = '*';
			$search['start'] = $start;
			$search['show'] = $show;
			$search['tables'] = array(
				0 => '`'.$this->table.'`'
			);
			$search['where'] = array();
			$search['order'] = array();
			$defaultSearch = false;
			$performSearch = getRequest('search');
			// impose default search criteria when search action is not explicitly called
			if (!$performSearch && !empty($this->searchValues)) {
				$performSearch = true;
				$defaultSearch = true;
			}
			if ($performSearch) {
				foreach ($this->searchFields as $field => $val) {
					if (!$val['range']) {
						if ($defaultSearch) {
							if (isset($this->searchValues[$field])) {
								$value = $this->searchValues[$field][0];
								$operator = $this->searchValues[$field][1];
							} else {
								$value = false;
							}
						} else {
							if (isset($this->searchValues[$field]) && $this->searchValues[$field][2]) {
								$value = $this->searchValues[$field][0];
								$operator = $this->searchValues[$field][1];
							} else {
								$value = clean(urldecode(getRequest($field)), $val['type']);
								$operator = getRequest($field.'_operator');
								$operator = isset($this->searchOperators[$operator]) ? $this->searchOperators[$operator] : false;
							}
						}
						if ($value) {
							if ($operator) {
								if ($operator == 'LIKE') {
									$value = preg_replace('/\*/', '%', prep($value));
								} else {
									$value = prep($value);
								}
							} else {
								$operator = '=';
								$value = prep($value);
							}
							$search['where'][$field] = "AND `".$field."` ".$operator." '".$value."'";
						}
					} else {
						if ($defaultSearch) {
							if (isset($this->searchValues[$field])) {
								$valueFrom = isset($this->searchValues[$field][0][0]) ? $this->searchValues[$field][0][0] : '';
								$valueTo = isset($this->searchValues[$field][0][1]) ? $this->searchValues[$field][0][1] : '';
							} else {
								$valueFrom = '';
								$valueTo = '';
							}
						} else {
							if (isset($this->searchValues[$field]) && $this->searchValues[$field][2]) {
								$valueFrom = isset($this->searchValues[$field][0][0]) ? $this->searchValues[$field][0][0] : '';
								$valueTo = isset($this->searchValues[$field][0][1]) ? $this->searchValues[$field][0][1] : '';
							} else {
								$valueFrom = clean(getRequest($field.'From'), $val['type']);
								$valueTo = clean(getRequest($field.'To'), $val['type']);
							}
						}
						if ($valueFrom != '' || $valueTo != '') {
							if ($val['type'] == 'date') {
								$valueFrom = $valueFrom ? dateToSql($valueFrom) : $valueFrom;
								$valueTo = $valueTo ? dateToSql($valueTo) : $valueTo;
							}
							if ($valueFrom != '' && $valueTo != '') {
								$search['where'][$field] = "AND `".$field."` BETWEEN '".$valueFrom."' AND '".$valueTo."'";
							} elseif ($valueFrom != '') {
								$search['where'][$field] = "AND `".$field."` >= '".$valueFrom."'";
							} else {
								$search['where'][$field] = "AND `".$field."` <= '".$valueTo."'";
							}
						}
					}
				}
			}
			if (!empty($search['where'])) {
				$key = key($search['where']);
				$search['where'][$key] = preg_replace('/^(AND|OR) /', '', $search['where'][$key]);
			}
			return $search;
		} // function getSearchComponents

		/**
		 *  Return records found from a general search using $this->getSearchComponents()
		 *    Override as needed
		 *  Args: none
		 *  Return: (array) found records
		 */
		public function performSearch() {
			$searchCriteria = $this->getSearchComponents();
			$sql = "SELECT ".$searchCriteria['select']." FROM ".implode(" ", $searchCriteria['tables'])." ".(!empty($searchCriteria['where']) ? "WHERE ".implode(" ", $searchCriteria['where'])." " : "").(!empty($searchCriteria['order']) ? "ORDER BY ".implode(", ", $searchCriteria['order'])." " : "")."LIMIT ".$searchCriteria['start'].", ".$searchCriteria['show'];
			$result = $this->dbh->query($sql);
			return $result->fetchAll();
		} // function performSearch

		/**
		 *  Count total records found from general search
		 *    Override as needed
		 *  Args: none
		 *  Return: (int) records found
		 */
		public function countRecordsFound() {
			$searchCriteria = $this->getSearchComponents();
			$sql = "SELECT COUNT(*) AS `count` FROM ".implode(" ", $searchCriteria['tables'])." ".(!empty($searchCriteria['where']) ? "WHERE ".implode(" ", $searchCriteria['where'])." " : "");
			$result = $this->dbh->query($sql);
			$row = $result->fetchRow();
			return $row['count'];
		} // function countRecordsFound

		/**
		 *  Get object tags from request
		 *  Args: (str) request field, (str) retrieval mode, (str) tags table
		 *  Return: (array) valid product tags
		 */
		public static function retrieveObjectTags($field, $mode = 'retrieveOnly', $tagTable = false) {
			$tagTable = prep(clean($tagTable, 'alphanum'));
			$retrievedTags = array();
			$tags = getRequest($field);
			if ($tags) {
				// tags are submitted either comma separated or one per line
				if (!is_array($tags)) {
					if (preg_match('/\r\n/', $tags)) {
						$tags = explode("\r\n", $tags);
					} else {
						$tags = explode(',', $tags);
					}
				}
				foreach ($tags as $key => &$tag) {
					$tag = strtoupper(clean($tag, 'alphanum'));
					if (!$tag) {
						unset($tags[$key]);
					}
				}
				unset($tag);
				if (!empty($tags)) {
					switch ($mode) {
						case 'createAndRetrieveExisting':
							$dbh = database::getInstance();
							// create new tags, retrieve tag ids as indexes
							foreach ($tags as $key => &$tag) {
								$tag = prep($tag);
							}
							unset($tag);
							$dbh->query("INSERT IGNORE INTO `".$tagTable."` (`tag`) VALUES ('".implode("'), ('", $tags)."')");
							$result = $dbh->query("SELECT `tag`, `tagID` FROM `".$tagTable."` WHERE `tag` IN ('".implode("', '", $tags)."')");
							if ($result->rowCount) {
								while ($tag = $result->fetchRow()) {
									$retrievedTags[$tag['tagID']] = $tag['tag'];
								}
							}
							break;
						case 'retrieveExistingOnly':
							$dbh = database::getInstance();
							// do not create new tags, retrieve existing tags only, tag ids as indexes
							foreach ($tags as $key => &$tag) {
								$tag = prep($tag);
							}
							unset($tag);
							$result = $dbh->query("SELECT `tag`, `tagID` FROM `".$tagTable."` WHERE `tag` IN ('".implode("', '", $tags)."')");
							if ($result->rowCount) {
								while ($tag = $result->fetchRow()) {
									$retrievedTags[$tag['tagID']] = $tag['tag'];
								}
							}
							break;
						case 'retrieveOnly':
						default:
							// return tags from request, indexes are not tag ids, tags may or may not exists
							$retrievedTags = $tags;
							break;
					}
				}
			}
			return $retrievedTags;
		} // function retrieveObjectTags

		/**
		 *  Retrieve a tag id, optionally create if not found
		 *  Args: (str) tag name, (str) tags table, (boolean) create tag record if not found
		 *  Return: (int) tag id
		 */
		public static function retrieveTagID($tag, $tagTable, $create = false) {
			$dbh = database::getInstance();
			$tagTable = prep(clean($tagTable, 'alphanum'));
			$result = $dbh->query("SELECT `tagID` FROM `".$tagTable."` WHERE `tag` = '".prep($tag)."'");
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				return $row['tagID'];
			} elseif ($create) {
				$tag = clean($tag, 'alphanum');
				if ($tag) {
					$result = $dbh->query("INSERT INTO `".$tagTable."` (`tag`) VALUES ('".prep($tag)."')");
					if ($result->insertID) {
						return $result->insertID;
					}
				}
			}
			return false;
		} // function retrieveTagID
	} // class controller

?>