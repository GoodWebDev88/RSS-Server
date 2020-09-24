<?php

/**
 * The Session class manages the user session
 */
class Base_Session {
	/**
	 * Initializes the session, with a name
	 * The session name is used as the name for cookies and URLs (ie PHPSESSID).
	 * It must contain only alphanumeric characters; it must be short and descriptive
	 */
	public static function init($name) {
		$cookie = session_get_cookie_params();
		self::keepCookie($cookie['lifetime']);

		// démarre la session
		session_name($name);
		session_start();
	}

	/**
	 * Used to retrieve a session variable
	 * @param $ p the parameter to retrieve
	 * @return the value of the session variable, false if does not exist
	 */	
	public static function param($p, $default = false) {
		return isset($_SESSION[$p]) ? $_SESSION[$p] : $default;
	}

	/**
	 * Allows you to create or update a session variable
	 * @param $ p the parameter to create or modify
	 * @param $ v the value to assign, false to remove
	 */
	public static function _param($p, $v = false) {
		if ($v === false) {
			unset($_SESSION[$p]);
		} else {
			$_SESSION[$p] = $v;
		}
	}


	/**
	 * Allows you to delete a session
	 * @param $ force if false, does not clear the language parameter
	 */
	public static function unset_session($force = false) {
		$language = self::param('language');

		session_destroy();
		$_SESSION = array();

		if (!$force) {
			self::_param('language', $language);
			Base_Translate::reset($language);
		}
	}

	public static function getCookieDir() {
		// Get the script_name (e.g. /public/i/index.php) and keep only the path.
		$cookie_dir = '';
		if (!empty($_SERVER['HTTP_X_FORWARDED_PREFIX'])) {
			$cookie_dir .= rtrim($_SERVER['HTTP_X_FORWARDED_PREFIX'], '/ ');
		}
		$cookie_dir .= empty($_SERVER['REQUEST_URI']) ? '/' : $_SERVER['REQUEST_URI'];
		if (substr($cookie_dir, -1) !== '/') {
			$cookie_dir = dirname($cookie_dir) . '/';
		}
		return $cookie_dir;
	}

	/**
	 * Specifies the lifetime of cookies
	 * @param $ l the lifetime
	 */
	public static function keepCookie($l) {
		session_set_cookie_params($l, self::getCookieDir(), '', Base_Request::isHttps(), true);
	}


	/**
	 * Regenerate a session id.
	 * Useful for calling session_set_cookie_params after session_start ()
	 */
	public static function regenerateID() {
		session_regenerate_id(true);
	}

	public static function deleteLongTermCookie($name) {
		setcookie($name, '', 1, '', '', Base_Request::isHttps(), true);
	}

	public static function setLongTermCookie($name, $value, $expire) {
		setcookie($name, $value, $expire, '', '', Base_Request::isHttps(), true);
	}

	public static function getLongTermCookie($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

}
