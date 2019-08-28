CREATE TABLE  `packagesHistory` (
  `packageHistoryID` int(11) unsigned NOT NULL auto_increment,
  `packageID` int(11) unsigned NOT NULL,
  `name` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `shortDescription` text NOT NULL,
  `image` varchar(60) NOT NULL default '',
  `availability` enum('available','outofstock','withheld','alwaysavailable','discontinued') NOT NULL default 'withheld',
  `cost` double(11,2) NOT NULL default '0.00',
  `weight` double(6,2) NOT NULL default '0.00',
  `dateCreated` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastModified` datetime NOT NULL default '0000-00-00 00:00:00',
  `effectiveThrough` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`packageHistoryID`),
  KEY  (`packageID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `packageTags` (
  `tagID` int(11) unsigned NOT NULL auto_increment,
  `tag` varchar(60) NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`tagID`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE  `packagesToTags` (
  `packageID` int(11) unsigned NOT NULL,
  `tagID` int(11) unsigned NOT NULL,
  `dateCreated` datetime NOT NULL,
  PRIMARY KEY  (`packageID`,`tagID`),
  KEY `tagID` (`tagID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `products` ADD INDEX `name`(`name`);