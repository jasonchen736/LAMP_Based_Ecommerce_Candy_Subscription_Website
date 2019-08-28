<?

	if (!adminCore::validate()) {
		redirect('/admin/login');
	} elseif (!adminCore::checkSiteRegistry()) {
		$_autoRegisterPages = array(
			'/admin/siteManagement/action/autoRegisterPrompt',
			'/admin/siteManagement/action/autoRegister'
		);
		if (!in_array($_SERVER['REQUEST_URI'], $_autoRegisterPages)) {
			redirect('/admin/siteManagement/action/autoRegisterPrompt');
		}
	}

	$_startDate = adminCore::get('startDate');
	$_endDate = adminCore::get('endDate');

	// initialize template
	$template = new template;
	$template->assignClean('_startDate', $_startDate);
	$template->assignClean('_endDate', $_endDate);
	$template->assignClean('_startDateDisplay', date('m/d/Y', strtotime($_startDate)));
	$template->assignClean('_endDateDisplay', date('m/d/Y', strtotime($_endDate)));

?>