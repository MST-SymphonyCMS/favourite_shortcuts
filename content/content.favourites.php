<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once CONTENT . '/class.sortable.php';

	Class contentExtensionFavourite_ShortcutsFavourites extends AdministrationPage {

		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Favourites'))));

			//if developer/manager ? 
			$this->appendSubheading(__('Favourites'), Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a Coupon'), 'create button', NULL, array('accesskey' => 'c')
			));

			$columns[] = array(
				'label' => __('Name'),
				// 'sortable' => true,
				'handle' => 'name',
				'attrs' => array(
					'id' => 'field-name'
				)
			);
			$columns[] = array(
				'label' => __('Group'),
				// 'sortable' => true,
				'handle' => 'group',
				'attrs' => array(
					'id' => 'field-group'
				)
			);
			$columns[] = array(
				'label' => __('Link'),
				// 'sortable' => true,
				'handle' => 'link',
				'attrs' => array(
					'id' => 'field-link'
				)
			);
			$columns[] = array(
				'label' => __('Type'),
				// 'sortable' => true,
				'handle' => 'limit',
				'attrs' => array(
					'id' => 'field-limit'
				)
			);

			$aTableHead = Sortable::buildTableHeaders($columns, $_GET['sort'], $_GET['order'], ($filter_querystring) ? "&amp;" . $filter_querystring : '');

			$aTableBody = array();

			$dateFormat = Symphony::Configuration()->get('date_format','region') . ' ' .  Symphony::Configuration()->get('time_format','region');

			$favourites = FavouriteManager::fetch();

			if(!is_array($favourites) || empty($favourites)){
				$aTableBody = array(Widget::TableRow(
					array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead)))
				));
			} else {

				$i = 0;

				foreach($favourites as $favourite){

					$columns = array();
					if (Symphony::Author()->isDeveloper()){
						$columns[] = Widget::TableData(Widget::Anchor(
							$favourite['name'], Administration::instance()->getCurrentPageURL().'edit/' . $favourite['id'] . '/', null, 'content'
						));
					} else {
						$columns[] = Widget::TableData($favourite['name']);
					}
					$columns[] = Widget::TableData($favourite['location']);
					$columns[] = Widget::TableData($favourite['link']);
					$columns[] = Widget::TableData($favourite['limit'] . "<input type='checkbox' name='items[{$favourite['id']}]' id='entry-{$favourite['id']}'/>");
					
					$aTableBody[] = Widget::TableRow($columns,null, "id-" . $favourite['id'] );

					$i++;
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$scriptContent =	"jQuery(document).ready(function(){
									jQuery('table').symphonySelectable();
								});";
			$script = new XMLElement('script',$scriptContent);
			$this->addElementToHead($script);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				0 => array(null, false, __('With Selected...')),
				1 => array('delete', false, __('Delete'), 'confirm'),
			);

			$tableActions->appendChild(Widget::Apply($options));
			$this->Form->appendChild($tableActions);
		}


		public function __viewNew() {
			$this->__viewEdit();
		}

		public function __viewEdit() {
			$isNew = true;
			$time = Widget::Time();

			// Verify coupon exists
			if($this->_context[0] == 'edit') {
				$isNew = false;

				if(!$favourite_id = $this->_context[1]) redirect(extension_Favourite_Shortcuts::baseURL() . 'favourites/');

				if(!$existing = FavouriteManager::fetch($favourite_id)){
					throw new SymphonyErrorPage(__('The Favourite you resuested was not found.'), __('Favourite not found'));
				}
			}

			// Append any Page Alerts from the form's
			
			// Has the form got any errors?
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert(
				__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR
			);
			else if(isset($this->_context[2])){
				switch($this->_context[2]){
					case 'saved':
						$this->pageAlert(
							__('Coupon updated at %s.', array($time->generate()))
							. ' <a href="' . extension_Favourite_Shortcuts::baseURL() . 'favourites/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . extension_Favourite_Shortcuts::baseURL() . 'favourites/" accesskey="a">'
							. __('View all favourites')
							. '</a>'
							, Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__('Coupon created at %s.', array($time->generate()))
							. ' <a href="' . extension_Favourite_Shortcuts::baseURL() . 'favourites/new/" accesskey="c">'
							. __('Create another?')
							. '</a> <a href="' . extension_Favourite_Shortcuts::baseURL() . 'favourites/" accesskey="a">'
							. __('View all favourites')
							. '</a>'
							, Alert::SUCCESS);
						break;
				}
			}

			$this->setPageType('form');


			if(isset($_POST['fields'])){
				$fields = $_POST['fields'];					
				$fields['author_id'] =  Symphony::Author()->get('id');
			} else
			$fields = array();
			$this->insertBreadcrumbs(array(Widget::Anchor(__('Favourites'), extension_Favourite_Shortcuts::baseURL() . 'favourites/')));
			if($isNew) {
				$this->setTitle(__('Symphony &ndash; Discount favourites'));
				$this->appendSubheading(__('Untitled'));
			}
			else {
				$this->setTitle(__('Symphony &ndash; Discount favourites &ndash; ') . $existing['name']);
				$this->appendSubheading($existing['name']);

				if(empty($fields)){
					$fields = $existing;
				}
			}

			//we might need to add the scripts for the date time stuff
			// $this->addScriptToHead(URL . '/extensions/wondering_jew/assets/newsletter.js');

			if (isset($_SESSION['alert'])){
				$this->pageAlert(
							__($_SESSION['alert']['message'])
							, $_SESSION['alert']['type']);
				unset($_SESSION['alert']);
			}

			$fieldset = new XMLElement('fieldset');

			if (isset($favourite_id))
				$fieldset->appendChild(Widget::Input('id', $favourite_id, 'hidden'));
			


			$primary = new XMLElement('div',null,array('class' => 'primary column'));
			$fieldset->appendChild($primary);

			$label = Widget::Label(__("Name"),Widget::Input("fields[name]", $fields['name']));
			if(isset($this->_errors['name'])) $primary->appendChild(Widget::Error($label, $this->_errors['name']));
			else $primary->appendChild($label);

			$label = Widget::Label(__("Group"),Widget::Input("fields[location]", $fields['location']));
			$primary->appendChild($label);

			$label = Widget::Label(__("Link"),Widget::Input("fields[link]", $fields['link']));
			$primary->appendChild($label);

			$secondary = new XMLElement('div',null,array('class' => 'secondary column'));
			$fieldset->appendChild($secondary);

			$options = array();
			$options[] =  array('user',(!isset($fields['limit']) || $fields['limit'] == 'user'),'User',null,'yes',null);
			$options[] =  array('author',$fields['limit'] == 'author','Author',null,'no',null);
			$options[] =  array('manager',$fields['limit'] == 'manager','Manager',null,'no',null);
			$options[] =  array('developer',$fields['limit'] == 'developer','Developer',null,'no',null);
			$limit = Widget::Select("fields[limit]",  $options);

			$label = Widget::Label(__("Favourite Type"),$limit);
			$secondary->appendChild($label);


			// Add the actions:
			$actions = new XMLElement('div');
			$actions->setAttribute('class', 'actions');
			$actions->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($fieldset);
			$this->Form->setAttribute('class','two columns');
						
			// $actions->appendChild(Widget::Anchor(
			// 	__('Back'), $this->_uri,
			// 	__('Back to List'), 'create button'));
			// $this->Form->appendChild($actions);
			$fieldset->appendChild($actions);
		}

		public function __actionNew() {
			return $this->__actionEdit();
		}

		public function __actionEdit() {

			if(array_key_exists('delete', $_POST['action'])) {
				return $this->__actionDelete($this->_context[1], extension_Favourite_Shortcuts::baseURL() . 'favourites/');
			}

			if(array_key_exists('save', $_POST['action'])) {
				$isNew = ($this->_context[0] !== "edit");
				$fields = $_POST['fields'];

				$fields['author_id'] = Symphony::Author()->get('id');

				unset($fields['date']);
				// var_dump($fields);die;

				// If we are editing, we need to make sure the current `$favourite_id` exists
				if(!$isNew) {
					if(!$favourite_id = $this->_context[1]) redirect(extension_Favourite_Shortcuts::baseURL() . 'favourites/');

					if(!$existing = FavouriteManager::fetch($favourite_id)){
						throw new SymphonyErrorPage(__('The coupon you requested to edit does not exist.'), __('Coupon not found'));
					}
				}

				$name = trim($fields['name']);

				if(strlen($name) == 0){
					$this->_errors['name'] = __('This is a required field');
					return false;
				}

				if(strlen(trim($fields['link'])) == 0){
					$this->_errors['link'] = __('This is a required field');
					return false;
				}

				if(strlen(trim($fields['location'])) == 0){
					$this->_errors['location'] = __('This is a required field');
					return false;
				}

				$data['favourites'] = $fields;

				if($isNew) {
					if($favourite_id = FavouriteManager::add($data)) {
						redirect(extension_Favourite_Shortcuts::baseURL() . 'favourites/edit/' . $favourite_id . '/created/');
					}
				}
				else if(FavouriteManager::edit($favourite_id, $data)) {
					redirect(extension_Favourite_Shortcuts::baseURL() . 'favourites/edit/' . $favourite_id . '/saved/');
				}
			}
		}

		public function __actionDelete($favourite_id = null, $redirect = null, $purge_members = false) {
			if(array_key_exists('delete', $_POST['action'])) {
				if(!$favourite_id) redirect(extension_Favourite_Shortcuts::baseURL() . 'favourites/');

				if(!$existing = FavouriteManager::fetch($favourite_id)) {
					throw new SymphonyErrorPage(__('The coupon you requested to delete does not exist.'), __('Coupon not found'));
				}

				if(!is_null($redirect)) redirect($redirect);
			}
		}

		public function __actionIndex() {

			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			if(is_array($checked) && !empty($checked)) {

				switch ($_POST['with-selected']) {
					case 'delete':
						foreach($checked as $favourite_id) {
							//only if user or has Permissions
							FavouriteManager::delete($favourite_id);
						}
						redirect(extension_Favourite_Shortcuts::baseURL() . '/favourites/');

						break;
				}
			}
		}
	}
