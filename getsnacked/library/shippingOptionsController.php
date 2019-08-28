<?

	class shippingOptionsController extends controller {
		// controller for specified table
		protected $table = 'shippingOptions';
		// fields available to search: array(field name => array(type, range))
		protected $searchFields = array(
			'shippingOptionID' => array('type' => 'integer', 'range' => false),
			'provider' => array('type' => 'alphanum-search', 'range' => false),
			'name' => array('type' => 'alphanum', 'range' => false),
			'rate' => array('type' => 'double', 'range' => true),
			'rateType' => array('type' => 'alphanum', 'range' => false),
			'status' => array('type' => 'alphanum', 'range' => false),
			'rule' => array('type' => 'alphanum', 'range' => false),
			'dateAdded' => array('type' => 'date', 'range' => true),
			'lastModified' => array('type' => 'date', 'range' => true)
		);

		/**
		 *  Return an array of quick update options available to the admin overview page
		 *  Args: none
		 *  Return: (array) quick actions
		 */
		public static function getQuickUpdateOptions() {
			$options = array(
				'Activate' => 'Activate',
				'Deactivate' => 'Deactivate'
			);
			return $options;
		} // function getQuickUpdateOptions

		/**
		 *  Check whether a shipping option is valid
		 *  Args: (int) shipping option
		 *  Return: (boolean) valid option
		 */
		public static function validOption($shippingOption) {
			$shippingOption = clean($shippingOption, 'integer');
			if ($shippingOption) {
				$result = query("SELECT `shippingOptionID` FROM `shippingOptions` WHERE `shippingOptionID` = '".$shippingOption."' AND `status` = 'active'");
				if ($result->rowCount) {
					return true;
				}
			}
			return false;
		} // function validOption

		/**
		 *  Retrieve available shipping options
		 *  Returns array(option id => option name)
		 *  Args: (address) shipping address object, (package) package object, (str) shipping provider
		 *  Return: (array) shipping options
		 */
		public static function getAvailableShippingOptions($address, $package, $provider = false) {
			$options = array();
			$where = array();
			$where[] = "`a`.`status` = 'active'";
			$where[] = "`c`.`siteID` = '".systemSettings::get('SITEID')."'";
			switch ($provider) {
				case 'ups':
				case 'none':
					$where[] = "`a`.`provider` = '".$provider."'";
					break;
				default:
					break;
			}
			$sql = "SELECT `a`.`shippingOptionID`, `a`.`name`, `a`.`rule`, `b`.`city`, `b`.`state`, `b`.`postal`, `b`.`country`, `b`.`weightCondition`, `b`.`weightValue`, `b`.`packageCondition`, `b`.`packageValue`, `b`.`costCondition`, `b`.`costValue` FROM `shippingOptions` `a` LEFT JOIN `shippingOptionRules` `b` USING (`shippingOptionID`) JOIN `shippingOptionSiteMap` `c` USING (`shippingOptionID`) WHERE ".implode(' AND ', $where);
			$result = query($sql);
			if ($result->rowCount > 0) {
				$itemCount = $package->get('itemCount');
				$totalCost = $package->get('totalCost');
				$packageWeight = $package->get('totalWeight');
				// begin packing items
				$items = $package->retrieveItemsForPacking();
				$containers = shippingContainersController::getAvailableContainers();
				packer::packItems($items, $containers);
				$packed = packer::$packed;
				$packages = packer::$packages;
				$totalPackages = count($packages);
				if (count($packed) != $itemCount) {
					// packing count error
					trigger_error('Packed items count does not match package items count', E_USER_WARNING);
				}
				$totalWeight = 0;
				foreach ($packages as $val) {
					$totalWeight += $val['weight'];
				}
				if ($totalWeight != $packageWeight) {
					// packing weight error
					trigger_error('Packed weight does not match package weight', E_USER_WARNING);
				}
				$city = strtoupper($address->getArrayData('form', 'city'));
				$state = strtoupper($address->getArrayData('form', 'state'));
				$province = strtoupper($address->getArrayData('form', 'province'));
				$postal = strtoupper($address->getArrayData('form', 'postal'));
				$country = strtoupper($address->getArrayData('form', 'country'));
				while ($row = $result->fetchRow()) {
					switch ($row['rule']) {
						case 'allow':
						case 'block':
							$match = false;
							$eval = array();
							if ($row['city']) {
								$eval[] = '($city == $row["city"])';
							}
							if ($row['state']) {
								$eval[] = '($state == $row["state"] || $province == $row["state"])';
							}
							if ($row['postal']) {
								$eval[] = '($postal == $row["postal"])';
							}
							if ($row['country']) {
								$eval[] = '($country == $row["country"])';
							}
							if ($row['weightCondition']) {
								switch ($row['weightCondition']) {
									case 'equal':
										$eval[] = '($totalWeight == $row["weightValue"])';
										break;
									case 'greater':
										$eval[] = '($totalWeight > $row["weightValue"])';
										break;
									case 'gte':
										$eval[] = '($totalWeight >= $row["weightValue"])';
										break;
									case 'less':
										$eval[] = '($totalWeight < $row["weightValue"])';
										break;
									case 'lte':
										$eval[] = '($totalWeight <= $row["weightValue"])';
										break;
									default:
										$eval[] = 'false';
										break;
								}
							}
							if ($row['packageCondition']) {
								switch ($row['packageCondition']) {
									case 'equal':
										$eval[] = '($totalPackages == $row["packageValue"])';
										break;
									case 'greater':
										$eval[] = '($totalPackages > $row["packageValue"])';
										break;
									case 'gte':
										$eval[] = '($totalPackages >= $row["packageValue"])';
										break;
									case 'less':
										$eval[] = '($totalPackages < $row["packageValue"])';
										break;
									case 'lte':
										$eval[] = '($totalPackages <= $row["packageValue"])';
										break;
									default:
										$eval[] = 'false';
										break;
								}
							}
							if ($row['costCondition']) {
								switch ($row['costCondition']) {
									case 'equal':
										$eval[] = '($totalCost == $row["costValue"])';
										break;
									case 'greater':
										$eval[] = '($totalCost > $row["costValue"])';
										break;
									case 'gte':
										$eval[] = '($totalCost >= $row["costValue"])';
										break;
									case 'less':
										$eval[] = '($totalCost < $row["costValue"])';
										break;
									case 'lte':
										$eval[] = '($totalCost <= $row["costValue"])';
										break;
									default:
										$eval[] = 'false';
										break;
								}
							}
							if (!empty($eval)) {
								eval('$match = '.implode(' && ', $eval).';');
							}
							if ($row['rule'] == 'allow') {
								// allow matches
								if ($match) {
									$options[$row['shippingOptionID']] = $row['name'];
								} elseif (isset($options[$row['shippingOptionID']])) {
									unset($options[$row['shippingOptionID']]);
								}
							} else {
								// allow non matches
								if (!$match) {
									$options[$row['shippingOptionID']] = $row['name'];
								} elseif (isset($options[$row['shippingOptionID']])) {
									unset($options[$row['shippingOptionID']]);
								}
							}
							break;
						case 'none':
						default:
							$options[$row['shippingOptionID']] = $row['name'];
							break;
					}
				}
			}
			return $options;
		} // function getAvailableShippingOptions

		/**
		 *  Generate cost of shipping
		 *  Args: (int) shipping option, (address) shipping address object, (package) package object
		 *  Return: (mixed) false if invalid, cost if successful
		 */
		public static function generateShippingCost($shippingOptionID, $address, $package) {
			$shippingOptionID = clean($shippingOptionID, 'integer');
			$hasError = false;
			if (!$shippingOptionID) {
				addError('Invalid shipping method');
				$hasError = true;
			} else {
				$shippingOption = new shippingOption($shippingOptionID);
				if (!$shippingOption->exists() || $shippingOption->get('status') != 'active') {
					addError('Invalid shipping method');
					$hasError = true;
				}
				$useOptions = shippingOptionsController::getImposedOptions();
				if ($useOptions['domestic'] === false) {
					$useOptions['domestic'] = $shippingOption;
				}
				if ($useOptions['international'] === false) {
					$useOptions['international'] = $shippingOption;
				}
			}
			$state = clean($address->getArrayData('form', 'state'), 'alphanum');
			if (!$state) {
				addError('Invalid shipping address');
				$hasError = true;
			}
			$postal = clean($address->getArrayData('form', 'postal'), 'alphanum');
			if (!$postal) {
				addError('Invalid shipping address');
				$hasError = true;
			}
			$country = clean($address->getArrayData('form', 'country'), 'alphanum');
			if (!$country) {
				addError('Invalid shipping address');
				$hasError = true;
			}
			$packageWeight = $package->get('totalWeight');
			if (!$packageWeight) {
				addError('Shopping cart error');
				$hasError = true;
			}
			$itemCount = $package->get('itemCount');
			if (!$itemCount) {
				addError('Shopping cart is empty');
				$hasError = true;
			}
			// begin packing items
			$items = $package->retrieveItemsForPacking(true);
			$packed = array();
			$packages = array();
			$itemsPacked = 0;
			$totalWeight = 0;
			foreach ($items as $memberID => $orderItems) {
				$containers = shippingContainersController::getAvailableContainers($memberID);
				packer::packItems($orderItems, $containers);
				$packed[$memberID] = packer::$packed;
				$packages[$memberID] = packer::$packages;
				$itemsPacked += count($packed[$memberID]);
				foreach ($packages[$memberID] as $val) {
					$totalWeight += $val['weight'];
				}
			}
			if ($itemsPacked != $itemCount) {
				// packing count error
				trigger_error('Packed items count does not match package items count', E_USER_WARNING);
				addError('There was an error in calculating the shipping rate');
				$hasError = true;
			}
			if (number_format($totalWeight, 2, '.', '') != $packageWeight) {
				// packing weight error
				trigger_error('Packed weight does not match package weight', E_USER_WARNING);
				addError('There was an error in calculating the shipping rate');
				$hasError = true;
			}
			if (!$hasError) {
				$rates = array();
				foreach ($packages as $memberID => $shipment) {
					$shippingOrigin = memberShippingLocation::retrieveMemberShippingLocation($memberID);
					if ($shippingOrigin->exists()) {
						$shippingState = $shippingOrigin->get('state');
						$shippingPostal = $shippingOrigin->get('postal');
						$shippingCountry = $shippingOrigin->get('country');
					} else {
						$shippingState = systemSettings::get('SHIPPINGFROMSTATE');
						$shippingPostal = systemSettings::get('SHIPPINGFROMPOSTAL');
						$shippingCountry = systemSettings::get('SHIPPINGFROMCOUNTRY');
					}
					$shippingCategory = $country == $shippingCountry ? 'domestic' : 'international';
					$shippingOption = $useOptions[$shippingCategory];
					$provider = $shippingOption->get('provider');
					switch ($provider) {
						case 'ups':
							if (systemSettings::get('UPS')) {
								ups::setAccountNumber(systemSettings::get('UPSACCOUNTNUMBER'));
								ups::setAccessNumber(systemSettings::get('UPSACCESSNUMBER'));
								ups::setUserName(systemSettings::get('UPSUSERNAME'));
								ups::setPassword(systemSettings::get('UPSPASSWORD'));
								ups::setService($shippingOption->get('externalValue'));
								ups::setDestinationPostalCode($postal);
								ups::setDestinationCountryCode($country);
								ups::setOriginPostalCode($shippingPostal);
								ups::setOriginCountryCode($shippingCountry);
								$rate = ups::getRate($shipment);
								if (validNumber($rate, 'double')) {
									$modifier = $shippingOption->get('modifier');
									$modifierType = $shippingOption->get('modifierType');
									if ($modifier && $modifierType) {
										switch ($modifierType) {
											case 'percentup':
												$rate += $rate * $modifier / 100;
												break;
											case 'percentdown':
												$rate -= $rate * $modifier / 100;
												break;
											case 'flatup':
												$rate += $modifier;
												break;
											case 'flatdown':
												$rate -= $modifier;
												break;
											case 'none':
											default:
												break;
										}
									}
									$rates[$memberID] = $rate;
								} else {
									$rates[$memberID] = false;
									trigger_error('Rate quote was invalid for shipping option '.$shippingOption->get('shippingOptionID').': '.$rate, E_USER_WARNING);
									addError('There was an error in calculating the shipping rate');
								}
							} else {
								$rates[$memberID] = false;
								addError('Shipping option is not available');
							}
							break;
						case 'fedex':
							if (systemSettings::get('FEDEX')) {
								fedex::setKey(systemSettings::get('FEDEXKEY'));
								fedex::setPassword(systemSettings::get('FEDEXPASSWORD'));
								fedex::setAccountNumber(systemSettings::get('FEDEXACCOUNTNUMBER'));
								fedex::setMeterNumber(systemSettings::get('FEDEXMETERNUMBER'));
								fedex::setService($shippingOption->get('externalValue'));
								fedex::setOriginStateOrProvinceCode($shippingState);
								fedex::setOriginPostalCode($shippingPostal);
								fedex::setOriginCountryCode($shippingCountry);
								fedex::setDestStateOrProvinceCode($state);
								fedex::setDestPostalCode($postal);
								fedex::setDestCountryCode($country);
								$rate = fedex::getRate($shipment);
								if (validNumber($rate, 'double')) {
									$modifier = $shippingOption->get('modifier');
									$modifierType = $shippingOption->get('modifierType');
									if ($modifier && $modifierType) {
										switch ($modifierType) {
											case 'percentup':
												$rate += $rate * $modifier / 100;
												break;
											case 'percentdown':
												$rate -= $rate * $modifier / 100;
												break;
											case 'flatup':
												$rate += $modifier;
												break;
											case 'flatdown':
												$rate -= $modifier;
												break;
											case 'none':
											default:
												break;
										}
									}
									$rates[$memberID] = $rate;
								} else {
									$rates[$memberID] = false;
									trigger_error('Rate quote was invalid for shipping option '.$shippingOption->get('shippingOptionID').': '.$rate, E_USER_WARNING);
									addError('There was an error in calculating the shipping rate');
								}
							} else {
								$rates[$memberID] = false;
								addError('Shipping option is not available');
							}
							break;
						case 'none':
						default:
							$rate = $shippingOption->get('rate');
							switch ($shippingOption->get('rateType')) {
								case 'flat':
									$finalRate = $rate;
									break;
								case 'per package':
									$finalRate = $rate * count($shipment);
									break;
								case 'per pound':
									$finalRate = $totalWeight * $rate;
									break;
								default:
									trigger_error('Invalid rate type for shipping option '.$shippingOption->get('shippingOptionID'), E_USER_WARNING);
									addError('There was an error in calculating the shipping rate');
									$finalRate = false;
									break;
							}
							$rates[$memberID] = $finalRate;
							break;
					}
				}
				return $rates;
			}
			return false;
		} // function generateShippingCost

		/**
		 *  Handles rule add/remove actions for a shipping option
		 *  Require rules array in the format: array(city, state, postal, country, weight condtion, weight value, package condition, package value, action)
		 *  Args: (int) shipping option id, (array) array of rule values and actions
		 *  Return: none
		 */
		public static function assertRules($shippingOptionID, $rules) {
			$dbh = database::getInstance();
			$results = array();
			foreach ($rules as $key => $val) {
				if ($val['action'] == 'add') {
					$sql = "INSERT INTO `shippingOptionRules` (`shippingOptionID`, `city`, `state`, `postal`, `country`, `weightCondition`, `weightValue`, `packageCondition`, `packageValue`, `costCondition`, `costValue`) VALUES ('".prep($shippingOptionID)."', '".prep(strtoupper($val['city']))."', '".prep(strtoupper($val['state']))."', '".prep(strtoupper($val['postal']))."', '".prep(strtoupper($val['country']))."', '".prep($val['weightCondition'])."', '".prep($val['weightValue'])."', '".prep($val['packageCondition'])."', '".prep($val['packageValue'])."', '".prep($val['costCondition'])."', '".prep($val['costValue'])."')";
					$result = $dbh->query($sql);
					if ($result->rowCount) {
						$val['success'] = true;
					} else {
						$val['success'] = false;
					}
				} elseif ($val['action'] == 'remove' && isset($val['shippingOptionRuleID'])) {
					$sql = "DELETE FROM `shippingOptionRules` WHERE `shippingOptionRuleID` = '".prep($val['shippingOptionRuleID'])."'";
					$result = $dbh->query($sql);
					if ($result->rowCount) {
						$val['success'] = true;
					} else {
						$val['success'] = false;
					}
				} else {
					$val['success'] = false;
				}
				$results[] = $val;
			}
			return $results;
		} // function assertRules

		/**
		 *  Compares and set actions for rules concerning a shipping option
		 *  Returns rules array in the format: array(city, state, postal, country, weight condtion, weight value, package condition, package value, action)
		 *  Args: (int) shipping option id, (array) option rules
		 *  Return: (array) array of rule values and actions
		 */
		public static function compareRules($shippingOptionID, $rules) {
			$ruleDiffs = array();
			$shippingOption = new shippingOption($shippingOptionID);
			if ($shippingOption->exists()) {
				$existingRules = $shippingOption->getShippingOptionRules();
				// remove rules that have not changed
				foreach ($existingRules as $key => $val) {
					foreach ($rules as $index => $vals) {
						if (
							$val['city'] == $vals['city'] &&
							$val['state'] == $vals['state'] &&
							$val['postal'] == $vals['postal'] &&
							$val['country'] == $vals['country'] &&
							$val['weightCondition'] == $vals['weightCondition'] &&
							$val['weightValue'] == $vals['weightValue'] &&
							$val['packageCondition'] == $vals['packageCondition'] &&
							$val['packageValue'] == $vals['packageValue'] &&
							$val['costCondition'] == $vals['costCondition'] &&
							$val['costValue'] == $vals['costValue']
						) {
							if (isset($existingRules[$key])) {
								unset($existingRules[$key]);
							}
							unset($rules[$index]);
						}
					}
				}
				// new rules
				foreach ($rules as $val) {
					$val['action'] = 'add';
					$ruleDiffs[] = $val;
				}
				// removed rules
				foreach ($existingRules as $val) {
					$val['action'] = 'remove';
					$ruleDiffs[] = $val;
				}
			}
			return $ruleDiffs;
		} // function compareRules

		/**
		 *  Retrieve imposed shipping options
		 *  Args: none
		 *  Return: (array) imposed options
		 */
		public static function getImposedOptions() {
			$options = array(
				'domestic' => false,
				'international' => false
			);
			$sql = "SELECT `a`.`shippingOptionID`, `a`.`imposedOn` FROM `shippingOptionsImposed` `a` JOIN `shippingOptionsImposedSiteMap` `b` USING (`shippingOptionsImposedID`) WHERE `b`.`siteID` = '".prep(systemSettings::get('SITEID'))."' AND `a`.`imposedOn` != ''";
			$result = query($sql);
			while ($row = $result->fetchRow()) {
				$shippingOption = new shippingOption($row['shippingOptionID']);
				if ($shippingOption->exists()) {
					switch ($row['imposedOn']) {
						case 'domestic':
						case 'international':
							$options[$row['imposedOn']] = $shippingOption;
							break;
						case 'all':
							$options['domestic'] = $shippingOption;
							$options['international'] = $shippingOption;
							break;
						default:
							break;
					}
				}
			}
			return $options;
		} // function getImposedOptions
	} // class shippingOptionsController

?>