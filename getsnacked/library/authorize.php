<?

	/**
	 *  Transaction array:
	 *  $transaction = array(
	 *  	'type'           => One of the following: CHARGE, AUTHORIZEONLY, REFUND, VOID
	 *  	'customer_id'    => member id of the customer if registered
	 *  	'description'    => ORDER [ORDERID] SUBORDER [SUBORDERID]
	 *  	'method'         => cc/echeck
	 *  	'invoice_num'    => [SUBORDERID]
	 *  	'card_num'       => cc number
	 *  	'exp_month'      => expiration month
	 *  	'exp_year'       => expiration year
	 *  	'card_code'      => cvv
	 *  	'amount'         => total charge
	 *  	'first_name'     => first name
	 *  	'last_name'      => last name
	 *  	'address'        => street address
	 *  	'city'           => city
	 *  	'state'          => state
	 *  	'zip'            => postal code
	 *  	'country'        => country
	 *  	'bank_aba_code'  => bank aba code
	 *  	'bank_acct_num'  => bank account number
	 *  	'bank_acct_type' => bank account type
	 *  	'bank_name'      => name of bank
	 *  	'bank_acct_name' => name of bank account
	 *  );
	 *  Response array:
	 *  $response = array(
	 *  	responseCode'   => numeric response code
	 *  	responseText'   => response text
	 *  	subCode'        => numeric response sub code
	 *  	reasonCode'     => numeric reason code
	 *  	reasonText'     => reason text
	 *  	approvalCode'   => numeric approval code
	 *  	transactionID'  => transaction reference id
	 *  	invoiceNumber'  => invoice number
	 *  	description'    => description
	 *  	amount'         => amount charged
	 *  	method'         => payment method
	 *  	type'           => transaction type
	 *  	memberID'       => customer id
	 *  	cvvCode'        => cvv verification code
	 *  	cvvResponse'    => cvv verification text
	 *  	avsCode'        => avs verification code
	 *  	avsResponse'    => avs verification text
	 *  );
  	 **/

	class authorize {
		public static $dbh;
		public static $testMode = false;

		/**
		 *  Sets up transaction environment and database handler
		 *  Args: none
		 *  Return: none
		 */
		public static function setup() {
			self::$testMode = isDevEnvironment() ? true : false;
			self::$dbh = database::getInstance();
		} // function setup

		/**
		 *  Switch gateway type, perform transaction
		 *  Args: (array) transaction
		 *  Return: (mixed) gateway response
		 */
		public static function authorizeTransaction($transaction) {
			if (!systemSettings::get('OFFLINE')) {
				switch ($transaction['gateway']) {
					case 'authorize':
						$response = gatewayAuthorize::authorizeTransaction($transaction);
						break;
					case 'linkpoint':
						$response = gatewayLinkpoint::authorizeTransaction($transaction);
						break;
					default:
						trigger_error('Unspecified or invalid gateway while attempting to process transaction for member: '.self::$memberID, E_USER_WARNING);
						return false;
						break;
				}
			} else {
				$response = self::offlineTransaction($transaction, 'approve');
			}
			return self::logTransaction($transaction, $response);
		} // function authorizeTransaction

		/**
		 *  Perform an offline test transaction with request selected response
		 *  Args: (array) transaction, (str) requested result
		 *  Return: (array) response array
		 */
		public static function offlineTransaction($transaction, $result) {
			$response = array();
			switch(strtolower($result)) {
				case 'approve':
				default:
					$response['responseCode'] = 1;
					$response['responseText'] = 'Approved';
					$response['subCode'] = '';
					$response['reasonCode'] = '';
					$response['reasonText'] = 'TEST APPROVED';
					$response['approvalCode'] = '';
					$response['transactionID'] = time();
					$response['invoiceNumber'] = $transaction['invoice_num'];
					$response['description'] = $transaction['description'];
					$response['amount'] = $transaction['amount'];
					$response['method'] = $transaction['method'];
					$response['type'] = $transaction['type'];
					$response['memberID'] = $transaction['cust_id'];
					$response['cvvCode'] = '';
					$response['cvvResponse'] = '';
					$response['avsCode'] = '';
					$response['avsResponse'] = '';
					$response['error'] = false;
					break;
			}
			return $response;
		} // function offlineTransaction

		/**
		 *  Logs transaction response
		 *  Args: (array) transaction array, (array) response array
		 *  Return: (array) response array with transaction record id added
		 */
		public static function logTransaction($transaction, $response) {
			if ($response && is_array($response)) {
				$queryVals = array(
					'~owner'          => prep($transaction['member_id']),
					'~gateway'        => prep($transaction['gateway']),
					'~responseCode'   => prep($response['responseCode']),
					'~responseText'   => prep($response['responseText']),
					'~subCode'        => prep($response['subCode']),
					'~reasonCode'     => prep($response['reasonCode']),
					'~reasonText'     => prep($response['reasonText']),
					'~approvalCode'   => prep($response['approvalCode']),
					'~transactionID'  => prep($response['transactionID']),
					'~invoiceNumber'  => prep($response['invoiceNumber']),
					'~description'    => prep($response['description']),
					'~amount'         => prep($response['amount']),
					'~method'         => prep($response['method']),
					'~type'           => prep($response['type']),
					'~customer'       => prep($transaction['customer_id']),
					'~cvvCode'        => prep($response['cvvCode']),
					'~cvvResponse'    => prep($response['cvvResponse']),
					'~avsCode'        => prep($response['avsCode']),
					'~avsResponse'    => prep($response['avsResponse']),
					'transactionDate' => 'NOW()'
				);
				if ($response['error']) {
					$queryVals['~error'] = prep($response['error']);
				}
				self::$dbh->perform('transactions', $queryVals);
				$response['transactionRecordID'] = self::$dbh->insertID;
			}
			return $response;
		} // function logTransaction
	} // class authorize

?>