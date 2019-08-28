<?

	require_once 'admin.php';

	$actions = array(
		'productTagsAdmin',
		'addTag',
		'removeTag'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		productTagsAdmin();
	}

	/**
	 *  Show the product tags admin, default action
	 *  Args: none
	 *  Return: none
	 */
	function productTagsAdmin() {
		$controller = new productTagsController;
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
		$template->assignClean('search', $controller->getSearchValues());
		$template->assignClean('querystring', $controller->retrieveQueryString());
		$template->getMessages();
		$template->display('admin/productTagsAdmin.htm');	
	} // function productTagsAdmin

	/**
	 *  Add product tags
	 *  Args: none
	 *  Return: none
	 */
	function addTag() {
		$tag = getRequest('tag', 'alphanum');
		if ($tag) {
			$dbh = database::getInstance();
			$sql = "SELECT `tagID` FROM `productTags` WHERE `tag` = '".prep($tag)."'";
			$result = $dbh->query($sql);
			if ($result->rowCount > 0) {
				addError('That tag already exists');
			} else {
				$sql = "INSERT INTO `productTags` (`tag`) VALUES ('".prep(strtoupper($tag))."')";
				$result = $dbh->query($sql);
				if ($result->rowCount > 0) {
					addSuccess('Tag added ('.$tag.')');
				} else {
					addError('An error occurred while adding the tag');
				}
			}
		} else {
			addError('Please enter a valid tag');
		}
		productTagsAdmin();
	} // function addTag

	/**
	 *  Remove product tags
	 *  Args: none
	 *  Return: none
	 */
	function removeTag() {
		$tagID = getRequest('tag', 'alphanum');
		if ($tagID) {
			$dbh = database::getInstance();
			$sql = "SELECT `tagID` FROM `productTags` WHERE `tagID` = '".prep($tagID)."'";
			$result = $dbh->query($sql);
			if ($result->rowCount > 0) {
				$row = $result->fetchRow();
				$tagID = $row['tagID'];
				$sql = "DELETE FROM `productTags` WHERE `tagID` = '".$tagID."'";
				$result = $dbh->query($sql);
				if ($result->rowCount > 0) {
					$sql = "DELETE FROM `productTagMap` WHERE `tagID` = '".$tagID."'";
					$result = $dbh->query($sql);
					$sql = "DELETE FROM `packageTagMap` WHERE `tagID` = '".$tagID."'";
					$result = $dbh->query($sql);
					addSuccess('Tag removed');
				} else {
					addError('An error occurred while removing the tag');
				}
			} else {
				addError('Tag does not exist');
			}
		} else {
			addError('Invalid tag');
		}
		productTagsAdmin();
	} // function removeTag

?>