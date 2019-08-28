<?

	class package_old extends dataObject {

		// package vars
		protected $packageID = false;
		// array[Item ID] => [(Q)uantity], [(N)ame], [(C)ost], [(W)eight], [(Length)], [(Width)], [(Height)], [(memberID)]
		protected $contents = false;
		protected $totalCost = false;
		protected $totalWeight = false;
		protected $itemCount = false;
		// if set, retrieve package with sensitive data from this time period
		//    via stored history records
		protected $packageDate = false;
		// Indicates whether to treat package as an offer
		//   (final cost is package cost rather than sum of product costs)
		protected $offerID;
		// when using package cost/weight, sum of products cost/weight is stored in these product variables
		protected $productsCost;
		protected $productsWeight;

		/**
		 *  Initiate package variables and database handler
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			$this->resetPackage();
			parent::__construct();
		} // function __construct

		/**
		 *  Prep for object serialization
		 *  Args: none
		 *  Return: (array) object variable names
		 */
		public function __sleep() {
			return parent::__sleep();
		} // function __sleep()

		/**
		 *  Reinitialize database handler
		 *  Args: none
		 *  Return: none
		 */
		public function __wakeup() {
			parent::__wakeup();
		} // function __wakeup

		/**
		 *  Set the offer ID
		 *  Args: (int) offer ID
		 *  Return: none
		 */
		public function setOfferID($offerID) {
			$this->offerID = false;
			if (validNumber($offerID, 'integer') && $offerID) {
				$this->offerID = $offerID;
			}
		} // setOfferID

		/**
		 *  Reset package variables
		 *  Args: none
		 *  Return: none
		 */
		public function resetPackage() {
			$this->packageID   = false;
			$this->contents    = array();
			$this->totalCost   = false;
			$this->totalWeight = false;
			$this->itemCount   = false;
		} // function resetPackage

		/**
		 *  Load package from id
		 *  Args: (str) package id
		 *  Return: none
		 */
		public function loadPackage($id) {
			$this->setPackageID($id);
			$this->getPackageArray();
		} // function loadPackage

		/**
		 *  Set internal var packageID
		 *  Args: (str) package id
		 *  Return: none
		 */
		public function setPackageID($id) {
			if (validNumber($id, 'integer')) {
				$this->packageID = $id;
			}
		} // function setPackageID

		/**
		 *  Set package date
		 *  Args: (date) package date
		 *  Return: none
		 */
		public function setPackageDate($date) {
			// ensure date
			$date = dateToSql($date, true);
			$this->packageDate = $date;
		} // function setPackageDate

		/**
		 *  Generate package contents, and package id if needed
		 *    $package[Item ID] => [(Q)uantity], [(N)ame], [(C)ost], [(W)eight]
		 *  Args: none
		 *  Return: (boolean) package successfuly created
		 */
		public function createPackage() {
			// create package data from product page or shopping cart edit
			$this->createContentArray();
			$this->completeContentArray();
			$errors = getErrors();
			if (!$this->validPackage()) {
				clearErrors();
				foreach ($errors as $error) {
					addError($error);
				}
				$this->resetPackage();
				return false;
			} else {
				return true;
			}
		} // function createPackage

		/**
		 *  Sets up package if package ID is passed in request
		 *  Args: none
		 *  Return: (boolean) package successfully created
		 */
		public function retrievePackage() {
			if (getRequest('p')) {
				$packageID = getRequest('p');
				// if package ID is passed, set up package
				if (validNumber($packageID, 'integer')) {
					// clear package vars if package was previously created
					$this->resetPackage();
					$this->packageID = $packageID;
					// set up package
					$this->getPackageArray();
					if ($this->validPackage()) {
						return true;
					} else {
						$this->resetPackage();
					}
				} else {
					$this->resetPackage();
				}
			}
			return false;
		} // function retrievePackage

		/**
		 *  Retrieves package information of package ID and returns formatted package array
		 *    $packageArray[Item ID] => [(Q)uantity], [(N)ame], [(C)ost], [(W)eight], [(Length)], [(Width)], [(Height)]
		 *  Args: none
		 *  Return: none
		 */
		public function getPackageArray() {
			if ($this->packageID) {
				if ($this->offerID) {
					// retrieve offer data as well
					$query = "SELECT `a`.`packageID`, `b`.`cost` AS `offerPrice` FROM `packages` `a` JOIN `packageToOffer` `b` ON (`b`.`offerID` = '".$this->offerID."' AND `a`.`packageID` = `b`.`packageID`) WHERE `a`.`packageID` = '".prepDB($this->packageID)."'";
				} else {
					$query = "SELECT `packageID` FROM `packages` WHERE `packageID` = '".prepDB($this->packageID)."'";
				}
				$result = $this->dbh->query($query);
			}
			if (isset($result) && $result->rowCount) {
				// get package content
				$row = $result->fetchAssoc();
				$sql = "SELECT `productID`, `quantity` FROM `productToPackage` WHERE `packageID` = '".$row['packageID']."'";
				$result = $this->dbh->query($sql);
				if ($result->rowCount) {
					$this->contents = array();
					while ($itemRow = $result->fetchAssoc()) {
						$this->contents[$itemRow['productID']] = array();
						$this->contents[$itemRow['productID']]['Q'] = $itemRow['quantity'];
					}
				}
				$this->completeContentArray();
				if (!$this->validContents()) {
					$this->resetPackage();
				} else {
					// set cost to offer specified (rather than sum of products)
					if ($this->offerID) {
						$this->productsCost = $row['offerPrice'];
						$this->totalCost = number_format($this->productsCost, 2, '.', '');
						if ($this->packageDate) {
							// retrieve history records from specified package date period
							$historyQuery = "SELECT `a`.`weight`, `b`.`availablePackages` 
												FROM `packagesHistory` `a` 
												JOIN `offersHistory` `b` ON (
													`b`.`offerID` = '".$this->offerID."' 
													AND `b`.`lastModified` <= '".prepDB($this->packageDate)."' 
													AND `b`.`effectiveThrough` >= '".prepDB($this->packageDate)."' 
												) WHERE `a`.`packageID` = '".$this->packageID."' 
												AND `a`.`lastModified` <= '".prepDB($this->packageDate)."'
												AND `a`.`effectiveThrough` >= '".prepDB($this->packageDate)."'";
							$historyResult = $this->dbh->query($historyQuery);
							if ($historyResult->rowCount) {
								$historyRow = $historyResult->fetchAssoc();
								$this->totalWeight = number_format($historyRow['weight'], 2, '.', '');
								$historyPackages = explode(';', $historyRow['availablePackages']);
								foreach ($historyPackages as $val) {
									list($historyPackageID, $historyCost) = split('-', $val);
									if ($historyPackageID == $this->packageID) {
										$this->totalCost = number_format($historyCost, 2, '.', '');
										break;
									}
								}
							}
						}
					}
				}
			} else {
				// nothing found
				$this->resetPackage();
			}
		} // function getPackageArray

		/**
		 *  Create content array from add to cart/edit cart submits, populates content array with preliminary data
		 *    Inputs are prefixed with a command (add/update/remove) followed by an item id
		 *  Args: none
		 *  Return: none
		 */
		private function createContentArray() {
			assertArray($this->contents);
			$removed = array();
			// cycles through all posts to add/update/remove product info from package content array
			foreach ($_REQUEST as $key => $val) {
				if (substr($key, 0, 7) == 'package') {
					$packageID = substr($key, 7, strlen($key) - 7);
					if (!validNumber($packageID, 'integer')) {
						continue;
					}
					// add package
					$content = packagesController::getPackageContents($packageID, true);
					if (!empty($content)) {
						foreach ($content as $product) {
							// add item
							if (!array_key_exists($product['productID'], $this->contents)) {
								$this->contents[$product['productID']]['Q'] = $product['packageQuantity'];
							} else {
								$this->contents[$product['productID']]['Q'] += $product['packageQuantity'];
							}
						}
					}
				} elseif (substr($key, 0, 3) == 'add') {
					$itemID = substr($key, 3, strlen($key) - 3);
					if (!validNumber($itemID, 'integer')) {
						continue;
					}
					// add item
					if (!array_key_exists($itemID, $this->contents)) {
						$this->contents[$itemID]['Q'] = 1;
					} else {
						$this->contents[$itemID]['Q']++;
					}
				} elseif (substr($key, 0, 6) == 'update') {
					$itemID = substr($key, 6, strlen($key) - 6);
					if (!validNumber($itemID, 'integer')) {
						continue;
					}
					// update item
					if (between($val, 1, 1000)) {
						if (!in_array($itemID, $removed)) {
							$this->contents[$itemID]['Q'] = $val;
						}
					} elseif (empty($val)) {
						unset($this->contents[$itemID]);
					}
				} elseif (substr($key, 0, 6) == 'remove') {
					$itemID = substr($key, 6, strlen($key) - 6);
					$itemID = preg_replace('/_[xy]$/', '', $itemID);
					if (!validNumber($itemID, 'integer')) {
						continue;
					}
					// remove item
					unset($this->contents[$itemID]);
					$removed[] = $itemID;
				}
			}
			if (empty($this->contents)) {
				$this->resetPackage();
			}
		} //function createContentArray

		/**
		 *  Generates complete content array, total cost, total weight from current content array
		 *  $content[Item ID] => [(Q)uantity], [(N)ame], [(C)ost], [(Length)], [(Width)], [(Height)], [(memberID)]
		 *  Args: none
		 *  Return: none
		 */
		private function completeContentArray() {
			if ($this->contents) {
				// initialize package variables
				$this->totalCost = 0;
				$this->totalWeight = 0;
				$productIDs = '';
				foreach ($this->contents as $key => $val) {
					$productIDs .= "'".prepDB($key)."', ";
				}
				$productIDs = rtrim($productIDs, ', ');
				// retrive products information
				$query = "SELECT `a`.*, `b`.`company` 
							FROM `products` `a` 
							LEFT JOIN `memberBusinessInfo` `b` USING (`memberID`) 
							WHERE `a`.`productID` IN (".$productIDs.")";
				$result = $this->dbh->query($query);
				// sets package items information
				if ($result->rowCount) {
					$productHistory = array();
					if ($this->packageDate) {
						// retrieve history records from specified package date period
						$historyQuery = "SELECT * 
											FROM `productsHistory`
											WHERE `productID` IN (".$productIDs.")
											AND `lastModified` <= '".prepDB($this->packageDate)."'
											AND `effectiveThrough` >= '".prepDB($this->packageDate)."' 
											AND `lastModified` != `effectiveThrough`";
						$historyResult = $this->dbh->query($historyQuery);
						if ($historyResult->rowCount) {
							while ($row = $historyResult->fetchAssoc()) {
								$productHistory[$row['productID']]['cost'] = $row['cost'];
								$productHistory[$row['productID']]['weight'] = $row['weight'];
								$productHistory[$row['productID']]['length'] = $row['length'];
								$productHistory[$row['productID']]['width'] = $row['width'];
								$productHistory[$row['productID']]['height'] = $row['height'];
								$productHistory[$row['productID']]['memberID'] = $row['memberID'];
							}
						}
					}
					while ($row = $result->fetchAssoc()) {
						$this->contents[$row['productID']]['N'] = $row['name'];
						if (array_key_exists($row['productID'], $productHistory)) {
							$this->contents[$row['productID']]['C'] = $productHistory[$row['productID']]['cost'];
							$this->contents[$row['productID']]['W'] = $productHistory[$row['productID']]['weight'];
							$this->contents[$row['productID']]['Length'] = $productHistory[$row['productID']]['length'];
							$this->contents[$row['productID']]['Width'] = $productHistory[$row['productID']]['width'];
							$this->contents[$row['productID']]['Height'] = $productHistory[$row['productID']]['height'];
							foreach ($row as $field => $value) {
								$this->contents[$row['productID']][$field] = $productHistory[$row['productID']][$field];
							}
						} else {
							$this->contents[$row['productID']]['C'] = $row['cost'];
							$this->contents[$row['productID']]['W'] = $row['weight'];
							$this->contents[$row['productID']]['Length'] = $row['length'];
							$this->contents[$row['productID']]['Width'] = $row['width'];
							$this->contents[$row['productID']]['Height'] = $row['height'];
							$this->contents[$row['productID']]['memberID'] = $row['memberID'];
							foreach ($row as $field => $value) {
								$this->contents[$row['productID']][$field] = $value;
							}
						}
						if ($this->contents[$row['productID']]['Q']) {
							$this->totalCost += $this->contents[$row['productID']]['C'] * $this->contents[$row['productID']]['Q'];
							$this->totalWeight += $this->contents[$row['productID']]['W'] * $this->contents[$row['productID']]['Q'];
						}
						$this->contents[$row['productID']]['company'] = $row['company'] ? $row['company'] : systemSettings::get('SITENAME');
					}
					$this->totalCost = number_format($this->totalCost, 2, '.', '');
					$this->totalWeight = number_format($this->totalWeight, 2, '.', '');
				} else {
					trigger_error('Could not locate package ID(s) '.implode(', ', array_keys($this->contents)), E_USER_WARNING);
					$this->resetPackage();
					return;
				}
				$this->itemCount = 0;
				foreach ($this->contents as $key => $val) {
					if (!isset($val['C']) || !isset($val['Q'])) {
						if (!isset($val['Q'])) {
							// information was found for a product that was not in package
							trigger_error('Information found for a product that was not in package: '.$key, E_USER_WARNING);
						} else {
							// no item information was found
							addError('Product error');
							trigger_error('No item information found for product: '.$key, E_USER_WARNING);
						}
						unset($this->contents[$key]);
						$modified = true;
					}
					$this->itemCount += $val['Q'];
				}
			}
		} // function completeContentArray

		/**
		 *  Logs package into package database assigning a new id to new package combinations
		 *    and retrieving package id to existing packages
		 *  Args: none
		 *  Return: none
		 */
		public function logPackage() {
			// if package array is not empty, enter into db
			if ($this->validContents()) {
				// search for existing product content, if exists, pull id only
				$content = array();
				foreach ($this->contents as $key => $val) {
					$content[$key] = $val['Q'];
				}
				$packageID = packageManager::findPackageByContent($content);
				if ($packageID !== false) {
					$this->packageID = $packageID;
				} else {
					$pm = new packageManager;
					$_POST['addProduct'] = array();
					$_POST['addQuantity'] = array();
					foreach ($this->contents as $itemID => $val) {
						$_POST['addProduct'][] = $itemID;
						$_POST['addQuantity'][] = $val['Q'];
					}
					$_POST['availability'] = 'withheld';
					if ($pm->addPackage()) {
						$this->packageID = $pm->getArrayData('record', 'packageID');
					} else {
						$this->resetPackage();
					}
				}
			} else {
				$this->resetPackage();
			}
		} // function logPackage

		/**
		 *  Validates content array
		 *  Args: none
		 *  Return: (boolean) valid/invalid content array
		 */
		private function validContents() {
			if (!isset($this->contents) || !is_array($this->contents) || empty($this->contents)) {
				return false;
			} else {
				foreach ($this->contents as $key => $vals) {
					if (!isset($vals['Q']) || !isset($vals['N']) || !isset($vals['C'])|| !isset($vals['W'])) {
						addError('Invalid package content');
						trigger_error('Incomplete content array (Products: '.implode(', ', array_keys($this->contents)).') ID: '.$key.', Q: '.$vals['Q'].', N: '.$vals['N'].', C: '.$vals['C'].', W: '.$vals['W'], E_USER_WARNING);
						return false;
					}
				}
			}
			return true;
		} // function validContents

		/**
		 *  Validates package id, contents array, content str, total cost, total weight
		 *  Args: none
		 *  Return: (boolean) valid/invalid package
		 */
		public function validPackage() {
			// total cost and weight may be 0 (promotions effect and other specials)
			$errors = array();
			if (!validNumber($this->totalCost, 'double')) {
				$errors[] = 'Invalid package price';
			}
			if (!validNumber($this->totalWeight, 'double')) {
				$errors[] = 'Invalid package weight';
			}
			if (!$this->validContents()) {
				$errors[] = 'Invalid package';
			}
			if (empty($errors)) {
				return true;
			} else {
				foreach ($errors as $error) {
					addError($error);
				}
				return false;
			}
		} // function validPackage

		/**
		 *  Prepare package contents to be usable for the packer object
		 *  Args: (boolean) separate content by sub orders
		 *  Return: (array) content items
		 */
		public function retrieveItemsForPacking($separateSuborders = false) {
			$items = array();
			if (!empty($this->contents)) {
				foreach ($this->contents as $key => $val) {
					for ($i = 0; $i < $val['Q']; $i++) {
						if ($separateSuborders) {
							if (!isset($items[$val['memberID']])) {
								$items[$val['memberID']] = array();
							}
							$items[$val['memberID']][] = array(array($val['Length'], $val['Width'], $val['Height']), $val['W']);
						} else {
							$items[] = array(array($val['Length'], $val['Width'], $val['Height']), $val['W']);
						}
					}
				}
			}
			return $items;
		} // function retrieveItemsForPacking

	} // class package_old

?>
