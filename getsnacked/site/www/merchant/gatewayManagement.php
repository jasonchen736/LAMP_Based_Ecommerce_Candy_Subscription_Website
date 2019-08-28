<?

	require_once 'merchant.php';

	$actions = array(
		'displayGatewayInfo',
		'editGateway',
		'updateGateway',
		'verifyGateway',
		'performVerification'
	);

	$action = getRequest('action');
	if (in_array($action, $actions)) {
		$action();
	} else {
		displayGatewayInfo();
	}

	/**
	 *  Display merchant gateway info
	 *  Args: none
	 *  Return: none
	 */
	function displayGatewayInfo() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$memberGatewayInfo = membersController::getMemberGatewayInfo($merchantInfo['id']);
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('memberGatewayInfo', $memberGatewayInfo->fetchArray());
		$template->getMessages();
		$template->display('merchant/gatewayInfo.htm');
	} // function displayGatewayInfo

	/**
	 *  Edit merchant gateway section
	 *  Args: none
	 *  Return: none
	 */
	function editGateway() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$memberGatewayInfo = membersController::getMemberGatewayInfo($merchantInfo['id']);
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('memberGatewayInfo', $memberGatewayInfo->fetchArray());
		$template->assignClean('gatewayOptions', memberGatewayInfo::gatewayOptions());
		$template->getMessages();
		$template->display('merchant/gatewayEdit.htm');
	} // function editGateway

	/**
	 *  Update an existing merchant gateway record
	 *  Args: none
	 *  Return: none
	 */
	function updateGateway() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$member = new member($merchantInfo['id']);
		$gatewayOptions = memberGatewayInfo::gatewayOptions();
		$memberGatewayInfo = membersController::getMemberGatewayInfo($merchantInfo['id']);
		$gateway = getPost('gateway');
		if (in_array($gateway, $gatewayOptions)) {
			$memberGatewayInfo->set('gateway', $gateway);
			$memberGatewayInfo->set('url', getPost('url'));
			$now = date('Y-m-d H:i:s');
			switch ($gateway) {
				case 'authorize':
					$memberGatewayInfo->makeRequired('login');
					$memberGatewayInfo->makeRequired('key');
					$memberGatewayInfo->makeRequired('hash');
					$memberGatewayInfo->set('login', getPost('login'));
					$memberGatewayInfo->set('key', getPost('key'));
					if ($memberGatewayInfo->exists()) {
						$memberGatewayInfo->set('hash', "AES_ENCRYPT('".getPost('hash', 'alphanum')."', CONCAT(`dateAdded`, ':".$member->get('dateCreated')."'))", false);
					} else {
						$memberGatewayInfo->set('hash', "AES_ENCRYPT('".getPost('hash', 'alphanum')."', '".$now.":".$member->get('dateCreated')."')", false);
					}
					$memberGatewayInfo->enclose('hash', false);
					$memberGatewayInfo->set('port', '');
					break;
				case 'linkpoint':
					$memberGatewayInfo->makeRequired('port');
					$memberGatewayInfo->makeRequired('key');
					$memberGatewayInfo->set('port', getPost('port'));
					$memberGatewayInfo->set('key', getPost('key'));
					$memberGatewayInfo->set('login', '');
					$memberGatewayInfo->set('hash', '');
					break;
				default:
					break;
			}
			if ($memberGatewayInfo->exists()) {
				if (!$memberGatewayInfoSaved = $memberGatewayInfo->update()) {
					addError('There was an error while updating the gateway information');
				}
			} else {
				$memberGatewayInfo->set('memberID', $merchantInfo['id']);
				$memberGatewayInfo->set('dateAdded', $now);
				if (!$memberGatewayInfoSaved = $memberGatewayInfo->save()) {
					addError('There was an error while saving the gateway information');
				}
			}
			if ($memberGatewayInfoSaved) {
				switch ($gateway) {
					case 'linkpoint':
						if (!$memberGatewayInfo->saveCertificate(getPost('certificate', 'alphanum'))) {
							addError('There was an error while writing the gateway certificate file');
							addErrorField('certificate');
						} else {
							addSuccess('Payment gateway has been set up successfully');
						}
						break;
					default:
						if (file_exists(systemSettings::get('CERTIFICATESDIR').$merchantInfo['id'].'.cert')) {
							unlink(systemSettings::get('CERTIFICATESDIR').$merchantInfo['id'].'.cert');
						}
						addSuccess('Payment gateway has been set up successfully');
						break;
				}
				editGateway();
				exit;
			}
			$memberGatewayInfo->assertRequired();
		} else {
			addError('Gateway is not supported');
		}
		addError('There was an error while updating the gateway information');
		$template = new template;
		$template->assignClean('merchantInfo', $merchantInfo);
		$template->assignClean('memberGatewayInfo', $memberGatewayInfo->fetchArray());
		$template->assignClean('gatewayOptions', $gatewayOptions);
		$template->getMessages();
		$template->display('merchant/gatewayEdit.htm');
	} // function updateGateway

	function verifyGateway() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$expYears = array();
		for ($i = 0; $i <= 10; $i++) {
			$expYears[] = date('Y', strtotime('+ '.$i.' year'));
		}	
		$expMonths = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
		$template = new template;
		$template->assignClean('merchantInfo', merchantCore::getMerchantInfo());
		$template->assignClean('stateOptions', formObject::stateOptions());
		$template->assignClean('expMonths', $expMonths);
		$template->assignClean('expYears', $expYears);
		$template->assignClean('first', getPost('first'));
		$template->assignClean('last', getPost('last'));
		$template->assignClean('address', getPost('address'));
		$template->assignClean('city', getPost('city'));
		$template->assignClean('state', getPost('state'));
		$template->assignClean('postal', getPost('postal'));
		$template->assignClean('ccType', getPost('ccType'));
		$template->assignClean('expMonth', getPost('expMonth'));
		$template->assignClean('expYear', getPost('expYear'));
		$template->assignClean('ccNum', getPost('ccNum'));
		$template->assignClean('cvv', getPost('cvv'));
		$template->getMessages();
		$template->display('merchant/verifyGateway.htm');
	} // function verifyGateway

	function performVerification() {
		$merchantInfo = merchantCore::getMerchantInfo();
		$member = new member($merchantInfo['id']);
		$memberGateways = membersController::getMemberGateways(array($merchantInfo['id']));
		$first = getPost('first');
		$last = getPost('last');
		$address = getPost('address');
		$city = getPost('city');
		$state = getPost('state');
		$postal = getPost('postal');
		$ccType = getPost('ccType');
		$expMonth = getPost('expMonth');
		$expYear = getPost('expYear');
		$ccNum = getPost('ccNum');
		$cvv = getPost('cvv');
		$certificatesRoot = systemSettings::get('CERTIFICATESDIR');
		authorize::setup();
		$transaction = array(
			'type'           => 'AUTHORIZEONLY',
			'customer_id'    => 0,
			'description'    => 'GATEWAY VERIFICATION FOR MEMBER '.$merchantInfo['id'],
			'method'         => 'CC',
			'invoice_num'    => time(),
			'card_num'       => $ccNum,
			'exp_month'      => $expMonth,
			'exp_year'       => $expYear,
			'card_code'      => $ccNum,
			'amount'         => 1,
			'first_name'     => $first,
			'last_name'      => $last,
			'address'        => $address,
			'city'           => $city,
			'state'          => $state,
			'zip'            => $zip,
			'country'        => $country,
			'bank_aba_code'  => '',
			'bank_acct_num'  => '',
			'bank_acct_type' => '',
			'bank_name'      => '',
			'bank_acct_name' => ''
		);
		$transaction['member_id'] = $merchantInfo['id'];
		$transaction['gateway'] = $memberGateways[$merchantInfo['id']]['gateway'];
		$transaction['host'] = $memberGateways[$merchantInfo['id']]['url'];
		switch ($memberGateways[$merchantInfo['id']]['gateway']) {
			case 'authorize':
				$transaction['login'] = $memberGateways[$merchantInfo['id']]['login'];
				$transaction['key'] = $memberGateways[$merchantInfo['id']]['decrypted'];
				$transaction['hashKey'] = $memberGateways[$merchantInfo['id']]['key'];
				break;
			case 'linkpoint':
				$transaction['port'] = $memberGateways[$merchantInfo['id']]['port'];
				$transaction['key'] = $memberGateways[$merchantInfo['id']]['key'];
				$transaction['certificate'] = $certificatesRoot.$merchantInfo['id'].'.cert';
				break;
			default:
				// this should never happen
				break;
		}
		$response = authorize::authorizeTransaction($transaction);
		if ($response && isset($response['responseText']) && $response['responseText'] == 'Approved') {
			if (membersController::activateGateway($merchantInfo['id'])) {
				addSuccess('Your payment gateway has been successfully verified');
			}
		} else {
			membersController::deactivateGateway($merchantInfo['id']);
			addError('Verification failed, please check your setup or verification information');
		}
		verifyGateway();
	} // function performVerification

?>