DROP TABLE `affiliateCustomPayouts`;

ALTER TABLE `customPayouts` ADD COLUMN `type` ENUM('affiliate', 'customer') NOT NULL AFTER `payoutID`
, DROP INDEX `customPayout`,
 ADD UNIQUE INDEX `ID_offerID_type` USING BTREE(`ID`, `offerID`, `type`);

DROP TABLE `affiliateRecurringPayouts`;

ALTER TABLE `recurringPayouts` ADD COLUMN `type` ENUM('affiliate', 'customer') NOT NULL AFTER `recurringPayoutID`;

DROP TABLE `affiliateOrderReference`;

ALTER TABLE `orderReference` ADD COLUMN `type` ENUM('affiliate', 'customer', 'invalid') NOT NULL AFTER `orderReferenceID`;

CREATE TABLE `exclusiveOffers` (
  `exclusiveOfferID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('affiliate', 'customer') NOT NULL,
  `ID` INTEGER(11) UNSIGNED NOT NULL,
  `offerID` INTEGER(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`exclusiveOfferID`),
  INDEX `offerID_type_ID`(`offerID`, `type`, `ID`)
)
ENGINE = InnoDB;

ALTER TABLE `affiliateTracking` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `campaignForwards` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `invalidTracking` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `offerDeviations` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `orderReference` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `recurringPayouts` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `tracking` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `trackingTags` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
ALTER TABLE `userPaths` MODIFY COLUMN `subID` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

INSERT INTO `campaigns` (`type`,  `name`,  `availability`,  `subject`,  `html`,  `text`,  `fromEmail`,  `linkedCampaign`,  `sendInterval`,  `dateAdded`,  `lastModified`) VALUES ('email', 'orderAlert', 'admin', '{$_SITENAME} has recieved an order', '<body>
<div style="width: 700px">
	<p>
		{$_SITENAME} has received an order.
	</p>
{if $order.orderID}
	<div style="border-bottom: 1px solid #7D7D7D; background-color: #EEE; font-weight: normal; width: 50%; padding: 2px 10px"><strong>Order number:</strong>&nbsp;&nbsp;{$order.orderID}</div>
	<br />
{/if}
	<table style="width: 100%" cellspacing="0">
		<tr><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Description</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Price</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Quantity</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Sub Total</td></tr>
{foreach from=$package key=id item=details}
		<tr><td style="padding: 2px 10px; width: 250px">{$details.N}</td><td align="right" style="padding: 2px 10px">${$details.C|string_format:"%.2f"}</td><td align="right" style="padding: 2px 10px">{$details.Q}</td><td align="right" style="padding: 2px 10px">${$details.C*$details.Q|string_format:"%.2f"}</td></tr>
{/foreach}
		<tr><td colspan="2"></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px"><strong>Shipping</strong></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-size: 13px; padding: 2px 10px" align="right">${$order.shippingCost|string_format:"%.2f"}</td></tr>
		<tr><td colspan="2"></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px"><strong>Final Cost</strong></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-size: 13px; padding: 2px 10px" align="right">${$order.totalCost|string_format:"%.2f"}</td></tr>
	</table>
	<div style="clear: both">&nbsp;</div>
	<div style="width: 49%; float: left">
		<div style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px; margin: 0 0 5px 0">Billing Address</div>
		<div style="margin-left: 10px">
			{$billingAddress.first}<br />
			{$billingAddress.last}<br />
			{$billingAddress.address1}<br />
{if $billingAddress.address2 != \'\'}
			{$billingAddress.address2}<br />
{/if}
			{$billingAddress.city},&nbsp;
{if $billingAddress.country == \'USA\'}
			{$billingAddress.state}&nbsp;
			{$billingAddress.postal}
{else}
			{$billingAddress.state}&nbsp;
			{$billingAddress.postal}<br />
			{$billingAddress.country}
{/if}
{if $billingAddress.phone != \'\'}
			<br />{$billingAddress.phone}
{/if}
{if $billingAddress.email != \'\'}
			<br />{$billingAddress.email}
{/if}
		</div>
	</div>
	<div style="margin-left: 10px; width: 49%; float: right">
		<div style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px; margin: 0 0 5px 0">Shipping Address</div>
		<div style="margin-left: 10px">
			{$shippingAddress.first}<br />
			{$shippingAddress.last}<br />
			{$shippingAddress.address1}<br />
{if $shippingAddress.address2 != \'\'}
			{$shippingAddress.address2}<br />
{/if}
			{$shippingAddress.city},&nbsp;
{if $shippingAddress.country == \'USA\'}
			{$shippingAddress.state}&nbsp;
			{$shippingAddress.postal}
{else}
			{$shippingAddress.state}&nbsp;
			{$shippingAddress.postal}<br />
			{$shippingAddress.country}
{/if}
{if $shippingAddress.phone != \'\'}
			<br />{$shippingAddress.phone}
{/if}
{if $shippingAddress.email != \'\'}
			<br />{$shippingAddress.email}
{/if}
		</div>
	</div>
	<div style="clear: both">&nbsp;</div>
	<table style="width: 100%">
		<tr><td colspan="2" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px">Payment Method</td></tr>
{if $paymentMethod.paymentMethod == \'cc\'}
		<tr><td style="padding: 2px 10px"><strong>Credit Card:</strong></td><td style="padding: 2px 10px">{$paymentMethod.ccType}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Card Number:</strong></td><td style="padding: 2px 10px">Ending in {$paymentMethod.accNum_LastFour}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Expiration:</strong></td><td style="padding: 2px 10px">{$paymentMethod.expMonth}/{$paymentMethod.expYear}</td></tr>
{elseif $paymentMethod.paymentMethod == \'echeck\'}
		<tr><td style="padding: 2px 10px"><strong>Bank:</strong></td><td style="padding: 2px 10px">{$paymentMethod.bName}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Routing Number:</strong></td><td style="padding: 2px 10px">{$paymentMethod.aba}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Name on Account:</strong></td><td style="padding: 2px 10px">{$paymentMethod.bAccName}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Account Type:</strong></td><td style="padding: 2px 10px">{$paymentMethod.accType}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Account Number:</strong></td><td style="padding: 2px 10px">Ending in {$paymentMethod.accNum_LastFour}</td></tr>
{else}
		<tr><td colspan="2" style="padding: 2px 10px">
			Please by check or money order to {$_COMPANYNAME} at:<br />
			<br />
			{if $_MAINADDRESS1}{$_MAINADDRESS1}<br />{/if}
			{if $_MAINADDRESS2}{$_MAINADDRESS2}<br />{/if}
			{if $_MAINADDRESS3}{$_MAINADDRESS3}<br />{/if}
			{$_MAINCITY}, {$_MAINSTATE} {$_MAINPOSTAL}<br />
		</td></tr>
{/if}
	</table>
</div>
</body>', '{$_SITENAME} has received an order.

{if $order.orderID}
Order number:  {$order.orderID}

{/if}
Order details:
{foreach from=$package key=id item=details}
{$details.N}: {$details.Q} x ${$details.C|string_format:"%.2f"} = ${$details.C*$details.Q|string_format:"%.2f"}
{/foreach}
Shipping Cost: ${$order.shippingCost|string_format:"%.2f"}
Final Cost: ${$order.totalCost|string_format:"%.2f"}

Billing Address:
{$billingAddress.first}
{$billingAddress.last}
{$billingAddress.address1}
{if $billingAddress.address2 != \'\'}
{$billingAddress.address2}
{/if}
{if $billingAddress.country == \'USA\'}
{$billingAddress.city}, {$billingAddress.state} {$billingAddress.postal}
{else}
{$billingAddress.city}
{$billingAddress.state}
{$billingAddress.postal}
{$billingAddress.country}
{/if}
{if $billingAddress.phone != \'\'}
{$billingAddress.phone}
{/if}
{if $billingAddress.email != \'\'}
{$billingAddress.email}
{/if}

Shipping Address:
{$shippingAddress.first}
{$shippingAddress.last}
{$shippingAddress.address1}
{if $shippingAddress.address2 != \'\'}
{$shippingAddress.address2}
{/if}
{if $shippingAddress.country == \'USA\'}
{$shippingAddress.city}, {$shippingAddress.state} {$shippingAddress.postal}
{else}
{$shippingAddress.city}
{$shippingAddress.state}
{$shippingAddress.postal}
{$shippingAddress.country}
{/if}
{if $shippingAddress.phone != \'\'}
{$shippingAddress.phone}
{/if}
{if $shippingAddress.email != \'\'}
{$shippingAddress.email}
{/if}

Payment Method:
{if $paymentMethod.paymentMethod == \'cc\'}
Credit Card: {$paymentMethod.ccType}
Card Number: Ending in {$paymentMethod.accNum_LastFour}
Expiration: {$paymentMethod.expMonth}/{$paymentMethod.expYear}
{elseif $paymentMethod.paymentMethod == \'echeck\'}
Bank: {$paymentMethod.bName}
Routing Number: {$paymentMethod.aba}
Name on Account: {$paymentMethod.bAccName}
Account Type: {$paymentMethod.accType}
Account Number: Ending in {$paymentMethod.accNum_LastFour}
{else}
Pay by check or money order to {$_COMPANYNAME} at:

{if $_MAINADDRESS1}
{$_MAINADDRESS1}
{/if}
{if $_MAINADDRESS2}
{$_MAINADDRESS2}
{/if}
{if $_MAINADDRESS3}
{$_MAINADDRESS3}
{/if}
{$_MAINCITY}, {$_MAINSTATE} {$_MAINPOSTAL}

{/if}', 'noreply@SITE.com', 0, 0, NOW(), NOW());

INSERT INTO `campaigns` (`type`,  `name`,  `availability`,  `subject`,  `html`,  `text`,  `fromEmail`,  `linkedCampaign`,  `sendInterval`,  `dateAdded`,  `lastModified`) VALUES ('email', 'orderReceipt', 'admin', '{$_SITENAME} Order Acknowledgement', '<body>
<div style="width: 700px">
	<p>
		Thank you for ordering with {$_SITENAME}!
		<br />
		This is an acknowledgment of your recent order:
	</p>
{if $order.orderID}
	<div style="border-bottom: 1px solid #7D7D7D; background-color: #EEE; font-weight: normal; width: 50%; padding: 2px 10px"><strong>Order number:</strong>&nbsp;&nbsp;{$order.orderID}</div>
	<br />
{/if}
	<table style="width: 100%" cellspacing="0">
		<tr><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Description</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Price</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Quantity</td><td align="right" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px">Sub Total</td></tr>
{foreach from=$package key=id item=details}
		<tr><td style="padding: 2px 10px; width: 250px">{$details.N}</td><td align="right" style="padding: 2px 10px">${$details.C|string_format:"%.2f"}</td><td align="right" style="padding: 2px 10px">{$details.Q}</td><td align="right" style="padding: 2px 10px">${$details.C*$details.Q|string_format:"%.2f"}</td></tr>
{/foreach}
		<tr><td colspan="2"></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px"><strong>Shipping</strong></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-size: 13px; padding: 2px 10px" align="right">${$order.shippingCost|string_format:"%.2f"}</td></tr>
		<tr><td colspan="2"></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; font-size: 13px; padding: 2px 10px"><strong>Final Cost</strong></td><td style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-size: 13px; padding: 2px 10px" align="right">${$order.totalCost|string_format:"%.2f"}</td></tr>
	</table>
	<div style="clear: both">&nbsp;</div>
	<div style="width: 49%; float: left">
		<div style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px; margin: 0 0 5px 0">Billing Address</div>
		<div style="margin-left: 10px">
			{$billingAddress.first}<br />
			{$billingAddress.last}<br />
			{$billingAddress.address1}<br />
{if $billingAddress.address2 != \'\'}
			{$billingAddress.address2}<br />
{/if}
			{$billingAddress.city},&nbsp;
{if $billingAddress.country == \'USA\'}
			{$billingAddress.state}&nbsp;
			{$billingAddress.postal}
{else}
			{$billingAddress.state}&nbsp;
			{$billingAddress.postal}<br />
			{$billingAddress.country}
{/if}
{if $billingAddress.phone != \'\'}
			<br />{$billingAddress.phone}
{/if}
{if $billingAddress.email != \'\'}
			<br />{$billingAddress.email}
{/if}
		</div>
	</div>
	<div style="margin-left: 10px; width: 49%; float: right">
		<div style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px; margin: 0 0 5px 0">Shipping Address</div>
		<div style="margin-left: 10px">
			{$shippingAddress.first}<br />
			{$shippingAddress.last}<br />
			{$shippingAddress.address1}<br />
{if $shippingAddress.address2 != \'\'}
			{$shippingAddress.address2}<br />
{/if}
			{$shippingAddress.city},&nbsp;
{if $shippingAddress.country == \'USA\'}
			{$shippingAddress.state}&nbsp;
			{$shippingAddress.postal}
{else}
			{$shippingAddress.state}&nbsp;
			{$shippingAddress.postal}<br />
			{$shippingAddress.country}
{/if}
{if $shippingAddress.phone != \'\'}
			<br />{$shippingAddress.phone}
{/if}
{if $shippingAddress.email != \'\'}
			<br />{$shippingAddress.email}
{/if}
		</div>
	</div>
	<div style="clear: both">&nbsp;</div>
	<table style="width: 100%">
		<tr><td colspan="2" style="background-color: #EEE; border-bottom: 1px solid #7D7D7D; font-weight: bold; padding: 2px 10px">Payment Method</td></tr>
{if $paymentMethod.paymentMethod == \'cc\'}
		<tr><td style="padding: 2px 10px"><strong>Credit Card:</strong></td><td style="padding: 2px 10px">{$paymentMethod.ccType}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Card Number:</strong></td><td style="padding: 2px 10px">Ending in {$paymentMethod.accNum_LastFour}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Expiration:</strong></td><td style="padding: 2px 10px">{$paymentMethod.expMonth}/{$paymentMethod.expYear}</td></tr>
{elseif $paymentMethod.paymentMethod == \'echeck\'}
		<tr><td style="padding: 2px 10px"><strong>Bank:</strong></td><td style="padding: 2px 10px">{$paymentMethod.bName}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Routing Number:</strong></td><td style="padding: 2px 10px">{$paymentMethod.aba}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Name on Account:</strong></td><td style="padding: 2px 10px">{$paymentMethod.bAccName}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Account Type:</strong></td><td style="padding: 2px 10px">{$paymentMethod.accType}</td></tr>
		<tr><td style="padding: 2px 10px"><strong>Account Number:</strong></td><td style="padding: 2px 10px">Ending in {$paymentMethod.accNum_LastFour}</td></tr>
{else}
		<tr><td colspan="2" style="padding: 2px 10px">
			Please make checks or money orders payable to {$_COMPANYNAME} and mail to:<br />
			<br />
			{if $_MAINADDRESS1}{$_MAINADDRESS1}<br />{/if}
			{if $_MAINADDRESS2}{$_MAINADDRESS2}<br />{/if}
			{if $_MAINADDRESS3}{$_MAINADDRESS3}<br />{/if}
			{$_MAINCITY}, {$_MAINSTATE} {$_MAINPOSTAL}<br />
			<br />
			Your order will be processed once your payment has been received.
		</td></tr>
{/if}
	</table>
</div>
</body>', 'Thank you for ordering with {$_SITENAME}!
This is an acknowledgment of your recent order:

{if $order.orderID}
Your order number is:  {$order.orderID}

{/if}
Your order:
{foreach from=$package key=id item=details}
{$details.N}: {$details.Q} x ${$details.C|string_format:"%.2f"} = ${$details.C*$details.Q|string_format:"%.2f"}
{/foreach}
Shipping Cost: ${$order.shippingCost|string_format:"%.2f"}
Final Cost: ${$order.totalCost|string_format:"%.2f"}

Billing Address:
{$billingAddress.first}
{$billingAddress.last}
{$billingAddress.address1}
{if $billingAddress.address2 != \'\'}
{$billingAddress.address2}
{/if}
{if $billingAddress.country == \'USA\'}
{$billingAddress.city}, {$billingAddress.state} {$billingAddress.postal}
{else}
{$billingAddress.city}
{$billingAddress.state}
{$billingAddress.postal}
{$billingAddress.country}
{/if}
{if $billingAddress.phone != \'\'}
{$billingAddress.phone}
{/if}
{if $billingAddress.email != \'\'}
{$billingAddress.email}
{/if}

Shipping Address:
{$shippingAddress.first}
{$shippingAddress.last}
{$shippingAddress.address1}
{if $shippingAddress.address2 != \'\'}
{$shippingAddress.address2}
{/if}
{if $shippingAddress.country == \'USA\'}
{$shippingAddress.city}, {$shippingAddress.state} {$shippingAddress.postal}
{else}
{$shippingAddress.city}
{$shippingAddress.state}
{$shippingAddress.postal}
{$shippingAddress.country}
{/if}
{if $shippingAddress.phone != \'\'}
{$shippingAddress.phone}
{/if}
{if $shippingAddress.email != \'\'}
{$shippingAddress.email}
{/if}

Payment Method:
{if $paymentMethod.paymentMethod == \'cc\'}
Credit Card: {$paymentMethod.ccType}
Card Number: Ending in {$paymentMethod.accNum_LastFour}
Expiration: {$paymentMethod.expMonth}/{$paymentMethod.expYear}
{elseif $paymentMethod.paymentMethod == \'echeck\'}
Bank: {$paymentMethod.bName}
Routing Number: {$paymentMethod.aba}
Name on Account: {$paymentMethod.bAccName}
Account Type: {$paymentMethod.accType}
Account Number: Ending in {$paymentMethod.accNum_LastFour}
{else}
Please make checks or money orders payable to {$_COMPANYNAME} and mail to:

{if $_MAINADDRESS1}
{$_MAINADDRESS1}
{/if}
{if $_MAINADDRESS2}
{$_MAINADDRESS2}
{/if}
{if $_MAINADDRESS3}
{$_MAINADDRESS3}
{/if}
{$_MAINCITY}, {$_MAINSTATE} {$_MAINPOSTAL}

{/if}', 'noreply@SITE.com', 0, 0, NOW(), NOW());