<?php
class ControllerModuleExchange1c extends Controller {
	private $error = array();
	private $module_name = 'Exchange 1C 8.x';

	/**
	 * Пишет информацию в файл журнала
	 *
	 * @param	int				Уровень сообщения
	 * @param	string,object	Сообщение или объект
	 */
	private function log($message, $level=1) {
		if ($this->config->get('exchange1c_log_level') >= $level) {

			if ($level == 1) {
				$this->log->write(print_r($message,true));

			} elseif ($level == 2) {
				$memory_usage = sprintf("%.3f", memory_get_usage() / 1024 / 1024);
				list ($di) = debug_backtrace();
				$line = sprintf("%04s",$di["line"]);

				if (is_array($message) || is_object($message)) {
					$this->log->write($memory_usage . " Mb | " . $line);
					$this->log->write(print_r($message, true));
				} else {
					$this->log->write($memory_usage . " Mb | " . $line . " | " . $message);
				}
			}
		}
	} // log()


	/**
	 * Выводит сообщение
	 */
	private function echo_message($ok, $message="") {
		if ($ok) {
			echo "success\n";
			$this->log("[ECHO] success",2);
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message,2);
			}
		} else {
			echo "failure\n";
			$this->log("[ECHO] failure",2);
			if ($message) {
				echo $message;
				$this->log("[ECHO] " . $message,2);
			}
		};
	} // echo_message()


	/**
	 * Сохраняет настройки сразу в базу данных
	 */
	private function configSet($key, $value, $store_id=0) {
		if (!$this->config->has('exchange1c_'.$key)) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "setting` SET `value` = '" . $value . "', `store_id` = " . $store_id . ", `code` = 'exchange1c', `key` = '" . $key . "'");
		}
	}

	/**
	 * Определяет значение переменной ошибки
	 */
	private function setParamError(&$data, $param) {
		if (isset($this->request->post[$param])) {
			$data['error_'.$param] = $this->request->post[$param];
		} else {
			$data['error_'.$param] = '';
		}
	} // setParamsError()


	/**
	 * Определяет значение переменной
	 */
	private function getParam($param, $default='') {
		if (isset($this->request->post['exchange1c_'.$param])) {
			return $this->request->post['exchange1c_'.$param];
		} else {
			if ($this->config->get('exchange1c_'.$param))
				return $this->config->get('exchange1c_'.$param);
			else
				return $default;
		}
	} // getParam()


	/**
	 * Выводит форму текстового многострочного поля
	 */
	private function htmlTextarea($name, $param) {
		$value = $this->getParam($name);
		if (!$value && isset($param['default'])) $value = $param['default'];
		$tmpl = '<textarea class="form-control" id="exchange1c_'.$name.'" name="exchange1c_'.$name.'" rows="6">'.$value.'</textarea>';
		return $tmpl;
	} // htmlTextarea()


	/**
	 * Выводит форму выбора значений
	 */
	private function htmlSelect($name, $param) {
		$value = $this->getParam($name);
        if (!$value && isset($param['default'])) $value = $param['default'];
		$tmpl = '<select name="exchange1c_'.$name.'" id="exchange1c_'.$name.'" class="form-control">';
		foreach ($param['options'] as $option => $text) {
			$selected = ($option == $value ? ' selected="selected"' : '');
			$tmpl .= '<option value="'.$option.'"'.$selected.'>'.$text.'</option>';
		}
		$tmpl .= '</select>';
		return $tmpl;
	} // htmlSelect()


	/**
	 * Выводит форму переключателя "Да+Нет"
	 */
	private function htmlRadio($name, $param) {
		$value = $this->getParam($name);
		//$this->log($name . ' = ' . $value);
		if (!$value && isset($param['default'])) $value = $param['default'];
		if (isset($param['text'])) {
			if ($param['text'] == 'on_off') {
				$text1 = 'text_on';
				$text0 = 'text_off';
			} else {
				$text1 = 'text_yes';
				$text0 = 'text_no';
			}
		} else {
			$text1 = 'text_yes';
			$text0 = 'text_no';
		}
		$id = isset($param['id']) ? ' id="'.$param['id'].'"' : '';
		$tmpl = '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="exchange1c_'.$name.'" value="1"'.($value == 1 ? ' checked = "checked"' : '').'>';
		$tmpl .= '&nbsp;'.$this->language->get($text1);
		$tmpl .= '</label>';
		$tmpl .= '<label class="radio-inline">';
		$tmpl .= '<input type="radio" name="exchange1c_'.$name.'" value="-1"'.($value == -1 ? ' checked = "checked"' : '').'>';
		$tmpl .= '&nbsp;'.$this->language->get($text0);
		$tmpl .= '</label>';
		return $tmpl;
	} // htmlRadio()


	/**
	 * Формирует форму кнопки
	 */
	private function htmlButton($name) {
		$tmpl = '<button id="exchange1c-button-'.$name.'" class="btn btn-primary" type="button" data-loading-text="' . $this->language->get('entry_button_'.$name). '">';
		$tmpl .= '<i class="fa fa-trash-o fa-lg"></i> ' . $this->language->get('text_button_'.$name) . '</button>';
		return $tmpl;
	} // htmlButton()


	/**
	 * Формирует форму картинки
	 */
	private function htmlImage($name, $param) {
		$tmpl = '<a title="" class="img_thumbnail" id="thumb-image0" aria-describedby="popover" href="" data-original-title="" data-toggle="image">';
		$tmpl .= '<img src="' . $param['thumb'] . '" data-placeholder="' . $param['ph'] . '" alt="" />';
		$tmpl .= '<input name="exchange1c_' . $name . '" id="input_image0" value="' . $param['value'] . '" type="hidden" /></a>';
		return $tmpl;
	} // htmlImage()


	/**
	 * Формирует форму поля ввода
	 */
	private function htmlInput($name, $param, $type='text') {
		$value = $this->getParam($name);
		if (empty($value) && !empty($param['default'])) $value = $param['default'];
		if ($this->language->get('ph_'.$name) != 'ph_'.$name) {
			$placeholder = ' placeholder="' . $this->language->get('ph_'.$name) . '"';
		} else {
			$placeholder = '';
		}
		$tmpl = '<input class="form-control"' . $placeholder . ' type="'.$type.'" id="exchange1c_'.$name.'" name="exchange1c_'.$name.'" value="'.$value.'">';
		return $tmpl;
	} // htmlInput()


	/**
	 * Формирует форму ...
	 */
	private function htmlParam($name, $text, $param, $head=false) {
		$tmpl = '';
		if ($head) {
			$tmpl .= '<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-pencil"></i>' . $this->language->get('legend_'.$name) . '</h3></div>';
		}
		//var_dump('<PRE>');var_dump($name);var_dump($param);var_dump('</PRE>');
		$label_width = isset($param['width'][0]) ? $param['width'][0] : 2;
		$entry_width = isset($param['width'][1]) ? $param['width'][1] : 2;
		$desc_width = isset($param['width'][2]) ? $param['width'][2] : 8;
		if ($label_width) {
			$tmpl .= '<label class="col-sm-'.$label_width.' control-label">'. $this->language->get('entry_'.$name) . '</label>';
		}
		$tmpl .= '<div class="col-sm-'.$entry_width.'">' . $text . '</div>';
		if ($desc_width) {
			$tmpl .= '<div class="col-sm-'.$desc_width.'"><div class="alert alert-info"><i class="fa fa-info-circle"></i> '. $this->language->get('desc_'.$name) . '</div></div>';
		}

		return $tmpl;
	} // HtmlParam()


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
	 * Основная функция
	 */
	public function index() {

		$data['lang'] = $this->load->language('module/exchange1c');
		$this->load->model('tool/image');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$data['update'] = "";
		$this->load->model('tool/exchange1c');
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// При нажатии кнопки сохранить
			$settings = $this->request->post;
			$settings['exchange1c_version'] = $this->config->get('exchange1c_version');
			$settings['exchange1c_order_date'] = $this->config->get('exchange1c_order_date');
			$settings['exchange1c_CMS_version'] = VERSION;

			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		} else {
			$settings = $this->model_setting_setting->getSetting('exchange1c', 0);

			if (!isset($settings['exchange1c_version'])) {
				// Чистая установка
				$this->install($settings);
				$this->load->model('extension/extension');
				$this->model_extension_extension->install('module', 'exchange1c');
				$data['update'] = "Модуль установлен";
			}
			$data['update'] = $this->model_tool_exchange1c->checkUpdates($settings);
		}

		$settings = $this->model_setting_setting->getSetting('exchange1c', 0);
		$data['version'] = $settings['exchange1c_version'];

		$data['exchange1c_config_icon'] = $this->getParam('config_icon');

		// error_warning
		$this->setParamError($data, 'warning');

		// Проверка базы данных
		$data['error_warning'] .= $this->model_tool_exchange1c->checkDB();

		$this->setParamError($data, 'image');
		$this->setParamError($data, 'exchange1c_username');
		$this->setParamError($data, 'exchange1c_password');

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

		/**
		 * ГЕНЕРАЦИЯ ШАБЛОНА
		 */

		// Магазины
		if (isset($this->request->post['exchange1c_stores'])) {
			$data['exchange1c_stores'] = $this->request->post['exchange1c_stores'];
		}
		else {
			$data['exchange1c_stores'] = $this->config->get('exchange1c_stores');
			if(empty($data['exchange1c_stores'])) {
				$data['exchange1c_stores'][] = array(
					'store_id'	=> 0,
					'name'		=> ''
				);
			}
		}

		// свойства из торговой системы
		if (isset($this->request->post['exchange1c_properties'])) {
			$data['exchange1c_properties'] = $this->request->post['exchange1c_properties'];
		}
		else {
			$data['exchange1c_properties'] = $this->config->get('exchange1c_properties');
			if(empty($data['exchange1c_properties'])) {
				$data['exchange1c_properties'] = array();
			}
		}

		//$data['config_stores'] = $this->getParam('stores', array());
		//var_dump('<PRE>');var_dump($data['config_stores']);var_dump('</PRE>');
		$stores = $this->db->query("SELECT * FROM `" . DB_PREFIX . "store`")->rows;
		$data['stores'] = array();
		$data['stores'][0] = $this->config->get('config_name');
		foreach ($stores as $store) {
			$data['stores'][$store['store_id']] = $store['name'];
		}

		// Поля товара для записи
		$data['product_fields'] = array(
			''		=> $this->language->get('text_not_import')
			,'sku'	=> $this->language->get('text_product_sku')
			,'ean'	=> $this->language->get('text_product_ean')
			,'mpn'	=> $this->language->get('text_product_mpn')
		);

		// Картинки
		$images = array(
			'watermark'	=> array(
				'ph'			=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'value'			=> $this->getParam('watermark'),
				'thumb'			=> $this->getParam('watermark') ? $this->model_tool_image->resize($this->getParam('watermark'), 100, 100) : $this->model_tool_image->resize('no_image.png', 100, 100)
			)
		);

		// Уровень записи в журнал
		$log_level_list = array(
			0	=> $this->language->get('text_log_level_0'),
			1	=> $this->language->get('text_log_level_1'),
			2	=> $this->language->get('text_log_level_2'),
			3	=> $this->language->get('text_log_level_3')
		);

		$file_exchange_list = array(
			'zip'	=> $this->language->get('text_file_exchange_zip'),
			'files'	=> $this->language->get('text_file_exchange_files')
		);

		// SEO
		if (isset($this->request->post['exchange1c_seo_product_tags'])) {
			$data['exchange1c_seo_product_tags'] = $this->request->post['exchange1c_seo_product_tags'];
		} else {
			$data['exchange1c_seo_product_tags'] = '{name}, {fullname}, {sku}, {brand}, {model}, {cats}, {prod_id}, {cat_id}';
		}

		if (isset($this->request->post['exchange1c_seo_category_tags'])) {
			$data['exchange1c_seo_category_tags'] = $this->request->post['exchange1c_seo_category_tags'];
		} else {
			$data['exchange1c_seo_category_tags'] = '{cat}, {cat_id}';
		}

		if (isset($this->request->post['exchange1c_seo_manufacturertags'])) {
			$data['exchange1c_seo_manufacturer_tags'] = $this->request->post['exchange1c_seo_manufacturer_tags'];
		} else {
			$data['exchange1c_seo_manufacturer_tags'] = '{brand}, {brand_id}';
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
		$list_seo_mode = array(
			'disable'		=> $this->language->get('text_disable')
		);
		if ($this->config->get('config_seo_url') == 1) {
			$list_seo_mode['if_empty'] 	= $this->language->get('text_if_empty');
			$list_seo_mode['overwrite']	= $this->language->get('text_overwrite');
		}

		// Статус товара по умолчанию при отсутствии
		$this->load->model('localisation/stock_status');
		$stock_statuses_info = $this->model_localisation_stock_status->getStockStatuses();
		$stock_statuses = array();
		foreach ($stock_statuses_info as $status) {
			$stock_statuses[$status['stock_status_id']] = $status['name'];

		}

		// список статусов заказов
		$this->load->model('localisation/order_status');
		$order_statuses_info = $this->model_localisation_order_status->getOrderStatuses();
		$order_statuses = array();
		$order_statuses[] = $this->language->get('text_order_status_to_exchange_not');
		foreach ($order_statuses_info as $order_status) {
			$order_statuses[$order_status['order_status_id']] = $order_status['name'];
		}

		$list_options = array(
			'feature'	=> $this->language->get('text_product_options_feature')
			,'related'	=> $this->language->get('text_product_options_related')
			//,'certine'	=> $this->language->get('text_product_options_certine')
		);

		$list_options_type = array(
			'select'	=> $this->language->get('text_product_options_type_select'),
			'radio'		=> $this->language->get('text_product_options_type_radio')
		);

		$select_import_product = array(
			'disable'	=> $this->language->get('text_disable'),
			'name'		=> $this->language->get('text_product_name'),
			'fullname'	=> $this->language->get('text_product_fullname')
		);

		$select_sync_new_poroduct = array(
			'sku'    	=> $this->language->get('text_product_sku'),
			'name'		=> $this->language->get('text_product_name'),
			'ean'		=> $this->language->get('text_product_ean')
		);

		$select_sync_attributes = array(
			'guid'    	=> $this->language->get('text_guid'),
			'name'		=> $this->language->get('text_name'),
		);

		$list_price_import_to = array(
			'discount'    	=> $this->language->get('text_discount'),
			'special'		=> $this->language->get('text_special'),
		);

		// Генерация опций
		$params = array(
			'cleaning_db' 							=> array('type' => 'button')
			,'cleaning_links' 						=> array('type' => 'button')
			,'cleaning_cache' 						=> array('type' => 'button')
			,'cleaning_old_images' 					=> array('type' => 'button')
			,'generate_seo' 						=> array('type' => 'button')
			,'flush_quantity_category'				=> array('type' => 'radio', 'default' => -1)
			,'watermark'							=> array('type' => 'image')
			,'allow_ip'								=> array('type' => 'textarea')
			,'import_images'						=> array('type' => 'radio', 'default' => 1)
			,'import_categories'					=> array('type' => 'radio', 'default' => 1)
			,'import_product_categories'			=> array('type' => 'radio', 'default' => 1)
			,'import_product_name'					=> array('type' => 'select', 'options' => $select_import_product, 'default' => 'name')
			,'import_product_description'			=> array('type' => 'radio', 'default' => 1)
			,'import_product_manufacturer'			=> array('type' => 'radio', 'default' => 1)
			,'status_new_product'					=> array('type' => 'radio', 'default' => 1, 'text' => 'on_off')
			,'status_new_category'					=> array('type' => 'radio', 'default' => 1, 'text' => 'on_off')
			,'description_html'						=> array('type' => 'radio', 'default' => 1)
			,'fill_parent_cats'						=> array('type' => 'radio', 'default' => 1)
			,'product_disable_if_quantity_zero'		=> array('type' => 'radio', 'default' => -1)
			,'product_disable_if_price_zero'		=> array('type' => 'radio', 'default' => -1)
			,'create_new_product'					=> array('type' => 'radio', 'default' => 1)
			,'create_new_category'					=> array('type' => 'radio', 'default' => 1)
			,'synchronize_by_code'					=> array('type' => 'radio', 'default' => -1)
			,'synchronize_new_product_by'        	=> array('type' => 'select', 'options' => $select_sync_new_poroduct, 'default' => 'sku')
			,'synchronize_attribute_by' 	      	=> array('type' => 'select', 'options' => $select_sync_attributes, 'default' => 'guid')
			,'status'								=> array('type' => 'radio', 'default' => 1)
			,'flush_log'							=> array('type' => 'radio', 'default' => 1)
			,'currency_convert'						=> array('type' => 'radio', 'default' => 1)
			,'convert_orders_cp1251'				=> array('type' => 'radio', 'default' => 1)
			,'parse_only_types_item'				=> array('type' => 'input')
			,'username'								=> array('type' => 'input',)
			,'password'								=> array('type' => 'input',)
			,'price_types_auto_load'				=> array('type' => 'radio', 'default' => 1)
			,'seo_product_seo_url_import'			=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_product_seo_url_template'			=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_title_import'		=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_product_meta_title_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_description_import'	=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_product_meta_description_template'=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_meta_keyword_import'		=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_product_meta_keyword_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_product_tag_import'				=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_product_tag_template'				=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_seo_url_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_title_template'		=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_description_template'=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_category_meta_keyword_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_seo_url_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_title_import'	=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_manufacturer_meta_title_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_description_import'	=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_manufacturer_meta_description_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'seo_manufacturer_meta_keyword_import'	=> array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1)
			,'seo_manufacturer_meta_keyword_template'	=> array('type' => 'input', 'width' => array(0,9,0))
			,'order_currency'						=> array('type' => 'input')
			,'ignore_price_zero'					=> array('type' => 'radio', 'default' => 1)
			,'log_memory_use_view'					=> array('type' => 'radio', 'default' => 1)
			,'log_debug_line_view'					=> array('type' => 'radio', 'default' => 1)
			,'order_notify'							=> array('type' => 'radio', 'default' => 1)
			,'product_options_mode'					=> array('type' => 'select', 'options' => $list_options)
			,'product_options_subtract'				=> array('type' => 'radio', 'default' => 1)
			,'default_stock_status'					=> array('type'	=> 'select', 'options' => $stock_statuses)
			,'log_level'							=> array('type' => 'select', 'options' => $log_level_list)
			,'file_exchange'						=> array('type' => 'select', 'options' => $file_exchange_list)
			,'seo_product_mode'						=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_product_seo_url'					=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_title'				=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_description'			=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_meta_keyword'				=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_product_tag'						=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_category_mode'					=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_category_seo_url'					=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_title'				=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_description'		=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_category_meta_keyword'			=> array('type' => 'select', 'options' => $list_category, 'width' => array(1,2,0))
			,'seo_manufacturer_mode'				=> array('type' => 'select', 'options' => $list_seo_mode, 'width' => array(1,2,9))
			,'seo_manufacturer_seo_url'				=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_title'			=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_description'	 => array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'seo_manufacturer_meta_keyword'		=> array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0))
			,'order_modify_exchange'				=> array('type' => 'radio', 'default' => 1)
			,'order_status_to_exchange'				=> array('type' => 'select', 'options' => $order_statuses)
			,'order_status_change'					=> array('type' => 'select', 'options' => $order_statuses)
			,'order_notify_subject'					=> array('type' => 'input')
			,'order_notify_text'					=> array('type' => 'textarea')
			,'set_quantity_if_zero'					=> array('type' => 'input')
			,'export_module_to_all'					=> array('type' => 'radio', 'default' => -1)
			,'price_import_to'						=> array('type' => 'select', 'options' => $list_price_import_to)

		);

		if ($this->model_tool_exchange1c->existField('product_description', 'meta_h1')) {
			$params['seo_product_meta_h1_import']	= array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1);
			$params['seo_product_meta_h1_template']	= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_product_meta_h1']			= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}
		if ($this->model_tool_exchange1c->existField('category_description', 'meta_h1')) {
			$params['seo_category_meta_h1_import']	= array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1);
			$params['seo_category_meta_h1_template']	= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_category_meta_h1']			= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}
		if ($this->model_tool_exchange1c->existField('manufacturer_description', 'meta_h1')) {
			$params['seo_manufacturer_meta_h1_import']	= array('type' => 'input', 'width' => array(0,9,0), 'hidden'=>1);
			$params['seo_manufacturer_meta_h1_template']	= array('type' => 'input', 'width' => array(0,9,0));
			$params['seo_manufacturer_meta_h1']			= array('type' => 'select', 'options' => $list_product, 'width' => array(1,2,0));
		}

		foreach ($params as $name => $param) {
			$html = '';
			switch ($param['type']) {
				case 'button':
					$html = $this->htmlButton($name, $param);
					break;
				case 'radio':
					$html = $this->htmlRadio($name, $param);
					break;
				case 'select':
					$html = $this->htmlSelect($name, $param);
					break;
				case 'image':
					$html = $this->htmlImage($name, $images[$name]);
					break;
				case 'input':
					$html = $this->htmlInput($name, $param);
					break;
				case 'textarea':
					$html = $this->htmlTextarea($name, $param);
					break;
			}
			if ($html)
				$data['html_'.$name] = $this->htmlParam($name, $html, $param);
		}


		// Группы покупателей
		if (version_compare(VERSION, '2.0.3.1', '>')) {
			$this->load->model('customer/customer_group');
			$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();
			array_unshift($data['customer_groups'], array('customer_group_id'=>0,'sort_order'=>0,'name'=>'--- Выберите ---'));
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
				$data['exchange1c_price_type'] = array();
//				$data['exchange1c_price_type'][] = array(
//					'keyword'			=> '',
//					'id_cml'			=> '',
//					'customer_group_id'	=> $this->config->get('config_customer_group_id'),
//					'quantity'			=> 1,
//					'priority'			=> 1
//				);
			}
		}


	 	// максимальный размер загружаемых файлов
		$data['lang']['text_max_filesize'] = sprintf($this->language->get('text_max_filesize'), @ini_get('max_file_uploads'));
		$data['upload_max_filesize'] = ini_get('upload_max_filesize');
		$data['post_max_size'] = ini_get('post_max_size');

		$links_info = $this->model_tool_exchange1c->linksInfo();
		$data['links_product_info'] = $links_info['product_to_1c'];
		$data['links_category_info'] = $links_info['category_to_1c'];
		$data['links_manufacturer_info'] = $links_info['manufacturer_to_1c'];
		$data['links_attribute_info'] = $links_info['attribute_to_1c'];

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
	 * Установка модуля
	 */
	public function install(&$settings) {

		$this->load->model('tool/exchange1c');
		$this->model_tool_exchange1c->setEvents();
		//$module_version = $this->model_tool_exchange1c->version();
		$module_version = '1.6.2.b9';

		$this->load->model('setting/setting');
		$settings['exchange1c_version'] 					= $module_version;
		$settings['exchange1c_name'] 						= 'Exchange 1C 8.x for OpenCart 2.x';
		$settings['exchange1c_CMS_version']					= VERSION;
		$settings['exchange1c_seo_category_name'] 			= '[category_name]';
		$settings['exchange1c_seo_parent_category_name'] 	= '[parent_category_name]';
		$settings['exchange1c_seo_product_name'] 			= '[product_name]';
		$settings['exchange1c_seo_product_price'] 			= '[product_price]';
		$settings['exchange1c_seo_manufacturer'] 			= '[manufacturer]';
		$settings['exchange1c_seo_sku'] 					= '[sku]';

		$this->model_setting_setting->editSetting('exchange1c', $settings);

//		$this->load->model('extension/module');
//		$this->model_extension_module->addModule('exchange1c',
//			array(
//				'version'	=> $this->module_version,
//				'name'		=> $this->module_name
//			)
//		);

		// Изменения в базе данных
		$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `product_feature_id` INT( 11 ) NOT NULL DEFAULT 0 AFTER  `option`");
		$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `unit_id` INT( 11 ) NOT NULL DEFAULT 0 AFTER  `option`");
		$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` DROP INDEX  `cart_id` ,	ADD INDEX  `cart_id` (  `customer_id` ,  `session_id` ,  `product_id` ,  `recurring_id` ,  `product_feature_id` , `unit_id`)");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		// Увеличиваем точность поля веса до тысячных
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `quantity` `quantity` decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT 'Количество'");
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `weight` `weight` decimal(15,3) NOT NULL DEFAULT 0.000 COMMENT 'Вес'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` CHANGE `quantity` `quantity` decimal(15,3) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Связь товаров с 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "product_to_1c` (
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`1c_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид товара в 1С',
				KEY (`product_id`),
				KEY (`1c_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `". DB_PREFIX ."product`(`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Связь категорий с 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "category_to_1c` (
				`category_id` 				INT(11) 		NOT NULL 				COMMENT 'ID категории',
				`1c_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид категории в 1С',
				KEY (`category_id`),
				KEY (`1c_id`),
				FOREIGN KEY (`category_id`) 			REFERENCES `". DB_PREFIX ."category`(`category_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Свойства из 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "attribute_to_1c` (
				`attribute_id` 				INT(11) 		NOT NULL 				COMMENT 'ID атрибута',
				`1c_id`						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид свойства в 1С',
				KEY (`attribute_id`),
				KEY (`1c_id`),
				FOREIGN KEY (`attribute_id`) 			REFERENCES `". DB_PREFIX ."attribute`(`attribute_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка производителя к каталогу 1С
		// В Ид производителя из 1С записывается либо Ид свойства сопоставленное либо Ид элемента справочника с производителями
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "manufacturer_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "manufacturer_to_1c` (
				`manufacturer_id` 			INT(11) 		NOT NULL 				COMMENT 'ID производителя',
				`1c_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид производителя в 1С',
				PRIMARY KEY (`manufacturer_id`),
				KEY (`1c_id`),
				FOREIGN KEY (`manufacturer_id`) 		REFERENCES `". DB_PREFIX ."manufacturer`(`manufacturer_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);


		// Привязка магазина к каталогу в 1С
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "store_to_1c` (
				`store_id` 					INT(11) 		NOT NULL 				COMMENT 'Код магазина',
				`1c_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид каталога в 1С',
				KEY (`store_id`),
				KEY (`1c_id`),
				FOREIGN KEY (`store_id`) 				REFERENCES `". DB_PREFIX ."store`(`store_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Остатки товара
		// Хранятся остатки товара как с характеристиками, так и без.
		// Если склады и характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_quantity` (
				`product_quantity_id` 		INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'Ссылка на товар',
				`product_feature_id` 		INT(11) 		DEFAULT 0 NOT NULL		COMMENT 'Ссылка на характеристику товара',
				`warehouse_id` 				INT(11) 		DEFAULT 0 NOT NULL 		COMMENT 'Ссылка на склад',
				`quantity` 					DECIMAL(10,3) 	DEFAULT 0 				COMMENT 'Остаток',
				PRIMARY KEY (`product_quantity_id`),
				FOREIGN KEY (`product_id`) 			REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 	REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`warehouse_id`) 		REFERENCES `" . DB_PREFIX . "warehouse`(`warehouse_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// склады
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "warehouse` (
				`warehouse_id` 				SMALLINT(3) 	NOT NULL AUTO_INCREMENT,
				`name` 						VARCHAR(100) 	NOT NULL DEFAULT '' 	COMMENT 'Название склада в 1С',
				`1c_id` 					VARCHAR(64) 	NOT NULL				COMMENT 'Ид склада в 1С',
				PRIMARY KEY (`warehouse_id`),
				KEY (`1c_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Характеристики товара
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_feature` (
				`product_feature_id` 		INT(11) 		NOT NULL AUTO_INCREMENT COMMENT 'Счетчик',
				`ean` 						VARCHAR(14) 	NOT NULL DEFAULT '' 	COMMENT 'Штрихкод',
				`name` 						VARCHAR(255) 	NOT NULL DEFAULT '' 	COMMENT 'Название',
				`sku` 						VARCHAR(128) 	NOT NULL DEFAULT '' 	COMMENT 'Артикул',
				`1c_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид характеристики в 1С',
				PRIMARY KEY (`product_feature_id`),
				KEY (`1c_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Значения характеристики товара(доп. значения)
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_feature_value` (
				`product_feature_value_id`	INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_feature_id` 		INT(11) 		NOT NULL 				COMMENT 'ID характеристики товара',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_option_id` 		INT(11) 		NOT NULL 				COMMENT 'ID опции товара',
				`product_option_value_id` 	INT(11) 		NOT NULL 				COMMENT 'ID значения опции товара',
				PRIMARY KEY (`product_feature_value_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`product_option_id`) 		REFERENCES `" . DB_PREFIX . "product_option`(`product_option_id`),
				FOREIGN KEY (`product_option_value_id`)	REFERENCES `" . DB_PREFIX . "product_option_value`(`product_option_value_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Цены, если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_price` (
				`product_price_id` 			INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_feature_id` 		INT(11) 		NOT NULL DEFAULT '0' 	COMMENT 'ID характеристики товара',
				`customer_group_id`			INT(11) 		NOT NULL DEFAULT '0'	COMMENT 'ID группы покупателя',
				`price` 					DECIMAL(15,4) 	NOT NULL DEFAULT '0'	COMMENT 'Цена',
				PRIMARY KEY (`product_price_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Единицы измерения товара (упаковки товара)
		// Если используются упаковки, то в эту таблицу записываются дополнительные единицы измерения
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_unit` (
				`product_unit_id`			INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_feature_id` 		INT(11) 		NOT NULL DEFAULT '0' 	COMMENT 'ID характеристики товара',
				`unit_id` 					INT(11) 		DEFAULT '0' NOT NULL 	COMMENT 'ID единицы измерения',
				`ratio` 					INT(9) 			DEFAULT '1' 			COMMENT 'Коэффициент пересчета количества',
				PRIMARY KEY (`product_unit_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `" . DB_PREFIX . "unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка единиц измерения к торговой системе
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "unit_to_1c` (
				`unit_id` 					SMALLINT(6) 	NOT NULL 				COMMENT 'ID единицы измерения по каталогу',
				`cml_id` 					VARCHAR(64) 	NOT NULL 				COMMENT 'Ид единицы измерения в ТС',
				`name` 						VARCHAR(16) 	NOT NULL 				COMMENT 'Наименование краткое',
				`code` 						VARCHAR(4) 		NOT NULL 				COMMENT 'Код числовой',
				`fullname` 					VARCHAR(50) 	NOT NULL 				COMMENT 'Наименование полное',
				`eng_name2` 				VARCHAR(50)		NOT NULL 				COMMENT 'Международное сокращение',
				KEY (`cml_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `". DB_PREFIX ."unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Классификатор единиц измерения
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit` (
				`unit_id` 					SMALLINT(6) 	NOT NULL AUTO_INCREMENT COMMENT 'Счетчик',
				`name` 						VARCHAR(255) 	NOT NULL 				COMMENT 'Наименование единицы измерения',
				`number_code` 				VARCHAR(5) 		NOT NULL 				COMMENT 'Код',
				`rus_name1` 				VARCHAR(50) 	DEFAULT '' NOT NULL		COMMENT 'Условное обозначение национальное',
				`eng_name1` 				VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Условное обозначение международное',
				`rus_name2` 				VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Кодовое буквенное обозначение национальное',
				`eng_name2` 				VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Кодовое буквенное обозначение международное',
				`unit_group_id`  			TINYINT(4) 		NOT NULL 				COMMENT 'Группа единиц измерения',
				`unit_type_id` 				TINYINT(4) 		NOT NULL 				COMMENT 'Раздел/приложение в которое входит единица измерения',
				`visible` 					TINYINT(4) 		DEFAULT '1' NOT NULL 	COMMENT 'Видимость',
				`comment` 					VARCHAR(255) 	DEFAULT '' NOT NULL 	COMMENT 'Комментарий',
				PRIMARY KEY (`unit_id`),
				UNIQUE KEY number_code (`number_code`),
  				KEY unit_group_id (`unit_group_id`),
  				KEY unit_type_id (`unit_type_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор единиц измерения ОКЕИ'"
		);

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_group`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit_group` (
				`unit_group_id` 			TINYINT(4) 		NOT NULL AUTO_INCREMENT COMMENT 'Счетчик',
				`name` 						VARCHAR(255) 	NOT NULL 				COMMENT 'Наименование группы',
				PRIMARY KEY (`unit_group_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Группы единиц измерения'"
		);

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_type`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit_type` (
				`unit_type_id` 			TINYINT(4) 			NOT NULL AUTO_INCREMENT COMMENT 'Счетчик',
				`name` 					VARCHAR(255) 		NOT NULL 				COMMENT 'Наименование раздела/приложения',
				PRIMARY KEY (`unit_type_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Разделы/приложения, в которые включены единицы измерения'"
		);

		// Загрузка классификатора единиц измерений
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(6, 'Единицы времени')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(1, 'Единицы длины')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(4, 'Единицы массы')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(3, 'Единицы объема')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(2, 'Единицы площади')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(5, 'Технические единицы')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_group` (unit_group_id, name) VALUES(7, 'Экономические единицы')");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_type` (unit_type_id, name) VALUES(1, 'Международные единицы измерения, включенные в ЕСКК')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_type` (unit_type_id, name) VALUES(2, 'Национальные единицы измерения, включенные в ЕСКК')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit_type` (unit_type_id, name) VALUES(3, 'Международные единицы измерения, не включенные в ЕСКК')");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(1, 'Миллиметр', '003', 'мм', 'mm', 'ММ', 'MMT', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(2, 'Сантиметр', '004', 'см', 'cm', 'СМ', 'CMT', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(3, 'Дециметр', '005', 'дм', 'dm', 'ДМ', 'DMT', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(4, 'Метр', '006', 'м', 'm', 'М', 'MTR', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(5, 'Километр; тысяча метров', '008', 'км; 10^3 м', 'km', 'КМ; ТЫС М', 'KMT', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(6, 'Мегаметр; миллион метров', '009', 'Мм; 10^6 м', 'Mm', 'МЕГАМ; МЛН М', 'MAM', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(7, 'Дюйм (25,4 мм)', '039', 'дюйм', 'in', 'ДЮЙМ', 'INH', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(8, 'Фут (0,3048 м)', '041', 'фут', 'ft', 'ФУТ', 'FOT', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(9, 'Ярд (0,9144 м)', '043', 'ярд', 'yd', 'ЯРД', 'YRD', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(10, 'Морская миля (1852 м)', '047', 'миля', 'n mile', 'МИЛЬ', 'NMI', 1, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(11, 'Квадратный миллиметр', '050', 'мм2', 'mm2', 'ММ2', 'MMK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(12, 'Квадратный сантиметр', '051', 'см2', 'cm2', 'СМ2', 'CMK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(13, 'Квадратный дециметр', '053', 'дм2', 'dm2', 'ДМ2', 'DMK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(14, 'Квадратный метр', '055', 'м2', 'm2', 'М2', 'MTK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(15, 'Тысяча квадратных метров', '058', '10^3 м^2', 'daa', 'ТЫС М2', 'DAA', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(16, 'Гектар', '059', 'га', 'ha', 'ГА', 'HAR', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(17, 'Квадратный километр', '061', 'км2', 'km2', 'КМ2', 'KMK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(18, 'Квадратный дюйм (645,16 мм2)', '071', 'дюйм2', 'in2', 'ДЮЙМ2', 'INK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(19, 'Квадратный фут (0,092903 м2)', '073', 'фут2', 'ft2', 'ФУТ2', 'FTK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(20, 'Квадратный ярд (0,8361274 м2)', '075', 'ярд2', 'yd2', 'ЯРД2', 'YDK', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(21, 'Ар (100 м2)', '109', 'а', 'a', 'АР', 'ARE', 2, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(22, 'Кубический миллиметр', '110', 'мм3', 'mm3', 'ММ3', 'MMQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(23, 'Кубический сантиметр; миллилитр', '111', 'см3; мл', 'cm3; ml', 'СМ3; МЛ', 'CMQ; MLT', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(24, 'Литр; кубический дециметр', '112', 'л; дм3', 'I; L; dm^3', 'Л; ДМ3', 'LTR; DMQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(25, 'Кубический метр', '113', 'м3', 'm3', 'М3', 'MTQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(26, 'Децилитр', '118', 'дл', 'dl', 'ДЛ', 'DLT', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(27, 'Гектолитр', '122', 'гл', 'hl', 'ГЛ', 'HLT', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(28, 'Мегалитр', '126', 'Мл', 'Ml', 'МЕГАЛ', 'MAL', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(29, 'Кубический дюйм (16387,1 мм3)', '131', 'дюйм3', 'in3', 'ДЮЙМ3', 'INQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(30, 'Кубический фут (0,02831685 м3)', '132', 'фут3', 'ft3', 'ФУТ3', 'FTQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(31, 'Кубический ярд (0,764555 м3)', '133', 'ярд3', 'yd3', 'ЯРД3', 'YDQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(32, 'Миллион кубических метров', '159', '10^6 м3', '10^6 m3', 'МЛН М3', 'HMQ', 3, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(33, 'Гектограмм', '160', 'гг', 'hg', 'ГГ', 'HGM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(34, 'Миллиграмм', '161', 'мг', 'mg', 'МГ', 'MGM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(35, 'Метрический карат', '162', 'кар', 'МС', 'КАР', 'CTM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(36, 'Грамм', '163', 'г', 'g', 'Г', 'GRM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(37, 'Килограмм', '166', 'кг', 'kg', 'КГ', 'KGM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(38, 'Тонна; метрическая тонна (1000 кг)', '168', 'т', 't', 'Т', 'TNE', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(39, 'Килотонна', '170', '10^3 т', 'kt', 'КТ', 'KTN', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(40, 'Сантиграмм', '173', 'сг', 'cg', 'СГ', 'CGM', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(41, 'Брутто-регистровая тонна (2,8316 м3)', '181', 'БРТ', '-', 'БРУТТ. РЕГИСТР Т', 'GRT', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(42, 'Грузоподъемность в метрических тоннах', '185', 'т грп', '-', 'Т ГРУЗОПОД', 'CCT', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(43, 'Центнер (метрический) (100 кг); гектокилограмм; квинтал1 (метрический); децитонна', '206', 'ц', 'q; 10^2 kg', 'Ц', 'DTN', 4, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(44, 'Ватт', '212', 'Вт', 'W', 'ВТ', 'WTT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(45, 'Киловатт', '214', 'кВт', 'kW', 'КВТ', 'KWT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(46, 'Мегаватт; тысяча киловатт', '215', 'МВт; 10^3 кВт', 'MW', 'МЕГАВТ; ТЫС КВТ', 'MAW', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(47, 'Вольт', '222', 'В', 'V', 'В', 'VLT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(48, 'Киловольт', '223', 'кВ', 'kV', 'КВ', 'KVT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(49, 'Киловольт-ампер', '227', 'кВ.А', 'kV.A', 'КВ.А', 'KVA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(50, 'Мегавольт-ампер (тысяча киловольт-ампер)', '228', 'МВ.А', 'MV.A', 'МЕГАВ.А', 'MVA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(51, 'Киловар', '230', 'квар', 'kVAR', 'КВАР', 'KVR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(52, 'Ватт-час', '243', 'Вт.ч', 'W.h', 'ВТ.Ч', 'WHR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(53, 'Киловатт-час', '245', 'кВт.ч', 'kW.h', 'КВТ.Ч', 'KWH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(54, 'Мегаватт-час; 1000 киловатт-часов', '246', 'МВт.ч; 10^3 кВт.ч', 'МW.h', 'МЕГАВТ.Ч; ТЫС КВТ.Ч', 'MWH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(55, 'Гигаватт-час (миллион киловатт-часов)', '247', 'ГВт.ч', 'GW.h', 'ГИГАВТ.Ч', 'GWH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(56, 'Ампер', '260', 'А', 'A', 'А', 'AMP', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(57, 'Ампер-час (3,6 кКл)', '263', 'А.ч', 'A.h', 'А.Ч', 'AMH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(58, 'Тысяча ампер-часов', '264', '10^3 А.ч', '10^3 A.h', 'ТЫС А.Ч', 'TAH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(59, 'Кулон', '270', 'Кл', 'C', 'КЛ', 'COU', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(60, 'Джоуль', '271', 'Дж', 'J', 'ДЖ', 'JOU', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(61, 'Килоджоуль', '273', 'кДж', 'kJ', 'КДЖ', 'KJO', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(62, 'Ом', '274', 'Ом', '<омега>', 'ОМ', 'OHM', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(63, 'Градус Цельсия', '280', 'град. C', 'град. C', 'ГРАД ЦЕЛЬС', 'CEL', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(64, 'Градус Фаренгейта', '281', 'град. F', 'град. F', 'ГРАД ФАРЕНГ', 'FAN', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(65, 'Кандела', '282', 'кд', 'cd', 'КД', 'CDL', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(66, 'Люкс', '283', 'лк', 'lx', 'ЛК', 'LUX', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(67, 'Люмен', '284', 'лм', 'lm', 'ЛМ', 'LUM', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(68, 'Кельвин', '288', 'K', 'K', 'К', 'KEL', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(69, 'Ньютон', '289', 'Н', 'N', 'Н', 'NEW', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(70, 'Герц', '290', 'Гц', 'Hz', 'ГЦ', 'HTZ', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(71, 'Килогерц', '291', 'кГц', 'kHz', 'КГЦ', 'KHZ', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(72, 'Мегагерц', '292', 'МГц', 'MHz', 'МЕГАГЦ', 'MHZ', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(73, 'Паскаль', '294', 'Па', 'Pa', 'ПА', 'PAL', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(74, 'Сименс', '296', 'См', 'S', 'СИ', 'SIE', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(75, 'Килопаскаль', '297', 'кПа', 'kPa', 'КПА', 'KPA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(76, 'Мегапаскаль', '298', 'МПа', 'MPa', 'МЕГАПА', 'MPA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(77, 'Физическая атмосфера (101325 Па)', '300', 'атм', 'atm', 'АТМ', 'ATM', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(78, 'Техническая атмосфера (98066,5 Па)', '301', 'ат', 'at', 'АТТ', 'ATT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(79, 'Гигабеккерель', '302', 'ГБк', 'GBq', 'ГИГАБК', 'GBQ', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(80, 'Милликюри', '304', 'мКи', 'mCi', 'МКИ', 'MCU', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(81, 'Кюри', '305', 'Ки', 'Ci', 'КИ', 'CUR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(82, 'Грамм делящихся изотопов', '306', 'г Д/И', 'g fissile isotopes', 'Г ДЕЛЯЩ ИЗОТОП', 'GFI', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(83, 'Миллибар', '308', 'мб', 'mbar', 'МБАР', 'MBR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(84, 'Бар', '309', 'бар', 'bar', 'БАР', 'BAR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(85, 'Гектобар', '310', 'гб', 'hbar', 'ГБАР', 'HBA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(86, 'Килобар', '312', 'кб', 'kbar', 'КБАР', 'KBA', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(87, 'Фарад', '314', 'Ф', 'F', 'Ф', 'FAR', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(88, 'Килограмм на кубический метр', '316', 'кг/м3', 'kg/m3', 'КГ/М3', 'KMQ', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(89, 'Беккерель', '323', 'Бк', 'Bq', 'БК', 'BQL', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(90, 'Вебер', '324', 'Вб', 'Wb', 'ВБ', 'WEB', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(91, 'Узел (миля/ч)', '327', 'уз', 'kn', 'УЗ', 'KNT', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(92, 'Метр в секунду', '328', 'м/с', 'm/s', 'М/С', 'MTS', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(93, 'Оборот в секунду', '330', 'об/с', 'r/s', 'ОБ/С', 'RPS', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(94, 'Оборот в минуту', '331', 'об/мин', 'r/min', 'ОБ/МИН', 'RPM', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(95, 'Километр в час', '333', 'км/ч', 'km/h', 'КМ/Ч', 'KMH', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(96, 'Метр на секунду в квадрате', '335', 'м/с2', 'm/s2', 'М/С2', 'MSK', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(97, 'Кулон на килограмм', '349', 'Кл/кг', 'C/kg', 'КЛ/КГ', 'CKG', 5, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(98, 'Секунда', '354', 'с', 's', 'С', 'SEC', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(99, 'Минута', '355', 'мин', 'min', 'МИН', 'MIN', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(100, 'Час', '356', 'ч', 'h', 'Ч', 'HUR', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(101, 'Сутки', '359', 'сут; дн', 'd', 'СУТ; ДН', 'DAY', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(102, 'Неделя', '360', 'нед', '-', 'НЕД', 'WEE', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(103, 'Декада', '361', 'дек', '-', 'ДЕК', 'DAD', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(104, 'Месяц', '362', 'мес', '-', 'МЕС', 'MON', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(105, 'Квартал', '364', 'кварт', '-', 'КВАРТ', 'QAN', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(106, 'Полугодие', '365', 'полгода', '-', 'ПОЛГОД', 'SAN', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(107, 'Год', '366', 'г; лет', 'a', 'ГОД; ЛЕТ', 'ANN', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(108, 'Десятилетие', '368', 'деслет', '-', 'ДЕСЛЕТ', 'DEC', 6, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(109, 'Килограмм в секунду', '499', 'кг/с', '-', 'КГ/С', 'KGS', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(110, 'Тонна пара в час', '533', 'т пар/ч', '-', 'Т ПАР/Ч', 'TSH', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(111, 'Кубический метр в секунду', '596', 'м3/с', 'm3/s', 'М3/С', 'MQS', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(112, 'Кубический метр в час', '598', 'м3/ч', 'm3/h', 'М3/Ч', 'MQH', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(113, 'Тысяча кубических метров в сутки', '599', '10^3 м3/сут', '-', 'ТЫС М3/СУТ', 'TQD', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(114, 'Бобина', '616', 'боб', '-', 'БОБ', 'NBB', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(115, 'Лист', '625', 'л.', '-', 'ЛИСТ', 'LEF', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(116, 'Сто листов', '626', '100 л.', '-', '100 ЛИСТ', 'CLF', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(117, 'Тысяча стандартных условных кирпичей', '630', 'тыс станд. усл. кирп', '-', 'ТЫС СТАНД УСЛ КИРП', 'MBE', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(118, 'Дюжина (12 шт.)', '641', 'дюжина', 'Doz; 12', 'ДЮЖИНА', 'DZN', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(119, 'Изделие', '657', 'изд', '-', 'ИЗД', 'NAR', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(120, 'Сто ящиков', '683', '100 ящ.', 'Hbx', '100 ЯЩ', 'HBX', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(121, 'Набор', '704', 'набор', '-', 'НАБОР', 'SET', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(122, 'Пара (2 шт.)', '715', 'пар', 'pr; 2', 'ПАР', 'NPR', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(123, 'Два десятка', '730', '20', '20', '2 ДЕС', 'SCO', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(124, 'Десять пар', '732', '10 пар', '-', 'ДЕС ПАР', 'TPR', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(125, 'Дюжина пар', '733', 'дюжина пар', '-', 'ДЮЖИНА ПАР', 'DPR', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(126, 'Посылка', '734', 'посыл', '-', 'ПОСЫЛ', 'NPL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(127, 'Часть', '735', 'часть', '-', 'ЧАСТЬ', 'NPT', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(128, 'Рулон', '736', 'рул', '-', 'РУЛ', 'NPL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(129, 'Дюжина рулонов', '737', 'дюжина рул', '-', 'ДЮЖИНА РУЛ', 'DRL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(130, 'Дюжина штук', '740', 'дюжина шт', '-', 'ДЮЖИНА ШТ', 'DPC', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(131, 'Элемент', '745', 'элем', 'CI', 'ЭЛЕМ', 'NCL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(132, 'Упаковка', '778', 'упак', '-', 'УПАК', 'NMP', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(133, 'Дюжина упаковок', '780', 'дюжина упак', '-', 'ДЮЖИНА УПАК', 'DZP', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(134, 'Сто упаковок', '781', '100 упак', '-', '100 УПАК', 'CNP', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(135, 'Штука', '796', 'шт', 'pc; 1', 'ШТ', 'PCE; NMB', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(136, 'Сто штук', '797', '100 шт', '100', '100 ШТ', 'CEN', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(137, 'Тысяча штук', '798', 'тыс. шт; 1000 шт', '1000', 'ТЫС ШТ', 'MIL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(138, 'Миллион штук', '799', '10^6 шт', '10^6', 'МЛН ШТ', 'MIO', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(139, 'Миллиард штук', '800', '10^9 шт', '10^9', 'МЛРД ШТ', 'MLD', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(140, 'Биллион штук (Европа); триллион штук', '801', '10^12 шт', '10^12', 'БИЛЛ ШТ (ЕВР); ТРИЛЛ ШТ', 'BIL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(141, 'Квинтильон штук (Европа)', '802', '10^18 шт', '10^18', 'КВИНТ ШТ', 'TRL', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(142, 'Крепость спирта по массе', '820', 'креп. спирта по массе', '% mds', 'КРЕП СПИРТ ПО МАССЕ', 'ASM', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(143, 'Крепость спирта по объему', '821', 'креп. спирта по объему', '% vol', 'КРЕП СПИРТ ПО ОБЪЕМ', 'ASV', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(144, 'Литр чистого (100%) спирта', '831', 'л 100% спирта', '-', 'Л ЧИСТ СПИРТ', 'LPA', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(145, 'Гектолитр чистого (100%) спирта', '833', 'Гл 100% спирта', '-', 'ГЛ ЧИСТ СПИРТ', 'HPA', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(146, 'Килограмм пероксида водорода', '841', 'кг H2О2', '-', 'КГ ПЕРОКСИД ВОДОРОДА', '-', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(147, 'Килограмм 90%-го сухого вещества', '845', 'кг 90% с/в', '-', 'КГ 90 ПРОЦ СУХ ВЕЩ', 'KSD', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(148, 'Тонна 90%-го сухого вещества', '847', 'т 90% с/в', '-', 'Т 90 ПРОЦ СУХ ВЕЩ', 'TSD', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(149, 'Килограмм оксида калия', '852', 'кг К2О', '-', 'КГ ОКСИД КАЛИЯ', 'KPO', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(150, 'Килограмм гидроксида калия', '859', 'кг КОН', '-', 'КГ ГИДРОКСИД КАЛИЯ', 'KPH', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(151, 'Килограмм азота', '861', 'кг N', '-', 'КГ АЗОТ', 'KNI', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(152, 'Килограмм гидроксида натрия', '863', 'кг NaOH', '-', 'КГ ГИДРОКСИД НАТРИЯ', 'KSH', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(153, 'Килограмм пятиокиси фосфора', '865', 'кг Р2О5', '-', 'КГ ПЯТИОКИСЬ ФОСФОРА', 'KPP', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(154, 'Килограмм урана', '867', 'кг U', '-', 'КГ УРАН', 'KUR', 7, 1, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(155, 'Погонный метр', '018', 'пог. м', '', 'ПОГ М', '', 1, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(156, 'Тысяча погонных метров', '019', '10^3 пог. м', '', 'ТЫС ПОГ М', '', 1, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(157, 'Условный метр', '020', 'усл. м', '', 'УСЛ М', '', 1, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(158, 'Тысяча условных метров', '048', '10^3 усл. м', '', 'ТЫС УСЛ М', '', 1, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(159, 'Километр условных труб', '049', 'км усл. труб', '', 'КМ УСЛ ТРУБ', '', 1, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(160, 'Тысяча квадратных дециметров', '054', '10^3 дм2', '', 'ТЫС ДМ2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(161, 'Миллион квадратных дециметров', '056', '10^6 дм2', '', 'МЛН ДМ2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(162, 'Миллион квадратных метров', '057', '10^6 м2', '', 'МЛН М2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(163, 'Тысяча гектаров', '060', '10^3 га', '', 'ТЫС ГА', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(164, 'Условный квадратный метр', '062', 'усл. м2', '', 'УСЛ М2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(165, 'Тысяча условных квадратных метров', '063', '10^3 усл. м2', '', 'ТЫС УСЛ М2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(166, 'Миллион условных квадратных метров', '064', '10^6 усл. м2', '', 'МЛН УСЛ М2', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(167, 'Квадратный метр общей площади', '081', 'м2 общ. пл', '', 'М2 ОБЩ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(168, 'Тысяча квадратных метров общей площади', '082', '10^3 м2 общ. пл', '', 'ТЫС М2 ОБЩ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(169, 'Миллион квадратных метров общей площади', '083', '10^6 м2 общ. пл', '', 'МЛН М2. ОБЩ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(170, 'Квадратный метр жилой площади', '084', 'м2 жил. пл', '', 'М2 ЖИЛ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(171, 'Тысяча квадратных метров жилой площади', '085', '10^3 м2 жил. пл', '', 'ТЫС М2 ЖИЛ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(172, 'Миллион квадратных метров жилой площади', '086', '10^6 м2 жил. пл', '', 'МЛН М2 ЖИЛ ПЛ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(173, 'Квадратный метр учебно-лабораторных зданий', '087', 'м2 уч. лаб. здан', '', 'М2 УЧ.ЛАБ ЗДАН', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(174, 'Тысяча квадратных метров учебно-лабораторных зданий', '088', '10^3 м2 уч. лаб. здан', '', 'ТЫС М2 УЧ. ЛАБ ЗДАН', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(175, 'Миллион квадратных метров в двухмиллиметровом исчислении', '089', '10^6 м2 2 мм исч', '', 'МЛН М2 2ММ ИСЧ', '', 2, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(176, 'Тысяча кубических метров', '114', '10^3 м3', '', 'ТЫС М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(177, 'Миллиард кубических метров', '115', '10^9 м3', '', 'МЛРД М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(178, 'Декалитр', '116', 'дкл', '', 'ДКЛ', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(179, 'Тысяча декалитров', '119', '10^3 дкл', '', 'ТЫС ДКЛ', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(180, 'Миллион декалитров', '120', '10^6 дкл', '', 'МЛН ДКЛ', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(181, 'Плотный кубический метр', '121', 'плотн. м3', '', 'ПЛОТН М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(182, 'Условный кубический метр', '123', 'усл. м3', '', 'УСЛ М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(183, 'Тысяча условных кубических метров', '124', '10^3 усл. м3', '', 'ТЫС УСЛ М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(184, 'Миллион кубических метров переработки газа', '125', '10^6 м3 перераб. газа', '', 'МЛН М3 ПЕРЕРАБ ГАЗА', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(185, 'Тысяча плотных кубических метров', '127', '10^3 плотн. м3', '', 'ТЫС ПЛОТН М3', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(186, 'Тысяча полулитров', '128', '10^3 пол. л', '', 'ТЫС ПОЛ Л', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(187, 'Миллион полулитров', '129', '10^6 пол. л', '', 'МЛН ПОЛ Л', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(188, 'Тысяча литров; 1000 литров', '130', '10^3 л; 1000 л', '', 'ТЫС Л', '', 3, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(189, 'Тысяча каратов метрических', '165', '10^3 кар', '', 'ТЫС КАР', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(190, 'Миллион каратов метрических', '167', '10^6 кар', '', 'МЛН КАР', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(191, 'Тысяча тонн', '169', '10^3 т', '', 'ТЫС Т', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(192, 'Миллион тонн', '171', '10^6 т', '', 'МЛН Т', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(193, 'Тонна условного топлива', '172', 'т усл. топл', '', 'Т УСЛ ТОПЛ', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(194, 'Тысяча тонн условного топлива', '175', '10^3 т усл. топл', '', 'ТЫС Т УСЛ ТОПЛ', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(195, 'Миллион тонн условного топлива', '176', '10^6 т усл. топл', '', 'МЛН Т УСЛ ТОПЛ', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(196, 'Тысяча тонн единовременного хранения', '177', '10^3 т единовр. хран', '', 'ТЫС Т ЕДИНОВР ХРАН', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(197, 'Тысяча тонн переработки', '178', '10^3 т перераб', '', 'ТЫС Т ПЕРЕРАБ', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(198, 'Условная тонна', '179', 'усл. т', '', 'УСЛ Т', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(199, 'Тысяча центнеров', '207', '10^3 ц', '', 'ТЫС Ц', '', 4, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(200, 'Вольт-ампер', '226', 'В.А', '', 'В.А', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(201, 'Метр в час', '231', 'м/ч', '', 'М/Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(202, 'Килокалория', '232', 'ккал', '', 'ККАЛ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(203, 'Гигакалория', '233', 'Гкал', '', 'ГИГАКАЛ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(204, 'Тысяча гигакалорий', '234', '10^3 Гкал', '', 'ТЫС ГИГАКАЛ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(205, 'Миллион гигакалорий', '235', '10^6 Гкал', '', 'МЛН ГИГАКАЛ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(206, 'Калория в час', '236', 'кал/ч', '', 'КАЛ/Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(207, 'Килокалория в час', '237', 'ккал/ч', '', 'ККАЛ/Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(208, 'Гигакалория в час', '238', 'Гкал/ч', '', 'ГИГАКАЛ/Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(209, 'Тысяча гигакалорий в час', '239', '10^3 Гкал/ч', '', 'ТЫС ГИГАКАЛ/Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(210, 'Миллион ампер-часов', '241', '10^6 А.ч', '', 'МЛН А.Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(211, 'Миллион киловольт-ампер', '242', '10^6 кВ.А', '', 'МЛН КВ.А', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(212, 'Киловольт-ампер реактивный', '248', 'кВ.А Р', '', 'КВ.А Р', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(213, 'Миллиард киловатт-часов', '249', '10^9 кВт.ч', '', 'МЛРД КВТ.Ч', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(214, 'Тысяча киловольт-ампер реактивных', '250', '10^3 кВ.А Р', '', 'ТЫС КВ.А Р', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(215, 'Лошадиная сила', '251', 'л. с', '', 'ЛС', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(216, 'Тысяча лошадиных сил', '252', '10^3 л. с', '', 'ТЫС ЛС', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(217, 'Миллион лошадиных сил', '253', '10^6 л. с', '', 'МЛН ЛС', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(218, 'Бит', '254', 'бит', '', 'БИТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(219, 'Байт', '255', 'бай', '', 'БАЙТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(220, 'Килобайт', '256', 'кбайт', '', 'КБАЙТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(221, 'Мегабайт', '257', 'Мбайт', '', 'МБАЙТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(222, 'Бод', '258', 'бод', '', 'БОД', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(223, 'Генри', '287', 'Гн', '', 'ГН', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(224, 'Тесла', '313', 'Тл', '', 'ТЛ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(225, 'Килограмм на квадратный сантиметр', '317', 'кг/см^2', '', 'КГ/СМ2', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(226, 'Миллиметр водяного столба', '337', 'мм вод. ст', '', 'ММ ВОД СТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(227, 'Миллиметр ртутного столба', '338', 'мм рт. ст', '', 'ММ РТ СТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(228, 'Сантиметр водяного столба', '339', 'см вод. ст', '', 'СМ ВОД СТ', '', 5, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(229, 'Микросекунда', '352', 'мкс', '', 'МКС', '', 6, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(230, 'Миллисекунда', '353', 'млс', '', 'МЛС', '', 6, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(231, 'Рубль', '383', 'руб', '', 'РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(232, 'Тысяча рублей', '384', '10^3 руб', '', 'ТЫС РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(233, 'Миллион рублей', '385', '10^6 руб', '', 'МЛН РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(234, 'Миллиард рублей', '386', '10^9 руб', '', 'МЛРД РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(235, 'Триллион рублей', '387', '10^12 руб', '', 'ТРИЛЛ РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(236, 'Квадрильон рублей', '388', '10^15 руб', '', 'КВАДР РУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(237, 'Пассажиро-километр', '414', 'пасс.км', '', 'ПАСС.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(238, 'Пассажирское место (пассажирских мест)', '421', 'пасс. мест', '', 'ПАСС МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(239, 'Тысяча пассажиро-километров', '423', '10^3 пасс.км', '', 'ТЫС ПАСС.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(240, 'Миллион пассажиро-километров', '424', '10^6 пасс. км', '', 'МЛН ПАСС.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(241, 'Пассажиропоток', '427', 'пасс.поток', '', 'ПАСС.ПОТОК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(242, 'Тонно-километр', '449', 'т.км', '', 'Т.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(243, 'Тысяча тонно-километров', '450', '10^3 т.км', '', 'ТЫС Т.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(244, 'Миллион тонно-километров', '451', '10^6 т. км', '', 'МЛН Т.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(245, 'Тысяча наборов', '479', '10^3 набор', '', 'ТЫС НАБОР', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(246, 'Грамм на киловатт-час', '510', 'г/кВт.ч', '', 'Г/КВТ.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(247, 'Килограмм на гигакалорию', '511', 'кг/Гкал', '', 'КГ/ГИГАКАЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(248, 'Тонно-номер', '512', 'т.ном', '', 'Т.НОМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(249, 'Автотонна', '513', 'авто т', '', 'АВТО Т', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(250, 'Тонна тяги', '514', 'т.тяги', '', 'Т ТЯГИ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(251, 'Дедвейт-тонна', '515', 'дедвейт.т', '', 'ДЕДВЕЙТ.Т', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(252, 'Тонно-танид', '516', 'т.танид', '', 'Т.ТАНИД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(253, 'Человек на квадратный метр', '521', 'чел/м2', '', 'ЧЕЛ/М2', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(254, 'Человек на квадратный километр', '522', 'чел/км2', '', 'ЧЕЛ/КМ2', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(255, 'Тонна в час', '534', 'т/ч', '', 'Т/Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(256, 'Тонна в сутки', '535', 'т/сут', '', 'Т/СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(257, 'Тонна в смену', '536', 'т/смен', '', 'Т/СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(258, 'Тысяча тонн в сезон', '537', '10^3 т/сез', '', 'ТЫС Т/СЕЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(259, 'Тысяча тонн в год', '538', '10^3 т/год', '', 'ТЫС Т/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(260, 'Человеко-час', '539', 'чел.ч', '', 'ЧЕЛ.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(261, 'Человеко-день', '540', 'чел.дн', '', 'ЧЕЛ.ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(262, 'Тысяча человеко-дней', '541', '10^3 чел.дн', '', 'ТЫС ЧЕЛ.ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(263, 'Тысяча человеко-часов', '542', '10^3 чел.ч', '', 'ТЫС ЧЕЛ.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(264, 'Тысяча условных банок в смену', '543', '10^3 усл. банк/ смен', '', 'ТЫС УСЛ БАНК/СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(265, 'Миллион единиц в год', '544', '10^6 ед/год', '', 'МЛН ЕД/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(266, 'Посещение в смену', '545', 'посещ/смен', '', 'ПОСЕЩ/СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(267, 'Тысяча посещений в смену', '546', '10^3 посещ/смен', '', 'ТЫС ПОСЕЩ/ СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(268, 'Пара в смену', '547', 'пар/смен', '', 'ПАР/СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(269, 'Тысяча пар в смену', '548', '10^3 пар/смен', '', 'ТЫС ПАР/СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(270, 'Миллион тонн в год', '550', '10^6 т/год', '', 'МЛН Т/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(271, 'Тонна переработки в сутки', '552', 'т перераб/сут', '', 'Т ПЕРЕРАБ/СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(272, 'Тысяча тонн переработки в сутки', '553', '10^3 т перераб/ сут', '', 'ТЫС Т ПЕРЕРАБ/СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(273, 'Центнер переработки в сутки', '554', 'ц перераб/сут', '', 'Ц ПЕРЕРАБ/СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(274, 'Тысяча центнеров переработки в сутки', '555', '10^3 ц перераб/ сут', '', 'ТЫС Ц ПЕРЕРАБ/СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(275, 'Тысяча голов в год', '556', '10^3 гол/год', '', 'ТЫС ГОЛ/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(276, 'Миллион голов в год', '557', '10^6 гол/год', '', 'МЛН ГОЛ/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(277, 'Тысяча птицемест', '558', '10^3 птицемест', '', 'ТЫС ПТИЦЕМЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(278, 'Тысяча кур-несушек', '559', '10^3 кур. несуш', '', 'ТЫС КУР. НЕСУШ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(279, 'Минимальная заработная плата', '560', 'мин. заработн. плат', '', 'МИН ЗАРАБОТН ПЛАТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(280, 'Тысяча тонн пара в час', '561', '10^3 т пар/ч', '', 'ТЫС Т ПАР/Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(281, 'Тысяча прядильных веретен', '562', '10^3 пряд.верет', '', 'ТЫС ПРЯД ВЕРЕТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(282, 'Тысяча прядильных мест', '563', '10^3 пряд.мест', '', 'ТЫС ПРЯД МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(283, 'Доза', '639', 'доз', '', 'ДОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(284, 'Тысяча доз', '640', '10^3 доз', '', 'ТЫС ДОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(285, 'Единица', '642', 'ед', '', 'ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(286, 'Тысяча единиц', '643', '10^3 ед', '', 'ТЫС ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(287, 'Миллион единиц', '644', '10^6 ед', '', 'МЛН ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(288, 'Канал', '661', 'канал', '', 'КАНАЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(289, 'Тысяча комплектов', '673', '10^3 компл', '', 'ТЫС КОМПЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(290, 'Место', '698', 'мест', '', 'МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(291, 'Тысяча мест', '699', '10^3 мест', '', 'ТЫС МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(292, 'Тысяча номеров', '709', '10^3 ном', '', 'ТЫС НОМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(293, 'Тысяча гектаров порций', '724', '10^3 га порц', '', 'ТЫС ГА ПОРЦ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(294, 'Тысяча пачек', '729', '10^3 пач', '', 'ТЫС ПАЧ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(295, 'Процент', '744', '%', '', 'ПРОЦ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(296, 'Промилле (0,1 процента)', '746', 'промилле', '', 'ПРОМИЛЛЕ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(297, 'Тысяча рулонов', '751', '10^3 рул', '', 'ТЫС РУЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(298, 'Тысяча станов', '761', '10^3 стан', '', 'ТЫС СТАН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(299, 'Станция', '762', 'станц', '', 'СТАНЦ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(300, 'Тысяча тюбиков', '775', '10^3 тюбик', '', 'ТЫС ТЮБИК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(301, 'Тысяча условных тубов', '776', '10^3 усл.туб', '', 'ТЫС УСЛ ТУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(302, 'Миллион упаковок', '779', '10^6 упак', '', 'МЛН УПАК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(303, 'Тысяча упаковок', '782', '10^3 упак', '', 'ТЫС УПАК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(304, 'Человек', '792', 'чел', '', 'ЧЕЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(305, 'Тысяча человек', '793', '10^3 чел', '', 'ТЫС ЧЕЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(306, 'Миллион человек', '794', '10^6 чел', '', 'МЛН ЧЕЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(307, 'Миллион экземпляров', '808', '10^6 экз', '', 'МЛН ЭКЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(308, 'Ячейка', '810', 'яч', '', 'ЯЧ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(309, 'Ящик', '812', 'ящ', '', 'ЯЩ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(310, 'Голова', '836', 'гол', '', 'ГОЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(311, 'Тысяча пар', '837', '10^3 пар', '', 'ТЫС ПАР', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(312, 'Миллион пар', '838', '10^6 пар', '', 'МЛН ПАР', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(313, 'Комплект', '839', 'компл', '', 'КОМПЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(314, 'Секция', '840', 'секц', '', 'СЕКЦ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(315, 'Бутылка', '868', 'бут', '', 'БУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(316, 'Тысяча бутылок', '869', '10^3 бут', '', 'ТЫС БУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(317, 'Ампула', '870', 'ампул', '', 'АМПУЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(318, 'Тысяча ампул', '871', '10^3 ампул', '', 'ТЫС АМПУЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(319, 'Флакон', '872', 'флак', '', 'ФЛАК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(320, 'Тысяча флаконов', '873', '10^3 флак', '', 'ТЫС ФЛАК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(321, 'Тысяча тубов', '874', '10^3 туб', '', 'ТЫС ТУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(322, 'Тысяча коробок', '875', '10^3 кор', '', 'ТЫС КОР', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(323, 'Условная единица', '876', 'усл. ед', '', 'УСЛ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(324, 'Тысяча условных единиц', '877', '10^3 усл. ед', '', 'ТЫС УСЛ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(325, 'Миллион условных единиц', '878', '10^6 усл. ед', '', 'МЛН УСЛ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(326, 'Условная штука', '879', 'усл. шт', '', 'УСЛ ШТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(327, 'Тысяча условных штук', '880', '10^3 усл. шт', '', 'ТЫС УСЛ ШТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(328, 'Условная банка', '881', 'усл. банк', '', 'УСЛ БАНК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(329, 'Тысяча условных банок', '882', '10^3 усл. банк', '', 'ТЫС УСЛ БАНК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(330, 'Миллион условных банок', '883', '10^6 усл. банк', '', 'МЛН УСЛ БАНК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(331, 'Условный кусок', '884', 'усл. кус', '', 'УСЛ КУС', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(332, 'Тысяча условных кусков', '885', '10^3 усл. кус', '', 'ТЫС УСЛ КУС', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(333, 'Миллион условных кусков', '886', '10^6 усл. кус', '', 'МЛН УСЛ КУС', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(334, 'Условный ящик', '887', 'усл. ящ', '', 'УСЛ ЯЩ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(335, 'Тысяча условных ящиков', '888', '10^3 усл. ящ', '', 'ТЫС УСЛ ЯЩ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(336, 'Условная катушка', '889', 'усл. кат', '', 'УСЛ КАТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(337, 'Тысяча условных катушек', '890', '10^3 усл. кат', '', 'ТЫС УСЛ КАТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(338, 'Условная плитка', '891', 'усл. плит', '', 'УСЛ ПЛИТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(339, 'Тысяча условных плиток', '892', '10^3 усл. плит', '', 'ТЫС УСЛ ПЛИТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(340, 'Условный кирпич', '893', 'усл. кирп', '', 'УСЛ КИРП', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(341, 'Тысяча условных кирпичей', '894', '10^3 усл. кирп', '', 'ТЫС УСЛ КИРП', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(342, 'Миллион условных кирпичей', '895', '10^6 усл. кирп', '', 'МЛН УСЛ КИРП', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(343, 'Семья', '896', 'семей', '', 'СЕМЕЙ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(344, 'Тысяча семей', '897', '10^3 семей', '', 'ТЫС СЕМЕЙ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(345, 'Миллион семей', '898', '10^6 семей', '', 'МЛН СЕМЕЙ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(346, 'Домохозяйство', '899', 'домхоз', '', 'ДОМХОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(347, 'Тысяча домохозяйств', '900', '10^3 домхоз', '', 'ТЫС ДОМХОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(348, 'Миллион домохозяйств', '901', '10^6 домхоз', '', 'МЛН ДОМХОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(349, 'Ученическое место', '902', 'учен. мест', '', 'УЧЕН МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(350, 'Тысяча ученических мест', '903', '10^3 учен. мест', '', 'ТЫС УЧЕН МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(351, 'Рабочее место', '904', 'раб. мест', '', 'РАБ МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(352, 'Тысяча рабочих мест', '905', '10^3 раб. мест', '', 'ТЫС РАБ МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(353, 'Посадочное место', '906', 'посад. мест', '', 'ПОСАД МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(354, 'Тысяча посадочных мест', '907', '10^3 посад. мест', '', 'ТЫС ПОСАД МЕСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(355, 'Номер', '908', 'ном', '', 'НОМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(356, 'Квартира', '909', 'кварт', '', 'КВАРТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(357, 'Тысяча квартир', '910', '10^3 кварт', '', 'ТЫС КВАРТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(358, 'Койка', '911', 'коек', '', 'КОЕК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(359, 'Тысяча коек', '912', '10^3 коек', '', 'ТЫС КОЕК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(360, 'Том книжного фонда', '913', 'том книжн. фонд', '', 'ТОМ КНИЖН ФОНД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(361, 'Тысяча томов книжного фонда', '914', '10^3 том. книжн. фонд', '', 'ТЫС ТОМ КНИЖН ФОНД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(362, 'Условный ремонт', '915', 'усл. рем', '', 'УСЛ РЕМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(363, 'Условный ремонт в год', '916', 'усл. рем/год', '', 'УСЛ РЕМ/ГОД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(364, 'Смена', '917', 'смен', '', 'СМЕН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(365, 'Лист авторский', '918', 'л. авт', '', 'ЛИСТ АВТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(366, 'Лист печатный', '920', 'л. печ', '', 'ЛИСТ ПЕЧ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(367, 'Лист учетно-издательский', '921', 'л. уч.-изд', '', 'ЛИСТ УЧ.ИЗД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(368, 'Знак', '922', 'знак', '', 'ЗНАК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(369, 'Слово', '923', 'слово', '', 'СЛОВО', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(370, 'Символ', '924', 'символ', '', 'СИМВОЛ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(371, 'Условная труба', '925', 'усл. труб', '', 'УСЛ ТРУБ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(372, 'Тысяча пластин', '930', '10^3 пласт', '', 'ТЫС ПЛАСТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(373, 'Миллион доз', '937', '10^6 доз', '', 'МЛН ДОЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(374, 'Миллион листов-оттисков', '949', '10^6 лист.оттиск', '', 'МЛН ЛИСТ.ОТТИСК', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(375, 'Вагоно(машино)-день', '950', 'ваг (маш).дн', '', 'ВАГ (МАШ).ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(376, 'Тысяча вагоно-(машино)-часов', '951', '10^3 ваг (маш).ч', '', 'ТЫС ВАГ (МАШ).Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(377, 'Тысяча вагоно-(машино)-километров', '952', '10^3 ваг (маш).км', '', 'ТЫС ВАГ (МАШ).КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(378, 'Тысяча место-километров', '953', '10 ^3мест.км', '', 'ТЫС МЕСТ.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(379, 'Вагоно-сутки', '954', 'ваг.сут', '', 'ВАГ.СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(380, 'Тысяча поездо-часов', '955', '10^3 поезд.ч', '', 'ТЫС ПОЕЗД.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(381, 'Тысяча поездо-километров', '956', '10^3 поезд.км', '', 'ТЫС ПОЕЗД.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(382, 'Тысяча тонно-миль', '957', '10^3 т.миль', '', 'ТЫС Т.МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(383, 'Тысяча пассажиро-миль', '958', '10^3 пасс.миль', '', 'ТЫС ПАСС.МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(384, 'Автомобиле-день', '959', 'автомоб.дн', '', 'АВТОМОБ.ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(385, 'Тысяча автомобиле-тонно-дней', '960', '10^3 автомоб.т.дн', '', 'ТЫС АВТОМОБ.Т.ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(386, 'Тысяча автомобиле-часов', '961', '10^3 автомоб.ч', '', 'ТЫС АВТОМОБ.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(387, 'Тысяча автомобиле-место-дней', '962', '10^3 автомоб.мест. дн', '', 'ТЫС АВТОМОБ.МЕСТ. ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(388, 'Приведенный час', '963', 'привед.ч', '', 'ПРИВЕД.Ч', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(389, 'Самолето-километр', '964', 'самолет.км', '', 'САМОЛЕТ.КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(390, 'Тысяча километров', '965', '10^3 км', '', 'ТЫС КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(391, 'Тысяча тоннаже-рейсов', '966', '10^3 тоннаж. рейс', '', 'ТЫС ТОННАЖ. РЕЙС', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(392, 'Миллион тонно-миль', '967', '10^6 т. миль', '', 'МЛН Т. МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(393, 'Миллион пассажиро-миль', '968', '10^6 пасс. миль', '', 'МЛН ПАСС. МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(394, 'Миллион тоннаже-миль', '969', '10^6 тоннаж. миль', '', 'МЛН ТОННАЖ. МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(395, 'Миллион пассажиро-место-миль', '970', '10^6 пасс. мест. миль', '', 'МЛН ПАСС. МЕСТ. МИЛЬ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(396, 'Кормо-день', '971', 'корм. дн', '', 'КОРМ. ДН', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(397, 'Центнер кормовых единиц', '972', 'ц корм ед', '', 'Ц КОРМ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(398, 'Тысяча автомобиле-километров', '973', '10^3 автомоб. км', '', 'ТЫС АВТОМОБ. КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(399, 'Тысяча тоннаже-сут', '974', '10^3 тоннаж. сут', '', 'ТЫС ТОННАЖ. СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(400, 'Суго-сутки', '975', 'суго. сут.', '', 'СУГО. СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(401, 'Штук в 20-футовом эквиваленте (ДФЭ)', '976', 'штук в 20-футовом эквиваленте', '', 'ШТ В 20 ФУТ ЭКВИВ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(402, 'Канало-километр', '977', 'канал. км', '', 'КАНАЛ. КМ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(403, 'Канало-концы', '978', 'канал. конц', '', 'КАНАЛ. КОНЦ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(404, 'Тысяча экземпляров', '979', '10^3 экз', '', 'ТЫС ЭКЗ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(405, 'Тысяча долларов', '980', '10^3 доллар', '', 'ТЫС ДОЛЛАР', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(406, 'Тысяча тонн кормовых единиц', '981', '10^3 корм ед', '', 'ТЫС Т КОРМ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(407, 'Миллион тонн кормовых единиц', '982', '10^6 корм ед', '', 'МЛН Т КОРМ ЕД', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(408, 'Судо-сутки', '983', 'суд.сут', '', 'СУД.СУТ', '', 7, 2, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(409, 'Гектометр', '017', '', 'hm', '', 'HMT', 1, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(410, 'Миля (уставная) (1609,344 м)', '045', '', 'mile', '', 'SMI', 1, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(411, 'Акр (4840 квадратных ярдов)', '077', '', 'acre', '', 'ACR', 2, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(412, 'Квадратная миля', '079', '', 'mile2', '', 'MIK', 2, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(413, 'Жидкостная унция СК (28,413 см3)', '135', '', 'fl oz (UK)', '', 'OZI', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(414, 'Джилл СК (0,142065 дм3)', '136', '', 'gill (UK)', '', 'GII', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(415, 'Пинта СК (0,568262 дм3)', '137', '', 'pt (UK)', '', 'PTI', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(416, 'Кварта СК (1,136523 дм3)', '138', '', 'qt (UK)', '', 'QTI', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(417, 'Галлон СК (4,546092 дм3)', '139', '', 'gal (UK)', '', 'GLI', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(418, 'Бушель СК (36,36874 дм3)', '140', '', 'bu (UK)', '', 'BUI', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(419, 'Жидкостная унция США (29,5735 см3)', '141', '', 'fl oz (US)', '', 'OZA', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(420, 'Джилл США (11,8294 см3)', '142', '', 'gill (US)', '', 'GIA', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(421, 'Жидкостная пинта США (0,473176 дм3)', '143', '', 'liq pt (US)', '', 'PTL', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(422, 'Жидкостная кварта США (0,946353 дм3)', '144', '', 'liq qt (US)', '', 'QTL', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(423, 'Жидкостный галлон США (3,78541 дм3)', '145', '', 'gal (US)', '', 'GLL', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(424, 'Баррель (нефтяной) США (158,987 дм3)', '146', '', 'barrel (US)', '', 'BLL', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(425, 'Сухая пинта США (0,55061 дм3)', '147', '', 'dry pt (US)', '', 'PTD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(426, 'Сухая кварта США (1,101221 дм3)', '148', '', 'dry qt (US)', '', 'QTD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(427, 'Сухой галлон США (4,404884 дм3)', '149', '', 'dry gal (US)', '', 'GLD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(428, 'Бушель США (35,2391 дм3)', '150', '', 'bu (US)', '', 'BUA', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(429, 'Сухой баррель США (115,627 дм3)', '151', '', 'bbl (US)', '', 'BLD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(430, 'Стандарт', '152', '', '-', '', 'WSD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(431, 'Корд (3,63 м3)', '153', '', '-', '', 'WCD', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(432, 'Тысячи бордфутов (2,36 м3)', '154', '', '-', '', 'MBF', 3, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(433, 'Нетто-регистровая тонна', '182', '', '-', '', 'NTT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(434, 'Обмерная (фрахтовая) тонна', '183', '', '-', '', 'SHT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(435, 'Водоизмещение', '184', '', '-', '', 'DPT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(436, 'Фунт СК, США (0,45359237 кг)', '186', '', 'lb', '', 'LBR', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(437, 'Унция СК, США (28,349523 г)', '187', '', 'oz', '', 'ONZ', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(438, 'Драхма СК (1,771745 г)', '188', '', 'dr', '', 'DRI', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(439, 'Гран СК, США (64,798910 мг)', '189', '', 'gn', '', 'GRN', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(440, 'Стоун СК (6,350293 кг)', '190', '', 'st', '', 'STI', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(441, 'Квартер СК (12,700586 кг)', '191', '', 'qtr', '', 'QTR', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(442, 'Центал СК (45,359237 кг)', '192', '', '-', '', 'CNT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(443, 'Центнер США (45,3592 кг)', '193', '', 'cwt', '', 'CWA', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(444, 'Длинный центнер СК (50,802345 кг)', '194', '', 'cwt (UK)', '', 'CWI', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(445, 'Короткая тонна СК, США (0,90718474 т) [2*]', '195', '', 'sht', '', 'STN', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(446, 'Длинная тонна СК, США (1,0160469 т) [2*]', '196', '', 'lt', '', 'LTN', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(447, 'Скрупул СК, США (1,295982 г)', '197', '', 'scr', '', 'SCR', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(448, 'Пеннивейт СК, США (1,555174 г)', '198', '', 'dwt', '', 'DWT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(449, 'Драхма СК (3,887935 г)', '199', '', 'drm', '', 'DRM', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(450, 'Драхма США (3,887935 г)', '200', '', '-', '', 'DRA', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(451, 'Унция СК, США (31,10348 г); тройская унция', '201', '', 'apoz', '', 'APZ', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(452, 'Тройский фунт США (373,242 г)', '202', '', '-', '', 'LBT', 4, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(453, 'Эффективная мощность (245,7 ватт)', '213', '', 'B.h.p.', '', 'BHP', 5, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(454, 'Британская тепловая единица (1,055 кДж)', '275', '', 'Btu', '', 'BTU', 5, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(455, 'Гросс (144 шт.)', '638', '', 'gr; 144', '', 'GRO', 7, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(456, 'Большой гросс (12 гроссов)', '731', '', '1728', '', 'GGR', 7, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(457, 'Короткий стандарт (7200 единиц)', '738', '', '-', '', 'SST', 7, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(458, 'Галлон спирта установленной крепости', '835', '', '-', '', 'PGL', 7, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(459, 'Международная единица', '851', '', '-', '', 'NIU', 7, 3, 1, '')");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(460, 'Сто международных единиц', '853', '', '-', '', 'HIU', 7, 3, 1, '')");

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

		$this->load->model('tool/exchange1c');

		//$this->load->model('extension/modification');
		//$modification = $this->model_extension_modification->getModificationByCode('exchange1c');
		//if ($modification) $this->model_extension_modification->deleteModification($modification['modification_id']);

		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "category_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "manufacturer_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "store_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_group`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_type`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "warehouse`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_value`");

		// Удаляем все корректировки в базе

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product` CHANGE `quantity` `quantity` int(4) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_option_value` CHANGE `quantity` `quantity` int(4) NOT NULL DEFAULT 0 COMMENT 'Количество'");

		$this->log->write("Отключен модуль " . $this->module_name);
		$this->log->write("Удалены таблицы: product_quantity, category_to_1c, attribute_to_1c, manufacturer_to_1c, store_to_1c, product_quantity, product_price, product_to_1c, product_unit, unit, unit_group, unit_type, warehouse.");
		$this->log->write("Восстановлены изменения таблиц product, product_option_value, cart");

	} // uninstall()


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
	 * Очистка связей с 1С через админ-панель
	 */
	public function manualCleaningLinks() {
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'module/exchange1c'))  {
			$this->load->model('tool/exchange1c');
			$result = $this->model_tool_exchange1c->cleanLinks();
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

	} // manualCleaningLinks()


	/**
	 * Очистка старых ненужных картинок через админ-панель
	 */
	public function manualCleaningOldImages() {
		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'module/exchange1c'))  {
			$this->load->model('tool/exchange1c');
			$result = $this->model_tool_exchange1c->cleanOldImages("import_files/");
			if ($result['error']) {
				$json['error'] = $result['error'];
			} else {
				$json['success'] = "Успешно удалено файлов: " . $result['num'];
			}
		} else {
			$json['error'] = "У Вас нет прав на изменение!";
		}

		$this->load->language('module/exchange1c');

		$this->response->setOutput(json_encode($json));

	} // manualCleaningLinks()


	/**
	 * Очистка кэша: системного, картинок
	 */
	public function manualCleaningCache() {

		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'module/exchange1c'))  {
			$this->load->model('tool/exchange1c');

			$result = $this->cleanCache();

			if (!$result) {
				$json['error'] = "Ошибка очистки кэша";
			} else {
				$json['success'] = "Кэш успешно очищен: \n" . $result;
			}
		} else {
			$json['error'] = "У Вас нет прав на изменение!";
		}

		$this->load->language('module/exchange1c');

		$this->response->setOutput(json_encode($json));

	} // manualCleaningCache()


	/**
	 * Генерация SEO на все товары
	 */
	public function manualGenerateSeo() {

		$json = array();
		// Проверим разрешение
		if ($this->user->hasPermission('modify', 'module/exchange1c'))  {
			$this->load->model('tool/exchange1c');

			$result = $this->model_tool_exchange1c->seoGenerate();

			if ($result['error']) {
				$json['error'] = "Ошибка формирования SEO\n" . $result['error'];
			} else {
				$json['success'] = "SEO успешно сформирован, обработано:\nТоваров: " . $result['product'] . "\nКатегорий: " . $result['category'] . "\nПроизводителей: " . $result['manufacturer'];
			}
		} else {
			$json['error'] = "У Вас нет прав на изменение!";
		}

		$this->load->language('module/exchange1c');

		$this->response->setOutput(json_encode($json));

	} // manualGenerateSeo()


	/**
	 * Проверка существования каталогов
	 */
	private function checkDirectories($name) {
		$path = DIR_IMAGE;
		$dir = explode("/", $name);
		for ($i = 0; $i < count($dir)-1; $i++) {
			$path .= $dir[$i]."/";
			//$this->log($path,2);
			if (!is_dir($path)) {

				$error = "";
				@mkdir($path, 0775) or die ($error = "Ошибка создания директории '" . $path . "'");
				if ($error) return $error;

				$this->log("[zip] create folder: ".$path,2);
			}
		}
		return "";
	}


	/**
	 * Распаковываем картинки
	 */
	private function extractImage($zipArc, $zip_entry, $name) {

		$error = "";

		$this->log("[zip]> extractImage() name = " . $name, 2);
		if (substr($name, -1) == "/") {
			if (is_dir(DIR_IMAGE.$name)) {
				$this->log('[zip] directory exist: '.$name, 2);
			} else {
				$this->log('[zip] create directory: '.$name, 2);
				@mkdir(DIR_IMAGE.$name, 0775) or die ($error = "Ошибка создания директории '" . DIR_IMAGE.$name . "'");
				if ($error) return $error;
			}
		} elseif (zip_entry_open($zipArc, $zip_entry, "r")) {
			$error = $this->checkDirectories($name);
			if ($error) return $error;

			if (is_file(DIR_IMAGE.$name)) {
				$this->log('[zip] file exist: '.$name, 2);
			} else {
				$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

				// для безопасности проверим, не является ли этот файл php
				$pos = strpos($dump, "<?php");
				if ($pos !== false) {
					$this->log("[!] ВНИМАНИЕ Файл '" . $name . "' является PHP скриптом и не будет записан!");
				} elseif ($fd = @fopen(DIR_IMAGE.$name,"w+")) {
					if ($fd === false) {
						return "Ошибка создания файла: " . DIR_IMAGE.$name . ", проверьте права доступа!";
					}
					$this->log('[zip] create file: '.$name, 2);
					fwrite($fd, $dump);
					fclose($fd);

					// для безопасности проверим, является ли этот файл картинкой
//					$image_info = getimagesize(DIR_IMAGE.$name);
//					if ($image_info == NULL) {
//						$this->log("[!] ВНИМАНИЕ Файл '" . $name . "' не является картинкой, и будет удален!");
//						unlink(DIR_IMAGE.$name);
//					}
				}
			}
			zip_entry_close($zip_entry);
		}

		$this->log("[zip]< extractImage()", 2);
		return $error;

	} // extractImage()


	/**
	 * Распаковываем XML
	 */
	private function extractXML($zipArc, $zip_entry, $name, &$xmlFiles) {

		$error = "";

		$this->log("[zip]> extractXML() name = " . $name, 2);
		$cache = DIR_CACHE . 'exchange1c/';

		if (substr($name, -1) == "/") {
			// это директория
			if (is_dir($cache.$name)) {
				$this->log('[zip] directory exist: '.$name, 2);
			} else {
				$this->log('[zip] create directory: '.$name, 2);
				@mkdir($cache.$name, 0775) or die ($error = "Ошибка создания директории '" . $cache.$name . "'");
				if ($error) return $error;
			}
		} elseif (zip_entry_open($zipArc, $zip_entry, "r")) {
			$dump = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			// для безопасности проверим, является ли этот файл XML
			$str_xml = mb_substr($dump, 1, 5);
			if ($str_xml != "<?xml") {
				$this->log("[!] ВНИМАНИЕ Файл '" . $name . "' не является XML файлом и не будет записан!");

			} elseif ($fd = @fopen($cache.$name,"w+")) {
				$xmlFiles[] = $name;
				$this->log('[zip] create file: '.$name, 2);
				fwrite($fd, $dump);
				fclose($fd);
//			} else {
//				$this->log('[zip] create directory: '.$name, 2);
//				@mkdir($cache.$name, 0775);
			}
			zip_entry_close($zip_entry);
		}
		$this->log("[zip]< extractXML()", 2);
		return "";

	} // extractXML()


	/**
	 * Распаковываем ZIP архив
	 */
	private function extractZip($zipFile, &$error) {
		$this->log("[zip]> extractZip() zipFile = " . $zipFile, 2);
		$xmlFiles = array();
		$cache = DIR_CACHE . 'exchange1c/';

		// Проверим на доступность записи в папку кэша
		if (!is_writable($cache)) {
			$error = "Папка '" . $cache . "' не доступна для записи, распаковка прервана";
			return $xmlFiles;
		}

		$zipArc = zip_open($zipFile);
		if (is_resource($zipArc)) {
			while ($zip_entry = zip_read($zipArc)) {
				$name = zip_entry_name($zip_entry);
				$pos = stripos($name, 'import_files/');
				if ($pos !== false) {
					$this->extractImage($zipArc, $zip_entry, substr($name, $pos));
				} else {
					$error = $this->extractXML($zipArc, $zip_entry, $name, $xmlFiles);
					if ($error) return $xmlFiles;
				}
			}
		} else {
			return $xmlFiles;
		}
		zip_close($zipArc);
		$this->log("[zip]< extractZip()", 2);
		return $xmlFiles;
	} // extractZip()


	/**
	 * Определяет тип файла по наименованию
	 */
	public function detectFileType($fileName) {
		$types = array('import', 'offers', 'prices', 'rests');
		foreach ($types as $type) {
			$pos = stripos($fileName, $type);
			if ($pos !== false)
				return $type;
		}
		return '';
	}


	/**
	 * Создание и скачивание заказов
	 */
	public function downloadOrders() {
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
		$this->response->addheader('Pragma: public');
		$this->response->addheader('Connection: Keep-Alive');
		$this->response->addheader('Expires: 0');
		$this->response->addheader('Content-Description: File Transfer');
		$this->response->addheader('Content-Type: application/octet-stream');
		$this->response->addheader('Content-Disposition: attachment; filename="orders.xml"');
		$this->response->addheader('Content-Transfer-Encoding: binary');
		$this->response->addheader('Content-Length: ' . strlen($orders));
		//$this->response->addheader('Content-Type: text/html; charset=windows-1251');

		//$this->response->setOutput(file_get_contents(DIR_CACHE . 'exchange1c/orders.xml', FILE_USE_INCLUDE_PATH, null));
        $this->response->setOutput($orders);

	} // downloadOrders()


	/**
	 * Импорт файла через админ-панель
	 */
	public function manualImport() {

		$this->load->language('module/exchange1c');
		$cache = DIR_CACHE . 'exchange1c/';
		$json = array();
		$error = "";

		// Разрешен ли IP
		if ($this->config->get('exchange1c_allow_ip') != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));
			if (!in_array($ip, $allow_ips)) {
				$json['error'] = "Ваш IP адрес " . $ip . " не найден в списке разрешенных";
				$this->response->setOutput(json_encode($json));
				return;
			}
		}

		if ($this->config->get('exchange1c_flush_log') == 1) {
			$this->clearLog();
		}

		if (!empty($this->request->files['file']['name']) && is_file($this->request->files['file']['tmp_name'])) {

			//$filename = basename(html_entity_decode($this->request->files['file']['name'], ENT_QUOTES, 'UTF-8'));

			$max_size_file = $this->modeCatalogInit(array(),FALSE);
			$xmlFiles = $this->extractZip($this->request->files['file']['tmp_name'], $error);

			if (count($xmlFiles) && !$error) {

				$goods = array();
				$properties = array();
				foreach ($xmlFiles as $key => $file) {
					$pos = strripos($file, "/goods/");
					if ($pos !== false) {
						$goods[] = $file;
						unset($xmlFiles[$key]);
					}
					$pos = strripos($file, "/properties/");
					if ($pos !== false) {
						$properties[] = $file;
						unset($xmlFiles[$key]);
					}
				}

				// Порядок обработки файлов
				foreach ($xmlFiles as $file) {
					$this->log('Обрабатывается файл основной: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
				}
				foreach ($properties as $file) {
					$this->log('Обрабатывается файл свойств: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
				}
				foreach ($goods as $file) {
					$this->log('Обрабатывается файл товаров: ' . $file, 2);
					$error = $this->modeImport($cache . $file);
				}

			}
			else {
				$import_file = $cache . $this->request->files['file']['name'];
				move_uploaded_file($this->request->files['file']['tmp_name'], $import_file);
				$this->log( "[i] Загружен файл: " . $this->request->files['file']['name'],2);
				$error = $this->modeImport($import_file);
				$this->log($error,2);
//				unlink($import_file);
			}
		}
		if ($error) {
			//$json['error'] = $this->language->get('text_upload_error');
			$json['error'] = $error;
			$this->log( "[!] Ручной обмен прошел ошибками", 2);

		} else {
			$json['success'] = $this->language->get('text_upload_success');
			$this->log( "[i] Ручной обмен прошел без ошибок", 2);

			// после обмена запускаем генерацию SEO
			$this->load->model('tool/exchange1c');
			$this->model_tool_exchange1c->seoGenerate();
		}

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
	 * Очистка лога
	 */
	private function clearLog() {
		$file = DIR_LOGS . $this->config->get('config_error_filename');
		$handle = fopen($file, 'w+');
		fclose($handle);
	}


	/**
	 * Обрабатывает команду инициализации каталога
	 */
	public function modeCatalogInit($param = array(), $echo = true) {

		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (!is_dir($cache)) {
			mkdir($cache);
			$this->log("[i] Создана директория: " . $cache,2);
		}

		$img = DIR_IMAGE . 'import_files/';
		if (!is_dir($img)) {
			mkdir($img);
			$this->log("[i] Создана директория: " . $img,2);
		}

		if ($echo) {
			if ($this->config->get('exchange1c_file_exchange') == 'zip') {
				echo "zip=yes\n";
			} else {
				echo "zip=no\n";
			}
			echo "file_limit=" . $this->getPostMaxFileSize() . "\n";
		}

		$this->configSet('exchange_status', 1);
		$this->log("[i] Exchange status=1");

		// При начале обмена запишем в регистр дату и время начала обмена, а после обмена удалим ее
		if ($this->config->has('exchange1c_date_exchange_stop')) {
			// Запишем в регистр время начала обмена
			$this->load->model('setting/setting');
			$config = $this->model_setting_setting->getSetting('exchange1c');
			unset($config['exchange1c_date_exchange_stop']);
			$config['exchange1c_date_exchange'] = date('Y-m-d H:i:s');
			$this->model_setting_setting->editSetting('exchange1c', $config);
			$this->log("> Начало обмена: " . $config['exchange1c_date_exchange'],2);
			$this->log("[PHP] file_limit = " . $this->getPostMaxFileSize(),2);

			// Очистка лога при начале обмена
			if ($this->config->get('exchange1c_flush_log')) {
				//$this->load->model('tool/exchange1c');
				// Очистка базы!!! ВРЕМЕННО!
				//$this->model_tool_exchange1c->cleanDB();
				$this->clearLog();
			}

		}
		//$this->clearLog();

		return $this->getPostMaxFileSize();
	} // modeCatalogInit()


	/**
	 * Обрабатывает команду инициализации продаж
	 */
	public function modeSaleInit() {
		if ($this->config->get('exchange1c_file_exchange') == 'zip') {
			echo "zip=yes\n";
		} else {
			echo "zip=no\n";
		}
		echo "file_limit=" . $this->getPostMaxFileSize() . "\n";
	} // modeSaleInit()


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
			$this->log( "[ERROR] No file name variable",1);
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
			$this->log("[ERROR] Папка " . $cache . " не доступна для записи",1);
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
					$xmlfiles = $this->extractZip($uplod_file, $error);
					if ($error) {
						$this->echo_message(0, "Error extract file: " . $uplod_file);
						exit;
					};
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
			return "Имя файла неопределено!";
		}

		// Определяем текущую локаль
		$this->load->model('tool/exchange1c');
		$language_id = $this->model_tool_exchange1c->getLanguageId($this->config->get('config_language'));

		// Загружаем файл
		$error = $this->model_tool_exchange1c->importFile($importFile, $this->detectFileType($importFile));
		if ($error) {
			if (!$manual) {
				$this->echo_message(0, "Error processing file " . $importFile);
			}
			$this->log("[!] Ошибка загрузки файла: " . $importFile);
			return $error;
		} else {
			if (!$manual) {
				$this->echo_message(1, "Successfully processed file " . $importFile);
			}
		}

		// Удалим файл
		//$this->log("[i] Удаление файла: " . $importFile,2);
		//unlink($importFile);

		$this->cache->delete('product');
		return "";
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
		echo header('Content-Type: text/html; charset=windows-1251', true);
		if ($this->config->get('exchange1c_convert_orders_cp1251') == 1)
			// посоветовал yuriygr с GitHub
			//echo iconv('utf-8', 'cp1251', $orders);
			echo iconv('utf-8', 'cp1251//TRANSLIT', $orders);
		else
			echo $orders;
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
			'new_status'		=> $this->config->get('exchange1c_order_status_change'),
			'notify'			=> $this->config->get('exchange1c_order_notify')
		));

		$this->load->model('setting/setting');
		$config = $this->model_setting_setting->getSetting('exchange1c');
		if($result){
			$config['exchange1c_order_date'] = date('Y-m-d H:i:s');
		}

		$config['exchange1c_date_exchange_stop'] = date('Y-m-d H:i:s');
		$this->model_setting_setting->editSetting('exchange1c', $config);
		$this->log("> Конец обмена: " . $config['exchange1c_date_exchange_stop'],2);

		$this->echo_message(1,$result);
	}


	// -- Системные процедуры
	/**
	 * Очистка папки cache
	 */
	private function cleanCache() {
		// Проверяем есть ли директория
		$result = "";
		if (file_exists(DIR_CACHE . 'exchange1c')) {
			if (is_dir(DIR_CACHE . 'exchange1c')) {
				$this->cleanDir(DIR_CACHE . 'exchange1c/');
			}
			else {
				unlink(DIR_CACHE . 'exchange1c');
			}
		}
		@mkdir (DIR_CACHE . 'exchange1c');
		$result .= "Очищен кэш модуля: /system/storage/cache/exchange1c/*\n";

		// очистка системного кэша
		$files = glob(DIR_CACHE . 'cache.*');
		foreach ($files as $file) {
			$this->cleanDir($file);
		}
        $result .= "Очищен системный кэш: /system/storage/cache/cache*\n";

		// очистка кэша картинок
		$imgfiles = glob(DIR_IMAGE . 'cache/*');
		foreach ($imgfiles as $imgfile) {
			$this->cleanDir($imgfile);
			$this->log("Удаление картинки: " . $imgfile ,2);
		}
		$result .= "Очищен кэш картинок: /image/cache/*\n";

		return $result;

	} // cleanCache()


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
		if (is_file($root)) {
			unlink($root);
		} else {
			if (substr($root, -1)!= '/') {
				$root .= '/';
			}
			$dir = dir($root);
			while ($file = $dir->read()) {
				if ($file == '.' || $file == '..') continue;
				if ($file == 'index.html') continue;
				if (file_exists($root . $file)) {
					if (is_file($root . $file)) { unlink($root . $file); continue; }
					if (is_dir($root . $file)) { $this->cleanDir($root . $file . '/', true); continue; }
					//var_dump ($file);
				}
				//var_dump($file);
			}
		}
		if ($self) {
			if(file_exists($root) && is_dir($root)) {
				rmdir($root); return 0;
			}
			//var_dump($root);
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
	} // eventManufacturerDelete()


	/**
	 * Удаляет категорию и все что в ней
	 */
    public function delete($path) {
		if (is_dir($path)) {
			array_map(function($value) {
				$this->delete($value);
				rmdir($value);
			},glob($path . '/*', GLOB_ONLYDIR));
			array_map('unlink', glob($path."/*"));
		}
	} // delete()


	/**
	* Формирует архив модуля для инсталляции
	*/
	public function modeExportModule() {

		if ($this->config->get('exchange1c_export_module_to_all') != 1) {
			// Разрешен ли IP
			if ($this->config->get('exchange1c_allow_ip') != '') {
				$ip = $_SERVER['REMOTE_ADDR'];
				$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));
				if (!in_array($_SERVER['REMOTE_ADDR'], $allow_ips)) {
					echo("Ваш IP адрес " . $_SERVER['REMOTE_ADDR'] . " не найден в списке разрешенных");
					return false;
				}
			} else {
				echo("Список IP адресов пуст, задайте адрес");
				return false;
			}
		}

		$this->log("Экспорт модуля " . $this->module_name . " для IP " . $_SERVER['REMOTE_ADDR']);
		// создаем папку export в кэше

		// Короткое название версии
		$cms_short_version = substr($this->config->get('exchange1c_CMS_version'),0,3);

		$filename = DIR_CACHE . 'opencart' . $cms_short_version . '-exchange1c_' . $this->config->get('exchange1c_version') . '.ocmod.zip';
		if (is_file($filename))
			unlink($filename);

		$cms_folder = substr(DIR_APPLICATION, 0, strlen(DIR_APPLICATION) - 6);

		// Пакуем в архив
		$zip = new ZipArchive;
		$zip->open($filename, ZIPARCHIVE::CREATE);
		$zip->addFile(DIR_APPLICATION . 'controller/module/exchange1c.php', 'upload/admin/controller/module/exchange1c.php');
		if (version_compare($this->config->get('exchange1c_CMS_version'), '2.3', '=')) {
			$zip->addFile(DIR_APPLICATION . 'language/en-gb/extension/module/exchange1c.php', 'upload/admin/language/english/module/exchange1c.php');
			$zip->addFile(DIR_APPLICATION . 'language/ru-ru/extension/module/exchange1c.php', 'upload/admin/language/russian/module/exchange1c.php');
		} else {
			$zip->addFile(DIR_APPLICATION . 'language/english/module/exchange1c.php', 'upload/admin/language/english/module/exchange1c.php');
			$zip->addFile(DIR_APPLICATION . 'language/russian/module/exchange1c.php', 'upload/admin/language/russian/module/exchange1c.php');
		}
		$zip->addFile(DIR_APPLICATION . 'model/tool/exchange1c.php', 'upload/admin/model/tool/exchange1c.php');
		$zip->addFile(DIR_APPLICATION . 'view/template/module/exchange1c.tpl', 'upload/admin/view/template/module/exchange1c.tpl');
		$zip->addFile($cms_folder . 'export/exchange1c.php', 'upload/export/exchange1c.php');

		if (is_file($cms_folder . 'export/history.txt'))
			$zip->addFile($cms_folder . 'export/history.txt', 'history.txt');
		if (is_file($cms_folder . 'export/install.php'))
			$zip->addFile($cms_folder . 'export/install.php', 'install.php');
		if (is_file($cms_folder . 'export/README.md'))
			$zip->addFile($cms_folder . 'export/README.md', 'README.md');

		$sql = "SELECT xml FROM " . DB_PREFIX . "modification WHERE code = 'exchange1c'";
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			if ($fp = fopen(DIR_CACHE . 'modification.xml', "wb")) {
				$result = fwrite($fp, $query->row['xml']);
				fclose($fp);
				$zip->addFile(DIR_CACHE . 'modification.xml', 'install.xml');
			}
		}

		$zip->close();
		if (is_file(DIR_CACHE . 'modification.xml'))
			unlink(DIR_CACHE . 'modification.xml');

		if ($fp = fopen($filename, "rb")) {
			echo '<a href="' . HTTP_CATALOG . 'system/storage/cache/' . substr($filename, strlen(DIR_CACHE)) . '">' . substr($filename, strlen(DIR_CACHE)) . '</a>';
		}

	} // modeExportModule()


	/**
	* Удаляет модуль
	*/
	public function modeRemoveModule() {

		return false;
		// Разрешен ли IP
		if ($this->config->get('exchange1c_allow_ip') != '') {
			$ip = $_SERVER['REMOTE_ADDR'];
			$allow_ips = explode("\r\n", $this->config->get('exchange1c_allow_ip'));
			if (!in_array($ip, $allow_ips)) {
				echo("Ваш IP адрес " . $ip . " не найден в списке разрешенных");
				return false;
			}
		} else {
			echo("Список IP адресов пуст, задайте адрес");
			return false;
		}

		$this->log("Удаление модуля " . $this->module_name,1);
		// создаем папку export в кэше

		$this->uninstall();

		$files = array();
		$files[] = DIR_APPLICATION . 'controller/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'language/english/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'language/russian/module/exchange1c.php';
		$files[] = DIR_APPLICATION . 'model/tool/exchange1c.php';
		$files[] = DIR_APPLICATION . 'view/template/module/exchange1c.tpl';
		$files[] = substr(DIR_APPLICATION, 0, strlen(DIR_APPLICATION) - 6) . 'export/exchange1c.php';
		foreach ($files as $file) {
			if (is_file($file)) {
				unlink($file);
				$this->log("Удален файл " . $file,1);
			}
		}

		// Удаление модификатора
		$this->load->model('extension/modification');
		$modification = $this->model_extension_modification->getModificationByCode('exchange1c');
		if ($modification) $this->model_extension_modification->deleteModification($modification['modification_id']);

		echo "Модуль успешно удален!";

	} // modeRemoveModule()

}
?>
