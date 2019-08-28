<?

	$article = getRequest('article', 'alphanum');

	// track landing, if not referred, set subid to indicate content page landing
	if (!tracker::wasReferred()) {
		$_REQUEST['s'] = 'content:'.$article;
	}
	tracker::trackLanding();

	// initialize checkout path variables
	checkoutPath::mapLocation(systemSettings::get('CHECKOUTPATH'));

	$user = setUser();

	// check for valid content
	if ($article) {
		$result = query("SELECT `a`.`contentID` FROM `content` `a` JOIN `contentSiteMap` `b` WHERE `a`.`name` = '".prep($article)."' AND `b`.`siteID` = '".systemSettings::get('SITEID')."'");
		if ($result->rowCount) {
			// initialize template
			$template = new template;
			$template->assignClean('customerLoggedIn', customerCore::validate());
			$template->registerContentResource();
			$content = $template->fetch('content:'.$article);
			$template->assign('content', $content);
			$template->setCheckoutData();
			$template->setCartData();
			$template->setProductsGateway();
			$template->getMessages();
			$template->display('site/content.htm');
		} else {
			redirect('/error/status/code/404');
		}
	} else {
		redirect('/error/status/code/404');
	}

?>