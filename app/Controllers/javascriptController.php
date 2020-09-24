<?php

class RSSServer_javascript_Controller extends Base_ActionController {
	public function firstAction() {
		$this->view->_layout(false);
	}

	public function actualizeAction() {
		header('Content-Type: application/json; charset=UTF-8');
		Base_Session::_param('actualize_feeds', false);
		$feedDAO = RSSServer_Factory::createFeedDao();
		$this->view->feeds = $feedDAO->listFeedsOrderUpdate(RSSServer_Context::$user_conf->ttl_default);
	}

	public function nbUnreadsPerFeedAction() {
		header('Content-Type: application/json; charset=UTF-8');
		$catDAO = RSSServer_Factory::createCategoryDao();
		$this->view->categories = $catDAO->listCategories(true, false);
		$tagDAO = RSSServer_Factory::createTagDao();
		$this->view->tags = $tagDAO->listTags(true);
	}

	//For Web-form login
	public function nonceAction() {
		header('Content-Type: application/json; charset=UTF-8');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T'));
		header('Expires: 0');
		header('Cache-Control: private, no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');

		$user = isset($_GET['user']) ? $_GET['user'] : '';
		if (RSSServer_user_Controller::checkUsername($user)) {
			try {
				$salt = RSSServer_Context::$system_conf->salt;
				$conf = get_user_configuration($user);
				$s = $conf->passwordHash;
				if (strlen($s) >= 60) {
					$this->view->salt1 = substr($s, 0, 29);	//CRYPT_BLOWFISH Salt: "$2a$", a two digit cost parameter, "$", and 22 characters from the alphabet "./0-9A-Za-z".
					$this->view->nonce = sha1($salt . uniqid(mt_rand(), true));
					Base_Session::_param('nonce', $this->view->nonce);
					return;	//Success
				}
			} catch (Base_Exception $me) {
				Base_Log::warning('Nonce failure: ' . $me->getMessage());
			}
		} else {
			Base_Log::notice('Nonce failure due to invalid username!');
		}
		//Failure: Return random data.
		$this->view->salt1 = sprintf('$2a$%02d$', RSSServer_password_Util::BCRYPT_COST);
		$alphabet = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		for ($i = 22; $i > 0; $i--) {
			$this->view->salt1 .= $alphabet[mt_rand(0, 63)];
		}
		$this->view->nonce = sha1(mt_rand());
	}
}
