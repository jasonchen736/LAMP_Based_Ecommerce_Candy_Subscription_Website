<?

	require_once 'SwiftMailer/lib/Swift.php';
	require_once 'SwiftMailer/lib/Swift/Plugin/Decorator.php';

	class mailer {
		// database
		private $dbh;
		// mail vars
		private $mailer;
		private $message;
		private $recipientList;
		private $recipientTags;
		// message vars
		private $subject;
		private $html;
		private $text;
		private $from;

		/**
		 *  Instantiate database and mailer objects
		 *  Args: none
		 *  Return: none
		 */
		public function __construct() {
			ini_set('memory_limit', '50M');
			$this->dbh = new database;
			if (!systemSettings::get('OFFLINE')) {
				switch (systemSettings::get('MAILPROTOCOL')) {
					case 'sendmail':
						require_once 'SwiftMailer/lib/Swift/Connection/Sendmail.php';
						$this->mailer = new Swift(new Swift_Connection_Sendmail());
						break;
					case 'smtp':
						require_once 'SwiftMailer/lib/Swift/Connection/SMTP.php';
						$smtp = new Swift_Connection_SMTP(systemSettings::get('MAILSERVER'), systemSettings::get('MAILPORT'));
						if (systemSettings::get('MAILAUTHENTICATION')) {
							require_once 'SwiftMailer/lib/Swift/Authenticator/LOGIN.php';
							$smtp->setUsername(systemSettings::get('MAILUSER'));
							$smtp->setPassword(systemSettings::get('MAILPASSWORD'));
							$smtp->attachAuthenticator(new Swift_Authenticator_LOGIN());
						}
						$this->mailer = new Swift($smtp);
						break;
					case 'nativemail':
					default:
						require_once 'SwiftMailer/lib/Swift/Connection/NativeMail.php';
						$this->mailer = new Swift(new Swift_Connection_NativeMail());
						break;
				}
			}
			$this->recipientList = new Swift_RecipientList();
			$this->recipientTags = array();
			$this->message = false;
		} // function __construct

		/**
		 *  Set message variable
		 *  Args: (str) message variable, (str) value
		 *  Return: none
		 */
		public function setMessage($message_var, $value) {
			$allow_set = array(
				'subject',
				'html',
				'text',
				'from'
			);
			if (in_array($message_var, $allow_set)) {
				$this->$message_var = $value;
			}
		} // function setMessage

		/**
		 *  Load a campaign from the database using campaign name
		 *  Args: (int) campaign id
		 *  Return: (boolean) success
		 */
		public function loadCampaign($campaignName) {
			$campaignName = clean($campaignName);
			if ($campaignName) {
				$result = $this->dbh->query("SELECT `subject`, `html`, `text`, `fromEmail` FROM `campaigns` WHERE `name` = '".prepDB($campaignID)."'");
				if ($result->rowCount) {
					$row = $result->fetchAssoc();
					$this->subject = $row['subject'];
					$this->html = $row['html'];
					$this->text = $row['text'];
					$this->from = $row['fromEmail'];
					return $this->composeMessage();
				}
			}
			return false;
		} // function loadCampaign

		/**
		 *  Compose the message from message vars
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public function composeMessage() {
			if (!empty($this->subject) && !empty($this->from) && (!empty($this->html) || !empty($this->text))) {
				$this->message = new Swift_Message($this->subject);
				$this->message->attach(new Swift_Message_Part($this->text));
				$this->message->attach(new Swift_Message_Part($this->html, "text/html"));
				return true;
			} else {
				trigger_error('There was an error while composing a message', E_USER_WARNING);
			}
			return false;
		} // function composeMessage

		/**
		 *  Add a recipient email address
		 *  Args: (str) email address
		 *  Return: none
		 */
		public function addRecipient($email) {
			$this->recipientList->addTo($email);
		} // function addRecipient

		/**
		 *  Add tag replacement for a recipient's message
		 *    tagArray = array('TAG_TO_REPLACE' => 'VALUE_TO_REPLACE_WITH', ... )
		 *  Args: (str) recipient email address, (array) tag array
		 *  Return: none
		 */
		public function assignTag($email, $tags) {
			$this->recipientTags[$email] = $tags;
		} // function assignTag

		/**
		 *  Execute email send
		 *  Args: (str) from email
		 *  Return: (int) emails sent
		 */
		public function send() {
			if (isDevEnvironment()) {
				$this->recipientList = new Swift_RecipientList();
				$adminEmails = systemSettings::get('ADMINEMAILS');
				assertArray($adminEmails);
				foreach ($adminEmails as $email) {
					$this->recipientList->addTo($email);
				}
			}
			if (!systemSettings::get('OFFLINE')) {
				$this->mailer->attachPlugin(new Swift_Plugin_Decorator($this->recipientTags), 'recipient_tags');
				return $this->mailer->batchSend($this->message, $this->recipientList, $this->from);
			} else {
				return true;
			}
		} // function send
	} // class mailer

?>