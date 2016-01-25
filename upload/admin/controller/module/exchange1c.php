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
	private function echo_message($ok, $message) {
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
			
		if  (!$this->config->get('exchange1c_version')) {
			$this->install();
			$this->load->model('extension/extension');
			$this->model_extension_extension->install('module', 'exchange1c');
		}
		
		// настройки сохраняются только для первого магазина
		$settings = $this->model_setting_setting->getSetting('exchange1c', 0);

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->request->post['exchange1c_order_date'] = $this->config->get('exchange1c_order_date');
			$settings = array_merge($settings, $this->request->post);
			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['version'] = $settings['exchange1c_version'];

		$data['lang']['text_max_filesize'] = sprintf($this->language->get('text_max_filesize'), @ini_get('max_file_uploads'));
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		
		if (isset($this->request->post['config_icon'])) {
			$data['config_icon'] = $this->request->post['config_icon'];
		} else {
			$data['config_icon'] = $this->config->get('config_icon');
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}
		else {
			$data['error_warning'] = '';
		}

 		if (isset($this->error['image'])) {
			$data['error_image'] = $this->error['image'];
		} else {
			$data['error_image'] = '';
		}

		if (isset($this->error['exchange1c_username'])) {
			$data['error_exchange1c_username'] = $this->error['exchange1c_username'];
		}
		else {
			$data['error_exchange1c_username'] = '';
		}

		if (isset($this->error['exchange1c_password'])) {
			$data['error_exchange1c_password'] = $this->error['exchange1c_password'];
		}
		else {
			$data['error_exchange1c_password'] = '';
		}
		
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

		//$data['action'] = HTTPS_SERVER . 'index.php?route=module/exchange1c&token=' . $this->session->data['token'];
		$data['action'] = $this->url->link('module/exchange1c', 'token=' . $this->session->data['token'], 'SSL');

		//$data['cancel'] = HTTPS_SERVER . 'index.php?route=extension/exchange1c&token=' . $this->session->data['token'];
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['exchange1c_username'])) {
			$data['exchange1c_username'] = $this->request->post['exchange1c_username'];
		}
		else {
			$data['exchange1c_username'] = $this->config->get('exchange1c_username');
		}

		if (isset($this->request->post['exchange1c_password'])) {
			$data['exchange1c_password'] = $this->request->post['exchange1c_password'];
		}
		else {
			$data['exchange1c_password'] = $this->config->get('exchange1c_password'); 
		}

		if (isset($this->request->post['exchange1c_allow_ip'])) {
			$data['exchange1c_allow_ip'] = $this->request->post['exchange1c_allow_ip'];
		}
		else {
			$data['exchange1c_allow_ip'] = $this->config->get('exchange1c_allow_ip'); 
		} 
		
		if (isset($this->request->post['exchange1c_status'])) {
			$data['exchange1c_status'] = $this->request->post['exchange1c_status'];
		}
		else {
			$data['exchange1c_status'] = $this->config->get('exchange1c_status');
		}

		// Группы
		if(version_compare(VERSION, '2.0.3.1', '>')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
		} else {
			$this->load->model('sale/customer_group');
			$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		}
		
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

		// Загрузка товаров в разных валютах
		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
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

		// Отключать товар если остаток меньше или равен нулю
		if (isset($this->request->post['exchange1c_product_status_disable_if_quantity_zero'])) {
			$data['exchange1c_product_status_disable_if_quantity_zero'] = $this->request->post['exchange1c_product_status_disable_if_quantity_zero'];
		}
		else {
			$data['exchange1c_product_status_disable_if_quantity_zero'] = $this->config->get('exchange1c_product_status_disable_if_quantity_zero');
		}

		// Загружать только типы номенклатуры указанные строкой через любой разделитель
		if (isset($this->request->post['exchange1c_parse_only_types_item'])) {
			$data['exchange1c_parse_only_types_item'] = $this->request->post['exchange1c_parse_only_types_item'];
		}
		else {
			$data['exchange1c_parse_only_types_item'] = $this->config->get('exchange1c_parse_only_types_item');
		}
		
		if (isset($this->request->post['exchange1c_flush_product'])) {
			$data['exchange1c_flush_product'] = $this->request->post['exchange1c_flush_product'];
		}
		else {
			$data['exchange1c_flush_product'] = $this->config->get('exchange1c_flush_product');
		}

		if (isset($this->request->post['exchange1c_flush_category'])) {
			$data['exchange1c_flush_category'] = $this->request->post['exchange1c_flush_category'];
		}
		else {
			$data['exchange1c_flush_category'] = $this->config->get('exchange1c_flush_category');
		}

		if (isset($this->request->post['exchange1c_flush_manufacturer'])) {
			$data['exchange1c_flush_manufacturer'] = $this->request->post['exchange1c_flush_manufacturer'];
		}
		else {
			$data['exchange1c_flush_manufacturer'] = $this->config->get('exchange1c_flush_manufacturer');
		}
        
		if (isset($this->request->post['exchange1c_flush_quantity'])) {
			$data['exchange1c_flush_quantity'] = $this->request->post['exchange1c_flush_quantity'];
		}
		else {
			$data['exchange1c_flush_quantity'] = $this->config->get('exchange1c_flush_quantity');
		}

		if (isset($this->request->post['exchange1c_flush_attribute'])) {
			$data['exchange1c_flush_attribute'] = $this->request->post['exchange1c_flush_attribute'];
		}
		else {
			$data['exchange1c_flush_attribute'] = $this->config->get('exchange1c_flush_attribute');
		}

		if (isset($this->request->post['exchange1c_fill_parent_cats'])) {
			$data['exchange1c_fill_parent_cats'] = $this->request->post['exchange1c_fill_parent_cats'];
		}
		else {
			$data['exchange1c_fill_parent_cats'] = $this->config->get('exchange1c_fill_parent_cats');
		}
		
		if (isset($this->request->post['exchange1c_relatedoptions'])) {
			$data['exchange1c_relatedoptions'] = $this->request->post['exchange1c_relatedoptions'];
		} else {
			$data['exchange1c_relatedoptions'] = $this->config->get('exchange1c_relatedoptions');
		}

		if (isset($this->request->post['exchange1c_order_status_to_exchange'])) {
			$data['exchange1c_order_status_to_exchange'] = $this->request->post['exchange1c_order_status_to_exchange'];
		} else {
			$data['exchange1c_order_status_to_exchange'] = $this->config->get('exchange1c_order_status_to_exchange');
		}
		
		if (isset($this->request->post['exchange1c_dont_use_artsync'])) {
			$data['exchange1c_dont_use_artsync'] = $this->request->post['exchange1c_dont_use_artsync'];
		} else {
			$data['exchange1c_dont_use_artsync'] = $this->config->get('exchange1c_dont_use_artsync');
		}

		if (isset($this->request->post['exchange1c_product_name_or_fullname'])) {
			$data['exchange1c_product_name_or_fullname'] = $this->request->post['exchange1c_product_name_or_fullname'];
		} else {
			$data['exchange1c_product_name_or_fullname'] = $this->config->get('exchange1c_product_name_or_fullname');
		}

		if (isset($this->request->post['exchange1c_synchronize_uuid_to_id'])) {
			$data['exchange1c_synchronize_uuid_to_id'] = $this->request->post['exchange1c_synchronize_uuid_to_id'];
		} else {
			$data['exchange1c_synchronize_uuid_to_id'] = $this->config->get('exchange1c_synchronize_uuid_to_id');
		}

		if (isset($this->request->post['exchange1c_seo_url'])) {
			$data['exchange1c_seo_url'] = $this->request->post['exchange1c_seo_url'];
		}
		else {
			$data['exchange1c_seo_url'] = $this->config->get('exchange1c_seo_url');
		}
		// Определим есть ли модуль deadcow seo
		if ($this->config->get('deadcow_seo_transliteration')) {
			$data['enable_module_deadcow'] = true;
		} else {
			$data['enable_module_deadcow'] = false;
		}

		if (isset($this->request->post['exchange1c_full_log'])) {
			$data['exchange1c_full_log'] = $this->request->post['exchange1c_full_log'];
		}
		else {
			$data['exchange1c_full_log'] = $this->config->get('exchange1c_full_log');
		}

		if (isset($this->request->post['exchange1c_watermark'])) {
			$data['exchange1c_watermark'] = $this->request->post['exchange1c_watermark'];
		}
		else {
			$data['exchange1c_watermark'] = $this->config->get('exchange1c_watermark');
		}

		if ($data['exchange1c_watermark']) {
			$data['thumb'] = $this->model_tool_image->resize($data['exchange1c_watermark'], 100, 100);
		}
		else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		if (isset($this->request->post['exchange1c_order_status'])) {
			$data['exchange1c_order_status'] = $this->request->post['exchange1c_order_status'];
		}
		else {
			$data['exchange1c_order_status'] = $this->config->get('exchange1c_order_status');
		}

		if (isset($this->request->post['exchange1c_order_status_cancel'])) {
			$data['exchange1c_order_status_cancel'] = $this->request->post['exchange1c_order_status_cancel'];
		} else {
			$data['exchange1c_order_status_cancel'] = $this->config->get('exchange1c_order_status_cancel');
		}
		
		if (isset($this->request->post['exchange1c_order_status_completed'])) {
			$data['exchange1c_order_status_completed'] = $this->request->post['exchange1c_order_status_completed'];
		} else {
			$data['exchange1c_order_status_completed'] = $this->config->get('exchange1c_order_status_completed');
		}

		if (isset($this->request->post['exchange1c_order_currency'])) {
			$data['exchange1c_order_currency'] = $this->request->post['exchange1c_order_currency'];
		}
		else {
			$data['exchange1c_order_currency'] = $this->config->get('exchange1c_order_currency');
		}

		if (isset($this->request->post['exchange1c_order_notify'])) {
			$data['exchange1c_order_notify'] = $this->request->post['exchange1c_order_notify'];
		}
		else {
			$data['exchange1c_order_notify'] = $this->config->get('exchange1c_order_notify');
		}

		if (isset($this->request->post['exchange1c_file_zip'])) {
			$data['exchange1c_file_zip'] = $this->request->post['exchange1c_file_zip'];
		}
		else {
			$data['exchange1c_file_zip'] = $this->config->get('exchange1c_file_zip');
		}

		// Статус товара по умолчанию при отсутствии
		$this->load->model('localisation/stock_status');
		$data['stock_statuses'] = $this->model_localisation_stock_status->getStockStatuses();
		if (isset($this->request->post['exchange1c_default_stock_status'])) {
			$data['exchange1c_default_stock_status'] = $this->request->post['exchange1c_default_stock_status'];
		}
		else {
			$data['exchange1c_default_stock_status'] = $this->config->get('exchange1c_default_stock_status');
		}

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
		unset($store_id);
		unset($result);

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
		// Магазины

		// Список полей, которые загружаются при импорте товаров
		$data['product_fields'] = array(
			'column'		=> 0,
			'sort_order'	=> 0,
		);

		if (isset($this->request->post['exchange1c_product_fields_update'])) {
			$data['exchange1c_product_fields_update'] = $this->request->post['exchange1c_product_fields_update'];
		} elseif (isset($this->request->get['exchange1c_product_fields_update'])) {
			$data['exchange1c_product_fields_update'] = $this->request->get['exchange1c_product_fields_update'];
		} else {
			if (isset($settings['exchange1c_product_fields_update'])) {
				$data['exchange1c_product_fields_update'] = $settings['exchange1c_product_fields_update'];
			} else {
				$data['exchange1c_product_fields_update'] = $data['product_fields'];
			}
		}
		// Список полей, которые загружаются при импорте товаров
		
		$this->load->model('localisation/order_status');

		$order_statuses = $this->model_localisation_order_status->getOrderStatuses();

		foreach ($order_statuses as $order_status) {
			$data['order_statuses'][] = array(
				'order_status_id' => $order_status['order_status_id'],
				'name'			  => $order_status['name']
			);
		}
		// + 1.6.1.7
		unset($order_statuses);
		
		$data['upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['post_max_size'] = ini_get('post_max_size');
		$data['memory_limit'] = ini_get('memory_limit');
		
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
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'product_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'product_to_1c` (
							`product_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`product_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (product_id) REFERENCES '. DB_PREFIX .'product(product_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		// Связь категорий с 1С
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'category_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'category_to_1c` (
							`category_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`category_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (category_id) REFERENCES '. DB_PREFIX .'category(category_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
	
		// Свойства из 1С
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'attribute_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'attribute_to_1c` (
							`attribute_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`attribute_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (attribute_id) REFERENCES '. DB_PREFIX .'attribute(attribute_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		// Характеристики из 1С
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'option_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'option_to_1c` (
							`option_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`option_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (option_id) REFERENCES '. DB_PREFIX .'option(option_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// Привязка производителя к каталогу 1С
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'manufacturer_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'manufacturer_to_1c` (
							`manufacturer_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`manufacturer_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (manufacturer_id) REFERENCES '. DB_PREFIX .'manufacturer(manufacturer_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// Привязка магазина к каталогу 1С
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'store_to_1c"');
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'store_to_1c` (
							`store_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							KEY (`store_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (store_id) REFERENCES '. DB_PREFIX .'store(store_id) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}
		
		// остатки по складам
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'product_quantity"');
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
		$query = $this->db->query('SHOW TABLES LIKE "' . DB_PREFIX . 'warehouse"');
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
	private function moveImages($from, $clean=false) {
		if (is_dir($from)) {
			$images = DIR_IMAGE . 'import_files/';
			if (is_dir($images) && $clean) {
				$this->cleanDir($images);
			}
			rename($from, $images);
		}
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
			if ($zip->open($this->request->files['file']['tmp_name']) === true) {
				$max_size_file = $this->modeCatalogInit(array(),false);
				//$this->log($this->request->files['file']);
				
					$zip->extractTo($cache);
					//ищем папку с папкой import_files
					$cache = $this->findFolder(DIR_CACHE, 'import_files');
					
					if (!$cache) {
						$this->log("Не найдена папка import_files");
						return 0;
					}
					
					$this->MoveImages($cache . 'import_files/', true);
					$files = scandir($cache);
					foreach ($files as $file) {
						if (is_file($cache . $file)) {
							$no_error = $this->modeImport($cache . $file);
						}
					}
					if (is_dir($cache . '1cbitrix_/import_files')) {
						rmdir($cache . '1cbitrix_/import_files');
					}
			}
			else {
				$import_file = $cache . $this->request->files['file']['name'];
				move_uploaded_file($this->request->files['file']['tmp_name'], $import_file);
				$this->log("Загружен файл: " . $this->request->files['file']['name']);
				$no_error = $this->modeImport($import_file);
				unlink($import_file);
			}
		}
		if ($no_error) {
			$json['success'] = $this->language->get('text_upload_success');
		} else {
			$json['error'] = $this->language->get('text_upload_error');
		}
		//$this->cleanCacheDir();
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
			if ($this->config->get('exchange1c_file_zip')) {
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
		if ($this->config->get('exchange1c_file_zip')) {
			echo "zip=yes\n";
		} else {
			echo "zip=no\n";
		}
		echo "file_limit=" . $this->getPostMaxFileSize() . "\n";
	} // modeSaleInit()
	

	/**
	 * Проверяем на наличие архива и распаковка
	 */
	private function extractArchive($filename) {
		$cache = DIR_CACHE . 'exchange1c/';
		$zip = new ZipArchive;
		if ($zip->open($filename) === true) {
			$zip->extractTo($cache);
			unlink($filename);
			// переносим папку import_files в картинки
			$this->MoveImages($cache . 'import_files/', true);
		}
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

		// Получаем данные
		$data = file_get_contents("php://input");

		if ($data !== false) {
			file_put_contents($uplod_file, $data);
			if ($fp = fopen($uplod_file, "wb")) {
				$result = fwrite($fp, $data);
				if ($result === strlen($data)) {
					chmod($uplod_file , 0777);
					$this->echo_message(1, "The file " . $this->request->get['filename'] . " has been successfully uploaded");
					$this->extractArchive($uplod_file);
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
			$this->echo_message(0, "No data in file " . $this->request->get['filename']);
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

		// Проверка базы данных
		$this->load->model('tool/exchange1c');
		if (!$this->model_tool_exchange1c->checkDB()) {
			if (!$manual) $this->echo_message(0, "Failure Database validation");
			return 0;
		}
		// Определяем текущую локаль
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
		
		// Только если выбран способ deadcow_seo
		if ($this->config->get('exchange1c_seo_url') == 1) {
			$this->load->model('module/deadcow_seo');
			$this->model_module_deadcow_seo->generateCategories($this->config->get('deadcow_seo_categories_template'), '', 'Russian', true, true);
			$this->model_module_deadcow_seo->generateProducts($this->config->get('deadcow_seo_products_template'), '.html', 'Russian', true, true);
			$this->model_module_deadcow_seo->generateManufacturers($this->config->get('deadcow_seo_manufacturers_template'), '', 'Russian', true, true);
			$this->model_module_deadcow_seo->generateProductsMetaKeywords($this->config->get('deadcow_seo_meta_template'), $this->config->get('deadcow_seo_yahoo_id'), 'Russian', false);
			$this->model_module_deadcow_seo->generateTags($this->config->get('deadcow_seo_tags_template'), 'Russian', false);
			$this->model_module_deadcow_seo->generateCategoriesMetaKeywords($this->config->get('deadcow_seo_categories_template'), 'Russian');
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
		$this->log("[F] Изменение статусов заказов");
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

		$this->echo($result);
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
	public function eventProductDelete($product_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->ProductLinkDelete($product_id);
	} // eventProductDelete()
	
	/**
	 * События
	 */
	public function eventCategoryDelete($category_id) {
		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->CategoryLinkDelete($category_id);
	} // eventCategoryDelete()
	

}
?>
