<?php
/**
 * The Model_array class represents the model interacting with text type files managing php arrays
 */
class Base_ModelArray {
	/**
	 * $filename is the name of the file
	 */
	protected $filename;

	/**
	 * Open the specified file, load the array into $ array and the $ filename
	 * @param $ filename the name of the file to open containing an array
	 * Note: $ array will necessarily be an array
	 */
	public function __construct ($filename) {
		$this->filename = $filename;
	}

	protected function loadArray() {
		if (!file_exists($this->filename)) {
			throw new Base_FileNotExistException($this->filename, Base_Exception::WARNING);
		} elseif (($handle = $this->getLock()) === false) {
			throw new Base_PermissionDeniedException($this->filename);
		} else {
			$data = include($this->filename);
			$this->releaseLock($handle);

			if ($data === false) {
				throw new Base_PermissionDeniedException($this->filename);
			} elseif (!is_array($data)) {
				$data = array();
			}
			return $data;
		}
	}

	/**
	 * Save the array $ array in the file $ filename
	 **/
	protected function writeArray($array) {
		if (file_put_contents($this->filename, "<?php\n return " . var_export($array, true) . ';', LOCK_EX) === false) {
			throw new Base_PermissionDeniedException($this->filename);
		}
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate($this->filename);	//Clear PHP cache for include
		}
		return true;
	}

	private function getLock() {
		$handle = fopen($this->filename, 'r');
		if ($handle === false) {
			return false;
		}

		$count = 50;
		while (!flock($handle, LOCK_SH) && $count > 0) {
			$count--;
			usleep(1000);
		}

		if ($count > 0) {
			return $handle;
		} else {
			fclose($handle);
			return false;
		}
	}

	private function releaseLock($handle) {
		flock($handle, LOCK_UN);
		fclose($handle);
	}
}
