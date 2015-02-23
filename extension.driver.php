<?php

include_once(EXTENSIONS . '/favourite_shortcuts/lib/class.favourite.php');

class extension_Favourite_Shortcuts extends Extension {

	public function fetchNavigation()
	{
		$navigation = array(
			array(
				'location'  => __('System'),
				'name'      => __('Favourites'),
				'link'      => '/favourites/'
			)
		);

		$favourites = FavouriteManager::fetch();

		$group = array();

		foreach ($favourites as $favourite) {

			if ( empty($group) || $group['name'] != $favourite['location']){
				if (!empty($group)) {
					$navigation[] = $group;
				}

				$group = array(
					'name' => 	$favourite['location'],
					'children' => array(),
					'type' => 'content',
					'index' => 1
				);
			}

			$link = $favourite['link'];
			$relative = 'yes';

			$navItem = array(
					'location'  => $favourite['location'],
					'name'      => $favourite['name'],
					'link'      => $link,
					'relative'  => false,
				);


			$group['children'][] = $navItem;
		}

		if (!empty($group))
			$navigation[] = $group;

		return $navigation;
	}
	
	public function getSubscribedDelegates() {
		return array(
			array(
				'page' => '/backend/',
				'delegate' => 'InitialiseAdminPageHead',
				'callback' => 'initializeAdmin',
			),
		);
	}
	
	/**
	 * Some admin customisations
	 */
	public function initializeAdmin($context) {

		$page = Administration::instance()->Page;
		$assets_path = URL . '/extensions/favourite_shortcuts/assets';
				
		// Only load on /publish/static-pages/ [this should be a variable]
		if ($page->_context['section_handle'] == 'pages' && $page->_context['page'] == 'index') {
			$page->addStylesheetToHead($assets_path . '/admin.css', 'all');
			$page->addScriptToHead($assets_path . '/js/pages.js');
			// Effectively disable backend pagination for this section
			// Symphony::Configuration()->set("pagination_maximum_rows", $LOAD_NUMBER++, "symphony");
		}
		
	}
		
	
	/**
	 * Installation
	 */
	public function install() {
		// Roles table:
		Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `tbl_favourite_shortcuts` (
				`id` INT(11) unsigned NOT NULL auto_increment,
				`author_id` INT(11) unsigned NOT NULL,
				`location` VARCHAR(255) NOT NULL,
				`name` VARCHAR(255) NOT NULL,
				`link` VARCHAR(255) NOT NULL,
				`limit` enum('user','author','manager','developer') DEFAULT 'user',
				`sort` INT(11) unsigned NOT NULL,
				PRIMARY KEY (`id`)
			);
		");
	}

	public static function baseURL(){
		return SYMPHONY_URL . '/extension/favourite_shortcuts/';
	}

}
?>