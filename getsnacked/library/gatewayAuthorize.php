<?

	class gatewayAuthorize {
		private static $deliminator = '|';
		private static $transactionMap = array(
			'CHARGE' => 'AUTH_CAPTURE',
			'AUTHORIZEONLY' => 'AUTH_ONLY',
			'REFUND' => 'CREDIT',
			'VOID' => 'VOID'
		);
		private static $responseMap = array(
			1 => 'Approved',
			2 => 'Declined',
			3 => 'Error',
			4 => 'Held'
		);
		private static $avsMap = array(
			'A' => 'Address (Street) matches, ZIP does not',
			'B' => 'Address information not provided for AVS check',
			'E' => 'AVS error',
			'G' => 'Non-U.S. Card Issuing Bank',
			'N' => 'No Match on Address (Street) or ZIP',
			'P' => 'AVS not applicable for this transaction',
			'R' => 'Retry - System unavailable or timed out',
			'S' => 'Service not supported by issuer',
			'U' => 'Address information is unavailable',
			'W' => '9 digit ZIP matches, Address (Street) does not',
			'X' => 'Address (Street) and 9 digit ZIP match',
			'Y' => 'Address (Street) and 5 digit ZIP match',
			'Z' => '5 digit ZIP matches, Address (Street) does not'
		);
		private static $cvvMap = array(
			'M' => 'Match',
			'N' => 'No Match',
			'P' => 'Not Processed',
			'S' => 'Should have been present',
			'U' => 'Issuer unable to process request'
		);

		/**
		 *  Perform transaction authorization and parse response string into array
		 *  Args: (array) transaction array
		 *  Return: (array) gateway response
		 */
		public static function authorizeTransaction($transaction) {
			if (!array_key_exists($transaction['type'], self::$transactionMap)) {
				return false;
			} else {
				$transaction['type'] = self::$transactionMap[$transaction['type']];
			}
			// set transaction values
			$authnet_values = array(
				'x_test_request'      => authorize::$testMode ? 'TRUE' : 'FALSE',
				'x_login'             => $transaction['login'],
				'x_version'           => "3.1",
				'x_delim_char'        => self::$deliminator,
				'x_delim_data'        => 'TRUE',
				'x_url'               => 'FALSE',
				'x_type'              => $transaction['type'],
				'x_method'            => $transaction['method'],
				'x_tran_key'          => $transaction['key'],
				'x_relay_response'    => 'FALSE',
				'x_cust_id'           => $transaction['customer_id'],
				'x_invoice_num'       => $transaction['invoice_num'],
				'x_description'       => $transaction['description'],
				'x_card_num'          => $transaction['card_num'],
				'x_exp_date'          => $transaction['exp_month'].'/'.$transaction['exp_year'],
				'x_card_code'         => $transaction['card_code'],
				'x_amount'            => $transaction['amount'],
				'x_first_name'        => $transaction['first_name'],
				'x_last_name'         => $transaction['last_name'],
				'x_address'           => $transaction['address'],
				'x_city'              => $transaction['city'],
				'x_state'             => $transaction['state'],
				'x_zip'               => $transaction['zip'],
				'x_country'           => $transaction['country'],
				'x_recurring_billing' => 'NO',
				'x_bank_aba_code'     => $transaction['bank_aba_code'],
				'x_bank_acct_num'     => $transaction['bank_acct_num'],
				'x_bank_acct_type'    => $transaction['bank_acct_type'],
				'x_bank_name'         => $transaction['bank_name'],
				'x_bank_acct_name'    => $transaction['bank_acct_name']
			);
			// prepare post
			$fields = '';
			foreach ($authnet_values as $key => $val) {
				$fields .= $key.'='.urlencode($val).'&';
			}
			$ch = curl_init($transaction['host']); // URL of gateway for cURL to post to
			curl_setopt($ch, CURLOPT_HEADER, 0); // set to 0 to eliminate header info from response
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // set to return response data instead of TRUE(1)
			curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($fields, '& ')); // use HTTP POST to send form data
			$response = curl_exec($ch); // execute post and get response
			curl_close ($ch); // close stream
			$responseArray = explode(self::$deliminator, $response);
			$return = array();
			$return['responseCode'] = $responseArray[0];
			$return['responseText'] = isset(self::$responseMap[$responseArray[0]]) ? self::$responseMap[$responseArray[0]] : 'Error';
			$return['subCode'] = $responseArray[1];
			$return['reasonCode'] = $responseArray[2];
			$return['reasonText'] = $responseArray[3];
			$return['approvalCode'] = $responseArray[4];
			$return['avsCode'] = $responseArray[5];
			$return['avsResponse'] = isset(self::$avsMap[$responseArray[5]]) ? self::$avsMap[$responseArray[5]] : 'NO VALUE RETURNED';
			$return['transactionID'] = $responseArray[6];
			$return['invoiceNumber'] = $responseArray[7];
			$return['description'] = $responseArray[8];
			$return['amount'] = $responseArray[9];
			$return['method'] = $responseArray[10];
			$return['type'] = $responseArray[11];
			$return['memberID'] = $responseArray[12];
			$return['cvvCode'] = $responseArray[38];
			$return['cvvResponse'] = isset(self::$cvvMap[$responseArray[38]]) ? self::$cvvMap[$responseArray[38]] : 'NO VALUE RETURNED';
			$md5verify = $transaction['hashKey'].$transaction['login'].$responseArray[7].$responseArray[10];
			$verification = md5($md5verify);
			$securityHash = strtolower($responseArray[37]);
			if ($verification === $securityHash) {
				$return['error'] = false;
			} else {
				$return['error'] = 'Security hash does not match: '.$securityHash;
				trigger_error('Security hash does not match in transaction response for member '.$transaction['member_id'].', invoice '.$transaction['invoice_number'], E_USER_WARNING);
			}
			return $return;
		} // function authorizeTransaction
	} // class gatewayAuthorize

?>