<?

	class systemSettings {

		private static $systemSettings = array(
			'SITEID' => array(
				'configID' => 'site_id',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'SITENAME' => array(
				'configID' => 'site_name',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'SITEURL' => array(
				'configID' => 'site_url',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'COMPANYNAME' => array(
				'configID' => 'company_name',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'SLOGAN' => array(
				'configID' => 'slogan',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINADDRESS1' => array(
				'configID' => 'main_address1',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINADDRESS2' => array(
				'configID' => 'main_address2',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINADDRESS3' => array(
				'configID' => 'main_address3',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINCITY' => array(
				'configID' => 'main_city',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINSTATE' => array(
				'configID' => 'main_state',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINPOSTAL' => array(
				'configID' => 'main_postal',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINPHONE' => array(
				'configID' => 'main_phone',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAINFAX' => array(
				'configID' => 'main_fax',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'SUBSCRIPTIONS' => array(
				'configID' => 'subscriptions',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'SHIPPINGDATES' => array(
				'configID' => 'shipping_dates',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'PACKAGEQUANTITY' => array(
				'configID' => 'package_quantity',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAXPROMOTIONS' => array(
				'configID' => 'max_promotions_allowed',
				'isArray' => false,
				'default' => 3,
				'set' => false
			),
			'FORCESAVEBILLING' => array(
				'configID' => 'force_save_billing',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'FORCESAVESHIPPING' => array(
				'configID' => 'force_save_shipping',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'ANALYTICS' => array(
				'configID' => 'analytics',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'GATEWAY' => array(
				'configID' => 'gateway',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'LINKPOINTHOST' => array(
				'configID' => 'linkpoint_host',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'LINKPOINTPORT' => array(
				'configID' => 'linkpoint_port',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'LINKPOINTCONFIGFILE' => array(
				'configID' => 'linkpoint_configfile',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'AUTHNETURL' => array(
				'configID' => 'auth_net_url',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'AUTHNETLOGINID' => array(
				'configID' => 'auth_net_login_id',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'AUTHNETTRANSACTIONKEY' => array(
				'configID' => 'auth_net_transaction_key',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'AUTHNETHASHKEY' => array(
				'configID' => 'auth_net_hash_key',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'SHIPPINGFROMSTATE' => array(
				'configID' => 'shipping_from_state',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'SHIPPINGFROMPOSTAL' => array(
				'configID' => 'shipping_from_postal',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'SHIPPINGFROMCOUNTRY' => array(
				'configID' => 'shipping_from_country',
				'isArray' => false,
				'default' => 'US',
				'set' => false
			),
			'UPS' => array(
				'configID' => 'ups',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'UPSACCESSNUMBER' => array(
				'configID' => 'ups_access_number',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'UPSUSERNAME' => array(
				'configID' => 'ups_user_name',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'UPSPASSWORD' => array(
				'configID' => 'ups_password',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'UPSACCOUNTNUMBER' => array(
				'configID' => 'ups_account_number',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'FEDEX' => array(
				'configID' => 'fedex',
				'isArray' => false,
				'default' => 'false',
				'set' => false
			),
			'FEDEXKEY' => array(
				'configID' => 'fedex_key',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'FEDEXPASSWORD' => array(
				'configID' => 'fedex_password',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'FEDEXACCOUNTNUMBER' => array(
				'configID' => 'fedex_account_number',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'FEDEXMETERNUMBER' => array(
				'configID' => 'fedex_meter_number',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'DATABASE' => array(
				'configID' => 'database',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'SESSIONDURATION' => array(
				'configID' => 'session_duration',
				'isArray' => false,
				'default' => 1800,
				'set' => false
			),
			'MAINCOOKIE' => array(
				'configID' => 'main_cookie',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'COOKIEDOMAIN' => array(
				'configID' => 'cookie_domain',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'ERRORPAGE' => array(
				'configID' => 'error_page',
				'isArray' => false,
				'default' => '/error/status/code/500',
				'set' => false
			),
			'LIBRARYPATH' => array(
				'configID' => 'library_path',
				'isArray' => false,
				'default' => '/library/',
				'set' => false
			),
			'TEMPLATEDIR' => array(
				'configID' => 'template_dir',
				'isArray' => false,
				'default' => '/templates/',
				'set' => false
			),
			'IMAGEDIR' => array(
				'configID' => 'image_dir',
				'isArray' => false,
				'default' => '/www/images/',
				'set' => false
			),
			'SOURCEDIR' => array(
				'configID' => 'source_dir',
				'isArray' => false,
				'default' => 'main',
				'set' => false
			),
			'CERTIFICATESDIR' => array(
				'configID' => 'certificates_dir',
				'isArray' => false,
				'default' => '/certificates/',
				'set' => false
			),
			'DEBUG' => array(
				'configID' => 'debug',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'OFFLINE' => array(
				'configID' => 'offline',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'FORCEDEVELOPMENTENVIRONMENT' => array(
				'configID' => 'force_development_environment',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAILPROTOCOL' => array(
				'configID' => 'mail_protocol',
				'isArray' => false,
				'default' => 'nativemail',
				'set' => false
			),
			'MAILSERVER' => array(
				'configID' => 'mail_server',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'MAILPORT' => array(
				'configID' => 'mail_port',
				'isArray' => false,
				'default' => 25,
				'set' => false
			),
			'MAILAUTHENTICATION' => array(
				'configID' => 'mail_authentication',
				'isArray' => false,
				'default' => false,
				'set' => false
			),
			'MAILUSER' => array(
				'configID' => 'mail_user',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'MAILPASSWORD' => array(
				'configID' => 'mail_password',
				'isArray' => false,
				'default' => '',
				'set' => false
			),
			'CHECKOUTPATH' => array(
				'configID' => 'checkout_path',
				'isArray' => true,
				'default' => array(
					array('url' => '/checkout/account', 'name' => 'Account Info', 'protocol' => 'https'),
					array('url' => '/checkout/billing', 'name' => 'Billing Info', 'protocol' => 'https'),
					array('url' => '/checkout/shipping', 'name' => 'Shipping Info', 'protocol' => 'https'),
					array('url' => '/checkout/shippingOption', 'name' => 'Shipping Method', 'protocol' => 'https'),
					array('url' => '/checkout/confirm', 'name' => 'Confirm Order', 'protocol' => 'https')
				),
				'set' => false
			),
			'ADMINEMAILS' => array(
				'configID' => 'admin_email',
				'isArray' => true,
				'default' => array(),
				'set' => false
			)
		);

		private static $gateways = array(
			'authorize' => 'Authorize.net',
			'linkpoint' => 'Linkpoint'
		);

		private static $mailProtocols = array(
			'nativemail' => 'Native Mail',
			'sendmail' => 'SendMail',
			'smtp' => 'SMTP'
		);

		// directory of the system backend
		private static $systemRoot;
		// directory of the site front end
		private static $siteRoot;
		// last two sections of the host name (host: www.example.com -> siteDomain: .example.com)
		private static $siteDomain;
		// contents of site_conf
		private static $site_conf;

		/**
		 *  Retrieve a system setting
		 *  Args: (str) system setting
		 *  Return: (mixed) system setting value
		 */
		public static function get($setting) {
			if (array_key_exists($setting, self::$systemSettings)) {
				return self::$systemSettings[$setting]['set'];
			}
			return NULL;
		} // function get

		/**
		 *  Get current configuration settings in an array exactly as it is on site_conf
		 *  If a setting is set as "default" will return as "default" not as the actual default value
		 *  Args: none
		 *  Return: (array) configuration settings
		 */
		public static function getConfig() {
			$config = array();
			foreach (self::$systemSettings as $key => $val) {
				$config[$val['configID']] = $val['set'];
			}
			$gatewayCertificateFile = self::get('CERTIFICATESDIR').'linkpoint.cert';
			if (file_exists($gatewayCertificateFile)) {
				$gatewayCertificate = file_get_contents($gatewayCertificateFile);
				$config['gateway_certificate'] = $gatewayCertificate;
			}
			return $config;
		} // function getConfig

		/**
		 *  Retrieve array of supported gateways
		 *  Args: none
		 *  Return: (array) supported gateways
		 */
		public static function getSupportedGateways() {
			return self::$gateways;
		} // function getSupportedGateways

		/**
		 *  Retrieve array of supported mail protocols
		 *  Args: none
		 *  Return: (array) supported mail protocols
		 */
		public static function getSupportedMailProtocols() {
			return self::$mailProtocols;
		} // function getSupportedMailProtocols

		/**
		 *  Read configuration file, initialize settings
		 *  Args: none
		 *  Return: none
		 */
		public static function configure() {
			self::$siteRoot = preg_replace('/(.*)\/.*$/', '$1', $_SERVER['DOCUMENT_ROOT']);
			self::$systemRoot = preg_replace('/(.*)\/.*$/', '$1', self::$siteRoot);
			$hostParts = explode('.', $_SERVER['HTTP_HOST']);
			$numParts = count($hostParts);
			self::$siteDomain = '.'.$hostParts[$numParts - 1].'.'.$hostParts[$numParts - 2];
			self::$systemSettings['MAINCOOKIE']['default'] = $_SERVER['HTTP_HOST'];
			self::$systemSettings['COOKIEDOMAIN']['default'] = self::$siteDomain;
			self::readConfig();
			self::establishSystemSettings();
			self::establishDevEnvironment();
		} // function configure

		/**
		 *  Read configuration file into internal var
		 *  Args: none
		 *  Return: (boolean) success
		 */
		private static function readConfig() {
			self::$site_conf = file_get_contents(self::$siteRoot.'/site_conf');
		} // function readConfig

		/**
		 *  Parse and set all system settings
		 *  Args: none
		 *  Return: none
		 */
		private static function establishSystemSettings() {
			foreach (self::$systemSettings as $key => &$val) {
				if (!$val['isArray']) {
					$matchFunction = 'preg_match';
				} else {
					$matchFunction = 'preg_match_all';
				}
				if ($matchFunction('/'.$val['configID'].'=([^\r\n]*)\r?\n?/', self::$site_conf, $setting)) {
					if (!$val['isArray'] && !is_array($setting[1])) {
						$setting[1] = trim($setting[1]);
						if ($setting[1] != 'default') {
							$val['set'] = $setting[1] == 'false' ? false : $setting[1];
						} else {
							$val['set'] = self::getSettingDefault($key, $val['default']);
						}
					} elseif ($val['isArray']) {
						if (!is_array($setting[1])) {
							if ($setting[1] != 'default') {
								$val['set'] = array($setting[1]);
							} else {
								$val['set'] = self::getSettingDefault($key, $val['default']);
							}
						} else {
							switch ($key) {
								case 'CHECKOUTPATH':
									$val['set'] = array();
									foreach ($setting[1] as &$path) {
										// remove open and close brackets for proper explode
										$path = preg_replace('/(^\[|\]$)/', '', trim($path));
										$path = explode('][', $path);
										$val['set'][] = array('url' => $path[0], 'name' => $path[1], 'protocol' => $path[2]);
									}
									break;
								default:
									$val['set'] = $setting[1];
									break;
							}
						}
					} else {
						$val['set'] = self::getSettingDefault($key, $val['default']);
					}
				} else {
					$val['set'] = self::getSettingDefault($key, $val['default']);
				}
			}
			self::$site_conf = false;
		} // function establishSystemSettings

		/**
		 *  Get setting default, perform necessary preprocessing to specific settings
		 *  Args: (str) name of setting, (mixed) default value
		 *  Return: (mixed) default value
		 */
		private static function getSettingDefault($constant, $default) {
			switch ($constant) {
				case 'TEMPLATEDIR':
				case 'IMAGEDIR':
					$value = self::$siteRoot.$default;
					break;
				case 'LIBRARYPATH':
					$value = self::$systemRoot.$default;
					break;
				default:
					$value = $default;
					break;
			}
			return $value;
		} // function getSettingDefault

		/**
		 *  Define DEVENVIRONMENT constant
		 *  Args: none
		 *  Return: none
		 */
		private static function establishDevEnvironment() {
			if (self::get('FORCEDEVELOPMENTENVIRONMENT')) {
				define('DEVENVIRONMENT', true);
			} else {
				define('DEVENVIRONMENT', false);
			}
		} // function establishDevEnvironment

		/**
		 *  Write configuration to file
		 *  Args: none
		 *  Return: none
		 */
		private static function writeConfig() {
			$config = '';
			foreach (self::$systemSettings as $key => $val) {
				if (!$val['isArray']) {
					if ($val['set'] === true) {
						$write = 'true';
					} elseif ($val['set'] === false) {
						$write = 'false';
					} else {
						$write = $val['set'];
					}
					$config .= $val['configID'].'='.$write."\r\n";
				} else {
					if ($key == 'CHECKOUTPATH') {
						foreach ($val['set'] as $index => $value) {
							$config .= $val['configID'].'=['.$value['url'].']['.$value['name'].']['.$value['protocol'].']'."\r\n";
						}
					} else {
						foreach ($val['set'] as $index => $value) {
							$config .= $val['configID'].'='.$value."\r\n";
						}
					}
				}
			}
			$file = fopen(self::$siteRoot.'/site_conf', 'w');
			$written = fwrite($file, $config);
			fclose($file);
			// check if site name has changed, update registry name as needed
			if ($written) {
				$siteName = self::get('SITENAME');
				$siteRegistry = new siteRegistry(self::get('SITEID'));
				if ($siteRegistry->exists()) {
					if ($siteName != $siteRegistry->get('siteName')) {
						$siteRegistry->set('siteName', $siteName);
						if (!$siteRegistry->update()) {
							addError('There was an error while updating the site name');
							trigger_error('Site registry failed while attempting to update site name for id '.self::get('SITEID'), E_USER_WARNING);
						}
					}
				} else {
					trigger_error('Site registry not found on update for id '.self::get('SITEID').', name '.self::get('SITENAME'), E_USER_WARNING);
				}
			}
			return $written;
		} // function writeConfig

		/**
		 *  Retrieve new settings from post, set and execute write
		 *  Args: none
		 *  Return: (boolean) success
		 */
		public static function updateConfig() {
			foreach (self::$systemSettings as $key => &$val) {
				if (!$val['isArray']) {
					$value = getPost($val['configID']);
					switch ($key) {
						case 'GATEWAY':
							if (array_key_exists($value, self::$gateways)) {
								$val['set'] = $value;
								if ($value == 'linkpoint') {
									$keyfile = getPost('linkpoint_keyfile');
									$certificatesRoot = systemSettings::get('CERTIFICATESDIR');
									$file = fopen($certificatesRoot.'linkpoint.cert', 'w');
									$written = fwrite($file, $keyfile);
									fclose($file);
									if ($written) {
										addSuccess('Linkpoint certificate file saved successfully');
									} elseif (!empty($keyfile)) {
										addError('An error occurred when saving your Linkpoint certificate file');
									}
								}
							} else {
								$val['set'] = '';
							}
							break;
						case 'MAILPROTOCOL':
							if (array_key_exists($value, self::$mailProtocols)) {
								$val['set'] = $value;
							} else {
								$val['set'] = 'nativemail';
							}
							break;
						default:
							if (!is_null($value)) {
								$val['set'] = $value == 'false' ? false : $value;
							}
							break;
					}
				} else {
					switch ($key) {
						case 'CHECKOUTPATH':
							$checkout_path_url = getPost('checkout_path_url');
							$checkout_path_name = getPost('checkout_path_name');
							$checkout_path_protocol = getPost('checkout_path_protocol');
							if (
								is_array($checkout_path_url) && !empty($checkout_path_url) &&
								is_array($checkout_path_name) && !empty($checkout_path_name) &&
								is_array($checkout_path_protocol) && !empty($checkout_path_protocol)
							) {
								$checkout_path = array();
								foreach ($checkout_path_url as $index => $url) {
									if (isset($checkout_path_url[$index]) && isset($checkout_path_name[$index]) && isset($checkout_path_protocol[$index])) {
										$checkout_path[] = array('url' => $checkout_path_url[$index], 'name' => $checkout_path_name[$index], 'protocol' => $checkout_path_protocol[$index]);
									}
								}
								$val['set'] = $checkout_path;
							} else {
								$val['set'] = $val['default'];
							}
							break;
						default:
							$value = getPost($val['configID']);
							if (is_array($value) && $value) {
								foreach ($value as $index => $arrayVal) {
									if (!$arrayVal) {
										unset($value[$index]);
									}
								}
								$val['set'] = $value;
							} else {
								$val['set'] = $val['default'];
							}
							break;
					}
				}
			}
			return self::writeConfig();
		} // function updateConfig

		/**
		 *  Retrieve site id of current site by site name and write to site_conf
		 *  Args: none
		 *  Return: (boolean) successful write
		 */
		public static function writeSiteID() {
			$siteName = systemSettings::get('SITENAME');
			$registry = new siteRegistry;
			if ($registry->loadSiteName($siteName)) {
				$siteID = $registry->get('siteID');
				self::$systemSettings['SITEID']['set'] = $siteID;
				if (self::writeConfig()) {
					return true;
				} else {
					addError('There was an error while attempting to write site '.$siteName.' (Site ID: '.$siteID.') to configuration file');
				}
			} else {
				addError('There was an error while attempting to load site '.$siteName.' while attempting to write the Site ID');
			}
			return false;
		} // function writeSiteID
	} // class systemSettings

?>