<?php
/**
 * The Error class is used to throw HTTP errors
 */
class Base_Error {
	public function __construct() { }

	/**
	 * Used to throw an error
	 * @param $ code the type of error, by default 404 (page not found)
	 * @param $ logs error logs split from the form
	 * 		> $ logs ['error']
	 * 		> $ logs ['warning']
	 * 		> $ logs ['notice']
	 * @param $ redirect indicates whether to force the redirection (logs will not be transmitted)
	 */
	public static function error($code = 404, $logs = array(), $redirect = true) {
		$logs = self::processLogs($logs);
		$error_filename = APP_PATH . '/Controllers/errorController.php';

		if (file_exists($error_filename)) {
			Base_Session::_param('error_code', $code);
			Base_Session::_param('error_logs', $logs);

			Base_Request::forward(array (
				'c' => 'error'
			), $redirect);
		} else {
			echo '<h1>An error occured</h1>' . "\n";

			if (!empty ($logs)) {
				echo '<ul>' . "\n";
				foreach ($logs as $log) {
					echo '<li>' . $log . '</li>' . "\n";
				}
				echo '</ul>' . "\n";
			}

			exit ();
		}
	}

	/**
	 * Allows you to return the logs so that you only have
	 * those we really want
	 * @param $ logs the logs sorted by categories (error, warning, notice)
	 * @return the list of logs, without category,
	 * depending on the environment
	 */
	private static function processLogs($logs) {
		$conf = Base_Configuration::get('system');
		$env = $conf->environment;
		$logs_ok = array();
		$error = array();
		$warning = array();
		$notice = array();

		if (isset($logs['error'])) {
			$error = $logs['error'];
		}
		if (isset($logs['warning'])) {
			$warning = $logs['warning'];
		}
		if (isset($logs['notice'])) {
			$notice = $logs['notice'];
		}

		if ($env == 'production') {
			$logs_ok = $error;
		}
		if ($env == 'development') {
			$logs_ok = array_merge ($error, $warning, $notice);
		}

		return $logs_ok;
	}
}
