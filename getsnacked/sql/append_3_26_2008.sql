ALTER TABLE `searchTrack` DROP COLUMN `category`,
 DROP COLUMN `subCategory`,
 MODIFY COLUMN `searches` INTEGER UNSIGNED DEFAULT 0,
 ADD COLUMN `date` DATE NOT NULL AFTER `searches`
, DROP INDEX `uniqueSearch`,
 ADD UNIQUE INDEX `searchTerm_date`(`searchTerm`, `date`);

ALTER TABLE `searchTrack` MODIFY COLUMN `searchID` INTEGER UNSIGNED NOT NULL DEFAULT NULL AUTO_INCREMENT;