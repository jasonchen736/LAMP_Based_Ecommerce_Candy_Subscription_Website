<?

	require_once 'admin.php';

	$actions = array(
		'productsAdmin',
		'addProduct',
		'saveProduct',
		'editProduct',
		'updateProduct',
		'quickUpdate',
		'previewImage'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		productsAdmin();
	}

	/**
	 *  Show the products admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function productsAdmin() {
		$controller = new productsController;
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
		$template->assignClean('updateOptions', productsController::getQuickUpdateOptions());
		$template->getMessages();
		$template->display('admin/productsAdmin.htm');	
	} // function productsAdmin

	/**
	 *  Add product section
	 *  Args: none
	 *  Return: none
	 */
	function addProduct() {
		$product = new product;
		$controller = new productsController;
		$template = new template;
		$template->assignClean('product', $product->fetchArray());
		$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('tags', array());
		$template->assignClean('productTags', productsController::getProductTags());
		$template->assignClean('sites', array(systemSettings::get('SITEID')));
		$template->assignClean('siteOptions', siteRegistryController::getSites());
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('admin/productEdit.htm');
	} // function addProduct

	/**
	 *  Save a new product record
	 *  Args: none
	 *  Return: none
	 */
	function saveProduct() {
		$product = new product;
		$product->set('name', getPost('name'));
		$product->set('sku', '');
		$sku = getPost('sku', 'alphanum');
		if ($sku) {
			if (!productsController::isDuplicateSKU($sku)) {
				$product->set('sku', $sku);
			} else {
				addError('The SKU number already exists');
			}
		}
		$product->set('brand', getPost('brand'));
		$product->set('availability', getPost('availability'));
		$product->set('cost', getPost('cost'));
		$product->set('weight', getPost('weight'));
		$product->set('length', getPost('length'));
		$product->set('width', getPost('width'));
		$product->set('height', getPost('height'));
		$product->set('sortWeight', getPost('sortWeight'));
		$product->set('description', getPost('description'));
		$product->set('shortDescription', getPost('shortDescription'));		
		if ($memberID = getPost('memberID', 'integer')) {
			$member = new member($memberID);
			if ($member->exists()) {
				$product->set('memberID', $member->get('memberID'));
			} else {
				addError('The merchant ID was invalid');
			}
		} else {
			$product->set('memberID', 0);
		}
		if ($product->save()) {
			addSuccess('Product '.$product->get('name').' added successfully');
			$productID = $product->get('productID');
			if (!$product->setInventory(getPost('quantity'))) {
				addError('There was an error while setting the product inventory');
			}
			$tags = productsController::retrieveObjectTags('productTags', 'createAndRetrieveExisting', 'productTags');
			if (!empty($tags)) {
				addSuccess('Tags: '.implode(', ', $tags));
				if (!$product->addTags($tags)) {
					addError('There was an error while adding tags to the product');
				}
			}
			if (productsController::processImages($productID) === false) {
				addError('There was an error while processing the product images');
			}
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			if (!empty($sites)) {
				if (!$product->addSites($sites)) {
					addError('There was an error while processing the product websites');
				}
			} else {
				addSuccess('There are no sites associated with the product');
			}
			$productBrand = $product->get('brand');
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
				editProduct($productID);
			} else {
				addProduct();
			}
		} else {
			addError('There was an error while saving the product');
			addError('Product images have to be re-uploaded');
			$tags = productsController::retrieveObjectTags('productTags', 'retrieveOnly');
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$controller = new productsController;
			$template = new template;
			$template->assignClean('product', $product->fetchArray());
			$template->assignClean('quantity', getPost('quantity', 'integer'));
			$template->assignClean('tags', $tags);
			$template->assignClean('productTags', productsController::getProductTags());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('sites', $sites);
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('mode', 'add');
			$template->getMessages();
			$template->display('admin/productEdit.htm');
		}
	} // function saveProduct

	/**
	 *  Edit product section
	 *  Args: (int) product id
	 *  Return: none
	 */
	function editProduct($productID = false) {
		if (!$productID) {
			$productID = getRequest('productID', 'integer');
		}
		$product = new product($productID);
		if ($product->exists()) {
			$controller = new productsController;
			$template = new template;
			$template->assignClean('product', $product->fetchArray());
			$template->assignClean('productTags', productsController::getProductTags());
			$template->assignClean('quantity', $product->getInventory());
			$template->assignClean('tags', $product->getObjectTags());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('sites', $product->getObjectSites());
			$template->assignClean('siteOptions', siteRegistryController::getSites());
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('admin/productEdit.htm');
		} else {
			addError('The product was not found');
			productsAdmin();
		}
	} // function editProduct

	/**
	 *  Update an existing product record
	 *  Args: none
	 *  Return: none
	 */
	function updateProduct() {
		$product = new product(getRequest('productID', 'integer'));
		if ($product->exists()) {
			$productID = $product->get('productID');
			$originalCost = $product->get('cost');
			$originalWeight = $product->get('weight');
			$product->set('name', getPost('name'));
			$sku = getPost('sku', 'alphanum');
			if ($sku) {
				if (!productsController::isDuplicateSKU($sku)) {
					$product->set('sku', $sku);
				} elseif ($sku != $product->get('sku')) {
					addError('The SKU number already exists');
				}
			}
			$product->set('brand', getPost('brand'));
			$product->set('availability', getPost('availability'));
			$product->set('cost', getPost('cost'));
			$product->set('weight', getPost('weight'));
			$product->set('length', getPost('length'));
			$product->set('width', getPost('width'));
			$product->set('height', getPost('height'));
			$product->set('sortWeight', getPost('sortWeight'));
			$product->set('description', getPost('description'));
			$product->set('shortDescription', getPost('shortDescription'));
			if ($memberID = getPost('memberID', 'integer')) {
				$member = new member($memberID);
				if ($member->exists()) {
					$product->set('memberID', $member->get('memberID'));
				} else {
					addError('The merchant ID was invalid');
				}
			} else {
				$product->set('memberID', 0);
			}
			if (!$product->update()) {
				addError('There was an error while updating the product details');
			} else {
				if ($originalCost != $product->get('cost') || $originalWeight != $product->get('weight')) {
					addSuccess('Product cost/weight has changed, updating any product packages');
					if (packagesController::updateProductPackages($productID)) {
						addSuccess('All product packages have been updated successfully');
					}
					addSuccess('Offer prices may need to be updated: <a href="/admin/offersManagement/action/viewOffers/item/product/itemID/'.$productID.'">View product offers</a>');
				}
			}
			$inventory = getPost('quantity');
			if ($inventory != $product->getInventory()) {
				if ($product->setInventory($inventory)) {
					addSuccess('Inventory updated');
				} else {
					addError('There was an error while setting the product inventory');
				}
			}
			$existingTags = $product->getObjectTags();
			$tags = productsController::retrieveObjectTags('productTags', 'createAndRetrieveExisting', 'productTags');
			$addTags = array_diff($tags, $existingTags);
			if (!empty($addTags)) {
				addSuccess('New tags: '.implode(', ', $addTags));
				if ($product->addTags($addTags)) {
					addSuccess('Tags added successfully');
				} else {
					addError('There was an error while adding tags to the product');
				}
			}
			$removeTags = array_diff($existingTags, $tags);
			if (!empty($removeTags)) {
				addSuccess('Remove tags: '.implode(', ', $removeTags));
				if ($product->removeTags($removeTags)) {
					addSuccess('Tags removed successfully');
				} else {
					addError('There was an error while removing tags from the product');
				}
			}
			$imagesProcessed = productsController::processImages($productID);
			if ($imagesProcessed === true) {
				addSuccess('Images have been processed successfully');
				$product->load($productID);
			} elseif ($imagesProcessed === false) {
				addError('There was an error while processing the product images');
			}
			$sites = getPost('sites');
			assertArray($sites);
			foreach ($sites as $key => $val) {
				if (!validNumber($val, 'integer')) {
					unset($sites[$key]);
				}
			}
			$existingSites = $product->getObjectSites();
			$addSites = array_diff($sites, $existingSites);
			if (!empty($addSites)) {
				if ($product->addSites($addSites)) {
					addSuccess('Websites have been added successfully');
				} else {
					addError('There was an error while adding product websites');
				}
			}
			$removeSites = array_diff($existingSites, $sites);
			if (!empty($removeSites)) {
				if ($product->removeSites($removeSites)) {
					addSuccess('Websites removed successfully');
				} else {
					addError('There was an error while removing product websites');
				}
			}
			$productBrand = $product->get('brand');
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
				addSuccess($product->get('name').' was updated successfully');
				$productSites = $product->getObjectSites();
				if (empty($productSites)) {
					addSuccess('There are no sites associated with the product');
				}
				editProduct($productID);
			} else {
				$controller = new productsController;
				$template = new template;
				$template->assignClean('product', $product->fetchArray());
				$template->assignClean('quantity', getPost('quantity', 'integer'));
				$template->assignClean('tags', $tags);
				$template->assignClean('productTags', productsController::getProductTags());
				$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
				$template->assignClean('sites', $sites);
				$template->assignClean('siteOptions', siteRegistryController::getSites());
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->assignClean('mode', 'edit');
				$template->getMessages();
				$template->display('admin/productEdit.htm');
			}
		} else {
			addError('Product does not exist');
			productsAdmin();
		}
	} // function updateProduct

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
					foreach ($records as $productID) {
						$product = new product($productID);
						$product->set('availability', $action);
						if ($product->update()) {
							addSuccess('Product '.$product->get('name').' updated');
						} else {
							addError('There was an error while updating product '.$product->get('name'));
						}
					}
					break;
				case 'addTags':
					$tags = productsController::retrieveObjectTags('productTags', 'createAndRetrieveExisting', 'productTags');
					if ($tags) {
						addSuccess('Tags: '.implode(', ', $tags));
						foreach ($records as $productID) {
							$product = new product($productID);
							if ($product->exists()) {
								if ($product->addTags($tags)) {
									addSuccess('Tags added to product '.$product->get('name'));
								} else {
									addError('There was an error while adding tags to product '.$product->get('name'));
								}
							} else {
								addError('Product #'.$productID.' was not found');
							}
						}
					} else {
						addError('Invalid tags');
					}
					break;
				case 'removeTags':
					$tags = productsController::retrieveObjectTags('productTags', 'retrieveExistingOnly', 'productTags');
					if ($tags) {
						addSuccess('Tags: '.implode(', ', $tags));
						foreach ($records as $productID) {
							$product = new product($productID);
							if ($product->exists()) {
								if ($product->removeTags($tags)) {
									addSuccess('Tags removed from product '.$product->get('name'));
								} else {
									addError('There was an error while removing tags from product '.$product->get('name'));
								}
							} else {
								addError('Product #'.$productID.' was not found');
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
		productsAdmin();
	} // function quickUpdate

	/**
	 *  Preview a product image
	 *  Args: none
	 *  Return: none
	 */
	function previewImage() {
		$size = getRequest('size', 'alpha');
		$file = getRequest('file', 'alphanum');
		headers::sendNoCacheHeaders();
		$image = new image($file.'.gif', 'file', systemSettings::get('IMAGEDIR').'/products/'.$size.'/');
		if ($image->exists()) {
			echo '<div>';
			echo 'Name: '.$image->getImageData('name').'<br />';
			echo 'Type: '.$image->getImageData('type').'<br />';
			echo 'Size: '.$image->getImageData('width').' x '.$image->getImageData('height');
			echo '</div>';
			echo '<img src="/images/products/'.$size.'/'.$file.'.gif" />';
		} else {
			echo '<p>Image not found</p>';
		}
	} // function previewImage

?>