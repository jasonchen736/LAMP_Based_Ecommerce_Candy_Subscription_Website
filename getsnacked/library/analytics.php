<?

	global $pageID;

	// this page logs user footprints throughout the site for web analytics
	$dbh = new database;
	// execute hits query for individual page hits, if its a new record, create new record
	$table = 'pageHits';
	$dbh->query("UPDATE `$table` SET `hits` = `hits` + 1 WHERE `pageID` = '".prepDB($pageID)."'");
	if (!$dbh->affected_rows) $dbh->query("INSERT INTO `$db`.`$table` (`pageID`, `uniqueHits`,`hits`) VALUES ('".prepDB($pageID)."', '1', '1')");

	// depending on action taken on previous page, database will be updated with additional information aside from page footprint
	$pageActions = array(
		'submitInfo'		=> 'si',	// regular submit
		'productSearch'		=> 'ps',	// product search
		'removeItem'		=> 'ri',	// remove item from cart
		'removePromotion'	=> 'rp',	// remove promotion from array
		'editCart'			=> 'ed',	// edit shopping cart
		'checkOut'			=> 'co'		// check out
	);

	$thisPageAction = $pageID;
	foreach ($_POST as $field => $val) {
		if (array_key_exists($field, $pageActions)) $thisPageAction .= $pageActions[$field];
	}

	// if new user, record traffic source (id/sid) and landing page, otherwise append to user browse path and update existing record
	$table = 'UserPaths';
	if (!$_SESSION['user']->pathID) {
		$_SESSION['user']->userPath = clean($thisPageAction);
		$analyticsFields = array(
			'ID'			=> $_SESSION['user']->ID,
			'subID'			=> $_SESSION['user']->subID,
			'offerID'		=> $_SESSION['user']->offerID,
			'campaignID'	=> $_SESSION['user']->campaignID,
			'path'			=> $_SESSION['user']->userPath
		);
		$insertFields = '';
		$insertValues = '';
		foreach ($analyticsFields as $field => $val) {
			$insertFields .= "`".prepDB($field)."`, ";
			$insertValues .= "'".prepDB($val)."', ";
		}
		$insertFields = substr($insertFields, 0, -2);
		$insertValues = substr($insertValues, 0, -2);
		$dbh->query("INSERT INTO `$table` (".$insertFields.") VALUES (".$insertValues.")");
		$_SESSION['user']->pathID = $dbh->insertID;
	} else {
		$_SESSION['user']->userPath .= ";".clean($thisPageAction);
		$dbh->query("UPDATE `$table` SET `path` = '".prepDB($_SESSION['user']->userPath)."' WHERE `pathID` = '".$_SESSION['user']->pathID."'");
	}

?>