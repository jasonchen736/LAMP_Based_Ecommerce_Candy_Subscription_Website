DROP TABLE `members`;
CREATE TABLE  `members` (
  `memberID` int(10) unsigned NOT NULL auto_increment,
  `first` varchar(50) NOT NULL default '',
  `last` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `address1` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `status` enum('new','active','inactive','deactivated','banned') NOT NULL default 'new',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberID`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE `membersHistory`;
CREATE TABLE  `membersHistory` (
  `memberHistoryID` int(10) unsigned NOT NULL auto_increment,
  `memberID` int(10) unsigned NOT NULL default 0,
  `first` varchar(50) NOT NULL default '',
  `last` varchar(50) NOT NULL default '',
  `phone` varchar(20) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `address1` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(50) NOT NULL default '',
  `state` varchar(50) NOT NULL default '',
  `postal` varchar(10) NOT NULL default '',
  `country` char(3) NOT NULL default '',
  `status` enum('new','active','inactive','deactivated','banned') NOT NULL default 'new',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberHistoryID`),
  KEY  (`memberID`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `memberBusinessInfo` (
  `memberBusinessInfoID` int(10) unsigned NOT NULL auto_increment,
  `memberID` int(10) unsigned NOT NULL default 0,
  `company` varchar(50) NOT NULL default '',
  `fax` varchar(25) NOT NULL default '',
  `website` varchar(255) NOT NULL default '',
  `taxID` varchar(20) NOT NULL default '',
  `industry` varchar(25) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `payTo` enum('business','contact') NOT NULL default 'business',
  `im` varchar(25) NOT NULL default '',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberBusinessInfoID`),
  UNIQUE KEY `memberID` (`memberID`),
  KEY `company` (`company`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `memberBusinessInfoHistory` (
  `memberBusinessInfoHistoryID` int(10) unsigned NOT NULL auto_increment,
  `memberBusinessInfoID` int(10) unsigned NOT NULL default 0,
  `memberID` int(10) unsigned NOT NULL default 0,
  `company` varchar(50) NOT NULL default '',
  `fax` varchar(25) NOT NULL default '',
  `website` varchar(255) NOT NULL default '',
  `taxID` varchar(20) NOT NULL default '',
  `industry` varchar(25) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `payTo` enum('business','contact') NOT NULL default 'business',
  `im` varchar(25) NOT NULL default '',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`memberBusinessInfoHistoryID`),
  KEY  (`memberBusinessInfoID`),
  KEY `memberID` (`memberID`),
  KEY `company` (`company`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `memberSiteMap` (
  `memberSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `memberID` INTEGER(10) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`memberSiteMapID`),
  UNIQUE INDEX `memberID_siteID`(`memberID`, `siteID`),
  INDEX `siteID_memberID`(`siteID`, `memberID`)
)
ENGINE = InnoDB;

CREATE TABLE `memberGroups` (
  `memberGroupID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` varchar(25) NOT NULL default '',
  `type` ENUM('business','individual') NOT NULL default 'individual',
  PRIMARY KEY (`memberGroupID`),
  UNIQUE INDEX `group`(`group`),
  INDEX `type`(`type`)
)
ENGINE = InnoDB;

CREATE TABLE `memberGroupMap` (
  `memberGroupMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `memberID` INTEGER(10) UNSIGNED NOT NULL,
  `memberGroupID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`memberGroupMapID`),
  UNIQUE INDEX `memberID_memberGroupID`(`memberID`, `memberGroupID`),
  INDEX `memberGroupID_memberID`(`memberGroupID`, `memberID`)
)
ENGINE = InnoDB;

