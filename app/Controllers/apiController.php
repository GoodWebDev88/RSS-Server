<?php

/**
 * This controller manage API-related features.
 */
class RSSServer_api_Controller extends Base_ActionController {
	/**
	 * This action updates the user API password.
	 *
	 * Parameter is:
	 * - apiPasswordPlain: the new user password
	 */
	public function updatePasswordAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		$return_url = array('c' => 'user', 'a' => 'profile');

		if (!Base_Request::isPost()) {
			Base_Request::forward($return_url, true);
		}

		$apiPasswordPlain = Base_Request::param('apiPasswordPlain', '', true);
		if ($apiPasswordPlain == '') {
			Base_Request::forward($return_url, true);
		}

		$username = Base_Session::param('currentUser');
		$userConfig = RSSServer_Context::$user_conf;

		$apiPasswordHash = RSSServer_password_Util::hash($apiPasswordPlain);
		$userConfig->apiPasswordHash = $apiPasswordHash;

		$feverKey = RSSServer_fever_Util::updateKey($username, $apiPasswordPlain);
		if (!$feverKey) {
			Base_Request::bad(_t('feedback.api.password.failed'), $return_url);
		}

		$userConfig->feverKey = $feverKey;
		if ($userConfig->save()) {
			Base_Request::good(_t('feedback.api.password.updated'), $return_url);
		} else {
			Base_Request::bad(_t('feedback.api.password.failed'), $return_url);
		}
	}
}
