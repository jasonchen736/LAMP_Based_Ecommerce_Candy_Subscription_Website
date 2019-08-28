INSERT INTO `campaigns` (`type`,  `name`,  `availability`,  `subject`,  `html`,  `text`,  `fromEmail`,  `linkedCampaign`,  `sendInterval`,  `dateAdded`,  `lastModified`) VALUES ('email', 'businessAccountActivation', 'admin', 'Your {$_SITENAME} business account has been activated', '<p>Thank you for your interest in working with {$_SITENAME}.</p><p>Your business account has been approved. You may log on at <a href="{$_SITEURL}/merchant/login">{$_SITEURL}/merchant/login</a> using the username and password you previously registered with.<p><p>Once you have logged on, you may access inventory controls, order reports, shipping and payment settings. You must activate your payment account for your inventory to appear on {$_SITENAME}.</p><p>Thank you, and we look forward to working with you.</p>', 'Thank you for your interest in working with {$_SITENAME}.

Your business account has been approved. You may log on at {$_SITEURL}/merchant/login using the username and password you previously registered with.

Once you have logged on, you may access inventory controls, order reports, shipping and payment settings. You must activate your payment account for your inventory to appear on {$_SITENAME}.

Thank you, and we look forward to working with you.', 'noreply@SITE.com', 0, 0, NOW(), NOW());

INSERT INTO `campaigns` (`type`,  `name`,  `availability`,  `subject`,  `html`,  `text`,  `fromEmail`,  `linkedCampaign`,  `sendInterval`,  `dateAdded`,  `lastModified`) VALUES ('email', 'merchantSignupAcknowledgement', 'admin', 'Your merchant application for {$_SITENAME} has been received', '<p>This is an acknowledgement that your merchant application for {$_SITENAME} has been received.</p><p>Your application will be reviewed shortly and you will be notified when your business account is approved.</p>', 'This is an acknowledgement that your merchant application for {$_SITENAME} has been received.

Your application will be reviewed shortly and you will be notified when your business account is approved.', 'noreply@SITE.com', 0, 0, NOW(), NOW());

INSERT INTO `campaigns` (`type`,  `name`,  `availability`,  `subject`,  `html`,  `text`,  `fromEmail`,  `linkedCampaign`,  `sendInterval`,  `dateAdded`,  `lastModified`) VALUES ('email', 'merchantSignupNotification', 'admin', '{$_SITENAME} has received a new merchant application', '{$_SITENAME} has received a new merchant application', '{$_SITENAME} has received a new merchant application', 'noreply@SITE.com', 0, 0, NOW(), NOW());

INSERT INTO `campaignsHistory` (`campaignID`, `type`, `name`, `availability`, `subject`, `html`, `text`, `fromEmail`, `linkedCampaign`, `sendInterval`, `dateAdded`, `lastModified`, `effectiveThrough`) SELECT *, '9999-12-31 23:59:59' FROM `campaigns`;

CREATE TABLE `categories` (
  `categoryID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `tagID` INTEGER(11) UNSIGNED NOT NULL,
  `lft` INTEGER UNSIGNED NOT NULL,
  `rgt` INTEGER UNSIGNED NOT NULL,
  `availability` ENUM('none','admin','merchant','customer','affiliate','all') NOT NULL,
  `siteID` INTEGER UNSIGNED NOT NULL,
  PRIMARY KEY (`categoryID`),
  INDEX `siteID`(`siteID`),
  INDEX `availability_lft_rgh`(`availability`, `lft`, `rgt`),
  INDEX `lft_rgh_availability`(`lft`, `rgt`, `availability`)
)
ENGINE = InnoDB;

ALTER TABLE `productTags` DROP COLUMN `dateCreated`;
ALTER TABLE `productsToTags` DROP COLUMN `dateCreated`;
ALTER TABLE `packagesToTags` DROP COLUMN `dateCreated`;
DROP TABLE `packageTags`;

ALTER TABLE `productsToTags` RENAME TO `productTagMap`;
ALTER TABLE `packagesToTags` RENAME TO `packageTagMap`;

ALTER TABLE `categories` DROP INDEX `siteID`
, DROP INDEX `availability_lft_rgh`
, DROP INDEX `lft_rgh_availability`,
 ADD INDEX `siteID_availability_lft_rgt` USING BTREE(`siteID`, `availability`, `lft`, `rgt`),
 ADD INDEX `lft_rgt` USING BTREE(`lft`, `rgt`),
 ADD INDEX `rgt_lft`(`rgt`, `lft`);

ALTER TABLE `orders` ADD INDEX `memberID`(`memberID`);