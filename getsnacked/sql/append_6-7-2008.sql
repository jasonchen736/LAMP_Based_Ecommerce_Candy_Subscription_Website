ALTER TABLE `tracking` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `trackingID`
, DROP INDEX `ID_subID_offerID_campaignID_payoutID_date`,
 ADD UNIQUE INDEX `siteID_ID_subID_offerID_campaignID_payoutID_date` USING BTREE(`siteID`, `ID`, `subID`, `offerID`, `campaignID`, `payoutID`, `date`);
 
ALTER TABLE `affiliateTracking` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `trackingID`
, DROP INDEX `ID_subID_offerID_campaignID_payoutID_date`,
 ADD UNIQUE INDEX `siteID_ID_subID_offerID_campaignID_payoutID_date` USING BTREE(`siteID`, `ID`, `subID`, `offerID`, `campaignID`, `payoutID`, `date`);

ALTER TABLE `invalidTracking` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `trackingID`
, DROP INDEX `ID_subID_offerID_campaignID_payoutID_date`,
 ADD UNIQUE INDEX `siteID_ID_subID_offerID_campaignID_payoutID_date` USING BTREE(`siteID`, `ID`, `subID`, `offerID`, `campaignID`, `payoutID`, `date`);

ALTER TABLE `packages` CHANGE COLUMN `imagesSmall` `imagesMedium` INTEGER UNSIGNED NOT NULL DEFAULT 0, CHANGE COLUMN `thumbs` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `packagesHistory` CHANGE COLUMN `imagesSmall` `imagesMedium` INTEGER UNSIGNED NOT NULL DEFAULT 0, CHANGE COLUMN `thumbs` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE `packageSiteMap` (
  `packageSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `packageID` INTEGER(11) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`packageSiteMapID`),
  UNIQUE INDEX `packageID_siteID`(`packageID`, `siteID`),
  INDEX `siteID_packageID`(`siteID`, `packageID`)
)
ENGINE = InnoDB;

CREATE TABLE `contentSiteMap` (
  `contentSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `contentID` INTEGER(10) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`contentSiteMapID`),
  UNIQUE INDEX `contentID_siteID`(`contentID`, `siteID`),
  INDEX `siteID_contentID`(`siteID`, `contentID`)
)
ENGINE = InnoDB;

ALTER TABLE `content` DROP COLUMN `site`
, DROP INDEX `site_name`,
 ADD INDEX `name` USING BTREE(`name`);

ALTER TABLE `searchTrack` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `searchID`
, DROP INDEX `searchTerm_date`,
 ADD UNIQUE INDEX `searchTerm_site_date` USING BTREE(`searchTerm`, `siteID`, `date`),
 ADD INDEX `siteID`(`siteID`),
 ADD INDEX `date`(`date`);

CREATE TABLE `shippingOptionSiteMap` (
  `shippingOptionSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `shippingOptionID` INTEGER(10) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`shippingOptionSiteMapID`),
  UNIQUE INDEX `shippingOptionID_siteID`(`shippingOptionID`, `siteID`),
  INDEX `siteID_shippingOptionID`(`siteID`, `shippingOptionID`)
)
ENGINE = InnoDB;

CREATE TABLE `shippingContainerSiteMap` (
  `shippingContainerSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `shippingContainerID` INTEGER(10) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`shippingContainerSiteMapID`),
  UNIQUE INDEX `shippingContainerID_siteID`(`shippingContainerID`, `siteID`),
  INDEX `siteID_shippingContainerID`(`siteID`, `shippingContainerID`)
)
ENGINE = InnoDB;

CREATE TABLE  `members` (
  `memberID` int(10) unsigned NOT NULL auto_increment,
  `company` varchar(50) NOT NULL default '',
  `first` varchar(50) NOT NULL default '',
  `last` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `fax` varchar(25) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `website` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `address1` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `isActive` enum('new','active','inactive','deactivated','banned') NOT NULL default 'new',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `membersHistory` (
  `memberHistoryID` int(10) unsigned NOT NULL auto_increment,
  `memberID` int(10) unsigned NOT NULL default 0,
  `company` varchar(50) NOT NULL default '',
  `first` varchar(50) NOT NULL default '',
  `last` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `fax` varchar(25) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `website` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `address1` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `isActive` enum('new','active','inactive','deactivated','banned') NOT NULL default 'new',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberHistoryID`),
  KEY  (`memberID`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;