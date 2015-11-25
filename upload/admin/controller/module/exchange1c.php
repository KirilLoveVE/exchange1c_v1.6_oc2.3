<?php
class ControllerModuleExchange1c extends Controller {
	private $error = array(); 
	private $module_version = '1.6.1.9';
	private $module_name = 'Exchange 1C 8.x';

	/**
	 * Пишет в файл журнала если включена настройка
	 *
	 * @param	string,array()	Сообщение или объект
	 */
	private function log($message) {
		if ($this->config->get('exchange1c_full_log')) $this->log->write(print_r($message,true));
	} // log()

	public function index() {

		$this->checkCMS();
		
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
		// + 1.6.1.7 исключаем группу по умолчанию
		//unset($data['customer_groups'][0]);

		
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

		if (isset($this->request->post['exchange1c_seo_url'])) {
			$data['exchange1c_seo_url'] = $this->request->post['exchange1c_seo_url'];
		}
		else {
			$data['exchange1c_seo_url'] = $this->config->get('exchange1c_seo_url');
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

		// + 1.6.1.7 Магазины
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
					'1c'			=> '',
					'name'			=> $this->config->get('config_name'),
					'store_id'		=> 0
				);
			}
		}
		// - 1.6.1.7 Магазины

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

		$this->template = 'module/exchange1c.tpl';
		$this->children = array(
			'common/header',
			'common/footer'	
		);
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('module/exchange1c.tpl', $data));
	}

	private function validate() {

		if (!$this->user->hasPermission('modify', 'module/exchange1c')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		}
		else {
			return false;
		}
	}

	public function install() {
		
		$this->load->model('extension/event');
		
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('exchange1c',
			array(
				'exchange1c'.'_version'	=> $this->module_version,
				'exchange1c'.'_name'	=> $this->module_name
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
							`1c_category_id` varchar(255) NOT NULL,
							KEY (`category_id`),
							KEY `1c_id` (`1c_category_id`),
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
							`1c_attribute_id` varchar(255) NOT NULL,
							KEY (`attribute_id`),
							KEY `1c_id` (`1c_attribute_id`),
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
							`1c_option_id` varchar(255) NOT NULL,
							KEY (`option_id`),
							KEY `1c_id` (`1c_option_id`),
							FOREIGN KEY (option_id) REFERENCES '. DB_PREFIX .'attribute(option_id) ON DELETE CASCADE
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
							`1c_store_id` varchar(255) NOT NULL,
							KEY (`store_id`),
							KEY `1c_id` (`1c_store_id`),
							FOREIGN KEY (store_id) REFERENCES '. DB_PREFIX .'attribute(store_id) ON DELETE CASCADE
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
							`1c_warehouse_id` varchar(255) NOT NULL,
                            PRIMARY KEY (`warehouse_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		$this->log->write("[+] Установлен модуль " . $this->module_name . " версии " . $this->module_version . " для OpenCart " . VERSION);
	}

	public function uninstall() {
		
		$this->load->model('extension/event');
		$this->model_extension_event->deleteEvent('exchange1c');
		
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('exchange1c');

//		$this->load->model('extension/module');
//		$this->model_extension_module->deleteModule('exchange1c'); 

		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "option_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		
		// Удаляем и файлы
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/export/exchange1c.php'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/export/exchange1c.php');
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/controller/module/exchange1c.php'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/controller/module/exchange1c.php');
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/model/tool/exchange1c.php'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/model/tool/exchange1c.php');
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/language/english/module/exchange1c.php'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/language/english/module/exchange1c.php');
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/language/russian/module/exchange1c.php'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/language/russian/module/exchange1c.php');
		if (is_file($_SERVER['DOCUMENT_ROOT'].'/admin/view/template/module/exchange1c.tpl'))
			unlink($_SERVER['DOCUMENT_ROOT'].'/admin/view/template/module/exchange1c.tpl');
			
		$this->log->write("[-] Удален модуль " . $this->module_name . " версии " . $this->module_version . " для OpenCart " . VERSION);
		
	}
	
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
		} else {
			$this->log->write("[i] Файл не найден: " . $htaccess);
		}
	}

	private function checkAuth() {
		// проверка IP, имени и пароля

		// Проверяем включен или нет модуль
		if (!$this->config->get('exchange1c_status')) {
			$this->log("[ERROR] Модуль отключен. Проверка авторизации из 1С невозможна!");
			return "failure\n1c module OFF";
		}

		// Разрешен ли IP
		if ($this->config->get('exchange1c_allow_ip') != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));

			if (!in_array($ip, $allow_ips)) {
				$this->log("[ERROR] С Вашего IP адреса [" . $ip. "] не разрешен обмен, скорректируйте настройки модуля!");
				return "failure\nIP is not allowed";
			}
		}

		// Авторизуем
		if (($this->config->get('exchange1c_username') != '') && (@$_SERVER['PHP_AUTH_USER'] != $this->config->get('exchange1c_username'))) {
			$this->log("[ERROR] Авторизация: неверное имя!");
			$this->authWarning();
			return "failure\nerror login";
		}
		
		if (($this->config->get('exchange1c_password') != '') && (@$_SERVER['PHP_AUTH_PW'] != $this->config->get('exchange1c_password'))) {
			$this->log("[ERROR] Авторизация: неверный пароль!");
			$this->authWarning();
			return "failure\nerror password";
		}
		
		return "";
	}
	
	// ---
	public function modeCheckauth() {

		$error = $this->checkAuth();
		
		if ($error) {
			echo $error;
			return false;
		}

		echo "success\n";
		echo "key\n";
		echo md5($this->config->get('exchange1c_password')) . "\n";
	}

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
	
	
	public function manualImport() {
		$this->load->language('module/exchange1c');
		$cache = DIR_CACHE . 'exchange1c/';
		$json = array();

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {

			$filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));
			$zip = new ZipArchive;
			if ($zip->open($this->request->files['file']['tmp_name']) === true) {
				$this->modeCatalogInit(false);

				$zip->extractTo($cache);
				$files = scandir($cache);

				foreach ($files as $file) {
					if (is_file($cache . $file)) {
						$this->modeImport($file);
					}
				}

				if (is_dir($cache . 'import_files')) {
					$images = DIR_IMAGE . 'import_files/';
					
					if (is_dir($images)) {
						$this->cleanDir($images);
					}

					rename($cache . 'import_files/', $images);
				}

			}
			else {

				// Читаем первые 1024 байт и определяем файл по сигнатуре, ибо мало ли, какое у него имя
				$handle = fopen($this->request->files['file']['tmp_name'], 'r');
				$buffer = fread($handle, 1024);
				fclose($handle);

				if (strpos($buffer, 'ПакетПредложений')) {
					move_uploaded_file($this->request->files['file']['tmp_name'], $cache . 'offers.xml');
					$this->modeImport('offers.xml');
				}
				else if (strpos($buffer, 'Документ')) {
					move_uploaded_file($this->request->files['file']['tmp_name'], $cache . 'orders.xml');
					$this->modeImport('orders.xml');
				}
				else if (strpos($buffer, 'Классификатор')) {
					$this->modeCatalogInit(array(), false);
					move_uploaded_file($this->request->files['file']['tmp_name'], $cache . 'import.xml');
					$this->modeImport('import.xml');
				}
				else {
					$this->log->write('Ошибка при ручной загрузке файла');
					$json['error'] = $this->language->get('text_upload_error');
					exit;
				}
			}

			$json['success'] = $this->language->get('text_upload_success');
		}

		$this->response->setOutput(json_encode($json));
	} // manualImport()
	
	public function modeCatalogInit($param = array(), $echo = true) {

		$this->checkCMS();
		
		if ($echo){
			if (!isset($this->request->cookie['key'])) {
				$this->log("[ERROR] Не указан ключ куки.");
				echo "no cookie key";
				return;
			}
	
			if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
				$this->log("[ERROR] Ошибка сессии.");
				echo "failure\n";
				echo "Session error";
				return;
			}
		}

		// + 1.6.1.7
		// Включена проверка на запись файлов и папок
		$test_file = DIR_CACHE . 'exchange1c/test.php';
		if ($fp = fopen($test_file, "w")) {
			fclose($fp);
			unlink($test_file);
		} else {
			$error_message = "ВНИМАНИЕ! Папка '" . DIR_CACHE . "exchange1c' не доступна для записи!";
			$this->log($error_message);
			echo "failure\n";
			echo $error_message;
			return;
		}

		$test_file = DIR_IMAGE . 'test.php';
		if ($fp = fopen($test_file, "w")) {
			fclose($fp);
			unlink($test_file);
		} else {
			$error_message = "ВНИМАНИЕ! Папка '" . DIR_IMAGE . "' не доступна для записи!";
			$this->log($error_message);
			echo "failure\n";
			echo $error_message;
			return;
		}
		// - 1.6.1.7

		$limit = 100000 * 1024;
	
		if ($echo) {
			echo "zip=no\n";
			echo "file_limit=".$limit."\n";
		}
	
	} // modeCatalogInit()

	public function modeSaleInit() {
		$limit = 100000 * 1024;
	
		echo "zip=no\n";
		echo "file_limit=".$limit."\n";
	} // modeSaleInit()
	
	/**
	 * Обрабатывает загруженный файл на сервер
	 *
	 */
	public function modeFile() {

		if (!isset($this->request->cookie['key'])) {
			return;
		}

		if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
			echo "failure\n";
			echo "Session error";
			return;
		}

		$cache = DIR_CACHE . 'exchange1c/';
		
		// Проверяем на наличие каталога
		if(!is_dir($cache)) mkdir($cache);

		// Проверяем на наличие имени файла
		if (isset($this->request->get['filename'])) {
			$uplod_file = $cache . $this->request->get['filename'];
		}
		else {
			echo "failure\n";
			echo "ERROR 10: No file name variable";
			$this->log("[ERROR] Имя загружаемого файла не определено, файл отстутствует, проверьте права на папку, обратитесь к хостеру или администратору сервера!");
			return;
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
					echo "success\n";

					chmod($uplod_file , 0777);
					echo "success\n";
				}
				else {
					echo "failure\n";
				}
			}
			else {
				echo "failure\n";
				echo "Can not open file: $uplod_file";
				$this->log("[ERROR] Отсутствует файл для загрузки '" . $uplod_file . "'");
			}
		}
		else {
			echo "failure\n";
			echo "No data file\n";
			$this->log("[ERROR] Отсутвуют данные в файле!");
		}


	} // modeFile()

	/**
	 * Обрабатывает *.XML файлы
	 *
	 * @param	boolean		true - ручной импорт
	 */
	public function modeImport($manual = false) {

		$this->checkCMS();
		
		$cache = DIR_CACHE . 'exchange1c/';
		if(!is_dir($cache)) mkdir($cache);
		
		if ($manual) {
			$filename = $manual;
			$importFile = $cache . $filename;
		}
		else if (isset($this->request->get['filename'])) {
			$filename = $this->request->get['filename'];
			$importFile = $cache . $filename;
		}
		else {
			echo "failure\n";
			echo "ERROR 10: No file name variable";
			$this->log("[ERROR] Отсутствует файл для загрузки каталога. Загрузка каталога отменена!");
			return 0;
		}

		$this->load->model('tool/exchange1c');

		// Проверка базы данных
		if (!$this->model_tool_exchange1c->checkDB()) {
			$this->log("[ERROR] Проверка базы данных не пройдена!. Загрузка отменена");
			return 0;
		}

		// Определяем текущую локаль
		$language_id = $this->model_tool_exchange1c->getLanguageId($this->config->get('config_language'));

		if (strpos($filename, 'import') !== false) {
			
			$this->model_tool_exchange1c->parseImportFromFile($filename);

			if ($this->config->get('exchange1c_fill_parent_cats')) {
				$this->model_tool_exchange1c->fillParentsCategories();
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

			if (!$manual) {
				echo "success\n";
			}
			
		}
		else if (strpos($filename, 'offers') !== false) {
			$exchange1c_price_type = $this->config->get('exchange1c_price_type');
			$this->model_tool_exchange1c->parseOffersFromFile($filename, $exchange1c_price_type);
			
			if (!$manual) {
				echo "success\n";
			}
		}
		else if (strpos($filename, 'orders') !== false) {
			$exchange1c_price_type = $this->config->get('exchange1c_price_type');
			$this->model_tool_exchange1c->parseOrdersFromFile($filename, $exchange1c_price_type);
			
			if (!$manual) {
				echo "success\n";
			}
		}
		else {
			echo "failure\n";
			echo $filename;
		}

		$this->cache->delete('product');
		return;
	} // modeImport()

	public function modeQueryOrders() {
		if (!isset($this->request->cookie['key'])) {
			echo "Cookie fail\n";
			return;
		}

		if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
			echo "failure\n";
			echo "Session error";
			return;
		}

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
	 * Changing order statuses.
	 */
	public function modeOrdersChangeStatus(){
		if (!isset($this->request->cookie['key'])) {
			echo "Cookie fail\n";
			return;
		}

		if ($this->request->cookie['key'] != md5($this->config->get('exchange1c_password'))) {
			echo "failure\n";
			echo "Session error";
			return;
		}

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

		if($result)
			echo "success\n";
		else
			echo "fail\n";
	}


	// -- Системные процедуры
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

		return 0;
	}

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
		
	}


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
	}
	
	public function checkCMS() {
		// Определяем версию системы
		if ($handle = fopen($_SERVER['DOCUMENT_ROOT'].'/index.php', 'r')) {
			$buffer = fread($handle, 2048);
			fclose($handle);
			
			if (!defined('CMS')) {
				if (strpos($buffer, 'OCSHOP')) {
					define('CMS', 'OCSHOP');
				} elseif (strpos($buffer, 'OCSTORE')) {
					define('CMS', 'OCSTORE');
				} else {
					define('CMS', 'OPENCART');
				}
			}
			
			if (!defined('VERSION')) {
				if (preg_match("/[\d](\.[\d])+/", $buffer, $matches)) {
					define('VERSION', $matches[0]);
				} else {
					define('VERSION', '2.0.3.1');
				}
			}
			$this->log("CMS: " . CMS . ", version: " . VERSION);
			return true;
		}
		$this->log("[i] Не удалось определить CMS");
	}

}
?>
