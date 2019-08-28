ALTER TABLE `campaigns` ADD COLUMN `dateAdded` DATETIME NOT NULL AFTER `sendInterval`,
 ADD COLUMN `lastModified` DATETIME NOT NULL AFTER `dateAdded`;

ALTER TABLE `campaigns` ADD COLUMN `fromEmail` VARCHAR(45) NOT NULL AFTER `text`;