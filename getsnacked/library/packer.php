<?
	/**
	 * All packing item arrays are in the format of array(array(length, width, height), weight)
	 *   This includes items and containers
	 *     Item weight is the weight of the item
	 *     Container weight is the maximum weight a container may carry
	 */

	class packer {
		// all items to pack
		//   this is an array of item specification arrays: array(array(length, width, height), weight)
		public static $items;
		// available containers
		//   this is an array of container specification arrays: array(array(length, width, height), max weight)
		public static $containers;
		// array of successfully packed items
		public static $packed;
		// array of packed containers
		//   array containing item arrays and additional container index referring to a container array
		//   and a weight index indicating total weight of packed container
		public static $packages;
		// array of packed containers used as temporary storage while calculating optimal container packing configurations
		public static $subPacked;

		/**
		 *  Reset packing values
		 *  Args: none
		 *  Return: none
		 */
		public static function reset() {
			self::$packed = array();
			self::$packages = array();
			self::$subPacked = array();
		} // function reset

		/**
		 *  Pack an array of items into available containers, see item and container array formats above
		 *    this algorithm does not reclaim unused spaces in a packed sub container
		 *    see functions findRemainingSpaces and recalculateRemainingSpace for reference
		 *  Args: (array) array of items, (array) array of containers
		 *  Return: none
		 */
		public static function packItems($items, $containers) {
			self::reset();
			self::$items = self::sortDimensions($items, 'bigfirst');
			self::$containers = self::sortDimensions($containers, 'smallfirst');
			$items = self::$items;
			$containers = self::$containers;
			// get index of the largest container
			$largest = array_keys($containers);
			$largest = $largest[count($largest) - 1];
			// start at biggest item
			foreach ($items as $index => $item) {
				if (!isset(self::$packed[$index])) {
					// find smallest container for the item
					$packed = self::findSmallestContainerFor($item, $containers);
					if ($packed[0] !== false) {
						if (count($items) > 1) {
							// try sub packing remaining items into current (smallest) container
							//   if in the end there are still items left and there are bigger containers
							//   then try packing into the next size container
							//   repeat until all items are finally packed or there are no more containers to try
							//   then determine and use the optimal sub packing configuration
							self::$subPacked = array();
							$remaining = self::findRemainingSpaces($item, $packed[1]);
							$itemsRemaining = $items;
							unset($itemsRemaining[$index]);
							if (!empty($remaining)) {
								// there is space in the container
								self::$subPacked[] = array(
									'container' => $packed[1],
									'weight' => 0
								);
								$subPackIndex = count(self::$subPacked) - 1;
								self::addSubPackedItem($index, $item, $packed[1], $subPackIndex);
								self::subPack($itemsRemaining, $remaining, $subPackIndex);
								$itemsRemaining = self::removePackedItems($items, self::$subPacked[$subPackIndex]);
							} else {
								// there is no space in the container
								self::$subPacked[] = array(
									'container' => $packed[1],
									'weight' => 0
								);
								$subPackIndex = count(self::$subPacked) - 1;
								self::addSubPackedItem($index, $item, $packed[1], $subPackIndex);
							}
							if (!empty($itemsRemaining)) {
								// if there are items left over from sub packing
								if ($packed[0] != $largest) {
									// try a bigger container
									$try = $packed[0];
									while ($try != $largest && !empty($itemsRemaining)) {
										// sub pack all remaining items
										$itemsRemaining = $items;
										unset($itemsRemaining[$index]);
										// remove all attempted containers
										$newSet = $containers;
										foreach ($newSet as $key => $val) {
											unset($newSet[$key]);
											if ($key == $try) {
												break;
											}
										}
										$newPacked = self::findSmallestContainerFor($item, $newSet);
										if ($newPacked[0] !== false) {
											$try = $newPacked[0];
											$remaining = self::findRemainingSpaces($item, $newPacked[1]);
											if (!empty($remaining)) {
												// there is space in the container
												self::$subPacked[] = array(
													'container' => $newPacked[1],
													'weight' => 0
												);
												$subPackIndex = count(self::$subPacked) - 1;
												self::addSubPackedItem($index, $item, $newPacked[1], $subPackIndex);
												self::subPack($itemsRemaining, $remaining, $subPackIndex);
												$itemsRemaining = self::removePackedItems($items, self::$subPacked[$subPackIndex]);
											}
										} else {
											// no more containers to attempt
											$try = $largest;
										}
									}
								}
							}
							// find the optimal sub packing configurations
							$items = self::useOptimalSubPackingConfiguration($items);
						} else {
							// all items packed, no need to sub pack
							self::addPackedItem($index, $item, $packed[1]);
							$items = self::removePackedItems($items, self::$packed);
							self::$packages[] = array(
								'container' => $packed[1],
								'weight' => $item[1],
								$index => array(
									'item' => $item,
									'in' => $packed[1]
								)
							);
						}
					} else {
						// could not find a container, pack with a custom container
						//   (items dimensions buffered by 1 inch on each side)
						self::addPackedItem($index, $item, $packed[1]);
						$items = self::removePackedItems($items, self::$packed);
						self::$packages[] = array(
							'container' => $packed[1],
							'weight' => $item[1],
							$index => array(
								'item' => $item,
								'in' => $packed[1]
							)
						);
					}
				}
			}
		} // function packItems

		/**
		 *  Sort the dimension arrays of a set of packing items (items or containers)
		 *  Return the packing items array sorted by volume
		 *  Args: (array) packing item array, (str) sort by volume direction
		 *  Return: (array) sorted packing item array
		 */
		public static function sortDimensions($dimensions, $direction) {
			foreach ($dimensions as &$array) {
				rsort($array[0]);
			}
			if ($direction == 'smallfirst') {
				uasort($dimensions, array('packer', 'sortByVolume_smallfirst'));
			} else {
				uasort($dimensions, array('packer', 'sortByVolume_bigfirst'));
			}
			return $dimensions;
		} // function sortDimensions

		/**
		 *  Usort algorithm to sort packing items by volume ascending
		 *  Args: (array) packing item, (array) packing item
		 *  Return: (integer) comparision result
		 */
		public static function sortByVolume_smallfirst($a, $b) {
			$aVolume = $a[0][0] * $a[0][1] * $a[0][2];
			$bVolume = $b[0][0] * $b[0][1] * $b[0][2];
			if ($aVolume == $bVolume) {
				return 0;
			} elseif ($aVolume > $bVolume) {
				return 1;
			} else {
				return -1;
			}
		} // function sortByVolume_smallfirst

		/**
		 *  Usort algorithm to sort packing items by volume descending
		 *  Args: (array) packing item, (array) packing item
		 *  Return: (integer) comparision result
		 */
		public static function sortByVolume_bigfirst($a, $b) {
			$aVolume = $a[0][0] * $a[0][1] * $a[0][2];
			$bVolume = $b[0][0] * $b[0][1] * $b[0][2];
			if ($aVolume == $bVolume) {
				return 0;
			} elseif ($aVolume > $bVolume) {
				return -1;
			} else {
				return 1;
			}
		} // function sortByVolume_bigfirst

		/**
		 *  Find the smallest container for item
		 *    The container's dimensions, volume and weight must exceed (not greater or equal to) the item's
		 *  Args: (array) packing item, (array) array of containers
		 *  Return: (array) container details
		 */
		public static function findSmallestContainerFor($item, $containers) {
			$volume = $item[0][0] * $item[0][1] * $item[0][2];
			foreach ($containers as $index => $container) {
				$containerVolume = $container[0][0] * $container[0][1] * $container[0][2];
				if ($container[0][0] > $item[0][0] && $container[0][1] > $item[0][1] && $container[0][2] > $item[0][2] && $containerVolume >= $volume && $container[1] > $item[1]) {
					return array($index, $container);
				}
			}
			return array(false, array(array($item[0][0] + 1, $item[0][1] + 1, $item[0][2] + 1), $item[1]));
		} // function findSmallestContainerFor

		/**
		 *  Calculate the remaining retangular spaces after an item has been placed in a container
		 *    Each space is calculated as its own container
		 *    Does not calculate for overlap
		 *  Args: (array) packing item, (array) array of containers
		 *  Return: (array) container details
		 */
		public static function findRemainingSpaces($item, $container) {
			$remaining = array(
				$container[0][0] - $item[0][0],
				$container[0][1] - $item[0][1],
				$container[0][2] - $item[0][2]
			);
			$volume = $remaining[0] * $remaining[1] * $remaining[2];
			if ($volume > 1) {
				$spaces = array();
				$spaces['length'] = array(array($container[0][0], $remaining[1], $container[0][2]), $container[1] - $item[1]);
				$spaces['width'] = array(array($remaining[0], $container[0][1], $container[0][2]), $container[1] - $item[1]);
				$spaces['height'] = array(array($container[0][0], $container[0][1], $remaining[2]), $container[1] - $item[1]);
			} else {
				$spaces = array();
			}
			$spaces = self::sortDimensions($spaces, 'smallfirst');
			return $spaces;
		} // function findRemainingSpaces

		/**
		 *  Pack additional items in a container
		 *    If additional items fit, recursively calculate remaining spaces and reattempt to subPack remaining items
		 *  Args: (array) packing items remaining, (array) containers/remaining spaces, (int) index of sub packing attempt
		 *  Return: none
		 */
		public static function subPack($items, $spaces, $subPackIndex) {
			foreach ($items as $index => $item) {
				if (!isset(self::$subPacked[$subPackIndex][$index])) {
					$packed = self::findSmallestContainerFor($item, $spaces);
					if ($packed[0] !== false) {
						self::addSubPackedItem($index, $item, $packed[1], $subPackIndex);
						$items = self::removePackedItems($items, self::$subPacked[$subPackIndex]);
						$remaining = self::findRemainingSpaces($item, $packed[1]);
		
						if (!empty($remaining) && !empty($items)) {
							// further sub packing
							self::subPack($items, $remaining, $subPackIndex);
						}
						// recalculate remaining spaces
						$spaces = self::recalculateRemainingSpace($packed[0], $spaces, $subPackIndex);
					}
				}
			}
		} // function subPack

		/**
		 *  Recalculate remaining spaces in a successful subPack attempt
		 *    This algorithm does not reclaim unused space
		 *    The entire space of the container that was subPacked is lost and the other spaces are recalculated
		 *  Args: (str) subPacked container section, (array) array of spaces, (int) index of sub packing attempt
		 *  Return: (array) array of containers
		 */
		public static function recalculateRemainingSpace($section, $spaces, $subPackIndex) {
			$filled = $spaces[$section];
			unset($spaces[$section]);
			switch ($section) {
				case 'length':
					// subtract width of section from remaining section widths
					foreach ($spaces as &$val) {
						$val[0][1] -= $filled[0][1];
						$val[1] = self::$subPacked[$subPackIndex]['container'][1] - self::$subPacked[$subPackIndex]['weight'];
					}
					break;
				case 'width':
					// subtract length of section from remaining section lengths
					foreach ($spaces as &$val) {
						$val[0][0] -= $filled[0][0];
						$val[1] = self::$subPacked[$subPackIndex]['container'][1] - self::$subPacked[$subPackIndex]['weight'];
					}
					break;
				case 'height':
					// subtract height of section from remaining section heights
					foreach ($spaces as &$val) {
						$val[0][2] -= $filled[0][2];
						$val[1] = self::$subPacked[$subPackIndex]['container'][1] - self::$subPacked[$subPackIndex]['weight'];
					}
					break;
				default:
					$spaces = array();
					break;
			}
			if (!empty($spaces)) {
				$spaces = self::sortDimensions($spaces, 'smallfirst');
			}
			return $spaces;
		} // function recalculateRemainingSpace

		/**
		 *  Add an item to the packed items array
		 *  Args: (int) index of item, (array) item, (array) container
		 *  Return: none
		 */
		public static function addPackedItem($index, $item, $container) {
			self::$packed[$index] = array('item' => $item, 'in' => $container);
		} // function addPackedItem

		/**
		 *  Add an item to the specified subPack attemp
		 *  Args: (int) index of item, (array) item, (array) container, (int) index of sub packing attempt
		 *  Return:
		 */
		public static function addSubPackedItem($index, $item, $container, $subPackIndex) {
			self::$subPacked[$subPackIndex][$index] = array('item' => $item, 'in' => $container);
			self::$subPacked[$subPackIndex]['weight'] += $item[1];
		} // function addSubPackedItem

		/**
		 *  Remove items packed within a container array from a stack of items
		 *  Args: (array) array of items, (array) array of packed items
		 *  Return: (array) array of items remaining
		 */
		public static function removePackedItems($stack, $container) {
			assertArray($stack);
			foreach ($stack as $key => $val) {
				if (isset($container[$key])) {
					unset($stack[$key]);
				}
			}
			return $stack;
		} // function removePackedItems

		/**
		 *  Calculate the optimal subPack configuration from an array of subPack attempts
		 *    Calculation is based on a ratio of items contained to remaining volume
		 *    Greatest ratio is considered the optimal
		 *  The items in the optimal packing configuration are then removed from an item stack
		 *  Args: (array) array of items
		 *  Return: (array) array of items remaining
		 */
		public static function useOptimalSubPackingConfiguration($items) {
			// item count to remaining volume
			$bestRatio = 0;
			reset(self::$subPacked);
			$optimal = current(self::$subPacked);
			foreach (self::$subPacked as $key => $package) {
				$containerVolume = $package['container'][0][0] * $package['container'][0][1] * $package['container'][0][2];
				$itemsVolume = 0;
				foreach ($package as $index => $details) {
					if ($index !== 'container' && $index !== 'weight') {
						$itemsVolume += $details['item'][0][0] * $details['item'][0][1] * $details['item'][0][2];
					}
				}
				$remainingVolume = $containerVolume - $itemsVolume;
				$itemCount = count($package) - 1;
				$containerRatio = $itemCount / $remainingVolume;
				if ($containerRatio > $bestRatio) {
					$optimal = $package;
				}
			}
			foreach ($optimal as $index => $subPacked) {
				if ($index !== 'container' && $index !== 'weight') {
					self::addPackedItem($index, $subPacked['item'], $optimal['container']);
				}
			}
			$items = self::removePackedItems($items, $optimal);
			self::$packages[] = $optimal;
			return $items;
		} // funciton useOptimalSubPackingConfiguration

	} // class packer

?>