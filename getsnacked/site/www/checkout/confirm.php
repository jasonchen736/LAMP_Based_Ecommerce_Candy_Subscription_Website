<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();
	$submitAction = getRequest('submit');

	// default invalid order, display invalid order template
	$action = 'invalid';
	// retrieve package ID or enter new package into database
	$user->logPackage();
	if ($user->validOrder()) {
		// display authorize form
		$action = 'confirm';
		if ($submitAction == 'Place Order') {
			$termsAccepted = getPost('termsAccepted');
			if ($termsAccepted) {
				if ($user->enterOrder()) {
					if ($user->processPayment()) {
						// order success
						$action = 'success';
					}
				}
			} else {
				addError('You must accept the terms and conditions in order to complete your purchase');
			}
		}
	}

	// initialize template
	$template = new template;
	$template->assignClean('customerLoggedIn', customerCore::validate());

	switch ($action) {
		case 'success':
			addSuccess('Your order has been received');
			// order completed, first retain order data and clear order
			$billingData = $user->getObjectData('billingAddress', 'record');
			$shippingData = $user->getObjectData('shippingAddress', 'record');
			$orderData = $user->getObjectData('order', 'record');
			$packageData = $user->getObjectData('package', 'contents');
			$shippingCost = $user->getObjectData('order', 'shippingCost');
			$finalCost = $user->getObjectData('order', 'finalOrderCost');
			$paymentMethod = $user->getObjectData('paymentMethod', 'record');
			$user->clearOrder();
			tracker::clearOfferData();
			tracker::dropReferenceCookie();
			checkoutPath::clearCompleted();
			if ($orderData['orderID']) {
				addSuccess('Your order number is: '.$orderData['orderID']);
			}
			addSuccess('Please print this page for your records');
			// authorization success, display order success
			$template->assignClean('order', $orderData);
			$template->assignClean('package', $packageData);
			$template->assignClean('shippingCost', $shippingCost);
			$template->assignClean('finalCost', $finalCost);
			$template->assignClean('billingAddress', $billingData);
			$template->assignClean('shippingAddress', $shippingData);
			$template->assignClean('paymentMethod', $paymentMethod);
			$template->assign('items', ordersController::getOrderItems($orderData['orderID']));
			// instantiate mailer
			$mailer = new mailer;
			// load campaign template
			$template->registerCampaignResource();
			$mailer->setMessage('subject', $template->fetch('campaign:orderReceipt:subject'));
			$mailer->setMessage('from', $template->fetch('campaign:orderReceipt:from'));
			$mailer->setMessage('html', $template->fetch('campaign:orderReceipt:html'));
			$mailer->setMessage('text', $template->fetch('campaign:orderReceipt:text'));
			$emailSent = false;
			if ($mailer->composeMessage()) {
				if ($billingData['email']) {
					$mailer->addRecipient($billingData['email']);
				}
				if ($shippingData['email'] && $shippingData['email'] != $billingData['email']) {
					$mailer->addRecipient($shippingData['email']);
				}
				if (isset($_SESSION['checkout']['member'])) {
					$member = $_SESSION['checkout']['member'];
					$memberEmail = $member->get('email');
					if ($memberEmail && $memberEmail != $shippingData['email'] && $memberEmail != $billingData['email']) {
						$mailer->addRecipient($memberEmail);
					}
				}
				$emailSent = $mailer->send();
			}
			// notify admins
			$adminEmails = systemSettings::get('ADMINEMAILS');
			if ($adminEmails) {
				$mailer = new mailer;
				$mailer->setMessage('subject', $template->fetch('campaign:orderAlert:subject'));
				$mailer->setMessage('from', $template->fetch('campaign:orderAlert:from'));
				$mailer->setMessage('html', $template->fetch('campaign:orderAlert:html'));
				$mailer->setMessage('text', $template->fetch('campaign:orderAlert:text'));
				if ($mailer->composeMessage()) {
					foreach ($adminEmails as $email) {
						$mailer->addRecipient($email);
					}
					$mailer->send();
				}
			}
			if ($emailSent) {
				addSuccess('A confirmation of your order has been mailed to your email address');
			}
			$template->assignClean('emailSent', $emailSent > 0);
			$page = 'confirmed.htm';
			break;
		case 'confirm':
			$template->assignClean('package', $user->getObjectData('package', 'contents'));
			$template->assignClean('shippingCost', $user->getObjectData('order', 'shippingCost'));
			$template->assignClean('finalCost', $user->getObjectData('order', 'finalOrderCost'));
			$template->assignClean('billingAddress', $user->getObjectData('billingAddress', 'form'));
			$template->assignClean('shippingAddress', $user->getObjectData('shippingAddress', 'form'));
			$paymentForm = $user->getObjectData('paymentMethod', 'form');
			$template->assignClean('paymentMethod', $paymentForm);
			$lastFour = $paymentForm['paymentMethod'] == 'cc' ? $paymentForm['ccNum'] : $paymentForm['accNum'];
			$lastFour = substr($lastFour, -4);
			$template->assignClean('acc_num_last_four', $lastFour);
			$page = 'confirmation.htm';
			break;
		case 'invalid':
		default:
			$errors = array(
				'Your order has not been completed.',
				'You may use the navigaton to go back and complete your order.'
			);
			$template->assignClean('invalidOrderReasons', $errors);
			clearErrors();
			$page = 'invalidOrder.htm';
			break;
	}

	$template->setCheckoutData();
	$template->setCartData();
	$template->setProductsGateway();
	$template->getMessages();
	$template->display('site/'.$page);

?>