ALTER TABLE `products` ADD COLUMN `memberID` INTEGER(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `sortWeight`;
ALTER TABLE `productsHistory` ADD COLUMN `memberID` INTEGER(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `sortWeight`;

INSERT INTO `memberGroups` (`memberGroupID`, `group`, `type`) VALUES (1, 'customer', 'individual'), (2, 'merchant', 'business'), (3, 'affiliate', 'business');

ALTER TABLE `products` MODIFY COLUMN `name` VARCHAR(100) NOT NULL;
ALTER TABLE `productsHistory` MODIFY COLUMN `name` VARCHAR(100) NOT NULL;
ALTER TABLE `packages` MODIFY COLUMN `name` VARCHAR(100) NOT NULL;
ALTER TABLE `packagesHistory` MODIFY COLUMN `name` VARCHAR(100) NOT NULL;

CREATE TABLE  `subOrders` (
  `subOrderID` int(11) unsigned NOT NULL auto_increment,
  `orderID` int(11) unsigned NOT NULL,
  `memberID` int(10) unsigned NOT NULL,
  `totalCost` double(9,2) default NULL,
  `shippingArrangement` int(10) unsigned NOT NULL,
  `shippingCost` double(7,2) default NULL,
  `fulfillBy` datetime NOT NULL default '0000-00-00 00:00:00',
  `fulfillmentDate` date default NULL,
  `paymentCleared` enum('no','cleared') NOT NULL default 'no',
  `status` enum('new','fulfilled','backordered','cancelled','paymentdeclined','returned') NOT NULL default 'new',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`subOrderID`),
  KEY `memberID` (`memberID`),
  KEY `orderDate` (`orderDate`),
  KEY `fulfillBy` (`fulfillBy`),
  KEY `fulfillmentDate` (`fulfillmentDate`),
  KEY `status` (`status`),
  KEY `paymentCleared` (`paymentCleared`),
  KEY `orderID` (`orderID`)
) ENGINE=InnoDB;

CREATE TABLE  `subOrdersHistory` (
  `subOrderHistoryID` int(11) unsigned NOT NULL auto_increment,
  `subOrderID` int(11) unsigned NOT NULL,
  `orderID` int(11) unsigned NOT NULL,
  `memberID` int(10) unsigned NOT NULL,
  `totalCost` double(9,2) default NULL,
  `shippingArrangement` int(10) unsigned NOT NULL,
  `shippingCost` double(7,2) default NULL,
  `fulfillBy` datetime NOT NULL default '0000-00-00 00:00:00',
  `fulfillmentDate` date default NULL,
  `paymentCleared` enum('no','cleared') NOT NULL default 'no',
  `status` enum('new','fulfilled','backordered','cancelled','paymentdeclined','returned') NOT NULL default 'new',
  `orderDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`subOrderHistoryID`),
  KEY `subOrderID` (`subOrderID`),
  KEY `memberID` (`memberID`),
  KEY `orderDate` (`orderDate`),
  KEY `fulfillBy` (`fulfillBy`),
  KEY `fulfillmentDate` (`fulfillmentDate`),
  KEY `status` (`status`),
  KEY `paymentCleared` (`paymentCleared`),
  KEY `orderID` (`orderID`)
) ENGINE=InnoDB;

ALTER TABLE `orders` CHANGE COLUMN `shippingDate` `fulfillBy` DATE NOT NULL DEFAULT '0000-00-00',
 CHANGE COLUMN `shippedOn` `fulfillmentDate` DATE DEFAULT NULL,
 CHANGE COLUMN `customerID` `memberID` int(10) unsigned NOT NULL,
 MODIFY COLUMN `orderStatus` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned') NOT NULL DEFAULT 'new'
, DROP INDEX `shippingDate`
, DROP INDEX `shippedOn`,
 ADD INDEX `fulfillBy` USING BTREE(`fulfillBy`),
 ADD INDEX `fulfillmentDate` USING BTREE(`fulfillmentDate`);

ALTER TABLE `ordersHistory` CHANGE COLUMN `shippingDate` `fulfillBy` DATE NOT NULL DEFAULT '0000-00-00',
 CHANGE COLUMN `shippedOn` `fulfillmentDate` DATE DEFAULT NULL,
 CHANGE COLUMN `customerID` `memberID` int(10) unsigned NOT NULL,
 MODIFY COLUMN `orderStatus` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned') NOT NULL DEFAULT 'new'
, DROP INDEX `shippingDate`
, DROP INDEX `shippedOn`,
 ADD INDEX `fulfillBy` USING BTREE(`fulfillBy`),
 ADD INDEX `fulfillmentDate` USING BTREE(`fulfillmentDate`);

ALTER TABLE `transactionError` CHANGE COLUMN `customerID` `memberID` INTEGER UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `transactions` CHANGE COLUMN `customerID` `memberID` INTEGER UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `addresses` DROP COLUMN `modifiedDate`,
 MODIFY COLUMN `first` VARCHAR(50) NOT NULL,
 MODIFY COLUMN `last` VARCHAR(50) NOT NULL,
 MODIFY COLUMN `address1` VARCHAR(255) NOT NULL,
 MODIFY COLUMN `address2` VARCHAR(255) NOT NULL;

CREATE TABLE `shippingOptionsImposed` (
  `shippingOptionsImposedID` INTEGER UNSIGNED NOT NULL DEFAULT NULL AUTO_INCREMENT,
  `shippingOptionID` INTEGER UNSIGNED NOT NULL,
  `imposedOn` ENUM('', 'all', 'domestic','international') NOT NULL,
  PRIMARY KEY (`shippingOptionsImposedID`),
  UNIQUE INDEX (`shippingOptionID`),
  INDEX `imposedOn`(`imposedOn`)
)
ENGINE = InnoDB;

CREATE TABLE `shippingOptionsImposedSiteMap` (
  `shippingOptionsImposedSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `shippingOptionsImposedID` INTEGER(10) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`shippingOptionsImposedSiteMapID`),
  UNIQUE INDEX `shippingOptionsImposedID_siteID`(`shippingOptionsImposedID`, `siteID`),
  INDEX `siteID_shippingOptionsImposedID`(`siteID`, `shippingOptionsImposedID`)
)
ENGINE = InnoDB;

