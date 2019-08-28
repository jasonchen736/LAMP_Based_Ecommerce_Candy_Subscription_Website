<?

	require_once 'merchant.php';

	$actions = array(
		'inventoryAdmin',
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
		inventoryAdmin();
	}

	/**
	 *  Show the inventory admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function inventoryAdmin() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$controller = new productsController;
		$controller->imposeSearch('memberID', $merchantInfo['id']);
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$updateOption = getRequest('updateOption', 'alphanum');
		$updateOptions = productsController::getQuickUpdateOptions();
		unset($updateOptions['addTags']);
		unset($updateOptions['removeTags']);
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->assignClean('updateOption', $updateOption);
		$template->assignClean('updateOptions', $updateOptions);
		$template->getMessages();
		$template->display('merchant/inventoryAdmin.htm');	
	} // function inventoryAdmin

	/**
	 *  Add product section
	 *  Args: none
	 *  Return: none
	 */
	function addProduct() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$product = new product;
		$controller = new productsController;
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('product', $product->fetchArray());
		$template->assignClean('tags', array());
		$template->assignClean('productTags', productsController::getProductTags());
		$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
		$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
		$template->assignClean('mode', 'add');
		$template->getMessages();
		$template->display('merchant/productEdit.htm');
	} // function addProduct

	/**
	 *  Save a new product record
	 *  Args: none
	 *  Return: none
	 */
	function saveProduct() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$product = new product;
		$product->set('name', getPost('name'));
		$product->set('availability', getPost('availability'));
		$product->set('cost', getPost('cost'));
		$product->set('weight', getPost('weight'));
		$product->set('length', getPost('length'));
		$product->set('width', getPost('width'));
		$product->set('height', getPost('height'));
		$product->set('sortWeight', getPost('sortWeight'));
		$product->set('description', getPost('description'));
		$product->set('shortDescription', getPost('shortDescription'));		
		$product->set('memberID', $merchantInfo['id']);
		$tags = productsController::retrieveObjectTags('productTags', 'retrieveExistingOnly', 'productTags');
		if ($product->save()) {
			addSuccess('Product '.$product->get('name').' added successfully');
			$productID = $product->get('productID');
			if (!$product->setInventory(getPost('quantity'))) {
				addError('There was an error while setting the product inventory');
			}
			if (!empty($tags)) {
				addSuccess('Tags: '.implode(', ', $tags));
				if (!$product->addTags($tags)) {
					addError('There was an error while adding product tags');
				}
			}
			if (productsController::processImages($productID) === false) {
				addError('There was an error while processing the product images');
			}
			$product->addSites(array(systemSettings::get('SITEID')));
			if (haveErrors() || getRequest('submit') == 'Add and Edit') {
				editProduct($productID);
			} else {
				addProduct();
			}
		} else {
			addError('There was an error while saving the product');
			addError('Product images have to be re-uploaded');
			$controller = new productsController;
			$template = new template;
			$template->assignClean('merchantInfo', $merchantInfo);
			$template->assignClean('product', $product->fetchArray());
			$template->assignClean('tags', $tags);
			$template->assignClean('productTags', productsController::getProductTags());
			$template->assignClean('quantity', getPost('quantity', 'integer'));
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
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
		$merchantInfo = merchantCore::getMerchantInfo();
		if (!$productID) {
			$productID = getRequest('productID', 'integer');
		}
		$product = new product($productID);
		if ($product->exists() && $product->get('memberID') == $merchantInfo['id']) {
			$controller = new productsController;
			$template = new template;
			$template->assignClean('merchantInfo', $merchantInfo);
			$template->assignClean('product', $product->fetchArray());
			$template->assignClean('tags', $product->getObjectTags());
			$template->assignClean('productTags', productsController::getProductTags());
			$template->assignClean('quantity', $product->getInventory());
			$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
			$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
			$template->assignClean('mode', 'edit');
			$template->getMessages();
			$template->display('merchant/productEdit.htm');
		} else {
			addError('The product was not found');
			inventoryAdmin();
		}
	} // function editProduct

	/**
	 *  Update an existing product record
	 *  Args: none
	 *  Return: none
	 */
	function updateProduct() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$product = new product(getRequest('productID', 'integer'));
		if ($product->exists() && $product->get('memberID') == $merchantInfo['id']) {
			$productID = $product->get('productID');
			$originalCost = $product->get('cost');
			$originalWeight = $product->get('weight');
			$product->set('name', getPost('name'));
			$product->set('availability', getPost('availability'));
			$product->set('cost', getPost('cost'));
			$product->set('weight', getPost('weight'));
			$product->set('length', getPost('length'));
			$product->set('width', getPost('width'));
			$product->set('height', getPost('height'));
			$product->set('sortWeight', getPost('sortWeight'));
			$product->set('description', getPost('description'));
			$product->set('shortDescription', getPost('shortDescription'));
			if (!$product->update()) {
				addError('There was an error while updating the product details');
			} else {
				if ($originalCost != $product->get('cost') || $originalWeight != $product->get('weight')) {
					if (!packagesController::updateProductPackages($productID)) {
						trigger_error('There was an error while updating package cost/weight for product #'.$productID.', merchant #'.$merchantInfo['id'].'; package and offer data may be out of sync', E_USER_WARNING);
						clearErrors();
					}
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
			$tags = productsController::retrieveObjectTags('productTags', 'retrieveExistingOnly', 'productTags');
			$addTags = array_diff($tags, $existingTags);
			if (!empty($addTags)) {
				addSuccess('New tags: '.implode(', ', $addTags));
				if ($product->addTags($addTags)) {
					addSuccess('Tags added successfully');
				} else {
					addError('There was an error while adding product tags');
				}
			}
			$removeTags = array_diff($existingTags, $tags);
			if (!empty($removeTags)) {
				addSuccess('Remove tags: '.implode(', ', $removeTags));
				if ($product->removeTags($removeTags)) {
					addSuccess('Tags removed successfully');
				} else {
					addError('There was an error while removing product tags');
				}
			}
			$imagesProcessed = productsController::processImages($productID);
			if ($imagesProcessed === true) {
				addSuccess('Images have been processed successfully');
				$product->load($productID);
			} elseif ($imagesProcessed === false) {
				addError('There was an error while processing the product images');
			}
			if (!haveErrors()) {
				addSuccess($product->get('name').' was updated successfully');
				editProduct($productID);
			} else {
				$controller = new productsController;
				$template = new template;
				$template->assignClean('merchantInfo', $merchantInfo);
				$template->assignClean('product', $product->fetchArray());
				$template->assignClean('tags', $tags);
				$template->assignClean('productTags', productsController::getProductTags());
				$template->assignClean('quantity', getPost('quantity', 'integer'));
				$template->assignClean('availabilityOptions', $controller->getOptions('availability'));
				$template->assignClean('propertyMenuItem', getRequest('propertyMenuItem'));
				$template->assignClean('mode', 'edit');
				$template->getMessages();
				$template->display('merchant/productEdit.htm');
			}
		} else {
			addError('The product was not found');
			inventoryAdmin();
		}
	} // function updateProduct

	/**
	 *  Perform specific update on multiple records
	 *  Args: none
	 *  Return: none
	 */
	function quickUpdate() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$records = getRequest('records');
		foreach ($records as $key => $val) {
			if (!validNumber($val, 'integer')) {
				unset($records[$key]);
				addError('One or more record IDs was invalid');
			}
		}
		if ($records) {
			$sql = "SELECT `productID` FROM `products` WHERE `memberID` = '".$merchantInfo['id']."' AND `productID` IN ('".implode("', '", $records)."')";
			$result = query($sql);
			if ($result->rowCount > 0) {
				$validRecords = array();
				while ($row = $result->fetchRow()) {
					$validRecords[] = $row['productID'];
				}
				$records = $validRecords;
			} else {
				$records = false;
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
				default:
					addError('Invalid action');
					break;
			}
		} else {
			addError('There are no records to update');
		}
		inventoryAdmin();
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