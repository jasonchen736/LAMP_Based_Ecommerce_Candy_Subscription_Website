<?

	require_once 'Smarty/libs/Smarty.class.php';

	class template extends Smarty {

		/**
		 *  Initiate smart and register paths
		 *  Args: (array) resource paths
		 *  Return: none
		 */
		public function __construct($dirPaths = false) {
			$this->Smarty();
			$this->initialize();
		} // function __construct

		/**
		 *  Set up resources that can be accessed while templating
		 *  Args: none
		 *  Return: none
		 */
		public function initialize() {
			if (empty($dirPaths)) {
				$this->template_dir = systemSettings::get('TEMPLATEDIR').'source/'.systemSettings::get('SOURCEDIR');
				$this->compile_dir  = systemSettings::get('TEMPLATEDIR').'compiled';
				$this->cache_dir    = systemSettings::get('TEMPLATEDIR').'cache';
				$this->config_dir   = systemSettings::get('TEMPLATEDIR').'configs';
			} else {
				$this->template_dir = $dirPaths[0];
				$this->compile_dir  = $dirPaths[1];
				$this->cache_dir    = $dirPaths[2];
				$this->config_dir   = $dirPaths[3];
			}
			$this->assignClean('_SOURCEDIR', systemSettings::get('SOURCEDIR'));
			$this->assignClean('_SITENAME', systemSettings::get('SITENAME'));
			$this->assignClean('_SITEURL', systemSettings::get('SITEURL'));
			$this->assignClean('_SLOGAN', systemSettings::get('SLOGAN'));
			$this->assignClean('_COMPANYNAME', systemSettings::get('COMPANYNAME'));
			$this->assignClean('_MAINADDRESS1', systemSettings::get('MAINADDRESS1'));
			$this->assignClean('_MAINADDRESS2', systemSettings::get('MAINADDRESS2'));
			$this->assignClean('_MAINADDRESS3', systemSettings::get('MAINADDRESS3'));
			$this->assignClean('_MAINCITY', systemSettings::get('MAINCITY'));
			$this->assignClean('_MAINSTATE', systemSettings::get('MAINSTATE'));
			$this->assignClean('_MAINPOSTAL', systemSettings::get('MAINPOSTAL'));
			$this->assignClean('_MAINPHONE', systemSettings::get('MAINPHONE'));
			$this->assignClean('_MAINFAX', systemSettings::get('MAINFAX'));
			$this->assignClean('_DEBUG', isDevEnvironment() && systemSettings::get('DEBUG'));
			$this->assignClean('_CUSTOMER', isset($_SESSION['checkout']['member']) ? $_SESSION['checkout']['member']->get('memberID') : false);
		} // function initialize

		/**
		 *  Assign that performs additional output escape
		 *  Args: (str) smarty assigned name, (mixed) value
		 *  Return: none
		 */
		public function assignClean($name, $value) {
			if (is_array($value)) {
				array_walk_recursive($value, 'htmlentitiesWalk');
			} else {
				$value = htmlentities($value);
			}
			$this->assign($name, $value);
		} // function assignClean

		/**
		 *  Rebuilds encoded htmlentities from a field assigned by $this->assignClean()
		 *  Sub array fields can be targetted with the syntax ">"
		 *    eg: "field1 > field2" will target $this->_tpl_vars['field1']['field2']
		 *  Args: (str) smarty assigned name, (mixed) array of entities to rebuild or string "all"
		 *  Return: none
		 */
		public function rebuildEntities($field, $entities) {
			$rebuildPatterns = array(
				'href' => array('/&lt;a href=&quot;(.*)&quot;&gt;(.*)&lt;\/a&gt;/', '<a href="\1">\2</a>')
			);
			$path = explode(' > ', $field);
			$id = '$this->_tpl_vars[\''.implode("']['", $path).'\']';
			eval('$set = isset('.$id.');');
			if ($set) {
				eval('$value = '.$id.';');
				if ($entities == 'all') {
					if (is_array($value)) {
						array_walk_recursive($value, 'htmlentitydecodeWalk');
					} else {
						$value = html_entity_decode($value);
					}
				} else {
					foreach ($entities as $entity) {
						if (array_key_exists($entity, $rebuildPatterns)) {
							$match = $rebuildPatterns[$entity][0];
							$replace = $rebuildPatterns[$entity][1];
							if (is_array($value)) {
								array_walk_recursive($value, 'rebuildEntitiesWalk', array($match, $replace));
							} else {
								$value = preg_replace($match, $replace, $value);
							}
						}
					}
				}
				eval($id.' = $value;');
			}
		} // function rebuildEntities

		/**
		 *  Assigns message arrays from systemNotifications
		 *  Args: (boolean) clear all messages
		 *  Return: none
		 */
		public function getMessages($clear = true) {
			$this->assignClean('errorMessages', getErrors());
			$this->assignClean('successMessages', getSuccess());
			$this->assignClean('generalMessages', getMessages());
			$this->assignClean('errorFields', getErrorFields());
			$this->rebuildEntities('errorMessages', array('href'));
			$this->rebuildEntities('successMessages', array('href'));
			$this->rebuildEntities('generalMessages', array('href'));
			if ($clear) {
				clearAllMessages();
			}
		} // function getMessages

		/**
		 *  Register template resource for retrieving email campaigns
		 *    Resource call argument uses the format "campaign:campaign-name:campaign-field"
		 *    ex: $template->display('campaign:receipt:subject')
		 *  Args: none
		 *  Return: none
		 */
		public function registerCampaignResource() {
			$this->register_resource(
				'campaign',
				array(
					'get_template_campaign',
					'get_timestamp_campaign',
					'get_secure_campaign',
					'get_trusted_campaign'
				)
			);
		} // function registerCampaignResource

		/**
		 *  Register template resource for retrieving content pages
		 *    Resource call argument uses the format "content:content name"
		 *    ex: $template->display('content:about')
		 *  Args: none
		 *  Return: none
		 */
		public function registerContentResource() {
			$this->register_resource(
				'content',
				array(
					'get_template_content',
					'get_timestamp_content',
					'get_secure_content',
					'get_trusted_content'
				)
			);
		} // function registerContentResource

		/**
		 *  Retrieve and assign checkout setup variables
		 *  Args: none
		 *  Return: none
		 */
		public function setCheckoutData() {
			$this->assign('_CHECKOUT', checkoutPath::getSetup());
		} // function setCheckoutData

		/**
		 *  Retrieve and assign product data access object
		 *  Args: none
		 *  Return: none
		 */
		public function setProductsGateway() {
			$productDataGateway = new productSearch;
			$this->assign('_PRODUCTS', $productDataGateway);
		} // function setProductsGateway

		/**
		 *  Retrieve and assign shopping cart data
		 *  Args: none
		 *  Return: none
		 */
		public function setCartData() {
			$user = setUser();
			$cart = array();
			$cart['itemCount'] = $user->getObjectData('package', 'itemCount');
			$cart['subTotal'] = $user->getObjectData('package', 'totalCost');
			$cart['contents'] = $user->getObjectData('package', 'contents');
			$this->assignClean('_CART', $cart);
		} // function setCartData
	} // class template

	/**
	 *  Smarty resource get template function - retrieve email campaign as template using campaign name
	 *    Argument template name uses the format "campaign-name:campaign-field"
	 *    ex: receipt:subject, receipt:html, receipt:text
	 *  Args: (str) template name, (str) template source, (smarty) smarty object
	 *  Return: (boolean) success
	 */
	function get_template_campaign($tpl_name, &$tpl_source, &$smarty_obj) {
		list($campaign, $field) = explode(':', $tpl_name);
		if (isset($GLOBALS['_campaignTemplates'][$campaign])) {
			$tpl_source = $GLOBALS['_campaignTemplates'][$campaign][$field];
			return true;
		} else {
			$campaign = clean($campaign);
			if ($campaign) {
				$result = query("SELECT `a`.`name`, `a`.`subject`, `a`.`html`, `a`.`text`, `a`.`fromEmail` FROM `campaigns` `a` JOIN `campaignSiteMap` `b` USING (`campaignID`) WHERE `a`.`name` = '".prep($campaign)."' AND `b`.`siteID` = '".systemSettings::get('SITEID')."'");
				if ($result->rowCount) {
					$row = $result->fetchRow();
					$GLOBALS['_campaignTemplates'][$row['name']]['subject'] = $row['subject'];
					$GLOBALS['_campaignTemplates'][$row['name']]['from'] = $row['fromEmail'];
					$GLOBALS['_campaignTemplates'][$row['name']]['text'] = $row['text'];
					$GLOBALS['_campaignTemplates'][$row['name']]['html'] = $row['html'];
					$tpl_source = $GLOBALS['_campaignTemplates'][$row['name']][$field];
					return true;
				}
			}
		}
		return false;
	} // function get_template_campaign

	/**
	 *  Smarty resource get timestamp function
	 *  Args: (str) template name, (str) template timestamp, (smarty) smarty object
	 *  Return: (boolean) success
	 */
	function get_timestamp_campaign($tpl_name, &$tpl_timestamp, &$smarty_obj) {
		$tpl_timestamp = time();
		return true;
	} // function get_timestamp_campaign

	/**
	 *  Smarty resource get secure function
	 *  Args: (str) template name, (smarty) smarty object
	 *  Return: none
	 */
	function get_secure_campaign($tpl_name, &$smarty_obj) {
		// assume all templates are secure
		return true;
	} // function get_secure_campaign

	/**
	 *  Smarty resource get trusted function
	 *  Args: (str) template name, (smarty) smarty object
	 *  Return: none
	 */
	function get_trusted_campaign($tpl_name, &$smarty_obj) {
		// not used for templates
	} // function get_trusted_campaign

	/**
	 *  Smarty resource get template function - retrieve content as template using content name
	 *  Args: (str) template name, (str) template source, (smarty) smarty object
	 *  Return: (boolean) success
	 */
	function get_template_content($tpl_name, &$tpl_source, &$smarty_obj) {
		$result = query("SELECT `a`.`content` FROM `content` `a` JOIN `contentSiteMap` `b` USING (`contentID`) WHERE `a`.`name` = '".prep(clean($tpl_name))."' AND `b`.`siteID` = '".systemSettings::get('SITEID')."'");
		if ($result->rowCount) {
			$row = $result->fetchRow();
			$tpl_source = $row['content'];
			return true;
		}
		return false;
	} // function get_template_content

	/**
	 *  Smarty resource get timestamp function
	 *  Args: (str) template name, (str) template timestamp, (smarty) smarty object
	 *  Return: (boolean) success
	 */
	function get_timestamp_content($tpl_name, &$tpl_timestamp, &$smarty_obj) {
		$tpl_timestamp = time();
		return true;
	} // function get_timestamp_content

	/**
	 *  Smarty resource get secure function
	 *  Args: (str) template name, (smarty) smarty object
	 *  Return: none
	 */
	function get_secure_content($tpl_name, &$smarty_obj) {
		// assume all templates are secure
		return true;
	} // function get_secure_content

	/**
	 *  Smarty resource get trusted function
	 *  Args: (str) template name, (smarty) smarty object
	 *  Return: none
	 */
	function get_trusted_content($tpl_name, &$smarty_obj) {
		// not used for templates
	} // function get_trusted_content

	/**
	 *  Array walk function for encoding html special chars
	 *  Args: (mixed) array parameter value, (str) key
	 *  Returns: none
	 */
	function htmlentitiesWalk(&$item, $key) {
		$item = htmlentities($item);
	} // function htmlentitiesWalk

	/**
	 *  Array walk function for decoding html special chars
	 *  Args: (mixed) array parameter value, (str) key
	 *  Returns: none
	 */
	function htmlentitydecodeWalk(&$item, $key) {
		$item = html_entity_decode($item);
	} // function htmlentitydecodeWalk

	/**
	 *  Array walk function for rebuilding html special chars
	 *  Args: (mixed) array parameter value, (str) key, (array) match and replace regex
	 *  Returns: none
	 */
	function rebuildEntitiesWalk(&$item, $key, $regex) {
		$match = $regex[0];
		$replace = $regex[1];
		$item = preg_replace($match, $replace, $item);
	} // function htmlentitiesWalk

?>