<?

	require_once 'lphp.php';

	class gatewayLinkpoint {
		private static $transactionMap = array(
			'CHARGE' => 'SALE',
			'AUTHORIZEONLY' => 'AUTHORIZE ONLY',
			'REFUND' => 'CREDIT',
			'VOID' => 'VOID'
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
			$lpAuthorize = new lphp;
			// set post values
			$order['host'] = authorize::$testMode ? 'staging.linkpt.net' : $transaction['host'];
			$order['port'] = $transaction['port'];
			$order['keyfile'] = $transaction['certificate'];
			$order['configfile'] = $transaction['key'];
			$order['ordertype'] = $transaction['type'];
			if ($transaction['type'] == self::$transactionMap['VOID'] || $transaction['type'] ==self::$transactionMap['REFUND']) {
				$order['oid'] = $transaction['referenceID'];
			}
			$order['result'] = 'LIVE';
			$order['chargetotal'] = $transaction['amount'];
			$order['name'] = $transaction['first_name'].' '.$transaction['last_name'];
			$order['country'] = $transaction['country'];
			$order['address1'] = $transaction['address'];
			$order['city'] = $transaction['city'];
			$order['state'] = $transaction['state'];
			$order['zip'] = $transaction['zip'];
			$order['referred'] = $transaction['customer_id'];
			$order['comments'] = $transaction['description'].' | Sub Order Number: '.$transaction['invoice_num'];
			switch (strtolower($transaction['method'])) {
				case 'cc':
					// credit card payment
					$order['cardnumber'] = $transaction['card_num'];
					$order['cardexpmonth'] = $transaction['exp_month'];
					$order['cardexpyear'] = substr($transaction['exp_year'], 2, 2);
					$order['cvmindicator'] = 'provided';
					$order['cvmvalue'] = $transaction['card_code'];
					$order['addrnum'] = $transaction['address'];
					break;
				case 'echeck':
					// incomplete
					// echeck payment
					$order['routing'] = $transaction['bank_aba_code'];
					$order['account'] = $transaction['bank_acct_num'];
//					$order['accounttype'] = 'pc';
					$order['bankname'] = $transaction['bank_name'];
//					$order['bankstate'] = 'CA';
					break;
				default:
					break;
			}
			$response = $lpAuthorize->curl_process($order);
			$return = array();
			switch (strtolower($response['r_approved'])) {
				case 'approved':
					// perform avs and card code response check
					if ($response['r_avs'] != 'null') {
						$avsResponse = substr($response['r_avs'], 0, 3);
						$cvvResponse = substr($response['r_avs'], 3, 1);
					} else {
						$avsResponse = '';
						$cvvResponse = '';
					}
					$return['responseCode'] = 1;
					$return['responseText'] = 'Approved';
					$return['subCode'] = $response['r_ref'];
					$return['reasonCode'] = '';
					$return['reasonText'] = $response['r_code'];
					$return['approvalCode'] = '';
					$return['transactionID'] = $response['r_ordernum'];
					$return['invoiceNumber'] = $transaction['invoice_num'];
					$return['description'] = $transaction['description'];
					$return['amount'] = $transaction['amount'];
					$return['method'] = $transaction['method'];
					$return['type'] = $transaction['type'];
					$return['memberID'] = $transaction['customer_id'];
					$return['cvvCode'] = '';
					$return['cvvResponse'] = $cvvResponse;
					$return['avsCode'] = '';
					$return['avsResponse'] = $avsResponse;
					$return['error'] = false;
					$verificationError = array();
					if ($avsResponse{0} != 'Y' || $avsResponse{1} != 'Y') {
						$verificationError[] = 'Address verification does not match.';
					}
					if ($cvvResponse != 'M' && !($cvvResponse == 'P' && $transaction['card_type'] == 'AMERICANEXPRESS')) {
						$verificationError[] = 'Card code does not match.';
					}
					if (!isDevEnvironment() && !empty($verificationError) && $transaction['type'] == 'SALE') {
						// avs check failed, log initial transaction and issue a void
						$return['responseCode'] = 2;
						$return['responseText'] = 'Declined';
						$return['reasonText'] = implode(' ', $verificationError);
						$return['description'] .= ' - Failed AVS verification';
						$voidTransaction = $transaction;
						$voidTransaction['type'] = 'VOID';
						$voidTransaction['referenceID'] = $return['transactionID'];
						$voidResponse = authorize::authorizeTransaction($voidTransaction);
						if ($voidResponse['responseCode'] != 'Approved') {
							trigger_error('There was an error while voiding linkpoint transaction '.$voidResponse['transactionRecordID'].' during and AVS check fail', E_USER_WARNING);
						}
					}
					break;
				case 'failure':
					$return['responseCode'] = 3;
					$return['responseText'] = 'Error';
					$return['reasonText'] = $response['r_error'];
					$return['approvalCode'] = '';
					$return['transactionID'] = '';
					$return['invoiceNumber'] = $transaction['invoice_num'];
					$return['description'] = $transaction['description'];
					$return['amount'] = $transaction['amount'];
					$return['method'] = $transaction['method'];
					$return['type'] = $transaction['type'];
					$return['memberID'] = $transaction['customer_id'];
					$return['cvvCode'] = '';
					$return['cvvResponse'] = '';
					$return['avsCode'] = '';
					$return['avsResponse'] = '';
					$return['error'] = $response['r_error'];
					break;
				default:
					$return['responseCode'] = 2;
					$return['responseText'] = 'Declined';
					$return['subCode'] = $response['r_ref'];
					$return['reasonCode'] = '';
					$return['reasonText'] = $response['r_error'];
					$return['approvalCode'] = '';
					$return['transactionID'] = $response['r_ordernum'];
					$return['invoiceNumber'] = $transaction['invoice_num'];
					$return['description'] = $transaction['description'];
					$return['amount'] = $transaction['amount'];
					$return['method'] = $transaction['method'];
					$return['type'] = $transaction['type'];
					$return['memberID'] = $transaction['customer_id'];
					$return['cvvCode'] = '';
					$return['cvvResponse'] = isset($response['r_avs']) && $response['r_avs'] ? substr($response['r_avs'], 3, 1) : '';
					$return['avsCode'] = '';
					$return['avsResponse'] = isset($response['r_avs']) && $response['r_avs'] ? substr($response['r_avs'], 0, 3) : '';
					$return['error'] = false;
					break;
			}
			return $return;
		} // function authorizeTransaction
	} // class gatewayLinkpoint

?>