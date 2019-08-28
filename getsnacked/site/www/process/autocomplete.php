<?

	$segment = getRequest('q', 'alphanum');
	$returnLimit = 10;

	$type = getRequest('type');

	switch($type) {
		case 'sku':
			$result = query("SELECT `sku` FROM `products` WHERE `sku` LIKE '".$segment."%' UNION SELECT `sku` FROM `packages` WHERE `sku` LIKE '".$segment."%' ORDER BY `sku` ASC LIMIT ".$returnLimit);
			if ($result->rowCount) {
				$returnVals = array();
				while ($row = $result->fetchRow()) {
					$returnVals[] = $row['sku'];
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
			echo $val."\n";
			++$returnCount;
			if ($returnCount >= $returnLimit) {
				break;
			}
		}
	}

?>