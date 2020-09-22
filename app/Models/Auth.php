<?php

/**
 * This class handles all authentication process.
 */
class RSSServer_Auth {
	/**
	 * Determines if user is connected.
	 */
	private static $login_ok = false;

	/**
	 * This method initializes authentication system.
	 */
	public static function init() {
		if (isset($_SESSION['REMOTE_USER']) && $_SESSION['REMOTE_USER'] !== httpAuthUser()) {
			//HTTP REMOTE_USER has changed
			self::removeAccess();
		}

		self::$login_ok = Minz_Session::param('loginOk', false);
		$current_user = Minz_Session::param('currentUser', '');
		if ($current_user === '') {
			$conf = Minz_Configuration::get('system');
			$current_user = $conf->default_user;
			Minz_Session::_param('currentUser', $current_user);
			Minz_Session::_param('csrf');
		}

		if (self::$login_ok) {
			self::giveAccess();
		} elseif (self::accessControl() && self::giveAccess()) {
			RSSServer_UserDAO::touch();
		} else {
			// Be sure all accesses are removed!
			self::removeAccess();
		}
		return self::$login_ok;
	}

	/**
	 * This method checks if user is allowed to connect.
	 *
	 * Required session parameters are also set in this method (such as
	 * currentUser).
	 *
	 * @return boolean true if user can be connected, false else.
	 */
	private static function accessControl() {
		$conf = Minz_Configuration::get('system');
		$auth_type = $conf->auth_type;
		switch ($auth_type) {
		case 'form':
			$credentials = RSSServer_FormAuth::getCredentialsFromCookie();
			$current_user = '';
			if (isset($credentials[1])) {
				$current_user = trim($credentials[0]);
				Minz_Session::_param('currentUser', $current_user);
				Minz_Session::_param('passwordHash', trim($credentials[1]));
				Minz_Session::_param('csrf');
			}
			return $current_user != '';
		case 'http_auth':
			$current_user = httpAuthUser();
			$login_ok = $current_user != '' && RSSServer_UserDAO::exists($current_user);
			if ($login_ok) {
				Minz_Session::_param('currentUser', $current_user);
				Minz_Session::_param('csrf');
			}
			return $login_ok;
		case 'none':
			return true;
		default:
			// TODO load extension
			return false;
		}
	}

	/**
	 * Gives access to the current user.
	 */
	public static function giveAccess() {
		$current_user = Minz_Session::param('currentUser');
		$user_conf = get_user_configuration($current_user);
		if ($user_conf == null) {
			self::$login_ok = false;
			return false;
		}
		$system_conf = Minz_Configuration::get('system');

		switch ($system_conf->auth_type) {
		case 'form':
			self::$login_ok = Minz_Session::param('passwordHash') === $user_conf->passwordHash;
			break;
		case 'http_auth':
			self::$login_ok = strcasecmp($current_user, httpAuthUser()) === 0;
			break;
		case 'none':
			self::$login_ok = true;
			break;
		default:
			// TODO: extensions
			self::$login_ok = false;
		}

		Minz_Session::_param('loginOk', self::$login_ok);
		Minz_Session::_param('REMOTE_USER', httpAuthUser());
		return self::$login_ok;
	}

	/**
	 * Returns if current user has access to the given scope.
	 *
	 * @param string $scope general (default) or admin
	 * @return boolean true if user has corresponding access, false else.
	 */
	public static function hasAccess($scope = 'general') {
		$systemConfiguration = Minz_Configuration::get('system');
		$currentUser = Minz_Session::param('currentUser');
		$userConfiguration = get_user_configuration($currentUser);
		$isAdmin = $userConfiguration && $userConfiguration->is_admin;
		$default_user = $systemConfiguration->default_user;
		$ok = self::$login_ok;
		switch ($scope) {
		case 'general':
			break;
		case 'admin':
			$ok &= $default_user === $currentUser || $isAdmin;
			break;
		default:
			$ok = false;
		}
		return $ok;
	}

	/**
	 * Removes all accesses for the current user.
	 */
	public static function removeAccess() {
		self::$login_ok = false;
		Minz_Session::_param('loginOk');
		Minz_Session::_param('csrf');
		Minz_Session::_param('REMOTE_USER');
		$system_conf = Minz_Configuration::get('system');

		$username = '';
		$token_param = Minz_Request::param('token', '');
		if ($token_param != '') {
			$username = trim(Minz_Request::param('user', ''));
			if ($username != '') {
				$conf = get_user_configuration($username);
				if ($conf == null) {
					$username = '';
				}
			}
		}
		if ($username == '') {
			$username = $system_conf->default_user;
		}
		Minz_Session::_param('currentUser', $username);

		switch ($system_conf->auth_type) {
		case 'form':
			Minz_Session::_param('passwordHash');
			RSSServer_FormAuth::deleteCookie();
			break;
		case 'http_auth':
		case 'none':
			// Nothing to do...
			break;
		default:
			// TODO: extensions
		}
	}

