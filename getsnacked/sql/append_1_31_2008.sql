ALTER TABLE `packageToOffer` ADD COLUMN `cost` DOUBLE(11,2) UNSIGNED NOT NULL DEFAULT 0.00 AFTER `packageID`;
ALTER TABLE `packageToOffer` DROP COLUMN `dateCreated`;
ALTER TABLE `campaignToOffer` DROP COLUMN `dateCreated`;
ALTER TABLE `productToPackage` DROP COLUMN `dateCreated`;

CREATE TABLE `productInventory` (
  `productID` INTEGER(11) UNSIGNED NOT NULL,
  `quantity` INTEGER(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`productID`),
  INDEX `quantity`(`quantity`)
)
ENGINE = InnoDB;

ALTER TABLE `productInventory` ADD INDEX `productID_quantity`(`productID`, `quantity`);

ALTER TABLE `productsHistory` DROP COLUMN `quantity`;
ALTER TABLE `products` DROP COLUMN `quantity`;

ALTER TABLE `offersHistory` DROP INDEX `offerID`,
 ADD INDEX `offerID_effectiveThrough` USING BTREE(`offerID`, `effectiveThrough`);
 
ALTER TABLE `errorTracking` MODIFY COLUMN `type` ENUM('Unknown','Error','Warning','Parsing Error','Notice','Core Error','Core Warning','Compile Error','Compile Warning','User Error','User Warning','User Notice','Runtime Notice','Catchable Fatal Error','Fatal') NOT NULL DEFAULT 'Unknown';