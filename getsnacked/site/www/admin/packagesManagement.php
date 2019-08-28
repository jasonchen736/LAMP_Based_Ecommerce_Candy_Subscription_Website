<?

	require_once 'admin.php';

	$actions = array(
		'packagesAdmin',
		'addPackage',
		'savePackage',
		'editPackage',
		'updatePackage',
		'quickUpdate',
		'previewImage',
		'viewContent'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		packagesAdmin();
	}

	/**
	 *  Show the packages admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function packagesAdmin() {
		$controller = new packagesController;
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$updateOption = getRequest('updateOption', 'alphanum');
		$template = new template;
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('additionaSearchOptions', getRequest('additionaSearchOptions', 'alphanum'));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', packagesController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/packagesAdmin.htm');	
	} // function packagesAdmin

	/**
	 *  Add package section
	 *  Args: none
	 *  Return: none
	 */
	function addPackage() {
		$package = new package;
		$controller = new packagesController;
		$template = new template;
		$template->assignClean('package', $package->fetchArray());
		$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('tags', array());
		$template->assignClean('productTags', productsController::getProductTags());
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/packageEdit.htm');
	} // function addPackage

	/**
	 *  Save a new package record
	 *  Args: none
	 *  Return: none
	 */
	function savePackage() {
		$package = new package;
		$package->set('name', getPost('name'));
		$package->set('sku', '');
		$sku = getPost('sku', 'alphanum');
		if ($sku) {
			if (!productsController::isDuplicateSKU($sku)) {
				$package->set('sku', $sku);
			} else {
				addError('The submitted SKU number already exists');
			}
		}
		$package->set('brand', getPost('brand'));
		$package->set('availability', getPost('availability'));
		$package->set('sortWeight', getPost('sortWeight'));
		$package->set('description', getPost('description'));
		$package->set('shortDescription', getPost('shortDescription'));		
		// validate package content
		$content = packagesController::getPackageContents(false);
		if (!empty($content)) {
			$search = array();
			foreach ($content as $productID => $vals) {
				$search[$productID] = $vals['quantity'];
			}
			$existing = packagesController::findPackageByContent($search);
			if (!$existing) {
				$packageCost = 0;
				$packageWeight = 0;
				foreach ($content as $id => $vals) {
					$packageCost += $vals['cost'] * $vals['quantity'];
					$packageWeight += $vals['weight'] * $vals['quantity'];
				}
				$package->set('cost', $packageCost);
				$package->set('weight', $packageWeight);
				if ($package->save()) {
					addSuccess('Package '.$package->get('name').' added successfully');
					$packageID = $package->get('packageID');
					if ($package->addContent($content)) {
						$tags = packagesController::retrieveObjectTags('packageTags', 'createAndRetrieveExisting', 'productTags');
						if (!empty($tags)) {
							addSuccess('Tags: '.implode(', ', $tags));
							if (!$package->addTags($tags)) {
								addError('There was an error while adding tags to the package');
							}
						}
						if (packagesController::processImages($packageID) === false) {
							addError('There was an error while processing the package images');
						}
						$sites = getPost('sites');
						assertArray($sites);
						foreach ($sites as $key => $val) {
							if (!validNumber($val, 'integer')) {
								unset($sites[$key]);
							}
						}
						if (!empty($sites)) {
							if (!$package->addSites($sites)) {
								addError('There was an error while processing the package websites');
							}
						} else {
							addSuccess('There are no sites associated with the package');
						}
						$productBrand = $package->get('brand');
						if ($productBrand) {
							$brand = new brand;
							$brand->set('brand', $productBrand);
							if (!$brand->isDuplicate()) {
								if (!$brand->save()) {
									addError('There was an error while adding the brand name to the brands list');
								}
							}
						}
						if (haveErrors() || getRequest('submit') == 'Add and Edit') {
							editPackage($packageID);
						} else {
							addPackage();
						}
						exit;
					} else {
						addError('There was an error while adding the package contents');
					}
				} else {
					addError('There was an error while saving the package data');
				}
			} else {
				addError('A package already exists with the content: Package ID '.$existing);
			}
		} else {
			addError('Package content is invalid');
		}
		addError('Package images have to be re-uploaded');
		$tags = packagesController::retrieveObjectTags('packageTags', 'retrieveOnly');
		$sites = getPost('sites');
		assertArray($sites);
		foreach ($sites as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($sites[$key]);
			}
		}
		$controller = new packagesController;
		$template = new template;
		$template->assignClean('package', $package->fetchArray());
		$template->assignClean('content', $content);
		$template->assignClean('tags', $tags);
		$template->assignClean('productTags', productsController::getProductTags());
		$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
		$template->assignClean('sites', $sites);
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/packageEdit.htm');
	} // function savePackage

	/**
	 *  Edit package section
	 *  Args: (int) package id
	 *  Return: none
	 */
	function editPackage($packageID = false) {
		if (!$packageID) {
			$packageID = getRequest('packageID', 'integer');
		}
		$package = new package($packageID);
		if ($package->exists()) {
			$controller = new packagesController;
			$template = new template;
			$template->assignClean('package', $package->fetchArray());
			$template->assignClean('content', packagesController::getPackageContents($package->get('packageID'), true));
			$template->assignClean('tags', $package->getObjectTags());
			$template->assignClean('productTags', productsController::getProductTags());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('sites', $package->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/packageEdit.htm');
		} else {
			addError('The package was not found');
			packagesAdmin();
		}
	} // function editPackage

	/**
	 *  Update an existing package record
	 *  Args: none
	 *  Return: none
	 */
	function updatePackage() {
		$package = new package(getRequest('packageID', 'integer'));
		if ($package->exists()) {
			$packageID = $package->get('packageID');
			$package->set('name', getPost('name'));
			$sku = getPost('sku', 'alphanum');
			if ($sku) {
				if (!productsController::isDuplicateSKU($sku)) {
					$package->set('sku', $sku);
				} elseif ($sku != $package->get('sku')) {
					addError('The SKU number already exists');
				}
			}
			$package->set('brand', getPost('brand'));
			$package->set('availability', getPost('availability'));
			$package->set('sortWeight', getPost('sortWeight'));
			$package->set('description', getPost('description'));
			$package->set('shortDescription', getPost('shortDescription'));
			if (!$package->update()) {
				addError('There was an error while updating the package details');
			}
			$imagesProcessed = packagesController::processImages($packageID);
			if ($imagesProcessed === true) {
				addSuccess('Images have been processed successfully');
				$package->load($packageID);
			} elseif ($imagesProcessed === false) {
				addError('There was an error while processing the package images');
			}
			$existingTags = $package->getObjectTags();
			$tags = packagesController::retrieveObjectTags('packageTags', 'createAndRetrieveExisting', 'productTags');
			$addTags = array_diff($tags, $existingTags);
			if (!empty($addTags)) {
				addSuccess('New tags: '.implode(', ', $addTags));
				if ($package->addTags($addTags)) {
					addSuccess('Tags added successfully');
				} else {
					addError('There was an error while adding tags to the package');
				}
			}
			$removeTags = array_diff($existingTags, $tags);
			if (!empty($removeTags)) {
				addSuccess('Remove tags: '.implode(', ', $removeTags));
				if ($package->removeTags($removeTags)) {
					addSuccess('Tags removed successfully');
				} else {
					addError('There was an error while removing tags from the package');
				}
			}
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$existingSites = $package->getObjectSites();
			$addSites = array_diff($sites, $existingSites);
			if (!empty($addSites)) {
				if ($package->addSites($addSites)) {
					addSuccess('Websites have been added successfully');
				} else {
					addError('There was an error while adding package websites');
				}
			}
			$removeSites = array_diff($existingSites, $sites);
			if (!empty($removeSites)) {
				if ($package->removeSites($removeSites)) {
					addSuccess('Websites removed successfully');
				} else {
					addError('There was an error while removing package websites');
				}
			}
			$productBrand = $package->get('brand');
			if ($productBrand) {
				$brand = new brand;
				$brand->set('brand', $productBrand);
				if (!$brand->isDuplicate()) {
					if (!$brand->save()) {
						addError('There was an error while adding the brand name to the brands list');
					}
				}
			}
			if (!haveErrors()) {
				addSuccess($package->get('name').' was updated successfully');
				$packageSites = $package->getObjectSites();
				if (empty($packageSites)) {
					addSuccess('There are no sites associated with the package');
				}
				editPackage($packageID);
			} else {
				$controller = new packagesController;
				$template = new template;
				$template->assignClean('package', $package->fetchArray());
				$template->assignClean('content', packagesController::getPackageContents($packageID, true));
				$template->assignClean('tags', $tags);
				$template->assignClean('productTags', productsController::getProductTags());
				$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
				$template->assignClean('sites', $sites);
				$template->assignClean('siteOptions', siteRegistryController::getSites());
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->assignClean('mode', 'edit');
				$template->getMessages();
				$template->display('admin/packageEdit.htm');
			}
		} else {
			addError('Package does not exist');
			packagesAdmin();
		}
	} // function updatePackage

	/**
	 *  Perform specific update on multiple records
	 *  Args: none
	 *  Return: none
	 */
	function quickUpdate() {
		$records = getRequest('records');
		foreach ($records as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($records[$key]);
				addError('One or more record IDs was invalid');
			}
		}
		if ($records) {
			$action = getRequest('updateOption');
			switch ($action) {
				case 'available':
				case 'alwaysavailable':
				case 'outofstock':
				case 'withheld':
				case 'discontinued':
					foreach ($records as $packageID) {
						$package = new package($packageID);
						$package->set('availability', $action);
						if ($package->update()) {
							addSuccess('Package '.$package->get('name').' updated');
						} else {
							addError('There was an error while updating package '.$package->get('name'));
						}
					}
					break;
				case 'addTags':
					$tags = packagesController::retrieveObjectTags('packageTags', 'createAndRetrieveExisting', 'productTags');
					if ($tags) {
						addSuccess('Tags: '.implode(', ', $tags));
						foreach ($records as $packageID) {
							$package = new package($packageID);
							if ($package->exists()) {
								if ($package->addTags($tags)) {
									addSuccess('Tags added to package '.$package->get('name'));
								} else {
									addError('There was an error while adding tags to package '.$package->get('name'));
								}
							} else {
								addError('Package #'.$packageID.' was not found');
							}
						}
					} else {
						addError('Invalid tags');
					}
					break;
				case 'removeTags':
					$tags = packagesController::retrieveObjectTags('packageTags', 'retrieveExistingOnly', 'productTags');
					if ($tags) {
						addSuccess('Tags: '.implode(', ', $tags));
						foreach ($records as $packageID) {
							$package = new package($packageID);
							if ($package->exists()) {
								if ($package->removeTags($tags)) {
									addSuccess('Tags removed from package '.$package->get('name'));
								} else {
									addError('There was an error while removing tags from package '.$package->get('name'));
								}
							} else {
								addError('Package #'.$packageID.' was not found');
							}
						}
					} else {
						addError('Tags not found');
					}
					break;
				default:
					addError('Invalid action');
					break;
			}
		} else {
			addError('There are no records to update');
		}
		packagesAdmin();
	} // function quickUpdate

	/**
	 *  Preview a package image
	 *  Args: none
	 *  Return: none
	 */
	function previewImage() {
		$size = getRequest('size', 'alpha');
		$file = getRequest('file', 'alphanum');
		headers::sendNoCacheHeaders();
		$image = new image($file.'.gif', 'file', systemSettings::get('IMAGEDIR').'/packages/'.$size.'/');
		if ($image->exists()) {
			echo '<div>';
			echo 'Name: '.$image->getImageData('name').'<br />';
			echo 'Type: '.$image->getImageData('type').'<br />';
			echo 'Size: '.$image->getImageData('width').' x '.$image->getImageData('height');
			echo '</div>';
			echo '<img src="/images/packages/'.$size.'/'.$file.'.gif" />';
		} else {
			echo '<p>Image not found</p>';
		}
	} // function previewImage

	/**
	 *  Show package content details
	 *  Args: none
	 *  Return: none
	 */
	function viewContent() {
		$packageID = getRequest('packageID', 'integer');
		if ($packageID) {
			$content = packagesController::getPackageContents($packageID);
		} else {
			$content = false;
		}
		$template = new template;
		$template->assignClean('content', $content);
		$template->display('admin/viewPackageContent.htm');
	} // function viewContent

?>