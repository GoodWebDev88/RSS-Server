<?php
class Base_PDOConnectionException extends Base_Exception {
	public function __construct($error, $user, $code = self::ERROR) {
		$message = 'Access to database is denied for `' . $user . '`: ' . $error;

		parent::__construct($message, $code);
	}
}
