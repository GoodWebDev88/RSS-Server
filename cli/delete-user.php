#!/usr/bin/php
<?php
require(__DIR__ . '/_cli.php');

$params = array(
	'user:',
);

$options = getopt('', $params);

if (!validateOptions($argv, $params) || empty($options['user'])) {
	fail('Usage: ' . basename(__FILE__) . " --user username");
}
$username = $options['user'];
if (!RSSServer_user_Controller::checkUsername($username)) {
	fail('RSSServer error: invalid username “' . $username . '”');
}

$usernames = listUsers();
if (!preg_grep("/^$username$/i", $usernames)) {
	fail('RSSServer error: username not found “' . $username . '”');
}

if (strcasecmp($username, RSSServer_Context::$system_conf->default_user) === 0) {
	fail('RSSServer error: default user must not be deleted: “' . $username . '”');
}

echo 'RSSServer deleting user “', $username, "”…\n";

$ok = RSSServer_user_Controller::deleteUser($username);

invalidateHttpCache(RSSServer_Context::$system_conf->default_user);

done($ok);
