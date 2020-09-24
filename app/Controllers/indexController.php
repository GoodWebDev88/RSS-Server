<?php

/**
 * This class handles main actions of RSSServer.
 */
class RSSServer_index_Controller extends Base_ActionController {

	/**
	 * This action only redirect on the default view mode (normal or global)
	 */
	public function indexAction() {
		$prefered_output = RSSServer_Context::$user_conf->view_mode;
		Base_Request::forward(array(
			'c' => 'index',
			'a' => $prefered_output
		));
	}

	/**
	 * This action displays the normal view of RSSServer.
	 */
	public function normalAction() {
		$allow_anonymous = RSSServer_Context::$system_conf->allow_anonymous;
		if (!RSSServer_Auth::hasAccess() && !$allow_anonymous) {
			Base_Request::forward(array('c' => 'auth', 'a' => 'login'));
			return;
		}

		try {
			$this->updateContext();
		} catch (RSSServer_Context_Exception $e) {
			Base_Error::error(404);
		}

		$this->_csp([
			'default-src' => "'self'",
			'frame-src' => '*',
			'img-src' => '* data:',
			'media-src' => '*',
		]);

		$this->view->categories = RSSServer_Context::$categories;

		$this->view->rss_title = RSSServer_Context::$name . ' | ' . Base_View::title();
		$title = RSSServer_Context::$name;
		if (RSSServer_Context::$get_unread > 0) {
			$title = '(' . RSSServer_Context::$get_unread . ') ' . $title;
		}
		Base_View::prependTitle($title . ' · ');

		RSSServer_Context::$id_max = time() . '000000';

		$this->view->callbackBeforeFeeds = function ($view) {
			try {
				$tagDAO = RSSServer_Factory::createTagDao();
				$view->tags = $tagDAO->listTags(true);
				$view->nbUnreadTags = 0;
				foreach ($view->tags as $tag) {
					$view->nbUnreadTags += $tag->nbUnread();
				}
			} catch (Exception $e) {
				Base_Log::notice($e->getMessage());
			}
		};

		$this->view->callbackBeforeEntries = function ($view) {
			try {
				RSSServer_Context::$number++;	//+1 for pagination
				$view->entries = RSSServer_index_Controller::listEntriesByContext();
				RSSServer_Context::$number--;
				ob_start();	//Buffer "one entry at a time"
			} catch (RSSServer_EntriesGetter_Exception $e) {
				Base_Log::notice($e->getMessage());
				Base_Error::error(404);
			}
		};

		$this->view->callbackBeforePagination = function ($view, $nbEntries, $lastEntry) {
			if ($nbEntries >= RSSServer_Context::$number) {
				//We have enough entries: we discard the last one to use it for the next pagination
				ob_clean();
				RSSServer_Context::$next_id = $lastEntry->id();
			}
			ob_end_flush();
		};
	}

	/**
	 * This action displays the reader view of RSSServer.
	 *
	 * @todo: change this view into specific CSS rules?
	 */
	public function readerAction() {
		$this->normalAction();
	}

	/**
	 * This action displays the global view of RSSServer.
	 */
	public function globalAction() {
		$allow_anonymous = RSSServer_Context::$system_conf->allow_anonymous;
		if (!RSSServer_Auth::hasAccess() && !$allow_anonymous) {
			Base_Request::forward(array('c' => 'auth', 'a' => 'login'));
			return;
		}

		Base_View::appendScript(Base_Url::display('/scripts/extra.js?' . @filemtime(PUBLIC_PATH . '/scripts/extra.js')));
		Base_View::appendScript(Base_Url::display('/scripts/global_view.js?' . @filemtime(PUBLIC_PATH . '/scripts/global_view.js')));

		try {
			$this->updateContext();
		} catch (RSSServer_Context_Exception $e) {
			Base_Error::error(404);
		}

		$this->view->categories = RSSServer_Context::$categories;

		$this->view->rss_title = RSSServer_Context::$name . ' | ' . Base_View::title();
		$title = _t('index.feed.title_global');
		if (RSSServer_Context::$get_unread > 0) {
			$title = '(' . RSSServer_Context::$get_unread . ') ' . $title;
		}
		Base_View::prependTitle($title . ' · ');

		$this->_csp([
			'default-src' => "'self'",
			'frame-src' => '*',
			'img-src' => '* data:',
			'media-src' => '*',
		]);
	}

	/**
	 * This action displays the RSS feed of RSSServer.
	 */
	public function rssAction() {
		$allow_anonymous = RSSServer_Context::$system_conf->allow_anonymous;
		$token = RSSServer_Context::$user_conf->token;
		$token_param = Base_Request::param('token', '');
		$token_is_ok = ($token != '' && $token === $token_param);

		// Check if user has access.
		if (!RSSServer_Auth::hasAccess() &&
				!$allow_anonymous &&
				!$token_is_ok) {
			Base_Error::error(403);
		}

		try {
			$this->updateContext();
		} catch (RSSServer_Context_Exception $e) {
			Base_Error::error(404);
		}

		try {
			$this->view->entries = RSSServer_index_Controller::listEntriesByContext();
		} catch (RSSServer_EntriesGetter_Exception $e) {
			Base_Log::notice($e->getMessage());
			Base_Error::error(404);
		}

		// No layout for RSS output.
		$this->view->url = PUBLIC_TO_INDEX_PATH . '/' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
		$this->view->rss_title = RSSServer_Context::$name . ' | ' . Base_View::title();
		$this->view->_layout(false);
		header('Content-Type: application/rss+xml; charset=utf-8');
	}

