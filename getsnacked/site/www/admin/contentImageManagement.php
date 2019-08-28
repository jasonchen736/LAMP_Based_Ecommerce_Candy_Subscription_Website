<?

	require_once 'admin.php';

	$actions = array(
		'contentImagesAdmin',
		'addContentImages',
		'uploadContentImages',
		'editContentImage',
		'renameContentImage',
		'replaceContentImage',
		'removeContentImage',
		'removeContentImages',
		'preview'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		contentImagesAdmin();
	}

	/**
	 *  Show the content images admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function contentImagesAdmin() {
		$controller = new contentImageIndexController;
		$records = $controller->performSearch();
		$recordsFound = $controller->countRecordsFound();
		list($start, $show, $page) = $controller->getTableLocation();
		$template = new template;
		$template->assignClean('records', $records);
		$template->assignClean('recordsFound', $recordsFound);
		$template->assignClean('show', $show);
		$template->assignClean('page', $page);
		$template->assignClean('start', $start);
		$template->assignClean('pages', ceil($recordsFound / $show));
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->getMessages();
		$template->display('admin/contentImagesAdmin.htm');
	} // function contentImagesAdmin

	/**
	 *  Add content images section
	 *  Args: none
	 *  Return: none
	 */
	function addContentImages() {
		$template = new template;
		$template->getMessages();
		$template->display('admin/addContentImages.htm');
	} // function addContentImages

	/**
	 *  Upload content images
	 *  Args: none
	 *  Return: none
	 */
	function uploadContentImages() {
		$images = getRequest('uploadName');
		foreach ($images as $key => $val) {
			$image = new contentImageIndex();
			$name = NULL;
			if ($val) {
				$name = $val;
			} elseif (isset($_FILES['uploadImage'.$key]['name'])) {
				$name = $_FILES['uploadImage'.$key]['name'];
			}
			// convert to gif
			if (!preg_match('/\.gif$/i', $name)) {
				$name = preg_replace('/\.[^\.]*$/', '', $name);
				$name = $name.'.gif';
			}
			$image->set('image', $name);
			if ($image->get('image')) {
				if ($image->save('uploadImage'.$key)) {
					addSuccess($image->get('image').' has been uploaded successfully');
				} else {
					addError('Image '.$image->get('image').' was not uploaded successfully');
				}
			} else {
				addError('One or more images may not have been uploaded');
			}
		}
		redirect('/admin/contentImageManagement');
	} // function uploadContentImages

	/**
	 *  Edit content section
	 *  Args: (int) image id
	 *  Return: none
	 */
	function editContentImage($imageID = false) {
		if (!$imageID) {
			$imageID = getRequest('imageID', 'integer');
		}
		$image = new contentImageIndex($imageID);
		if ($image->exists()) {
			$template = new template;
			$template->assignClean('image', $image->fetchArray());
			$template->getMessages();
			$template->display('admin/contentImageEdit.htm');
		} else {
			addError('Image does not exist');
			contentImagesAdmin();
		}
	} // function editContentImage

	/**
	 *  Rename a content image
	 *  Args: none
	 *  Return: none
	 */
	function renameContentImage() {
		$imageID = getRequest('imageID', 'integer');
		$image = new contentImageIndex($imageID);
		if ($image->exists()) {
			$name = getRequest('name', 'file');
			if ($name) {
				// convert to gif
				if (!preg_match('/\.gif$/i', $name)) {
					$name = preg_replace('/\.[^\.]*$/', '', $name);
					$name = $name.'.gif';
				}
				$original = $image->get('image');
				if ($image->rename($name)) {
					addSuccess($original.' has been successfully renamed to '.$name);
					editContentImage($image->getID());
				} else {
					addError($original.' could not be renamed to '.$name);
					editContentImage($image->getID());
				}
			} else {
				addError('Invalid file name');
				editContentImage($image->getID());
			}
		} else {
			addError('Image not found');
			redirect('/admin/contentImageManagement');
		}
	} // function renameContentImage

	/**
	 *  Replace a content image file
	 *  Args: none
	 *  Return: none
	 */
	function replaceContentImage() {
		$imageID = getRequest('imageID', 'integer');
		$image = new contentImageIndex($imageID);
		if ($image->exists()) {
			if ($image->replace('image')) {
				addSuccess('Image was successfully replaced');
				editContentImage($image->getID());
			} else {
				addError('There was an error while replacing the image');
				editContentImage($image->getID());
			}
		} else {
			addError('Image not found');
			redirect('/admin/contentImageManagement');
		}
	} // function replaceContentImage

	/**
	 *  Remove a content image record and file
	 *  Args: none
	 *  Return: none
	 */
	function removeContentImage() {
		$imageID = getRequest('imageID', 'integer');
		$image = new contentImageIndex($imageID);
		if ($image->exists()) {
			$name = $image->get('image');
			if ($image->delete()) {
				addSuccess($name.' has been successfully removed');
			} else {
				addError('There was an error while attempting to remove '.$name);
			}
		} else {
			addError('Image not found');
		}
		redirect('/admin/contentImageManagement');
	} // function removeContentImage

	/**
	 *  Remove a set of content image records and files
	 *  Args: none
	 *  Return: none
	 */
	function removeContentImages() {
		$images = getRequest('records');
		assertArray($images);
		if ($images) {
			foreach ($images as $imageID) {
				$imageID = clean($imageID, 'integer');
				$image = new contentImageIndex($imageID);
				if ($image->exists()) {
					$name = $image->get('image');
					if ($image->delete()) {
						addSuccess($name.' (ID: '.$imageID.') has been successfully removed');
					} else {
						addError('There was an error while attempting to remove '.$name.' (ID: '.$imageID.')');
					}
				} else {
					addError('Image ID '.$imageID.' not found');
				}
			}
		} else {
			addError('No images selected');
		}
		redirect('/admin/contentImageManagement');
	} // function removeContentImages

	/**
	 *  Display a content image file
	 *  Args: none
	 *  Return: none
	 */
	function preview() {
		$imageID = getRequest('imageID', 'integer');
		$image = new contentImageIndex($imageID);
		headers::sendNoCacheHeaders();
		if ($image->exists()) {
			echo '<img src="/images/content/'.$image->get('image').'" />';
		} else {
			echo 'Image not found';
		}
	} // function preview

?>