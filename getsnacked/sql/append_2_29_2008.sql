ALTER TABLE `orders` ADD COLUMN `shippedOn` DATE AFTER `shippingDate`,
 ADD INDEX `shippedOn`(`shippedOn`);

ALTER TABLE `ordersHistory` ADD COLUMN `shippedOn` DATE AFTER `shippingDate`,
 ADD INDEX `shippedOn`(`shippedOn`);