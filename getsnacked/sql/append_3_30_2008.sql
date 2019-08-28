CREATE TABLE `content` (
  `contentID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `site` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `content` TEXT NOT NULL,
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  PRIMARY KEY (`contentID`),
  UNIQUE INDEX `site_name`(`site`, `name`)
)
ENGINE = InnoDB;

CREATE TABLE `contentHistory` (
  `contentHistoryID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `contentID` INTEGER UNSIGNED NOT NULL,
  `site` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `content` TEXT NOT NULL,
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  `effectiveThrough` DATETIME NOT NULL,
  PRIMARY KEY (`contentHistoryID`),
  INDEX `contentID`(`contentID`),
  INDEX `site_name`(`site`, `name`),
  INDEX `lastModified_effectiveThrough`(`lastModified`, `effectiveThrough`)
)
ENGINE = InnoDB;

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'about', '<p>
	{$_SITENAME} is proud to offer in demand products at the best prices.  We are a new company, but we are quickly building a great service record of quick shipments and deliveries, as well as great customer service quality, and we are darn proud of that, too!
</p>
<p>
	All our items are IN STOCK and ready to ship out the very next day of your order.  We ship with UPS, and USPS.
</p>', NOW(), NOW());

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'terms', '<p>
	<h2>Thanks for Visiting {$_SITENAME}</h2>
</p>
<p>
	We hope you enjoy using our Web site and our service.
</p>
<p>
	We wish to provide you with the best service and a great experience. As a result, we need to point out the legal rules governing usage of our Web site. We hope all of these terms and conditions are clear, but if you have any questions after reading this, please feel free to contact us at: <a href="/content/display/page/contact">http://{$_SITEURL}.com/content/display/page/contact</a>.
</p>
<p>
	<h2>Agreement with Our Terms and Conditions</h2>
	This web site is owned and operated by {$_SITENAME}. Please feel free to browse this Site; however, your access to, and use of, this Web site is subject to the following terms and conditions of use and all applicable laws. By accessing and browsing this Site, you accept, without limitation or qualification, these Terms and Conditions.  We reserve the right, at our discretion, to change, modify, add, or remove portions of these terms and conditions at any time. Please check this page periodically for changes. Your continued use of our Web site following the posting of changes to these terms will mean that you accept those changes. 
</p>
<p>
	Please make sure you read these terms and conditions carefully.  By using this site, you agree to accept these terms and conditions.  If you do not agree with our terms and conditions, please do not use our Web site.
</p>
<p>
	Use of the Web site is also subject to {$_SITENAME}�s posted privacy policy, which may be viewed at <a href="/content/display/page/privacy">http://{$_SITEURL}.com/content/display/page/privacy</a>. Your use of the Web site constitutes your acknowledgement of and agreement with the terms of the privacy policy.
</p>
<p>
	<h2>Restrictions on the Use of the Materials in Our Web Site</h2>
	Unless otherwise specified, the materials in our site are presented to provide information about {$_SITENAME}, its products offered for sale and to provide related services and information to visitors.
</p>
<p>
	The {$_SITENAME} Web site and all of its content is protected by international copyright and trademark laws. No material (including, but not limited to, the text, images, audio and/or video) and no software (including but not limited to any images or files incorporated in or generated by the software or data accompanying such software) (individually and collectively the "Materials") may be copied, reproduced, republished, uploaded, posted, transmitted, or distributed in any way or decompiled, reverse engineered or disassembled, except that, unless otherwise specified on the Web site with respect to particular materials, you may download one copy of the Materials on any single computer for your personal, non-commercial use only, provided you keep completely intact and unmodified all copyright and other proprietary notices.
</p>
<p>
	Be aware that sometimes we provide access to other Web sites from our Web site. We don\'t endorse or approve any products or information offered at sites you reach through our Web site.
</p>
<p>
	<h2>Online Conduct</h2>
	Any conduct by you that in {$_SITENAME}�s sole discretion restricts or inhibits any other user from using or enjoying our Web site will not be permitted. You agree to use our Web site only for lawful purposes. You are prohibited from using our Web site for posting on or transmitting any unlawful, threatening, harassing, abusive, harmful, defamatory, vulgar, obscene, sexually explicit, profane, hateful, racially, ethnically, or otherwise objectionable material of any kind, including but not limited to any material that encourages conduct that would constitute a criminal offense, give rise to civil liability, or otherwise violate any applicable local, state, national, or international law.
