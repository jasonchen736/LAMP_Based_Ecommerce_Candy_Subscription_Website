<?

	require_once 'admin.php';

	$pm = new productsManager;

	switch (getRequest('action')) {
		case 'add':
			$pageTemplate = 'admin/productEdit.htm';
			$mode = 'add';
			break;
		case 'addProduct':
			if ($pm->addProduct()) {
				$productID = $pm->getArrayData('record', 'productID');
				$message = 'Product added (Product ID: '.$productID.')';
				if (!$pm->uploadImage()) {
					$message .= '.  However, the product image has failed to upload.';
				} else {
					$pm->update();
				}
				addSuccess($message);
				if (getRequest('submit') == 'Add and Edit') {
					redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
				} else {
					redirect($_SERVER['PHP_SELF'].'/action/add');
				}
			} else {
				$pageTemplate = 'admin/productEdit.htm';
				$mode = 'add';
			}
			break;
		case 'edit':
			if ($pm->loadID(getRequest('productID', 'integer'))) {
				$pageTemplate = 'admin/productEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'update':
			if ($pm->loadID(getPost('productID'))) {
				if ($pm->updateProduct()) {
					$productID = $pm->getArrayData('record', 'productID');
					addSuccess('Product (Product ID: '.$productID.') updated');
					redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
				}
				$pageTemplate = 'admin/productEdit.htm';
				$mode = 'edit';
			} else {
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'massAction':
			if ($pm->takeAction(getRequest('records'), getRequest('updateAction'))) {
				redirect($_SERVER['PHP_SELF'].$pm->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			} else {
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'productChanges':
			// a cost or weight product change has been detected
			// first cascade to all packages automatically
			$productID = getRequest('productID', 'integer');
			if ($productID) {
				$packages = productsManager::cascadeProductChanges($productID);
				if (in_array(false, $packages)) {
					$updated = 0;
					$notUpdated = array();
					foreach ($packages as $key => $val) {
						if ($val) {
							++$updated;
						} else {
							$notUpdated[] = $key;
						}
					}
					addSuccess($updated.' package(s) have been updated');
					addMessage(count($notUpdated).' package(s) have not been updated');
					trigger_error('Unable to cascade product changes for product '.$productID.' - Packages failed: '.implode(', ', $notUpdated), E_USER_WARNING);
				} else {
					addSuccess(count($packages).' package(s) have been updated');
				}
				// second prompt for cascading to all offers that have updated packages
				$result = query("SELECT COUNT(DISTINCT `b`.`offerID`) AS `offers` FROM `productToPackage` `a` JOIN `packageToOffer` `b` ON (`a`.`packageID` = `b`.`packageID`) WHERE `a`.`productID` = '".$productID."'");
				$row = $result->fetchAssoc();
				$offers = $row['offers'];
				if ($offers) {
					// offers with updated package were found
					redirect($_SERVER['PHP_SELF'].'/action/cascadeToOffers/productID/'.$productID);
				} else {
					// no offers found with updated packages
					redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
				}
			} else {
				addError('Product ID has been lost while in this session');
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'cascadeToOffers':
			$productID = getRequest('productID', 'integer');
			if ($productID) {
				$result = query("SELECT COUNT(`b`.`offerID`) AS `offers` FROM `productToPackage` `a` JOIN `packageToOffer` `b` ON (`a`.`packageID` = `b`.`packageID`) WHERE `a`.`productID` = '".$productID."'");
				$row = $result->fetchAssoc();
				$offers = $row['offers'];
				if ($offers) {
					// offers with updated package were found
					$pageTemplate = 'admin/cascadeToOffers.htm';
				} else {
					// no offers found with updated packages
					redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
				}
			} else {
				addError('Product ID has been lost while in this session');
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'performCascadeToOffers':
			$productID = getRequest('productID', 'integer');
			if ($productID) {
				if (getRequest('cascadeChanges') == 'Yes') {
					$offersUpdated = productsManager::cascadeProductChanges($productID, 'offers');
					$notUpdated = 0;
					$updated = 0;
					foreach ($offersUpdated as $offer => $packages) {
						foreach ($packages as $package => $success) {
							if ($success) {
								++$updated;
							} else {
								++$notUpdated;
							}
						}
					}
					if ($notUpdated) {
						addSuccess($updated.' offer packages updated successfully; '.$notUpdated.' offer packages require attention');
						redirect($_SERVER['PHP_SELF'].'/action/cascadeToOffersManual/productID/'.$productID);
					} else {
						addSuccess($updated.' offer packages updated');
						redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
					}
				} else {
					if ($pm->loadID(getRequest('productID', 'integer'))) {
						$pageTemplate = 'admin/productEdit.htm';
						$mode = 'edit';
					} else {
						addError('Product ID has been lost while in this session');
						$pageTemplate = 'admin/productsAdmin.htm';
					}
				}
			} else {
				addError('Product ID has been lost while in this session');
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'cascadeToOffersManual':
			$productID = getRequest('productID', 'integer');
			if ($productID) {
				$sql = "SELECT 
							`d`.`offerID` , 
							`d`.`name` AS `offerName`, 
							`d`.`availability` AS `offerAvailability`, 
							`c`.`packageID`, 
							`c`.`name` AS `packageName`, 
							`c`.`cost` AS `packageCost`, 
							`c`.`weight` AS `packageWeight`, 
							`c`.`availability` AS `packageAvailability`, 
							`b`.`cost` AS `offerPrice` 
						FROM `productToPackage` `a` 
						JOIN `packageToOffer` `b` USING (`packageID`) 
						JOIN `packages` `c` USING (`packageID`) 
						JOIN `offers` `d` USING (`offerID`) 
						WHERE `productID` = '".$productID."' 
						AND `b`.`cost` != `c`.`cost`";
				$result = query($sql);
				if ($result->rowCount) {
					$pageTemplate = 'admin/cascadeToOffersManual.htm';
					$offerPackages = array();
					while ($row = $result->fetchAssoc()) {
						$offerPackages[] = $row;
					}
				} else {
					if ($pm->loadID($productID)) {
						addError('There were no offer package price discrepancies');
						$pageTemplate = 'admin/productEdit.htm';
						$mode = 'edit';
					} else {
						addError('Product ID has been lost while in this session');
						$pageTemplate = 'admin/productsAdmin.htm';
					}
				}
			} else {
				addError('Product ID has been lost while in this session');
				$pageTemplate = 'admin/productsAdmin.htm';
			}
			break;
		case 'performCascadeToOffersManual':
			$productID = getRequest('productID', 'integer');
			switch (getRequest('submit')) {
				case 'Update All':
					$offerPackages = getRequest('offerPackages');
					$validInput = array();
					foreach ($offerPackages as $key => $val) {
						list($offerID, $packageID) = split('-', $val);
						if (validNumber($offerID, 'integer') && validNumber($packageID, 'integer')) {
							if (!isset($validInput[$offerID])) {
								$validInput[$offerID] = array();
							}
							$validInput[$offerID][$packageID] = false;
						}
					}
					if ($validInput) {
						$updated = array();
						$notUpdated = array();
						$om = new offersManager;
						foreach ($validInput as $key => &$val) {
							if ($om->loadID($key)) {
								foreach ($val as $packageID => &$packageUpdated) {
									if ($om->updateOfferPackagePrice($packageID, false, false)) {
										$updated[] = $key.':'.$packageID;
										$packageUpdated = true;
									} else {
										$notUpdated[] = $key.':'.$packageID;
									}
								}
							}
						}
						if ($updated) {
							addSuccess(count($updated).' offer package(s) updated successfully');
						}
						if ($notUpdated) {
							addMessage(count($notUpdated).' offer package(s) not updated');
							addMessage('Offer packages not updated (Offer ID:Package ID) - ('.implode('), (', $notUpdated).')');
						}
						$productID = getRequest('productID', 'integer');
						if ($productID) {
							redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
						} else {
							addError('Product ID has been lost while in this session');
							redirect($_SERVER['PHP_SELF']);
						}
					} else {
						$productID = getRequest('productID', 'integer');
						if ($productID) {
							if ($pm->loadID($productID)) {
								addError('Input Error: Offer packages not updated');
								$pageTemplate = 'admin/productEdit.htm';
								$mode = 'edit';
							} else {
								addError('Product ID has been lost while in this session');
								$pageTemplate = 'admin/productsAdmin.htm';
							}
						} else {
							addError('Product ID has been lost while in this session');
							$pageTemplate = 'admin/productsAdmin.htm';
						}
					}
					break;
				case 'Submit Changes':
					$offerPackages = getRequest('offerPackages');
					$validInput = array();
					foreach ($offerPackages as $key => $val) {
						$offerPackageCost = getRequest($val);
						list($offerID, $packageID) = split('-', $val);
						if (validNumber($offerPackageCost) && validNumber($offerID, 'integer') && validNumber($packageID, 'integer')) {
							if (!isset($validInput[$offerID])) {
								$validInput[$offerID] = array();
							}
							$validInput[$offerID][$packageID] = $offerPackageCost;
						}
					}
					if ($validInput) {
						$updated = array();
						$notUpdated = array();
						$om = new offersManager;
						foreach ($validInput as $key => $val) {
							if ($om->loadID($key)) {
								foreach ($val as $packageID => $cost) {
									if ($om->updateOfferPackagePrice($packageID, $cost)) {
										$updated[] = $key.':'.$packageID;
									} else {
										$notUpdated[] = $key.':'.$packageID;
									}
								}
							}
						}
						if ($updated) {
							addSuccess(count($updated).' offer package(s) updated successfully');
						}
						if ($notUpdated) {
							addMessage(count($notUpdated).' offer package(s) not updated');
							addMessage('Offer packages not updated (Offer ID:Package ID) - ('.implode('), (', $notUpdated).')');
						}
						$productID = getRequest('productID', 'integer');
						if ($productID) {
							redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
						} else {
							addError('Product ID has been lost while in this session');
							redirect($_SERVER['PHP_SELF']);
						}
					} else {
						$productID = getRequest('productID', 'integer');
						if ($productID) {
							if ($pm->loadID($productID)) {
								addError('Input Error: Offer packages not updated');
								$pageTemplate = 'admin/productEdit.htm';
								$mode = 'edit';
							} else {
								addError('Product ID has been lost while in this session');
								$pageTemplate = 'admin/productsAdmin.htm';
							}
						} else {
							addError('Product ID has been lost while in this session');
							$pageTemplate = 'admin/productsAdmin.htm';
						}
					}
					break;
				default:
					$productID = getRequest('productID', 'integer');
					if ($productID) {
						if ($pm->loadID($productID)) {
							addMessage('Offer packages not updated');
							$pageTemplate = 'admin/productEdit.htm';
							$mode = 'edit';
						} else {
							addError('Product ID has been lost while in this session');
							$pageTemplate = 'admin/productsAdmin.htm';
						}
					} else {
						addError('Product ID has been lost while in this session');
						$pageTemplate = 'admin/productsAdmin.htm';
					}
					break;
			}
			break;
		default:
			$pageTemplate = 'admin/productsAdmin.htm';
			break;
		case 'cascadeToOffersManual':
			$notUpdated = getRequest('packages');
			$notUpdated = explode(';', $notUpdated);
			foreach ($notUpdated as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($notUpdated[$key]);
				}
			}
			$packageData = array();
			if ($notUpdated) {
				$result = query("SELECT `packageID`, `name`, `cost`, `weight` FROM `packages` WHERE `packageID` IN ('".implode("', '", $notUpdated)."')");
				while ($row = $result->fetchAssoc()) {
					$row['content'] = packageManager::getPackageContents($row['packageID']);
					$packageData[] = $row;
				}
			}
			$pageTemplate = 'admin/cascadeToPackagesManual.htm';
			break;
		case 'cascadeToPackages':
			$costs = getPost('cost');
			$weights = getPost('weight');
			$validInput = true;
			foreach ($costs as $key => $val) {
				if (!validNumber($val, 'double') || !validNumber($key, 'integer')) {
					$validInput = false;
					break;
				} elseif (!isset($weights[$key]) || !validNumber($weights[$key], 'double')) {
					$validInput = false;
					break;
				}
			}
			if ($validInput) {
				$updated = array();
				foreach ($costs as $key => $val) {
					$updated[$key] = false;
					$package = new packageManager;
					if ($package->loadID($key)) {
						$package->setField('cost', $val);
						$package->setField('weight', $weights[$key]);
						$updated[$key] = $package->update();
					}
				}
				if (in_array(false, $updated)) {
					$success = 0;
					$notUpdated = array();
					foreach ($updated as $key => $val) {
						if ($val) {
							++$success;
						} else {
							$notUpdated[] = $key;
						}
					}
					addSuccess($success.' package(s) have been updated');
					addMessage(count($notUpdated).' package(s) have not been updated');
					addMessage('Package(s) '.implode(', ', $notUpdated).' have not been updated');
				} else {
					addSuccess(count($updated).' packages have been updated');
				}
				// prompt for cascading to all offers that have updated packages
				$productID = getRequest('productID', 'integer');
				if ($productID) {
					$result = query("SELECT COUNT(DISTINCT `b`.`offerID`) AS `offers` FROM `productToPackage` `a` JOIN `packageToOffer` `b` ON (`a`.`packageID` = `b`.`packageID`) WHERE `a`.`productID` = '".$productID."'");
					$row = $result->fetchAssoc();
					$offers = $row['offers'];
					if ($offers) {
						// offers with updated package were found
						redirect($_SERVER['PHP_SELF'].'/action/cascadeToOffers/productID/'.$productID);
					} else {
						// no offers found with updated packages
						redirect($_SERVER['PHP_SELF'].'/action/edit/productID/'.$productID);
					}
				} else {
					addError('Product ID has been lost while in this session');
				}
			} else {
				addError('There was an error in your request, packages have not been updated');
			}
			$pageTemplate = 'admin/productsAdmin.htm';
			break;
	}

	switch ($pageTemplate) {
		case 'admin/productsAdmin.htm':
			list($start, $show, $page) = $pm->getTableLocation();
			list($search, $count) = $pm->getSearch($start, $show);
			$dbh = new database;
			$result = $dbh->query($search);
			$template->assignClean('records', $result->fetchAllAssoc());
			$result = $dbh->query($count);
			$row = $result->fetchAssoc();
			$totalRecords = $row['count'];
			$template->assignClean('totalRecords', $totalRecords);
			$template->assignClean('search', $pm->getSearchVars());
			$template->assignClean('updateActions', $pm->getActions());
			$template->assignClean('updateAction', getRequest('updateAction'));
			$template->assignClean('show', $show);
			$template->assignClean('page', $page);
			$template->assignClean('start', $start);
			$template->assignClean('pages', ceil($totalRecords / $show));
			$template->assignClean('querystring', $pm->getQueryString(array('submit', 'nextPage', 'previousPage', 'records', 'action', 'updateAction')));
			break;
		case 'admin/productEdit.htm':
			$template->assignClean('product', $pm->get('record'));
			$template->assignClean('quantity', $pm->get('quantity'));
			$template->assignClean('mode', $mode);
			$productTags = $pm->get('productTags');
			assertArray($productTags);
			$template->assignClean('tags', implode("\r\n", $productTags));
			$template->assignClean('availabilityOptions', $pm->getArrayData('fields', 'availability'));
			break;
		case 'admin/cascadeToOffers.htm':
			$template->assignClean('offers', $offers);
			$template->assignClean('productID', $productID);
			break;
		case 'admin/cascadeToOffersManual.htm':
			$template->assignClean('productID', $productID);
			$template->assignClean('offerPackages', $offerPackages);
			break;
		default:
			break;
	}

	$template->getMessages();
	$template->display($pageTemplate);

?>