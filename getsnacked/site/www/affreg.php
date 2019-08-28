<?

session_start();

startDev();

$affiliate = isset($_SESSION['affiliate']) ? $_SESSION['affiliate'] : new affiliate();

if ($_REQUEST['submit']) {
	if (isset($_REQUEST['affiliateRegistration'])) {
		$affiliate->processRegistrationForm();
	} elseif (isset($_REQUEST['affiliateLogin'])) {
		$affiliate->processLoginForm();
		if ($affiliate->get('affiliateID')) {
			$loggedIn = true;
		}
	}
}

$ao = new affiliateOutput($affiliate);

if (getErrors()) {
	$ao->getMissingFields();
	$ao->getErrorMsgs();
	echo $ao->errorMsgsDisplay();
}

$macros = array_merge($ao->registrationFormMacros(), $ao->loginFormMacros());

if (isset($loggedIn)) {
	$affiliate->retrieveOfferData();
	debug($affiliate->get('availableOffers'), 'Available Offers');
}

echo $ao->loadTemplate('templates/header.htm');
echo $ao->loadTemplate('templates/affiliateRegistrationForm.htm', $macros);
echo $ao->loadTemplate('templates/footer.htm');

endDev();

?>