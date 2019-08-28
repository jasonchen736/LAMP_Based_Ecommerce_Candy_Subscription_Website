CREATE TABLE `siteRegistry` (
  `siteID` INTEGER UNSIGNED NOT NULL DEFAULT NULL AUTO_INCREMENT,
  `siteName` VARCHAR(45) NOT NULL,
  `dateCreated` DATETIME NOT NULL,
  PRIMARY KEY (`siteID`),
  UNIQUE INDEX `siteName`(`siteName`)
)
ENGINE = InnoDB;

ALTER TABLE `shippingOptions` ADD COLUMN `modifier` DOUBLE(3,2) UNSIGNED NOT NULL AFTER `externalValue`,
 ADD COLUMN `modifierType` ENUM('none','percentup','percentdown','flatup','flatdown') NOT NULL AFTER `modifier`;

ALTER TABLE `shippingOptionsHistory` ADD COLUMN `modifier` DOUBLE(3,2) UNSIGNED NOT NULL AFTER `externalValue`,
 ADD COLUMN `modifierType` ENUM('none','percentup','percentdown','flatup','flatdown') NOT NULL AFTER `modifier`;

ALTER TABLE `orders` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `orderStatus`,
 ADD INDEX `siteID`(`siteID`);

ALTER TABLE `ordersHistory` ADD COLUMN `siteID` INTEGER(10) UNSIGNED NOT NULL AFTER `orderStatus`;

ALTER TABLE `products` MODIFY COLUMN `length` DOUBLE(6,2);
ALTER TABLE `products` MODIFY COLUMN `width` DOUBLE(6,2);
ALTER TABLE `products` MODIFY COLUMN `height` DOUBLE(6,2);
ALTER TABLE `productsHistory` MODIFY COLUMN `length` DOUBLE(6,2);
ALTER TABLE `productsHistory` MODIFY COLUMN `width` DOUBLE(6,2);
ALTER TABLE `productsHistory` MODIFY COLUMN `height` DOUBLE(6,2);
ALTER TABLE `shippingContainers` MODIFY COLUMN `length` DOUBLE(6,2);
ALTER TABLE `shippingContainers` MODIFY COLUMN `width` DOUBLE(6,2);
ALTER TABLE `shippingContainers` MODIFY COLUMN `height` DOUBLE(6,2);
ALTER TABLE `shippingContainersHistory` MODIFY COLUMN `length` DOUBLE(6,2);
ALTER TABLE `shippingContainersHistory` MODIFY COLUMN `width` DOUBLE(6,2);
ALTER TABLE `shippingContainersHistory` MODIFY COLUMN `height` DOUBLE(6,2);
ALTER TABLE `orders` MODIFY COLUMN `shippingCost` DOUBLE(7,2);
ALTER TABLE `ordersHistory` MODIFY COLUMN `shippingCost` DOUBLE(7,2);
ALTER TABLE `shippingOptions` MODIFY COLUMN `modifier` DOUBLE(7,2);
ALTER TABLE `shippingOptions` MODIFY COLUMN `rate` DOUBLE(7,2);
ALTER TABLE `shippingOptionsHistory` MODIFY COLUMN `modifier` DOUBLE(7,2);
ALTER TABLE `shippingOptionsHistory` MODIFY COLUMN `rate` DOUBLE(7,2);
ALTER TABLE `subscriptions` MODIFY COLUMN `shippingCost` DOUBLE(7,2);
ALTER TABLE `customPayouts` MODIFY COLUMN `payout` DOUBLE(8,2);
ALTER TABLE `offers` MODIFY COLUMN `payout` DOUBLE(8,2);
ALTER TABLE `offersHistory` MODIFY COLUMN `payout` DOUBLE(8,2);
ALTER TABLE `packages` MODIFY COLUMN `weight` DOUBLE(8,2);
ALTER TABLE `packagesHistory` MODIFY COLUMN `weight` DOUBLE(8,2);
ALTER TABLE `products` MODIFY COLUMN `weight` DOUBLE(8,2);
ALTER TABLE `productsHistory` MODIFY COLUMN `weight` DOUBLE(8,2);
ALTER TABLE `shippingContainers` MODIFY COLUMN `maxWeight` DOUBLE(8,2);
ALTER TABLE `shippingContainersHistory` MODIFY COLUMN `maxWeight` DOUBLE(8,2);
ALTER TABLE `shippingOptionRules` MODIFY COLUMN `weightValue` DOUBLE(8,2);
ALTER TABLE `orders` MODIFY COLUMN `totalCost` DOUBLE(9,2);
ALTER TABLE `orders` MODIFY COLUMN `discount` DOUBLE(9,2);
ALTER TABLE `ordersHistory` MODIFY COLUMN `totalCost` DOUBLE(9,2);
ALTER TABLE `ordersHistory` MODIFY COLUMN `discount` DOUBLE(9,2);
ALTER TABLE `subscriptions` MODIFY COLUMN `totalCost` DOUBLE(9,2);
ALTER TABLE `subscriptions` MODIFY COLUMN `initialDiscount` DOUBLE(9,2);
ALTER TABLE `subscriptions` MODIFY COLUMN `subscriptionDiscount` DOUBLE(9,2);
ALTER TABLE `transactionError` MODIFY COLUMN `amount` DOUBLE(9,2);
ALTER TABLE `transactions` MODIFY COLUMN `amount` DOUBLE(9,2);
ALTER TABLE `packages` MODIFY COLUMN `cost` DOUBLE(9,2);
ALTER TABLE `packagesHistory` MODIFY COLUMN `cost` DOUBLE(9,2);
ALTER TABLE `packageToOffer` MODIFY COLUMN `cost` DOUBLE(9,2);
ALTER TABLE `products` MODIFY COLUMN `cost` DOUBLE(9,2);
ALTER TABLE `productsHistory` MODIFY COLUMN `cost` DOUBLE(9,2);
ALTER TABLE `shippingOptionRules` MODIFY COLUMN `costValue` DOUBLE(9,2);

