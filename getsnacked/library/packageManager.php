<?

	class packageManager extends recordEditor {

		protected $historyTable = 'packagesHistory';

		protected $required = array();

		protected $default = array(
			'dateCreated' => array('key' => 'dateCreated', 'value' => 'NOW()', 'update' => false),
			'lastModified' => array('key' => 'lastModified', 'value' => 'NOW()', 'update' => true)
		);

		protected $searchFields = array(
			'packageID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'alphanum-search', 'range' => false),
			'availability' => array('type' => 'alphanum', 'range' => false),
			'cost' => array('type' => 'money', 'range' => true),
			'weight' => array('type' => 'decimal', 'range' => true),
			'sortWeight' => array('type' => 'integer', 'range' => true)
		);

		protected $imageDir;

		protected $originalTags = false;
		protected $packageTags = false;

		/**
		 *  Initialize editor settings
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			$this->imageDir = systemSettings::get('IMAGEDIR');
			if (!preg_match('/\/$/', $this->imageDir)) {
				$this->imageDir .= '/';
			}
			$this->imageDir = $this->imageDir.'packages';
			parent::__construct('packages', array('packageID'));
		} // function __construct

		/**
		 *  Return array of search variables
		 *  Args: none
		 *  Return: (array) search vars
		 */
		public function getSearchVars() {
			$search = parent::getSearchVars();
			$search['tags'] = array();
			$search['tags']['value'] = getRequest('tags');
			$search['content'] = array();
			$search['content']['value'] = getRequest('content');
			return $search;
		} // function getSearchVars

		/**
		 *  Return search and count query
		 *  Args: (int) limit start record, (int) limit show record
		 *  Return: (array) search query, count query
		 */
		public function getSearch($start, $show) {
			$search = parent::getSearchArray();
			$content = getRequest('content');
			if ($content) {
				$content = explode(',', $content);
				$products = array();
				foreach ($content as $key => $val) {
					$val = trim($val);
					if (!validNumber($val, 'integer') || !$val) {
						unset($content[$key]);
					} else {
						$products[$val] = true;
					}
				}
				if ($products) {
					// retrieve a list of filtered product ids
					$packageIDs = self::findPackageByContent($products, true);
					if ($packageIDs) {
						$search[] = "`packageID` IN ('".implode("', '", $packageIDs)."')";
					}
				}
			}
			$tags = getRequest('tags');
			if ($tags) {
				if ($tags) {
					$tags = explode(',', $tags);
					foreach ($tags as $key => $tag) {
						if (!$tag || !preg_match('/^[a-z0-9 ]*$/i', $tag)) {
							unset($tags[$key]);
						} else {
							$tags[$key] = strtoupper(trim($tag));
						}
					}
				}
			}
			if ($tags) {
				$firstKey = false;
				$tagTables = array();
				$packageTables = array();
				$tagClause = array();
				foreach ($tags as $key => $val) {
					if ($firstKey === false) {
						$firstKey = $key;
						$packageTables[] = '`packageTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`)';
					} else {
						$packageTables[] = '`packageTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID` AND `pt'.$firstKey.'`.`packageID` = `pt'.$key.'`.`packageID`)';
					}
					$tagTables[] = '`productTags` `t'.$key.'`';
					$tagClause[] = "`t".$key."`.`tag` = '".$val."'";
				}

				$searchSql = 'SELECT `packages`.* FROM '.implode(' JOIN ', $tagTables).' JOIN '.implode(' JOIN ', $packageTables).' JOIN `packages` ON (`pt'.$firstKey.'`.`packageID` = `packages`.`packageID`)'.($search ? ' WHERE `packages`.'.implode(' AND `packages`.', $search).' AND '.implode(' AND ', $tagClause) : ' WHERE '.implode(' AND ', $tagClause));

				$countSql = 'SELECT COUNT(*) AS `count` FROM '.implode(' JOIN ', $tagTables).' JOIN '.implode(' JOIN ', $packageTables).' JOIN `packages` ON (`pt'.$firstKey.'`.`packageID` = `packages`.`packageID`)'.($search ? ' WHERE `packages`.'.implode(' AND `packages`.', $search).' AND '.implode(' AND ', $tagClause) : ' WHERE '.implode(' AND ', $tagClause));
			} else {
				$searchSql = 'SELECT * FROM `packages`'.($search ? ' WHERE '.implode(' AND ', $search) : '').' LIMIT '.$start.', '.$show;
				$countSql = 'SELECT COUNT(*) AS `count` FROM `packages`'.($search ? ' WHERE '.implode(' AND ', $search) : '');
			}
			return array($searchSql, $countSql);
		} // function getSearch

		/**
		 *  Return an array of available actions
		 *  Args: none
		 *  Return: (array) actions
		 */
		public function getActions() {
			$actions = array();
			$actions['addTags'] = 'Add Tags';
			$actions['removeTags'] = 'Remove Tags';
			$actions = array_merge($actions, $this->fields['availability']);
			return $actions;
		} // function getActions

		/**
		 *  Update a set of given records
		 *  Args: (array) record ids, (str) action
		 *  Return: none
		 */
		public function takeAction($recordIDs, $action) {
			$updated = array();
			if (is_array($recordIDs) && $recordIDs) {
				switch ($action) {
					case 'addTags':
						$tagIDs = $this->getTagsFromPost('createAndRetrieveIDs');
						if ($tagIDs) {
							foreach ($recordIDs as $val) {
								if (validNumber($val, 'integer')) {
									$sql = "INSERT IGNORE INTO `packageTagMap` (`tagID`, `packageID`, `dateCreated`) VALUES ('".implode("', '".$val."', NOW()), ('", $tagIDs)."', '".$val."', NOW())";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] added to packages (id) '.implode(', ', $updated));
							}
						}
						break;
					case 'removeTags':
						$tagIDs = $this->getTagsFromPost('retrieveIDs');
						if ($tagIDs) {
							foreach ($recordIDs as $val) {
								if (validNumber($val, 'integer')) {
									$sql = "DELETE FROM `packageTagMap` WHERE `packageID` = '".$val."' AND `tagID` IN ('".implode("', '", $tagIDs)."')";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] removed from packages (id) '.implode(', ', $updated));
							}
						}
						break;
					default:
						if(in_array($action, $this->fields['availability'])) {
							foreach ($recordIDs as $val) {
								if (validNumber($val, 'integer')) {
									$this->loadID($val);
									$this->record['availability'] = $action;
									$this->update();
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Packages (id) '.implode(', ', $updated).' updated');
							}
						}
						break;
				} // switch ($action)
			} // if (is_array($recordIDs) && $recordIDs)
			if ($updated) {
				return true;
			} else {
				addError('Unable to update packages');
				return false;
			}
		} // function takeAction

		/**
		 *  Search for a package by its contents
		 *  Args: (array) content array array(productID => quantity, ... ), (boolean) ignore quantities
		 *  Return: (mixed) false for unable to locate, packageID for quantity search, array if ignoring quantities
		 */
		public static function findPackageByContent($content, $ignoreQuantities = false) {
			$query = 'SELECT `t0`.`packageID` FROM `productToPackage` `t0`';
			$joins = array();
			$clause = array();
			$count = 0;
			foreach ($content as $key => $val) {
				if ($count != 0) {
					$joins[] = "JOIN `productToPackage` `t".$count."` ON (`t".($count - 1)."`.`packageID` = `t".$count."`.`packageID`)";
				}
				$clause[] = "`t".$count."`.`productID` = '".$key."'".(!$ignoreQuantities ? " AND `t".$count."`.`quantity` = '".$val."'" : '');
				++$count;
			}
			$joins[] = "LEFT JOIN `productToPackage` `t".$count."` ON (`t".($count - 1)."`.`packageID` = `t".$count."`.`packageID` AND `t".$count."`.`productID` NOT IN ('".implode("', '", array_keys($content))."'))";
			$clause[] = "`t".$count."`.`packageID` IS NULL";
			$query .= implode(' ', $joins).' WHERE '.implode(' AND ', $clause);
			$result = query($query);
			if ($result->rowCount) {
				if (!$ignoreQuantities) {
					$row = $result->fetchAssoc();
					return $row['packageID'];
				} else {
					$packageIDs = array();
					while ($row = $result->fetchAssoc()) {
						$packageIDs[] = $row['packageID'];
					}
					return $packageIDs;
				}
			} else {
				return false;
			}
		} // function findPackageByContent

		/**
		 *  Process, validate and add a package record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function addPackage() {
			// validate package content
			$products = getPost('addProduct');
			$quantities = getPost('addQuantity');
			$validContent = true;
			if (is_array($products) && $products && is_array($quantities) || count($quantities) == count($products)) {
				// validate product ids and quantities
				$productArray = array();
				foreach ($products as $key => $val) {
					if (!isset($quantities[$key]) || !validNumber($quantities[$key], 'integer') || !$quantities[$key]) {
						$validContent = false;
						addError('Package content is invalid');
						continue;
					}
					if (!validNumber($val, 'integer') || !$val) {
						$validContent = false;
						addError('Package content is invalid');
						continue;
					}
					if (!in_array($val, $productArray)) {
						$this->dbh->query("SELECT * FROM `products` WHERE `productID` = '".$val."'");
						if (!$this->dbh->rowCount) {
							$validContent = false;
							addError('Package content error: Product ID '.$val.' cannot be found');
							continue;
						} else {
							$productArray[$key] = $val;
						}
					} else {
						$validContent = false;
						continue;
					}
				}
				if ($validContent) {
					// check for duplicate package
					$content = array();
					foreach ($productArray as $key => $val) {
						$content[$val] = $quantities[$key];
					}
					$foundPackage = self::findPackageByContent($content);
					if ($foundPackage !== false) {
						$validContent = false;
						$foundPackage = self::retrievePackageByID($foundPackage);
						addError('Package already exists: <a href="/admin/packages/packageID/'.$foundPackage['packageID'].'/action/edit" >Package ID '.$foundPackage['packageID'].' - '.$foundPackage['name'].'</a>');
					}
				}
			} else {
				$validContent = false;
				addError('Package content is invalid');
			}
			if ($validContent) {
				// calculate price and weight
				$packageData = self::calculatePackageData($productArray, $quantities);
				$_POST['cost'] = $packageData['cost'];
				$_POST['weight'] = $packageData['weight'];
				$saved = $this->addRecord(array('packageID'));
				if ($saved) {
					// create productToPackage associations
					$insertVals = '';
					foreach ($productArray as $key => $val) {
						$insertVals .= "('".$val."', '".$quantities[$key]."', '".$this->record['packageID']."'), ";
					}
					$insertVals = rtrim($insertVals, ', ');
					$this->dbh->query("INSERT INTO `productToPackage` (`productID`, `quantity`, `packageID`) VALUES ".$insertVals);
					// save package tags
					$tags = getPost('packageTags');
					if ($tags) {
						if (preg_match('/\r\n/', $tags)) {
							$tags = explode("\r\n", $tags);
						} else {
							$tags = explode(',', $tags);
						}
						$tags[] = systemSettings::get('SITENAME');
						$tags = array_unique($tags);
						$tags = implode(',', $tags);
					} else {
						$tags = systemSettings::get('SITENAME');
					}
					$_POST['packageTags'] = $tags;
					$this->takeAction(array($this->record['packageID']), 'addTags');
					$this->originalTags = $this->getPackageTags($this->record['packageID']);
					$this->packageTags = $this->originalTags;
				} else {
					$tags = getPost('packageTags');
					if ($tags) {
						if (preg_match('/\r\n/', $tags)) {
							$tags = explode("\r\n", $tags);
						} else {
							$tags = explode(',', $tags);
						}
						$tags[] = systemSettings::get('SITENAME');
						$tags = array_unique($tags);
					} else {
						$tags = array(systemSettings::get('SITENAME'));
					}
					$this->packageTags = $tags;
				}
			} else {
				$this->record = array();
				foreach ($this->fields as $key => $val) {
					if ($key != 'packageID') {
						$this->record[$key] = clean(getPost($key));
					}
				}
				$tags = getPost('packageTags');
				if ($tags) {
					if (preg_match('/\r\n/', $tags)) {
						$tags = explode("\r\n", $tags);
					} else {
						$tags = explode(',', $tags);
					}
					$tags[] = systemSettings::get('SITENAME');
					$tags = array_unique($tags);
				} else {
					$tags = array(systemSettings::get('SITENAME'));
				}
				$this->packageTags = $tags;
				$saved = false;
			}
			return $saved;
		} // function addPackage

		/**
		 *  Process, validate and update a package record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updatePackage() {
			if ($this->original) {
				foreach ($_POST as $key => $val) {
					if ($key != 'packageID' && array_key_exists($key, $this->record)) {
						$this->record[$key] = clean(getPost($key));
					}
				}
				$imageChange = isset($_FILES['imageUpload']) && $_FILES['imageUpload']['name'] && $this->uploadImage();
				$this->update();
				$tags = $this->getTagsFromPost('retrieveTags');
				$add = array_diff($tags, $this->originalTags);
				if ($add) {
					$_POST['packageTags'] = implode(',', $add);
					$added = $this->takeAction(array($this->record['packageID']), 'addTags');
				} else {
					$added = false;
				}
				$remove = array_diff($this->originalTags, $tags);
				if ($remove) {
					$_POST['packageTags'] = implode(',', $remove);
					$removed = $this->takeAction(array($this->record['packageID']), 'removeTags');
				} else {
					$removed = false;
				}
				if ($added || $removed || $imageChange) {
					$this->originalTags = $this->getPackageTags($this->record['packageID']);
					$this->packageTags = $this->originalTags;
					removeError('Record not updated: no change');
				}
				if (haveErrors()) {
					return false;
				} else {
					return true;
				}
			}
			addError('Unable to update package');
			return false;
		} // function updatePackage

		/**
		 *  Load a package record and associated package tags, override from parent
		 *  Args: (array) record id values
		 *  Return: (boolean) success
		 */
		public function load($id) {
			$loaded = parent::load($id);
			if ($loaded) {
				$this->originalTags = $this->getPackageTags($this->record['packageID']);
				$this->packageTags = $this->originalTags;
			}
			return $loaded;
		} // function load

		/**
		 *  Resize and upload a package image
		 *  Args: none
		 *  Return: none
		 */
		public function uploadImage() {
			if ($this->original) {
				if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['name']) {
					$image = new image('imageUpload');
					// currently only uploads small image, needs to be expanded
					$image->resize(148, 148);
					$image->convertImage('gif');
					if ($image->copyImage($this->imageDir.'/small/', $this->record['packageID'].'_1.gif')) {
						$this->record['imagesSmall'] = 1;
						return true;
					} else {
						addErrorField('image');
						addError('Image could not be uploaded');
						return false;
					}
				} else {
					return true;
				}
			}
			return false;
		} // function uploadImage

		/**
		 *  Get and explode package tags post request
		 *  Args: (boolean) create non existing tags
		 *  Return: (array) valid package tags
		 */
		public function getTagsFromPost($mode) {
			$tagIDs = array();
			$tags = getPost('packageTags');
			if ($tags) {
				if (preg_match('/\r\n/', $tags)) {
					$tags = explode("\r\n", $tags);
				} else {
					$tags = explode(',', $tags);
				}
				foreach ($tags as $key => $tag) {
					if ($tag && preg_match('/^[a-z0-9_ ]*$/i', $tag)) {
						switch ($mode) {
							case 'createAndRetrieveIDs':
								$cleanTag = prepDB(trim(strtoupper($tag)));
								$this->dbh->query("INSERT IGNORE INTO `productTags` (`tag`, `dateCreated`) VALUES ('".$cleanTag."', NOW())");
								if ($this->dbh->rowCount) {
									$tagIDs[$cleanTag] = $this->dbh->insertID;
								} else {
									$result = $this->dbh->query("SELECT `tagID` FROM `productTags` WHERE `tag` = '".$cleanTag."'");
									if ($result->rowCount) {
										$tagID = $result->fetchAssoc();
										$tagIDs[$cleanTag] = $tagID['tagID'];
									}
								}
								break;
							case 'retrieveIDs':
								$cleanTag = prepDB(trim(strtoupper($tag)));
								$result = $this->dbh->query("SELECT `tagID` FROM `productTags` WHERE `tag` = '".$cleanTag."'");
								if ($result->rowCount) {
									$tagID = $result->fetchAssoc();
									$tagIDs[$cleanTag] = $tagID['tagID'];
								}
								break;
							case 'retrieveTags':
							default:
								$tagIDs[] = trim(strtoupper($tag));
								break;
						}
					}
				}
			}
			return $tagIDs;
		} // function getTagsFromPost

		/**
		 *  Retrieve tags associated to a package
		 *  Args: (int) package id
		 *  Return: (array) package tags array(tagID => tag)
		 */
		public function getPackageTags($packageID) {
			$tags = array();
			if (validNumber($packageID, 'integer')) {
				$result = $this->dbh->query("SELECT `b`.`tagID`, `b`.`tag` FROM `packageTagMap` `a` JOIN `productTags` `b` ON (`a`.`tagID` = `b`.`tagID`) WHERE `a`.`packageID` = '".$packageID."'");
				if ($result->rowCount) {
					while ($row = $result->fetchAssoc()) {
						$tags[$row['tagID']] = $row['tag'];
					}
				}
			}
			return $tags;
		} // function getPackageTags

		/**
		 *  Retrieve product contents of a package as an array of product records
		 *  Args: (int) package id, (boolean) retrieve current associations on record
		 *  Return: (array) package content
		 */
		public static function getPackageContents($packageID, $retrieveFromPackage = false) {
			$products = getPost('addProduct');
			if ($products && !$retrieveFromPackage) {
				assertArray($products);
				$quantities = getPost('addQuantity');
				assertArray($quantities);
				$contents = array();
				foreach ($products as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($products[$key]);
					} else {
						$contents[$val] = array();
						if (isset($quantities[$key]) && validNumber($quantities[$key])) {
							$contents[$val]['quantity'] = $quantities[$key];
						} else {
							$contents[$val]['quantity'] = '';
						}
					}
				}
				if ($products) {
					$result = query("SELECT `productID`, `name`, `cost`, `availability` FROM `products` WHERE `productID` IN ('".implode("', '", $products)."')");
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$contents[$row['productID']]['name'] = $row['name'];
							$contents[$row['productID']]['cost'] = $row['cost'];
							$contents[$row['productID']]['availability'] = $row['availability'];
						}
					}
				}
			} elseif (validNumber($packageID, 'integer') && $packageID) {
				$result = query("SELECT `a`.`quantity` AS `packageQuantity`, `b`.*, `c`.`quantity` AS `inventory` FROM `productToPackage` `a` JOIN `products` `b` USING (`productID`) JOIN `productInventory` `c` USING (`productID`) WHERE `a`.`packageID` = '".$packageID."'");
				if ($result->rowCount) {
					$contents = $result->fetchAllAssoc();
				}
			}
			assertArray($contents);
			return $contents;
		} // function getPackageContents

		/**
		 *  Calculate a package's total cost and weight from given products and quantities
		 *  Args: (array) product IDs, (array) quantities
		 *  Return: (mixed) false if unable to calculate, array if calculated
		 */
		public static function calculatePackageData($products, $quantities) {
			if (is_array($products) && $products && is_array($quantities) && $quantities && count($products) == count($quantities)) {
				foreach ($products as $key => $val) {
					if (validNumber($val, 'integer')) {
						if (!isset($quantities[$key]) || !validNumber($quantities[$key], 'integer')) {
							return false;
						}
					} else {
						return false;
					}
				}
				$result = query("SELECT `productID`, `cost`, `weight` FROM `products` WHERE `productID` IN ('".implode("', '", $products)."')");
				if ($result->rowCount) {
					$packageData = array();
					$packageData['cost'] = 0;
					$packageData['weight'] = 0;
					while ($row = $result->fetchAssoc()) {
						$productKey = array_search($row['productID'], $products);
						$packageData['cost'] += $row['cost'] * $quantities[$productKey];
						$packageData['weight'] += $row['weight'] * $quantities[$productKey];
					}
					return $packageData;
				}
			}
			return false;
		} // function calculatePackageData

		/**
		 *  Recalculate and save package data
		 *  Args: (str) type of data
		 *  Return: (boolean) success
		 */
		public function recalculatePackageData($type = 'all') {
			if ($this->original) {
				$result = query("SELECT SUM(`a`.`quantity` * `b`.`cost`) AS `packageCost`, SUM(`a`.`quantity` * `b`.`weight`) AS `packageWeight` FROM `productToPackage` `a` JOIN `products` `b` USING(`productID`) WHERE `a`.`packageID` = '".$this->record['packageID']."'");
				$row = $result->fetchAssoc();
				switch ($type) {
					case 'cost':
						$this->record['cost'] = $row['packageCost'];
						break;
					case 'weight':
						$this->record['weight'] = $row['packageWeight'];
						break;
					case 'all':
					default:
						$this->record['cost'] = $row['packageCost'];
						$this->record['weight'] = $row['packageWeight'];
						break;
				}
				return $this->updatePackage();
			}
			return false;
		} // function recalculatePackageData

		/**
		 *  Retrieve package record by id
		 *  Args: (integer) package ID
		 *  Return: (mixed) false if unable to locate, array if found
		 */
		public static function retrievePackageByID($packageID) {
			$package = false;
			if (validNumber($packageID, 'integer')) {
				$result = query("SELECT * FROM `packages` WHERE `packageID` = '".$packageID."'");
				if ($result->rowCount) {
					$package = $result->fetchAssoc();
				}
			}
			return $package;
		} // function retrievePackageByID

	} // class packageManager

?>