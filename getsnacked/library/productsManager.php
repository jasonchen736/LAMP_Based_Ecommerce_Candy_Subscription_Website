<?

	class productsManager extends recordEditor {

		protected $historyTable = 'productsHistory';

		protected $required = array(
			'name',
			'availability',
			'cost',
			'weight'
		);

		protected $default = array(
			'dateAdded' => array('key' => 'dateAdded', 'value' => 'NOW()', 'update' => false),
			'lastModified' => array('key' => 'lastModified', 'value' => 'NOW()', 'update' => true)
		);

		protected $searchFields = array(
			'productID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'alphanum-search', 'range' => false),
			'availability' => array('type' => 'alphanum', 'range' => false),
			'cost' => array('type' => 'money', 'range' => true),
			'weight' => array('type' => 'decimal', 'range' => true),
			'length' => array('type' => 'decimal', 'range' => true),
			'width' => array('type' => 'decimal', 'range' => true),
			'height' => array('type' => 'decimal', 'range' => true),
			'quantity' => array('type' => 'integer', 'range' => true),
			'sortWeight' => array('type' => 'integer', 'range' => true)
		);

		protected $imageDir;

		protected $originalTags = false;
		protected $productTags = false;

		protected $originalQuantity;
		protected $quantity;

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
			$this->imageDir = $this->imageDir.'products';
			parent::__construct('products', array('productID'));
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
			return $search;
		} // function getSearchVars

		/**
		 *  Return search and count query
		 *  Args: (int) limit start record, (int) limit show record
		 *  Return: (array) search query, count query
		 */
		public function getSearch($start, $show) {
			$search = parent::getSearchArray();
			$tags = getRequest('tags');
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
			if ($tags) {
				$firstKey = false;
				$tagTables = array();
				$productTables = array();
				$tagClause = array();
				foreach ($tags as $key => $val) {
					if ($firstKey === false) {
						$firstKey = $key;
						$productTables[] = '`productTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`)';
					} else {
						$productTables[] = '`productTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID` AND `pt'.$firstKey.'`.`productID` = `pt'.$key.'`.`productID`)';
					}
					$tagTables[] = '`productTags` `t'.$key.'`';
					$tagClause[] = "`t".$key."`.`tag` = '".$val."'";
				}
				$searchSql = 'SELECT `products`.* FROM '.implode(' JOIN ', $tagTables).' JOIN '.implode(' JOIN ', $productTables).' JOIN `products` ON (`pt'.$firstKey.'`.`productID` = `products`.`productID`) JOIN `productInventory` ON (`products`.`productID` = `productInventory`.`productID`)'.($search ? ' WHERE `products`.'.implode(' AND `products`.', $search).' AND '.implode(' AND ', $tagClause) : ' WHERE '.implode(' AND ', $tagClause));
				$countSql = 'SELECT COUNT(*) AS `count` FROM '.implode(' JOIN ', $tagTables).' JOIN '.implode(' JOIN ', $productTables).' JOIN `products` ON (`pt'.$firstKey.'`.`productID` = `products`.`productID`) JOIN `productInventory` ON (`products`.`productID` = `productInventory`.`productID`)'.($search ? ' WHERE `products`.'.implode(' AND `products`.', $search).' AND '.implode(' AND ', $tagClause) : ' WHERE '.implode(' AND ', $tagClause));
			} else {
				$searchSql = 'SELECT * FROM `products` JOIN `productInventory` ON (`products`.`productID` = `productInventory`.`productID`)'.($search ? ' WHERE '.implode(' AND ', $search) : '').' LIMIT '.$start.', '.$show;
				$countSql = 'SELECT COUNT(*) AS `count` FROM `products` JOIN `productInventory` ON (`products`.`productID` = `productInventory`.`productID`)'.($search ? ' WHERE '.implode(' AND ', $search) : '');
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
									$sql = "INSERT IGNORE INTO `productTagMap` (`tagID`, `productID`, `dateCreated`) VALUES ('".implode("', '".$val."', NOW()), ('", $tagIDs)."', '".$val."', NOW())";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] added to products (id) '.implode(', ', $updated));
							}
						}
						break;
					case 'removeTags':
						$tagIDs = $this->getTagsFromPost('retrieveIDs');
						if ($tagIDs) {
							foreach ($recordIDs as $val) {
								if (validNumber($val, 'integer')) {
									$sql = "DELETE FROM `productTagMap` WHERE `productID` = '".$val."' AND `tagID` IN ('".implode("', '", $tagIDs)."')";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] removed from products (id) '.implode(', ', $updated));
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
								addSuccess('Products (id) '.implode(', ', $updated).' updated');
							}
						}
						break;
				} // switch ($action)
			} // if (is_array($recordIDs) && $recordIDs)
			if ($updated) {
				return true;
			} else {
				addError('Unable to update products');
				return false;
			}
		} // function takeAction

		/**
		 *  Process, validate and add a product record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function addProduct() {
			$saved = $this->addRecord(array('productID'));
			if ($saved) {
				$sql = "INSERT INTO `productInventory` (`productID`, `quantity`) VALUES ('".$this->record['productID']."', '".getPost('quantity', 'integer')."')";
				query($sql);
				$tags = getPost('productTags');
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
				$_POST['productTags'] = $tags;
				$this->takeAction(array($this->record['productID']), 'addTags');
				$this->originalTags = $this->getProductTags($this->record['productID']);
				$this->productTags = $this->originalTags;
			} else {
				$tags = getPost('productTags');
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
				$this->productTags = $tags;
			}
			return $saved;
		} // function addProduct

		/**
		 *  Process, validate and update a product record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateProduct() {
			if ($this->original) {
				foreach ($_POST as $key => $val) {
					if ($key != 'productID' && array_key_exists($key, $this->record)) {
						$this->record[$key] = clean(getPost($key));
					}
				}
				if ($this->original['cost'] != $this->record['cost'] ||
					$this->original['weight'] != $this->record['weight']) {
					$dataChange = true;
				} else {
					$dataChange = false;
				}
				$imageChange = isset($_FILES['imageUpload']) && $_FILES['imageUpload']['name'] && $this->uploadImage();
				$this->update();
				$this->quantity = getPost('quantity', 'integer');
				$quantityUpdated = false;
				if ($this->originalQuantity != $this->quantity) {
					$sql = "UPDATE `productInventory` SET `quantity` = '".$this->quantity."' WHERE `productID` = '".$this->record['productID']."'";
					query($sql);
					$this->originalQuantity = $this->quantity;
					$quantityUpdated = true;
				}
				$tags = $this->getTagsFromPost('retrieveTags');
				$add = array_diff($tags, $this->originalTags);
				if ($add) {
					$_POST['productTags'] = implode(',', $add);
					$added = $this->takeAction(array($this->record['productID']), 'addTags');
				} else {
					$added = false;
				}
				$remove = array_diff($this->originalTags, $tags);
				if ($remove) {
					$_POST['productTags'] = implode(',', $remove);
					$removed = $this->takeAction(array($this->record['productID']), 'removeTags');
				} else {
					$removed = false;
				}
				if ($added || $removed || $imageChange || $quantityUpdated) {
					$this->originalTags = $this->getProductTags($this->record['productID']);
					$this->productTags = $this->originalTags;
					removeError('Record not updated: no change');
				}
				if (haveErrors()) {
					return false;
				} else {
					if ($dataChange) {
						$message = 'Product (Product ID: '.$this->record['productID'].') updated';
						redirect($_SERVER['PHP_SELF'].'/action/productChanges/productID/'.$this->record['productID'].'/successMessage/'.$message);
					}
					return true;
				}
			}
			addError('Unable to update product');
			return false;
		} // function updateProduct

		/**
		 *  Load a product record and associated product tags, override from parent
		 *  Args: (array) record id values
		 *  Return: (boolean) success
		 */
		public function load($id) {
			$loaded = parent::load($id);
			if ($loaded) {
				$result = query("SELECT `quantity` FROM `productInventory` WHERE `productID` = '".$this->record['productID']."'");
				$row = $result->fetchAssoc();
				$this->originalQuantity = $row['quantity'];
				$this->quantity = $row['quantity'];
				$this->originalTags = $this->getProductTags($this->record['productID']);
				$this->productTags = $this->originalTags;
			}
			return $loaded;
		} // function load

		/**
		 *  Resize and upload a product image
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
					if ($image->copyImage($this->imageDir.'/small/', $this->record['productID'].'_1.gif')) {
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
		 *  Get and explode product tags post request
		 *  Args: (boolean) create non existing tags
		 *  Return: (array) valid product tags
		 */
		public function getTagsFromPost($mode) {
			$tagIDs = array();
			$tags = getPost('productTags');
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
		 *  Retrieve tags associated to a product
		 *  Args: (int) product id
		 *  Return: (array) product tags array(tagID => tag)
		 */
		public function getProductTags($productID) {
			$tags = array();
			if (validNumber($productID, 'integer')) {
				$result = $this->dbh->query("SELECT `b`.`tagID`, `b`.`tag` FROM `productTagMap` `a` JOIN `productTags` `b` ON (`a`.`tagID` = `b`.`tagID`) WHERE `a`.`productID` = '".$productID."'");
				if ($result->rowCount) {
					while ($row = $result->fetchAssoc()) {
						$tags[$row['tagID']] = $row['tag'];
					}
				}
			}
			return $tags;
		} // function getProductTags

		/**
		 *  Recalculate sums of all packages containing argument product
		 *  Args: (int) product id, (str) cascade to
		 *  Return: (array) package ids and status array(ID => [true/false])
		 */
		public static function cascadeProductChanges($productID, $cascadeTo = 'packages') {
			switch ($cascadeTo) {
				case 'packages':
					$packages = array();
					if (validNumber($productID, 'integer')) {
						$result = query("SELECT `packageID` FROM `productToPackage` WHERE `productID` = '".$productID."'");
						if ($result->rowCount) {
							while ($row = $result->fetchAssoc()) {
								$packages[$row['packageID']] = false;
							}
							if ($packages) {
								$pm = new packageManager;
								foreach ($packages as $key => &$val) {
									if ($pm->loadID($key)) {
										if ($pm->recalculatePackageData()) {
											$val = true;
										}
									}
								}
							}
						}
					}
					return $packages;
					break;
				case 'offers':
					$offers = array();
					if (validNumber($productID, 'integer')) {
						$result = query("SELECT `b`.`offerID`, `b`.`packageID` FROM `productToPackage` `a` JOIN `packageToOffer` `b` ON (`a`.`packageID` = `b`.`packageID`) WHERE `a`.`productID` = '".$productID."'");
						if ($result->rowCount) {
							while ($row = $result->fetchAssoc()) {
								if (!isset($offers[$row['offerID']])) {
									$offers[$row['offerID']] = array();
								}
								$offers[$row['offerID']][$row['packageID']] = false;
							}
							if ($offers) {
								$om = new offersManager;
								foreach ($offers as $key => &$val) {
									if ($om->loadID($key)) {
										foreach ($val as $packageID => &$updated) {
											if ($om->updateOfferPackagePrice($packageID)) {
												$updated = true;
											}
										}
									}
								}
							}
						}
					}
					return $offers;
					break;
				default:
					return false;
					break;
			}
		} // function cascadeProductChanges

	} // class productsManager

?>