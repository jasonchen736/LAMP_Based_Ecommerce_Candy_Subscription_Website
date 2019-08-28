<?

	require_once 'SwiftMailer/lib/Swift.php';
	require_once 'SwiftMailer/lib/Swift/Connection/SMTP.php';
	require_once 'SwiftMailer/lib/Swift/Plugin/Decorator.php';

	class campaigns {

		// database object
		private $dbh;
		// email object
		private $mailer;
		// active campaigns
		// scheduled campaigns:
		//   array([campaignScheduleID], [currentCampaignID], [linkedCampaignID], [list])
		// free run campaigns:
		//   array([campaignID] => [sendInterval])
		private $campaigns;
		// array - store any lists to be used in campaign building/execution
		private $lists;
		// campaign emails
		private $emails;
		// date/time references
		private $today, $startRef, $endRef;

		/**
		 *  Instantiate database and mailer, and time references
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			ini_set('memory_limit', '50M');
			$this->dbh = new database;
			$this->mailer = new Swift(new Swift_Connection_SMTP("localhost"));
			// establish date/time references
			$this->today = date('Y-m-d');
			// round minutes to the nearest 15 (0, 15, 30, 45)
			$minute = date('i');
			if ($minute >= 53 || $minute <= 7) $minute = 0;
			elseif ($minute >= 8 || $minute <= 22) $minute = 15;
			elseif ($minute >= 23 || $minute <= 37) $minute = 30;
			else $minute = 45;
			$hour = date('H');
			// 15 minute intervals
			$this->startRef = $this->today.' '.$hour.':'.$minute.':00';
			$this->endRef = $this->today.' '.$hour.':'.($minute + 14).':59';
			// initiate with no valid campaigns
			$this->campaigns = false;
		} // class __construct

		/**
		 *  Disconnect mailer
		 *  Args: none
		 *  Return: none
		 */
		public function __destruct() {
			$this->mailer->disconnect();
		} // function __destruct

		/**
		 *  Reset campaign vars
		 *  Args: none
		 *  Return: none
		 */
		public function resetCampaigns() {
			$this->campaigns = array();
			$this->emails = array();
			$this->lists = array();
		} // function resetCampaigns

		/**
		 *  Logs campaign data
		 *  Args: (str) List name, Campaign type (int) Campaign ID, Emails found, Emails sent
		 *  Return: none
		 */
		private function logCampaignData($list, $type, $campaign, $found, $sent) {
			// type is an enum database value
			$queryVals = array(
				'~type'       => prepDB($type),
				'campaignID'  => prepDB($campaign),
				'~list'       => prepDB($list),
				'emailsFound' => prepDB($found),
				'emailsSent'  => prepDB($sent),
				'date'        => 'NOW()'
			);
			$this->dbh->perform('campaignDataLog', $queryVals);
		} // function logCampaignData

		/**
		 *  Determine and prepares outgoing scheduled campaigns
		 *  Args: none
		 *  Return: none
		 */
		public function buildCampaigns() {
			$result = $this->dbh->query("SELECT `campaignScheduleID`, `currentCampaignID`, 
												`linkedCampaignID`, `list` 
											FROM `campaignSchedule` 
											WHERE `sendDate` BETWEEN '".$this->startRef."' AND '".$this->endRef."' 
											AND `status` = 'new'");
			if ($this->dbh->rowCount > 0) {
				$this->resetCampaigns();
				$retrieve = array();
				while ($row = mysql_fetch_assoc($result)) {
					$this->campaigns[] = $row;
					// determine campaigns to retrieve
					if (!in_array($row['currentCampaignID'], $retrieve)) {
						$retrieve[] = $row['currentCampaignID'];
					}
				}
				mysql_free_result($result);
				if (!empty($retrieve)) {
					// retrieve campaigns and set up messages
					$campRes = $this->dbh->query("SELECT `campaignID`, `subject`, `html`, `text` FROM `campaigns` WHERE `campaignID` IN (".implode(', ', $retrieve).")");
					if (!$this->dbh->rowCount) {
						trigger_error('Error: Unable to retrieve campaign emails '.implode(', ', $retrieve), E_USER_WARNING);
					} else {
						while ($cRow = mysql_fetch_assoc($result)) {
							if ($cRow['html'] || $cRow['text']) {
								$this->emails[$cRow['campaignID']] = new Swift_Message($cRow['subject']);
								if ($cRow['text']) $this->emails[$cRow['campaignID']]->attach(new Swift_Message_Part($cRow['text']));
								if ($cRow['html']) $this->emails[$cRow['campaignID']]->attach(new Swift_Message_Part($cRow['html'], "text/html"));
							} else {
								// remove campaign schedules with the campaign, update table with error
								$errorIDs = array();
								foreach ($this->campaigns as $key => $val) {
									if ($val['currentCampaignID'] == $cRow['campaignID']) {
										unset($this->campaigns[$key]);
										$errorIDs[] = $val['campaignScheduleID'];
									}
								}
								$this->dbh->query("UPDATE `campaignSchedule` SET `status` = 'error', `modifiedDate` = NOW() WHERE `campaignScheduleID` IN (".implode(', ', $errorIDs).")");
								trigger_error('Error: No email content for campaign '.$cRow['campaignID'], E_USER_WARNING);
							}
						}
						mysql_free_result($campRes);
					}
				} else {
					// this error should never happen, may consider removing
					$campaignScheduleIDs = array();
					foreach ($this->campaigns as $key => $val) {
						$campaignScheduleIDs[] = $val['campaignScheduleID'];
					}
					trigger_error('Error: No campaigns to retrieve for campaign schedule ID(s): '.implode(', ', $campaignScheduleIDs), E_USER_WARNING);
				}
			}
		} // function buildCampaigns

		/**
		 *  Activate and send mail to valid campaigns
		 *  Args: none
		 *  Return: none
		 */
		public function executeCampaigns() {
			if (!empty($this->campaigns)) {
				// array([campaignScheduleID], [currentCampaignID], [linkedCampaignID], [list])
				foreach ($this->campaigns as $key => $val) {
					$result = $this->dbh->query("SELECT `email` FROM `".$val['list']."` WHERE `campaignScheduleID` = ".$val['campaignScheduleID']);
					$found = $this->dbh->rowCount;
					if ($this->dbh->rowCount > 0) {
						// set up email list
						$list = new Swift_RecipientList();
						while ($row = mysql_fetch_assoc($result)) {
							$list->addTo($row['email']);
						}
						mysql_free_result($result);	
						// execute campaign
						$success = $this->mailer->batchSend($this->emails[$val['currentCampaignID']], $list, CAMPAIGNEMAIL);
						// set up linked campaign schedule if linked
						//   otherwise clear campaign schedule
						if ($val['linkedCampaignID']) {
							$this->dbh->query("INSERT INTO `campaignSchedule` (`mainCampaignID`, `currentCampaignID`, `linkedCampaignID`, `list`, `sendDate`, `modifiedDate`, `linkedFrom`) 
												SELECT `a`.`mainCampaignID`, `a`.`linkedCampaignID`, `b`.`linkedCampaign`, 
													`a`.`list`, DATE_ADD(`a`.`sendDate`, INTERVAL `c`.`sendInterval` DAY), 
													NOW(), `a`.`campaignScheduleID` 
												FROM `campaignSchedule` AS `a` 
												JOIN `campaigns` AS `b` ON (`a`.`linkedCampaignID` = `b`.`campaignID`) 
												JOIN `campaigns` AS `c` ON (`a`.`currentCampaignID` = `c`.`campaignID`)");
							$this->dbh->query("UPDATE `".$val['list']."` SET `campaignScheduleID` = ".$this->dbh->insertID." WHERE `campaignScheduleID` = ".$val['campaignScheduleID']);
						} else {
							$this->dbh->query("UPDATE `".$val['list']."` SET `campaignScheduleID` = 0 WHERE `campaignScheduleID` = ".$val['campaignScheduleID']);
						}
					} else {
						$success = 0;
					}
					// update
					$this->dbh->query("UPDATE `campaignSchedule` SET `status` = 'completed', `modifiedDate` = NOW() WHERE `campaignScheduleID` = ".$val['campaignScheduleID']);
					// log campaign data
					$this->logCampaignData($val['list'], 'scheduled', $val['currentCampaignID'], $found, $success);
				}
			}
		} // function executeCampaigns

		/**
		 *  Build non formally scheduled campaigns
		 *    ex: lifecycle campaigns triggered by user hitting certain pages
		 *  Args: (array) list names
		 *  Return: none
		 */
		public function buildFreeRunCampaigns($lists) {
			if (!is_array($lists) || empty($lists)) return;
			$this->resetCampaigns();
			$retrieve = array();
			foreach ($lists as $key => $val) {
				$val = clean($val);
				$result = $this->dbh->query("SELECT `currentCampaign` FROM `freeRunCampaigns` WHERE `sendDate` BETWEEN '".$this->startRef."' AND '".$this->endRef."' GROUP BY `currentCampaign`");
				if ($this->dbh->rowCount > 0) {
					$this->lists[] = $val;
					while ($row = mysql_fetch_assoc($result)) {
						if (!in_array($row['currentCampaign'], $retrieve)) {
							$retrieve[] = $row['currentCampaign'];
						}
					}
					mysql_free_result($result);
				}
			}
			if (!empty($retrieve)) {
				// retrieve campaigns and set up messages
				$campRes = $this->dbh->query("SELECT `campaignID`, `subject`, `html`, `text`, `sendInterval` FROM `campaigns` WHERE `campaignID` IN (".implode(', ', $retrieve).")");
				if (!$this->dbh->rowCount) {
					trigger_error('Error: Unable to retrieve campaign emails '.implode(', ', $retrieve), E_USER_WARNING);
				} else {
					while ($cRow = mysql_fetch_assoc($result)) {
						if ($cRow['html'] || $cRow['text']) {
							$this->emails[$cRow['campaignID']] = new Swift_Message($cRow['subject']);
							if ($cRow['text']) $this->emails[$cRow['campaignID']]->attach(new Swift_Message_Part($cRow['text']));
							if ($cRow['html']) $this->emails[$cRow['campaignID']]->attach(new Swift_Message_Part($cRow['html'], "text/html"));
							$this->campaigns[$cRow['campaignID']] = $cRow['sendInterval'];
						} else {
							trigger_error('Error: No email content for campaign '.$cRow['campaignID'], E_USER_WARNING);
						}
					}
					mysql_free_result($campRes);
				}
			}
		} // function buildFreeRunCampaigns

		/**
		 *  Executes non formally scheduled campaigns
		 *    ex: lifecycle campaigns triggered by user hitting certain pages
		 *  Args: none
		 *  Return: none
		 */
		public function executeFreeRunCampaigns() {
			$campaignIDs = array_keys($this->emails);
			$campaignStr = implode(', ', $campaignIDs);
			// initialize email address objects and counters
			$addresses = array();
			$emailCount = array();
			foreach ($campaignIDs as $index => $id) {
				$addresses[$id] = new Swift_RecipientList();
				$emailCount[$id] = 0;
			}
			foreach ($this->lists as $curList) {
				$result = $this->dbh->query("SELECT `email`, `currentCampaign`  
												FROM `".$curList."` 
												WHERE `sendDate` BETWEEN '".$this->startRef."' AND '".$this->endRef."' 
												AND `currentCampaign` IN (".$campaignStr.")");
				if ($this->dbh->rowCount > 0) {
					while ($row = mysql_fetch_assoc($result)) {
						$addresses[$row['currentCampaign']]->addTo($row['email']);
						$emailCount[$id]++;
					}
					foreach($campaignIDs as $id) {
						if ($emailCount[$id] > 0) {
							$success = $this->mailer->batchSend($this->emails[$id], $addresses[$id], CAMPAIGNEMAIL);
							// log campaign data
							$this->logCampaignData($curList, 'freerun', $id, $emailCount[$id], $success);
						}
					}
					// reset resources/email address objects and counters
					mysql_free_result($result);
					foreach ($campaignIDs as $index => $id) {
						$addresses[$id]->flush();
						$emailCount[$id] = 0;
					}
					$this->dbh->query("UPDATE `".$curList."` AS `a`, `campaigns` AS `b`, `campaigns` AS `c` 
										SET `a`.`currentCampaign` = `a`.`linkedCampaign`, 
											`a`.`linkedCampaign` = `b`.`linkedCampaign`, 
											`a`.`sendDate` = DATE_ADD(`a`.`sendDate`, INTERVAL `c`.`sendInterval` DAY) 
										WHERE `a`.`linkedCampaign` = `b`.`campaignID` 
										AND `a`.`currentCampaign` =  `c`.`campaignID` 
										AND `a`.`sendDate` BETWEEN '".$this->startRef."' AND '".$this->endRef."' 
										AND `a`.`currentCampaign` IN (".$campaignStr.")");
				}
			}
		} // function executeFreeRunCampaigns

	} // class campaigns

?>