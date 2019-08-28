<?

	class category extends activeRecord {
		// active record table
		protected $table = 'categories';
		// existing auto increment field
		protected $autoincrement = 'categoryID';
		// array unique id fields
		protected $idFields = array(
			'categoryID'
		);
		// field array
		//   array(friendly name => array(field name, field type, min chars, max chars))
		protected $fields = array(
			'categoryID'   => array('categoryID', 'integer', 0, 10),
			'tagID'        => array('tagID', 'integer', 1, 11),
			'lft'          => array('lft', 'integer', 1, 10),
			'rgt'          => array('rgt', 'integer', 1, 10),
			'availability' => array('availability', 'alphanum', 1, 20),
			'siteID'       => array('siteID', 'integer', 1, 10)
		);

		/**
		 *  Set defaults for saving
		 *  Args: none
		 *  Return: none
		 */
		public function assertSaveDefaults() {
			$this->set('categoryID', NULL, false);
		} // function assertSaveDefaults

		/**
		 *  Insert a category node
		 *  Args: (str) category name, (int) position to insert after, (str) category availability
		 *  Return: (boolean) success
		 */
		static function insert($category, $position, $availability) {
			$dbh = database::getInstance();
			$siteID = systemSettings::get('SITEID');
			$sql = "SELECT `siteID` FROM `categories` WHERE `siteID` = '".$siteID."'";
			$result = $dbh->query($sql);
			if ($result->rowCount == 0) {
				$sql = "SELECT `rgt` FROM `categories` ORDER BY `rgt` DESC LIMIT 1";
				$result = $dbh->query($sql);
				if ($result->rowCount < 1) {
					$root = 0;
				} else {
					$row = $result->fetchRows();
					$root = $row['rgt'];
				}
				$sql = "INSERT INTO `categories` (`tagID`, `lft`, `rgt`, `availability`, `siteID`) VALUES ('*SITE ROOT', ".($root + 1).", ".($root + 2).", 'none', '".$siteID."')";
				$dbh->query($sql);
				$position = $root + 1;
			}
			$sql = "UPDATE `categories` SET `rgt` = `rgt` + 2 WHERE `rgt` > '".$position."'";
			$dbh->query($sql);
			$sql = "UPDATE `categories` SET `lft` = `lft` + 2 WHERE `lft` > '".$position."'";
			$dbh->query($sql);
			$sql = "INSERT INTO `categories` (`tagID`, `lft`, `rgt`, `availability`, `siteID`) VALUES ('".$categoryID."', '".($position + 1)."', '".($position + 2)."', '".$availability."', '".$siteID."')";
			$dbh->query($sql);
		} // function insertCategory

       public static function display_tree($root, $applyStatus = false) {
               $siteID = 1;
               $result = mysql_query('SELECT lft, rgt FROM t WHERE lft ="'.$root.'";');
               $row = mysql_fetch_array($result);
               $result = mysql_query("SELECT tid, tag, lft, rgt, status FROM t
WHERE lft BETWEEN ".$row['lft']." AND ".$row['rgt']." AND site =
".$siteID." ORDER BY lft ASC");
               $tree = array();
               $path = array();
               $set = " = array('tag' => \$row['tag'], 'sub' => array());";
               $rgtStack = array();
               $row = mysql_fetch_array($result);
               array_push($path, 'sub', $row['tid']);
               array_push($rgtStack, $row['rgt']);
               $rgtLast = $row['rgt'];
               if ($row['status'] == 'on' || $row['tag'] == '*SITE ROOT') {
                       $pathStr = "\$tree['".implode("']['", $path)."']".$set;
                       eval($pathStr);
               }
               if ($applyStatus) {
                       $branch = array(
                               'status' => $row['tag'] == '*SITE ROOT' ? 'on' : $row['status'],
                               'rgt' => $row['rgt']
                       );
                       while ($row = mysql_fetch_array($result)) {
                               if ($row['lft'] < $rgtLast) {
                                       array_push($path, 'sub', $row['tid']);
                                       if ($branch['status'] == 'on' && $row['status'] == 'off') {
                                               $branch['status'] = 'off';
                                               $branch['rgt'] = $row['rgt'];
                                       }
                               } else {
                                       $rgt = $rgtLast;
                                       while ($row['lft'] > $rgt) {
                                               array_pop($path);
                                               array_pop($path);
                                               array_pop($rgtStack);
                                               $rgt = end($rgtStack);
                                       }
                                       array_push($path, 'sub', $row['tid']);
                                       if ($row['lft'] > $branch['rgt']) {
                                               if ($row['status'] == 'off') {
                                                       $branch['status'] = 'off';
                                                       $branch['rgt'] = $row['rgt'];
                                               } else {
                                                       $branch['status'] = $row['status'];
                                               }
                                       } elseif ($branch['status'] == 'on' && $row['status'] == 'off') {
                                               $branch['status'] = 'off';
                                               $branch['rgt'] = $row['rgt'];
                                       }
                               }
                               if ($branch['status'] == 'on') {
                                       $pathStr = "\$tree['".implode("']['", $path)."']".$set;
                                       eval($pathStr);
                               }
                               $rgtLast = $row['rgt'];
                               array_push($rgtStack, $row['rgt']);
                       }
               } else {
                       while ($row = mysql_fetch_array($result)) {
                               if ($row['lft'] < $rgtLast) {
                                       array_push($path, 'sub', $row['tid']);
                               } else {
                                       $rgt = $rgtLast;;
                                       while ($row['lft'] > $rgt) {
                                               array_pop($path);
                                               array_pop($path);
                                               array_pop($rgtStack);
                                               $rgt = end($rgtStack);
                                       }
                                       array_push($path, 'sub', $row['tid']);
                               }
                               $pathStr = "\$tree['".implode("']['", $path)."']".$set;
                               eval($pathStr);
                               $rgtLast = $row['rgt'];
                               array_push($rgtStack, $row['rgt']);
                       }
               }
               return $tree;
       }
	} // class category

?>
<?

mysql_query("TRUNCATE t");

test::insert('1', 1, 'on');
test::insert('1/1', 2, 'off');
test::insert('1/1/1', 3, 'on');
test::insert('2', 7, 'on');
test::insert('2/1', 8, 'on');
test::insert('2/2', 10, 'on');
test::insert('2/2/1', 11, 'off');

print_r(test::display_tree(1));
print_r(test::display_tree(1, true));

?>