	/**
	 * Return if authentication is enabled on this instance of FRSS.
	 */
	public static function accessNeedsLogin() {
		$conf = Minz_Configuration::get('system');
		$auth_type = $conf->auth_type;
		return $auth_type !== 'none';
	}

	/**
	 * Return if authentication requires a PHP action.
	 */
	public static function accessNeedsAction() {
		$conf = Minz_Configuration::get('system');
		$auth_type = $conf->auth_type;
		return $auth_type === 'form';
	}

	public static function csrfToken() {
		$csrf = Minz_Session::param('csrf');
		if ($csrf == '') {
			$salt = RSSServer_Context::$system_conf->salt;
			$csrf = sha1($salt . uniqid(mt_rand(), true));
			Minz_Session::_param('csrf', $csrf);
		}
		return $csrf;
	}
	public static function isCsrfOk($token = null) {
		$csrf = Minz_Session::param('csrf');
		if ($token === null) {
			$token = Minz_Request::fetchPOST('_csrf');
		}
		return $token != '' && $token === $csrf;
	}
}


class RSSServer_FormAuth {
	public static function checkCredentials($username, $hash, $nonce, $challenge) {
		if (!RSSServer_user_Controller::checkUsername($username) ||
				!ctype_graph($hash) ||
				!ctype_graph($challenge) ||
				!ctype_alnum($nonce)) {
			Minz_Log::debug('Invalid credential parameters:' .
			                ' user=' . $username .
			                ' challenge=' . $challenge .
			                ' nonce=' . $nonce);
			return false;
		}

		return password_verify($nonce . $hash, $challenge);
	}

	public static function getCredentialsFromCookie() {
		$token = Minz_Session::getLongTermCookie('RSSServer_login');
		if (!ctype_alnum($token)) {
			return array();
		}

		$token_file = DATA_PATH . '/tokens/' . $token . '.txt';
		$mtime = @filemtime($token_file);
		$conf = Minz_Configuration::get('system');
		$limits = $conf->limits;
		$cookie_duration = empty($limits['cookie_duration']) ? 2592000 : $limits['cookie_duration'];
		if ($mtime + $cookie_duration < time()) {
			// Token has expired (> cookie_duration) or does not exist.
			@unlink($token_file);
			return array();
		}

		$credentials = @file_get_contents($token_file);
		return $credentials === false ? array() : explode("\t", $credentials, 2);
	}

	public static function makeCookie($username, $password_hash) {
		$conf = Minz_Configuration::get('system');
		do {
			$token = sha1($conf->salt . $username . uniqid(mt_rand(), true));
			$token_file = DATA_PATH . '/tokens/' . $token . '.txt';
		} while (file_exists($token_file));

		if (@file_put_contents($token_file, $username . "\t" . $password_hash) === false) {
			return false;
		}

		$limits = $conf->limits;
		$cookie_duration = empty($limits['cookie_duration']) ? 2592000 : $limits['cookie_duration'];
		$expire = time() + $cookie_duration;
		Minz_Session::setLongTermCookie('RSSServer_login', $token, $expire);
		return $token;
	}

	public static function deleteCookie() {
		$token = Minz_Session::getLongTermCookie('RSSServer_login');
		if (ctype_alnum($token)) {
			Minz_Session::deleteLongTermCookie('RSSServer_login');
			@unlink(DATA_PATH . '/tokens/' . $token . '.txt');
		}

		if (rand(0, 10) === 1) {
			self::purgeTokens();
		}
	}

	public static function purgeTokens() {
		$conf = Minz_Configuration::get('system');
		$limits = $conf->limits;
		$cookie_duration = empty($limits['cookie_duration']) ? 2592000 : $limits['cookie_duration'];
		$oldest = time() - $cookie_duration;
		foreach (new DirectoryIterator(DATA_PATH . '/tokens/') as $file_info) {
			$extension = $file_info->getExtension();
			if ($extension === 'txt' && $file_info->getMTime() < $oldest) {
				@unlink($file_info->getPathname());
			}
		}
	}
}
