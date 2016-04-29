<?php
class ControllerModuleExchange1c extends Controller {
	private $error = array(); 
	private $module_name = 'Exchange 1C 8.x';

	/**
	 * Пишет в файл журнала если включена настройка
	 * @param	string,array()	Сообщение или объект
	 */
	private function log($message) {
		if ($this->config->get('exchange1c_full_log')) $this->log->write(print_r($message,true));
	} // log()


	/**
	 * Выводит сообщение
	 */
	private function echo_message($ok, $message="") {
		if ($ok) {
			echo "success\n";
			$this->log("[ECHO] success");
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message);
			}
		} else {
			echo "failure\n";
			$this->log("[ECHO] failure");
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message);
			}
		};
	} // echo_message()


	/**
	 * Основная функция 
	 */
	public function index() {

		$data['lang'] = $this->load->language('module/exchange1c');
		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
			
		$this->load->model('tool/exchange1c');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// При нажатии кнопки сохранить
			$settings = $this->request->post;
			$settings['exchange1c_version'] = $this->config->get('exchange1c_version');
			$settings['exchange1c_order_date'] = $this->config->get('exchange1c_order_date');
			
			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		} else {
			$settings = $this->model_setting_setting->getSetting('exchange1c', 0);
			$data['update'] = "";
			
			if (!isset($settings['exchange1c_version'])) {
				// Чистая установка
				$this->install();
				$this->load->model('extension/extension');
				$this->model_extension_extension->install('module', 'exchange1c');
				$data['update'] = "Модуль установлен";
			} else {
				// Нужно ли обновлять
				if (version_compare($settings['exchange1c_version'], $this->model_tool_exchange1c->version(), '<')) {
					$data['update'] = $this->model_tool_exchange1c->update($settings);
				}
			}
		}

		$settings = $this->model_setting_setting->getSetting('exchange1c', 0);
		$data['version'] = $settings['exchange1c_version'];

		$data = $this->setParams($data, 'config_icon');

		// error_warning
		$data = $this->setParamsError($data, 'warning');

		// Проверка базы данных
		$data['error_warning'] .= $this->model_tool_exchange1c->checkDB();

		$data = $this->setParamsError($data, 'image');
		$data = $this->setParamsError($data, 'exchange1c_username');
		$data = $this->setParamsError($data, 'exchange1c_password');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_home'),
			'href'		=> $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> false
		);
		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_module'),
			'href'		=> $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
		$data['token'] = $this->session->data['token'];
		$data['action'] = $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		// ОБЩИЕ

		// Магазины
		$data['stores'] = array();
		$data['store_default'] = $this->config->get('config_name');
		$store_id = 1;
		$result = $this->db->query("SELECT * FROM `" . DB_PREFIX . "store`")->rows;
		foreach ($result as $store) {
			$data['stores'][] = array(
				'store_id'	=> $store_id,
				'name'		=> $store['name']
			);
			$store_id++;
		}

		if (isset($this->request->post['exchange1c_stores'])) {
			$data['exchange1c_stores'] = $this->request->post['exchange1c_stores'];
		}
		else {
			$data['exchange1c_stores'] = $this->config->get('exchange1c_stores');
			if(empty($data['exchange1c_stores'])) {
				$data['exchange1c_stores'][] = array(
					'keyword'		=> '',
					'name'			=> $this->config->get('config_name'),
					'store_id'		=> 0
				);
			}
		}

		// АВТОРИЗАЦИЯ

		// пользователь
		$data = $this->formText('exchange1c_username', $data);
		// пароль
		$data = $this->formText('exchange1c_password', $data);

		// БЕЗОПАСНОСТЬ
		
		// ip адреса
		$data = $this->formTextarea('exchange1c_allow_ip', $data);

		// ПРОЧЕЕ

		// статус
		$data = $this->formSelectEnableDisable('exchange1c_status', $data);

		// лог файл
		$data = $this->formRadioYesNo('exchange1c_full_log', $data);

		$exchange1c_file_zip_list = array(
			'zip'	=> 'Все в одном архиве',
			'files'	=> 'Каждый файл по отдельности' 		
		);
		$data = $this->formSelect('exchange1c_file_zip', $data, $exchange1c_file_zip_list);
		
		/**
		 * ТОВАРЫ
		 */
		// Загрузка товаров в разных валютах
		$this->load->model('localisation/currency');
		// список валют
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
		// валюта по-умолчанию
		$data['currency_default'] = $this->model_localisation_currency->getCurrencyByCode($this->config->get('config_currency'));

		if (isset($this->request->post['exchange1c_currency'])) {
			$data['exchange1c_currency'] = $this->request->post['exchange1c_currency'];
		}
		else {
			$data['exchange1c_currency'] = $this->config->get('exchange1c_currency');
			if(empty($data['exchange1c_currency'])) {
				$data['exchange1c_currency'][] = array(
					'currency_id'		=> $data['currency_default']['currency_id'],
					'name1c'			=> '',
					'code'		=> $data['currency_default']['code'] 
				);
			}
		}

		// Группы покупателей
		if (version_compare(VERSION, '2.0.3.1', '>')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		} else {
			$this->load->model('sale/customer_group');
			$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		}
		
		// типы цен
		if (isset($this->request->post['exchange1c_price_type'])) {
			$data['exchange1c_price_type'] = $this->request->post['exchange1c_price_type'];
		}
		else {
			$data['exchange1c_price_type'] = $this->config->get('exchange1c_price_type');
			if(empty($data['exchange1c_price_type'])) {
				$data['exchange1c_price_type'][] = array(
					'keyword'			=> '',
					'customer_group_id'	=> $this->config->get('config_customer_group_id'),
					'quantity'			=> 1,
					'priority'			=> 1
				);
			}
		}

		// очищать остатки
		$data = $this->formRadioYesNo('exchange1c_flush_quantity', $data);

		// КАРТИНКИ
		
		// водяные знаки
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data = $this->setParams($data, 'exchange1c_watermark');
		if ($data['exchange1c_watermark']) {
			$data['thumb'] = $this->model_tool_image->resize($data['exchange1c_watermark'], 100, 100);
		}
		else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}
		
		// ОБНОВЛЯТЬ ПРИ ИМПОРТЕ

		// товары - список возможныйх полей
		$product_fields = array(
			'images'	=> $this->language->get('text_product_field_images'),
			'category'  => $this->language->get('text_product_field_category'),
			'name'  	=> $this->language->get('text_product_field_name')
		);
		// товары - чтение настроек
		$data = $this->formCheckBox('exchange1c_product_fields_update', $data, $product_fields);

		// ПРОЧЕЕ
		
		// загружать только типы номенклатуры
		$data = $this->formText('exchange1c_parse_only_types_item', $data);
		
		// заполнять родительские категории
		$data = $this->formRadioYesNo('exchange1c_fill_parent_cats', $data);
		
		// Статус товара по умолчанию при отсутствии
		$this->load->model('localisation/stock_status');
		$stock_statuses_info = $this->model_localisation_stock_status->getStockStatuses();
		$stock_statuses = array();
		foreach ($stock_statuses_info as $status) {
			$stock_statuses[$status['stock_status_id']] = $status['name'];
				
		}
		unset($stock_statuses_info);

		$data = $this->formSelect('exchange1c_default_stock_status', $data, $stock_statuses);
		

		// Отключать товар если остаток меньше или равен нулю
		$data = $this->formRadioYesNo('exchange1c_product_disable_if_zero', $data);

		// не искать по артикулам
		$data = $this->formRadioYesNo('exchange1c_dont_use_artsync', $data);

		// в наименование товара загружать короткое или полное наименование с 1С		
		// товары - список возможныйх полей
		$product_name_fields = array(
			'name'		=> $this->language->get('text_product_name'),
			'fullname'	=> $this->language->get('text_product_fullname')
		);
		$data = $this->formSelect('exchange1c_product_name_field', $data, $product_name_fields);
		
		// связанные опции
		//$data = $this->formRadioYesNo('exchange1c_relatedoptions', $data);

		// синхронизировать XML_ID 1С с ID Opencart
		$data = $this->formRadioYesNo('exchange1c_synchronize_uuid_to_id', $data);
		

		/**
		 * SEO
		 */
		// SEO товары
		if (isset($this->request->post['exchange1c_seo_product_tags'])) {
			$data['exchange1c_seo_product_tags'] = $this->request->post['exchange1c_seo_product_tags'];
		} else {
			$data['exchange1c_seo_product_tags'] = '{name}, {sku}, {brand}, {desc}, {cats}, {price}, {prod_id}, {cat_id}';
		}
		$list_product = array(
			'disable'		=> $this->language->get('text_disable'),
			'template'		=> $this->language->get('text_template')
			//'import'		=> $this->language->get('text_import')
		); 
		$list_category = array(
			'disable'		=> $this->language->get('text_disable'),
			'template'		=> $this->language->get('text_template')
		); 
		$list_overwrite = array(
			'if_empty'		=> $this->language->get('text_seo_if_empty'),
			'overwrite'		=> $this->language->get('text_seo_overwrite')
		);
		// Перезапись
		$data = $this->formSelect('exchange1c_seo_product_overwrite', $data, $list_overwrite, array(1,2,9));
		// поле seo-url для ЧПУ
		$data = $this->formSelect('exchange1c_seo_product_seo_url', $data, $list_product, array(1,2,0));
		$data = $this->setParams($data, 'exchange1c_seo_product_seo_url_import', 'seo_url');
		$data = $this->formText('exchange1c_seo_product_seo_url_import', $data, array(0,9,0), 'hidden');
		$data = $this->formText('exchange1c_seo_product_seo_url_template', $data, array(0,9,0));
		// поле meta-title
		$data = $this->formSelect('exchange1c_seo_product_meta_title', $data, $list_product, array(1,2,0));
		$data = $this->setParams($data, 'exchange1c_seo_product_meta_title_import', 'seo_title');
		$data = $this->formText('exchange1c_seo_product_meta_title_import', $data, array(0,9,0), 'hidden');
		$data = $this->formText('exchange1c_seo_product_meta_title_template', $data, array(0,9,0));
		// поле meta-description
		$data = $this->formSelect('exchange1c_seo_product_meta_description', $data, $list_product, array(1,2,0));
		$data = $this->setParams($data, 'exchange1c_seo_product_meta_description_import', 'seo_description');
		$data = $this->formText('exchange1c_seo_product_meta_description_import', $data, array(0,9,0), 'hidden');
		$data = $this->formText('exchange1c_seo_product_meta_description_template', $data, array(0,9,0));
		// поле keywords
		$data = $this->formSelect('exchange1c_seo_product_meta_keyword', $data, $list_product, array(1,2,0));
		$data = $this->setParams($data, 'exchange1c_seo_product_meta_keyword_import', 'seo_keyword');
		$data = $this->formText('exchange1c_seo_product_meta_keyword_import', $data, array(0,9,0), 'hidden');
		$data = $this->formText('exchange1c_seo_product_meta_keyword_template', $data, array(0,9,0));

		// SEO категории
		if (isset($this->request->post['exchange1c_seo_category_tags'])) {
			$data['exchange1c_seo_category_tags'] = $this->request->post['exchange1c_seo_category_tags'];
		} else {
			$data['exchange1c_seo_category_tags'] = '{cat}, {cat_id}';
		}
		// Перезапись
		$data = $this->formSelect('exchange1c_seo_category_overwrite', $data, $list_overwrite, array(1,2,9));
		// поле seo-url для ЧПУ
		$data = $this->formSelect('exchange1c_seo_category_seo_url', $data, $list_category, array(1,2,0));
		$data = $this->formText('exchange1c_seo_category_seo_url_template', $data, array(0,9,0));
		// поле meta-title
		$data = $this->formSelect('exchange1c_seo_category_meta_title', $data, $list_category, array(1,2,0));
		$data = $this->formText('exchange1c_seo_category_meta_title_template', $data, array(0,9,0));
		// поле meta-description
		$data = $this->formSelect('exchange1c_seo_category_meta_description', $data, $list_category, array(1,2,0));
		$data = $this->formText('exchange1c_seo_category_meta_description_template', $data, array(0,9,0));
		// поле keyword
		$data = $this->formSelect('exchange1c_seo_category_meta_keyword', $data, $list_category, array(1,2,0));
		$data = $this->formText('exchange1c_seo_category_meta_keyword_template', $data, array(0,9,0));
		// SEO Производители
		// SEO товары
		if (isset($this->request->post['exchange1c_seo_manufacturertags'])) {
			$data['exchange1c_seo_manufacturer_tags'] = $this->request->post['exchange1c_seo_manufacturer_tags'];
		} else {
			$data['exchange1c_seo_manufacturer_tags'] = '{brand}, {brand_id}';
		}
		// Перезапись
		$data = $this->formSelect('exchange1c_seo_manufacturer_overwrite', $data, $list_overwrite, array(1,2,9));
		// поле seo-url для ЧПУ
		$data = $this->formSelect('exchange1c_seo_manufacturer_seo_url', $data, $list_category, array(1,2,0));
		$data = $this->formText('exchange1c_seo_manufacturer_seo_url_template', $data, array(0,9,0));

		/**
		 * ЗАКАЗЫ
		 */
		// список статусов заказов
		$this->load->model('localisation/order_status');
		$order_statuses_info = $this->model_localisation_order_status->getOrderStatuses();
		$order_statuses = array();
		$order_statuses[] = $this->language->get('text_order_status_to_exchange_not');
		foreach ($order_statuses_info as $order_status) {
			$order_statuses[$order_status['order_status_id']] = $order_status['name'];
		}
		// + 1.6.1.7
		unset($order_statuses_info);

		$data = $this->formSelect('exchange1c_order_status_to_exchange', $data, $order_statuses);
		$data = $this->formSelect('exchange1c_order_status_change', $data, $order_statuses);
		$data = $this->formSelect('exchange1c_order_status_canceled', $data, $order_statuses);
		$data = $this->formSelect('exchange1c_order_status_completed', $data, $order_statuses);
		$data = $this->formText('exchange1c_order_currency', $data);
		$data = $this->formRadioYesNo('exchange1c_order_notify', $data);

		/**
		 * РУЧНАЯ ОБРАБОТКА
		 */
	 	// максимальный размер загружаемых файлов
		$data['lang']['text_max_filesize'] = sprintf($this->language->get('text_max_filesize'), @ini_get('max_file_uploads'));
		$data['upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['post_max_size'] = ini_get('post_max_size');
		

		/**
		 * РАЗРАБОТКА
		 */
	 	// информация о памяти
		$data['memory_limit'] = ini_get('memory_limit');

		// Вывод шаблона
		
		$this->template = 'module/exchange1c.tpl';
		$this->children = array(
			'common/header',
			'common/footer'	
		);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('module/exchange1c.tpl', $data));
	} // index()


	/**
	 * Проверка разрешения на изменение 
	 */
	private function validate() {
		if (!$this->user->hasPermission('modify', 'module/exchange1c'))
			$this->error['warning'] = $this->language->get('error_permission');
		if (!$this->error) return true;
		else return false;
	} // validate()


	/**
	 * Установка модуля 
	 */
	public function install() {
		
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->setEvents();
		$module_version = $this->model_tool_exchange1c->version();
		
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('exchange1c',
			array(
				'exchange1c_version'	=> $module_version,
				'exchange1c_name'		=> 'Exchange 1C 8.x for OpenCart 2.x'
			)
		);


//		$this->load->model('extension/module');
//		$this->model_extension_module->addModule('exchange1c', 
//			array(
//				'version'	=> $this->module_version,
//				'name'		=> $this->module_name
//			)
//		);
	
		// Связь товаров с 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'product_to_1c` (
							`product_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`product_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`product_id`) REFERENCES `'. DB_PREFIX .'product`(`product_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		// Связь категорий с 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "category_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'category_to_1c` (
							`category_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`category_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`category_id`) REFERENCES `'. DB_PREFIX .'category`(`category_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
	
		// Свойства из 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "attribute_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'attribute_to_1c` (
							`attribute_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`attribute_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`attribute_id`) REFERENCES `'. DB_PREFIX .'attribute`(`attribute_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		// Характеристики из 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "option_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'option_to_1c` (
							`option_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`option_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`option_id`) REFERENCES `'. DB_PREFIX .'option`(`option_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// Привязка производителя к каталогу 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'manufacturer_to_1c` (
							`manufacturer_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`manufacturer_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`manufacturer_id`) REFERENCES `'. DB_PREFIX .'manufacturer`(`manufacturer_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// Привязка магазина к каталогу 1С
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "store_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'store_to_1c` (
							`store_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`store_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`store_id`) REFERENCES `'. DB_PREFIX .'store`(`store_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// остатки по складам
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "product_quantity'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'product_quantity` (
						`product_id` int(11) NOT NULL,
						`warehouse_id` int(11) NOT NULL,
						`quantity` int(10) DEFAULT 0,
						KEY (`product_id`),
						KEY (`warehouse_id`)                            
					) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		// склады
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "warehouse'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'warehouse` (
							`warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
							`name` varchar(100) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
                            PRIMARY KEY (`warehouse_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		$this->log->write("Включен модуль " . $this->module_name . " версии " . $this->model_tool_exchange1c->version());
	} // install()


	/**
	 * Деинсталляция
	 */
	public function uninstall() {
		
		$this->load->model('extension/event');
		$this->model_extension_event->deleteEvent('exchange1c');
		
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('exchange1c');

		//$this->load->model('extension/modification');
		//$modification = $this->model_extension_modification->getModificationByCode('exchange1c');
		//if ($modification) $this->model_extension_modification->deleteModification($modification['modification_id']);
		
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "option_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "manufacturer_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
		
		// Удаляем и файлы
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/export/exchange1c.php'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/export/exchange1c.php');
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/controller/module/exchange1c.php'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/controller/module/exchange1c.php');
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/model/tool/exchange1c.php'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/model/tool/exchange1c.php');
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/language/english/module/exchange1c.php'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/language/english/module/exchange1c.php');
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/language/russian/module/exchange1c.php'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/language/russian/module/exchange1c.php');
//		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/view/template/module/exchange1c.tpl'))
//			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/view/template/module/exchange1c.tpl');
			
		$this->log->write("Отключен модуль " . $this->module_name);
	} // uninstall()
	

	/**
	 * Выводит сообщение в лог с инструкцией, если авторизация не удалась
	 */
	private function authWarning() {
		// Проверим есть ли в файле .htaccess строчки:
		// RewriteCond %{HTTP:Authorization} ^Basic.*
		// RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
		$htaccess = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
		if ($fp = fopen($htaccess, 'r')) {
			$buffer = fread($fp, 4096);
			fclose($fp);
			if (!strpos($buffer, 'RewriteCond %{HTTP:Authorization} ^Basic.*')) {
				$this->log->write("ВНИМАНИЕ! при ошибке авторизации добавьте в файл .htaccess следующие строки:");
				$this->log->write("RewriteCond %{HTTP:Authorization} ^Basic.*");
				$this->log->write("RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]");
			}
		}
	} // authWarning()


	/**
	 * Проверка доступа с IP адреса
	 */
	private function checkAccess($echo = false) {
		// Проверяем включен или нет модуль
		if (!$this->config->get('exchange1c_status')) {
			if ($echo) $this->echo_message(0, "The module is disabled");
			return false;
		}
		// Разрешен ли IP
		if ($this->config->get('exchange1c_allow_ip') != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));
			if (!in_array($ip, $allow_ips)) {
				if ($echo) $this->echo_message(0, "From Your IP address are not allowed");
				return false;
			}
		}
		return true;
	} // checkAccess()
	

	/**
	 * Режим проверки авторизации через http запрос
	 */
	public function modeCheckauth() {
		if (!$this->checkAccess(true))
			exit;
		// Авторизуем
		if (($this->config->get('exchange1c_username') != '') && (@$_SERVER['PHP_AUTH_USER'] != $this->config->get('exchange1c_username'))) {
			$this->echo_message(0, "Incorrect login");
		}
		if (($this->config->get('exchange1c_password') != '') && (@$_SERVER['PHP_AUTH_PW'] != $this->config->get('exchange1c_password'))) {
			$this->echo_message(0, "Incorrect password");
			exit;
		}
		$this->echo_message(1, "key\n");
		echo md5($this->config->get('exchange1c_password')) . "\n";
	} // modeCheckauth()

	/**
	 * Очистка базы данных через админ-панель
	 */
	public function manualCleaning() {
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'module/exchange1c'))  {
			$this->load->model('tool/exchange1c');
			$result = $this->model_tool_exchange1c->cleanDB();
			if (!$result) {
				$json['error'] = "Таблицы не были очищены";
			} else {
				$json['success'] = "Успешно очищены таблицы: \n" . $result;
			}
		} else {
			$json['error'] = "У Вас нет прав на изменение!";
		}
		
		$this->load->language('module/exchange1c');
		
		$this->response->setOutput(json_encode($json));
	} // manualCleaning()
	
	
	/**
	 * Поиск папки по имени во всех вложениях в папке кэша
	 */
	private function findFolder($folder, $folder_name) {
		$dir = opendir($folder);
		while ($file_dir = readdir($dir)) {
			if($file_dir == "." || $file_dir == "..") continue;
			if(is_dir($folder . $file_dir)){
				if ($file_dir == $folder_name) {
					return $folder;
				}
				$found_folder = $this->findFolder($folder . $file_dir . "/", $folder_name);
				if ($found_folder) {
					return $found_folder;
				}
			}
		}
		closedir($dir);
		return 0;
	} // findFolder()
	

	/**
	 * Импорт файла через админ-панель
	 */
	private function moveImages($from, $to) {
		`cp -a $from $to`;
		`rm -rf $from`;
		return;
	}


	/**
	 * Импорт файла через админ-панель
	 */
	public function manualImport() {
		$this->load->language('module/exchange1c');
		$cache = DIR_CACHE . 'exchange1c/';
		$json = array();
		$no_error = 0;

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {

			$filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));
			$zip = new ZipArchive;
			if ($zip->open($this->request->files['file']['tmp_name']) === TRUE) {
				$max_size_file = $this->modeCatalogInit(array(),FALSE);
				//$this->log($this->request->files['file']);
				
				$xmlfiles = $this->extractArchive($zip);
				$zip->close();
				//unlink($this->request->files['file']['tmp_name']);
					
				foreach ($xmlfiles as $file) {
					if (is_file($cache . $file)) {
						$no_error = $this->modeImport($cache . $file);
					}
				}
			}
			else {
				$import_file = $cache . $this->request->files['file']['name'];
				move_uploaded_file($this->request->files['file']['tmp_name'], $import_file);
				$this->log("Загружен файл: " . $this->request->files['file']['name']);
				$no_error = $this->modeImport($import_file);
				//unlink($import_file);
			}
		}
		if ($no_error) {
			$json['success'] = $this->language->get('text_upload_success');
		} else {
			$json['error'] = $this->language->get('text_upload_error');
		}
		
		$this->cleanCacheDir();
		$this->cache->delete('product');
		$this->response->setOutput(json_encode($json));

	} // manualImport()


	/**
	 * Проверяет наличие куки ключа
	 */
	private function checkAuthKey($echo=true) {

		if (!isset($this->request->cookie['key'])) {
			if ($echo) $this->echo_message(0, "no cookie key");
			return false;
		}
		if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
			if ($echo) $this->echo_message(0, "Session error");
			return false;
		}
		return true;
	}
	

	/**
	 * Возвращает максимальный объем файла в байта для загрузки
	 */
	private function getPostMaxFileSize() {
		$size = ini_get('post_max_size');
		$type = $size{strlen($size)-1};
		$size = (integer)$size;
		switch ($type) {
			case 'K': $size = $size*1024;
				break;
			case 'M': $size = $size*1024*1024;
				break;
			case 'G': $size = $size*1024*1024*1024;
				break;
		}
		return $size;
	}

	/**
	 * Обрабатывает команду инициализации каталога
	 */
	public function modeCatalogInit($param = array(), $echo = true) {

		// Включена проверка на запись файлов и папок
		$test_file = DIR_CACHE . 'exchange1c/test.php';
		if ($fp = fopen($test_file, "w")) {
			fclose($fp);
			unlink($test_file);
		} else {
			if ($echo) $this->echo_message(0, "The folder " . DIR_CACHE . " is not writable!");
			return 0;
		}

		$test_file = DIR_IMAGE . 'import_files/test.php';
		if ($fp = fopen($test_file, "w")) {
			fclose($fp);
			unlink($test_file);
		} else {
			if ($echo) $this->echo_message(0, "The folder " . DIR_IMAGE . " is not writable!");
			return 0;
		}

		if ($echo) {
			if ($this->config->get('exchange1c_file_zip') == 'zip') {
				echo "zip=yes\n";
			} else {
				echo "zip=no\n";
			}
			echo "file_limit=" . $this->getPostMaxFileSize() . "\n";
		}
		$this->log("file_limit = " . $this->getPostMaxFileSize());
		return $this->getPostMaxFileSize();
	} // modeCatalogInit()


	/**
	 * Обрабатывает команду инициализации продаж
	 */
	public function modeSaleInit() {
		if ($this->config->get('exchange1c_file_zip') == 'zip') {
			echo "zip=yes\n";
		} else {
			echo "zip=no\n";
		}
		echo "file_limit=" . $this->getPostMaxFileSize() . "\n";
	} // modeSaleInit()
	

	/**
	 * Распаковываем содержимое архива по "полочкам"
	 */
	private function extractArchive($zip) {
		
		$cache = DIR_CACHE . 'exchange1c';

		$xmlfiles = array();
		$imgfiles = array();

		for($i = 0; $i < $zip->numFiles; $i++) {
			$entry = $zip->getNameIndex($i);
			if (strpos($entry, "mport_files/")) {
				$imgfiles[] = $entry;
				$this->log("Картинка: " . $entry);
				continue;
			}
			$this->log("XML файл: " . $entry);
			$xmlfiles[] = $entry;
		}

		if (count($xmlfiles)) {
			$this->log("Распаковка *.xml в папку " . $cache . "...");
			if ($zip->extractTo($cache, $xmlfiles) === TRUE) {
				$this->log("XML успешно распакованы");
			}
		}

		if (count($imgfiles)) {
			$this->log("Распаковка картинок в папку " . DIR_IMAGE . "...");
			if ($zip->extractTo(DIR_IMAGE, $imgfiles) === TRUE) {
				$this->log("Картинки успешно распакованы");
			}
		}
		return $xmlfiles;
		
	} // extractArchive()


	/**
	 * Обрабатывает загруженный файл на сервер
	 */
	public function modeFile() {
		if (!$this->checkAuthKey()) exit;
		$cache = DIR_CACHE . 'exchange1c/';
		
		// Проверяем на наличие каталога
		if(!is_dir($cache)) mkdir($cache);

		// Проверяем на наличие имени файла
		if (isset($this->request->get['filename'])) {
			$uplod_file = $cache . $this->request->get['filename'];
		}
		else {
			$this->log(false, "No file name variable");
			exit;
		}

		// Проверяем XML или изображения
		if (strpos($this->request->get['filename'], 'import_files') !== false) {
			$cache = DIR_IMAGE;
			$uplod_file = $cache . $this->request->get['filename'];
			$this->checkUploadFileTree(dirname($this->request->get['filename']) , $cache);
		}
		
		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (!is_writable($cache)) {
			$this->log('Папка ' . $cache . ' не доступна для записи');
			$this->echo_message(0, "The folder " . $cache . " is not writable!");
			exit;
		}

		// Получаем данные
		$data = file_get_contents("php://input");

		if ($data !== false) {
			file_put_contents($uplod_file, $data);
			if ($fp = fopen($uplod_file, "wb")) {
				$result = fwrite($fp, $data);
				if ($result === strlen($data)) {
					chmod($uplod_file , 0664);
					$this->echo_message(1, "The file " . $this->request->get['filename'] . " has been successfully uploaded");
					$zip = new ZipArchive;
					if ($zip->open($uplod_file) === TRUE) {
						$xmlfiles = $this->extractArchive($zip);
						$zip->close();
					}
					//unlink($uplod_file);
				}
				else {
					$this->echo_message(0, "Empty file " . $this->request->get['filename']);
				}
			}
			else {
				$this->echo_message(0, "Can not open file " . $this->request->get['filename']);
			}
		}
		else {
			$this->echo_message(0, "No data" . $this->request->get['filename']);
		}

	} // modeFile()


	/**
	 * Обрабатывает *.XML файлы
	 *
	 * @param	boolean		true - ручной импорт
	 */
	public function modeImport($manual = false) {

		if ($manual) $this->log("[i] Ручная загрузка данных.");
		
		$cache = DIR_CACHE . 'exchange1c/';
		if(!is_dir($cache)) mkdir($cache);
		
		// Определим имя файла
		if ($manual)
			$importFile = $manual;
		elseif (isset($this->request->get['filename']))
			$importFile = $cache . $this->request->get['filename'];
		else {
			if (!$manual) $this->echo_message(0, "No import file name");
			return 0;
		}

		// Определяем текущую локаль
		$this->load->model('tool/exchange1c');
		$language_id = $this->model_tool_exchange1c->getLanguageId($this->config->get('config_language'));

		// Загружаем файл
		if (!$this->model_tool_exchange1c->importFile($importFile)) {
			if (!$manual) {
				$this->echo_message(0, "Error processing file " . $importFile);
			}
			return 0;
		} else {
			if (!$manual) {
				$this->echo_message(1, "Successfully processed file " . $importFile);
			}
		}
		
		$this->cache->delete('product');
		return 1;
	} // modeImport()

	/**
	 * Режим запроса заказов
	 */
	public function modeQueryOrders() {
		if (!$this->checkAuthKey(true)) exit;

		$this->load->model('tool/exchange1c');

		$orders = $this->model_tool_exchange1c->queryOrders(
			array(
				 'from_date' 	=> $this->config->get('exchange1c_order_date')
				,'exchange_status'	=> $this->config->get('exchange1c_order_status_to_exchange')
				,'new_status'	=> $this->config->get('exchange1c_order_status')
				,'notify'		=> $this->config->get('exchange1c_order_notify')
				,'currency'		=> $this->config->get('exchange1c_order_currency') ? $this->config->get('exchange1c_order_currency') : 'руб.'
			)
		);
		echo iconv('utf-8', 'cp1251', $orders);
	}

	/**
	 * Изменение статусов заказов
	 */
	public function modeOrdersChangeStatus(){
		if (!$this->checkAuthKey(true)) exit;
		$this->load->model('tool/exchange1c');

		$result = $this->model_tool_exchange1c->queryOrdersStatus(array(
			'from_date' 		=> $this->config->get('exchange1c_order_date'),
			'exchange_status'	=> $this->config->get('exchange1c_order_status_to_exchange'),
			'new_status'		=> $this->config->get('exchange1c_order_status'),
			'notify'			=> $this->config->get('exchange1c_order_notify')
		));

		if($result){
			$this->load->model('setting/setting');
			$config = $this->model_setting_setting->getSetting('exchange1c');
			$config['exchange1c_order_date'] = date('Y-m-d H:i:s');
			$this->model_setting_setting->editSetting('exchange1c', $config);
		}

		$this->echo_message(1,$result);
	}


	// -- Системные процедуры
	/**
	 * Очистка папки cache
	 */
	private function cleanCacheDir() {
		// Проверяем есть ли директория
		if (file_exists(DIR_CACHE . 'exchange1c')) {
			if (is_dir(DIR_CACHE . 'exchange1c')) {
				return $this->cleanDir(DIR_CACHE . 'exchange1c/');
			}
			else { 
				unlink(DIR_CACHE . 'exchange1c');
			}
		}
		mkdir (DIR_CACHE . 'exchange1c'); 
	} // cleanCacheDir()


	/**
	 * Проверка дерева каталога для загрузки файлов
	 */
	private function checkUploadFileTree($path, $curDir = null) {
		if (!$curDir) $curDir = DIR_CACHE . 'exchange1c/';
		foreach (explode('/', $path) as $name) {
			if (!$name) continue;
			if (file_exists($curDir . $name)) {
				if (is_dir( $curDir . $name)) {
					$curDir = $curDir . $name . '/';
					continue;
				}
				unlink ($curDir . $name);
			}
			mkdir ($curDir . $name );
			$curDir = $curDir . $name . '/';
		}
	} // checkUploadFileTree()

	
	/**
	 * Очистка папки рекурсивно
	 */
	private function cleanDir($root, $self = false) {
		$dir = dir($root);
		while ($file = $dir->read()) {
			if ($file == '.' || $file == '..') continue;
			if (file_exists($root . $file)) {
				if (is_file($root . $file)) { unlink($root . $file); continue; }
				if (is_dir($root . $file)) { $this->cleanDir($root . $file . '/', true); continue; }
				var_dump ($file);	
			}
			var_dump($file);
		}
		if ($self) {
			if(file_exists($root) && is_dir($root)) {
				rmdir($root); return 0;
			}
			var_dump($root);
		}
		return 0;
	} // cleanDir()
	
	
	/**
	 * События
	 */
	public function eventDeleteProduct($product_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->deleteLinkProduct($product_id);
	} // eventProductDelete()
	

	/**
	 * События
	 */
	public function eventDeleteCategory($category_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->deleteLinkCategory($category_id);
	} // eventCategoryDelete()


	/**
	 * События
	 */
	public function eventDeleteManufacturer($manufacturer_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->deleteLinkManufacturer($manufacturer_id);
	} // eventProductDelete()


	/**
	 * События
	 */
	public function eventDeleteOption($option_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->deleteLinkOption($option_id);
	} // eventProductDelete()


	/**
	 * Определяет значение переменной ошибки
	 */
	private function setParamsError($data, $param) {
		if (isset($this->request->post[$param])) {
			$data['error_'.$param] = $this->request->post[$param];
		} else {
			$data['error_'.$param] = '';
		}
		return $data;		
	} // setParamsError()

	/**
	 * Определяет значение переменной
	 */
	private function setParams($data, $param, $default='') {
		if (isset($this->request->post[$param])) {
			$data[$param] = $this->request->post[$param];
		} else {
			if ($this->config->get($param)) {
				$data[$param] = $this->config->get($param);
			} else {
				if ($default) {
					$data[$param] = $default;
				} else {
					$data[$param] = '';
				}
			}
		}
		return $data;		
	} // setParams()
	

	/**
	 * Определяет значение переменной
	 */
	private function getParams($param) {
		if (isset($this->request->post[$param])) {
			return $this->request->post[$param];
		} else {
			return $this->config->get($param);
		}
	}
	
	private function formRadioYesNo($name, $data) {
		$value = $this->getParams($name);
		$param_name = substr($name,11);		
		$description = $this->language->get('desc_'.$param_name);
		$label_name = $this->language->get('entry_'.$param_name);
		$tmpl = '';
		if ($label_name <> 'entry_'.$param_name) {
			$tmpl .= '<label class="col-sm-2 control-label">'.$label_name.'</label>';
		}
		$tmpl .= '<div class="col-sm-3">'; 
		$tmpl .= '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="'.$name.'" value="1"'.($value ? ' checked = "checked"' : '').'>';
		$tmpl .= '&nbsp;'.$this->language->get('text_yes');
		$tmpl .= '</label>';
		$tmpl .= '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="'.$name.'" value="0"'.($value ? '' : ' checked = "checked"').'>';
		$tmpl .= '&nbsp;'.$this->language->get('text_no');
		$tmpl .= '</label></div>';
		if ($description <> 'desc_'.$param_name) {
			$tmpl .= '<div class="col-sm-7">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}

	private function formSelectYesNo($name, $data, $width_label=2, $width=3, $width_desc=7) {
		$value = $this->getParams($name);		
		$param_name = substr($name,11);		
		$description = $this->language->get('desc_'.$param_name);
		$label_name = $this->language->get('entry_'.$param_name);
		$tmpl = '';
		if ($label_name <> 'entry_'.$param_name) {
			$tmpl .= '<label class="col-sm-'.$width_label.' control-label">'.$label_name.'</label>';
		}
		$tmpl .= '<div class="col-sm-'.$width.'">'; 
		$tmpl .= '<select name="'.$name.'" id="'.$name.'" class="form-control">';
		$tmpl .= '<option value="0" '.(!$value?'selected="selected"':'').'>'.$this->language->get('text_no').'</option>';
		$tmpl .= '<option value="1" '.($value?'selected="selected"':'').'>'.$this->language->get('text_yes').'</option>';
		$tmpl .= '</select></div>';
		if ($description <> 'desc_'.$param_name) {
			$tmpl .= '<div class="col-sm-'.$width_desc.'">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}

	private function formSelectEnableDisable($name, $data) {
		$value = $this->getParams($name);
		$param_name = substr($name,11);		
		$description = $this->language->get('desc_'.$param_name);
		$label_name = $this->language->get('entry_'.$param_name);
		$tmpl = '';
		if ($label_name <> 'entry_'.$param_name) {
			$tmpl = '<label class="col-sm-2 control-label">'.$label_name.'</label>';
		}
		$tmpl .= '<div class="col-sm-3">'; 
		$tmpl .= '<select name="'.$name.'" id="'.$name.'" class="form-control">';
		$tmpl .= '<option value="0" '.(!$value?'selected="selected"':'').'>'.$this->language->get('text_disabled').'</option>';
		$tmpl .= '<option value="1" '.($value?'selected="selected"':'').'>'.$this->language->get('text_enabled').'</option>';
		$tmpl .= '</select></div>';
		if ($description <> 'desc_'.$param_name) {
			$tmpl .= '<div class="col-sm-7">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}

	private function formText($name, $data, $width=array(), $type='text') {
		$value = $this->getParams($name);
		$param_name = substr($name,11);

		$width_label = isset($width[0]) ? $width[0] : 2;
		$width_text = isset($width[1]) ? $width[1] : 3;
		$width_desc = isset($width[2]) ? $width[2] : 7;

		$description = $this->language->get('desc_'.$param_name);
		$placeholder = $this->language->get('placeholder_'.$param_name);
		$label_name = $this->language->get('entry_'.$param_name);
		$tmpl = '';
		if ($width_label > 0) {
			$tmpl .= '<label class="col-sm-'.$width_label.' control-label">'.$label_name.'</label>'; 
		}
		if ($placeholder <> 'placeholder_'.$param_name) $placeholder = ' placeholder="'.$placeholder.'"';
		if ($width_text  > 0) {
			$tmpl .= '<div class="col-sm-'.$width_text.'">';
			$tmpl .= '<input type="'.$type.'" class="form-control"'.$placeholder.' id="'.$name.'" name="'.$name.'" value="'.$value.'"/>';
			$tmpl .= '</div>';
		}		
		if ($width_desc > 0) {
			$tmpl .= '<div class="col-sm-'.$width_desc.'">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}

	private function formTextarea($name, $data) {
		$value = $this->getParams($name);
		$param_name = substr($name,11);
		$description = $this->language->get('desc_'.$param_name);
		$placeholder = $this->language->get('placeholder_'.$param_name);
		$tmpl = '';
		if ($placeholder <> 'placeholder_'.$param_name) $placeholder = ' placeholder="'.$placeholder.'"';
		$label_name = $this->language->get('entry_'.$param_name);
		if ($label_name <> 'entry_'.$param_name) {
			$tmpl .= '<label class="col-sm-2 control-label">'.$label_name.'</label>'; 
		}
		$tmpl .= '<div class="col-sm-3">';
		$tmpl .= '<textarea class="form-control" id="'.$name.'" name="'.$name.'" rows="6"'.$placeholder.'>'.$value.'</textarea>';
		$tmpl .= '</div>';
		if ($description <> 'desc_'.$param_name) {
			$tmpl .= '<div class="col-sm-7">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}


	private function formSelect($name, $data, $values, $width=array(), $alert='') {
		$param = $this->getParams($name);
		$param_name = substr($name,11);		
		$description = $this->language->get('desc_'.$param_name);
		$tmpl = '';
		$label_name = $this->language->get('entry_'.$param_name);
		$width_label = isset($width[0]) ? $width[0] : 2;
		$width_select = isset($width[1]) ? $width[1] : 3;
		$width_desc = isset($width[2]) ? $width[2] : 7;
		if ($width_label > 0) {
			$tmpl .= '<label class="col-sm-'.$width_label.' control-label">'.$label_name.'</label>';
		}
		if ($width_select > 0) {
			$tmpl .= '<div class="col-sm-'.$width_select.'">'; 
			$tmpl .= '<select name="'.$name.'" id="'.$name.'" class="form-control">';
			foreach ($values as $value => $text) {
				$selected = ($param == $value ? ' selected="selected"' : '');
				$tmpl .= '<option value="'.$value.'"'.$selected.'>'.$text.'</option>';
			}
			$tmpl .= '</select></div>';
		}
		if ($width_desc > 0) {
			$tmpl .= '<div class="col-sm-'.$width_desc.'">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div>';
			if ($alert) {
				$tmpl .= '<div class="alert alert-danger"><i class="fa fa-warning"></i>'.$alert.'</div>';
			}
			$tmpl .= '</div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}

	private function formCheckBox($name, $data, $values, $height=150) {
		$param = $this->getParams($name);
		$param_name = substr($name,11);
		$description = $this->language->get('desc_'.$param_name);
		$tmpl = '';
		$label_name = $this->language->get('entry_'.$param_name);
		if ($label_name <> 'entry_'.$param_name) {
			$tmpl .= '<label class="col-sm-2 control-label" for="entry_'.$param_name.'">'.$label_name.'</label>';
		}
		$tmpl .= '<div class="col-sm-3">';
		$tmpl .= '<div class="well well-sm" style="height: '.$height.'px; overflow: auto;">';
		foreach ($values as $value => $text) {
			$tmpl .= '<div class="checkbox">';
			$tmpl .= '<label>';
			$checked = (isset($param[$value]) ? ' checked="checked"' : '');
			$tmpl .= '<input type="checkbox" name="'.$name.'['.$value.']" value="1"'.$checked.' />';
			$tmpl .= '&nbsp;'.$text;
			$tmpl .= '</label>';
			$tmpl .= '</div>';
		}
		$tmpl .= '</div></div>';
		if ($description <> 'desc_'.$param_name) {
			$tmpl .= '<div class="col-sm-7">';
			$tmpl .= '<div class="alert alert-info">';
			$tmpl .= '<i class="fa fa-info-circle"></i>';
			$tmpl .= '&nbsp;'.$description;
			$tmpl .= '</div></div>';
		}
		$data['form_'.$name] = $tmpl;
		return $data;
	}


}
?>
