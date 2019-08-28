<?

	require_once('admin.php');

	$message = false;
	$error = array();
	switch (getRequest('action')) {
		case 'updateSystemSettings':
			if (systemSettings::updateConfig()) {
				addSuccess('System configurations updated');
			} else {
				addError('System configurations could not be updated');
			}
			break;
		default:
			break;
	}

	$template->getMessages();
	$template->assignClean('gateways', systemSettings::getSupportedGateways());
	$template->assignClean('mailProtocols', systemSettings::getSupportedMailProtocols());
	$template->assignClean('config', systemSettings::getConfig());
	$template->display('admin/systemConfiguration.htm');

?>
