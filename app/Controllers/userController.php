<?php

/**
 * Controller to handle user actions.
 */
class RSSServer_user_Controller extends Base_ActionController {
	/**
	 * The username is also used as folder name, file name, and part of SQL table name.
	 * '_' is a reserved internal username.
	 */
	const USERNAME_PATTERN = '([0-9a-zA-Z_][0-9a-zA-Z_.@-]{1,38}|[0-9a-zA-Z])';

	public static function checkUsername($username) {
		return preg_match('/^' . self::USERNAME_PATTERN . '$/', $username) === 1;
	}

	public static function updateUser($user, $email, $passwordPlain, $userConfigUpdated = array()) {
		$userConfig = get_user_configuration($user);
		if ($userConfig === null) {
			return false;
		}

		if ($email !== null && $userConfig->mail_login !== $email) {
			$userConfig->mail_login = $email;

			if (RSSServer_Context::$system_conf->force_email_validation) {
				$salt = RSSServer_Context::$system_conf->salt;
				$userConfig->email_validation_token = sha1($salt . uniqid(mt_rand(), true));
				$mailer = new RSSServer_User_Mailer();
				$mailer->send_email_need_validation($user, $userConfig);
			}
		}

		if ($passwordPlain != '') {
			$passwordHash = RSSServer_password_Util::hash($passwordPlain);
			$userConfig->passwordHash = $passwordHash;
		}

		if (is_array($userConfigUpdated)) {
			foreach ($userConfigUpdated as $configName => $configValue) {
				if ($configValue !== null) {
					$userConfig->_param($configName, $configValue);
				}
			}
		}

		$ok = $userConfig->save();
		return $ok;
	}

