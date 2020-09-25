<?php
/**
 * The Dispatcher takes care of initializing the Controller and executing the action
 * determined in the Request
 * It's a singleton
 */
class Base_Dispatcher {

	/* singleton */
	private static $instance = null;
	private static $needsReset;
	private static $registrations = array();

	private $controller;

	/**
	 * Retrieves the Dispatcher instance
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Base_Dispatcher();
		}
		return self::$instance;
	}

	/**
	 * Launches the controller indicated in Request
	 * Fills the Response body from the View
	 * @exception Base_Exception
	 */
	public function run() {
		do {
			self::$needsReset = false;

			try {
				$this->createController(Base_Request::controllerName());
				
				$conf = Base_Configuration::get('system');
				if ($conf->debugging) {
					echo '@@@ Dispatcher@run: controllerName';
					var_dump(Base_Request::controllerName());
					echo('@@@ Dispatcher@run: actionName');
					var_dump(Base_Request::actionName());
				}

				$this->controller->init();
				$this->controller->prependAction();
				if (!self::$needsReset) {
					$this->launchAction(
						Base_Request::actionName()
						. 'Action'
					);
				}
				$this->controller->appendAction();

				if (!self::$needsReset) {
					$this->controller->declareCspHeader();
					$this->controller->view()->build();
				} 
			} catch (Base_Exception $e) {
				throw $e;
			}
		} while (self::$needsReset);

		$conf = Base_Configuration::get('system');
		if ($conf->debugging) {
			echo '@@@ Dispatcher@run:';
			var_dump('exit in loop');
		}
	}

	/**
	 * Informs the controller that he must start again because the request has been modified
	 */
	public static function reset() {
		$conf = Base_Configuration::get('system');
		if ($conf->debugging) {
			echo '@@@ Dispatcher@reset:';
			var_dump('reset is called');
		}
		
		self::$needsReset = true;
	}

	/**
	 * Instantiates the Controller
	 * @param $ base_name the name of the controller to instantiate
	 * @exception ControllerNotExistException the controller does not exist
	 * @exception ControllerNotActionControllerException controller is not
	 * 					> an instance of ActionController
	 */
	private function createController($base_name) {
		if (self::isRegistered($base_name)) {
			self::loadController($base_name);
			$controller_name = 'FreshExtension_' . $base_name . '_Controller';
		} else {
			$controller_name = 'RSSServer_' . $base_name . '_Controller';
		}

		if (!class_exists($controller_name)) {
			throw new Base_ControllerNotExistException (
				$controller_name,
				Base_Exception::ERROR
			);
		}
		$this->controller = new $controller_name();

		if (! ($this->controller instanceof Base_ActionController)) {
			throw new Base_ControllerNotActionControllerException (
				$controller_name,
				Base_Exception::ERROR
			);
		}
	}

	/**
	 * Launch the action on the dispatcher controller
	 * @param $ action_name the name of the action
	 * @exception ActionException if we cannot execute the action on
	 * the controller
	 */
	private function launchAction($action_name) {
		if (!is_callable(array(
			$this->controller,
			$action_name
		))) {
			throw new Base_ActionException (
				get_class($this->controller),
				$action_name,
				Base_Exception::ERROR
			);
		}
		call_user_func(array(
			$this->controller,
			$action_name
		));
	}

	/**
	 * Register a controller file.
	 *
	 * @param $base_name the base name of the controller (i.e. ./?c=<base_name>)
	 * @param $base_path the base path where we should look into to find info.
	 */
	public static function registerController($base_name, $base_path) {
		if (!self::isRegistered($base_name)) {
			self::$registrations[$base_name] = $base_path;
		}
	}

	/**
	 * Return if a controller is registered.
	 *
	 * @param $base_name the base name of the controller.
	 * @return true if the controller has been registered, false else.
	 */
	public static function isRegistered($base_name) {
		return isset(self::$registrations[$base_name]);
	}

	/**
	 * Load a controller file (include).
	 *
	 * @param $base_name the base name of the controller.
	 */
	private static function loadController($base_name) {
		$base_path = self::$registrations[$base_name];
		$controller_filename = $base_path . '/Controllers/' . $base_name . 'Controller.php';
		include_once $controller_filename;
	}

	private static function setViewPath($controller, $base_name) {
		$base_path = self::$registrations[$base_name];
		$controller->view()->setBasePathname($base_path);
	}
}
