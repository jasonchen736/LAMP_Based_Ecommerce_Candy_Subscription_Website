<?

	$segment = getRequest('q', 'alphanum');
	$returnLimit = getRequest('limit', 'integer') ? getRequest('limit', 'integer') : 10;

	$type = getRequest('type');

	switch($type) {
		case 'basicPackageInfo':
			$result = query("SELECT `packageID`, IF(`name` != '', `name`, 'Unnamed Package') AS `name` FROM `packages` WHERE `name` LIKE '".$segment."%' OR `packageID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'shippingMethod':
			$result = query("SELECT `shippingOptionID`, `name` FROM `shippingOptions` WHERE `name` LIKE '".$segment."%' OR `shippingOptionID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'customerSearch':
			$result = query("SELECT `memberID`, `email` FROM `members` WHERE `email` LIKE '".$segment."%' OR `memberID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'websiteSearch':
			$result = query("SELECT `siteID`, `siteName` FROM `siteRegistry` WHERE `siteName` LIKE '".$segment."%' OR `siteID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'merchantSearch':
			$result = query("SELECT `a`.`memberID`, `b`.`company`, `a`.`email` FROM `members` `a` JOIN `memberBusinessInfo` `b` USING (`memberID`) WHERE `a`.`email` LIKE '".$segment."%' OR `a`.`memberID` LIKE '".$segment."%' OR `b`.`company` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'productInfo':
			$result = query("SELECT `productID`, `cost`, `name`, `availability` FROM `products` WHERE `name` LIKE '".$segment."%' OR `productID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'packageInfo':
			$result = query("SELECT `packageID`, `cost`, `name`, `availability` FROM `packages` WHERE `name` LIKE '".$segment."%' OR `packageID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'campaignInfo':
			$result = query("SELECT `campaignID`, `type`, `name`, `availability` FROM `campaigns` WHERE `name` LIKE '".$segment."%' OR `campaignID` LIKE '".$segment."%' LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'state':
			$result = query("SELECT `stateCode`, `stateName` FROM `stateCodes` WHERE `stateName` LIKE '".$segment."%' OR `stateCode` LIKE '".$segment."%'");
			if ($result->rowCount) {
				$returnVals = $result->fetchAll();
			}
			break;
		case 'brand':
			$result = query("SELECT `brand` FROM `brands` WHERE `brand` LIKE '".$segment."%'");
			if ($result->rowCount) {
				$returnVals = array();
				while ($row = $result->fetchRow()) {
					$returnVals[] = $row['brand'];
				}
			}
			break;
		default:
			exit;
			break;
	}

	if (isset($returnVals) && is_array($returnVals)) {
		$returnCount = 0;
		foreach ($returnVals as $val) {
			switch($type) {
				case 'productInfo':
				case 'packageInfo':
					echo 'id-cost-name-availability|';
					break;
				case 'campaignInfo':
					echo 'id-type-name-availability|';
					break;
				case 'basicPackageInfo':
				case 'shippingMethod':
				case 'customerSearch':
				case 'websiteSearch':
					echo 'id-name|';
					break;
				case 'merchantSearch':
					echo 'id-name-email|';
					break;
				default:
					break;
			}
			if (!is_array($val)) {
				echo $val."\n";
			} else {
				foreach ($val as $item) {
					echo $item."|";
				}
				echo "\n";
			}
			++$returnCount;
			if ($returnCount >= $returnLimit) {
				break;
			}
		}
	}

?>