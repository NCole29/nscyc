<?php

/**
 * @file
 * Install block content.
 */

use Drupal\block_content\Entity\BlockContent;

function club_memberinfo_block() {

  // Add MemberInfo block for "My Account" page.
  $body = "<h3><strong>MEMBER INFO</strong></h3>
	<p>Club members have access to special features when logged in to the Club website.</p>
	<p><strong>Self-service account links</strong></p>
	<ul>
		<li><a href='/user/edit-contact-info'>Edit contact info</a> - Keep your contact info up-to-date!</li>
		<li>Edit email address and password - Use the link under My Account and your username</li>
		<li><a href='/user/renew-membership'>Renew your membership</a></li>
	</ul>
	<p><strong>Member-only website content</strong></p>
	<ul>
		<li>Sign up for the <a href='/ride-gps-club-account'>RideWithGPS club account</a></li>
		<li>See member-only announcements on the home page&nbsp;when logged-in</li>
	</ul>
	<p>Use the <strong><a href='/user'>My Account</a></strong> link at the top right of the page to return here from anywhere in the website.</p>
	<p><strong>Questions? </strong>Send an email to our&nbsp;<strong><a href='/club-leaders'>Membership Coordinator</a></strong></p>
	<p><strong>Note: </strong>Site pages load faster when you are not logged in. Log out when you do not need access to restricted information.</p>";

  $block = BlockContent::create([
    'info' => 'Member Info',
    'type' => 'basic',
    'uuid' => 'member_info_block',
    'langcode' => 'en',
    'body' => [
      'value' => $body,
      'format' => 'basic_html',
    ],
  ]);
  $block->save();
}
