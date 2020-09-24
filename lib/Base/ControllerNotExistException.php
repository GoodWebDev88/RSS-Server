<?php
class Base_ControllerNotExistException extends Base_Exception {
	public function __construct ($controller_name, $code = self::ERROR) {
		$message = 'Controller not found!';
		parent::__construct ($message, $code);
	}
}
