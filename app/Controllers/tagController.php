<?php

/**
 * Controller to handle every tag actions.
 */
class RSSServer_tag_Controller extends Base_ActionController {
	/**
	 * This action is called before every other action in that class. It is
	 * the common boiler plate for every action. It is triggered by the
	 * underlying BASE template.
	 */
	public function firstAction() {
		if (!RSSServer_Auth::hasAccess()) {
			Base_Error::error(403);
		}
		// If ajax request, we do not print layout
		$this->ajax = Base_Request::param('ajax');
		if ($this->ajax) {
			$this->view->_layout(false);
			Base_Request::_param('ajax');
		}
	}

	/**
	 * This action adds (checked=true) or removes (checked=false) a tag to an entry.
	 */
	public function tagEntryAction() {
		if (Base_Request::isPost()) {
			$id_tag = Base_Request::param('id_tag');
			$name_tag = trim(Base_Request::param('name_tag'));
			$id_entry = Base_Request::param('id_entry');
			$checked = Base_Request::paramTernary('checked');
			if ($id_entry != false) {
				$tagDAO = RSSServer_Factory::createTagDao();
				if ($id_tag == 0 && $name_tag != '' && $checked) {
					//Create new tag
					$id_tag = $tagDAO->addTag(array('name' => $name_tag));
				}
				if ($id_tag != 0) {
					$tagDAO->tagEntry($id_tag, $id_entry, $checked);
				}
			}
		} else {
			Base_Error::error(405);
		}
		if (!$this->ajax) {
			Base_Request::forward(array(
				'c' => 'index',
				'a' => 'index',
			), true);
		}
	}

	public function deleteAction() {
		if (Base_Request::isPost()) {
			$id_tag = Base_Request::param('id_tag');
			if ($id_tag != false) {
				$tagDAO = RSSServer_Factory::createTagDao();
				$tagDAO->deleteTag($id_tag);
			}
		} else {
			Base_Error::error(405);
		}
		if (!$this->ajax) {
			Base_Request::forward(array(
				'c' => 'index',
				'a' => 'index',
			), true);
		}
	}

	public function getTagsForEntryAction() {
		$this->view->_layout(false);
		header('Content-Type: application/json; charset=UTF-8');
		header('Cache-Control: private, no-cache, no-store, must-revalidate');
		$id_entry = Base_Request::param('id_entry', 0);
		$tagDAO = RSSServer_Factory::createTagDao();
		$this->view->tags = $tagDAO->getTagsForEntry($id_entry);
	}
}
