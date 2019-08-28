<?

	class packagesController extends controller {
		// controller for specified table
		protected $table = 'packages';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'packageID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'name-search', 'range' => false),
			'sku' => array('type' => 'alphanum-search', 'range' => false),
			'brand' => array('type' => 'name-search', 'range' => false),
			'availability' => array('type' => 'alpha', 'range' => false),
			'cost' => array('type' => 'double', 'range' => true),
			'weight' => array('type' => 'double', 'range' => true)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'addTags' => 'Add Tags',
				'removeTags' => 'Remove Tags',
				'available' => 'Set Availability: Available',
				'alwaysavailable' => 'Set Availability: Always Available',
				'outofstock' => 'Set Availability: Out Of Stock',
				'withheld' => 'Set Availability: Withheld',
				'discontinued' => 'Set Availability: Discontinued'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Return array of search values
		 *  Adds additional package search values
		 *    Override
		 *  Args: none
		 *  Return: (array) search values
		 */
		public function getSearchValues() {
			$search = parent::getSearchValues();
			$search['tags'] = array();
			$search['tags']['value'] = getRequest('tags');
			$search['content'] = array();
			$search['content']['value'] = getRequest('content');
			return $search;
		} // function getSearchValues

		/**
		 *  Return array of search sql components
		 *  Rearranges and prepares additional product search components to work with native search method
		 *    Override
		 *  Args: none
		 *  Return: (array) search components
		 */
		public function getSearchComponents() {
			$search = parent::getSearchComponents();
			$search['tables'][0] = '`'.$this->table.'` `a`';
			foreach ($search['where'] as $field => &$val) {
				$val = preg_replace('/^(AND |OR )?/', '$1`a`.', $val);
			}
			unset($val);
			$tags = getRequest('tags');
			if ($tags) {
				$tags = explode(',', $tags);
				foreach ($tags as $key => &$tag) {
					$tag = strtoupper(trim(clean($tag, 'alphanum')));
					if (!$tag) {
						unset($tags[$key]);
					}
				}
				$tagTables = array();
				if (!empty($tags)) {
					$firstKey = false;
					foreach ($tags as $key => $val) {
						if ($firstKey === false) {
							$firstKey = $key;
							$search['tables'][0] = 'JOIN `'.$this->table.'` `a` ON (`pt'.$firstKey.'`.`packageID` = `a`.`packageID`)';
							$tagTables[] = '`productTags` `t'.$key.'`';
							$tagTables[] = 'JOIN `packageTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`)';
						} else {
							$tagTables[] = 'JOIN `packageTagMap` `pt'.$key.'` ON (`pt'.$firstKey.'`.`packageID` = `pt'.$key.'`.`packageID`)';
							$tagTables[] = 'JOIN `productTags` `t'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`)';
						}
						$search['where'][] = "AND `t".$key."`.`tag` = '".$val."'";
					}
					krsort($tagTables);
					foreach ($tagTables as $tagTable) {
						array_unshift($search['tables'], $tagTable);
					}
				}
			}
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
					$packageIDs = self::findPackageByContent($products, false, false);
					if ($packageIDs) {
						$search['where'][] = "AND `a`.`packageID` IN ('".implode("', '", $packageIDs)."')";
					}
				}
			}
			if (!empty($search['where'])) {
				reset($search['where']);
				$key = key($search['where']);
				$search['where'][$key] = preg_replace('/^(AND|OR) /', '', $search['where'][$key]);
			}
			$search['order'][] = '`a`.`packageID` ASC';
			return $search;
		} // function getSearchComponents

		/**
		 *  Read image inputs and perform adds/updates/removes
		 *  Args: (int) package id
		 *  Return: (mixed) boolean for process success, null if no change detected
		 */
		public static function processImages($packageID) {
			$package = new package($packageID);
			if ($package->exists()) {
				$packageID = $package->get('packageID');
				assertArray($_FILES['packageImages']);
				assertArray($_FILES['packageImages']['name']);
				assertArray($_FILES['packageImages']['type']);
				assertArray($_FILES['packageImages']['tmp_name']);
				assertArray($_FILES['packageImages']['error']);
				assertArray($_FILES['packageImages']['size']);
				$small = $package->get('imagesSmall');
				$medium = $package->get('imagesMedium');
				$large = $package->get('imagesLarge');
				$packageImagesSizes = getPost('packageImagesSizes');
				$packageImagesExistingSize = getPost('packageImagesExistingSize');
				$packageImagesExistingIndex = getPost('packageImagesExistingIndex');
				// size => array(image index)
				// assume all images to be removed initially
				$remove = array(
					'small' => array(),
					'medium' => array(),
					'large' => array()
				);
				for ($i = 1; $i <= $small; $i++) {
					$remove['small'][$i] = true;
				}
				for ($i = 1; $i <= $medium; $i++) {
					$remove['medium'][$i] = true;
				}
				for ($i = 1; $i <= $large; $i++) {
					$remove['large'][$i] = true;
				}
				// size => array(image index => file index, existing => array(size, index))
				$process = array(
					'small' => array(),
					'medium' => array(),
					'large' => array(),
				);
				$imageChange = false;
				// calculate image process jobs
				foreach ($_FILES['packageImages']['name'] as $key => $val) {
					if (isset($packageImagesSizes[$key]) && array_key_exists($packageImagesSizes[$key], $process)) {
						$existingSize = isset($packageImagesExistingSize[$key]) && array_key_exists($packageImagesExistingSize[$key], $process) ? $packageImagesExistingSize[$key] : false;
						if ($existingSize) {
							$existingIndex = isset($packageImagesExistingIndex[$key]) && between($packageImagesExistingIndex[$key], 1, $$existingSize) ? $packageImagesExistingIndex[$key] : false;
						} else {
							$existingIndex = false;
						}
						if ($existingSize && $existingIndex) {
							$existing = array($existingSize, $existingIndex);
						} else {
							$existing = false;
						}
						if ($existing === false) {
							if (!empty($_FILES['packageImages']['tmp_name'][$key])) {
								$process[$packageImagesSizes[$key]][] = array(
									$key,
									$existing
								);
								$imageChange = true;
							}
						} else {
							$process[$packageImagesSizes[$key]][] = array(
								$key,
								$existing
							);
							if ($existing[0] != $packageImagesSizes[$key] || $existing[1] != count($process[$packageImagesSizes[$key]]) || !empty($_FILES['packageImages']['tmp_name'][$key])) {
								$imageChange = true;
							}
						}
						$remove[$packageImagesSizes[$key]][count($process[$packageImagesSizes[$key]])] = false;
					}
				}
				foreach ($remove as $key => $size) {
					foreach ($size as $delete) {
						if ($delete) {
							$imageChange = true;
						}
					}
				}
				if ($imageChange) {
					$imageDir = systemSettings::get('IMAGEDIR');
					// first duplicate any images that need to be moved (size change only)
					foreach ($process as $size => $images) {
						foreach ($images as $imageIndex => $imageData) {
							++$imageIndex;
							// existing
							if ($imageData[1] !== false) {
								// no new image
								if (empty($_FILES['packageImages']['tmp_name'][$imageData[0]])) {
									// different size or position
									if ($imageData[1][0] != $size || $imageData[1][1] != $imageIndex) {
										$fileData = array(
											'name' => $_FILES['packageImages']['name'][$imageData[0]],
											'type' => $_FILES['packageImages']['type'][$imageData[0]],
											'tmp_name' => $_FILES['packageImages']['tmp_name'][$imageData[0]],
											'error' => $_FILES['packageImages']['error'][$imageData[0]],
											'size' => $_FILES['packageImages']['size'][$imageData[0]]
										);
										$image = new image($packageID.'_'.$imageData[1][1].'.gif', 'file', $imageDir.'/packages/'.$imageData[1][0].'/');
										if (!$image->copyImage($imageDir.'/packages/'.$imageData[1][0].'/', $packageID.'_'.$imageData[1][1].'_swp.gif')) {
											addError('There was an error while creating a swap image');
											return false;
										}
									}
								}
							}
						}
					}
					foreach ($process as $size => $images) {
						foreach ($images as $imageIndex => $imageData) {
							++$imageIndex;
							$fileData = array(
								'name' => $_FILES['packageImages']['name'][$imageData[0]],
								'type' => $_FILES['packageImages']['type'][$imageData[0]],
								'tmp_name' => $_FILES['packageImages']['tmp_name'][$imageData[0]],
								'error' => $_FILES['packageImages']['error'][$imageData[0]],
								'size' => $_FILES['packageImages']['size'][$imageData[0]]
							);
							$move = false;
							if ($imageData[1] === false || !empty($fileData['tmp_name'])) {
								$image = new image($fileData, 'param');
							} else {
								if ($size != $imageData[1][0] || $imageIndex != $imageData[1][1]) {
									$move = true;
									$image = new image($packageID.'_'.$imageData[1][1].'_swp.gif', 'file', $imageDir.'/packages/'.$imageData[1][0].'/');
								} else {
									$image = false;
								}
							}
							if ($image !== false) {
								if (!$move) {
									// always convert to gif
									$image->convertImage('gif');
								}
								if (!$image->copyImage($imageDir.'/packages/'.$size.'/', $packageID.'_'.$imageIndex.'.gif')) {
									addError('There was an error while saving an image');
									return false;
								} elseif ($move) {
									if (!unlink($imageDir.'packages/'.$imageData[1][0].'/'.$packageID.'_'.$imageData[1][1].'_swp.gif')) {
										addError('There was an error while removing a swap image');
									}
								}
							}
						}
					}
					foreach ($remove as $size => $image) {
						foreach ($image as $index => $delete) {
							if ($delete) {
								$file = $imageDir.'packages/'.$size.'/'.$packageID.'_'.$index.'.gif';
								if (file_exists($file)) {
									if (!unlink($file)) {
										addError('There was an error while removing an image');
										return false;
									}
								}
							}
						}
					}
					$newSmall = count($process['small']);
					$newMedium = count($process['medium']);
					$newLarge = count($process['large']);
					$package->set('imagesSmall', $newSmall);
					$package->set('imagesMedium', $newMedium);
					$package->set('imagesLarge', $newLarge);
					return $package->update();
				} else {
					return NULL;
				}
				return false;
			} else {
				addError('Package was not found while trying to process images');
			}
		} // function processImages

		/**
		 *  Search for a package by its contents
		 *  Args: (array) content array array(productID => quantity, ... ), (boolean) ignore quantities
		 *  Args: (boolean) exact content match
		 *  Return: (mixed) false for unable to locate, packageID for quantity search, array if ignoring quantities
		 */
		public static function findPackageByContent($content, $quantities = true, $exact = true) {
			$query = 'SELECT `t0`.`packageID` FROM `productToPackage` `t0`';
			$joins = array();
			$clause = array();
			$count = 0;
			foreach ($content as $key => $val) {
				if ($count != 0) {
					$joins[] = "JOIN `productToPackage` `t".$count."` ON (`t".($count - 1)."`.`packageID` = `t".$count."`.`packageID`)";
				}
				$clause[] = "`t".$count."`.`productID` = '".$key."'".($quantities ? " AND `t".$count."`.`quantity` = '".$val."'" : '');
				++$count;
			}
			if ($exact) {
				$joins[] = "LEFT JOIN `productToPackage` `t".$count."` ON (`t".($count - 1)."`.`packageID` = `t".$count."`.`packageID` AND `t".$count."`.`productID` NOT IN ('".implode("', '", array_keys($content))."'))";
				$clause[] = "`t".$count."`.`packageID` IS NULL";
			}
			$query .= implode(' ', $joins).' WHERE '.implode(' AND ', $clause);
			$dbh = database::getInstance();
			$result = $dbh->query($query);
			if ($result->rowCount) {
				if ($quantities) {
					$row = $result->fetchRow();
					return $row['packageID'];
				} else {
					$packageIDs = array();
					while ($row = $result->fetchRow()) {
						$packageIDs[] = $row['packageID'];
					}
					return $packageIDs;
				}
			} else {
				return false;
			}
		} // function findPackageByContent

		/**
		 *  Retrieve product contents of a package as an array of product records from request or database
		 *  Args: (int) package id, (boolean) record is existing, retrieve from database
		 *  Return: (array) package content (if existing, indexes will be product ids)
		 */
		public static function getPackageContents($packageID, $existing = false) {
			$products = getPost('addProduct');
			$contents = array();
			if ($products && !$existing) {
				// retrieve from request
				assertArray($products);
				$quantities = getPost('addQuantity');
				assertArray($quantities);
				foreach ($products as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($products[$key]);
					} else {
						$contents[$val] = array();
						if (isset($quantities[$key]) && validNumber($quantities[$key])) {
							$contents[$val]['quantity'] = $quantities[$key];
						} else {
							$contents[$val]['quantity'] = 1;
						}
					}
				}
				if ($products) {
					$result = query("SELECT `productID`, `name`, `cost`, `weight`, `availability` FROM `products` WHERE `productID` IN ('".implode("', '", $products)."')");
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$contents[$row['productID']]['name'] = $row['name'];
							$contents[$row['productID']]['cost'] = $row['cost'];
							$contents[$row['productID']]['weight'] = $row['weight'];
							$contents[$row['productID']]['availability'] = $row['availability'];
						}
					}
				}
			} elseif (validNumber($packageID, 'integer') && $packageID) {
				// retrieve from database
				$result = query("SELECT `a`.`quantity` AS `packageQuantity`, `b`.*, `c`.`quantity` AS `inventory` FROM `productToPackage` `a` JOIN `products` `b` USING (`productID`) JOIN `productInventory` `c` USING (`productID`) WHERE `a`.`packageID` = '".$packageID."'");
				if ($result->rowCount) {
					$contents = $result->fetchAll();
				}
			}
			return $contents;
		} // function getPackageContents

		/**
		 *  Update cost and weight for all packages containing a product
		 *  Args: (int) product id
		 *  Return: (boolean) all packages updated successfully
		 */
		public function updateProductPackages($productID) {
			$product = new product($productID);
			if ($product->exists()) {
				$result = query("SELECT `a`.`packageID`, 
									SUM(`c`.`cost` * `b`.`quantity`) AS `cost`, 
									SUM(`c`.`weight` * `b`.`quantity`) AS `weight` 
								FROM `productToPackage` `a` 
								JOIN `productToPackage` `b` USING (`packageID`) 
								JOIN `products` `c` ON (`b`.`productID` = `c`.`productID`) 
								WHERE `a`.`productID` = '".$product->get('productID')."' 
								GROUP BY `a`.`packageID`");
				$error = false;
				if ($result->rowCount > 0) {
					while ($row = $result->fetchRow()) {
						$package = new package($row['packageID']);
						$package->set('cost', $row['cost']);
						$package->set('weight', $row['weight']);
						if (!$package->update()) {
							$error = true;
							addError('There was an error while updating product package '.$package->get('packageID'));
						}
					}
				}
				if ($error) {
					addError('Some package data may be out of date');
					addError('Any failed packages should be checked and updated manually');
					trigger_error('Some product packages have failed to updated for product #'.$productID, E_USER_WARNING);
				}
				return !$error;
			} else {
				addError('Product not found while attempting to update product packages');
				addError('Some package data may be out of date');
				addError('Any package containing the updated product should be checked and updated manually');
				trigger_error('Product not found while attempting to update product packages for product #'.$productID, E_USER_WARNING);
			}
			return false;
		} // function updateProductPackages

		/**
		 *  Search package by skus
		 *  Args: (array) skus
		 *  Return: (array) details of all packages found
		 */
		public static function skuSearch($skus) {
			assertArray($skus);
			$packages = array();
			if ($skus) {
				$search = array();
				foreach ($skus as $index => $sku) {
					$sku = clean($sku, 'alphanum');
					if (!$sku) {
						unset($skus[$index]);
					}
				}
			}
			if ($skus) {
				$dbh = database::getInstance();
				$sql = "SELECT `a`.* FROM `packages` `a` JOIN `packageSiteMap` `b` USING (`packageID`) WHERE `b`.`siteID` = '".systemSettings::get('SITEID')."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND `a`.`sku` IN ('".implode("', '", $skus)."')";
				$result = $dbh->query($sql);
				if ($result->rowCount) {
					// assign a match value according to the number of matches in each package
					while ($row = $result->fetchRow()) {
						$packages[$row['packageID']] = $row;
					}
					$found = array_keys($packages);
					// do not display products from members with inactive payment gateways
					$sql = "SELECT `a`.`packageID` FROM `packages` `a` JOIN `packageSiteMap` `b` ON (`a`.`packageID` = `b`.`packageID`) JOIN `productToPackage` `c` ON (`a`.`packageID` = `c`.`packageID`) JOIN `products` `d` ON (`c`.`productID` = `d`.`productID`) LEFT JOIN `memberGatewayInfo` `e` ON (`d`.`memberID` = `e`.`memberID` AND `e`.`status` != 'active') WHERE `a`.`packageID` IN ('".implode("', '", $found)."') AND `d`.`memberID` != 0 AND `e`.`memberGatewayInfoID` IS NOT NULL GROUP BY `a`.`packageID`";
					$filter = $dbh->query($sql);
					if ($filter->rowCount) {
						while ($row = $filter->fetchRow()) {
							unset($packages[$row['packageID']]);
						}
					}
				}
			}
			return $packages;
		} // function skuSearch

		/**
		 *  Search package by brands
		 *  Args: (array) brands
		 *  Return: (array) details of all packages found
		 */
		public static function brandSearch($brands) {
			assertArray($brands);
			$packages = array();
			if ($brands) {
				$search = array();
				foreach ($brands as $index => $brand) {
					$brand = clean($brand, 'name');
					if ($brand) {
						$brandSearch .= "'".prep($brand)."', ";
					}
				}
				if ($brandSearch) {
					$brandSearch = substr($brandSearch, 0, -2);
				}
			}
			if ($brandSearch) {
				$dbh = database::getInstance();
				$sql = "SELECT `a`.* FROM `packages` `a` JOIN `packageSiteMap` `b` USING (`packageID`) WHERE `b`.`siteID` = '".systemSettings::get('SITEID')."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND `a`.`brand` IN (".$brandSearch.")";
				$result = $dbh->query($sql);
				if ($result->rowCount) {
					// assign a match value according to the number of matches in each package
					while ($row = $result->fetchRow()) {
						$packages[$row['packageID']] = $row;
					}
					$found = array_keys($packages);
					// do not display products from members with inactive payment gateways
					$sql = "SELECT `a`.`packageID` FROM `packages` `a` JOIN `packageSiteMap` `b` ON (`a`.`packageID` = `b`.`packageID`) JOIN `productToPackage` `c` ON (`a`.`packageID` = `c`.`packageID`) JOIN `products` `d` ON (`c`.`productID` = `d`.`productID`) LEFT JOIN `memberGatewayInfo` `e` ON (`d`.`memberID` = `e`.`memberID` AND `e`.`status` != 'active') WHERE `a`.`packageID` IN ('".implode("', '", $found)."') AND `d`.`memberID` != 0 AND `e`.`memberGatewayInfoID` IS NOT NULL GROUP BY `a`.`packageID`";
					$filter = $dbh->query($sql);
					if ($filter->rowCount) {
						while ($row = $filter->fetchRow()) {
							unset($packages[$row['packageID']]);
						}
					}
				}
			}
			return $packages;
		} // function skuSearch

		/**
		 *  Search package by tags
		 *  Args: (array) package tags
		 *  Return: (array) details of all packages found
		 */
		public static function tagSearch($tags) {
			$dbh = database::getInstance();
			assertArray($tags);
			$packages = array();
			$joins = array();
			$wheres = array();
			if ($tags) {
				$key = 0;
				foreach ($tags as $val) {
					$joins[] = '`productTags` `t'.$key.'`';
					$joins[] = '`packageTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`'.($key == 0 ? '' : 'AND `pt'.($key - 1).'`.`packageID` = `pt'.$key.'`.`packageID`').')';
					$wheres[] = "`t".$key."`.`tag` = '".prep(strtoupper($val))."'";
					++$key;
				}
			}
			$sql = "SELECT `a`.* FROM ".(!empty($joins) ? implode(" JOIN ", $joins)." JOIN `packages` `a` ON (`pt0`.`packageID` = `a`.`packageID`)" : "`packages` `a`")." JOIN `packageSiteMap` `b` ON (`a`.`packageID` = `b`.`packageID`) WHERE `a`.`availability` IN ('available', 'alwaysavailable') AND `b`.`siteID` = '".systemSettings::get('SITEID')."'".(!empty($wheres) ? " AND ".implode(" AND ", $wheres) : "")." ORDER BY `a`.`sortWeight` ASC";
			$result = $dbh->query($sql);
			if ($result->rowCount) {
				while ($row = $result->fetchRow()) {
					$packages[$row['packageID']] = $row;
				}
				$found = array_keys($packages);
				// do not display products from members with inactive payment gateways
				$sql = "SELECT `a`.`packageID` FROM `packages` `a` JOIN `packageSiteMap` `b` ON (`a`.`packageID` = `b`.`packageID`) JOIN `productToPackage` `c` ON (`a`.`packageID` = `c`.`packageID`) JOIN `products` `d` ON (`c`.`productID` = `d`.`productID`) LEFT JOIN `memberGatewayInfo` `e` ON (`d`.`memberID` = `e`.`memberID` AND `e`.`status` != 'active') WHERE `a`.`packageID` IN ('".implode("', '", $found)."') AND `d`.`memberID` != 0 AND `e`.`memberGatewayInfoID` IS NOT NULL GROUP BY `a`.`packageID`";
				$filter = $dbh->query($sql);
				if ($filter->rowCount) {
					while ($row = $filter->fetchRow()) {
						unset($packages[$row['packageID']]);
					}
				}
			}
			return $packages;
		} // function tagSearch

		/**
		 *  Search packages by keywords (match in name and description)
		 *  Args: (array) keywords
		 *  Return: (array) details of all packages found
		 */
		public static function keywordSearch($keywords) {
			assertArray($keywords);
			$packages = array();
			if ($keywords) {
				$search = array();
				foreach ($keywords as $index => $keyword) {
					$keyword = clean($keyword, 'alphanum');
					if ($keyword) {
						$search[] = "(`a`.`name` LIKE '%".prep($keyword)."%' OR `a`.`description` LIKE '%".prepDB($keyword)."%' OR `a`.`shortDescription` LIKE '%".prep($keyword)."%')";
					} else {
						unset($keywords[$index]);
					}
				}
				if ($search) {
					$sql = "SELECT `a`.* FROM `packages` `a` JOIN `packageSiteMap` `b` USING (`packageID`) WHERE `b`.`siteID` = '".systemSettings::get('SITEID')."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND ".implode(' AND ', $search);
					$result = query($sql);
					if ($result->rowCount) {
						// assign a match value according to the number of matches in each package
						while ($row = $result->fetchRow()) {
							$matches = 0;
							foreach ($keywords as $key => $val) {
								$matches += preg_match_all('/'.$val.'/', $row['name'], $x);
								$matches += preg_match_all('/'.$val.'/', $row['description'], $x);
								$matches += preg_match_all('/'.$val.'/', $row['shortDescription'], $x);
							}
							$packages[$row['packageID']] = array($row, $matches);
						}
						$found = array_keys($packages);
						// do not display products from members with inactive payment gateways
						$sql = "SELECT `a`.`packageID` FROM `packages` `a` JOIN `packageSiteMap` `b` ON (`a`.`packageID` = `b`.`packageID`) JOIN `productToPackage` `c` ON (`a`.`packageID` = `c`.`packageID`) JOIN `products` `d` ON (`c`.`productID` = `d`.`productID`) LEFT JOIN `memberGatewayInfo` `e` ON (`d`.`memberID` = `e`.`memberID` AND `e`.`status` != 'active') WHERE `a`.`packageID` IN ('".implode("', '", $found)."') AND `d`.`memberID` != 0 AND `e`.`memberGatewayInfoID` IS NOT NULL GROUP BY `a`.`packageID`";
						$filter = $dbh->query($sql);
						if ($filter->rowCount) {
							while ($row = $filter->fetchRow()) {
								unset($packages[$row['packageID']]);
							}
						}
						// sort by best match value descending
						usort($packages, array('productSearch', 'usortByMatches'));
						foreach ($packages as $key => $vals) {
							$packages[$key] = $vals[0];
							$packages[$key][1] = $matches;
						}
					}
				}
			}
			return $packages;
		} // function keywordSearch
	} // class packagesController

?>