	public function updateAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		if (Base_Request::isPost()) {
			$passwordPlain = Base_Request::param('newPasswordPlain', '', true);
			Base_Request::_param('newPasswordPlain');	//Discard plain-text password ASAP
			$_POST['newPasswordPlain'] = '';

			$username = Base_Request::param('username');
			$ok = self::updateUser($username, null, $passwordPlain, array(
				'token' => Base_Request::param('token', null),
			));

			if ($ok) {
				$isSelfUpdate = Base_Session::param('currentUser', '_') === $username;
				if ($passwordPlain == '' || !$isSelfUpdate) {
					Base_Request::good(_t('feedback.user.updated', $username), array('c' => 'user', 'a' => 'manage'));
				} else {
					Base_Request::good(_t('feedback.profile.updated'), array('c' => 'index', 'a' => 'index'));
				}
			} else {
				Base_Request::bad(_t('feedback.user.updated.error', $username),
				                  array('c' => 'user', 'a' => 'manage'));
			}
		}
	}

	/**
	 * This action displays the user profile page.
	 */
	public function profileAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		$email_not_verified = RSSServer_Context::$user_conf->email_validation_token != '';
		$this->view->disable_aside = false;
		if ($email_not_verified) {
			$this->view->_layout('simple');
			$this->view->disable_aside = true;
		}

		Base_View::prependTitle(_t('conf.profile.title') . ' · ');

		Base_View::appendScript(Base_Url::display('/scripts/bcrypt.min.js?' . @filemtime(PUBLIC_PATH . '/scripts/bcrypt.min.js')));

		if (Base_Request::isPost()) {
			$system_conf = RSSServer_Context::$system_conf;
			$user_config = RSSServer_Context::$user_conf;
			$old_email = $user_config->mail_login;

			$email = trim(Base_Request::param('email', ''));
			$passwordPlain = Base_Request::param('newPasswordPlain', '', true);
			Base_Request::_param('newPasswordPlain');	//Discard plain-text password ASAP
			$_POST['newPasswordPlain'] = '';

			if ($system_conf->force_email_validation && empty($email)) {
				Base_Request::bad(
					_t('user.email.feedback.required'),
					array('c' => 'user', 'a' => 'profile')
				);
			}

			if (!empty($email) && !validateEmailAddress($email)) {
				Base_Request::bad(
					_t('user.email.feedback.invalid'),
					array('c' => 'user', 'a' => 'profile')
				);
			}

			$ok = self::updateUser(
				Base_Session::param('currentUser'),
				$email,
				$passwordPlain,
				array(
					'token' => Base_Request::param('token', null),
				)
			);

			Base_Session::_param('passwordHash', RSSServer_Context::$user_conf->passwordHash);

			if ($ok) {
				if ($system_conf->force_email_validation && $email !== $old_email) {
					Base_Request::good(_t('feedback.profile.updated'), array('c' => 'user', 'a' => 'validateEmail'));
				} elseif ($passwordPlain == '') {
					Base_Request::good(_t('feedback.profile.updated'), array('c' => 'user', 'a' => 'profile'));
				} else {
					Base_Request::good(_t('feedback.profile.updated'), array('c' => 'index', 'a' => 'index'));
				}
			} else {
				Base_Request::bad(_t('feedback.profile.error'),
				                  array('c' => 'user', 'a' => 'profile'));
			}
		}
	}

	public function purgeAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		if (Base_Request::isPost()) {
			$username = Base_Request::param('username');

			if (!RSSServer_UserDAO::exists($username)) {
				Base_Error::error(404);
			}

			$feedDAO = RSSServer_Factory::createFeedDao($username);
			$feedDAO->purge();
		}
	}

	/**
	 * This action displays the user management page.
	 */
	public function manageAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		Base_View::prependTitle(_t('admin.user.title') . ' · ');

		if (Base_Request::isPost()) {
			$action = Base_Request::param('action');
			switch ($action) {
				case 'delete':
					$this->deleteAction();
					break;
				case 'update':
					$this->updateAction();
					break;
				case 'purge':
					$this->purgeAction();
					break;
				case 'promote':
					$this->promoteAction();
					break;
				case 'demote':
					$this->demoteAction();
					break;
			}
		}

		$this->view->show_email_field = RSSServer_Context::$system_conf->force_email_validation;
		$this->view->current_user = Base_Request::param('u');

		foreach (listUsers() as $user) {
			$this->view->users[$user] = $this->retrieveUserDetails($user);
		}
	}

	public static function createUser($new_user_name, $email, $passwordPlain, $userConfigOverride = [], $insertDefaultFeeds = true) {
		$userConfig = [];

		$customUserConfigPath = join_path(DATA_PATH, 'config-user.custom.php');
		if (file_exists($customUserConfigPath)) {
			$customUserConfig = include($customUserConfigPath);
			if (is_array($customUserConfig)) {
				$userConfig = $customUserConfig;
			}
		}

		if (is_array($userConfigOverride)) {
			$userConfig = array_merge($userConfig, $userConfigOverride);
		}

		$ok = self::checkUsername($new_user_name);
		$homeDir = join_path(DATA_PATH, 'users', $new_user_name);

		if ($ok) {
			$languages = Base_Translate::availableLanguages();
			if (empty($userConfig['language']) || !in_array($userConfig['language'], $languages)) {
				$userConfig['language'] = 'en';
			}

			$ok &= !in_array(strtoupper($new_user_name), array_map('strtoupper', listUsers()));	//Not an existing user, case-insensitive

			$configPath = join_path($homeDir, 'config.php');
			$ok &= !file_exists($configPath);
		}
		if ($ok) {
			if (!is_dir($homeDir)) {
				mkdir($homeDir);
			}
			$ok &= (file_put_contents($configPath, "<?php\n return " . var_export($userConfig, true) . ';') !== false);
		}
		if ($ok) {
			$newUserDAO = RSSServer_Factory::createUserDao($new_user_name);
			$ok &= $newUserDAO->createUser();

			if ($ok && $insertDefaultFeeds) {
				$opmlPath = DATA_PATH . '/opml.xml';
				if (!file_exists($opmlPath)) {
					$opmlPath = RSSSERVER_PATH . '/opml.default.xml';
				}
				$importController = new RSSServer_importExport_Controller();
				try {
					$importController->importFile($opmlPath, $opmlPath, $new_user_name);
				} catch (Exception $e) {
					Base_Log::error('Error while importing default OPML for user ' . $new_user_name . ': ' . $e->getMessage());
				}
			}

			$ok &= self::updateUser($new_user_name, $email, $passwordPlain);
		}
		return $ok;
	}

	/**
	 * This action creates a new user.
	 *
	 * Request parameters are:
	 *   - new_user_language
	 *   - new_user_name
	 *   - new_user_email
	 *   - new_user_passwordPlain
	 *   - r (i.e. a redirection url, optional)
	 *
	 * @todo clean up this method. Idea: write a method to init a user with basic information.
	 * @todo handle r redirection in Base_Request::forward directly?
	 */
	public function createAction() {
		if (!RSSServer_Auth::hasAccess('admin') && max_registrations_reached()) {
			Base_Error::error(403);
		}

		if (Base_Request::isPost()) {
			$system_conf = RSSServer_Context::$system_conf;

			$new_user_name = Base_Request::param('new_user_name');
			$email = Base_Request::param('new_user_email', '');
			$passwordPlain = Base_Request::param('new_user_passwordPlain', '', true);

			if (!self::checkUsername($new_user_name)) {
				Base_Request::bad(
					_t('user.username.invalid'),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			if (RSSServer_UserDAO::exists($new_user_name)) {
				Base_Request::bad(
					_t('user.username.taken', $new_user_name),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			if (!RSSServer_password_Util::check($passwordPlain)) {
				Base_Request::bad(
					_t('user.password.invalid'),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			$tos_enabled = file_exists(join_path(DATA_PATH, 'tos.html'));
			$accept_tos = Base_Request::param('accept_tos', false);

			if ($system_conf->force_email_validation && empty($email)) {
				Base_Request::bad(
					_t('user.email.feedback.required'),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			if (!empty($email) && !validateEmailAddress($email)) {
				Base_Request::bad(
					_t('user.email.feedback.invalid'),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			if ($tos_enabled && !$accept_tos) {
				Base_Request::bad(
					_t('user.tos.feedback.invalid'),
					array('c' => 'auth', 'a' => 'register')
				);
			}

			$ok = self::createUser($new_user_name, $email, $passwordPlain, array(
				'language' => Base_Request::param('new_user_language', RSSServer_Context::$user_conf->language),
				'is_admin' => Base_Request::paramBoolean('new_user_is_admin'),
			));
			Base_Request::_param('new_user_passwordPlain');	//Discard plain-text password ASAP
			$_POST['new_user_passwordPlain'] = '';
			invalidateHttpCache();

			// If the user has admin access, it means he's already logged in
			// and we don't want to login with the new account. Otherwise, the
			// user just created its account himself so he probably wants to
			// get started immediately.
			if ($ok && !RSSServer_Auth::hasAccess('admin')) {
				$user_conf = get_user_configuration($new_user_name);
				Base_Session::_param('currentUser', $new_user_name);
				Base_Session::_param('passwordHash', $user_conf->passwordHash);
				Base_Session::_param('csrf');
				RSSServer_Auth::giveAccess();
			}

			$notif = array(
				'type' => $ok ? 'good' : 'bad',
				'content' => _t('feedback.user.created' . (!$ok ? '.error' : ''), $new_user_name)
			);
			Base_Session::_param('notification', $notif);
		}

		$redirect_url = urldecode(Base_Request::param('r', false, true));
		if (!$redirect_url) {
			$redirect_url = array('c' => 'user', 'a' => 'manage');
		}
		Base_Request::forward($redirect_url, true);
	}

	public static function deleteUser($username) {
		$ok = self::checkUsername($username);
		if ($ok) {
			$default_user = RSSServer_Context::$system_conf->default_user;
			$ok &= (strcasecmp($username, $default_user) !== 0);	//It is forbidden to delete the default user
		}
		$user_data = join_path(DATA_PATH, 'users', $username);
		$ok &= is_dir($user_data);
		if ($ok) {
			RSSServer_fever_Util::deleteKey($username);
			$oldUserDAO = RSSServer_Factory::createUserDao($username);
			$ok &= $oldUserDAO->deleteUser();
			$ok &= recursive_unlink($user_data);
			array_map('unlink', glob(PSHB_PATH . '/feeds/*/' . $username . '.txt'));
		}
		return $ok;
	}

	/**
	 * This action validates an email address, based on the token sent by email.
	 * It also serves the main page when user is blocked.
	 *
	 * Request parameters are:
	 *   - username
	 *   - token
	 *
	 * This route works with GET requests since the URL is provided by email.
	 * The security risks (e.g. forged URL by an attacker) are not very high so
	 * it's ok.
	 *
	 * It returns 404 error if `force_email_validation` is disabled or if the
	 * user doesn't exist.
	 *
	 * It returns 403 if user isn't logged in and `username` param isn't passed.
	 */
	public function validateEmailAction() {
		if (!RSSServer_Context::$system_conf->force_email_validation) {
			Base_Error::error(404);
		}

		Base_View::prependTitle(_t('user.email.validation.title') . ' · ');
		$this->view->_layout('simple');

		$username = Base_Request::param('username');
		$token = Base_Request::param('token');

		if ($username) {
			$user_config = get_user_configuration($username);
		} elseif (RSSServer_Auth::hasAccess()) {
			$user_config = RSSServer_Context::$user_conf;
		} else {
			Base_Error::error(403);
		}

		if (!RSSServer_UserDAO::exists($username) || $user_config === null) {
			Base_Error::error(404);
		}

		if ($user_config->email_validation_token === '') {
			Base_Request::good(
				_t('user.email.validation.feedback.unnecessary'),
				array('c' => 'index', 'a' => 'index')
			);
		}

		if ($token) {
			if ($user_config->email_validation_token !== $token) {
				Base_Request::bad(
					_t('user.email.validation.feedback.wrong_token'),
					array('c' => 'user', 'a' => 'validateEmail')
				);
			}

			$user_config->email_validation_token = '';
			if ($user_config->save()) {
				Base_Request::good(
					_t('user.email.validation.feedback.ok'),
					array('c' => 'index', 'a' => 'index')
				);
			} else {
				Base_Request::bad(
					_t('user.email.validation.feedback.error'),
					array('c' => 'user', 'a' => 'validateEmail')
				);
			}
		}
	}

	/**
	 * This action resends a validation email to the current user.
	 *
	 * It only acts on POST requests but doesn't require any param (except the
	 * CSRF token).
	 *
	 * It returns 403 error if the user is not logged in or 404 if request is
	 * not POST. Else it redirects silently to the index if user has already
	 * validated its email, or to the user#validateEmail route.
	 */
	public function sendValidationEmailAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		if (!Base_Request::isPost()) {
			Base_Error::error(404);
		}

		$username = Base_Session::param('currentUser', '_');
		$user_config = RSSServer_Context::$user_conf;

		if ($user_config->email_validation_token === '') {
			Base_Request::forward(array(
				'c' => 'index',
				'a' => 'index',
			), true);
		}

		$mailer = new RSSServer_User_Mailer();
		$ok = $mailer->send_email_need_validation($username, $user_config);

		$redirect_url = array('c' => 'user', 'a' => 'validateEmail');
		if ($ok) {
			Base_Request::good(
				_t('user.email.validation.feedback.email_sent'),
				$redirect_url
			);
		} else {
			Base_Request::bad(
				_t('user.email.validation.feedback.email_failed'),
				$redirect_url
			);
		}
	}

	/**
	 * This action delete an existing user.
	 *
	 * Request parameter is:
	 *   - username
	 *
	 * @todo clean up this method. Idea: create a User->clean() method.
	 */
	public function deleteAction() {
		$username = Base_Request::param('username');
		$self_deletion = Base_Session::param('currentUser', '_') === $username;

		if (!RSSServer_Auth::hasAccess('admin') && !$self_deletion) {
			Base_Error::error(403);
		}

		$redirect_url = urldecode(Base_Request::param('r', false, true));
		if (!$redirect_url) {
			$redirect_url = array('c' => 'user', 'a' => 'manage');
		}

		if (Base_Request::isPost()) {
			$ok = true;
			if ($ok && $self_deletion) {
				// We check the password if it's a self-destruction
				$nonce = Base_Session::param('nonce');
				$challenge = Base_Request::param('challenge', '');

				$ok &= RSSServer_FormAuth::checkCredentials(
					$username, RSSServer_Context::$user_conf->passwordHash,
					$nonce, $challenge
				);
			}
			if ($ok) {
				$ok &= self::deleteUser($username);
			}
			if ($ok && $self_deletion) {
				RSSServer_Auth::removeAccess();
				$redirect_url = array('c' => 'index', 'a' => 'index');
			}
			invalidateHttpCache();

			$notif = array(
				'type' => $ok ? 'good' : 'bad',
				'content' => _t('feedback.user.deleted' . (!$ok ? '.error' : ''), $username)
			);
			Base_Session::_param('notification', $notif);
		}

		Base_Request::forward($redirect_url, true);
	}

	public function promoteAction() {
		$this->switchAdminAction(true);
	}

	public function demoteAction() {
		$this->switchAdminAction(false);
	}

	private function switchAdminAction($isAdmin) {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		if (!Base_Request::isPost()) {
			Base_Error::error(403);
		}

		$username = Base_Request::param('username');
		if (!RSSServer_UserDAO::exists($username)) {
			Base_Error::error(404);
		}

		if (null === $userConfig = get_user_configuration($username)) {
			Base_Error::error(500);
		}

		$userConfig->_param('is_admin', $isAdmin);

		$ok = $userConfig->save();

		if ($ok) {
			Base_Request::good(_t('feedback.user.updated', $username), array('c' => 'user', 'a' => 'manage'));
		} else {
			Base_Request::bad(_t('feedback.user.updated.error', $username),
							  array('c' => 'user', 'a' => 'manage'));
		}
	}

	public function detailsAction() {
		if (!RSSServer_Auth::hasAccess('admin')) {
			Base_Error::error(403);
		}

		$username = Base_Request::param('username');
		if (!RSSServer_UserDAO::exists($username)) {
			Base_Error::error(404);
		}

		$this->view->isDefaultUser = $username === RSSServer_Context::$system_conf->default_user;
		$this->view->username = $username;
		$this->view->details = $this->retrieveUserDetails($username);
	}

	private function retrieveUserDetails($username) {
		$feedDAO = RSSServer_Factory::createFeedDao($username);
		$entryDAO = RSSServer_Factory::createEntryDao($username);
		$databaseDAO = RSSServer_Factory::createDatabaseDAO($username);

		$userConfiguration = get_user_configuration($username);

		return array(
			'feed_count' => $feedDAO->count(),
			'article_count' => $entryDAO->count(),
			'database_size' => $databaseDAO->size(),
			'language' => $userConfiguration->language,
			'mail_login' => $userConfiguration->mail_login,
			'is_admin' => $userConfiguration->is_admin,
			'last_user_activity' => date('c', RSSServer_UserDAO::mtime($username)),
		);
	}
}
