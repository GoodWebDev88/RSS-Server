<?php

class RSSServer_password_UtilTest extends PHPUnit\Framework\TestCase {
	public function testCheck() {
		$password = '1234567';

		$ok = RSSServer_password_Util::check($password);

		$this->assertTrue($ok);
	}

	public function testCheckReturnsFalseIfEmpty() {
		$password = '';

		$ok = RSSServer_password_Util::check($password);

		$this->assertFalse($ok);
	}

	public function testCheckReturnsFalseIfLessThan7Characters() {
		$password = '123456';

		$ok = RSSServer_password_Util::check($password);

		$this->assertFalse($ok);
	}
}
