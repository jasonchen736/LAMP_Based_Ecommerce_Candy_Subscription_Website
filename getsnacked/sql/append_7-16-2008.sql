ALTER TABLE `orders` MODIFY COLUMN `orderStatus` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned','processing','attention') NOT NULL DEFAULT 'new';

ALTER TABLE `ordersHistory` MODIFY COLUMN `orderStatus` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned','processing','attention') NOT NULL DEFAULT 'new';

ALTER TABLE `subOrders` MODIFY COLUMN `status` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned','processing','attention') NOT NULL DEFAULT 'new';

ALTER TABLE `subOrdersHistory` MODIFY COLUMN `status` ENUM('new','fulfilled','backordered','cancelled','paymentdeclined','returned','processing','attention') NOT NULL DEFAULT 'new';