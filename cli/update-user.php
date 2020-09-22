#!/usr/bin/php
<?php
$isUpdate = true;
require(__DIR__ . '/_update-or-create-user.php');

$username = cliInitUser($options['user']);

echo 'RSSServer updating user “', $username, "”…\n";

$ok = RSSServer_user_Controller::updateUser(
	$username,
	empty($options['mail_login']) ? null : $options['mail_login'],
	empty($options['password']) ? '' : $options['password'],
	$values);

if (!$ok) {
	fail('RSSServer could not update user!');
}

invalidateHttpCache($username);

accessRights();

done($ok);