	/**
	 * This action updates the Context object by using request parameters.
	 *
	 * Parameters are:
	 *   - state (default: conf->default_view)
	 *   - search (default: empty string)
	 *   - order (default: conf->sort_order)
	 *   - nb (default: conf->posts_per_page)
	 *   - next (default: empty string)
	 *   - hours (default: 0)
	 */
	private function updateContext() {
		if (empty(RSSServer_Context::$categories)) {
			$catDAO = RSSServer_Factory::createCategoryDao();
			RSSServer_Context::$categories = $catDAO->listSortedCategories();
		}

		// Update number of read / unread variables.
		$entryDAO = RSSServer_Factory::createEntryDao();
		RSSServer_Context::$total_starred = $entryDAO->countUnreadReadFavorites();
		RSSServer_Context::$total_unread = RSSServer_CategoryDAO::CountUnreads(
			RSSServer_Context::$categories, 1
		);

		RSSServer_Context::_get(Base_Request::param('get', 'a'));

		RSSServer_Context::$state = Base_Request::param(
			'state', RSSServer_Context::$user_conf->default_state
		);
		$state_forced_by_user = Base_Request::param('state', false) !== false;
		if (RSSServer_Context::$user_conf->default_view === 'adaptive' &&
				RSSServer_Context::$get_unread <= 0 &&
				!RSSServer_Context::isStateEnabled(RSSServer_Entry::STATE_READ) &&
				!$state_forced_by_user) {
			RSSServer_Context::$state |= RSSServer_Entry::STATE_READ;
		}

		RSSServer_Context::$search = new RSSServer_BooleanSearch(Base_Request::param('search', ''));
		RSSServer_Context::$order = Base_Request::param(
			'order', RSSServer_Context::$user_conf->sort_order
		);
		RSSServer_Context::$number = intval(Base_Request::param('nb', RSSServer_Context::$user_conf->posts_per_page));
		if (RSSServer_Context::$number > RSSServer_Context::$user_conf->max_posts_per_rss) {
			RSSServer_Context::$number = max(
				RSSServer_Context::$user_conf->max_posts_per_rss,
				RSSServer_Context::$user_conf->posts_per_page);
		}
		RSSServer_Context::$first_id = Base_Request::param('next', '');
		RSSServer_Context::$sinceHours = intval(Base_Request::param('hours', 0));
	}

	/**
	 * This method returns a list of entries based on the Context object.
	 */
	public static function listEntriesByContext() {
		$entryDAO = RSSServer_Factory::createEntryDao();

		$get = RSSServer_Context::currentGet(true);
		if (is_array($get)) {
			$type = $get[0];
			$id = $get[1];
		} else {
			$type = $get;
			$id = '';
		}

		$limit = RSSServer_Context::$number;

		$date_min = 0;
		if (RSSServer_Context::$sinceHours) {
			$date_min = time() - (RSSServer_Context::$sinceHours * 3600);
			$limit = RSSServer_Context::$user_conf->max_posts_per_rss;
		}

		foreach ($entryDAO->listWhere(
					$type, $id, RSSServer_Context::$state, RSSServer_Context::$order,
					$limit, RSSServer_Context::$first_id,
					RSSServer_Context::$search, $date_min)
				as $entry) {
			yield $entry;
		}
	}

	/**
	 * This action displays the about page of RSSServer.
	 */
	public function aboutAction() {
		Base_View::prependTitle(_t('index.about.title') . ' · ');
	}

	/**
	 * This action displays the EULA page of RSSServer.
	 * This page is enabled only if admin created a data/tos.html file.
	 * The content of the page is the content of data/tos.html.
	 * It returns 404 if there is no EULA.
	 */
	public function tosAction() {
		$terms_of_service = file_get_contents(join_path(DATA_PATH, 'tos.html'));
		if (!$terms_of_service) {
			Base_Error::error(404);
		}

		$this->view->terms_of_service = $terms_of_service;
		$this->view->can_register = !max_registrations_reached();
		Base_View::prependTitle(_t('index.tos.title') . ' · ');
	}

	/**
	 * This action displays logs of RSSServer for the current user.
	 */
	public function logsAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		Base_View::prependTitle(_t('index.log.title') . ' · ');

		if (Base_Request::isPost()) {
			RSSServer_LogDAO::truncate();
		}

		$logs = RSSServer_LogDAO::lines();	//TODO: ask only the necessary lines

		//gestion pagination
		$page = Base_Request::param('page', 1);
		$this->view->logsPaginator = new Base_Paginator($logs);
		$this->view->logsPaginator->_nbItemsPerPage(50);
		$this->view->logsPaginator->_currentPage($page);
	}
}
