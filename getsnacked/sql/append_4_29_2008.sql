CREATE TABLE  `shippingOptions` (
  `shippingOptionID` int(10) unsigned NOT NULL auto_increment,
  `provider` varchar(10) NOT NULL,
  `externalValue` varchar(10) NOT NULL,
  `name` varchar(45) NOT NULL,
  `rate` double(5,2) default NULL,
  `status` enum('active','inactive') NOT NULL default 'active',
  `dateAdded` datetime NOT NULL,
  `lastModified` datetime NOT NULL,
  PRIMARY KEY  (`shippingOptionID`),
  KEY `provider` (`provider`),
  KEY `status` (`status`)
) ENGINE=InnoDB;

CREATE TABLE  `shippingOptionsHistory` (
  `shippingOptionHistoryID` int(10) unsigned NOT NULL auto_increment,
  `shippingOptionID` int(10) unsigned NOT NULL,
  `provider` varchar(10) NOT NULL,
  `externalValue` varchar(10) NOT NULL,
  `name` varchar(45) NOT NULL,
  `rate` double(5,2) default NULL,
  `status` enum('active','inactive') NOT NULL default 'active',
  `dateAdded` datetime NOT NULL,
  `lastModified` datetime NOT NULL,
  `effectiveThrough` datetime NOT NULL,
  PRIMARY KEY  (`shippingOptionHistoryID`),
  KEY `shippingOptionID` (`shippingOptionID`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  KEY `lastModified_effectiveThrough`(`lastModified`, `effectiveThrough`)
) ENGINE=InnoDB;

INSERT INTO `shippingOptions` (`provider`, `externalValue`, `name`, `rate`, `status`) VALUES ('', '', 'Regular', 5, 1), ('', '', 'Express', 10, 1);

ALTER TABLE `orders` MODIFY COLUMN `shippingArrangement` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'regular';
UPDATE `orders` `a`, `shippingOptions` `b` SET `a`.`shippingArrangement` = `b`.`shippingOptionID` WHERE `a`.`shippingArrangement` = `b`.`name`;
ALTER TABLE `orders` MODIFY COLUMN `shippingArrangement` INTEGER(10) UNSIGNED NOT NULL;

ALTER TABLE `ordersHistory` MODIFY COLUMN `shippingArrangement` VARCHAR(10) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'regular';
UPDATE `ordersHistory` `a`, `shippingOptions` `b` SET `a`.`shippingArrangement` = `b`.`shippingOptionID` WHERE `a`.`shippingArrangement` = `b`.`name`;
ALTER TABLE `ordersHistory` MODIFY COLUMN `shippingArrangement` INTEGER(10) UNSIGNED NOT NULL;

ALTER TABLE `products` ADD COLUMN `length` double(4,2) UNSIGNED NOT NULL AFTER `weight`,
 ADD COLUMN `width` double(4,2) UNSIGNED NOT NULL AFTER `length`,
 ADD COLUMN `height` double(4,2) UNSIGNED NOT NULL AFTER `width`;

ALTER TABLE `productsHistory` ADD COLUMN `length` double(4,2) UNSIGNED NOT NULL AFTER `weight`,
 ADD COLUMN `width` double(4,2) UNSIGNED NOT NULL AFTER `length`,
 ADD COLUMN `height` double(4,2) UNSIGNED NOT NULL AFTER `width`;

CREATE TABLE `shippingContainers` (
  `shippingContainerID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `length` double(4,2) UNSIGNED NOT NULL,
  `width` double(4,2) UNSIGNED NOT NULL,
  `height` double(4,2) UNSIGNED NOT NULL,
  `maxWeight` double(6,2) UNSIGNED NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL,
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  PRIMARY KEY (`shippingContainerID`),
  INDEX `status`(`status`)
)
ENGINE = InnoDB;

CREATE TABLE `shippingContainersHistory` (
  `shippingContainerHistoryID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `shippingContainerID` INTEGER UNSIGNED NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `length` double(4,2) UNSIGNED NOT NULL,
  `width` double(4,2) UNSIGNED NOT NULL,
  `height` double(4,2) UNSIGNED NOT NULL,
  `maxWeight` double(6,2) UNSIGNED NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL,
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  `effectiveThrough` DATETIME NOT NULL,
  PRIMARY KEY (`shippingContainerHistoryID`),
  INDEX `shippingContainerID`(`shippingContainerID`),
  INDEX `status`(`status`)
)
ENGINE = InnoDB;