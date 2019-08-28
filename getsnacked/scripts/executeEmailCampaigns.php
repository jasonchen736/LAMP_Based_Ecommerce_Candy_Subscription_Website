<?

	/**
	 *  This script is made to be run within the cron wrapper "executeCronScript.php"
	 *  From there it will be required in the cron object function "executeScript"
	 *  As a result, all cron object functions and variables are accessible directly with $this
	 */

	$campaignObj = new campaigns;
	$this->statusMsgs[] = 'Campaigns object constructed';
	$this->logData();

	$campaignObj->buildCampaigns();
	$this->statusMsgs[] = 'Scheduled campaigns built';
	$this->logData();

	$campaignObj->executeCampaigns();
	$this->statusMsgs[] = 'Scheduled campaigns executed';
	$this->logData();

	$campaignObj->buildFreeRunCampaigns(array('freeRunCampaigns'));
	$this->statusMsgs[] = 'Free run campaigns built';
	$this->logData();

	$campaignObj->executeFreeRunCampaigns();
	$this->statusMsgs[] = 'Free run campaigns executed';
	$this->logData();

?>