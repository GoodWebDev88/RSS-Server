<?php
/**
 * The FrontController class is the template's Dispatcher, it launches the application
 * It is usually called in the index.php file at the root of the server
 */
class Base_FrontController {
	protected $dispatcher;

	/**
	 * Constructor
	 * Initializes the dispatcher, updates the Request
	 */
	public function __construct() {
		try {
			Base_Configuration::register('system',
			                             DATA_PATH . '/config.php',
			                             RSSSERVER_PATH . '/config.default.php');
			$this->setReporting();

			Base_Request::init();

			$url = $this->buildUrl();
			$url['params'] = array_merge (
				$url['params'],
				Base_Request::fetchPOST ()
			);
			Base_Request::forward ($url);
		} catch (Base_Exception $e) {
			Base_Log::error($e->getMessage());
			$this->killApp ($e->getMessage ());
		}

		$this->dispatcher = Base_Dispatcher::getInstance();
	}

	/**
	 * Returns an array representing the url passed through the address bar
	 * @return table representing the url
	 */
	private function buildUrl() {
		$url = array ();

		$url['c'] = Base_Request::fetchGET(
			'c',
			Base_Request::defaultControllerName()
		);
		$url['a'] = Base_Request::fetchGET(
			'a',
			Base_Request::defaultActionName()
		);
		$url['params'] = Base_Request::fetchGET();

		// post-traitement
		unset ($url['params']['c']);
		unset ($url['params']['a']);

		return $url;
	}

	/**
	 * Start the application (launch the dispatcher and return the response)
	 */
	public function run() {
		try {
			$this->dispatcher->run();
		} catch (Base_Exception $e) {
			try {
				Base_Log::error($e->getMessage());
			} catch (Base_PermissionDeniedException $e) {
				$this->killApp ($e->getMessage ());
			}

			if ($e instanceof Base_FileNotExistException ||
					$e instanceof Base_ControllerNotExistException ||
					$e instanceof Base_ControllerNotActionControllerException ||
					$e instanceof Base_ActionException) {
				Base_Error::error (
					404,
					array ('error' => array ($e->getMessage ())),
					true
				);
			} else {
				$this->killApp ();
			}
		}
	}

	/**
	* Allows you to stop the program in an emergency
	*/
	private function killApp($txt = '') {
		if ($txt == '') {
			$txt = 'See logs files';
		}
		exit ('### Application problem ###<br />'."\n".$txt);
	}

	private function setReporting() {
		$envType = getenv('RSSSERVER_ENV');
		if ($envType == '') {
			$conf = Base_Configuration::get('system');
			$envType = $conf->environment;
		}
		switch ($envType) {
			case 'development':
				error_reporting(E_ALL);
				ini_set('display_errors', 'On');
				ini_set('log_errors', 'On');
				break;
			case 'silent':
				error_reporting(0);
				break;
			case 'production':
			default:
				error_reporting(E_ALL);
				ini_set('display_errors', 'Off');
				ini_set('log_errors', 'On');
				break;
		}
	}
}
