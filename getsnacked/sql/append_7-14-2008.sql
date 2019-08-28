ALTER TABLE `products` ADD COLUMN `sku` VARCHAR(25) NOT NULL AFTER `name`,
 ADD COLUMN `brand` VARCHAR(100) NOT NULL AFTER `sku`,
 ADD INDEX `sku`(`sku`),
 ADD INDEX `brand`(`brand`);

ALTER TABLE `productsHistory` ADD COLUMN `sku` VARCHAR(25) NOT NULL AFTER `name`,
 ADD COLUMN `brand` VARCHAR(100) NOT NULL AFTER `sku`;

ALTER TABLE `packages` ADD COLUMN `sku` VARCHAR(25) NOT NULL AFTER `name`,
 ADD COLUMN `brand` VARCHAR(100) NOT NULL AFTER `sku`,
 ADD INDEX `sku`(`sku`),
 ADD INDEX `brand`(`brand`);

ALTER TABLE `packagesHistory` ADD COLUMN `sku` VARCHAR(25) NOT NULL AFTER `name`,
 ADD COLUMN `brand` VARCHAR(100) NOT NULL AFTER `sku`;

CREATE TABLE `brands` (
  `brandID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`brandID`),
  INDEX `brand`(`brand`)
)
ENGINE = InnoDB;