<?php
//NB: Do not edit; use ./constants.local.php instead.

//<Not customisable>
define('RSSSERVER_VERSION', '1.16.2');
define('RSSSERVER_WEBSITE', 'https://rssserver.org');
define('RSSSERVER_WIKI', 'https://rssserver.github.io/RSSServer/');

define('RSSSERVER_PATH', __DIR__);
define('PUBLIC_PATH', RSSSERVER_PATH . '/p');
define('PUBLIC_TO_INDEX_PATH', '/i');
define('INDEX_PATH', PUBLIC_PATH . PUBLIC_TO_INDEX_PATH);
define('PUBLIC_RELATIVE', '..');
define('LIB_PATH', RSSSERVER_PATH . '/lib');
define('APP_PATH', RSSSERVER_PATH . '/app');
define('CORE_EXTENSIONS_PATH', LIB_PATH . '/core-extensions');
//</Not customisable>

function safe_define($name, $value) {
	if (!defined($name)) {
		return define($name, $value);
	}
}

if (file_exists(__DIR__ . '/constants.local.php')) {
	//Include custom / local settings:
	include(__DIR__ . '/constants.local.php');
}

safe_define('RSSSERVER_USERAGENT', 'RSSServer/' . RSSSERVER_VERSION . ' (' . PHP_OS . '; ' . RSSSERVER_WEBSITE . ')');

// PHP text output compression http://php.net/ob_gzhandler (better to do it at Web server level)
safe_define('PHP_COMPRESSION', false);

safe_define('COPY_LOG_TO_SYSLOG', filter_var(getenv('COPY_LOG_TO_SYSLOG'), FILTER_VALIDATE_BOOLEAN));
// For cases when syslog is not available
safe_define('COPY_SYSLOG_TO_STDERR', filter_var(getenv('COPY_SYSLOG_TO_STDERR'), FILTER_VALIDATE_BOOLEAN));

// Maximum log file size in Bytes, before it will be divided by two
safe_define('MAX_LOG_SIZE', 1048576);

//This directory must be writable
safe_define('DATA_PATH', RSSSERVER_PATH . '/data');

safe_define('UPDATE_FILENAME', DATA_PATH . '/update.php');
safe_define('USERS_PATH', DATA_PATH . '/users');
safe_define('ADMIN_LOG', USERS_PATH . '/_/log.txt');
safe_define('API_LOG', USERS_PATH . '/_/log_api.txt');
safe_define('CACHE_PATH', DATA_PATH . '/cache');
safe_define('PSHB_LOG', USERS_PATH . '/_/log_pshb.txt');
safe_define('PSHB_PATH', DATA_PATH . '/PubSubHubbub');
safe_define('EXTENSIONS_DATA', DATA_PATH . '/extensions-data');
safe_define('THIRDPARTY_EXTENSIONS_PATH', RSSSERVER_PATH . '/extensions');

//Deprecated constants
safe_define('EXTENSIONS_PATH', RSSSERVER_PATH . '/extensions');

//Directory used for feed mutex with *.rssserver.lock files. Must be writable.
safe_define('TMP_PATH', sys_get_temp_dir());
