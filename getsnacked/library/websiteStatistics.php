<?

	class websiteStatistics {
		public static $dbh;
		public static $startDate;
		public static $endDate;

		/**
		 *  Initialize resources and references
		 *  Args: (str) start date, (str) end date
		 *  Returns: none
		 */
		public static function initialize($startDate, $endDate) {
			self::$dbh = database::getInstance();
			self::$startDate = $startDate;
			self::$endDate = $endDate;
		} // function initialize

		/**
		 *  Retrieve top converting affiliate data
		 *  Args: (int) record limit
		 *  Returns: (array) array of top converting affiliate data
		 */
		public static function getBestAffiliates($limit = 10) {
			$sql = "SELECT 
						`a`.`ID`, `a`.`subID`, `a`.`offerID`, `a`.`campaignID`, 
						`a`.`payoutID`, SUM(`a`.`hits`) AS `totalHits`, 
						SUM(`a`.`uniqueHits`) AS `totalUniques`, 
						SUM(`a`.`conversions`) AS `totalConversions`, `b`.`email`, `c`.`company` 
					FROM `affiliateTracking` `a` 
					LEFT JOIN `members` `b` ON (`a`.`ID` = `b`.`memberID`) 
					LEFT JOIN `memberBusinessInfo` `c` USING (`memberID`) 
					WHERE `a`.`conversions` > 0 
					AND `a`.`date` BETWEEN '".self::$startDate."' AND '".self::$endDate."' 
					GROUP BY `a`.`id`, `a`.`subID`, `a`.`offerID`, `a`.`campaignID`, `a`.`payOutID` 
					ORDER BY `totalConversions` DESC 
					LIMIT ".$limit;
			$result = self::$dbh->query($sql);
			return $result->fetchAll();
		} // function getBestAffiliates

		/**
		 *  Retrieve top converting customer data
		 *  Args: (int) record limit
		 *  Returns: (array) array of top converting customer data
		 */
		public static function getBestCustomers($limit = 10) {
			$sql = "SELECT 
						`a`.`ID`, `a`.`subID`, `a`.`offerID`, `a`.`campaignID`, 
						`a`.`payoutID`, SUM(`a`.`hits`) AS `totalHits`, 
						SUM(`a`.`uniqueHits`) AS `totalUniques`, 
						SUM(`a`.`conversions`) AS `totalConversions`, `b`.`email` 
					FROM `tracking` `a` 
					LEFT JOIN `members` `b` ON (`a`.`ID` = `b`.`memberID`) 
					WHERE `a`.`conversions` > 0 
					AND `a`.`date` BETWEEN '".self::$startDate."' AND '".self::$endDate."' 
					GROUP BY `a`.`id`, `a`.`subID`, `a`.`offerID`, `a`.`campaignID`, `a`.`payOutID` 
					ORDER BY `totalConversions` DESC 
					LIMIT ".$limit;
			$result = self::$dbh->query($sql);
			return $result->fetchAll();
		} // function getBestCustomers

		/**
		 *  Retrieve top selling products
		 *  Args: (int) record limit
		 *  Returns: (array) array of top selling products data
		 */
		public static function getBestProducts($limit = 10) {
			$sql = "SELECT 
						SUM(`a`.`orders`) AS `totalOrders`, `b`.`name`, 
						`a`.`productID`, `b`.`sku` 
					FROM `productTrack` `a` 
					JOIN `products` `b` USING (`productID`) 
					WHERE `a`.`dateOrdered` BETWEEN '".self::$startDate."' AND '".self::$endDate."' 
					GROUP BY `a`.`productID` 
					ORDER BY `totalOrders` DESC 
					LIMIT ".$limit;
			$result = self::$dbh->query($sql);
			return $result->fetchAll();
		} // function getBestProducts

		/**
		 *  Retrieve top selling packages
		 *  Args: (int) record limit
		 *  Returns: (array) array of top selling package data
		 */
		public static function getBestPackages($limit = 10) {
			$sql = "SELECT 
						SUM(`a`.`orders`) AS `totalOrders`, 
						`a`.`packageID`, `b`.`name` 
					FROM `packageTrack` `a` 
					JOIN `packages` `b` USING (`packageID`) 
					WHERE `a`.`dateOrdered` BETWEEN '".self::$startDate."' AND '".self::$endDate."' 
					GROUP BY `a`.`packageID` 
					ORDER BY `totalOrders` 
					DESC LIMIT ".$limit;
			$result = self::$dbh->query($sql);
			return $result->fetchAll();
		} // function getBestPackages
	} // class websiteStatistics

?>