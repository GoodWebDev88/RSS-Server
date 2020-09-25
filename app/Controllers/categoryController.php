<?php

/**
 * Controller to handle actions relative to categories.
 * User needs to be connected.
 */
class RSSServer_category_Controller extends Base_ActionController {
	/**
	 * This action is called before every other action in that class. It is
	 * the common boiler plate for every action. It is triggered by the
	 * underlying BASE template.
	 *
	 */
	public function prependAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}

		$catDAO = RSSServer_Factory::createCategoryDao();
		$catDAO->checkDefault();
	}

	/**
	 * This action creates a new category.
	 *
	 * Request parameter is:
	 *   - new-category
	 */
	public function createAction() {
		$catDAO = RSSServer_Factory::createCategoryDao();
		$url_redirect = array('c' => 'subscription', 'a' => 'index');

		$limits = RSSServer_Context::$system_conf->limits;
		$this->view->categories = $catDAO->listCategories(false);

		if (count($this->view->categories) >= $limits['max_categories']) {
			Base_Request::bad(_t('feedback.sub.category.over_max', $limits['max_categories']),
			                  $url_redirect);
		}

		if (Base_Request::isPost()) {
			invalidateHttpCache();

			$cat_name = Base_Request::param('new-category');
			if (!$cat_name) {
				Base_Request::bad(_t('feedback.sub.category.no_name'), $url_redirect);
			}

			$cat = new RSSServer_Category($cat_name);

			if ($catDAO->searchByName($cat->name()) != null) {
				Base_Request::bad(_t('feedback.sub.category.name_exists'), $url_redirect);
			}

			$values = array(
				'id' => $cat->id(),
				'name' => $cat->name(),
			);

			if ($catDAO->addCategory($values)) {
				Base_Request::good(_t('feedback.sub.category.created', $cat->name()), $url_redirect);
			} else {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}
		}

		Base_Request::forward($url_redirect, true);
	}

	/**
	 * This action updates the given category.
	 *
	 * Request parameters are:
	 *   - id
	 *   - name
	 */
	public function updateAction() {
		$catDAO = RSSServer_Factory::createCategoryDao();
		$url_redirect = array('c' => 'subscription', 'a' => 'index');

		if (Base_Request::isPost()) {
			invalidateHttpCache();

			$id = Base_Request::param('id');
			$name = Base_Request::param('name', '');
			if (strlen($name) <= 0) {
				Base_Request::bad(_t('feedback.sub.category.no_name'), $url_redirect);
			}

			if ($catDAO->searchById($id) == null) {
				Base_Request::bad(_t('feedback.sub.category.not_exist'), $url_redirect);
			}

			$cat = new RSSServer_Category($name);
			$values = array(
				'name' => $cat->name(),
			);

			if ($catDAO->updateCategory($id, $values)) {
				Base_Request::good(_t('feedback.sub.category.updated'), $url_redirect);
			} else {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}
		}

		Base_Request::forward($url_redirect, true);
	}

	/**
	 * This action deletes a category.
	 * Feeds in the given category are moved in the default category.
	 * Related user queries are deleted too.
	 *
	 * Request parameter is:
	 *   - id (of a category)
	 */
	public function deleteAction() {
		$feedDAO = RSSServer_Factory::createFeedDao();
		$catDAO = RSSServer_Factory::createCategoryDao();
		$url_redirect = array('c' => 'subscription', 'a' => 'index');

		if (Base_Request::isPost()) {
			invalidateHttpCache();

			$id = Base_Request::param('id');
			if (!$id) {
				Base_Request::bad(_t('feedback.sub.category.no_id'), $url_redirect);
			}

			if ($id === RSSServer_CategoryDAO::DEFAULTCATEGORYID) {
				Base_Request::bad(_t('feedback.sub.category.not_delete_default'), $url_redirect);
			}

			if ($feedDAO->changeCategory($id, RSSServer_CategoryDAO::DEFAULTCATEGORYID) === false) {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}

			if ($catDAO->deleteCategory($id) === false) {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}

			// Remove related queries.
			RSSServer_Context::$user_conf->queries = remove_query_by_get(
				'c_' . $id, RSSServer_Context::$user_conf->queries);
			RSSServer_Context::$user_conf->save();

			Base_Request::good(_t('feedback.sub.category.deleted'), $url_redirect);
		}

		Base_Request::forward($url_redirect, true);
	}

	/**
	 * This action deletes all the feeds relative to a given category.
	 * Feed-related queries are deleted.
	 *
	 * Request parameter is:
	 *   - id (of a category)
	 */
	public function emptyAction() {
		$feedDAO = RSSServer_Factory::createFeedDao();
		$url_redirect = array('c' => 'subscription', 'a' => 'index');

		if (Base_Request::isPost()) {
			invalidateHttpCache();

			$id = Base_Request::param('id');
			if (!$id) {
				Base_Request::bad(_t('feedback.sub.category.no_id'), $url_redirect);
			}

			// List feeds to remove then related user queries.
			$feeds = $feedDAO->listByCategory($id);

			if ($feedDAO->deleteFeedByCategory($id)) {
				// TODO: Delete old favicons

				// Remove related queries
				foreach ($feeds as $feed) {
					RSSServer_Context::$user_conf->queries = remove_query_by_get(
						'f_' . $feed->id(), RSSServer_Context::$user_conf->queries);
				}
				RSSServer_Context::$user_conf->save();

				Base_Request::good(_t('feedback.sub.category.emptied'), $url_redirect);
			} else {
				Base_Request::bad(_t('feedback.sub.category.error'), $url_redirect);
			}
		}

		Base_Request::forward($url_redirect, true);
	}
}
