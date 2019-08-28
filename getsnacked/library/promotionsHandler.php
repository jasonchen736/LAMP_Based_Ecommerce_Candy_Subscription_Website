<?

	// migrate to promotions class

	class promotionsHandler extends dataObject {

		// promotions vars
		protected $promotions; // array[index] => [ID] [description] [type] [effect] [stackID] [applied] [status] [discount]
		protected $promotionComboID;
		protected $initialDiscount;
		protected $subscriptionDiscount;
		protected $pseudoDiscount;
		protected $addItems; // array[index] => (0 => ID, 1 => quantity, 2 => name)
		protected $freeShipments;
		protected $invalidPromos; // array[ID] => message

		/**
		 *  Initiate promotions variables and database handler
		 *  Args: none
		 *  Return: (array) object variable names
		 */
		public function __construct() {
			$this->resetPromotions();
			parent::__construct();
		} // function __construct

		/**
		 *  Prep for object serialization
		 *  Args: none
		 *  Return: (array) object variable names
		 */
		public function __sleep() {
			return parent::__sleep();
		} // function __sleep()

		/**
		 *  Reinitialize database handler
		 *  Args: none
		 *  Return: none
		 */
		public function __wakeup() {
			parent::__wakeup();
		} // function __wakeup

		/**
		 *  Reset promotion variables
		 *  Args: (boolean) partial reset
		 *  Return: none
		 */
		public function resetPromotions($partial = false) {
			if (!$partial) {
				$this->promotions    = array();
				$this->invalidPromos = array();
			}
			$this->promotionComboID     = false;
			$this->initialDiscount      = false;
			$this->subscriptionDiscount = false;
			$this->pseudoDiscount       = false;
			$this->addItems             = array();
			$this->freeShipments        = false;
		} // function resetPromotions

		/**
		 *  Handles promotion id's submitted via get/post
		 *    Sets promotion array variable
		 *  Args: none
		 *  Return: none
		 */
		public function setPromotions() {
			assertArray($this->promotions);
			if (isset($_REQUEST['pc'])) {
				// promotion combination string submitted, reset promotions array
				$this->resetPromotions();
				$promo_temp = array();
				$promo_temp = explode(';', $_REQUEST['pc']);
				foreach ($promo_temp as $key => &$val) {
					$val = clean($val);
					if (empty($val)) unset($promo_temp[$key]);
				}
				// filters out duplicate promo ids
				$promo_temp = array_unique($promo_temp);
				foreach ($promo_temp as $promo) {
					$this->promotions[]['ID'] = $promo;
				}
			} elseif (isset($_REQUEST['removePromo'])) {
				// remove promotion
				foreach ($this->promotions as $key => $val) {
					if ($val['ID'] == $_REQUEST['removePromo']) unset($this->promotions[$key]);
				}
			} elseif (count($this->promotions) < systemSettings::get('MAXPROMOTIONS') && isset($_REQUEST['addPromo'])) {
				// add promotion, submitted from form
				$promo = false;
				if (clean($_REQUEST['addPromo']) != '') $promo = clean($_REQUEST['addPromo']);
				// filters out duplicate promo ids
				if ($promo) {
					$add = true;
					foreach ($this->promotions as $key => $vals) {
						if ($vals['ID'] == $promo) $add = false;
					}
					if ($add) $this->promotions[]['ID'] = $promo;
				}
			} elseif (isset($_REQUEST['addPromo']) && $_REQUEST['addPromo'] != '') {
				// do not allow addition of any more promotions
				$this->invalidPromos[$_REQUEST['addPromo']] = "You have reached the maximum number of promotions";
			}
			// sort promotions array by value of index ID to minimize promotion id variablility
			// sort method defined in globals
			usort($this->promotions, array('promotionsHandler', 'usortByID'));
			$this->applyPromotions();
		} // function setPromotions

		/**
		 *  Validates and processes promotion codes
		 *    completes promotions array: array[index] => [ID] [description] [type] [effect] [stackID] [applied] [status] [discount]
		 *    generates internal variables invalidPromos, initialDiscount, subscriptionDiscount, pseudoDiscount, addItems, promotionComboID
		 *  Args: none
		 *  Return: none
		 */
		public function applyPromotions() {
			$this->resetPromotions(true);
			// checked/validated and all retrieved/promotions info temporarily stored here
			$pArray = array();
			$valid = array();
			// $pCount indexes the promotion array and is used to ensure that calculated promotions do not exceed cap
			$pCount = 0;
			// bad promo codes (array[CODE] => Status)
			$pBadPromos = array();
			// get promotion records from database
			$promotionIDs = '';
			foreach ($this->promotions as $key => $val) {
				$promotionIDs .= "'".prepDB($val['ID'])."', ";
			}
			$promotionIDs = rtrim($promotionIDs, ', ');
			if ($promotionIDs) {
				$pResult = $this->dbh->query("SELECT * FROM `promotions` WHERE `promotionCode` IN (".$promotionIDs.") AND `status` IN ('active', 'unlimited') AND `availability` IN ('all', '".prepDB($this->dataObject->get('refferalType'))."')");
				// validate promotions
				if ($pResult->rowCount > 0) {
					while ($pRow = $pResult->fetchAssoc()) {
						if ($pRow['exclusiveID'] == 0 || $pRow['exclusiveID'] == $this->ID) {
							$begun = strtotime($pRow['startDate']) - strtotime(date('Y-m-d'));
							$expired = strtotime($pRow['endDate']) - strtotime(date('Y-m-d'));
							if ($begun <= 0 && $expired > 0) {
								if ($pRow['remainingServes'] > 0 || $pRow['totalServes'] == 0) {
									// promotion valid, set to validated array
									$pArray[$pCount]['ID']          = $pRow['promotionCode'];
									$pArray[$pCount]['description'] = $pRow['description'];
									$pArray[$pCount]['type']        = $pRow['type'];
									$pArray[$pCount]['effect']      = $pRow['effect'];
									$pArray[$pCount]['duration']    = $pRow['duration'];
									$pArray[$pCount]['stackID']     = $pRow['stackID'];
									$pArray[$pCount]['applied']     = false;
									$pArray[$pCount]['status']      = 'unprocessed';
									$pArray[$pCount]['discount']    = 0;
									$valid[] = $pRow['promotionCode'];
									$pCount++;
								} else {
									// out of serves
									$pBadPromos[$pRow['promotionCode']] = 'This is a limited promotion and has exceeded serving limit.';
								}
							} elseif ($begun > 0) {
								// has not begun
								$pBadPromos[$pRow['promotionCode']] = 'This promotion has not begun.';
							} else {
								// expired
								$pBadPromos[$pRow['promotionCode']] = 'This promotion has expired.';
							}
						} else {
							// incorrect affiliate id (ineligible)
							$pBadPromos[$pRow['promotionCode']] = 'You are not eligible for this promotion.';
						}
					}
				}
				// calculate invalid promotioin ids (not found)
				foreach ($this->promotions as $key => $val) {
					if (!in_array($val['ID'], $valid) && !array_key_exists($val['ID'], $pBadPromos)) {
						// invalid promotion id
						$pBadPromos[$this->promotions[$key]['ID']] = 'Invalid promotion code.';
					}
				}
				// perform promotions stack id check where:
				//   no two promotions with the same stack id can be applied at the same time
				//   stack id 9 cannot be combined with any other stack ids
				// also stops any promotions beyond the maximum promotions allowed from being applied
				// stackIDs array stores encountered stack ids
				$stackIDs = array();
				$stop = false;
				// i is promotion count
				for ($i = 0; $i < count($pArray); $i++) {
					// check if maximum promotions already accounted for
					if ($i + 1 > systemSettings::get('MAXPROMOTIONS')) {
						$pArray[$i]['status'] = 'limit reached';
						if ($pArray[$i]['stackID'] == 9) $pArray[$i]['status'] .= '<br>This promotion cannot be combined with any other promotion';
						elseif (in_array($pArray[$i]['stackID'], $stackIDs)) $pArray[$i]['status'] .= '<br>This promotion cannot be combined with one or more promotions listed above';
					} elseif ($pArray[$i]['stackID'] == 9) {
						if ($i > 0) {
							$pArray[$i]['status'] = 'This promotion cannot be combined with any other promotion.';
						} else {
							$pArray[$i]['status'] = 'unprocessed';
							$stop = true;
						}
						if (!in_array($pArray[$i]['stackID'], $stackIDs)) $stackIDs[] = $pArray[$i]['stackID'];
					} elseif (!in_array($pArray[$i]['stackID'], $stackIDs) && !$stop) {
						$pArray[$i]['status'] = 'unprocessed';
						$stackIDs[] = $pArray[$i]['stackID'];
					} else {
						$pArray[$i]['status'] = 'This promotion cannot be combined with one or more promotions listed above';
					}
				}
				// these are non monetary discount promotions - extra variables needed to flag and activate
				// this array stores items to add for add item type promotions
				$addItems = array();
				// this var stores number of free shipments to add to a subscription
				$freeShipments = 0;
				// complete application of promotions only if shopping cart contains items
				$cart           = $this->dataObject->getCart('contents');
				$totalCost      = $this->dataObject->getCart('totalCost');
				$completedForms = $this->dataObject->get('completedForms');
				$orderForm      = $this->dataObject->get('orderForm');
				if (is_array($cart) && !empty($cart)) {
					// if user has not completed order information, default quantity and number of shipments to 1
					if (is_array($completedForms) && in_array('O', $completedForms)) {
						$orderQuantity = $orderForm['quantity'];
						// order type 1 indicates subscription order
						$numShips = $orderForm['orderType'] == 1 ? count($this->dataObject->get('shippingDates')) + 1 : 1;
						$shippingCost = $this->dataObject->get('shippingCost');
					} else {
						$orderQuantity = 1;
						$numShips = 1;
						$shippingCost = 5;
					}
					// only calculate up to max allowable promotions
					$pCount = count($pArray) < systemSettings::get('MAXPROMOTIONS') ? count($pArray) : systemSettings::get('MAXPROMOTIONS');
					for ($i = 0; $i < $pCount; $i++) {
						if ($pArray[$i]['status'] == 'unprocessed') {
							$effect = explode(';', $pArray[$i]['effect']);
							switch ($pArray[$i]['type']) {
								case 'freeshipping':
									if (($totalCost * $orderQuantity * $numShips) >= $effect[0]) {
										$pArray[$i]['discount'] = $shippingCost;
										$pArray[$i]['applied'] = true;
										$pArray[$i]['status'] = 'applied';
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'percentdiscount':
									$costCriteria = $effect[0];
									$promoDiscount = $effect[1];
									if (($totalCost * $orderQuantity * $numShips) >= $costCriteria) {
										$pArray[$i]['discount'] = $totalCost * $orderQuantity * $promoDiscount / 100;
										$pArray[$i]['applied'] = true;
										$pArray[$i]['status'] = 'applied';
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'dollardiscount':
									$costCriteria = $effect[0];
									$promoDiscount = $effect[1];
									if (($totalCost * $orderQuantity * $numShips) >= $costCriteria) {
										$pArray[$i]['discount'] = $promoDiscount;
										$pArray[$i]['applied'] = true;
										$pArray[$i]['status'] = 'applied';
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'dollarrebate':
									$costCriteria = $effect[0];
									$promoDiscount = $effect[1];
									if (($totalCost * $orderQuantity * $numShips) >= $costCriteria) {
										$pArray[$i]['discount'] = $promoDiscount;
										$pArray[$i]['status'] .= '<br>Please fill out a rebate form at [REBATEURL] and include this promotion ID ('.$pArray[$i]['ID'].') and the Order/Subscription ID you will receive with your order confirmation.';
										$pArray[$i]['applied'] = true;
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'freeshipments':
									$costCriteria = $effect[0];
									$promoDiscount = $effect[1];
									$shipmentCriteria = $effect[2];
									if (($totalCost * $orderQuantity * $numShips) >= $costCriteria) {
										if ($numShips >= $shipmentCriteria) {
											$freeShipments += $promoDiscount;
											$pArray[$i]['discount'] = $totalCost * $orderQuantity * $promoDiscount;
											$pArray[$i]['applied'] = true;
											$pArray[$i]['status'] = 'applied';
										} else {
											$pArray[$i]['status'] = 'Promotional criteria not reached.';
										}
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'freeitem':
									$costCriteria = $effect[0];
									$promoItem = $effect[1];
									$quantityToAdd = $effect[2];
									$itemName = $effect[3];
									if (($totalCost * $orderQuantity * $numShips) >= $costCriteria) {
										$result = $this->dbh->query("SELECT `cost` FROM `products` WHERE `productID` = '".prepDB($promoItem)."'");
										if ($result->rowCount) {
											$pArray[$i]['discount'] = $result->fetchAssoc();
											$pArray[$i]['discount'] = $pArray[$i]['discount']['cost'] * $quantityToAdd;
										}
										$addItems[] = array($promoItem, $quantityToAdd, $itemName);
										$pArray[$i]['status'] .= '<br>'.$itemName.' x '.$quantityToAdd.' will be added to your order after checkout';
										$pArray[$i]['applied'] = true;
									} else {
										$pArray[$i]['status'] = 'Promotional criteria not reached.';
									}
									break;
								case 'dollartiereddiscount':
									$promoItem = $effect[0];
									$discount1 = $effect[1];
									$criteriaT2 = $effect[2];
									$discount2 = $effect[3];
									$criteriaT3 = $effect[4];
									$discount3 = $effect[5];
									if (array_key_exists($promoItem, $cart)) {
										if ($cart[$promoItem]['Q'] * $orderQuantity >= $criteriaT3) { //< 3rd tier
											$pArray[$i]['discount'] = $cart[$promoItem]['Q'] * $orderQuantity * $discount3;
										} elseif ($cart[$promoItem]['Q'] * $orderQuantity >= $criteriaT2) { //< 2nd tier
											$pArray[$i]['discount'] = $cart[$promoItem]['Q'] * $orderQuantity * $discount2;
										} else { //< 1st tier
											$pArray[$i]['discount'] = $cart[$promoItem]['Q'] * $orderQuantity * $discount1;
										}
										$pArray[$i]['applied'] = true;
										$pArray[$i]['status'] = 'applied';
									} else {
										$pArray[$i]['status'] = 'Promotional item is not found in your shopping bag.';
									}
									break;
							} // switch($pArray[$i]['type'])
						} // if ($pArray[$i]['status'] == 'applied')
					} // for ($i = 0; $i < $pCount; $i++)
					// calculate total discount
					$this->initialDiscount = 0;
					$this->subscriptionDiscount = 0;
					$this->pseudoDiscount = 0;
					// promotion combo string
					$promoString = '';
					foreach ($pArray as $key => $promoVals) {
						if ($promoVals['applied']) {
							$promoString .= $promoVals['ID'].';';
							switch ($promoVals['duration']) {
								case 'initial':
									$this->initialDiscount += $promoVals['discount'];
									break;
								case 'permanent':
									$this->initialDiscount += $promoVals['discount'];
									if ($orderForm['orderType'] == 1) {
										$this->subscriptionDiscount += $promoVals['discount'];
									}
									break;
								case 'pseudo':
									$this->pseudoDiscount += $promoVals['discount'];
									break;
							} // switch
						} // if applied
					} // foreach
				} // if ($this->packageID && !empty($this->cart) && is_array($this->cart))
				// set internal promotion variables
				// log promotion combination and set promotion id
				$promoString = rtrim($promoString, ';');
				if ($promoString) $this->logPromotionCombo($promoString);
				$this->promotions = $pArray;
				if (!empty($addItems)) $this->addItems = $addItems;
				if (!empty($freeShipments)) $this->freeShipments = $freeShipments;
				assertArray($this->invalidPromos);
				if (!empty($pBadPromos)) $this->invalidPromos = array_merge($this->invalidPromos, $pBadPromos);
			} // if ($promotionIDs)
		} //function applyPromotions

		/**
		 *  Retrieves/creates promotions combination record and sets internal value promotionComboID
		 *  Args: (str) string of promotion combinations
		 *  Return: none
		 */
		private function logPromotionCombo($promoString) {
			$result = $this->dbh->query("SELECT `promotionCombinationID` FROM `promotionCombination` WHERE `promotionCombination` = '".prepDB($promoString)."'");
			if ($result->rowCount == 0) {
				$this->dbh->query("INSERT INTO `promotionCombination` (`promotionCombination`, `dateCreated`) VALUES ('".prepDB($promoString)."', NOW())");
				$this->promotionComboID = $this->dbh->insertID;
			} else {
				$this->promotionComboID = $result->fetchAssoc();
				$this->promotionComboID = $this->promotionComboID['promotionCombinationID'];
			}
		} // function logPromotionCombo

		/**
		 *  Returns true if holding valid promotion ids
		 *  Args: none
		 *  Return: (boolean)
		 */
		public function validPromotion() {
			if (is_array($this->promotions) && !empty($this->promotions)) return true;
			else return false;
		} // function validPromotion

		/**
		 *  Used for usorting multidimensional array by value of ID index
		 *  Args: (array) arguments are mutidimensional arrays with an ID index
		 *  Return: none
		 */
		public static function usortByID($x, $y) {
			if ($x['ID'] == $y['ID']) return 0;
			elseif ($x['ID'] < $y['ID']) return -1;
			else return 1;
		} // function usortByID


	} // class promotionsHandler

?>