</p>
<p>
	You agree to indemnify {$_SITENAME} and to hold {$_SITENAME} harmless from any and all claims (including reasonable attorneys\' fees and costs) resulting from your acts, omissions, or representations in any way related to these Terms and Conditions or your access to, and use of, this Site.
</p>
<p>
	<h2>Treatment of Sales Tax</h2>
	We are required by law to charge applicable sales tax on products shipped to those jurisdictions that levy such a tax and in which we have a physical location.
</p>
<p>
	<h2>Disclaimer and Limitation of Liability</h2>
	The following is important information about your use of this site. None of the following affects in any way our return policy. If for any reason you are not satisfied with a purchase that you make from our site, please return it. Our return policy and instructions will be included with your order.
</p>
<p>
	THE <strong>{$_SITENAME}</strong> WEB SITE CONTENT IS PROVIDED ON AN "AS IS" "AS AVAILABLE" BASIS AND WITHOUT REPRESENTATIONS OR WARRANTIES OF ANY KIND, EITHER EXPRESS OR IMPLIED, AS TO THE OPERATION OF THE SITE, THE INFORMATION, CONTENT, MATERIALS OR PRODUCTS INCLUDED ON THIS SITE. TO THE FULLEST EXTENT PERMISSIBLE PURSUANT TO APPLICABLE LAW, <strong>{$_SITENAME}</strong> DISCLAIMS ALL REPRESENTATIONS AND WARRANTIES WITH RESPECT TO THIS WEB SITE OR ITS CONTENTS, WHETHER EXPRESS OR IMPLIED OR STATUTORY, INCLUDING, BUT NOT LIMITED TO, WARRANTIES OF TITLE, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
</p>
<p>
	<strong>{$_SITENAME}</strong> WILL NOT BE LIABLE FOR ANY DAMAGES OR INJURY, INCLUDING BUT NOT LIMITED TO DIRECT, INDIRECT, INCIDENTAL, PUNITIVE AND CONSEQUENTIAL DAMAGES THAT ACCOMPANY OR RESULT FROM YOUR USE OF OUR WEB SITE. THESE INCLUDE (BUT ARE NOT LIMITED TO) DAMAGES OR INJURY CAUSED BY ANY USE OF (OR INABILITY TO USE) THE WEB SITE OR ANY SITE TO WHICH YOU HYPERLINK FROM OUR WEB SITE, FAILURE OF PERFORMANCE, ERROR, OMISSION, INACCURACY, INTERRUPTION, DEFECT, DELAY IN OPERATION OR TRANSMISSION, COMPUTER VIRUS, OR LINE FAILURE.
</p>
<p>
	FURTHERMORE, WE ARE NOT LIABLE EVEN IF WE\'VE BEEN NEGLIGENT OR IF OUR AUTHORIZED REPRESENTATIVE HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
</p>
<p>
	EXCEPTION: IN CERTAIN STATES THE LAW MAY NOT ALLOW US TO LIMIT OR EXCLUDE LIABILITY FOR THESE "INCIDENTAL" OR "CONSEQUENTIAL" DAMAGES, SO THE ABOVE LIMITATION MAY NOT APPLY.
</p>
<p>
	THIS WEB SITE MAY CONTAIN TYPOGRAPHICAL ERRORS OR INACCURACIES AND MAY NOT BE COMPLETE OR CURRENT. {$_SITENAME} THEREFORE RESERVES THE RIGHT TO CORRECT ANY ERRORS, INACCURACIES OR OMISSIONS (INCLUDING AFTER AN ORDER HAS BEEN SUBMITTED) AND TO CHANGE OR UPDATE INFORMATION AT ANY TIME WITHOUT PRIOR NOTICE. PLEASE NOTE THAT SUCH ERRORS, INACCURACIES OR OMISSIONS MAY RELATE TO PRICING AND AVAILABILITY. WE APOLOGIZE FOR ANY INCONVENIENCE.
</p>
<p>
	PLEASE BE ADVISED THAT TO THE EXTENT THAT <strong>{$_SITENAME}</strong> PROVIDES ANY CONTENT FROM THIRD PARTIES, SUCH CONTENT IS PROVIDED FOR INFORMATIONAL PURPOSES ONLY AND <strong>{$_SITENAME}</strong> CANNOT AND DOES NOT INVESTIGATE THE LEGITIMACY, VALIDITY, ACCURACY AND LEGALITY OF THE ITEMS LISTED AND EXPRESSLY DISCLAIMS ANY RESPONSIBILITY OR LIABILITY ARISING OUT OF OR RELATING TO ANY THIRD PARTY CONTENT LISTED.
</p>
<p>
	<h2>Jurisdiction</h2>
	This Agreement shall be construed solely in accordance with the internal laws of the State of New York and the United States, without giving effect to principles of conflicts of laws. Any actions, proceedings or suits concerning or relating to this Agreement may only be brought in a court of competent jurisdiction in the City and County of New York, New York, or in the U.S. District Court for the District of New York, and each party hereby consents to the jurisdiction and venue of such court, and waives any objections thereto. 
</p>
<p>
	We in no way imply that the materials on our Web site are appropriate or available for use outside of the United States. If you use our Web site from locations outside of the United States, you are responsible for compliance with any applicable local laws. In addition, you may not use or export the materials in violation of U.S. export laws and regulations.
</p>
<p>
	<h2>Additional Points about the Terms and Conditions of this User Agreement</h2>
	These terms and conditions and the agreement they create, shall be governed by and interpreted according to the laws of the State of New York. {$_SITENAME} reserves the right to bring any civil action in New York, arising from your violation of the terms and conditions of this Agreement.
</p>
<p>
	If any provision of this Agreement shall be deemed unlawful, void, or for any reason unenforceable, then that provision shall be deemed severable from this Agreement and shall not affect the validity and enforceability of any remaining provisions. {$_SITENAME} may modify these terms and conditions, and the agreement they create, at any time, simply by updating this posting and without notice to you. This is the entire agreement regarding all the matters that have been discussed in the preceding paragraphs.
</p>
<p>
	� {$smarty.now|date_format:"%Y"} {$_SITENAME}. All rights reserved.
</p>', NOW(), NOW());

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'privacy', '<h2>We are committed to protecting your privacy.</h2>
<p>
	<h2>Privacy &amp; Security</h2>
	To make your shopping experience more convenient, we must sometimes ask you for information. We maintain the privacy of your information using security technologies and adhering to policies that prevent unauthorized use of any personal information you provide.
</p>
<p>
	This privacy policy is intended to assist you in understanding what information we gather about you when you visit this Web site, how we use that information, and the safeguards we have in place for the information.
</p>
<p>
	<h2>How do we protect your information?</h2>
	When you make a purchase or access your account, {$_SITENAME} uses state-of-the-art secure server software and encryption technologies to protect the loss, misuse and alteration of the information.  SSL technology encrypts all your personal information before it is sent to us, preventing unauthorized access to your information. In addition, we use security procedures and practices including the use of firewalls to protect the data it stores on its servers.
</p>
<p>
	<h2>What type of information does {$_SITENAME} collect online?</h2>
</p>
<p>
	<h3>General Browsing:</h3>
	{$_SITENAME} gathers navigational and non-personal information about where visitors go and which services were used on our Web site (for example: product browsing and searching). This information allows us to see which areas of our Web site are most visited and helps us better understand our visitors\' experiences and preferences at {$_SITENAME}. With this information, we can improve the quality of our Web site by recognizing and delivering more of the features, areas and services our visitors prefer.
</p>
<p>
	<h3>Personal Information:</h3>
	If you make a purchase on our Web site, we will ask you to provide your name, email, billing address, telephone number, credit card or e-checking information, as well as the recipient\'s name and mailing address to which you would like the order shipped. By placing an order, we will save your billing information in your order record, as well as the shipping address you provide.
</p>
<p>
	We may also collect personal information from you if you enter the online contests that may be offered at this Web site. This information may include your name, mailing address, telephone number and e-mail address.
</p>
<p>
	Occasionally, we may offer special surveys, quizzes or polls. Participation in these surveys is completely voluntary, and your have a choice whether or not to disclose this information. Information requested may include your name, mailing address, e-mail address, and demographic information (such as zip code, age and gender). This information is used to improve and personalize your experience with our site and our product offerings.
</p>
<p>
	To ensure compliance with federal law, {$_SITENAME} does not knowingly collect or maintain information provided by children under the age of 13.
</p>
<p>
	<h2>How does {$_SITENAME} use the information collected online?</h2>
	The information we collect online is primarily used to complete and deliver purchases that you make at our web site and to provide services and information about our products to you.
</p>
<p>
	When you sign up for e-mails or newsletters, you will be added to the {$_SITENAME} e-mail marketing list and we will send you exclusive information about special offers, new products and much more. We will not sell, rent or lease your personally identifiable information to unaffiliated companies. Unless we have your permission or are required by law, we will only share the personal data you provide online with {$_SITENAME} and/or business partners who are acting on our behalf.
</p>
<p>
	We may occasionally provide you with the opportunity on our site to opt-in to receive e-mail messages from third parties. If you do opt-in we will share your e-mail address with the specific third party with whom you have opted-in to receive e-mail messages.
</p>
<p>
	Please be aware, even if you do not request to receive e-mail, or subsequently ask to be removed from the {$_SITENAME} e-mail marketing list, you may still receive e-mail confirmations for any purchases made on our Web site (product, gift card or Online Gift Certificate) and other operational e-mail.
</p>
<p>
	<h2>Third Party Service Providers</h2>
	We will use third parties to help us provide services to you and to give us comparative information on the performance of our site. These services include such things as operating our web site, monitoring site activity, conducting surveys, processing shopping, gift card and Online Gift Certificate orders, maintaining our database and administering and monitoring e-mails, surveys, drawings or contests.
</p>
<p>
	<h2>Legal Disclaimer</h2>
	Though we make every effort to preserve your privacy we may need to disclose personal information when required by law or in the good-faith belief that such action is necessary in order to conform to the edicts of the law or comply with a legal process served on our Web site.
</p>
<p>
	We do provide aggregate information about site usage and traffic patterns, stripped of any individual identification, to third parties to comply with various reporting obligations and for business or marketing purposes.
</p>
<p>
	<h2>Use of Cookies and other technology</h2>
	A cookie is a piece of data stored on the user�s computer tied to non-personal information about the user. Usage of a cookie is in no way linked to any personally identifiable information while on our site. We use both session ID cookies and persistent cookies. For the session ID cookie, once users close the browser, the cookie simply terminates. A persistent cookie is a small text file stored on the user�s hard drive for an extended period of time. Persistent cookies can be removed by following Internet browser help file directions.
</p>
<p>
We use cookies to tell us whether {$_SITENAME} has been previously visited by you or someone using your computer and to assist in the administration of your use of features and programs on our site. We may use third-party advertising companies to serve ads on our behalf (elsewhere on the Internet and not on this site). These companies may employ cookies and action tags (also known as single pixel gifs or web beacons) to measure advertising effectiveness. Any information that these third parties collect via cookies and action tags is completely anonymous.
</p>
<p>
	If you would like more information about this practice and your choices, please click here: <a href="http://www.networkadvertising.org/optout_nonppii.asp">http://www.networkadvertising.org/optout_nonppii.asp</a>
</p>
<p>
	Customers who wish to opt-out of receiving advertising should directly contact the website operator who has chosen to run the advertisements. It is very common to see advertisements on sites and it is up to the site operators to decide their ads operation at their own discretion.
</p>
<p>
	<h2>Links</h2>
	For your convenience, our Web site may contain links to other Web sites. These websites have their own policies regarding privacy. {$_SITENAME} is not responsible for the privacy practices, advertising, products or the content of other websites. Links that appear on {$_SITENAME} should not necessarily be deemed to imply that {$_SITENAME} endorses or has any affiliation with the linked Web sites. For your own protection, you should review their policies upon visiting these other sites to make sure they respect your privacy.
</p>
<p>
	<h2>Choice/Opt-Out</h2>
	Our site provides users the opportunity to opt-out of receiving email communications from us at the point where we request information about the visitor.
</p>
<p>
To opt-out, you may visit:<br />
<a href="/content/display/page/optout">http://{$_SITEURL}.com/content/display/page/optout</a>
</p>
<p>
	<h2>Updates to Privacy Policy</h2>
	Should there be a material change in the categories of information collected at {$_SITENAME} or a material change in how we use information collected at {$_SITENAME}, we will post the revised Privacy Policy on this Web site, state the effective date of the revised Privacy Policy at the bottom of the Privacy Policy and indicate these material changes by noting them in italic type with "[Revised]" following the revised sentence. If you are concerned about how your personal information is used, please visit our Web site often for this and other important announcements from {$_SITENAME}.
</p>
<p>
	<h2>Security Statement</h2>
	{$_SITENAME} has implemented the following security measures to protect the information we have collected at our Web site: use of technologies and policies such as limited access data-centers, firewall technology, and limitations of administrative access to our systems. 
</p>
<p>
	We designed {$_SITENAME} to accept shopping orders only from Web browsers that permit communication through Secure Socket Layer (SSL) technology; for example, 3.0 versions or higher of Netscape Navigator and versions 3.02 or higher of Internet Explorer. This means you\'ll be unable to inadvertently place an order through an unsecured connection. 
</p>
<p>
While we implement the above security measures for this Web site, you should be aware that 100% security is not always possible.
</p>
<p>
	<h2>Applicability</h2>
	This Privacy Policy applies only to the information collected online by {$_SITENAME}.
</p>
<p>
	<h2>Contact Us</h2>
	If you have additional questions or would like more information on this topic, please feel free to contact us at:
	<br />
	<a href="/content/display/page/contact">http://{$_SITEURL}.com/content/display/page/contact</a>
</p>
<p>
	This privacy policy is effective as of January 1, 2007
</p>', NOW(), NOW());

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'return', '<p>
	We guarantee a 15 day return policy from the day an item is received.  All returned items are subject to a 20% restocking fee.
</p>', NOW(), NOW());

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'optout', '<form action="/process/optout" method="post">
	We respect your privacy.
	<br /><br />
	Please enter an email address to unsubscribe from our marketing letters:
	<br /><br />
	<input type="text" name="email" value="" />
	<br /><br />
	<input type="submit" name="submit" value="Unsubscribe" class="mediumBlueButton" />
	<input type="hidden" name="action" value="unsubscribe" />
</form>', NOW(), NOW());

INSERT INTO `content` (`site`, `name`, `content`, `dateAdded`, `lastModified`) VALUES ('SITE', 'contact', 'If you have any questions, comments or concerns, please use the contact information below.
<br />
<div class="indent">
	{$_COMPANYNAME}
	<br />
	{$_MAINADDRESS1}
	<br />
{if $_MAINADDRESS2}
	{$_MAINADDRESS2}
	<br />
{/if}
{if $_MAINADDRESS3}
	{$_MAINADDRESS3}
	<br />
{/if}
	{$_MAINCITY}, {$_MAINSTATE} {$_MAINPOSTAL}
	<br />
{if $_MAINPHONE}
	<strong>Phone:</strong> {$_MAINPHONE}
	<br />
{/if}
{if $_MAINFAX}
	<strong>Fax:</strong> {$_MAINFAX}
{/if}
</div>', NOW(), NOW());

INSERT INTO `contentHistory` (`contentID`, `site`, `name`, `content`, `dateAdded`, `lastModified`, `effectiveThrough`) SELECT *, '9999-12-31 23:59:59' FROM `content`;

CREATE TABLE `contentImages` (
  `imageID` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `image` VARCHAR(45) NOT NULL,
  `size` INTEGER UNSIGNED NOT NULL,
  `width` INTEGER(4) UNSIGNED NOT NULL,
  `height` INTEGER(4) UNSIGNED NOT NULL,
  `dateAdded` DATETIME NOT NULL,
  `lastModified` DATETIME NOT NULL,
  PRIMARY KEY (`imageID`),
  UNIQUE INDEX `image`(`image`)
)
ENGINE = InnoDB;
