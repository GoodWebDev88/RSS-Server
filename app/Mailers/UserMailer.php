<?php

/**
 * Manage the emails sent to the users.
 */
class RSSServer_User_Mailer extends Base_Mailer {
	public function send_email_need_validation($username, $user_config) {
		$this->view->_path('user_mailer/email_need_validation.txt');

		$this->view->username = $username;
		$this->view->site_title = RSSServer_Context::$system_conf->title;
		$this->view->validation_url = Base_Url::display(
			array(
				'c' => 'user',
				'a' => 'validateEmail',
				'params' => array(
					'username' => $username,
					'token' => $user_config->email_validation_token
				)
			),
			'txt',
			true
		);

		$subject_prefix = '[' . RSSServer_Context::$system_conf->title . ']';
		return $this->mail(
			$user_config->mail_login,
			$subject_prefix . ' ' ._t('user.mailer.email_need_validation.title')
		);
	}
}
