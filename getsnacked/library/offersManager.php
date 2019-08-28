<?

	class offersManager extends recordEditor {

		protected $historyTable = 'offersHistory';

		protected $required = array(
			'name',
			'payType',
			'payout',
			'defaultPackage',
			'totalShipments',
			'availability',
			'startDate',
			'endDate'
		);

		protected $default = array(
			'dateAdded' => array('key' => 'dateAdded', 'value' => 'NOW()', 'update' => false),
			'lastModified' => array('key' => 'lastModified', 'value' => 'NOW()', 'update' => true)
		);

		protected $searchFields = array(
			'offerID' => array('type' => 'integer', 'range' => false),
			'name' => array('type' => 'alphanum', 'range' => false),
			'payType' => array('type' => 'alphanum', 'range' => false),
			'payout' => array('type' => 'double', 'range' => true),
			'defaultPackage' => array('type' => 'integer', 'range' => false),
			'totalShipments' => array('type' => 'integer', 'range' => true),
			'availability' => array('type' => 'alphanum', 'range' => false),
			'startDate' => array('type' => 'date', 'range' => true),
			'endDate' => array('type' => 'date', 'range' => true)
		);

		protected $imageDir;

		protected $originalTags = false;
		protected $offerTags = false;

		protected $originalPackages = false;
		protected $offerPackages = false;

		protected $originalCampaigns = false;
		protected $offerCampaigns = false;

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
			$this->imageDir = $this->imageDir.'offers';
			parent::__construct('offers', array('offerID'));
		} // function __construct

		/**
		 *  Return an array of available actions
		 *  Args: none
		 *  Return: (array) actions
		 */
		public function getActions() {
			$actions = array();
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
									$sql = "INSERT IGNORE INTO `offersToTags` (`tagID`, `offerID`, `dateCreated`) VALUES ('".implode("', '".$val."', NOW()), ('", $tagIDs)."', '".$val."', NOW())";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] added to offers (id) '.implode(', ', $updated));
							}
						}
						break;
					case 'removeTags':
						$tagIDs = $this->getTagsFromPost('retrieveIDs');
						if ($tagIDs) {
							foreach ($recordIDs as $val) {
								if (validNumber($val, 'integer')) {
									$sql = "DELETE FROM `offersToTags` WHERE `offerID` = '".$val."' AND `tagID` IN ('".implode("', '", $tagIDs)."')";
									$this->dbh->query($sql);
									$updated[] = $val;
								}
							}
							if ($updated) {
								addSuccess('Tags ['.implode(', ', array_keys($tagIDs)).'] removed from offers (id) '.implode(', ', $updated));
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
								addSuccess('Offers (id) '.implode(', ', $updated).' updated');
							}
						}
						break;
				} // switch ($action)
			} // if (is_array($recordIDs) && $recordIDs)
			if ($updated) {
				return true;
			} else {
				addError('Unable to update offers');
				return false;
			}
		} // function takeAction

		/**
		 *  Load an offer record and associated offer tags, override from parent
		 *  Args: (array) record id values
		 *  Return: (boolean) success
		 */
		public function load($id) {
			$loaded = parent::load($id);
			if ($loaded) {
				$this->originalTags = $this->getOfferTags($this->record['offerID']);
				$this->offerTags = $this->originalTags;
				$this->originalPackages = self::getOfferPackages($this->record['offerID'], true);
				$this->offerPackages = $this->originalPackages;
				$this->originalCampaigns = self::getOfferCampaigns($this->record['offerID'], true);
				$this->offerCampaigns = $this->originalCampaigns;
			}
			return $loaded;
		} // function load

		/**
		 *  Process, validate and add a offer record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function addOffer() {
			$saved = false;
			// validate packages
			$packages = getPost('addPackage');
			$packageArray = array();
			$offerPrices = getPost('offerPrice');
			$priceArray = array();
			$validPackages = true;
			if (is_array($packages) && $packages && is_array($offerPrices) && count($offerPrices) == count($packages)) {
				// validate package ids
				foreach ($packages as $key => $val) {
					if (!isset($offerPrices[$key])) {
						$validPackages = false;
						addError('A package offer price has not been specified');
						continue;
					}
					if (!validNumber($val, 'integer') || !$val) {
						$validPackages = false;
						addError('A linked package is invalid');
						continue;
					}
					if (!in_array($val, $packageArray)) {
						$result = $this->dbh->query("SELECT * FROM `packages` WHERE `packageID` = '".$val."'");
						if (!$result->rowCount) {
							$validPackages = false;
							addError('Package ID '.$val.' cannot be found');
							continue;
						} else {
							// if the package offer cost is invalid, default to the normal package price
							if (!validNumber($offerPrices[$key]) || !$offerPrices[$key]) {
								$row = $result->fetchAssoc();
								$priceArray[$val] = $row['cost'];
							} else {
								$priceArray[$val] = $offerPrices[$key];
							}
							$packageArray[] = $val;
						}
					}
				}
			} else {
				$validPackages = false;
				addError('The offer must have at least one package');
			}
			$defaultPackage = getPost('defaultPackage', 'integer');
			if ($defaultPackage && !in_array($defaultPackage, $packageArray)) {
				addError('The default package should be a package available to the offer');
				$validPackages = false;
			} elseif (!$packageArray) {
				// set to allow any package
				$_POST['defaultPackage'] = 0;
			}
			// validate campaigns
			$campaigns = getPost('addCampaign');
			$campaignArray = array();
			$validCampaigns = true;
			if (is_array($campaigns) && $campaigns) {
				// validate campaign ids
				foreach ($campaigns as $key => $val) {
					if (!validNumber($val, 'integer') || !$val) {
						$validCampaigns = false;
						addError('A linked campaign is invalid');
						continue;
					}
					if (!in_array($val, $campaignArray)) {
						$this->dbh->query("SELECT * FROM `campaigns` WHERE `campaignID` = '".$val."'");
						if (!$this->dbh->rowCount) {
							$validCampaigns = false;
							addError('Campaign ID '.$val.' cannot be found');
							continue;
						} else {
							$campaignArray[] = $val;
						}
					}
				}
			}
			if ($validPackages && $validCampaigns) {
				sort($packageArray);
				sort($campaignArray);
				// construct associated packages string
				$packagesStr = '';
				foreach ($packageArray as $val) {
					// used in the offer record as the package to offer associations string
					$packagesStr .= $val.'-'.number_format($priceArray[$val], 2, '.', '').';';
				}
				$packagesStr = rtrim($packagesStr, ';');
				$saved = $this->addRecord(array('offerID'), $packagesStr, implode(';', $campaignArray));
				if ($saved) {
					$packagesInsertVals = '';
					foreach ($packageArray as $val) {
						// used in the insert into packageToOffer associations table
						$packagesInsertVals .= "('".$val."', '".$this->record['offerID']."', '".$priceArray[$val]."'), ";
					}
					$packagesInsertVals = rtrim($packagesInsertVals, ', ');
					// create packageToOffer associations
					if ($packageArray && $offerPrices) {
						$this->dbh->query("INSERT INTO `packageToOffer` (`packageID`, `offerID`, `cost`) VALUES ".$packagesInsertVals);
					}
					// create campaignToOffer associations
					if ($campaignArray) {
						$campaignsInsertVals = '';
						foreach ($campaignArray as $val) {
							$campaignsInsertVals .= "('".$val."', '".$this->record['offerID']."'), ";
						}
						$campaignsInsertVals = rtrim($campaignsInsertVals, ', ');
						$this->dbh->query("INSERT INTO `campaignToOffer` (`campaignID`, `offerID`) VALUES ".$campaignsInsertVals);
					}
					// create tag associations
					$tags = getPost('offerTags');
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
					$_POST['offerTags'] = $tags;
					$this->takeAction(array($this->record['offerID']), 'addTags');
					$this->originalTags = $this->getOfferTags($this->record['offerID']);
					$this->offerTags = $this->originalTags;
				} else {
					$tags = getPost('offerTags');
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
					$this->offerTags = $tags;
				}
			} else {
				$this->record = array();
				foreach ($this->fields as $key => $val) {
					if ($key != 'offerID') {
						if ($key != 'startDate' && $key != 'endDate') {
							$this->record[$key] = clean(getPost($key));
						} elseif ($dateValue = getPost($key)) {
							$this->record[$key] = dateToSql($dateValue, true);
						}
					}
				}
				$tags = getPost('offerTags');
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
				$this->offerTags = $tags;
			}
			return $saved;
		} // function addOffer

		/**
		 *  Process, validate and add a record from post input, override
		 *    Available packages and campaigns are assumed to be comma separated ids
		 *  Args: (array) fields to ignore, (str) available packages, (str) available campaigns
		 *  Return: (boolean) success
		 */
		public function addRecord($ignore = false, $availablePackages, $availableCampaigns) {
			$this->record = array();
			if (!$ignore || !is_array($ignore)) {
				$ignore = array();
			}
			foreach ($this->fields as $key => $val) {
				if (!in_array($key, $ignore)) {
					if ($key != 'startDate' && $key != 'endDate') {
						$this->record[$key] = clean(getPost($key));
					} elseif ($dateValue = getPost($key)) {
						$this->record[$key] = dateToSql($dateValue, true);
					}
				}
			}
			$this->record['availablePackages'] = $availablePackages;
			$this->record['availableCampaigns'] = $availableCampaigns;
			return $this->save();
		} // function addRecord

		/**
		 *  Process, validate and update an offer record from input
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function updateOffer() {
			if ($this->original) {
				// validate packages
				$packages = getPost('addPackage');
				$packageArray = array();
				$offerPrices = getPost('offerPrice');
				$priceArray = array();
				$validPackages = true;
				if (is_array($packages) && $packages && is_array($offerPrices) && count($offerPrices) == count($packages)) {
					// validate package ids
					foreach ($packages as $key => $val) {
						if (!isset($offerPrices[$key])) {
							$validPackages = false;
							addError('A package offer price has not been specified');
							continue;
						}
						if (!validNumber($val, 'integer') || !$val) {
							$validPackages = false;
							addError('A linked package is invalid');
							continue;
						}
						if (!in_array($val, $packageArray)) {
							$result = $this->dbh->query("SELECT * FROM `packages` WHERE `packageID` = '".$val."'");
							if (!$this->dbh->rowCount) {
								$validPackages = false;
								addError('Package ID '.$val.' cannot be found');
								continue;
							} else {
								// if the package offer cost is invalid, default to the normal package price
								if (!validNumber($offerPrices[$key]) || !$offerPrices[$key]) {
									$row = $result->fetchAssoc();
									$priceArray[$val] = $row['cost'];
								} else {
									$priceArray[$val] = $offerPrices[$key];
								}
								$packageArray[] = $val;
							}
						}
					}
				} else {
					$validPackages = false;
					addError('Offer must have at least one package');
				}
				$defaultPackage = getPost('defaultPackage', 'integer');
				if ($defaultPackage && !in_array($defaultPackage, $packageArray)) {
					addError('Default package should be a package available to the offer');
					$validPackages = false;
				} elseif (!$packageArray) {
					// set to allow any package
					$_POST['defaultPackage'] = 0;
				}
				// validate campaigns
				$campaigns = getPost('addCampaign');
				$campaignArray = array();
				$validCampaigns = true;
				if (is_array($campaigns) && $campaigns) {
					// validate campaign ids
					foreach ($campaigns as $key => $val) {
						if (!validNumber($val, 'integer') || !$val) {
							$validCampaigns = false;
							addError('A linked campaign is invalid');
							continue;
						}
						if (!in_array($val, $campaignArray)) {
							$this->dbh->query("SELECT * FROM `campaigns` WHERE `campaignID` = '".$val."'");
							if (!$this->dbh->rowCount) {
								$validCampaigns = false;
								addError('Campaign ID '.$val.' cannot be found');
								continue;
							} else {
								$campaignArray[] = $val;
							}
						}
					}
				}
				if ($validPackages && $validCampaigns) {
					sort($packageArray);
					// construct associated packages string
					$packagesStr = '';
					foreach ($packageArray as $val) {
						// used in the offer record as the package to offer associations string
						$packagesStr .= $val.'-'.number_format($priceArray[$val], 2, '.', '').';';
					}
					$packagesStr = rtrim($packagesStr, ';');
					if ($packagesStr != $this->original['availablePackages']) {
						$this->record['availablePackages'] = $packagesStr;
						$originalPackages = array_keys($this->originalPackages);
						$addPackages = array_diff($packageArray, $originalPackages);
						$removePackages = array_diff($originalPackages, $packageArray);
						// add and remove packages
						if ($addPackages || $removePackages) {
							if ($addPackages) {
								$insertVals = '';
								foreach ($addPackages as $val) {
									$insertVals .= "('".$val."', '".$this->record['offerID']."', '".$priceArray[$val]."'), ";
								}
								$insertVals = rtrim($insertVals, ', ');
								$this->dbh->query("INSERT INTO `packageToOffer` (`packageID`, `offerID`, `cost`) VALUES ".$insertVals);
							}
							if ($removePackages) {
								$this->dbh->query("DELETE FROM `packageToOffer` WHERE `offerID` = '".$this->record['offerID']."' AND `packageID` IN ('".implode("', '", $removePackages)."')");
							}
						}
						// update any package prices
						foreach ($packageArray as $val) {
							if (!in_array($val, $addPackages)) {
								if ($priceArray[$val] != $this->originalPackages[$val]['offerPrice']) {
									query("UPDATE `packageToOffer` SET `cost` = '".$priceArray[$val]."' WHERE `offerID` = '".$this->record['offerID']."' AND `packageID` = '".$val."'");
								}
							}
						}
					}
					sort($campaignArray);
					$originalCampaigns = array_keys($this->originalCampaigns);
					$addCampaigns = array_diff($campaignArray, $originalCampaigns);
					$removeCampaigns = array_diff($originalCampaigns, $campaignArray);
					if ($addCampaigns || $removeCampaigns) {
						if ($addCampaigns) {
							$insertVals = '';
							foreach ($addCampaigns as $val) {
								$insertVals .= "('".$val."', '".$this->record['offerID']."'), ";
							}
							$insertVals = rtrim($insertVals, ', ');
							$this->dbh->query("INSERT INTO `campaignToOffer` (`campaignID`, `offerID`) VALUES ".$insertVals);
						}
						if ($removeCampaigns) {
							$this->dbh->query("DELETE FROM `campaignToOffer` WHERE `offerID` = '".$this->record['offerID']."' AND `campaignID` IN ('".implode("', '", $removeCampaigns)."')");
						}
						$this->record['availableCampaigns'] = implode(';', $campaignArray);
					}
					$imageChange = isset($_FILES['imageUpload']) && $_FILES['imageUpload']['name'] && $this->uploadImage();
					foreach ($_POST as $key => $val) {
						if ($key != 'offerID' && array_key_exists($key, $this->record)) {
							if ($key != 'startDate' && $key != 'endDate') {
								$this->record[$key] = clean(getPost($key));
							} else {
								$this->record[$key] = dateToSql(getPost($key), true);
							}
						}
					}
					$this->update();
					$tags = $this->getTagsFromPost('retrieveTags');
					$add = array_diff($tags, $this->originalTags);
					if ($add) {
						$_POST['offerTags'] = implode(',', $add);
						$added = $this->takeAction(array($this->record['offerID']), 'addTags');
					} else {
						$added = false;
					}
					$remove = array_diff($this->originalTags, $tags);
					if ($remove) {
						$_POST['offerTags'] = implode(',', $remove);
						$removed = $this->takeAction(array($this->record['offerID']), 'removeTags');
					} else {
						$removed = false;
					}
					if ($added || $removed || $imageChange) {
						$this->originalTags = $this->getOfferTags($this->record['offerID']);
						$this->offerTags = $this->originalTags;
						removeError('Record not updated: no change');
					}
					if (haveErrors()) {
						return false;
					} else {
						return true;
					}
				}
			}
			addError('Failed to update offer');
			return false;
		} // function updateOffer

		/**
		 *  Resize and upload a offer image
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
					if ($image->copyImage($this->imageDir.'/thumbs/', $this->record['offerID'].'_1.gif')) {
						$this->record['image'] = 1;
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
		 *  Get and explode offer tags post request
		 *  Args: (boolean) create non existing tags
		 *  Return: (array) valid offer tags
		 */
		public function getTagsFromPost($mode) {
			$tagIDs = array();
			$tags = getPost('offerTags');
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
								$this->dbh->query("INSERT IGNORE INTO `offerTags` (`tag`, `dateCreated`) VALUES ('".$cleanTag."', NOW())");
								if ($this->dbh->rowCount) {
									$tagIDs[$cleanTag] = $this->dbh->insertID;
								} else {
									$result = $this->dbh->query("SELECT `tagID` FROM `offerTags` WHERE `tag` = '".$cleanTag."'");
									if ($result->rowCount) {
										$tagID = $result->fetchAssoc();
										$tagIDs[$cleanTag] = $tagID['tagID'];
									}
								}
								break;
							case 'retrieveIDs':
								$cleanTag = prepDB(trim(strtoupper($tag)));
								$result = $this->dbh->query("SELECT `tagID` FROM `offerTags` WHERE `tag` = '".$cleanTag."'");
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
		 *  Retrieve tags associated to an offer
		 *  Args: (int) offer id
		 *  Return: (array) offer tags array(tagID => tag)
		 */
		public function getOfferTags($offerID) {
			$tags = array();
			if (validNumber($offerID, 'integer')) {
				$result = $this->dbh->query("SELECT `b`.`tagID`, `b`.`tag` FROM `offersToTags` `a` JOIN `offerTags` `b` ON (`a`.`tagID` = `b`.`tagID`) WHERE `a`.`offerID` = '".$offerID."'");
				if ($result->rowCount) {
					while ($row = $result->fetchAssoc()) {
						$tags[$row['tagID']] = $row['tag'];
					}
				}
			}
			return $tags;
		} // function getOfferTags

		/**
		 *  Retrieve packages from an offer as an array of package records
		 *  Args: (int) offer id, (boolean) retrieve the current associations on record
		 *  Return: (array) offer packages
		 */
		public static function getOfferPackages($offerID, $retrieveFromOffer = false) {
			$postPackages = getPost('addPackage');
			if ($postPackages && !$retrieveFromOffer) {
				assertArray($postPackages);
				$offerPrices = getPost('offerPrice');
				assertArray($offerPrices);
				foreach ($postPackages as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($postPackages[$key]);
					}
				}
				if ($postPackages) {
					$result = query("SELECT `packageID`, `name`, `cost`, `availability` FROM `packages` WHERE `packageID` IN ('".implode("', '", $postPackages)."')");
					$packages = array();
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$packages[$row['packageID']] = array(
								'name' => $row['name'],
								'cost' => $row['cost'],
								'availability' => $row['availability'],
								'offerPrice' => $offerPrices[array_search($row['packageID'], $postPackages)]
							);
						}
					}
				}
			} elseif (validNumber($offerID, 'integer') && $offerID) {
				$result = query("SELECT `b`.`packageID`, `b`.`name`, `b`.`cost`, `b`.`availability`, `a`.`cost` AS `offerPrice` FROM `packageToOffer` `a` JOIN `packages` `b` USING (`packageID`) WHERE `offerID` = '".$offerID."'");
				if ($result->rowCount) {
					$packages = array();
					while ($row = $result->fetchAssoc()) {
						$packages[$row['packageID']] = array(
							'name' => $row['name'],
							'cost' => $row['cost'],
							'availability' => $row['availability'],
							'offerPrice' => $row['offerPrice']
						);
					}
				}
			}
			assertArray($packages);
			return $packages;
		} // function getOfferPackages

		/**
		 *  Retrieve campaigns from an offer as an array of campaign records
		 *  Args: (int) offer id, (boolean) retrieve the current associations on record
		 *  Return: (array) offer campaigns
		 */
		public static function getOfferCampaigns($offerID, $retrieveFromOffer = false) {
			$campaigns = getPost('addCampaign');
			if ($campaigns && !$retrieveFromOffer) {
				assertArray($campaigns);
				foreach ($campaigns as $key => $val) {
					if (!validNumber($val, 'integer')) {
						unset($campaigns[$key]);
					}
				}
				if ($campaigns) {
					$result = query("SELECT `campaignID`, `name`, `type`, `availability` FROM `campaigns` WHERE `campaignID` IN ('".implode("', '", $campaigns)."')");
					$campaigns = array();
					if ($result->rowCount) {
						while ($row = $result->fetchAssoc()) {
							$campaigns[$row['campaignID']] = array(
								'name' => $row['name'],
								'type' => $row['type'],
								'availability' => $row['availability']
							);
						}
					}
				}
			} elseif (validNumber($offerID, 'integer') && $offerID) {
				$result = query("SELECT `b`.`campaignID`, `b`.`name`, `b`.`type`, `b`.`availability` FROM `campaignToOffer` `a` JOIN `campaigns` `b` USING (`campaignID`) WHERE `offerID` = '".$offerID."'");
				if ($result->rowCount) {
					$campaigns = array();
					while ($row = $result->fetchAssoc()) {
						$campaigns[$row['campaignID']] = array(
							'name' => $row['name'],
							'type' => $row['type'],
							'availability' => $row['availability']
						);
					}
				}
			}
			assertArray($campaigns);
			return $campaigns;
		} // function getOfferCampaigns

		/**
		 *  Update an offer package's offer price
		 *  Args: (int) package id
		 *		  (double) offer package price
		 *        (boolean) update only if the current offer package price 
		 *           matches the package price on the previous package record update
		 *  Return: (boolean) updated
		 */
		public function updateOfferPackagePrice($packageID, $price = false, $updateOnMatch = true) {
			if ($this->original && is_array($this->originalPackages) && array_key_exists($packageID, $this->originalPackages)) {
				$_POST['addPackage'] = array();
				$_POST['offerPrice'] = array();
				$_POST['addCampaign'] = array();
				if ($price === false) {
					$sql = "SELECT `a`.`cost` AS `newCost`, `b`.`cost` AS `oldCost` FROM `packagesHistory` `a` JOIN `packagesHistory` `b` ON (`a`.`packageID` = `b`.`packageID` AND `b`.`effectiveThrough` = DATE_SUB(`a`.`lastModified`, INTERVAL 1 SECOND)) WHERE `a`.`packageID` = ".$packageID." AND `a`.`effectiveThrough` = '9999-12-31 23:59:59'";
					$result = query($sql);
					if ($result->rowCount) {
						$row = $result->fetchAssoc();
						if ($updateOnMatch) {
							if ($this->originalPackages[$packageID]['offerPrice'] == $row['oldCost'] && $this->originalPackages[$packageID]['offerPrice'] != $row['newCost']) {
								$this->offerPackages[$packageID]['offerPrice'] = $row['newCost'];
								foreach($this->offerPackages as $key => $val) {
									$_POST['addPackage'][$key] = $key;
									$_POST['offerPrice'][$key] = $val['offerPrice'];
								}
								foreach($this->offerCampaigns as $key => $val) {
									$_POST['addCampaign'][$key] = $key;
								}
								return $this->updateOffer();
							}
						} else {
							if ($this->originalPackages[$packageID]['offerPrice'] != $row['newCost']) {
								$this->offerPackages[$packageID]['offerPrice'] = $row['newCost'];
								foreach($this->offerPackages as $key => $val) {
									$_POST['addPackage'][$key] = $key;
									$_POST['offerPrice'][$key] = $val['offerPrice'];
								}
								foreach($this->offerCampaigns as $key => $val) {
									$_POST['addCampaign'][$key] = $key;
								}
								return $this->updateOffer();
							}
						}
					}
				} elseif (validNumber($price)) {
					if ($this->originalPackages[$packageID]['offerPrice'] != $price) {
						$this->offerPackages[$packageID]['offerPrice'] = $price;
						foreach($this->offerPackages as $key => $val) {
							$_POST['addPackage'][$key] = $key;
							$_POST['offerPrice'][$key] = $val['offerPrice'];
						}
						foreach($this->offerCampaigns as $key => $val) {
							$_POST['addCampaign'][$key] = $key;
						}
						return $this->updateOffer();
					}
				}
			}
			return false;
		} // function recalculatePackagePrice

	} // class offersManager

?>