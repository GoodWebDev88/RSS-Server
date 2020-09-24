<?php

class RSSServer extends Base_FrontController {
	/**
	 * Initialize the different RSSServer / Base components.
	 *
	 * PLEASE DON'T CHANGE THE ORDER OF INITIALIZATIONS UNLESS YOU KNOW WHAT
	 * YOU DO!!
	 *
	 * Here is the list of components:
	 * - Create a configuration setter and register it to system conf
	 * - Init extension manager and enable system extensions (has to be done asap)
	 * - Init authentication system
	 * - Init user configuration (need auth system)
	 * - Init RSSServer context (need user conf)
	 * - Init i18n (need context)
	 * - Init sharing system (need user conf and i18n)
	 * - Init generic styles and scripts (need user conf)
	 * - Init notifications
	 * - Enable user extensions (need all the other initializations)
	 */
	public function init() {
		if (!isset($_SESSION)) {
			Base_Session::init('RSSServer');
		}

		// Register the configuration setter for the system configuration
		$configuration_setter = new RSSServer_ConfigurationSetter();
		$system_conf = Base_Configuration::get('system');
		$system_conf->_configurationSetter($configuration_setter);

		// Load list of extensions and enable the "system" ones.
		Base_ExtensionManager::init();

		// Auth has to be initialized before using currentUser session parameter
		// because it's this part which create this parameter.
		self::initAuth();

		// Then, register the user configuration and use the configuration setter
		// created above.
		$current_user = Base_Session::param('currentUser', '_');
		Base_Configuration::register('user',
		                             join_path(USERS_PATH, $current_user, 'config.php'),
		                             join_path(RSSSERVER_PATH, 'config-user.default.php'),
		                             $configuration_setter);

		// Finish to initialize the other RSSServer / Base components.
		RSSServer_Context::init();
		self::initI18n();
		self::loadNotifications();
		// Enable extensions for the current (logged) user.
		if (RSSServer_Auth::hasAccess() || $system_conf->allow_anonymous) {
			$ext_list = RSSServer_Context::$user_conf->extensions_enabled;
			Base_ExtensionManager::enableByList($ext_list);
		}

		if ($system_conf->force_email_validation && !RSSServer_Auth::hasAccess('admin')) {
			self::checkEmailValidated();
		}

		Base_ExtensionManager::callHook('rssserver_init');
	}

	private static function initAuth() {
		RSSServer_Auth::init();
		if (Base_Request::isPost()) {
			if (!is_referer_from_same_domain()) {
				// Basic protection against XSRF attacks
				RSSServer_Auth::removeAccess();
				$http_referer = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
				Base_Translate::init('en');	//TODO: Better choice of fallback language
				Base_Error::error(403, array('error' => array(
						_t('feedback.access.denied'),
						' [HTTP_REFERER=' . htmlspecialchars($http_referer, ENT_NOQUOTES, 'UTF-8') . ']'
					)));
			}
			if (!(RSSServer_Auth::isCsrfOk() ||
				(Base_Request::controllerName() === 'auth' && Base_Request::actionName() === 'login') ||
				(Base_Request::controllerName() === 'user' && Base_Request::actionName() === 'create' &&
					!RSSServer_Auth::hasAccess('admin'))
				)) {
				// Token-based protection against XSRF attacks, except for the login or self-create user forms
				Base_Translate::init('en');	//TODO: Better choice of fallback language
				Base_Error::error(403, array('error' => array(
						_t('feedback.access.denied'),
						' [CSRF]'
					)));
			}
		}
	}

	private static function initI18n() {
		Base_Session::_param('language', RSSServer_Context::$user_conf->language);
		Base_Translate::init(RSSServer_Context::$user_conf->language);
	}

	public static function loadStylesAndScripts() {
		$theme = RSSServer_Themes::load(RSSServer_Context::$user_conf->theme);
		if ($theme) {
			foreach(array_reverse($theme['files']) as $file) {
				if ($file[0] === '_') {
					$theme_id = 'base-theme';
					$filename = substr($file, 1);
				} else {
					$theme_id = $theme['id'];
					$filename = $file;
				}
				if (_t('gen.dir') === 'rtl') {
					$filename = substr($filename, 0, -4);
					$filename = $filename . '.rtl.css';
				}
				$filetime = @filemtime(PUBLIC_PATH . '/themes/' . $theme_id . '/' . $filename);
				$url = '/themes/' . $theme_id . '/' . $filename . '?' . $filetime;
				Base_View::prependStyle(Base_Url::display($url));
			}
		}
		//Use prepend to insert before extensions. Added in reverse order.
		if (Base_Request::controllerName() !== 'index') {
			Base_View::prependScript(Base_Url::display('/scripts/extra.js?' . @filemtime(PUBLIC_PATH . '/scripts/extra.js')));
		}
		Base_View::prependScript(Base_Url::display('/scripts/main.js?' . @filemtime(PUBLIC_PATH . '/scripts/main.js')));
	}

	private static function loadNotifications() {
		$notif = Base_Session::param('notification');
		if ($notif) {
			Base_View::_param('notification', $notif);
			Base_Session::_param('notification');
		}
	}

	public static function preLayout() {
		header("X-Content-Type-Options: nosniff");

		RSSServer_Share::load(join_path(APP_PATH, 'shares.php'));
		self::loadStylesAndScripts();
	}

	private static function checkEmailValidated() {
		$email_not_verified = RSSServer_Auth::hasAccess() && RSSServer_Context::$user_conf->email_validation_token !== '';
		$action_is_allowed = (
			Base_Request::is('user', 'validateEmail') ||
			Base_Request::is('user', 'sendValidationEmail') ||
			Base_Request::is('user', 'profile') ||
			Base_Request::is('user', 'delete') ||
			Base_Request::is('auth', 'logout') ||
			Base_Request::is('feed', 'actualize') ||
			Base_Request::is('javascript', 'nonce')
		);
		if ($email_not_verified && !$action_is_allowed) {
			Base_Request::forward(array(
				'c' => 'user',
				'a' => 'validateEmail',
			), true);
		}
	}
}
