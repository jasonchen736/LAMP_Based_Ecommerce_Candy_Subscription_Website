<?

	/**
	 *  All searches are site specific
	 */
	class productSearch {

		/**
		 *  Search products and packages by sku
		 *  Args: (array) skus
		 *  Return: (array) details of all products and packages found
		 */
		public static function skuSearch($skus) {
			assertArray($skus);
			$products = productsController::skuSearch($skus);
			$packages = packagesController::skuSearch($skus);
			$items = array();
			$index = 0;
			foreach ($products as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'product';
				$items[$index]['itemID'] = $val['productID'];
				++$index;
			}
			foreach ($packages as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'package';
				$items[$index]['itemID'] = $val['packageID'];
				++$index;
			}
			if (!empty($items)) {
				usort($items, array('productSearch', 'usortBySortWeight'));
			}
			return $items;
		} // function skuSearch

		/**
		 *  Search products and packages by brand
		 *  Args: (array) brands
		 *  Return: (array) details of all products and packages found
		 */
		public static function brandSearch($brands) {
			assertArray($brands);
			$products = productsController::brandSearch($brands);
			$packages = packagesController::brandSearch($brands);
			$items = array();
			$index = 0;
			foreach ($products as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'product';
				$items[$index]['itemID'] = $val['productID'];
				++$index;
			}
			foreach ($packages as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'package';
				$items[$index]['itemID'] = $val['packageID'];
				++$index;
			}
			if (!empty($items)) {
				usort($items, array('productSearch', 'usortBySortWeight'));
			}
			return $items;
		} // function brandSearch

		/**
		 *  Search products and packages by tags
		 *  Args: (array) product tags
		 *  Return: (array) details of all products and packages found
		 */
		public static function tagSearch($tags) {
			assertArray($tags);
			$products = productsController::tagSearch($tags);
			$packages = packagesController::tagSearch($tags);
			$items = array();
			$index = 0;
			foreach ($products as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'product';
				$items[$index]['itemID'] = $val['productID'];
				++$index;
			}
			foreach ($packages as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'package';
				$items[$index]['itemID'] = $val['packageID'];
				++$index;
			}
			if (!empty($items)) {
				usort($items, array('productSearch', 'usortBySortWeight'));
			}
			return $items;
		} // function tagSearch

		/**
		 *  Search products and packages by keywords (match in name and description)
		 *  Args: (array) keywords
		 *  Return: (array) details of all products and packages found
		 */
		public static function keywordSearch($keywords) {
			assertArray($keywords);
			$products = productsController::keywordSearch($keywords);
			$packages = packagesController::keywordSearch($keywords);
			$items = array();
			$index = 0;
			foreach ($products as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'product';
				$items[$index]['itemID'] = $val['productID'];
				++$index;
			}
			foreach ($packages as $key => $val) {
				$items[$index] = $val;
				$items[$index]['itemType'] = 'package';
				$items[$index]['itemID'] = $val['packageID'];
				++$index;
			}
			if (!empty($items)) {
				usort($items, array('productSearch', 'usortByMatches'));
			}
			// log search
			// keywords entered are exploded by white space
			//   if there is more than 1 segment, the entire search term is appended to the array
			//   remove the last term in this case so we can track accurately
			if ($end = count($keywords) - 1) {
				unset($keywords[$end]);
			}
			$sql = "INSERT INTO `searchTrack` (`searchTerm`, `siteID`, `searches`, `date`) VALUES ('".prep(implode(' ', $keywords))."', '".systemSettings::get('SITEID')."', 1, CURDATE()) ON DUPLICATE KEY UPDATE `searches` = `searches` + 1";
			query($sql);
			return $items;
		} // function keywordSearch

		/**
		 *  Used for usorting multidimensional array by number of matches greatest to least
		 *  Args: (array) arguments are mutidimensional arrays with index 1 used for sorting
		 *  Return: none
		 */
		public static function usortByMatches($x, $y) {
			if ($x[1] == $y[1]) return 0;
			elseif ($x[1] > $y[1]) return -1;
			else return 1;
		} // function usortByMatches

		/**
		 *  Used for usorting multidimensional array by each item's sortWeight index (ascending)
		 *  Args: (array) arguments are mutidimensional arrays with index sortWeight used for sorting
		 *  Return: none
		 */
		public static function usortBySortWeight($x, $y) {
			if ($x['sortWeight'] == $y['sortWeight']) return 0;
			elseif ($x['sortWeight'] < $y['sortWeight']) return -1;
			else return 1;
		} // function usortBySortWeight

		/**
		 *  Retrieve all brands from the database
		 *  Args: none
		 *  Return: (array) brands
		 */
		public static function getBrands() {
			$sql = "SELECT `brand` FROM `brands` ORDER BY `brand` ASC";
			$result = query($sql);
			$brands = array();
			if ($result->rowCount > 0) {
				while ($row = $result->fetchRow()) {
					$brands[$row['brand']] = $row['brand'];
				}
			}
			return $brands;
		} // function getBrands
	} // class productSearch

?>