<?

	/**
	 *   Navigation path should be in the natural progression order
	 *   array(key => url, name, protocol)
	 */
	class checkoutPath {
		// array of completed page keys
		public static $completed;
		// array(page id => status [incomplete, completed, current, next], page url, page name)
		public static $pathData;
		// the next page on the checkout path
		public static $nextStep;

		/**
		 *  Add to completed pages session array
		 *  Args: (str) page url, (array) navigation path
		 *  Return: none
		 */
		public static function setCompleted($page = false, $path = false) {
			assertArray($_SESSION['checkoutPath::completed']);
			if (!$page) {
				$page = $_SERVER['PHP_SELF'];
			}
			if (!$path) {
				$path = systemSettings::get('CHECKOUTPATH');
			}
			assertArray($path);
			foreach ($path as $key => $val) {
				if ($page == $val['url']) {
					addToArray($_SESSION['checkoutPath::completed'], $key);
				}
			}
		} // function setCompleted

		/**
		 *  Remove from completed pages session array
		 *  Args: (str) page url, (array) navigation path
		 *  Return: none
		 */
		public static function removeCompleted($page = false, $path = false) {
			assertArray($_SESSION['checkoutPath::completed']);
			if (!$page) {
				$page = $_SERVER['PHP_SELF'];
			}
			if (!$path) {
				$path = systemSettings::get('CHECKOUTPATH');
			}
			assertArray($path);
			foreach ($path as $key => $val) {
				if ($page == $val['url']) {
					removeFromArray($_SESSION['checkoutPath::completed'], $key);
				}
			}
		} // function removeCompleted

		/**
		 *  Clear visited pages session array
		 *  Args: none
		 *  Return: none
		 */
		public static function clearCompleted() {
			unset($_SESSION['checkoutPath::completed']);
		} // function clearCompleted

		/**
		 *  Calculates and sets navigation variables
		 *  Args: (array) navigation path
		 *  Return: none
		 */
		public static function mapLocation($path) {
			assertArray($path);
			$completed = getSession('checkoutPath::completed');
			assertArray($completed);
			$currentPage = $_SERVER['PHP_SELF'];
			// determine current page key
			$current = false;
			$next = false;
			$incomplete = array();
			foreach ($path as $key => $val) {
				if ($val['url'] == $currentPage) {
					$current = $key;
				} elseif (!in_array($key, $completed)) {
					if ($next === false) {
						$next = $key;
					} else {
						$incomplete[] = $key;
					}
				}
			}
			$lastStep = end($path);
			if ($current !== false && !in_array($current, $completed) && ($next === false || $currentPage != $lastStep['url'])) {
				// on path, current page not completed, set next step to current page
				$nextPage = $currentPage;
				$protocol = $path[$current]['protocol'];
			} elseif ($next !== false) {
				// some pages are incomplete
				$nextPage = $path[$next]['url'];
				$protocol = $path[$next]['protocol'];
			} else {
				// all pages completed
				//   this should only happen if navigation path is empty
				$nextPage = $currentPage;
				$protocol = 'http';
			}
			$nextStep = $protocol.'://'.$_SERVER['HTTP_HOST'].$nextPage;
			self::$nextStep = &$nextStep;
			// create order path
			$pathData = array();
			foreach ($completed as $key) {
				$pathData[$key] = array('status' => 'completed', 'url' => $path[$key]['url'], 'name' => $path[$key]['name']);
			}
			// current may override completed path status
			$pathData[$current] = array('status' => 'current', 'url' => $path[$current]['url'], 'name' => $path[$current]['name']);
			if ($next !== false) {
				$pathData[$next] = array('status' => 'next', 'url' => $path[$next]['url'], 'name' => $path[$next]['name']);
			}
			foreach ($incomplete as $key => $val) {
				$pathData[$val] = array('status' => 'incomplete', 'url' => $path[$val]['url'], 'name' => $path[$val]['name']);
			}
			self::$pathData = &$pathData;
			// if on the last path page, but there are still incomplete steps, then the user tried to jump directly to the last page
			//   redirect to next step
			if ($currentPage == $pathData[count($pathData) - 1]['url'] && $next !== false) {
				redirect($nextStep);
			}
		} // function mapLocation

		/**
		 *  Set access status of the next step on the navigation path and return path data
		 *    this method will determine whether the next location will actually be accessible
		 *    on a navigation trail
		 *  Args: none
		 *  Return: (array) order path data
		 */
		public static function getPath() {
			$path = self::$pathData;
			assertArray($path);
			// determine whether the next step in the navigation path should have status incomplete or next
			//   if current page has not been completed, next page should not be available
			//   if current page has already been completed, next page should be available
			$completed = getSession('checkoutPath::completed');
			assertArray($completed);
			foreach ($path as $key => $val) {
				if ($val['status'] == 'current' && !in_array($key, $completed)) {
					// next page is set to incomplete
					foreach ($path as $key => &$val) {
						if ($val['status'] == 'next') {
							$val['status'] = 'incomplete';
						}
					}
					break;
				}
			}
			return $path;
		} // function getPath

		/**
		 *  Check whether current page is on the checkout path
		 *  Args: (array) navigation path
		 *  Return: (boolean) on path
		 */
		public static function onPath($path = false) {
			if (!$path) {
				$path = systemSettings::get('CHECKOUTPATH');
			}
			foreach ($path as $val) {
				if ($_SERVER['PHP_SELF'] == $val['url']) {
					return true;
				}
			}
			return false;
		} // function onPath

		/**
		 *  Return all vars necessary for template path setup
		 *  Args: none
		 *  Return: (array) path setup
		 */
		public static function getSetup() {
			$setup = array();
			$setup['orderPath'] = self::getPath();
			$setup['lastNode'] = count($setup['orderPath']) - 1;
			$setup['nextStep'] = self::$nextStep;
			return $setup;
		} // function getSetup
	} // class checkoutPath

?>