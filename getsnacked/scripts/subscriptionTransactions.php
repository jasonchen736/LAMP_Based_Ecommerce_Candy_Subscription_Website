<?

	/**
	 *  This script is made to be run within the cron wrapper "executeCronScript.php"
	 *  From there it will be required in the cron object function "executeScript"
	 *  As a result, all cron object functions and variables are accessible directly with $this
	 */

	$subsObj = new subscription;
	$this->statusMsgs[] = 'Subscription object constructed';
	$this->logData();

	$subsObj->enterReorders();
	$this->statusMsgs[] = 'Reorders entered';
	$this->logData();

	$subsObj->clearCCReOrders();
	$this->statusMsgs[] = 'CC reorders completed';
	$this->logData();

	$subsObj->cleareCheckReOrders();
	$this->statusMsgs[] = 'eCheck reorders completed';
	$this->logData();

	$subsObj->notifyCheckSubscriptions();
	$this->statusMsgs[] = 'Check/money order notifications completed';
	$this->logData();

	$subsObj->notifyPaidInFull();
	$this->statusMsgs[] = 'Paid notifications completed';
	$this->logData();

	$subsObj->updateReorderedSubscriptions();
	$this->statusMsgs[] = 'Reordered subscriptions updated';
	$this->logData();

?>