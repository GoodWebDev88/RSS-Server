#!/usr/bin/php
<?php
$isUpdate = false;
require(__DIR__ . '/_update-or-create-user.php');

$username = $options['user'];
if (!RSSServer_user_Controller::checkUsername($username)) {
	fail('RSSServer error: invalid username “' . $username .
		'”! Must be matching ' . RSSServer_user_Controller::USERNAME_PATTERN);
}

$usernames = listUsers();
if (preg_grep("/^$username$/i", $usernames)) {
	fail('RSSServer error: username already taken “' . $username . '”');
}

echo 'RSSServer creating user “', $username, "”…\n";

$ok = RSSServer_user_Controller::createUser(
	$username,
	empty($options['mail_login']) ? '' : $options['mail_login'],
	empty($options['password']) ? '' : $options['password'],
	$values,
	!isset($options['no_default_feeds'])
);

if (!$ok) {
	fail('RSSServer could not create user!');
}

invalidateHttpCache(RSSServer_Context::$system_conf->default_user);

echo '• Remember to refresh the feeds of the user: ', $username , "\n",
	"\t", './cli/actualize-user.php --user ', $username, "\n";

accessRights();

done($ok);