CREATE TABLE `memberShippingLocations` (
  `memberShippingLocationID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `memberID` INTEGER UNSIGNED NOT NULL,
  `state` VARCHAR(50) NOT NULL,
  `postal` VARCHAR(10) NOT NULL,
  `country` CHAR(3) NOT NULL,
  PRIMARY KEY (`memberShippingLocationID`),
  UNIQUE INDEX `memberID`(`memberID`)
)
ENGINE = InnoDB;

ALTER TABLE `shippingOptions` MODIFY COLUMN `externalValue` VARCHAR(30);
ALTER TABLE `shippingOptionsHistory` MODIFY COLUMN `externalValue` VARCHAR(30);

CREATE TABLE `memberGatewayInfo` (
  `memberGatewayInfoID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `memberID` INTEGER UNSIGNED NOT NULL,
  `gateway` ENUM('authorize','linkpoint') NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `port` VARCHAR(10) NOT NULL,
  `login` VARCHAR(45) NOT NULL,
  `key` VARCHAR(45) NOT NULL,
  `hash` VARCHAR(255) NOT NULL,
  `status` ENUM('inactive', 'active', 'deactivated', 'fail') NOT NULL DEFAULT 'inactive',
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  PRIMARY KEY (`memberGatewayInfoID`),
  UNIQUE INDEX `memberID`(`memberID`),
  INDEX `memberID_status`(`memberID`, `status`)
)
ENGINE = InnoDB;

CREATE TABLE `memberGatewayInfoHistory` (
  `memberGatewayInfoHistoryID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `memberGatewayInfoID` INTEGER UNSIGNED NOT NULL,
  `memberID` INTEGER UNSIGNED NOT NULL,
  `gateway` ENUM('authorize','linkpoint') NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `port` VARCHAR(10) NOT NULL,
  `login` VARCHAR(45) NOT NULL,
  `key` VARCHAR(45) NOT NULL,
  `hash` VARCHAR(255) NOT NULL,
  `status` ENUM('inactive', 'active', 'deactivated', 'fail') NOT NULL DEFAULT 'inactive',
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`memberGatewayInfoHistoryID`),
  INDEX `memberGatewayInfoID`(`memberGatewayInfoID`),
  INDEX `memberID`(`memberID`)
)
ENGINE = InnoDB;

DROP TABLE transactionError;

ALTER TABLE `transactions` MODIFY COLUMN `responseCode` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
 MODIFY COLUMN `reasonCode` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `error` TEXT NOT NULL AFTER `avsResponse`,
 ADD COLUMN `responseText` VARCHAR(255) NOT NULL AFTER `responseCode`;

ALTER TABLE `transactions` CHANGE COLUMN `memberID` `customer` INTEGER UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN `owner` INTEGER UNSIGNED NOT NULL AFTER `transactionRecordID`,
 ADD COLUMN `gateway` VARCHAR(20) NOT NULL AFTER `owner`;