<?

	// track landing
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$email = getRequest('email');
	if (validEmail($email)) {
		$sql = "INSERT IGNORE INTO `unsubs` (`email`) VALUES ('".prepDB($email)."')";
		$result = query($sql);
		if ($GLOBALS['_dbh']->sqlError === false) {
			// initialize template
			$template = new template;
			$template->assignClean('itemCount', $user->getObjectData('package', 'itemCount'));
			$template->assignClean('message', $email.' has been successfully unsubscribed.');
			$template->setCheckoutData();
			$template->display('site/content.htm');
		} else {
			redirect('/content/show/article/optout/error/There was an error, please try again later.');
		}
	} else {
		redirect('/content/show/article/optout/error/Invalid Email Address');
	}

?>