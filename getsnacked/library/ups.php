<?

	class ups {
		// account information
		private static $server = "https://www.ups.com/ups.app/xml/Rate";
		private static $accessnumber;
		private static $accountnumber;
		private static $username;
		private static $password;
		// packaging information
		private static $packagingTypeCode = '02';
		private static $dimensionUnit = 'IN';
		private static $weightUnit = 'LBS';
		private static $service;
		// origin Address
		private static $originPostalCode;
		private static $originCountryCode;
		// destination Address
		private static $destinationPostalCode;
		private static $destinationCountryCode;

		/**
		 *  Set request server url
		 *  Args: (str) server url
		 *  Return: none
		 */
		public static function setServer($server) {
			self::$server = $server;
		} // function setServer

		/**
		 *  Set ups access number
		 *  Args: (int) access number
		 *  Return: none
		 */
		public static function setAccessNumber($accessNumber) {
			self::$accessnumber = $accessNumber;
		} // function setAccessNumber

		/**
		 *  Set ups account number
		 *  Args: (int) access number
		 *  Return: none
		 */
		public static function setAccountNumber($accountNumber) {
			self::$accountnumber = $accountNumber;
		} // function setAccountNumber

		/**
		 *  Set user name
		 *  Args: (str) user name
		 *  Return: none
		 */
		public static function setUserName($userName) {
			self::$username = $userName;
		} // function setUserName

		/**
		 *  Set password
		 *  Args: (str) password
		 *  Return: none
		 */
		public static function setPassword($password) {
			self::$password = $password;
		} // function setPassword

		/**
		 *  Set packaging type code
		 *  Args: (int) packaging type code
		 *  Return: none
		 */
		public static function setPackagingTypeCode($packagingTypeCode) {
			self::$packagingTypeCode = $packagingTypeCode;
		} // function setPackagingTypeCode

		/**
		 *  Set unit of measure for packaging dimensions
		 *  Args: (str) dimension unit
		 *  Return: none
		 */
		public static function setDimensionUnit($dimensionUnit) {
			self::$dimensionUnit = $dimensionUnit;
		} // function setDimensionUnit

		/**
		 *  Set unit of measure for packaging weight
		 *  Args: (str) weight unit
		 *  Return: none
		 */
		public static function setWeightUnit($weightUnit) {
			self::$weightUnit = $weightUnit;
		} // function setWeightUnit

		/**
		 *  Set origin postal code
		 *  Args: (str) origin postal code
		 *  Return: none
		 */
		public static function setOriginPostalCode($code) {
			self::$originPostalCode = $code;
		} // function setOriginPostalCode

		/**
		 *  Set origin country
		 *  Args: (str) origin country code (3 char)
		 *  Return: none
		 */
		public static function setOriginCountryCode($code) {
			self::$originCountryCode = $code;
		} // function setOriginCountryCode

		/**
		 *  Set destination postal code
		 *  Args: (str) destination postal code
		 *  Return: none
		 */
		public static function setDestinationPostalCode($code) {
			self::$destinationPostalCode = $code;
		} // function setDestinationPostalCode

		/**
		 *  Set destination country
		 *  Args: (str) destination country code (3 char)
		 *  Return: none
		 */
		public static function setDestinationCountryCode($code) {
			self::$destinationCountryCode = $code;
		} // function setDestinationCountryCode

		/**
		 *  Set service code
		 *  Args: (str) shipping service code
		 *  Return: none
		 */
		public static function setService($code) {
			self::$service = $code;
		} // function setService

		/**
		 *  Retrieve a shipping fee from UPS
		 *  Args: (array) array of package specifications
		 *  Return: (double) shipping rate
		 */
		public static function getRate($packages) {
			assertArray($packages);
			if (!empty($packages)) {
				$packageData = '';
				foreach ($packages as $key => $val) {
					$packageData .= '
<Package>
	<PackagingType>
		<Code>'.self::$packagingTypeCode.'</Code>
	</PackagingType>
	<Dimensions>
		<UnitOfMeasurement>
			<Code>'.self::$dimensionUnit.'</Code>
		</UnitOfMeasurement>
		<Length>'.$val['container'][0][0].'</Length>
		<Width>'.$val['container'][0][1].'</Width>
		<Height>'.$val['container'][0][2].'</Height>
	</Dimensions>
	<PackageWeight>
		<UnitOfMeasurement>
			<Code>'.self::$weightUnit.'</Code>
		</UnitOfMeasurement>
		<Weight>'.$val['weight'].'</Weight>
	</PackageWeight>
</Package>';
				}
				$data ='<?xml version="1.0"?>
<AccessRequest xml:lang="en-US">
	<AccessLicenseNumber>'.self::$accessnumber.'</AccessLicenseNumber>
	<UserId>'.self::$username.'</UserId>
	<Password>'.self::$password.'</Password>
</AccessRequest>
<?xml version="1.0"?>
<RatingServiceSelectionRequest xml:lang="en-US">
	<Request>
		<TransactionReference>
		<CustomerContext>Rate Request</CustomerContext>
		<XpciVersion>1.0001</XpciVersion>
		</TransactionReference>
			<RequestAction>Rate</RequestAction>
			<RequestOption>Rate</RequestOption>
	</Request>
	<PickupType>
		<Code>01</Code>
	</PickupType>
	<Shipment>
		<Shipper>
			<Address>
				<PostalCode>'.self::$originPostalCode.'</PostalCode>
				<CountryCode>'.self::$originCountryCode.'</CountryCode>
			</Address>
			<ShipperNumber>'.self::$accountnumber.'</ShipperNumber>
		</Shipper>
		<ShipTo>
			<Address>
				<PostalCode>'.self::$destinationPostalCode.'</PostalCode>
				<CountryCode>'.self::$destinationCountryCode.'</CountryCode>
				<ResidentialAddressIndicator/>
			</Address>
		</ShipTo>
		<ShipFrom>
			<Address>
				<PostalCode>'.self::$originPostalCode.'</PostalCode>
				<CountryCode>'.self::$originCountryCode.'</CountryCode>
			</Address>
		</ShipFrom>
		<Service>
			<Code>'.self::$service.'</Code>
		</Service>
		'.$packageData.'
	</Shipment>
</RatingServiceSelectionRequest>';
				$ch = curl_init(self::$server);
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				$response = curl_exec($ch);
				if (curl_errno($ch)) {
					trigger_error('There was an error calculating UPS shipping cost: '.curl_error($ch), E_USER_WARNING);
					addError('There was an error calculating the shipping cost');
					curl_close($ch);
					return false;
				} else {
					curl_close($ch);
					$array = webServicesController::xmlToArray($response);
					return $array['RATINGSERVICESELECTIONRESPONSE']['RATEDSHIPMENT']['TOTALCHARGES']['MONETARYVALUE'];
				}
			} else {
				return false;
			}
		} // function getRate
	} // class ups

?>