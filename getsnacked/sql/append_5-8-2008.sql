ALTER TABLE `shippingOptions` ADD COLUMN `rule` ENUM('none', 'allow', 'block') NOT NULL AFTER `status`;
ALTER TABLE `shippingOptionsHistory` ADD COLUMN `rule` ENUM('none', 'allow', 'block') NOT NULL AFTER `status`;

CREATE TABLE `shippingOptionRules` (
  `shippingOptionRuleID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `shippingOptionID` INTEGER(10) UNSIGNED NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `state` VARCHAR(50) NOT NULL,
  `postal` VARCHAR(10) NOT NULL,
  `country` CHAR(3) NOT NULL,
  `weightCondition` VARCHAR(10) NOT NULL,
  `weightValue` DOUBLE(6,2) NOT NULL,
  `packageCondition` VARCHAR(10) NOT NULL,
  `packageValue` INTEGER(4) UNSIGNED NOT NULL,
  `costCondition` VARCHAR(10) NOT NULL,
  `costValue` DOUBLE(6,2) UNSIGNED NOT NULL,
  PRIMARY KEY (`shippingOptionRuleID`),
  INDEX `shippingOptionID`(`shippingOptionID`)
)