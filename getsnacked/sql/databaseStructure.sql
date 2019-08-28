DROP TABLE IF EXISTS `addresses`;
CREATE TABLE `addresses` (
  `addressID` int(11) unsigned NOT NULL auto_increment,
  `first` varchar(30) NOT NULL default '',
  `last` varchar(30) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `address1` varchar(50) NOT NULL default '',
  `address2` varchar(50) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`addressID`),
  KEY `city` (`city`),
  KEY `state` (`state`),
  KEY `postal` (`postal`),
  KEY `country` (`country`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `addressToType`;
CREATE TABLE `addressToType` (
  `addressID` int(11) unsigned NOT NULL default '0',
  `addressBit` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`addressID`),
  KEY `addressBit` USING BTREE (`addressBit`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `adminUser`;
CREATE TABLE `adminUser` (
  `userID` int(10) unsigned NOT NULL auto_increment,
  `login` varchar(45) NOT NULL,
  `password` varchar(16) NOT NULL,
  `name` varchar(45) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY  (`userID`),
  KEY `login_password` (`login`,`password`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `affiliateCustomPayouts`;
CREATE TABLE `affiliateCustomPayouts` (
  `payoutID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `offerID` int(11) unsigned NOT NULL default '0',
  `payout` double(6,2) NOT NULL default '0.00',
  PRIMARY KEY  (`payoutID`),
  UNIQUE KEY `customPayout` (`ID`,`offerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `affiliateOrderReference`;
CREATE TABLE `affiliateOrderReference` (
  `orderReferenceID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `passThroughVariable` varchar(75) NOT NULL default '',
  `orderID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `IP` varchar(50) NOT NULL default '',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`orderReferenceID`),
  UNIQUE KEY `uniqueReference` (`orderID`,`subscriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `affiliateRecurringPayouts`;
CREATE TABLE `affiliateRecurringPayouts` (
  `recurringPayoutID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `passThroughVariable` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`recurringPayoutID`),
  UNIQUE KEY `subscriptionID` (`subscriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `affiliates`;
CREATE TABLE `affiliates` (
  `affiliateID` int(10) unsigned NOT NULL auto_increment,
  `company` varchar(50) NOT NULL default '',
  `first` varchar(30) NOT NULL default '',
  `last` varchar(30) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `fax` varchar(25) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `website` varchar(255) NOT NULL default '',
  `password` varchar(50) NOT NULL default '',
  `address1` varchar(50) NOT NULL default '',
  `address2` varchar(50) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `agreeTerms` tinyint(1) NOT NULL default '0',
  `agreePolicy` tinyint(1) NOT NULL default '0',
  `isOverAge` tinyint(1) NOT NULL default '0',
  `isApproved` tinyint(1) NOT NULL default '0',
  `isActive` enum('signup','active','inactive','deactivated','banned') NOT NULL default 'signup',
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `totalLogins` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`affiliateID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `affiliateTracking`;
CREATE TABLE `affiliateTracking` (
  `trackingID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `hits` int(11) unsigned NOT NULL default '0',
  `uniqueHits` int(11) unsigned NOT NULL default '0',
  `conversions` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`trackingID`),
  UNIQUE KEY `ID_subID_offerID_campaignID_payoutID_date` USING BTREE (`ID`,`subID`,`offerID`,`campaignID`,`payoutID`,`date`),
  KEY `date_conversiona` (`date`,`conversions`),
  KEY `conversions_date` (`conversions`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `campaignDataLog`;
CREATE TABLE `campaignDataLog` (
  `campaignDataLogID` int(10) unsigned NOT NULL auto_increment,
  `type` enum('freerun','scheduled','undefined') NOT NULL default 'undefined',
  `campaignID` int(10) unsigned NOT NULL default '0',
  `list` varchar(45) NOT NULL default '',
  `emailsFound` int(10) unsigned NOT NULL default '0',
  `emailsSent` int(10) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`campaignDataLogID`),
  KEY `date` (`date`),
  KEY `campaignID` (`campaignID`),
  KEY `list` (`list`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `campaignForwards`;
CREATE TABLE `campaignForwards` (
  `forwardID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `offerID` int(11) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`forwardID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE `campaigns` (
  `campaignID` int(11) unsigned NOT NULL auto_increment,
  `offerID` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `availability` enum('admin','customer','affiliate','all','none','exclusive') NOT NULL default 'admin',
  `subject` varchar(255) NOT NULL default '',
  `html` text NOT NULL,
  `text` text NOT NULL,
  `linkedCampaign` int(11) unsigned NOT NULL default '0',
  `sendInterval` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`campaignID`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `campaignSchedule`;
CREATE TABLE `campaignSchedule` (
  `campaignScheduleID` int(10) unsigned NOT NULL auto_increment,
  `mainCampaignID` int(10) unsigned NOT NULL default '0',
  `currentCampaignID` int(10) unsigned NOT NULL default '0',
  `linkedCampaignID` int(10) unsigned NOT NULL default '0',
  `list` varchar(45) NOT NULL default '',
  `sendDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` enum('new','initiated','error','incomplete','completed') NOT NULL default 'new',
  `linkedFrom` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`campaignScheduleID`),
  KEY `mainCampaignID` USING BTREE (`mainCampaignID`),
  KEY `currentCampaignID` (`currentCampaignID`),
  KEY `sendDate` USING BTREE (`sendDate`),
  KEY `list` USING BTREE (`list`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `campaignsSent`;
CREATE TABLE `campaignsSent` (
  `campaignsSentID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(50) NOT NULL default '',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `currentCampaign` int(11) unsigned NOT NULL default '0',
  `sendDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `linkedCampaign` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`campaignsSentID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `commentsID` int(11) unsigned NOT NULL auto_increment,
  `orderID` int(11) unsigned NOT NULL default '0',
  `orderStatus` varchar(100) NOT NULL default '',
  `comment` text NOT NULL,
  `customerNotified` tinyint(1) NOT NULL default '0',
  `dateTime` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`commentsID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `countryCodes`;
CREATE TABLE `countryCodes` (
  `countryCode` char(3) NOT NULL default '',
  `countryName` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`countryCode`),
  KEY `countryName` (`countryName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `cronLog`;
CREATE TABLE `cronLog` (
  `cronLogID` int(10) unsigned NOT NULL auto_increment,
  `script` varchar(255) NOT NULL default '',
  `start` datetime NOT NULL default '0000-00-00 00:00:00',
  `end` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` text NOT NULL,
  `lastModified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`cronLogID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customerID` int(11) unsigned NOT NULL auto_increment,
  `first` varchar(30) NOT NULL default '',
  `last` varchar(30) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `password` varchar(50) NOT NULL default '',
  `name` varchar(50) NOT NULL default '',
  `address1` varchar(50) NOT NULL default '',
  `address2` varchar(50) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(2) NOT NULL default '',
  `billingID` int(11) unsigned NOT NULL default '0',
  `shippingID` int(11) unsigned NOT NULL default '0',
  `credits` int(11) unsigned NOT NULL default '0',
  `isActive` enum('active','inactive','deactivated','banned') NOT NULL default 'active',
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastCompletedOrder` int(11) unsigned NOT NULL default '0',
  `lastCompletedOrderType` enum('none','subscription','single') NOT NULL default 'none',
  `lastPackage` int(11) unsigned NOT NULL default '0',
  `lastPromotion` varchar(255) NOT NULL default '',
  `lastDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastOrderStatus` enum('none','incomplete','completed') NOT NULL default 'none',
  `totalLogins` int(11) unsigned NOT NULL default '0',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`customerID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `customerTagNames`;
CREATE TABLE `customerTagNames` (
  `tagNameID` int(11) unsigned NOT NULL auto_increment,
  `customerID` int(11) unsigned NOT NULL default '0',
  `tagName` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`tagNameID`),
  UNIQUE KEY `tagName` (`tagName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `customPayouts`;
CREATE TABLE `customPayouts` (
  `payoutID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `offerID` int(11) unsigned NOT NULL default '0',
  `payout` double(6,2) NOT NULL default '0.00',
  PRIMARY KEY  (`payoutID`),
  UNIQUE KEY `customPayout` (`ID`,`offerID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `errorTracking`;
CREATE TABLE `errorTracking` (
  `errorTrackingID` int(11) unsigned NOT NULL auto_increment,
  `class` enum('exception','error') NOT NULL default 'error',
  `code` int(10) unsigned NOT NULL default '0',
  `type` enum('Unknown','Error','Warning','Parsing Error','Notice','Core Error','Core Warning','Compile Error','Compile Warning','User Error','User Warning','User Notice','Runtime Notice','Catchable Fatal Error','Fatal','Warning','Notice') NOT NULL default 'Unknown',
  `file` varchar(255) NOT NULL default '',
  `line` int(10) unsigned NOT NULL default '0',
  `function` varchar(45) NOT NULL default '',
  `initialErrorTrace` text NOT NULL,
  `message` text NOT NULL,
  `date` date NOT NULL default '0000-00-00',
  `errorCount` int(10) unsigned NOT NULL default '1',
  `status` enum('uncaught','new','review','resolved','overflow','uncaughtoverflow') NOT NULL default 'new',
  PRIMARY KEY  (`errorTrackingID`),
  UNIQUE KEY `class_code_file_line_function_message_date` USING BTREE (`class`,`code`,`file`,`line`,`function`,`message`(100),`date`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=260 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `freeRunCampaigns`;
CREATE TABLE `freeRunCampaigns` (
  `freeRunCampaignID` int(10) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `mainCampaign` int(10) unsigned NOT NULL default '0',
  `currentCampaign` int(10) unsigned NOT NULL default '0',
  `linkedCampaign` int(10) unsigned NOT NULL default '0',
  `sendDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`freeRunCampaignID`),
  KEY `sendDate` (`sendDate`),
  KEY `mainCampaign` (`mainCampaign`),
  KEY `currentCampaign` (`currentCampaign`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `invalidTracking`;
CREATE TABLE `invalidTracking` (
  `trackingID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `hits` int(11) unsigned NOT NULL default '0',
  `uniqueHits` int(11) unsigned NOT NULL default '0',
  `conversions` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`trackingID`),
  UNIQUE KEY `ID_subID_offerID_campaignID_payoutID_date` USING BTREE (`ID`,`subID`,`offerID`,`campaignID`,`payoutID`,`date`),
  KEY `date_conversions` (`date`,`conversions`),
  KEY `conversions_date` (`conversions`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `newsletter`;
CREATE TABLE `newsletter` (
  `newsletterID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `optInDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `optInSite` varchar(255) NOT NULL default '',
  `campaignScheduleID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`newsletterID`),
  UNIQUE KEY `email` (`email`),
  KEY `campaignScheduleID` USING BTREE (`campaignScheduleID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `offerDeviations`;
CREATE TABLE `offerDeviations` (
  `offerDeviationID` int(10) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '0',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `intendedPackageID` int(11) unsigned NOT NULL default '0',
  `intendedShipments` int(6) NOT NULL default '0',
  `orderedPackageID` int(11) unsigned NOT NULL default '0',
  `orderedShipments` int(6) NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`offerDeviationID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `offerID` int(11) unsigned NOT NULL auto_increment,
  `category` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `payType` enum('cpa','recurringcpa','revshare','recurringrevshare') NOT NULL default 'cpa',
  `payout` double(6,2) NOT NULL default '0.00',
  `packageID` int(11) unsigned NOT NULL default '0',
  `totalShipments` int(11) unsigned NOT NULL default '0',
  `availability` enum('admin','customer','affiliate','all','none','exclusive') NOT NULL default 'admin',
  `startDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `endDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`offerID`),
  KEY `startDate` (`startDate`),
  KEY `endDate` (`endDate`),
  KEY `availability` (`availability`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `orderReference`;
CREATE TABLE `orderReference` (
  `orderReferenceID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `passThroughVariable` varchar(75) NOT NULL default '',
  `orderID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `IP` int(10) unsigned NOT NULL default '0',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`orderReferenceID`),
  UNIQUE KEY `uniqueReference` (`orderID`,`subscriptionID`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `orderID` int(11) unsigned NOT NULL auto_increment,
  `customerID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `packageID` int(11) unsigned NOT NULL default '0',
  `quantity` int(10) unsigned NOT NULL default '0',
  `totalCost` double(7,2) NOT NULL default '0.00',
  `shippingArrangement` enum('regular','express') NOT NULL default 'regular',
  `shippingCost` double(5,2) NOT NULL default '0.00',
  `promotionComboID` int(10) unsigned NOT NULL default '0',
  `discount` double(7,2) NOT NULL default '0.00',
  `shippingID` int(11) unsigned NOT NULL default '0',
  `shippingDate` date NOT NULL default '0000-00-00',
  `billingID` int(11) unsigned NOT NULL default '0',
  `paymentMethod` enum('none','echeck','checkmoneyorder','AMERICANEXPRESS','MASTERCARD','DISCOVER','VISA') NOT NULL default 'none',
  `paymentCleared` enum('no','cleared') NOT NULL default 'no',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `orderStatus` enum('new','reorder','processing','shipped','cancelled','paymentdeclined') NOT NULL default 'new',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`orderID`),
  KEY `subscriptionID` (`subscriptionID`),
  KEY `orderStatus` (`orderStatus`),
  KEY `paymentCleared` (`paymentCleared`),
  KEY `shippingDate` (`shippingDate`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ordersHistory`;
CREATE TABLE `ordersHistory` (
  `orderHistoryID` int(10) unsigned NOT NULL auto_increment,
  `orderID` int(10) unsigned NOT NULL,
  `customerID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `packageID` int(11) unsigned NOT NULL default '0',
  `quantity` int(10) unsigned NOT NULL default '0',
  `totalCost` double(7,2) NOT NULL default '0.00',
  `shippingArrangement` enum('regular','express') NOT NULL default 'regular',
  `shippingCost` double(5,2) NOT NULL default '0.00',
  `promotionComboID` int(10) unsigned NOT NULL default '0',
  `discount` double(7,2) NOT NULL default '0.00',
  `shippingID` int(11) unsigned NOT NULL default '0',
  `shippingDate` date NOT NULL default '0000-00-00',
  `billingID` int(11) unsigned NOT NULL default '0',
  `paymentMethod` enum('none','echeck','checkmoneyorder','AMERICANEXPRESS','MASTERCARD','DISCOVER','VISA') NOT NULL default 'none',
  `paymentCleared` enum('no','cleared') NOT NULL default 'no',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `orderStatus` enum('new','reorder','processing','shipped','cancelled','paymentdeclined') NOT NULL default 'new',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`orderHistoryID`),
  KEY `subscriptionID` (`subscriptionID`),
  KEY `orderStatus` (`orderStatus`),
  KEY `paymentCleared` (`paymentCleared`),
  KEY `shippingDate` (`shippingDate`),
  KEY `orderID` (`orderID`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `packageID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `shortDescription` text NOT NULL,
  `image` varchar(60) NOT NULL default '',
  `availability` enum('available','outofstock','withheld','alwaysavailable','discontinued') NOT NULL default 'withheld',
  `cost` double(11,2) NOT NULL default '0.00',
  `weight` double(6,2) NOT NULL default '0.00',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`packageID`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `packageTrack`;
CREATE TABLE `packageTrack` (
  `packageID` int(11) unsigned NOT NULL default '0',
  `orders` int(11) unsigned NOT NULL default '0',
  `dateOrdered` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`packageID`,`dateOrdered`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `pageHits`;
CREATE TABLE `pageHits` (
  `pageHitID` int(11) unsigned NOT NULL auto_increment,
  `pageID` varchar(255) NOT NULL default '',
  `uniqueHits` int(11) unsigned NOT NULL default '0',
  `hits` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`pageHitID`),
  UNIQUE KEY `pageID` (`pageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `pageID` int(10) unsigned NOT NULL auto_increment,
  `pageDescription` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`pageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `paymentLog`;
CREATE TABLE `paymentLog` (
  `paymentLogID` int(11) unsigned NOT NULL auto_increment,
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `orderID` int(11) unsigned NOT NULL default '0',
  `transactionRecordID` int(10) unsigned NOT NULL default '0',
  `method` enum('cc','echeck','check','moneyorder') NOT NULL default 'cc',
  `datePosted` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`paymentLogID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `paymentMethods`;
CREATE TABLE `paymentMethods` (
  `paymentID` int(11) unsigned NOT NULL auto_increment,
  `addressID` int(11) unsigned NOT NULL default '0',
  `paymentMethod` enum('none','checkmoneyorder','echeck','cc') NOT NULL default 'none',
  `ccType` enum('none','AMERICANEXPRESS','DISCOVER','MASTERCARD','VISA') NOT NULL default 'none',
  `expMonth` tinyint(2) unsigned NOT NULL default '0',
  `expYear` tinyint(4) unsigned NOT NULL default '0',
  `bAccName` varchar(60) NOT NULL default '',
  `aba` varchar(20) NOT NULL default '',
  `bName` varchar(50) NOT NULL default '',
  `accType` varchar(20) NOT NULL default '',
  `accNum_LastFour` varchar(4) NOT NULL,
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`paymentID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `productID` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `shortDescription` text NOT NULL,
  `image` varchar(60) NOT NULL default '',
  `availability` enum('available','outofstock','withheld','alwaysavailable','discontinued') NOT NULL default 'withheld',
  `cost` double(11,2) NOT NULL default '0.00',
  `weight` double(6,2) NOT NULL default '0.00',
  `quantity` int(11) NOT NULL default '0',
  `dateAdded` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`productID`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `productsHistory`;
CREATE TABLE `productsHistory` (
  `productHistoryID` int(11) unsigned NOT NULL auto_increment,
  `productID` int(11) unsigned NOT NULL,
  `name` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `shortDescription` text NOT NULL,
  `image` varchar(60) NOT NULL default '',
  `availability` enum('available','outofstock','withheld','alwaysavailable','discontinued') NOT NULL default 'withheld',
  `cost` double(11,2) NOT NULL default '0.00',
  `weight` double(6,2) NOT NULL default '0.00',
  `quantity` int(11) NOT NULL default '0',
  `dateAdded` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`productHistoryID`),
  KEY `productID` (`productID`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `productsToTags`;
CREATE TABLE `productsToTags` (
  `productID` int(11) unsigned NOT NULL,
  `tagID` int(11) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`productID`,`tagID`),
  KEY `tagID` (`tagID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `productTags`;
CREATE TABLE `productTags` (
  `tagID` int(11) unsigned NOT NULL auto_increment,
  `tag` varchar(60) NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`tagID`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `productToPackage`;
CREATE TABLE `productToPackage` (
  `productID` int(11) unsigned NOT NULL default '0',
  `quantity` int(5) unsigned NOT NULL,
  `packageID` int(11) unsigned NOT NULL default '0',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  USING BTREE (`productID`,`quantity`,`packageID`),
  KEY `packageID` (`packageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `productTrack`;
CREATE TABLE `productTrack` (
  `productID` int(11) unsigned NOT NULL default '0',
  `orders` int(11) unsigned NOT NULL default '0',
  `dateOrdered` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`productID`,`dateOrdered`),
  KEY `dateOrdered` (`dateOrdered`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `promotionCombination`;
CREATE TABLE `promotionCombination` (
  `promotionCombinationID` int(10) unsigned NOT NULL auto_increment,
  `promotionCombination` varchar(188) NOT NULL default '',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`promotionCombinationID`),
  UNIQUE KEY `promotionCombination` (`promotionCombination`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `promotionCombinationTrack`;
CREATE TABLE `promotionCombinationTrack` (
  `promotionCombinationID` int(10) unsigned NOT NULL default '0',
  `redemptions` int(10) unsigned NOT NULL default '0',
  `dateUsed` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`promotionCombinationID`,`dateUsed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE `promotions` (
  `promotionID` int(11) unsigned NOT NULL auto_increment,
  `promotionCode` varchar(20) NOT NULL default '',
  `name` varchar(50) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `type` enum('freeshipping','percentdiscount','dollardiscount','dollarrebate','freeshipments','freeitem','dollartiereddiscount') NOT NULL default 'freeshipping',
  `effect` varchar(50) NOT NULL default '',
  `duration` enum('initial','permanent','pseudo') NOT NULL default 'initial',
  `stackID` tinyint(1) NOT NULL default '0',
  `status` enum('active','cancelled','expired','deactivated','unlimited') NOT NULL default 'active',
  `totalServes` int(11) NOT NULL default '0',
  `remainingServes` int(11) NOT NULL default '0',
  `availability` enum('none','all','customer','affiliate') NOT NULL default 'none',
  `exclusiveID` int(11) unsigned NOT NULL default '0',
  `startDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `endDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`promotionID`),
  UNIQUE KEY `promotionCode` (`promotionCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `promotionTrack`;
CREATE TABLE `promotionTrack` (
  `promotionCode` varchar(20) NOT NULL default '',
  `redemptions` int(11) unsigned NOT NULL default '0',
  `dateUsed` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`promotionCode`,`dateUsed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `recurringPayouts`;
CREATE TABLE `recurringPayouts` (
  `recurringPayoutID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `passThroughVariable` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`recurringPayoutID`),
  UNIQUE KEY `subscriptionID` USING BTREE (`subscriptionID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `savedAddresses`;
CREATE TABLE `savedAddresses` (
  `addressID` int(11) unsigned NOT NULL auto_increment,
  `customerID` int(11) unsigned NOT NULL default '0',
  `addressName` varchar(30) NOT NULL default '',
  `first` varchar(30) NOT NULL default '',
  `last` varchar(30) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `address1` varchar(50) NOT NULL default '',
  `address2` varchar(50) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`addressID`),
  KEY `customerID` (`customerID`),
  KEY `city` (`city`),
  KEY `state` (`state`),
  KEY `postal` (`postal`),
  KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `savedPaymentMethods`;
CREATE TABLE `savedPaymentMethods` (
  `paymentID` int(11) unsigned NOT NULL auto_increment,
  `customerID` int(11) unsigned NOT NULL default '0',
  `addressID` int(11) unsigned NOT NULL default '0',
  `paymentName` varchar(30) NOT NULL default '',
  `paymentMethod` enum('none','checkmoneyorder','echeck','cc') NOT NULL default 'none',
  `ccType` enum('none','AMERICANEXPRESS','DISCOVER','MASTERCARD','VISA') NOT NULL default 'none',
  `expMonth` tinyint(2) unsigned NOT NULL default '0',
  `expYear` tinyint(4) unsigned NOT NULL default '0',
  `bAccName` varchar(60) NOT NULL default '',
  `aba` varchar(20) NOT NULL default '',
  `bName` varchar(50) NOT NULL default '',
  `accType` varchar(20) NOT NULL default '',
  `accNum` varchar(20) NOT NULL default '',
  `entryDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `modifiedDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`paymentID`),
  UNIQUE KEY `paymentRecord` USING BTREE (`customerID`,`accNum`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `searchTrack`;
CREATE TABLE `searchTrack` (
  `searchID` bigint(20) unsigned NOT NULL auto_increment,
  `category` varchar(60) NOT NULL default '',
  `subCategory` varchar(60) NOT NULL default '',
  `searchTerm` varchar(255) NOT NULL default '',
  `searches` bigint(20) unsigned NOT NULL default '0',
  PRIMARY KEY  (`searchID`),
  UNIQUE KEY `uniqueSearch` (`searchTerm`,`category`,`subCategory`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `securityImages`;
CREATE TABLE `securityImages` (
  `imageID` bigint(20) unsigned NOT NULL auto_increment,
  `insertDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `referenceID` varchar(100) NOT NULL default '',
  `hiddenText` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`imageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `sendDeals`;
CREATE TABLE `sendDeals` (
  `sendDealsID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `optInDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `optInSite` varchar(100) NOT NULL default '',
  `campaignScheduleID` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`sendDealsID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `session_id` varchar(32) NOT NULL,
  `session_data` longtext NOT NULL,
  `expires` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`session_id`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `stateCodes`;
CREATE TABLE `stateCodes` (
  `stateCode` char(2) NOT NULL default '',
  `stateName` varchar(45) NOT NULL default '',
  PRIMARY KEY  (`stateCode`),
  KEY `stateName` (`stateName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `subscriptionDataLog`;
CREATE TABLE `subscriptionDataLog` (
  `subscriptionDataLogID` int(10) unsigned NOT NULL auto_increment,
  `ordersEntered` int(10) unsigned NOT NULL default '0',
  `ccOrdersFound` int(10) unsigned NOT NULL default '0',
  `ccOrdersCleared` int(10) unsigned NOT NULL default '0',
  `ccTotalEmails` int(10) unsigned NOT NULL default '0',
  `ccClearedEmailsSent` int(10) unsigned NOT NULL default '0',
  `ccFailedEmailsSent` int(10) unsigned NOT NULL default '0',
  `eCheckOrdersFound` int(10) unsigned NOT NULL default '0',
  `eCheckOrdersCleared` int(10) unsigned NOT NULL default '0',
  `eCheckTotalEmails` int(10) unsigned NOT NULL default '0',
  `eCheckClearedEmailsSent` int(10) unsigned NOT NULL default '0',
  `eCheckFailedEmailsSent` int(10) unsigned NOT NULL default '0',
  `checkOrdersFound` int(10) unsigned NOT NULL default '0',
  `checkTotalEmails` int(10) unsigned NOT NULL default '0',
  `checkEmailsSent` int(10) unsigned NOT NULL default '0',
  `paidOrdersFound` int(10) unsigned NOT NULL default '0',
  `paidTotalEmails` int(10) unsigned NOT NULL default '0',
  `paidEmailsSent` int(10) unsigned NOT NULL default '0',
  `conversionsCredited` int(10) unsigned NOT NULL default '0',
  `subscriptionsUpdated` int(10) unsigned NOT NULL default '0',
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`subscriptionDataLogID`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `subscriptionReorderTracking`;
CREATE TABLE `subscriptionReorderTracking` (
  `reorderID` int(11) unsigned NOT NULL auto_increment,
  `orderID` int(11) unsigned NOT NULL default '0',
  `subscriptionID` int(11) unsigned NOT NULL default '0',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`reorderID`),
  UNIQUE KEY `orderID` (`orderID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `subscriptionID` int(11) unsigned NOT NULL auto_increment,
  `customerID` int(11) unsigned NOT NULL default '0',
  `packageID` int(11) unsigned NOT NULL default '0',
  `quantity` int(11) unsigned NOT NULL default '0',
  `totalCost` double(7,2) unsigned NOT NULL default '0.00',
  `shippingArrangement` enum('normal','express') NOT NULL default 'normal',
  `shippingCost` double(5,2) unsigned NOT NULL default '0.00',
  `promotionComboID` int(10) unsigned NOT NULL default '0',
  `initialDiscount` double(7,2) unsigned NOT NULL default '0.00',
  `subscriptionDiscount` double(7,2) unsigned NOT NULL default '0.00',
  `shippingID` int(11) unsigned NOT NULL default '0',
  `shippingInterval` varchar(62) NOT NULL default '',
  `totalShipments` int(11) unsigned NOT NULL default '0',
  `startDate` date NOT NULL default '0000-00-00',
  `terminationDate` date NOT NULL default '0000-00-00',
  `remainingShipments` int(11) unsigned NOT NULL default '0',
  `lastShipmentDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `nextShipmentDate` date NOT NULL default '0000-00-00',
  `billingID` int(11) unsigned NOT NULL default '0',
  `payArrangement` enum('full','per shipment') NOT NULL default 'per shipment',
  `paymentMethod` enum('none','echeck','checkmoneyorder','AMERICANEXPRESS','MASTERCARD','DISCOVER','VISA') NOT NULL default 'none',
  `paymentCleared` enum('no','cleared','authorized') NOT NULL default 'no',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `subscriptionStatus` enum('new','active','expired','canceled','paymentdeclined') NOT NULL default 'new',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`subscriptionID`),
  KEY `subscriptionStatus` USING BTREE (`subscriptionStatus`),
  KEY `payArrangement` USING BTREE (`payArrangement`),
  KEY `paymentMethod` USING BTREE (`paymentMethod`),
  KEY `nextShipmentDate` USING BTREE (`nextShipmentDate`),
  KEY `paymentCleared` (`paymentCleared`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `tracking`;
CREATE TABLE `tracking` (
  `trackingID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `payoutID` int(11) unsigned NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  `hits` int(11) unsigned NOT NULL default '0',
  `uniqueHits` int(11) unsigned NOT NULL default '0',
  `conversions` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`trackingID`),
  UNIQUE KEY `ID_subID_offerID_campaignID_payoutID_date` USING BTREE (`ID`,`subID`,`offerID`,`campaignID`,`payoutID`,`date`),
  KEY `date_conversions` USING BTREE (`date`,`conversions`),
  KEY `conversions_date` USING BTREE (`conversions`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `trackingTags`;
CREATE TABLE `trackingTags` (
  `tagID` int(11) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `tag` text NOT NULL,
  PRIMARY KEY  (`tagID`),
  UNIQUE KEY `uniqueTag` (`ID`,`subID`,`offerID`,`campaignID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `transactionError`;
CREATE TABLE `transactionError` (
  `transactionRecordID` int(10) unsigned NOT NULL auto_increment,
  `responseCode` enum('Approved','Declined','Error','Held') NOT NULL default 'Approved',
  `subCode` varchar(45) NOT NULL default '',
  `reasonCode` int(3) unsigned NOT NULL default '0',
  `reasonText` varchar(255) NOT NULL default '',
  `approvalCode` varchar(6) NOT NULL default '',
  `transactionID` varchar(45) NOT NULL default '',
  `invoiceNumber` int(11) unsigned NOT NULL default '0',
  `description` varchar(45) NOT NULL default '',
  `amount` double(7,2) unsigned NOT NULL default '0.00',
  `method` varchar(10) NOT NULL default '',
  `type` varchar(15) NOT NULL default '',
  `customerID` int(11) unsigned NOT NULL default '0',
  `cvvCode` char(1) NOT NULL default '',
  `cvvResponse` varchar(255) NOT NULL default '',
  `avsCode` char(1) NOT NULL default '',
  `avsResponse` varchar(255) NOT NULL default '',
  `md5Hash` varchar(32) NOT NULL default '',
  `transactionDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`transactionRecordID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `transactionRecordID` int(11) unsigned NOT NULL auto_increment,
  `responseCode` enum('Approved','Declined','Error','Held') NOT NULL default 'Approved',
  `subCode` varchar(45) NOT NULL default '',
  `reasonCode` int(3) unsigned NOT NULL default '0',
  `reasonText` varchar(255) NOT NULL default '',
  `approvalCode` varchar(6) NOT NULL default '',
  `transactionID` varchar(45) NOT NULL default '',
  `invoiceNumber` int(11) unsigned NOT NULL default '0',
  `description` varchar(45) NOT NULL default '',
  `amount` double(7,2) unsigned NOT NULL default '0.00',
  `method` varchar(10) NOT NULL default '',
  `type` varchar(15) NOT NULL default '',
  `customerID` int(11) unsigned NOT NULL default '0',
  `cvvCode` char(1) NOT NULL default '',
  `cvvResponse` varchar(255) NOT NULL default '',
  `avsCode` char(1) NOT NULL default '',
  `avsResponse` varchar(255) NOT NULL default '',
  `transactionDate` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`transactionRecordID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `userPaths`;
CREATE TABLE `userPaths` (
  `pathID` bigint(20) unsigned NOT NULL auto_increment,
  `ID` int(11) unsigned NOT NULL default '0',
  `subID` varchar(75) NOT NULL default '',
  `offerID` int(11) unsigned NOT NULL default '0',
  `campaignID` int(11) unsigned NOT NULL default '0',
  `path` text NOT NULL,
  PRIMARY KEY  (`pathID`),
  UNIQUE KEY `uniqueSource` (`ID`,`subID`,`offerID`,`campaignID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `usubs`;
CREATE TABLE `usubs` (
  `unsubID` int(11) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`unsubID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- data

LOCK TABLES `adminUser` WRITE;
/*!40000 ALTER TABLE `adminUser` DISABLE KEYS */;
INSERT INTO `adminUser` VALUES (1,'admin','43e9a4ab75570f5b','admin',NOW());
/*!40000 ALTER TABLE `adminUser` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `stateCodes` WRITE;
/*!40000 ALTER TABLE `stateCodes` DISABLE KEYS */;
INSERT INTO `stateCodes` VALUES ('AL','Alabama'),('AK','Alaska'),('AS','American Samoa'),('AZ','Arizona'),('AR','Arkansas'),('CA','California'),('CO','Colorado'),('CT','Connecticut'),('DE','Delaware'),('DC','District Of Columbia'),('FM','Federated States Of Micronesia'),('FL','Florida'),('GA','Georgia'),('GU','Guam'),('HI','Hawaii'),('ID','Idaho'),('IL','Illinois'),('IN','Indiana'),('IA','Iowa'),('KS','Kansas'),('KY','Kentucky'),('LA','Louisiana'),('ME','Maine'),('MH','Marshall Islands'),('MD','Maryland'),('MA','Massachusetts'),('MI','Michigan'),('MN','Minnesota'),('MS','Mississippi'),('MO','Missouri'),('MT','Montana'),('NE','Nebraska'),('NV','Nevada'),('NH','New Hampshire'),('NJ','New Jersey'),('NM','New Mexico'),('NY','New York'),('NC','North Carolina'),('ND','North Dakota'),('MP','Northern Mariana Islands'),('OH','Ohio'),('OK','Oklahoma'),('OR','Oregon'),('PW','Palau'),('PA','Pennsylvania'),('PR','Puerto Rico'),('RI','Rhode Island'),('SC','South Carolina'),('SD','South Dakota'),('TN','Tennessee'),('TX','Texas'),('UT','Utah'),('VT','Vermont'),('VI','Virgin Islands'),('VA','Virginia'),('WA','Washington'),('WV','West Virginia'),('WI','Wisconsin'),('WY','Wyoming');
/*!40000 ALTER TABLE `stateCodes` ENABLE KEYS */;
UNLOCK TABLES;