ALTER TABLE `offers` DROP COLUMN `category`,
 CHANGE COLUMN `packageID` `defaultPackage` INTEGER(11) UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `longDescription` TEXT NOT NULL AFTER `description`;
 
ALTER TABLE `campaigns` ADD COLUMN `type` ENUM('email', 'banner') NOT NULL AFTER `campaignID`, DROP COLUMN `offerID`;

ALTER TABLE `offers` ADD COLUMN `terms` TEXT NOT NULL AFTER `endDate`,
 ADD COLUMN `unsubLink` VARCHAR(255) NOT NULL AFTER `terms`;
 
ALTER TABLE `offers` ADD COLUMN `dateAdded` DATETIME NOT NULL AFTER `unsubLink`,
 ADD COLUMN `lastModified` DATETIME NOT NULL AFTER `dateAdded`;
 
ALTER TABLE `offers` ADD COLUMN `availablePackages` TEXT NOT NULL AFTER `unsubLink`,
 ADD COLUMN `availableCampaigns` TEXT NOT NULL AFTER `availablePackages`;

ALTER TABLE `offers` DROP COLUMN `image`,
 ADD COLUMN `unsubFile` VARCHAR(255) NOT NULL AFTER `unsubLink`;

CREATE TABLE  `offersHistory` (
  `offersHistoryID` int(11) unsigned NOT NULL auto_increment,
  `offerID` int(11) unsigned NOT NULL default 0,
  `name` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `longDescription` text NOT NULL,
  `link` varchar(255) NOT NULL default '',
  `payType` enum('cpa','recurringcpa','revshare','recurringrevshare') NOT NULL default 'cpa',
  `payout` double(6,2) NOT NULL default '0.00',
  `defaultPackage` int(11) unsigned NOT NULL default '0',
  `totalShipments` int(11) unsigned NOT NULL default '0',
  `availability` enum('admin','customer','affiliate','all','none','exclusive') NOT NULL default 'admin',
  `startDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `endDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `terms` text NOT NULL,
  `unsubLink` varchar(255) NOT NULL,
  `unsubFile` varchar(255) NOT NULL,
  `availablePackages` text NOT NULL,
  `availableCampaigns` text NOT NULL,
  `dateAdded` datetime NOT NULL,
  `lastModified` datetime NOT NULL,
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`offersHistoryID`),
  KEY `offerID` (`offerID`),
  KEY `startDate` (`startDate`),
  KEY `endDate` (`endDate`),
  KEY `availability` (`availability`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

ALTER TABLE `products` CHANGE COLUMN `image` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `imagesLarge` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `imagesSmall`,
 ADD COLUMN `thumbs` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `shortDescription`;

ALTER TABLE `productsHistory` CHANGE COLUMN `image` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `imagesLarge` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `imagesSmall`,
 ADD COLUMN `thumbs` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `shortDescription`;

ALTER TABLE `packages` CHANGE COLUMN `image` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `imagesLarge` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `imagesSmall`,
 ADD COLUMN `thumbs` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `shortDescription`;

ALTER TABLE `packagesHistory` CHANGE COLUMN `image` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `imagesLarge` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `imagesSmall`,
 ADD COLUMN `thumbs` INTEGER UNSIGNED NOT NULL DEFAULT 0 AFTER `shortDescription`;

CREATE TABLE  `offerTags` (
  `tagID` int(11) unsigned NOT NULL auto_increment,
  `tag` varchar(60) NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`tagID`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

CREATE TABLE  `offersToTags` (
  `offerID` int(11) unsigned NOT NULL,
  `tagID` int(11) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`offerID`,`tagID`),
  KEY `tagID` (`tagID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `packages` ADD INDEX `name`(`name`);

CREATE TABLE `packageToOffer` (
  `offerID` INTEGER(11) UNSIGNED NOT NULL,
  `packageID` INTEGER(11) UNSIGNED NOT NULL,
  `dateCreated` DATETIME NOT NULL,
  PRIMARY KEY (`offerID`, `packageID`),
  INDEX `packageID`(`packageID`)
)
ENGINE = InnoDB;

CREATE TABLE  `campaignToOffer` (
  `offerID` INTEGER(11) UNSIGNED NOT NULL,
  `campaignID` INTEGER(11) UNSIGNED NOT NULL,
  `dateCreated` DATETIME NOT NULL,
  PRIMARY KEY  (`offerID`,`campaignID`),
  KEY `campaignID` (`campaignID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `adminUser` ADD UNIQUE INDEX `login`(`login`);

ALTER TABLE `offers` ADD COLUMN `image` TINYINT(1) UNSIGNED NOT NULL AFTER `terms`;
ALTER TABLE `offersHistory` ADD COLUMN `image` TINYINT(1) UNSIGNED NOT NULL AFTER `terms`;