ALTER TABLE `orders` MODIFY COLUMN `orderStatus` ENUM('new','shipped','cancelled','paymentdeclined') NOT NULL DEFAULT 'new';
ALTER TABLE `ordersHistory` MODIFY COLUMN `orderStatus` ENUM('new','shipped','cancelled','paymentdeclined') NOT NULL DEFAULT 'new';

ALTER TABLE `products` CHANGE COLUMN `imagesSmall` `imagesMedium` INTEGER UNSIGNED NOT NULL DEFAULT 0, CHANGE COLUMN `thumbs` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `productsHistory` CHANGE COLUMN `imagesSmall` `imagesMedium` INTEGER UNSIGNED NOT NULL DEFAULT 0, CHANGE COLUMN `thumbs` `imagesSmall` INTEGER UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE `productSiteMap` (
  `productSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `productID` INTEGER(11) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`productSiteMapID`),
  UNIQUE INDEX `productID_siteID`(`productID`, `siteID`),
  INDEX `siteID_productID`(`siteID`, `productID`)
)
ENGINE = InnoDB;

ALTER TABLE `productToPackage` ADD INDEX `productID_packageID`(`productID`, `packageID`);

CREATE TABLE `campaignSiteMap` (
  `campaignSiteMapID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaignID` INTEGER(11) UNSIGNED NOT NULL,
  `siteID` INTEGER(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`campaignSiteMapID`),
  UNIQUE INDEX `campaignID_siteID`(`campaignID`, `siteID`),
  INDEX `siteID_campaignID`(`siteID`, `campaignID`)
)
ENGINE = InnoDB;

CREATE TABLE  `campaignsHistory` (
  `campaignsHistoryID` int(11) unsigned NOT NULL auto_increment,
  `campaignID` int(11) unsigned NOT NULL,
  `type` enum('email','banner') NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `availability` enum('admin','customer','affiliate','all','none','exclusive') NOT NULL default 'admin',
  `subject` varchar(255) NOT NULL default '',
  `html` text NOT NULL,
  `text` text NOT NULL,
  `fromEmail` varchar(45) NOT NULL,
  `linkedCampaign` int(11) unsigned NOT NULL default '0',
  `sendInterval` int(4) unsigned NOT NULL default '0',
  `dateAdded` datetime NOT NULL,
  `lastModified` datetime NOT NULL,
  `effectiveThrough` datetime NOT NULL,
  PRIMARY KEY  (`campaignsHistoryID`),
  INDEX `campaignID` (`campaignID`),
  INDEX `lastModified_effectiveThrough`(`lastModified`, `effectiveThrough`)
) ENGINE=InnoDB;

ALTER TABLE `campaigns` DROP INDEX `name`,
 ADD INDEX `name` USING BTREE(`name`);