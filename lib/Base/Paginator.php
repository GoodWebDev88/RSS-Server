<?php
/**
 * The Paginator class allows you to easily manage the pagination of the application
 */
class Base_Paginator {
	/**
	 * $ items array of items to display / manage
	 */
	private $items = array();

	/**
	 * $ nbItemsPerPage the number of items per page
	 */
	private $nbItemsPerPage = 10;

	/**
	 * $ currentPage current page to manage
	 */
	private $currentPage = 1;

	/**
	 * $ nbPage the number of pagination pages
	 */
	private $nbPage = 1;

	/**
	 * $ nbItems the number of items
	 */
	private $nbItems = 0;

	/**
	 * Constructor
	 * @param $ items the elements to manage
	 */
	public function __construct($items) {
		$this->_items ($items);
		$this->_nbItems (count ($this->items (true)));
		$this->_nbItemsPerPage ($this->nbItemsPerPage);
		$this->_currentPage ($this->currentPage);
	}

	/**
	 * Allows display of pagination
	 * @param $ view name of the view file located in / app / views / helpers /
	 * @param $ variable emitter of type $ _GET [] allowing to find the page
	 */
	public function render($view, $getteur) {
		$view = APP_PATH . '/views/helpers/'.$view;

		if (file_exists ($view)) {
			include ($view);
		}
	}

	/**
	 * Allows you to find the page of a given element
	 * @param $ item the element to find
	 * @return the page where the element is located (false if not found)
	 */
	public function pageByItem($item) {
		$page = false;
		$i = 0;

		do {
			if ($item == $this->items[$i]) {
				$page = ceil (($i + 1) / $this->nbItemsPerPage);
			}

			$i++;
		} while (!$page && $i < $this->nbItems ());

		return $page;
	}

	/**
	 * Allows you to find the position of a given element (from 0)
	 * @param $ item the element to find
	 * @return the position the element is in (false if not found)
	 */
	public function positionByItem($item) {
		$find = false;
		$i = 0;

		do {
			if ($item == $this->items[$i]) {
				$find = true;
			} else {
				$i++;
			}
		} while (!$find && $i < $this->nbItems ());

		return $i;
	}

	/**
	 * Allows you to retrieve an item by its position
	 * @param $ pos the position of the element
	 * @return the item located at $ pos (last item if $ pos <0, 1st if $ pos> = count ($ items))
	 */
	public function itemByPosition($pos) {
		if ($pos < 0) {
			$pos = $this->nbItems () - 1;
		}
		if ($pos >= count($this->items)) {
			$pos = 0;
		}

		return $this->items[$pos];
	}

	/**
	 * GETTERS
	 */
	/**
	 * @param $ all if true, returns all elements without taking pagination into account
	 */
	public function items($all = false) {
		$array = array ();
		$nbItems = $this->nbItems ();

		if ($nbItems <= $this->nbItemsPerPage || $all) {
			$array = $this->items;
		} else {
			$begin = ($this->currentPage - 1) * $this->nbItemsPerPage;
			$counter = 0;
			$i = 0;

			foreach ($this->items as $key => $item) {
				if ($i >= $begin) {
					$array[$key] = $item;
					$counter++;
				}
				if ($counter >= $this->nbItemsPerPage) {
					break;
				}
				$i++;
			}
		}

		return $array;
	}
	public function nbItemsPerPage() {
		return $this->nbItemsPerPage;
	}
	public function currentPage() {
		return $this->currentPage;
	}
	public function nbPage() {
		return $this->nbPage;
	}
	public function nbItems() {
		return $this->nbItems;
	}

	/**
	 * SETTERS
	 */
	public function _items($items) {
		if (is_array ($items)) {
			$this->items = $items;
		}

		$this->_nbPage();
	}
	public function _nbItemsPerPage($nbItemsPerPage) {
		if ($nbItemsPerPage > $this->nbItems ()) {
			$nbItemsPerPage = $this->nbItems ();
		}
		if ($nbItemsPerPage < 0) {
			$nbItemsPerPage = 0;
		}

		$this->nbItemsPerPage = $nbItemsPerPage;
		$this->_nbPage ();
	}
	public function _currentPage($page) {
		if($page < 1 || ($page > $this->nbPage && $this->nbPage > 0)) {
			throw new CurrentPagePaginationException ($page);
		}

		$this->currentPage = $page;
	}
	private function _nbPage() {
		if ($this->nbItemsPerPage > 0) {
			$this->nbPage = ceil ($this->nbItems () / $this->nbItemsPerPage);
		}
	}
	public function _nbItems($value) {
		$this->nbItems = $value;
	}
}
