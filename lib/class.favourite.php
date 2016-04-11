<?php

	Class FavouriteManager {

		public static $_pool = array();

		/**
		 * Given an associative array of data with the keys being the
		 * relevant table names, and the values being an associative array
		 * of data to insert, add a new favourite to the Database. favourites are spread
		 * across three tables, `tbl_favourite_shortcuts`, `tbl_favourite_shortcuts_forbidden_pages`
		 * and `tbl_favourite_shortcuts_event_permissions`. This function will return
		 * the ID of the favourite after it has been added to the database.
		 *
		 * @param array $data
		 * @return integer
		 *  The newly created favourite's ID
		 */
		public static function add(array $data) {

			Symphony::Database()->insert($data['favourites'], 'tbl_favourite_shortcuts');
			$favourite_id = Symphony::Database()->getInsertID();

			return $favourite_id;
		}

		/**
		 * Given a `$favourite_id` and an associative array of data in the same fashion
		 * as `favouriteManager::add()`, this will update a favourite record returning boolean
		 *
		 * @param integer $favourite_id
		 * @param array $data
		 * @return boolean
		 */
		public static function edit($favourite_id, array $data) {
			if(is_null($favourite_id)) return false;

			Symphony::Database()->update($data['favourites'], 'tbl_favourite_shortcuts', "`id` = " . $favourite_id);

			return true;
		}

		/**
		 * Will delete the favourite given a `$favourite_id`.
		 *
		 * @param integer $favourite_id
		 * @param boolean $purge_members
		 * @return boolean
		 */
		public static function delete($favourite_id) {
			//delete only if has permissions to. IE Developer or same author ID

			Symphony::Database()->delete("`tbl_favourite_shortcuts`", " `id` = " . $favourite_id);

			return true;
		}

		/**
		 * This function will return favourites from the database. If the `$favourite_id` is
		 * given the function will return a favourite object (should it be found) otherwise
		 * an array of favourite objects will be returned.
		 *
		 * @param integer $favourite_id
		 * @return favourite|array
		 */
		public static function fetch($favourite_id = null) {
			$result = array();
			$return_single = is_null($favourite_id) ? false : true;

			$limit = "'author','manager','developer'";

			if (ExtensionManager::fetchExtensionID('group_lock')){
				$group = ExtensionManager::getInstance('group_lock')->getCurrentGroup();

				if (isset($group)){
					$limit .= ",'{$group}'";
				}
			}

			$where = sprintf(" ( ( `limit` = 'user' AND `author_id` = '%d' ) OR `limit` in (%s) )",
				Symphony::Author()->get('id'), 
				$limit) ;

			if($return_single) {
				// Check static cache for object
				if(in_array($favourite_id, array_keys(favouriteManager::$_pool))) {
					return favouriteManager::$_pool[$favourite_id];
				}

				// No cache object found
				if(!$favourites = Symphony::Database()->fetch(sprintf("
						SELECT * FROM `tbl_favourite_shortcuts` WHERE `id` = %d AND {$where} ORDER BY `id` ASC LIMIT 1",
						$favourite_id
					))
				) return array();
			}
			else {
				$favourites = Symphony::Database()->fetch("SELECT * FROM `tbl_favourite_shortcuts` WHERE {$where} ORDER BY `location`,`limit`,`sort` ASC");
			}

			foreach($favourites as $favourite) {
				if(!in_array($favourite['id'], array_keys(favouriteManager::$_pool))) {
					favouriteManager::$_pool[$favourite['id']] = $favourite; //new favourite($favourite);
				}

				$result[] = favouriteManager::$_pool[$favourite['id']];
			}

			return $return_single ? current($result) : $result;
		}
	}

