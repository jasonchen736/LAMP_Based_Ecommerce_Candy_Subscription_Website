<?

	class productsController extends controller {
		// controller for specified table
		protected $table = 'products';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'productID' => array('type' => 'integer', 'range' => false),
			'memberID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'name-search', 'range' => false),
			'sku' => array('type' => 'alphanum-search', 'range' => false),
			'brand' => array('type' => 'name-search', 'range' => false),
			'availability' => array('type' => 'alpha', 'range' => false),
			'cost' => array('type' => 'double', 'range' => true),
			'weight' => array('type' => 'double', 'range' => true),
			'length' => array('type' => 'double', 'range' => true),
			'width' => array('type' => 'double', 'range' => true),
			'height' => array('type' => 'double', 'range' => true),
			'quantity' => array('type' => 'integer', 'range' => true)
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
		 *  Adds additional product search values
		 *    Override
		 *  Args: none
		 *  Return: (array) search values
		 */
		public function getSearchValues() {
			$search = parent::getSearchValues();
			$search['tags'] = array();
			$search['tags']['value'] = getRequest('tags');
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
			$search['select'] = "`a`.*, `b`.`quantity`, `d`.`company`, `c`.`email`";
			$search['tables'][0] = '`'.$this->table.'` `a`';
			$search['tables'][] = 'JOIN `productInventory` `b` ON (`a`.`productID` = `b`.`productID`)';
			$search['tables'][] = 'LEFT JOIN `members` `c` ON (`a`.`memberID` = `c`.`memberID`)';
			$search['tables'][] = 'LEFT JOIN `memberBusinessInfo` `d` ON (`a`.`memberID` = `d`.`memberID`)';
			foreach ($search['where'] as $field => &$val) {
				switch ($field) {
					case 'quantity':
						$val = preg_replace('/^(AND |OR )?/', '$1`b`.', $val);
						break;
					default:
						$val = preg_replace('/^(AND |OR )?/', '$1`a`.', $val);
						break;
				}
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
							$search['tables'][0] = 'JOIN `'.$this->table.'` `a` ON (`pt'.$firstKey.'`.`productID` = `a`.`productID`)';
							$tagTables[] = '`productTags` `t'.$key.'`';
							$tagTables[] = 'JOIN `productTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`)';
						} else {
							$tagTables[] = 'JOIN `productTagMap` `pt'.$key.'` ON (`pt'.$firstKey.'`.`productID` = `pt'.$key.'`.`productID`)';
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
			if (!empty($search['where'])) {
				reset($search['where']);
				$key = key($search['where']);
				$search['where'][$key] = preg_replace('/^(AND|OR) /', '', $search['where'][$key]);
			}
			$search['order'][] = '`a`.`productID` ASC';
			return $search;
		} // function getSearchComponents

		/**
		 *  Read image inputs and perform adds/updates/removes
		 *  Args: (int) product id
		 *  Return: (mixed) boolean for process success, null if no change detected
		 */
		public static function processImages($productID) {
			$product = new product($productID);
			if ($product->exists()) {
				$productID = $product->get('productID');
				assertArray($_FILES['productImages']);
				assertArray($_FILES['productImages']['name']);
				assertArray($_FILES['productImages']['type']);
				assertArray($_FILES['productImages']['tmp_name']);
				assertArray($_FILES['productImages']['error']);
				assertArray($_FILES['productImages']['size']);
				$small = $product->get('imagesSmall');
				$medium = $product->get('imagesMedium');
				$large = $product->get('imagesLarge');
				$productImagesSizes = getPost('productImagesSizes');
				$productImagesExistingSize = getPost('productImagesExistingSize');
				$productImagesExistingIndex = getPost('productImagesExistingIndex');
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
				foreach ($_FILES['productImages']['name'] as $key => $val) {
					if (isset($productImagesSizes[$key]) && array_key_exists($productImagesSizes[$key], $process)) {
						$existingSize = isset($productImagesExistingSize[$key]) && array_key_exists($productImagesExistingSize[$key], $process) ? $productImagesExistingSize[$key] : false;
						if ($existingSize) {
							$existingIndex = isset($productImagesExistingIndex[$key]) && between($productImagesExistingIndex[$key], 1, $$existingSize) ? $productImagesExistingIndex[$key] : false;
						} else {
							$existingIndex = false;
						}
						if ($existingSize && $existingIndex) {
							$existing = array($existingSize, $existingIndex);
						} else {
							$existing = false;
						}
						if ($existing === false) {
							if (!empty($_FILES['productImages']['tmp_name'][$key])) {
								$process[$productImagesSizes[$key]][] = array(
									$key,
									$existing
								);
								$imageChange = true;
							}
						} else {
							$process[$productImagesSizes[$key]][] = array(
								$key,
								$existing
							);
							if ($existing[0] != $productImagesSizes[$key] || $existing[1] != count($process[$productImagesSizes[$key]]) || !empty($_FILES['productImages']['tmp_name'][$key])) {
								$imageChange = true;
							}
						}
						$remove[$productImagesSizes[$key]][count($process[$productImagesSizes[$key]])] = false;
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
								if (empty($_FILES['productImages']['tmp_name'][$imageData[0]])) {
									// different size or position
									if ($imageData[1][0] != $size || $imageData[1][1] != $imageIndex) {
										$fileData = array(
											'name' => $_FILES['productImages']['name'][$imageData[0]],
											'type' => $_FILES['productImages']['type'][$imageData[0]],
											'tmp_name' => $_FILES['productImages']['tmp_name'][$imageData[0]],
											'error' => $_FILES['productImages']['error'][$imageData[0]],
											'size' => $_FILES['productImages']['size'][$imageData[0]]
										);
										$image = new image($productID.'_'.$imageData[1][1].'.gif', 'file', $imageDir.'/products/'.$imageData[1][0].'/');
										if (!$image->copyImage($imageDir.'/products/'.$imageData[1][0].'/', $productID.'_'.$imageData[1][1].'_swp.gif')) {
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
								'name' => $_FILES['productImages']['name'][$imageData[0]],
								'type' => $_FILES['productImages']['type'][$imageData[0]],
								'tmp_name' => $_FILES['productImages']['tmp_name'][$imageData[0]],
								'error' => $_FILES['productImages']['error'][$imageData[0]],
								'size' => $_FILES['productImages']['size'][$imageData[0]]
							);
							$move = false;
							if ($imageData[1] === false || !empty($fileData['tmp_name'])) {
								$image = new image($fileData, 'param');
							} else {
								if ($size != $imageData[1][0] || $imageIndex != $imageData[1][1]) {
									$move = true;
									$image = new image($productID.'_'.$imageData[1][1].'_swp.gif', 'file', $imageDir.'/products/'.$imageData[1][0].'/');
								} else {
									$image = false;
								}
							}
							if ($image !== false) {
								if (!$image->copyImage($imageDir.'/products/'.$size.'/', $productID.'_'.$imageIndex.'.gif')) {
									addError('There was an error while saving an image');
									return false;
								} elseif ($move) {
									if (!unlink($imageDir.'products/'.$imageData[1][0].'/'.$productID.'_'.$imageData[1][1].'_swp.gif')) {
										addError('There was an error while removing a swap image');
									}
								}
							}
						}
					}
					foreach ($remove as $size => $image) {
						foreach ($image as $index => $delete) {
							if ($delete) {
								$file = $imageDir.'products/'.$size.'/'.$productID.'_'.$index.'.gif';
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
					$product->set('imagesSmall', $newSmall);
					$product->set('imagesMedium', $newMedium);
					$product->set('imagesLarge', $newLarge);
					return $product->update();
				} else {
					return NULL;
				}
				return false;
			} else {
				addError('Product was not found while trying to process images');
			}
		} // function processImages

		/**
		 *  Search products by skus
		 *  Args: (array) skus
		 *  Return: (array) details of all packages found
		 */
		public static function skuSearch($skus) {
			assertArray($skus);
			$products = array();
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
				$sql = "SELECT `a`.* FROM `products` `a` JOIN `productSiteMap` `b` USING (`productID`) LEFT JOIN `memberGatewayInfo` `c` ON (`a`.`memberID` = `c`.`memberID`) WHERE `b`.`siteID` = '".systemSettings::get('SITEID')."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND `a`.`sku` IN ('".implode("', '", $skus)."') AND (`c`.`status` = 'active' OR `a`.`memberID` = 0) ORDER BY `a`.`sortWeight` ASC";
				$result = query($sql);
				if ($result->rowCount) {
					// assign a match value according to the number of matches in each package
					while ($row = $result->fetchRow()) {
						$products[$row['productID']] = $row;
					}
				}
			}
			return $products;
		} // function skuSearch

		/**
		 *  Search products by brands
		 *  Args: (array) brands
		 *  Return: (array) details of all packages found
		 */
		public static function brandSearch($brands) {
			assertArray($brands);
			$products = array();
			$brandSearch = '';
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
				$sql = "SELECT `a`.* FROM `products` `a` JOIN `productSiteMap` `b` USING (`productID`) LEFT JOIN `memberGatewayInfo` `c` ON (`a`.`memberID` = `c`.`memberID`) WHERE `b`.`siteID` = '".systemSettings::get('SITEID')."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND `a`.`brand` IN (".$brandSearch.") AND (`c`.`status` = 'active' OR `a`.`memberID` = 0) ORDER BY `a`.`sortWeight` ASC";
				$result = query($sql);
				if ($result->rowCount) {
					// assign a match value according to the number of matches in each package
					while ($row = $result->fetchRow()) {
						$products[$row['productID']] = $row;
					}
				}
			}
			return $products;
		} // function brandSearch

		/**
		 *  Search products by tags
		 *  Args: (array) product tags
		 *  Return: (array) details of all products found
		 */
		public static function tagSearch($tags) {
			assertArray($tags);
			$products = array();
			$joins = array();
			$wheres = array();
			if ($tags) {
				$key = 0;
				foreach ($tags as $val) {
					$joins[] = '`productTags` `t'.$key.'`';
					$joins[] = '`productTagMap` `pt'.$key.'` ON (`t'.$key.'`.`tagID` = `pt'.$key.'`.`tagID`'.($key == 0 ? '' : 'AND `pt'.($key - 1).'`.`productID` = `pt'.$key.'`.`productID`').')';
					$wheres[] = "`t".$key."`.`tag` = '".prep(strtoupper($val))."'";
					++$key;
				}
			}
			$sql = "SELECT `a`.* FROM ".(!empty($joins) ? implode(" JOIN ", $joins)." JOIN `products` `a` ON (`pt0`.`productID` = `a`.`productID`)" : "`products` `a`")." JOIN `productSiteMap` `b` ON (`a`.`productID` = `b`.`productID`) LEFT JOIN `memberGatewayInfo` `c` ON (`a`.`memberID` = `c`.`memberID`) WHERE `a`.`availability` IN ('available', 'alwaysavailable') AND `b`.`siteID` = '".systemSettings::get('SITEID')."' AND (`c`.`status` = 'active' OR `a`.`memberID` = 0)".(!empty($wheres) ? " AND ".implode(" AND ", $wheres) : "")." ORDER BY `a`.`sortWeight` ASC";
			$result = query($sql);
			if ($result->rowCount) {
				$products = $result->fetchAll();
			}
			return $products;
		} // function tagSearch

		/**
		 *  Search products by keywords (match in name and description)
		 *  Args: (array) keywords
		 *  Return: (array) details of all products found
		 */
		public static function keywordSearch($keywords) {
			assertArray($keywords);
			$products = array();
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
					$sql = "SELECT `a`.* FROM `products` `a` JOIN `productSiteMap` `b` USING (`productID`) LEFT JOIN `memberGatewayInfo` `c` ON (`a`.`memberID` = `c`.`memberID`) WHERE `b`.`siteID` = '".prep(systemSettings::get('SITEID'))."' AND `a`.`availability` IN ('available', 'alwaysavailable') AND (`c`.`status` = 'active' OR `a`.`memberID` = 0) AND ".implode(' AND ', $search);
					$result = query($sql);
					if ($result->rowCount) {
						// find number of matches, sort by most matches descending
						while ($row = $result->fetchRow()) {
							$matches = 0;
							foreach ($keywords as $key => $val) {
								$matches += preg_match_all('/'.$val.'/', $row['name'], $x);
								$matches += preg_match_all('/'.$val.'/', $row['description'], $x);
								$matches += preg_match_all('/'.$val.'/', $row['shortDescription'], $x);
							}
							$products[] = array($row, $matches);
						}
						usort($products, array('productSearch', 'usortByMatches'));
						foreach ($products as $key => $vals) {
							$products[$key] = $vals[0];
							$products[$key][1] = $matches;
						}
					}
				}
			}
			return $products;
		} // function keywordSearch

		/**
		 *  Check if a sku value exists
		 *  Args: (str) sku
		 *  Return: (boolean) is duplicate
		 */
		public static function isDuplicateSKU($sku) {
			$sku = clean($sku, 'alphanum');
			$sql = "SELECT `sku` FROM `products` WHERE `sku` = '".prep($sku)."' UNION SELECT `sku` FROM `packages` WHERE `sku` = '".prep($sku)."'";
			$result = query($sql);
			if ($result->rowCount > 0) {
				return true;
			} else {
				return false;
			}
		} // function isDuplicateSKU

		/**
		 *  Retrieve all product tags sorted alphabetically
		 *    this should be a temporary function
		 *  Args: none
		 *  Return: (array) product tags
		 */
		public static function getProductTags() {
			$dbh = database::getInstance();
			$result = $dbh->query("SELECT `tag` FROM `productTags` ORDER BY `tag`");
			$tags = array();
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$tags[$row['tag']] = $row['tag'];
				}
			}
			return $tags;
		} // function getProductTags
	} // class productsController

?>