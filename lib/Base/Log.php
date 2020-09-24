<?php
/**
 * The Log class is used to log errors
 */
class Base_Log {
	/**
	 * Records a message in a specific log file
	 * Message not logged if
	 * - environment = SILENT
	 * - level = LOG_WARNING and environment = PRODUCTION
	 * - level = LOG_NOTICE and environment = PRODUCTION
	 * @param $ information error message / information to record
	 * @param $ level error level https://php.net/function.syslog
	 * @param $ file_name log file
	 * @throws Base_PermissionDeniedException
	 */
	public static function record($information, $level, $file_name = null) {
		$env = getenv('RSSSERVER_ENV');
		if ($env == '') {
			try {
				$conf = Base_Configuration::get('system');
				$env = $conf->environment;
			} catch (Base_ConfigurationException $e) {
				$env = 'production';
			}
		}

		if (! ($env === 'silent'
		       || ($env === 'production'
		       && ($level >= LOG_NOTICE)))) {
			$username = Base_Session::param('currentUser', '');
			if ($username == '') {
				$username = '_';
			}
			if ($file_name === null) {
				$file_name = join_path(USERS_PATH, $username, 'log.txt');
			}

			switch ($level) {
			case LOG_ERR :
				$level_label = 'error';
				break;
			case LOG_WARNING :
				$level_label = 'warning';
				break;
			case LOG_NOTICE :
				$level_label = 'notice';
				break;
			case LOG_DEBUG :
				$level_label = 'debug';
				break;
			default :
				$level = LOG_INFO;
				$level_label = 'info';
			}

			$log = '[' . date('r') . ']'
			     . ' [' . $level_label . ']'
			     . ' --- ' . $information . "\n";

			if (defined('COPY_LOG_TO_SYSLOG') && COPY_LOG_TO_SYSLOG) {
				syslog($level, '[' . $username . '] ' . trim($log));
			}

			self::ensureMaxLogSize($file_name);

			if (file_put_contents($file_name, $log, FILE_APPEND | LOCK_EX) === false) {
				throw new Base_PermissionDeniedException($file_name, Base_Exception::ERROR);
			}
		}
	}

	/**
	 * Make sure we do not waste a huge amount of disk space with old log messages.
	 *
	 * This method can be called multiple times for one script execution, but its result will not change unless
	 * you call clearstatcache() in between. We won't due do that for performance reasons.
	 *
	 * @param $file_name
	 * @throws Base_PermissionDeniedException
	 */
	protected static function ensureMaxLogSize($file_name) {
		$maxSize = defined('MAX_LOG_SIZE') ? MAX_LOG_SIZE : 1048576;
		if ($maxSize > 0 && @filesize($file_name) > $maxSize) {
			$fp = fopen($file_name, 'c+');
			if ($fp && flock($fp, LOCK_EX)) {
				fseek($fp, -intval($maxSize / 2), SEEK_END);
				$content = fread($fp, $maxSize);
				rewind($fp);
				ftruncate($fp, 0);
				fwrite($fp, $content ? $content : '');
				fwrite($fp, sprintf("[%s] [notice] --- Log rotate.\n", date('r')));
				fflush($fp);
				flock($fp, LOCK_UN);
			} else {
				throw new Base_PermissionDeniedException($file_name, Base_Exception::ERROR);
			}
			if ($fp) {
				fclose($fp);
			}
		}
	}

	/**
	 * Automates the log of global variables $ _GET and $ _POST
	 * Calls the record function (...)
	 * Only works in "development" environment
	 * @param $ file_name log file
	 */
	public static function recordRequest($file_name = null) {
		$msg_get = str_replace("\n", '', '$_GET content : ' . print_r($_GET, true));
		$msg_post = str_replace("\n", '', '$_POST content : ' . print_r($_POST, true));

		self::record($msg_get, LOG_DEBUG, $file_name);
		self::record($msg_post, LOG_DEBUG, $file_name);
	}

	/**
	 * Some helpers to Base_Log::record() method
	 * Parameters are the same of those of the record() method.
	 */
	public static function debug($msg, $file_name = null) {
		self::record($msg, LOG_DEBUG, $file_name);
	}
	public static function notice($msg, $file_name = null) {
		self::record($msg, LOG_NOTICE, $file_name);
	}
	public static function warning($msg, $file_name = null) {
		self::record($msg, LOG_WARNING, $file_name);
	}
	public static function error($msg, $file_name = null) {
		self::record($msg, LOG_ERR, $file_name);
	}
}
