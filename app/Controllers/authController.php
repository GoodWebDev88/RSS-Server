<?php

/**
 * This controller handles action about authentication.
 */
class RSSServer_auth_Controller extends Base_ActionController {
	/**
	 * This action handles authentication management page.
	 *
	 * Parameters are:
	 *   - token (default: current token)
	 *   - anon_access (default: false)
	 *   - anon_refresh (default: false)
	 *   - auth_type (default: none)
	 *   - unsafe_autologin (default: false)
	 *   - api_enabled (default: false)
	 *
	 * @todo move unsafe_autologin in an extension.
	 */
	public function indexAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		Base_View::prependTitle(_t('admin.auth.title') . ' · ');

		if (Base_Request::isPost()) {
			$ok = true;

			$anon = Base_Request::param('anon_access', false);
			$anon = ((bool)$anon) && ($anon !== 'no');
			$anon_refresh = Base_Request::param('anon_refresh', false);
			$anon_refresh = ((bool)$anon_refresh) && ($anon_refresh !== 'no');
			$auth_type = Base_Request::param('auth_type', 'none');
			$unsafe_autologin = Base_Request::param('unsafe_autologin', false);
			$api_enabled = Base_Request::param('api_enabled', false);
			if ($anon != RSSServer_Context::$system_conf->allow_anonymous ||
				$auth_type != RSSServer_Context::$system_conf->auth_type ||
				$anon_refresh != RSSServer_Context::$system_conf->allow_anonymous_refresh ||
				$unsafe_autologin != RSSServer_Context::$system_conf->unsafe_autologin_enabled ||
				$api_enabled != RSSServer_Context::$system_conf->api_enabled) {

				// TODO: test values from form
				RSSServer_Context::$system_conf->auth_type = $auth_type;
				RSSServer_Context::$system_conf->allow_anonymous = $anon;
				RSSServer_Context::$system_conf->allow_anonymous_refresh = $anon_refresh;
				RSSServer_Context::$system_conf->unsafe_autologin_enabled = $unsafe_autologin;
				RSSServer_Context::$system_conf->api_enabled = $api_enabled;

				$ok &= RSSServer_Context::$system_conf->save();
			}

			invalidateHttpCache();

			if ($ok) {
				Base_Request::good(_t('feedback.conf.updated'),
				                   array('c' => 'auth', 'a' => 'index'));
			} else {
				Base_Request::bad(_t('feedback.conf.error'),
				                  array('c' => 'auth', 'a' => 'index'));
			}
		}
	}

	/**
	 * This action handles the login page.
	 *
	 * It forwards to the correct login page (form) or main page if
	 * the user is already connected.
	 */
	public function loginAction() {
		if (RSSServer_Auth::hasAccess() && Base_Request::param('u', '') == '') {
			Base_Request::forward(array('c' => 'index', 'a' => 'index'), true);
		}

		$auth_type = RSSServer_Context::$system_conf->auth_type;
		switch ($auth_type) {
		case 'form':
			Base_Request::forward(array('c' => 'auth', 'a' => 'formLogin'));
			break;
		case 'http_auth':
			Base_Error::error(403, array('error' => array(_t('feedback.access.denied'),
					' [HTTP Remote-User=' . htmlspecialchars(httpAuthUser(), ENT_NOQUOTES, 'UTF-8') . ']'
				)), false);
			break;
		case 'none':
			// It should not happen!
			Base_Error::error(404);
		default:
			// TODO load plugin instead
			Base_Error::error(404);
		}
	}

	/**
	 * This action handles form login page.
	 *
	 * If this action is reached through a POST request, username and password
	 * are compared to login the current user.
	 *
	 * Parameters are:
	 *   - nonce (default: false)
	 *   - username (default: '')
	 *   - challenge (default: '')
	 *   - keep_logged_in (default: false)
	 *
	 * @todo move unsafe autologin in an extension.
	 */
	public function formLoginAction() {
		invalidateHttpCache();

		Base_View::prependTitle(_t('gen.auth.login') . ' · ');
		Base_View::appendScript(Base_Url::display('/scripts/bcrypt.min.js?' . @filemtime(PUBLIC_PATH . '/scripts/bcrypt.min.js')));

		$conf = Base_Configuration::get('system');
		$limits = $conf->limits;
		$this->view->cookie_days = round($limits['cookie_duration'] / 86400, 1);

		$isPOST = Base_Request::isPost() && !Base_Session::param('POST_to_GET');
		Base_Session::_param('POST_to_GET');

		if ($isPOST) {
			$nonce = Base_Session::param('nonce');
			$username = Base_Request::param('username', '');
			$challenge = Base_Request::param('challenge', '');

			$conf = get_user_configuration($username);
			if ($conf == null) {
				//We do not test here whether the user exists, so most likely an internal error.
				Base_Error::error(403, array(_t('feedback.auth.login.invalid')), false);
				return;
			}

			$ok = RSSServer_FormAuth::checkCredentials(
				$username, $conf->passwordHash, $nonce, $challenge
			);
			if ($ok) {
				// Set session parameter to give access to the user.
				Base_Session::_param('currentUser', $username);
				Base_Session::_param('passwordHash', $conf->passwordHash);
				Base_Session::_param('csrf');
				RSSServer_Auth::giveAccess();

				// Set cookie parameter if nedded.
				if (Base_Request::param('keep_logged_in')) {
					RSSServer_FormAuth::makeCookie($username, $conf->passwordHash);
				} else {
					RSSServer_FormAuth::deleteCookie();
				}

				// All is good, go back to the index.
				Base_Request::good(_t('feedback.auth.login.success'),
				                   array('c' => 'index', 'a' => 'index'));
			} else {
				Base_Log::warning('Password mismatch for' .
				                  ' user=' . $username .
				                  ', nonce=' . $nonce .
				                  ', c=' . $challenge);

				header('HTTP/1.1 403 Forbidden');
				Base_Session::_param('POST_to_GET', true);	//Prevent infinite internal redirect
				Base_View::_param('notification', [
					'type' => 'bad',
					'content' => _t('feedback.auth.login.invalid'),
				]);
				Base_Request::forward(['c' => 'auth', 'a' => 'login'], false);
				return;
			}
		} elseif (RSSServer_Context::$system_conf->unsafe_autologin_enabled) {
			$username = Base_Request::param('u', '');
			$password = Base_Request::param('p', '');
			Base_Request::_param('p');

			if (!$username) {
				return;
			}

			RSSServer_FormAuth::deleteCookie();

			$conf = get_user_configuration($username);
			if ($conf == null) {
				return;
			}

			$s = $conf->passwordHash;
			$ok = password_verify($password, $s);
			unset($password);
			if ($ok) {
				Base_Session::_param('currentUser', $username);
				Base_Session::_param('passwordHash', $s);
				Base_Session::_param('csrf');
				RSSServer_Auth::giveAccess();

				Base_Request::good(_t('feedback.auth.login.success'),
				                   array('c' => 'index', 'a' => 'index'));
			} else {
				Base_Log::warning('Unsafe password mismatch for user ' . $username);
				Base_Request::bad(
					_t('feedback.auth.login.invalid'),
					array('c' => 'auth', 'a' => 'login')
				);
			}
		}
	}

	/**
	 * This action removes all accesses of the current user.
	 */
	public function logoutAction() {
		invalidateHttpCache();
		RSSServer_Auth::removeAccess();
		Base_Request::good(_t('feedback.auth.logout.success'),
		                   array('c' => 'index', 'a' => 'index'));
	}

	/**
	 * This action gives possibility to a user to create an account.
	 *
	 * The user is redirected to the home if he's connected.
	 *
	 * A 403 is sent if max number of registrations is reached.
	 */
	public function registerAction() {
		if (RSSServer_Auth::hasAccess()) {
			Base_Request::forward(array('c' => 'index', 'a' => 'index'), true);
		}

		if (max_registrations_reached()) {
			Base_Error::error(403);
		}

		$this->view->show_tos_checkbox = file_exists(join_path(DATA_PATH, 'tos.html'));
		$this->view->show_email_field = RSSServer_Context::$system_conf->force_email_validation;
		Base_View::prependTitle(_t('gen.auth.registration.title') . ' · ');
	}
}
