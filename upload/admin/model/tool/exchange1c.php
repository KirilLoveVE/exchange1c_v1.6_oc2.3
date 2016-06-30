<?php

class ModelToolExchange1c extends Model {

	private $VERSION_XML 	= '';
	private $STORE_ID		= 0;
	private $LANG_ID		= 0;


	/**
	 * ****************************** ОБЩИЕ ФУНКЦИИ ****************************** 
	 */


	/**
	 * Номер текущей версии
	 *
	 */
	public function version() {
		return "1.6.2.b8";
	} // version()
	

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
	 * Конвертирует XML в массив 
	 *
	 * @param	array				data	
	 * @param	SimpleXMLElement	XML
	 * @return	XML
	 */
	function array_to_xml($data, &$xml) {
		foreach($data as $key => $value) {
			if (is_array($value)) {
				if (!is_numeric($key)) {
					$subnode = $xml->addChild(preg_replace('/\d/', '', $key));
					$this->array_to_xml($value, $subnode);
				}
			}
			else {
				$xml->addChild($key, $value);
			}
		}
		return $xml;
	} // array_to_xml()


	/**
	 * Возвращает строку даты  
	 *
	 * @param	string	var	
	 * @return	string
	 */
	function format($var){
		return preg_replace_callback(
		    '/\\\u([0-9a-fA-F]{4})/',
		    create_function('$match', 'return mb_convert_encoding("&#" . intval($match[1], 16) . ";", "UTF-8", "HTML-ENTITIES");'),
		    json_encode($var)
		);
	} // format()


	/**
	 * Проверим файл на стандарт Commerce ML
	 */
	private function checkCML($xml) {
		$this->log("==> checkCML()",2);
		if ($xml['ВерсияСхемы']) {
			$this->VERSION_XML = (string)$xml['ВерсияСхемы'];
			$this->log("[i] Версия XML: " . $this->VERSION_XML,2);
		} else {
			$this->log("[ERROR] Файл не является стандартом Commerce ML!");
			return 0;
		}
		return 1;
	} // checkCML()


	/**
	 * Очищает базу
	 */
	public function cleanDB() {
		$this->log("==> cleanDB()",2);
		// Удаляем товары
		$result = "";

		$this->log("[i] Очистка таблиц товаров...",2);
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_attribute`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_discount`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_image`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_related`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_reward`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_special`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_quantity`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');

		//SEO
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'url_alias`');

		// Характеристики (группы опций)
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_price`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');
		
		// Дополнительные единицы измерений товара
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_unit`');

		$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "relatedoptions%'");
		if ($query->num_rows) {
			$this->log("Очистка таблиц связанных опций...",2);
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_to_char`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_option`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_option`');
			$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_product`');
			$result .=  "Очищены таблицы связанных опций\n";
		}

		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_category`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_download`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_layout`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_store`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
		$result .=  "Очищены таблицы товаров, опций\n";

		// Очищает таблицы категорий
		$this->log("Очистка таблиц категорий...",2);
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		$result .=  "Очищены таблицы категорий\n";

		// Очищает таблицы от всех производителей
		$this->log("Очистка таблиц производителей...",2);
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_1c');
		$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "manufacturer_description'");
		if ($query->num_rows) {
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
		}
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
		$result .=  "Очищены таблицы производителей\n";

		// Очищает атрибуты
		$this->log("Очистка таблиц атрибутов...",2);
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_description`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group_description`");
		$result .=  "Очищены таблицы атрибутов\n";

		// Выставляем кол-во товаров в 0
		$this->log("Очистка остатков...",2);
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = 0");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "warehouse`");
		$result .=  "Обнулены все остатки\n";

		return $result;
	} // cleanDB()


	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkProduct($product_id) {
		$this->log("==> deleteLinkProduct()",2);
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_to_1c` WHERE product_id = '" . $product_id . "'");
			$this->log("Удалена связь товара cml_id с id: " . $product_id,2);
		}
		$this->load->model('catalog/product');
		$product = $this->model_catalog_product->getProduct($product_id);
		if ($product['image']) {
			// Удаляем только в папке import_files
			if (substr($product['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $product['image']);
				$this->log("Удален файл: " . $product['image'],2);
			}
		}
		$productImages = $this->model_catalog_product->getProductImages($product_id);
		foreach ($productImages as $image) {
			// Удаляем только в папке import_files
			if (substr($image['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $image['image']);
				$this->log("Удален файл: " . $image['image'],2);
			}
		}
		$this->log("Удален файл: " . $image['image'],2);
	} // deleteLinkProduct()
	

	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkCategory($category_id) {
		$this->log("==> deleteLinkCategory()",2);
		// Удаляем линк
		if ($category_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "category_to_1c` WHERE category_id = '" . $category_id . "'");
			$this->log("Удалена связь категории cml_id с id: " . $category_id,2);
		}
	} //  deleteLinkCategory()
	

	/**
	 * Удаляет связи cml_id -> id
	 */
	public function deleteLinkManufacturer($manufacturer_id) {
		$this->log("==> deleteLinkManufacturer()",2);
		// Удаляем линк
		if ($manufacturer_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "manufacturer_to_1c` WHERE manufacturer_id = '" . $manufacturer_id . "'");
			$this->log("Удалена связь производителя cml_id с id: " . $manufacturer_id,2);
		}
	} //  deleteLinkManufacturer()


	/**
	 * Удаляет связи товара c характеристиками 1С
	 */
	public function deleteLinkFeature($product_id) {
		$this->log("==> deleteLinkFeature()",2);
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_feature` WHERE product_id = '" . $product_id . "'");
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_feature_value` WHERE product_id = '" . $product_id . "'");
			$this->log("Удалена связь характеристик с товаром, id: " . $product_id,2);
		}
	} //  deleteLinkFeature()


	/**
	 * Создает события
	 */
	public function setEvents() {
		$this->log("==> setEvents()",2);
		// Установка событий
		$this->load->model('extension/event');
		
		// Удалим все события
		$this->model_extension_event->deleteEvent('exchange1c');
		
		// Добавим удаление связей при удалении товара
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.product.delete', 'module/exchange1c/eventDeleteProduct');
		// Добавим удаление связей при удалении категории
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.category.delete', 'module/exchange1c/eventDeleteCategory');
		// Добавим удаление связей при удалении Производителя
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.manufacturer.delete', 'module/exchange1c/eventDeleteManufacturer');
		// Добавим удаление связей при удалении Характеристики
		$this->model_extension_event->addEvent('exchange1c', 'pre.admin.option.delete', 'module/exchange1c/eventDeleteOption');
	} // setEvents()


	/**
	 * Получает language_id из code (ru, en, etc)
	 * Как ни странно, подходящей функции в API не нашлось
	 *
	 * @param	string
	 * @return	int
	 */
	public function getLanguageId($lang) {
		$this->log("==> getLanguageId()",2);
		$query = $this->db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $lang . "'");
		$this->LANG_ID = $query->row['language_id'];
		return $query->row['language_id'];
	} // getLanguageId()


	/**
	 * Проверяет таблицы модуля
	 */
	public function checkDB() {
		$this->log("==> checkDB()",2);
		if (version_compare($this->config->get('exchange1c_version'), '1.6.2.b7', '<')) {
			$tables_module = array("product_to_1c","product_quantity","category_to_1c","warehouse","store_to_1c","attribute_to_1c","manufacturer_to_1c");
		} else {
			$tables_module = array("product_to_1c","product_quantity","product_price","category_to_1c","warehouse","product_feature","product_feature_value","store_to_1c","attribute_to_1c","manufacturer_to_1c","unit");
		}
		foreach ($tables_module as $table) {
			$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "%" . $table . "'");
			if (!$query->rows) {
				$error = "[ERROR] Таблица " . $table . " в базе отсутствует, переустановите модуль! Все связи будут потеряны!";
				$this->log(1,$error);
				return $error;
			}
		}
		// проверка полей таблиц
		
		return "";
	} // checkDB()


	/**
	 * Устанавливает SEO URL (ЧПУ) для заданного товара
	 * @param 	inf
	 * @param 	string
	 */
	private function setSeoURL($url_type, $element_id, $element_name) {
		$this->log("==> setSeoURL()",2);
		$sql = "DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $url_type . "=" . $element_id . "'";
		$this->log($sql,2); 
		$this->db->query($sql);

		$sql = "INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword`='" . $element_name . "'";
		$this->log($sql,2); 
		$this->db->query($sql);

	} // setSeoURL()


	/**
	 * Транслиетрирует RUS->ENG
	 * @param string $aString
	 * @return string type
	 */
	private function transString($aString) {
		$rus = array(" ", "/", "*", "-", "+", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "+", "[", "]", "{", "}", "~", ";", ":", "'", "\"", "<", ">", ",", ".", "?", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ъ", "Ы", "Ь", "Э", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ъ", "ы", "ь", "э", "ё",  "ж",  "ц",  "ч",  "ш",  "щ",   "ю",  "я",  "Ё",  "Ж",  "Ц",  "Ч",  "Ш",  "Щ",   "Ю",  "Я");
		$lat = array("-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-",  "-", "-", "-", "-", "-", "-", "a", "b", "v", "g", "d", "e", "z", "i", "y", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "",  "i", "",  "e", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s", "t", "u", "f", "h", "",  "i", "",  "e", "yo", "zh", "ts", "ch", "sh", "sch", "yu", "ya", "yo", "zh", "ts", "ch", "sh", "sch", "yu", "ya");
		$string = str_replace($rus, $lat, $aString);
		while (mb_strpos($string, '--')) {
			$string = str_replace('--', '-', $string);
		}
		$string = strtolower(trim($string, '-'));
		return $string;
	} // transString()


	/**
	 * Формирует строку запроса при наличии переменной
	 */
	private function setStrQuery($field_name, $type) {
		if ($type == 'string') {
			return isset($data[$field_name]) ? ", " . $field_name . " = '" . $this->db->escape($data[$field_name]) . "'" : "";
			
		}
		elseif ($type == 'int') {
			return isset($data[$field_name]) ? ", " . $field_name . " = '" . (int)$data[$field_name] . "'" : "";
		}
		return "";
	} //setStrQuery()


	/**
	 * Поиск cml_id товара по ID
	 */
	private function getcml_idByProductId($product_id) {
		$query = $this->db->query("SELECT 1c_id FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . $product_id);
		return isset($query->row['1c_id']) ? $query->row['1c_id'] : '';
	} // getcml_idByProductId()


	/**
	 * Проверка на существование поля в таблице
	 */
	public function existField($table, $field, $value="") {
		if (!$this->existTable($table)) return 0;
		$query = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` WHERE `field` = '" . $field . "'");
		if ($query->num_rows) {
			if (!empty($value)) {
				return ", " . $field . " = '" . $value . "'";
			} else {
				return 1;
			}
			
		}
		if (!empty($value)) {
			return "";
		} else {
			return 0;
		}
	} // existField()
	
	
	/**
	 * Проверка на существование таблицы
	 */
	private function existTable($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
		if ($query->num_rows) {
			return 1;
		} else {
			return 0;
		}
	} // existTable()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ SEO ****************************** 
	 */

	/**
	 * Получает все категории продукта
	 */
    private function getProductCategories($product_id) {

		$this->log("==> getProductCategories()",2);

        $categories = array();
		$query = $this->db->query("SELECT c.category_id, cd.name FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` pc ON (pc.category_id = c.category_id) WHERE cd.language_id = " . $this->LANG_ID . " AND pc.product_id = " . $product_id . " ORDER BY c.sort_order, cd.name ASC");
		foreach ($query->rows as $category) {
			$categories[] = $category['name'];
		}
		return implode(',', $categories);
		
    } // getProductCategories()


	/**
	 * Генерит SEO строк
	 */
	private function seoGenerateString($template, $product_tags, $trans = false) {
		
		$this->log("==> seoGenerateString()",2);
		
		// Выберем все теги которые используются в шаблоне
		preg_match_all('/\{(\w+)\}/', $template, $matches);
		$values = array();
		
		foreach ($matches[0] as $match) {
			$value = isset($product_tags[$match]) ? $product_tags[$match] : '';
			if ($trans) {
				$values[] = $this->transString($value);
			} else {
				$values[] = $value;
			}
		}
		
		return str_replace($matches[0], $values, $template);
		
	} // seoGenerateStr()


	/**
	 * Генерит SEO переменные шаблона для товара
	 */
	private function seoGenerateProduct(&$data) {
		$this->log("==> seoGenerateProduct()",2);
		
		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
			'tag'				=> array()
		);

		// Сопоставляем значения
		$tags = array(
			'{name}'		=> isset($data['name']) 		? $data['name'] 								: '',
			'{sku}'			=> isset($data['sku'])			? $data['sku'] 									: '',
			'{brand}'		=> isset($data['manufacturer'])	? $data['manufacturer'] 						: '',
			'{desc}'		=> isset($data['description'])	? $data['description'] 							: '',
			'{cats}'		=> isset($data['categories'])	? $data['categories'] 							: '',
			'{price}'		=> isset($data['price'])		? $this->currency->format($data['price']) 		: '',
			'{prod_id}'		=> isset($data['product_id'])	? $data['product_id'] 							: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 							: ''
		);

		if ($this->existField('product_description', 'meta_h1')) {
			$seo_fields['meta_h1'] = array();
		}

		// Получим поля для сравнения
		$fields_list = array();
		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') continue;
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');
		if (!isset($data['name']))
			$fields .= ", name";
		$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "product_description` WHERE product_id = " . $data['product_id'] . " AND language_id = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			foreach ($fields_list as $field) {
				$data[$field] = $query->row[$field];
				$this->log('field: '.$field,2);
			}
		}
		if (!isset($data['name']) && isset($query->row['name'])) {
			$data['name'] = $query->row['name'];
			$tags['{name}']	= $data['name'];
		}

		$this->log($data, 2);
		
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_product_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_product_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}
			if ($this->config->get('exchange1c_seo_product_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}
			$this->log("Поле '" . $field . "' = '" . $data[$field] . "'", 2);
		}
		
		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_product_overwrite') == 'overwrite') {
				$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE query = 'product_id=" . $data['product_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
				}
			}
		}

	} // seoGenerateProduct()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateCategory(&$data) {
		$this->log("==> seoGenerateCategory()",2);
		
		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);
		
		if ($this->existField('product_description', 'meta_h1')) {
			$seo_fields['meta_h1'] = array();
		}

		// Получим поля для сравнения
		$fields_list = array();
		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') continue;
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');
		$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` WHERE category_id = " . $data['category_id'] . " AND language_id = " . $this->LANG_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			foreach ($fields_list as $field) {
				$data[$field] = $query->row[$field];
			}
		}
		
		// Сопоставляем значения к тегам
		$tags = array(
			'{cat}'			=> isset($data['name']) 		? $data['name'] 		: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 	: ''
		);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_category_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_category_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_category_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}
			
			if ($this->config->get('exchange1c_seo_category_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}
			
		}
		
		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_category_overwrite') == 'overwrite') {
				$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE query = 'category_id=" . $data['category_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
				}
			}
		}

	} // seoGenerateCategory()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateManufacturer(&$data) {
		$this->log("==> seoGenerateManufacturer()",2);

		// Производители
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);

		
		if ($this->existTable('product_description')) {
			if ($this->existField('product_description', 'meta_h1')) {
				$seo_fields['meta_h1'] = array();
			}
			// Получим поля для сравнения
			$fields_list = array();
			foreach ($seo_fields as $field=>$param) {
				if ($field == 'seo_url') continue;
				$fields_list[] = $field;
			}
			$fields	= implode($fields_list,', ');
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "manufacturer_description` WHERE manufacturer_id = " . $data['manufacturer_id'] . " AND language_id = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				foreach ($fields_list as $field) {
					$data[$field] = $query->row[$field];
				}
			}
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{brand}'		=> isset($data['name']) 			? $data['name'] 			: '',
			'{brand_id}'	=> isset($data['manufacturer_id'])	? $data['manufacturer_id'] 	: ''
		);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_manufacturer_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'import') {
				// из свойства которое считалось при обмене
			}

			if ($this->config->get('exchange1c_seo_manufacturer_overwrite') == 'overwrite') {
				// Перезаписывать
				$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
			} else {
				// Только если поле пустое
				if (empty($data[$field])) {
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
				} else {
					$this->log("Поле '" . $field . "' не пустое", 2);
				}
			}

		}
		
		if (isset($data['seo_url'])) {
			if ($this->config->get('exchange1c_seo_manufacturer_overwrite') == 'overwrite') {
				$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
			} else {
				$sql = "SELECT keyword FROM `" . DB_PREFIX . "url_alias` WHERE query = 'manufacturer_id=" . $data['manufacturer_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$data['seo_url'] = $query->row['keyword'];
					if (empty($data['seo_url']))
						$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
				} else {
					$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
				}
			}
		}


	} // seoGenerateManufacturer()
	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ КАТАЛОГА ****************************** 
	 */

	/**
	 * Формирует строку запроса для категории
	 */
	private function prepareStrQueryCategory($data, $sep = ',') {
		$this->log("==> prepareStrQueryCategory()",2);
		if ($data['parent_id'] == 0) {
			$sql = ", top = '1'";
		} else {
			$sql = isset($data['top'])		? $sep . " top = '" . 			(int)$data['top'] . "'" 			: "";
		}
		$sql .= isset($data['column'])		? $sep . " column = '" .		(int)$data['column'] . "'" 			: "";
		$sql .= isset($data['sort_order'])	? $sep . " sort_order = '" . 	(int)$data['sort_order'] . "'" 		: "";
		$sql .= isset($data['status'])		? $sep . " status = '" . 		(int)$data['status'] . "'" 			: "";
		$sql .= isset($data['noindex'])		? $sep . " noindex = '" . 		(int)$data['noindex'] . "'" 		: "";
		return $sql;
	} //prepareStrQueryCategory()
	

	/**
	 * Формирует строку запроса для описания категорий и товаров
	 */
	private function prepareStrQueryDescription($data, $mode = 'set') {
		$this->log("==> prepareStrQueryDescription()",2);
		$sql = array();
		if (isset($data['name']))
			$sql[] = $mode == 'set' 	? "name = '" .				$this->db->escape($data['name']) . "'"				: "name";
		if (isset($data['description']))
			$sql[] = $mode == 'set' 	? "description = '" .		$this->db->escape($data['description']) . "'"		: "description";
		if (isset($data['meta_title']))
			$sql[] = $mode == 'set' 	? "meta_title = '" .		$this->db->escape($data['meta_title']) . "'"		: "meta_title";
		if (isset($data['meta_h1']))
			$sql[] = $mode == 'set' 	? "meta_h1 = '" .			$this->db->escape($data['meta_h1']) . "'"			: "meta_h1";
		if (isset($data['meta_description']))
			$sql[] = $mode == 'set' 	? "meta_description = '" .	$this->db->escape($data['meta_description']) . "'"	: "meta_description";
		if (isset($data['meta_keyword']))
			$sql[] = $mode == 'set' 	? "meta_keyword = '" .		$this->db->escape($data['meta_keyword']) . "'"		: "meta_keyword";
		if (isset($data['tag']))
			$sql[] = $mode == 'set' 	? "tag = '" .				$this->db->escape($data['tag']) . "'"				: "tag";
			
		return implode(($mode = 'set' ? ', ' : ' AND '),$sql);
	} //prepareStrQueryDescription()


	/**
	 * Подготавливает запрос для товара
	 */
	private function prepareQueryProduct($data, $mode = 'set') {
		$this->log('==> prepareQueryProduct()',2);
		$sql = array();
		if (isset($data['model']))
	 		$sql[] = $mode == 'set'		? "model = '" .				$this->db->escape($data['model']) . "'"				: "model";
		if (isset($data['sku']))
	 		$sql[] = $mode == 'set'		? "sku = '" .				$this->db->escape($data['sku']) . "'"				: "sku";
		if (isset($data['upc']))
	 		$sql[] = $mode == 'set'		? "upc = '" .				$this->db->escape($data['upc']) . "'"				: "upc";
		if (isset($data['ean']))
	 		$sql[] = $mode == 'set'		? "ean = '" .				$this->db->escape($data['ean']) . "'"				: "ean";
		if (isset($data['jan']))
	 		$sql[] = $mode == 'set'		? "jan = '" .				$this->db->escape($data['jan']) . "'"				: "jan";
		if (isset($data['isbn']))
	 		$sql[] = $mode == 'set'		? "isbn = '" .				$this->db->escape($data['isbn']) . "'"				: "isbn";
		if (isset($data['mpn']))
	 		$sql[] = $mode == 'set'		? "mpn = '" .				$this->db->escape($data['mpn']) . "'"				: "mpn";
		if (isset($data['location']))
	 		$sql[] = $mode == 'set'		? "location = '" .			$this->db->escape($data['location']) . "'"			: "location";
		if (isset($data['quantity']))
	 		$sql[] = $mode == 'set'		? "quantity = '" .			(float)$data['quantity'] . "'"						: "quantity";
		if (isset($data['minimum']))
	 		$sql[] = $mode == 'set'		? "minimum = '" .			(float)$data['minimum'] . "'"						: "minimum";
		if (isset($data['subtract']))
	 		$sql[] = $mode == 'set'		? "subtract = '" .			(int)$data['subtract'] . "'"						: "subtract";
		if (isset($data['stock_status_id']))
	 		$sql[] = $mode == 'set'		? "stock_status_id = '" .	(int)$data['stock_status_id'] . "'"					: "stock_status_id";
		if (isset($data['date_available']))
	 		$sql[] = $mode == 'set'		? "date_available = '" .	$this->db->escape($data['date_available']) . "'"	: "date_available";
		if (isset($data['manufacturer_id']))
	 		$sql[] = $mode == 'set'		? "manufacturer_id = '" .	(int)$data['manufacturer_id'] . "'"					: "manufacturer_id";
		if (isset($data['shipping']))
	 		$sql[] = $mode == 'set'		? "shipping = '" .			(int)$data['shipping'] . "'"						: "shipping";
		if (isset($data['price']))
	 		$sql[] = $mode == 'set'		? "price = '" .				(float)$data['price'] . "'"							: "price";
		if (isset($data['points']))
	 		$sql[] = $mode == 'set'		? "points = '" .			(int)$data['points'] . "'"							: "points";
		if (isset($data['length']))
	 		$sql[] = $mode == 'set'		? "length = '" .			(float)$data['length'] . "'"						: "length";
		if (isset($data['width']))
	 		$sql[] = $mode == 'set'		? "width = '" .				(float)$data['width'] . "'"							: "width";
		if (isset($data['weight']))
	 		$sql[] = $mode == 'set'		? "weight = '" .			(float)$data['weight'] . "'"						: "weight";
		if (isset($data['height']))
	 		$sql[] = $mode == 'set'		? "height = '" .			(float)$data['height'] . "'"						: "height";
		if (isset($data['status']))
	 		$sql[] = $mode == 'set'		? "status = '" .			(int)$data['status'] . "'"							: "status";
		if (isset($data['noindex']))
	 		$sql[] = $mode == 'set'		? "noindex = '" .			(int)$data['noindex'] . "'"							: "noindex";
		if (isset($data['tax_class_id']))
	 		$sql[] = $mode == 'set'		? "tax_class_id = '" .		(int)$data['tax_class_id'] . "'"					: "tax_class_id";
		if (isset($data['sort_order']))
	 		$sql[] = $mode == 'set'		? "sort_order = '" .		(int)$data['sort_order'] . "'"						: "sort_order";
		if (isset($data['length_class_id']))
	 		$sql[] = $mode == 'set'		? "length_class_id = '" .	(int)$data['length_class_id'] . "'"					: "length_class_id";
		if (isset($data['weight_class_id']))
	 		$sql[] = $mode == 'set'		? "weight_class_id = '" .	(int)$data['weight_class_id'] . "'"					: "weight_class_id";

		return implode(($mode = 'set' ? ', ' : ' AND '),$sql);
		
	} // prepareQueryProduct()



	/**
	 * Формирует строку запроса для описания производителя
	 */
	private function prepareStrQueryManufacturerDescription($data) {

		$this->log('==> prepareStrQueryManufacturerDescription()',2);

		$sql  = isset($data['description']) 		? ", description = '" . $this->db->escape($data['description']) . "'"				: "";
		if ($this->existField("manufacturer_description", "name")) {
			// Пока не знаю зачем это поле было добавлено в ocStore 2.1.0.2.1, в ocShop 2.1.0.1.4 его нет
			$sql .= isset($data['name']) 				? ", name = '" . $this->db->escape($data['name']) . "'" 							: "";
		}
		$sql .= isset($data['meta_description']) 	? ", meta_description = '" . $this->db->escape($data['meta_description']) . "'" 	: "";
		$sql .= isset($data['meta_keyword']) 		? ", meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['meta_title']) 			? ", meta_title = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_h1']) 			? ", meta_h1 = '" . $this->db->escape($data['meta_h1']) . "'" 						: "";
		return $sql;
	} //prepareStrQueryManufacturerDescription()


	/**
	 * Сравнивает запрос с массивом данных и формирует список измененных полей
	 */
	private function compareArrays($query, $data) {
		$this->log('==> compareArrays()',2);
		// Сравниваем значения полей, если есть изменения, формируем поля для запроса
		$upd_fields = array();
		if ($query->num_rows) {
			foreach($query->row as $key => $row) {
				if (!isset($data[$key])) continue;
				if ($row <> $data[$key]) {
					$upd_fields[] = $key . " = '" . $this->db->escape($data[$key]) . "'";
				} 
			}
		}
		return implode(', ', $upd_fields);
	} // compareArrays()
	

	/**
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories($data) {
		
		$this->log('==> fillParentsCategories()',2);
		if (!$data['product_id']) {
			$this->log(1,"[ERROR] Заполнение родительскими категориями отменено, т.к. не указан product_id!");
			return false;
		}
		
		// Перезаписываем все родительские категории
		$sql = "DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = " . $data['product_id'];
		$this->log($sql,2);
		$this->db->query($sql);
		
		// Подгружаем только один раз
		$this->load->model('catalog/product');
		
		foreach ($data['product_categories'] as $category_id) {
			$parents_id = array_merge($data['product_categories'], $this->findParentsCategories($category_id));
			foreach ($parents_id as $parent_id) {
				if ($parent_id != 0) {
					if (method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
						$this->db->query("INSERT INTO `" .DB_PREFIX . "product_to_category` SET product_id = " . $data['product_id'] . ", category_id = " . $parent_id . ", main_category = " . ($category_id == $parent_id ? 1 : 0));
					} else {
						$this->db->query("INSERT INTO `" .DB_PREFIX . "product_to_category` SET product_id = " . $data['product_id'] . ", category_id = " . $parent_id);
					}
					$this->log("> Родительская категория, category_id: " . $parent_id,2);
				}
			}
		}
		$this->log(1,"> Заполнены родительские категории");
		return true;
	} // fillParentsCategories()


	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {
		$this->log('==> findParentsCategories()',2);
		$result = array();
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX ."category` WHERE category_id = " . $category_id);
		if (isset($query->row['parent_id'])) {
			if ($query->row['parent_id'] <> 0) {
				$result[] = $query->row['parent_id'];
				$result = array_merge($result, $this->findParentsCategories($query->row['parent_id']));
			}  
		}
		return $result;
	} // findParentsCategories()


	/**
	 * Устанавливает в какой магазин загружать данные
	 */
	private function setStore($classifier_name) {
		$this->log('==> setStore()',2);
		$config_stores = $this->config->get('exchange1c_stores');
		if (!$config_stores) {
			$this->STORE_ID = 0;
			return;
		}
		// Если ничего не заполнено - по умолчанию
		foreach ($config_stores as $key => $config_store) {
			if ($classifier_name == "Классификатор (" . $config_store['name'] . ")") {
				$this->STORE_ID = $config_store['store_id'];
			}
		}
	} // setStore()
	 

	/**
	 * Возвращает id по cml_id
	 */
	private function getCategoryIdBycml_id($cml_id) {
		$this->log('==> getCategoryIdBycml_id()',2);
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_to_1c` WHERE 1c_id = '" . $this->db->escape($cml_id) . "'");
		return isset($query->row['category_id']) ? $query->row['category_id'] : 0;
	} // getCategoryIdBycml_id()


	/**
	 * Возвращает массив id,name категории по cml_id
	 */
	private function getCategoryBycml_id($cml_id) {
		$this->log('==> getCategoryBycml_id()',2);
		$query = $this->db->query("SELECT c.category_id, cd.name FROM `" . DB_PREFIX . "category_to_1c` c LEFT JOIN `" . DB_PREFIX. "category_description` cd ON (c.category_id = cd.category_id) WHERE c.1c_id = '" . $this->db->escape($cml_id) . "' AND cd.language_id = '" . $this->LANG_ID . "'");
		return $query->rows;
	} // getCategoryBycml_id()


	/**
	 * Обновляет описание категории
	 */
	private function updateCategoryDescription($data) {

		$this->log('==> updateCategoryDescription()',2);
		// Надо ли обновлять
		$fields = $this->prepareStrQueryDescription($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` cd LEFT JOIN `" . DB_PREFIX . "category_to_store` cs ON (cd.category_id = cs.category_id) WHERE cd.category_id = '" . (int)$data['category_id'] . "' AND cd.language_id = '" . $this->LANG_ID . "' AND cs.store_id = '" . $this->STORE_ID . "'";		
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Нечего даже обновлять
			$this->log("[i] Нечего обновлять",2);
			return false;
		}
		
//		$this->log($query,2);
//		$this->log($data,2);

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

//		$this->log($fields,2);

		// Если есть расхождения, производим обновление
		if ($fields) {
			$sql = "UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE category_id = '" . (int)$data['category_id'] . "' AND language_id = '" . (int)$this->LANG_ID . "'";
			$this->log($sql,2);
			$this->db->query($sql);

			$sql = "UPDATE `" . DB_PREFIX . "category` SET date_modified = NOW() WHERE category_id = " . (int)$data['category_id'];
			$this->log($sql,2);
			$this->db->query($sql);
			
			$this->log(1,"[i] Описание категории обновлено, обновленые поля: '" . $fields . "'");
			return true;
		}
		
		$this->log("[i] Описание категории не нуждается в обновлении",2);
		return false;

	} // updateCategoryDescription()


	/**
	 * Изменен ли родитель категории?
	 * Возврат истина - изменен, ложь - остался прежним
	 */
	private function changeParentCategory($data) {
		
		$this->log('==> changeParentCategory()',2);
		$sql = "SELECT c.parent_id FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_to_store` cs ON (c.category_id = cs.category_id) WHERE c.category_id = '" . (int)$data['category_id'] . "' AND cs.store_id = '" . (int)$this->STORE_ID . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		$update = false;
		
		if (isset($query->row['parent_id'])) {
			$old_parent_id = $query->row['parent_id'];
			if ($old_parent_id <> $data['parent_id']) {
				$update = true;
			}
		} else {
			$this->log(1,"[i] Не найден родитель у категории " . $data['category_id']);
			$update = true;
		}
		
		if ($update) {
			$sql = "UPDATE `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_to_store` cs ON (c.category_id = cs.category_id) SET parent_id = '" . $data['parent_id'] . "' WHERE c.category_id = '" . (int)$data['category_id'] . "' AND cs.store_id = '" . (int)$this->STORE_ID . "'";					
			$this->log($sql,2);
			$this->db->query($sql);
			$this->log("[i] Категория '" . $data['name'] . "' перенесена в id: " . $data['category_id'],2);
		}
		
		return $update;

	} // changeParentCategory()


	/**
	 * Добавляет иерархию категории
	 */
	private function addHierarchical($category_id, $data) {

		$this->log("==> addHierarchical()",2);
		
		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

			$level++;
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");
				
		$this->log("[i] Добавлена иерархия",2);
	} // addHierarchical()

	
	/**
	 * Обновляет иерархию категории
	 */
	private function updateHierarchical($data) {
		
		$this->log("==> updateHierarchical()",2);
		
		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE path_id = '" . (int)$data['category_id'] . "' ORDER BY level ASC");

		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' AND level < '" . (int)$category_path['level'] . "'");

				$path = array();

				// Get the nodes new parents
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Get whats left of the nodes current path
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$category_path['category_id'] . "' ORDER BY level ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Combine the paths with a new level
				$level = 0;

				foreach ($path as $path_id) {
					$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_path['category_id'] . "', `path_id` = '" . (int)$path_id . "', level = '" . (int)$level . "'");

					$level++;
				}
			}
		} else {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['category_id'] . "'");

			// Fix for records with no paths
			$level = 0;

			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");

			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$data['category_id'] . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");

				$level++;
			}

			$this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$data['category_id'] . "', `path_id` = '" . (int)$data['category_id'] . "', level = '" . (int)$level . "'");
		}
		
		$this->log("[i] Обновлена иерархия",2);

	} // updateHierarchical()


	/**
	 * Обновляет категорию
	 */
	private function updateCategory($data) {

		$this->log("==> updateCategory(): ",2);
		$this->log("------------------------------",2);

		$update = false;
		
		// Проверить изменен ли родитель...
		if ($this->changeParentCategory($data))
			$update = true;

		// SEO
 		$this->seoGenerateCategory($data);
		
		// Если было обновление описания  
		if ($this->updateCategoryDescription($data)) 
			$update = true;
		
		if ($update) {
			// Запись иерархии категорий если были изменения
			$this->updateHierarchical($data);
		}
		
		$this->cache->delete('category');
		
		$this->log("> Категория : '" . $data['name'] . "' обновлена",2);
		
		return $update;
		
	} // updateCategory()
	

	/**
	 * Добавляет категорию
	 */
	private function addCategory($data) {
		
		$this->log("==> addCategory()",2);
		$this->log("------------------------------",2);
		$this->log("> Категория: '" . $data['name'] . "'");

		if ($data == false) return 0;
		
		if ($this->config->get('exchange1c_new_category_status_off') == 1){
			$data['status'] = 0;
		}

		$sql = $this->prepareStrQueryCategory($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "category` SET parent_id = '" . (int)$data['parent_id'] . "'" . $sql . ", date_modified = NOW(), date_added = NOW()";
		$this->log($sql,2);
		$this->db->query($sql);

		$data['category_id'] = $this->db->getLastId();
		
		// SEO
 		$this->seoGenerateCategory($data);
		
		$this->log($data,2);
		// Описание
		$fields = $this->prepareStrQueryDescription($data, 'set');
		$this->log($fields,2);
		
		if ($fields) {
			$sql = "SELECT category_id FROM `" . DB_PREFIX . "category_description` WHERE category_id = '" . $data['category_id'] . "' AND language_id = '" . (int)$this->LANG_ID . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$this->log("[i] Добавление описания к категории отменено, так оно уже существует у category_id = " . $data['category_id']);
				return $data['category_id'];
			}
			
			$sql = "INSERT INTO `" . DB_PREFIX . "category_description` SET category_id = '" . $data['category_id'] . "', language_id = '" . (int)$this->LANG_ID . "', " . $fields;
			$this->log($sql,2);
			$this->db->query($sql);
		}

		// Запись иерархии категорий для админки
		$this->addHierarchical($data['category_id'], $data);
		
		// Магазин
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET category_id = '" . $data['category_id'] . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// Добавим линк
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_1c` SET category_id = " . $data['category_id'] . ", 1c_id = '" . $this->db->escape($data['cml_id']) . "'");
		
		// Чистим кэш
		$this->cache->delete('category');
		
		$this->log("> Категория добавлена: '" . $data['name'] . "', id: " . $data['category_id'] . ", Ид: " . $data['cml_id'],2);
		
		return $data['category_id'];

	} // addCategory()


	/**
	 * Обрабатывает категории
	 */
	private function parseCategories($xml, $parent_id=0) {
		$this->log("==> parseCategories()",2);
		
		foreach ($xml->Группа as $category){
			if (isset($category->Ид) && isset($category->Наименование) ){
				$data = array();
				$data['cml_id']			= (string)$category->Ид;
				$data['name']			= (string)$category->Наименование;
				$data['category_id']	= $this->getCategoryIdBycml_id($data['cml_id']);
				$data['parent_id']		= $parent_id;
				$data['status']			= 1;

				if (!$data['category_id']) {
					$data['category_id'] = $this->addCategory($data);
				} else {
					$this->updateCategory($data);
				}
			}
			if ($category->Группы) {
				$this->parseCategories($category->Группы, $data['category_id']);
			}
		}
		
	} // parseCategories()


	/**
	 * **************************************** ОПЦИИ ТОВАРА ******************************************
	 */


	/**
	 * Поиск опции в товаре по id товара и id опции
	 */
	private function getProductOption($product_id, $option_id, $value='') {
		
		$this->log("==> getProductOption()",2);

		// Ищем опцию в товаре
		$sql = "SELECT product_option_id FROM `" . DB_PREFIX . "product_option` WHERE product_id = '" . $product_id . "' AND option_id = '" . $option_id . "' AND value = '" . $value . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
        if ($query->num_rows) {
        	return $query->row['product_option_id'];
        }
		return 0;

	} // getProductOption()


	/**
	 * добавляет опцию в товар
	 */
	private function addProductOption($product_id, $option_id, $value='', $required=1) {
		
		$this->log("==> addProductOption()",2);
		
		// Добавляем опцию в товар
		$sql = "INSERT INTO `" . DB_PREFIX . "product_option` SET product_id = '" . $product_id . "', option_id = '" . $option_id . "', value = '" . $value . "', required = '" . $required . "'";
 		$this->log($sql,2);
		$this->db->query($sql);
		
		$product_option_id = $this->db->getLastId();

		$this->log("product_option_id = " . $product_option_id,2);

       	return $product_option_id;
		
	} // addProductOption()


	/**
	 * Находит или добавляет значение опции в товар
	 * Опций будет ровно столько сколько видов цен у товара для групп покупателей
	 */
	private function setProductOptionValue($data) {
		
		$this->log("==> setProductOptionValue()",2);
		//$this->log("option:",2);
		//$this->log($option,2);
		//$this->log("data:",2);
		//$this->log($data,2);

		// Читаем значения опции товара для всех групп покупателей
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '" . $data['product_id'] . "' AND product_option_id = '" . $data['product_option_id'] . "' AND option_id = '" . $data['option_id'] . "' AND option_value_id = '" . $data['option_value_id'] . "' AND product_feature_id = '" . $data['product_feature_id'] . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		$values_id = array();
		
		if (isset($data['prices'])) {
			$value_old = array();
			// добавляем опции для каждой группы покупателей
			foreach ($data['prices'] as $price_data) {
				//$this->log("price_data:");
				//$this->log($price_data);
				$found = false;
				foreach ($query->rows as $row) {
					//$this->log("row:");
					//$this->log($row);
					if ($row['customer_group_id'] == $price_data['customer_group_id'] or $row['customer_group_id'] == 0 && $price_data['default']) {
						$sql = "UPDATE `" . DB_PREFIX . "product_option_value` SET price = '" . $price_data['value'] . "', quantity = '" . $data['quantity'] . "' WHERE product_option_value_id = '" . $row['product_option_value_id'] . "'";
						$this->log($sql,2);
						$this->db->query($sql);
						
						$values_id[$price_data['customer_group_id']] = $row['product_option_value_id'];
						$found = true;
					}
				}
				if (!$found) {
					$sql = "INSERT INTO `" . DB_PREFIX . "product_option_value` SET product_option_id = '" . $data['product_option_id'] . "', product_id = '" . $data['product_id'] . "', option_id = '" . $data['option_id'] . "', option_value_id = '" . $data['option_value_id'] . "', customer_group_id = '" . $price_data['customer_group_id'] . "', price = '" . $price_data['value'] . "', quantity = '" . $data['quantity'] . "', product_feature_id = '" . $data['product_feature_id'] . "', price_prefix = '+', points_prefix = '+'";
					$this->log($sql,2);
					$this->db->query($sql);
					
					$values_id[$price_data['customer_group_id']] = $this->db->getLastId();
				}
			}

		}
		return $values_id;
		
	} // setProductOptionValue()


	/**
	 * Поиск значения опции по названию
	 */
	private function getOptionValueByName($option_id, $name) {
		
		$this->log("==> getOptionValueByName()",2);
		$sql = "SELECT option_value_id FROM `" . DB_PREFIX . "option_value_description` WHERE language_id = '" . $this->LANG_ID . "' AND option_id = '" . $option_id . "' AND name = '" . $this->db->escape($name) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
 			$this->log("option_value_id = " . $query->row['option_value_id'],2);
        	return $query->row['option_value_id'];
       	}
		
		return 0;
		
	} // getOptionValueByName()


	/**
	 * Добавляет или получает значение опциию по названию
	 */
	private function setOptionValue($option_id, $value, $image='', $sort_order=0) {
		
		$this->log("==> setOptionValue()",2);
		// Проверим есть ли такое значение
		$option_value_id = $this->getOptionValueByName($option_id, $value);
		
		if ($option_value_id)
			return $option_value_id;
		
		$sql = "INSERT INTO `" . DB_PREFIX . "option_value` SET option_id = '" . $option_id . "', image = '" . $image . "', sort_order = '" . $sort_order . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$option_value_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_value_description` SET option_id = '" . $option_id . "', option_value_id = '" . $option_value_id . "', language_id = '" . $this->LANG_ID . "', name = '" . $this->db->escape($value) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		return $option_value_id;  
	
	} // setOptionValue()


	/**
	 * Добавляет или получает значение опциию по названию
	 */
	private function setOptionValues($option_id, $values) {
		
		$this->log("==> setOptionValues()",2);
		
		// Удалим все значения опции
		//$sql = "DELETE FROM `" . DB_PREFIX . "option_value` WHERE option_id = '" . $option_id . "'";
 		//$this->log($sql,2);
		//$this->db->query($sql);
		
		foreach ($values as $key => $value) {

			$option_value_id = 0;
			
			$sql = "SELECT ov.option_value_id FROM `" . DB_PREFIX . "option_value` ov LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (ov.option_value_id = ovd.option_value_id) WHERE ov.option_id = '" . $option_id . "' AND ovd.language_id = '" . $this->LANG_ID . "' AND ovd.name = '" . $value['name'] . "'";
	 		$this->log($sql,2);
			$query = $this->db->query($sql);

			if ($query->num_rows) {
				$option_value_id = $query->row['option_value_id'];
			}
			
			if (!$option_value_id) {

				$sql = "INSERT INTO `" . DB_PREFIX . "option_value` SET option_id = '" . $option_id . "', image = '" . $value['image'] . "', sort_order = '" . $value['sort_order'] . "'";
		 		$this->log($sql,2);
				$query = $this->db->query($sql);
		
				$option_value_id = $this->db->getLastId();
		
				$sql = "INSERT INTO `" . DB_PREFIX . "option_value_description` SET option_id = '" . $option_id . "', option_value_id = '" . $option_value_id . "', language_id = '" . $this->LANG_ID . "', name = '" . $value['name'] . "'";
		 		$this->log($sql,2);
				$query = $this->db->query($sql);
	
			}
			
			$values[$key]['option_value_id'] = $option_value_id;
		}
		
		//$this->log($values,2);
		return $values;
		
	} // setOptionValues()


	/**
	 * Установка опции
	 */
	private function setOption($name, $type='select') {

		$this->log("==> setOption()",2);
		
		$option_id = 0;
		$sql = "SELECT o.option_id, o.type FROM `" . DB_PREFIX . "option` o LEFT JOIN `" . DB_PREFIX . "option_description` od ON (o.option_id = od.option_id) WHERE od.name = '" . $name . "' AND od.language_id = '" . $this->LANG_ID . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
        if ($query->num_rows) {
        	$option_id = $query->row['option_id'];
        	if ($query->row['type'] <> $type) {
				$sql = "UPDATE `" . DB_PREFIX . "option` SET `type`='" . $type . "' WHERE option_id = " . $option_id;
				$this->log($sql,2);
				$this->db->query($sql);
        	}
        }
			
		// Если опции нет, добавляем
		if (!$option_id) {
			$option_id = $this->addOption($name, $type);
		}
		
		return $option_id;

	} // setOption()




	/**
	 * **************************************** ФУНКЦИИ ДЛЯ РАБОТЫ С ТОВАРОМ ******************************************
	 */


	/**
	 * Добавляет товар в базу
	 */
	private function addProduct($data) {

		$this->log("==> addProduct()",2);
		
		if ($this->config->get('exchange1c_new_product_status_off') == 1){
			$data['status'] = 0;
		}
		
		// Подготовим список полей по которым есть данные
		$fields = $this->prepareQueryProduct($data);
		if ($fields) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product` SET " . $fields . ", date_added = NOW(), date_modified = NOW()";
			$this->log(2,$sql);
			$this->db->query($sql);
			
			$data['product_id'] = $this->db->getLastId();
			
		} else {
			// Если нет данны - выходим
			return false;
		}
		
		// описание (пока только для одного языка)
		$fields = $this->prepareStrQueryDescription($data);
		if ($fields) {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_description` SET product_id = '" . $data['product_id'] . "', language_id = '" . (int)$this->LANG_ID . "', " . $fields;
			$this->log($sql,2);
			$this->db->query($sql);
		}
		
		// категории продукта
		// Если есть поле main_category
		$main_category = $this->existField("product_to_category", "main_category", 1);
		
		if (isset($data['product_categories'])) {
			foreach ($data['product_categories'] as $category_id) {
				$sql = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . $data['product_id'] . "', category_id = '" . (int)$category_id . "'" . $main_category;
				$this->log($sql,2);		 
				$this->db->query($sql);
				$this->log("[i] В товар добавлена категория, category_id: " . $category_id,2);
			}
		}
		
		// Устанавливаем магазин
		$sql = "SELECT store_id  FROM `" . DB_PREFIX . "product_to_store` WHERE product_id = " . $data['product_id'] . " AND store_id = " . $this->STORE_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = " . $data['product_id'] . ", store_id = " . (int)$this->STORE_ID);
		}
		
		// Связь с 1С
		$sql = "INSERT INTO `" . DB_PREFIX . "product_to_1c` SET product_id = '" . $data['product_id'] . "', 1c_id = '" . $data['product_cml_id'] . "'";
		$this->log($sql,2);		 
		$this->db->query($sql);

		// Чистим кэш
		$this->cache->delete('product');
		
		$this->log("[i] Товар добавлен. product_id: " . $data['product_id'],2);
		
		return $data['product_id'];
		 		
	} // addProduct()
	

	/**
	 * Обновляет описание товара в базе для одного языка
	 */
	private function updateProductDescription($data) {
		
		$this->log("==> updateProductDescription()",2);

		$fields = $this->prepareStrQueryDescription($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . " FROM `" . DB_PREFIX . "product_description` WHERE product_id = '" . $data['product_id'] . "' AND language_id = '" . (int)$this->LANG_ID . "'";		
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Нечего обновлять даже
			$this->log("[i] Нет заданы поля для обновления",2);
			return false;
		}

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$this->log($query,2);
		$this->log($data,2);
		$fields = $this->compareArrays($query, $data);
		$this->log($fields,2);
		
		// Если есть расхождения, производим обновление
		if ($fields) {
			
			$sql = "UPDATE `" . DB_PREFIX . "product_description` SET language_id = '" . (int)$this->LANG_ID . "', " . $fields . " WHERE product_id = '" . $data['product_id'] . "'";
			$this->log($sql,2);
			$this->db->query($sql);
			
			$this->log("[i] Описание категории обновлено, обновлены поля: '" . $fields . "'",2);
			
			return true;
		}

		return false;

	} // updateProductDescription()
	
	
	/**
	 * Обновляет товар в базе
	 */
	private function updateProduct($data) {

		$this->log("==> updateProduct()",2);
		$this->log($data,2);		
		$update = false;

		// Обнуляем остаток только у тех товаров что загружаются 
		if ($this->config->get('exchange1c_flush_quantity') == 1) {
			$data['quantity'] = 0;
		}

		// ФИЛЬТР ОБНОВЛЕНИЯ
		if ($this->config->get('exchange1c_import_product_name') == 'disable') {
			unset($data['name']);
			$this->log("[i] Обновление названия отключено",2);
		}
		if (!$this->config->get('exchange1c_import_categories') == 1) {
			unset($data['product_categories']);
			$this->log("[i] Обновление категорий отключено",2);
		}
		if (!$this->config->get('exchange1c_import_product_description') == 1) {
			unset($data['description']);
			$this->log("[i] Обновление описаний товаров отключено",2);
		}
		// КОНЕЦ ФИЛЬТРА

		$fields = $this->prepareQueryProduct($data, 'get');
		if ($fields) {
			$sql = "SELECT " . $fields . "  FROM `" . DB_PREFIX . "product` WHERE product_id = '" . $data['product_id'] . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);
		}
		
		// SEO формируем только из offers
		if (isset($data['product_new']))
			$this->seoGenerateProduct($data);
		
		//$this->log($data, 2);

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

		if ($fields) {
			$sql = "UPDATE `" . DB_PREFIX . "product` SET " . $fields . ", date_modified = NOW() WHERE product_id = '" . $data['product_id'] . "'";
			$this->log($sql,2);
			$this->db->query($sql);
			$update = true;
		}
		
		if ($this->updateProductDescription($data))
			$update = true;
		
		// категории
		$main_category = $this->existField("product_to_category", "main_category", 1);
		
		// Перезаписываем все категории товара, товар может переместили в другую категорию
		if (isset($data['product_categories'])) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . $data['product_id'] . "'");
			foreach ($data['product_categories'] as $category_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . $data['product_id'] . "', category_id = '" . (int)$category_id . "'" . $main_category);
			}
		}
		
		// Устанавливаем магазин
		$sql = "SELECT store_id  FROM `" . DB_PREFIX . "product_to_store` WHERE product_id = " . $data['product_id'] . " AND store_id = " . $this->STORE_ID;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if (!$query->num_rows) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = " . $data['product_id'] . ", store_id = " . (int)$this->STORE_ID);
		}
		
		$this->cache->delete('product');
		
	} // updateProduct()

	
	/**
	 * Получает product_id по артикулу
	 */
	private function getProductBySKU($sku) {
		
		$this->log("==> getProductBySKU()",2);
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE sku = '" . $this->db->escape($sku) . "'");
        
		if ($query->num_rows) 
			return $query->row['product_id'];
		else 
			return 0;
	} // getProductBySKU()


	/**
	 * Получает product_id по наименованию товара
	 */
	private function getProductByName($name) {
		
		$this->log("==> getProductByName()",2);
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE name = '" . $this->db->escape($name) . "'");
        
		if ($query->num_rows) 
			return $query->row['product_id'];
		else 
			return 0;
	} // getProductByName()


	/**
	 * Обновление или добавление товара
	 */
 	private function setProduct(&$data) {
		$this->log("==> setProduct()",2);
		
 		// Ищем товар...
 		$data['product_id'] = $this->getProductIdByCML($data['product_cml_id']);
 		if (!$data['product_id']) {
 			if ($this->config->get('synchronize_new_product_by') == 'sku' && isset($data['sku'])) {
				$data['product_id'] = $this->getProductBySKU($data['sku']);
 			} elseif ($this->config->get('synchronize_new_product_by') == 'name' && isset($data['name'])) {
 				$data['product_id'] = $this->getProductByName($data['name']);
 			}
 		}
 		// Можно добавить поиск по наименованию или другим полям...
		
 		// Если не найден товар...
 		if (!$data['product_id']) {
 			$data['product_id'] = $this->addProduct($data);
 		} else {
 			$this->updateProduct($data);
 		}
 	} // setProduct()

	
	/**
	 * Загружает реквизиты товара в массив
	 */
	private function parseRequisite($xml, $data) {
		$this->log("==> parseRequisite()",2);

		$this->log("> Всего реквизитов: " . sizeof($xml->ЗначениеРеквизита));
				
		foreach ($xml->ЗначениеРеквизита as $requisite){
			$name 	= (string)$requisite->Наименование;
			$value 	= $requisite->Значение;
			
			switch ($name){
				case 'Вес':
					$data['weight'] = $value ? (float)str_replace(',','.',$value) : 0;
					$this->log("> Реквизит: " . $name. " => weight",2);
				break;
				case 'ТипНоменклатуры':
					$data['item_type'] = $value ? (string)$value : '';
					$this->log("> Реквизит: " . $name. " => item_type",2);
				break;
				case 'ВидНоменклатуры':
					$data['item_view'] = $value ? (string)$value : '';
					$this->log("> Реквизит: " . $name. " => item_view",2);
				break;
				case 'ОписаниеВФорматеHTML':
					if ($value) {
						$data['description'] =  (string)$value;
						$this->log("> Реквизит: " . $name. " => description (HTML format)",2);
					}
				break;
				case 'Полное наименование':
					if ($this->config->get('exchange1c_import_product_name') == 'fullname') {
						$data['name'] = $value ? htmlspecialchars((string)$value) : '';
						$this->log("> Реквизит: " . $name. " => full_name",2);
					}
				break;
				default:
					$this->log("[!] Неиспользуемый реквизит: " . $name. " = " . (string)$value,2);
			}
		}
		return $data;
	} // parseRequisite()
	

	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 */
	private function applyWatermark($filename, $wm_filename) {
		$this->log("==> applyWatermark()",2);

		$wm_fullname = DIR_IMAGE . $wm_filename;
		$fullname = DIR_IMAGE . $filename;

		if (is_file($wm_fullname) && is_file($fullname)) {

			// Получим расширение файла
			$info = pathinfo($filename);
			$extension = $info['extension'];

			// Создаем объект картинка из водяного знака и получаем информацию о картинке
			$image = new Image($fullname);
			if (version_compare($this->config->get('exchange1c_CMS_version'), '2.0.3.1', '>')) {
				$image->watermark(new Image($wm_fullname));
			} else  {
				$image->watermark($wm_fullname);
			}

			// Формируем название для файла с наложенным водяным знаком
			$new_image = utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '_wm.' . $extension;

			// Сохраняем картинку с водяным знаком
			$image->save(DIR_IMAGE . $new_image);
			$this->log("> Наложен водяной знак",1);
			$this->log("> Файл с водяным знаком " . $new_image,1);
			return $new_image;
		}
		else {
			return $filename;
		}
	} // applyWatermark()


	/**
	 * Определяет что за файл и принимает дальнейшее действие
	 */
	private function setFile($filename, $product_id) {
		$this->log("==> setFile()",2);
		$info = pathinfo($filename);
		if (isset($info['extension'])) {

			// Если расширение txt - грузим в описание
			if ($info['extension'] == "txt") {
				$description = file_get_contents($filename);
				// если не в кодировке UTF-8, переводим
				if (!mb_check_encoding($description, 'UTF-8')) {
					$description = nl2br(htmlspecialchars(iconv('windows-1251', 'utf-8', $description)));
				}
				// обновляем только описание
				$this->updateProductDescription(array('description'	=> $description), $product_id);
				$this->log("> Добавлено описание товара из файла",1);
				$this->log("> Файл описания  " . $filename,2);
				return 1;
			}
		}
		return 0;
	} // setFile())
	

	/**
	 * Добавляет картинки в товар
	 */
	private function parseImages($xml, $product_id) {
		$this->log("==> parseImages()",2);

		if (!$product_id) {
			$this->log("[ERROR] Обновление картинок отменено, так как product_id=0");
			return false;
		}

		$watermark = $this->config->get('exchange1c_watermark');
		$index = 0;
		
		// Нужно ли обновлять картинки товара
		if (!$this->config->get('exchange1c_import_images') == 1) {
			$this->log("[i] Обновление картинок отключено!",1);
			return true;
		}
		
		$sql = "DELETE FROM `" . DB_PREFIX . "product_image` WHERE product_id = '" . (int)$product_id . "'";
		$this->log($sql,2);
		$this->db->query($sql);
		foreach ($xml as $image) {

			$image = (string)$image;
			$this->log("Картинка: " . $image,2);
			
			if (empty($image)) {
				continue;
			}
			
			$full_image = DIR_IMAGE . $image;
			
			if (file_exists($full_image)) {
				// не картинки обрабатываем тут
				//$this->log("Расположение картинки: " . $full_image,2);
				if (getimagesize($full_image) == NULL) {
					if (!$this->setFile($full_image, $product_id)) {
						$this->log("Файл '" . $image . "' не является картинкой",1);
					}
					continue;
				}
				// накладываем водяные знаки только на существующую картинку 
				$newimage = empty($watermark) ? $image : $this->applyWatermark($image, $watermark);
			} else {
				// если картинки нет подставляем эту
				$newimage = 'no_image.png';
			}
			
			// основная картинка
			if ($index == 0) {
				$sql = "UPDATE `" . DB_PREFIX . "product` SET image = '" . $this->db->escape($newimage) . "' WHERE product_id = '" . (int)$product_id . "'";
				$this->log($sql,2);
				$this->db->query($sql);
				$this->log("> Картинка основная: '" . $newimage . "'",2);
			}
			// дополнительные картинки
			else {
				$sql = "INSERT INTO `" . DB_PREFIX . "product_image` SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($newimage) . "', sort_order = '" . (int)$index . "'";
				$this->log($sql,2);
				$this->db->query($sql);
				$this->log("> Картинка дополнительная: '" . $newimage . "'",2);
			}
			$index ++;
		}
		$this->log("> Картинок: " . $index,1);
		return true;
	} // parseImages()


	/**
	 * Возвращает id группы для свойств
	 */
	private function setAttributeGroup($name) {
		$this->log("==> setAttributeGroup()",2);
		$query = $this->db->query("SELECT attribute_group_id FROM `" . DB_PREFIX . "attribute_group_description` WHERE name = '" . $name . "'");
		if ($query->rows) {
			return $query->row['attribute_group_id'];
		}
		
		// Добавляем группу
		$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group` SET sort_order = '1'");
		$attribute_group_id = $this->db->getLastId();
		$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_group_description` SET attribute_group_id = '" . (int)$attribute_group_id . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $name . "'");
		return $attribute_group_id;
	} // setAttributeGroup()


	/**
	 * Возвращает id атрибута из базы
	 */
	private function setAttribute($cml_id, $attribute_group_id, $name, $sort_order) {
		$this->log("==> setAttribute()",2);

		// Ищем свойства по 1С Ид
		$query = $this->db->query("SELECT attribute_id FROM `" . DB_PREFIX . "attribute_to_1c` WHERE 1c_id = '" . $this->db->escape($cml_id) . "'");
		if ($query->num_rows) {
			return $query->row['attribute_id'];
		}

		// Попытаемся найти по наименованию
		$query = $this->db->query("SELECT a.attribute_id FROM `" . DB_PREFIX . "attribute` a LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON (a.attribute_id = ad.attribute_id) WHERE ad.language_id = '" . $this->LANG_ID . "' AND ad.name LIKE '" . $this->db->escape($name) . "' AND a.attribute_group_id = '" . (int)$attribute_group_id . "'");
		if ($query->num_rows) {
			return $query->row['attribute_id'];
		}
		
		// Добавим в базу характеристику
		$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute` SET attribute_group_id = '" . (int)$attribute_group_id . "', sort_order = '" . (int)$sort_order . "'"); 
		$attribute_id = $this->db->getLastId();
		$this->db->query("INSERT INTO `" . DB_PREFIX . "attribute_description` SET attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $this->db->escape($name) . "'");
			
		// Добавляем ссылку для 1С Ид
		$this->db->query("INSERT INTO `" .  DB_PREFIX . "attribute_to_1c` SET attribute_id = '" . (int)$attribute_id . "', 1c_id = '" . $this->db->escape($cml_id) . "'");
			
		return $attribute_id;
	} // setAttribute()


	/**
	 * Загружает значения атрибута (Свойства из 1С)
	 */
	private function parseAttributesValues($xml) {
		$this->log("==> parseAttributesValues()",2);
		$data = array();
		if (!$xml) return $data;
		
		if (isset($xml->ВариантыЗначений)) {
			if (isset($xml->ВариантыЗначений->Справочник)) {
				foreach ($xml->ВариантыЗначений->Справочник as $item) {
					$data[(string)$item->ИдЗначения] = (string)$item->Значение;
					$this->log("> Значение: " . (string)$item->Значение,2);
				}
			}
		}
		return $data;
	} // parseAttributesValues()


	/**
	 * Загружает атрибуты (Свойства из 1С) в классификаторе
	 */
	private function parseAttributes($xml) {
		$this->log("==> parseAttributes()",2);
		
		// Установим группу для свойств
		$attribute_group_id = $this->setAttributeGroup('Свойства');
		
		$data = array();
		$sort_order = 0;
		if ($xml->Свойство) {
			$properties = $xml->Свойство;
		} else {
			$properties = $xml->СвойствоНоменклатуры;
		}
		foreach ($properties as $property) {
			$cml_id		= (string)$property->Ид;
			$name 		= trim((string)$property->Наименование);

			switch ($name) {
				case 'Производитель':
					$values = $this->parseAttributesValues($property);
					foreach ($values as $val_cml_id=>$value) {
						$manufacturer_id = $this->setManufacturer($value, $val_cml_id);
					}
				//break;
				default:
					$data[$cml_id] = array(
						'name'			=> $name,
						'attribute_id'	=> $this->setAttribute($cml_id, $attribute_group_id, $name, $sort_order) 
					);
					$values = $this->parseAttributesValues($property);
					if ($values) {
						$data[$cml_id]['values'] = $values;
					}
					$sort_order ++;
					$this->log("> Свойство: '" . $name . "'",2);
			}
			
		}
		$this->log("> Свойств загружено: " . sizeof($properties),1);

		return $data;
		
	} // parseAttributes()


	/**
	 * Читает свойства товара  записывает их в массив
	 */
	private function parseProductAttributes($xml, $attributes, $data) {
		$this->log("==> parseProductAttributes()",2);

		$product_attributes = array();
		
		foreach ($xml->ЗначенияСвойства as $property) {
			
			// Ид объекта в 1С
			$cml_id = (string)$property->Ид;
			
			// Загружаем только те что в классификаторе
			if (!isset($attributes[$cml_id])) {
				$this->log("[i] Свойство не было загружено в классификаторе, Ид: " . $cml_id,2);
				continue;
			}
			
			$name 	= trim($attributes[$cml_id]['name']);
			$value 	= trim((string)$property->Значение);
			
			if ($value) {
				if ($attributes[$cml_id]) {
					// агрегатный тип
					if (isset($attributes[$cml_id]['values'])) {
						$value = trim($attributes[$cml_id]['values'][$value]);
					}
				}
			}
			
			// Пропускаем с пустыми значениями
			if (empty($value))
				continue;

			switch ($name) {
				case 'Производитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if (!isset($data['manufacturer_id'])) {
						$data['manufacturer_id'] = $this->setManufacturer($value);
						$this->log("> Производитель (из свойства): " . $value . ", id: " . $data['manufacturer_id'],1);
					} else {
						$this->log("> Производитель уже известен, id: " . $data['manufacturer_id'],1);
					}
				break;
				case 'Вес':
					$data['weight'] = (float)str_replace(',','.',$value);
					$this->log("> Свойство Вес => weight = ".$data['weight'],1);
				break;
				case 'Ширина':
					$data['width'] = (float)str_replace(',','.',$value);
					$this->log("> Свойство Ширина => width",1);
				break;
				case 'Высота':
					$data['height'] = (float)str_replace(',','.',$value);
					$this->log("> Свойство Высота => height",1);
				break;
				case 'Длина':
					$data['length'] = (float)str_replace(',','.',$value);
					$this->log("> Свойство Длина => length",1);
				break;
				case 'Модель':
					$data['model'] = (string)$value;
					$this->log("> Свойство Модель => model",1);
				break;
				case 'Артикул':
					$data['sku'] = (string)$value;
					$this->log("> Свойство Артикул => sku",1);
				break;
				default:
					$product_attributes[] = array(
						'name'			=> $name,
						'value'			=> $value,
						'cml_id'		=> $cml_id,
						'attribute_id'	=> $attributes[$cml_id]['attribute_id']
					);
					$this->log("> Свойство: '" . $name . "' = '" . $value . "'",2);
			}
		}
		$data['product_attributes'] = $product_attributes;
		return $data;
		
	} // parseProductAttributes()


	/**
	 * Устанавливает свойства в товар из массива
	 */
	private function setProductAttributes($data) {

		$this->log("==> setAttributes()",2);
		//$this->log($data,2);
		// Удаляем старые атрибуты
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE product_id = '" . $data['product_id'] . "'");
		foreach ($data['product_attributes'] as $property) {
			// Добавим в товар
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET product_id = '" . $data['product_id'] . "', attribute_id = '" . $property['attribute_id'] . "', language_id = '" . $this->LANG_ID . "', text = '" .  $this->db->escape($property['value']) . "'");
			$this->log("> Свойство '" . $property['name'] . "' = '" . $property['value'] . "' записано в товар id: " . $data['product_id'],2);
		}
	} // setProductAttributes()


	/**
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($data, $manufacturer_id) {
		$this->log("==> updateManufacturer()",2);
		$sql = "SELECT name FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		if ($query->row['name'] <> $data['name']) {
			// Обновляем
			$sql  = " name = '" . $this->db->escape($data['name']) . "'";
			$sql .= isset($data['noindex']) ? ", noindex = '" . (int)$data['noindex'] . "'" : "";
			$sql = "UPDATE `" . DB_PREFIX . "manufacturer` SET " . $sql . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "'";
			$this->log($sql,2);
			$this->db->query($sql);
		}
		
		if ($this->existTable('manufacturer_description')) {

	        $this->seoGenerateManufacturer($data);
        
			$sql = "SELECT name, description, meta_title, meta_description, meta_keyword FROM `" . DB_PREFIX . "manufacturer_description` WHERE manufacturer_id = " . (int)$manufacturer_id . " AND language_id = " . $this->LANG_ID;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			
			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$fields = $this->compareArrays($query, $data);
	
			if ($fields) {
				$sql = "UPDATE `" . DB_PREFIX . "manufacturer_description` SET " . $fields . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "' AND language_id = " . $this->LANG_ID;
				$this->log($sql,2);
				$this->db->query($sql);
				$this->log("> Обновлено описание производителя '" . $data['name'] . "'",2);
			}
	
		}
		
		return true;
	} // updateManufacturer()
	

	/**
	 * Добавляем производителя
	 */
	private function addManufacturer($data) {
		
		$this->log("==> addManufacturer()",2);
		
		$sql 	 = " name = '" . $this->db->escape($data['name']) . "'";
		$sql 	.= isset($data['sort_order']) 		? ", sort_order = '" . (int)$data['sort_order'] . "'" 	: "";
		$sql 	.= isset($data['image']) 			? ", image = '" . (int)$data['image'] . "'" 			: ", image = ''";
		$sql 	.= isset($data['noindex']) 			? ", noindex = '" . (int)$data['noindex'] . "'" 		: "";
		$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer` SET" . $sql;
		$this->log($sql,2);
		$query = $this->db->query($sql);

		$data['manufacturer_id'] = $this->db->getLastId();

        $this->seoGenerateManufacturer($data);
        
		if ($this->existTable('manufacturer_description')) {
			$sql = $this->prepareStrQueryManufacturerDescription($data);
			if ($sql) {
				$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET manufacturer_id = '" . $data['manufacturer_id'] . "', language_id = '" . (int)$this->LANG_ID . "'" . $sql;
				$this->log($sql,2);
				$this->db->query($sql);
			}
		}
		
		if (isset($data['cml_id'])) {
			// добавляем связь
			$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_1c` SET 1c_id = '" . $this->db->escape($data['cml_id']) . "', manufacturer_id = '" . $data['manufacturer_id'] . "'";
			$this->log($sql,2);
			$this->db->query($sql);
		}

		$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET manufacturer_id = '" . $data['manufacturer_id'] . "', store_id = '" . (int)$this->STORE_ID . "'";
		$this->log($sql,2);
		$this->db->query($sql);
		
		$this->log("> Производитель '" . $data['name'] . "' добавлен, id: " . $data['manufacturer_id']);
		return $data['manufacturer_id'];
		 
	} // addManufacturer()


	/**
	 * Устанавливаем производителя
	 */
	private function setManufacturer($name, $cml_id='') {

		$this->log("==> setManufacturer()",2);

		$data = array();
		$data['name']			= htmlspecialchars($name);
		$data['description'] 	= 'Производитель ' . $data['name'];
		$data['sort_order']		= 1;
		$data['cml_id']			= $cml_id;		

		if ($this->existField("manufacturer", "noindex")) {
			$data['noindex'] = 1;	// значение по умолчанию
		}

		if ($cml_id) {
			// Поиск (производителя) изготовителя по 1C Ид
			$sql 	= "SELECT mc.manufacturer_id FROM `" . DB_PREFIX . "manufacturer_to_1c` mc LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (mc.manufacturer_id = ms.manufacturer_id) WHERE mc.1c_id = '" . $this->db->escape($cml_id) . "' AND ms.store_id = '" . $this->STORE_ID . "'";
		} else {
			// Поиск по имени
			$sql 	= "SELECT m.manufacturer_id FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (m.manufacturer_id = ms.manufacturer_id) WHERE m.name LIKE '" . $this->db->escape($data['name']) . "' AND ms.store_id = '" . $this->STORE_ID . "'";
		}
		
		// Если есть таблица manufacturer_description тогда нужно условие  
		// AND language_id = '" . $this->LANG_ID . "'

		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$data['manufacturer_id'] = $query->row['manufacturer_id'];
			$this->log("Найден manufacturer_id: " . $data['manufacturer_id'], 2);
		}

		if (!isset($data['manufacturer_id'])) {
			// Создаем
			$data['manufacturer_id'] = $this->addManufacturer($data);
		} else {
			// Обновляем
			$this->updateManufacturer($data, $data['manufacturer_id']);
		}

		$this->log("> Производитель: '" . $data['name'] . "'",1);
		
		return $data['manufacturer_id'];
	} // setManufacturer()


	/**
	 * Обрабатывает единицу измерения
	 * в стадии разработки
	 */
	private function parseUnit($xml) {

		$this->log("==> parseUnit()",2);
		return array();
		
	} // parseUnit()


	/**
	 * Обрабатывает товары
	 */
	private function parseProducts($xml, $classifier) {
		
		$this->log("==> parseProducts()",2);
		
		if (!isset($xml->Товар)) return false;
		
		if ($this->existField("product", "noindex")) {
			$noindex = 1;
		}
		$default_stock_status = $this->config->get('exchange1c_default_stock_status');

		foreach ($xml->Товар as $product){
			if (isset($product->Ид) && isset($product->Наименование) ){
				$data = array();

				$cml_id = explode("#", (string)$product->Ид);
				$data['product_cml_id'] = $cml_id[0];
				$data['feature_cml_id'] = isset($cml_id[1]) ? $cml_id[1] : '';

				$data['mpn']				= $data['product_cml_id'];
				$data['name']				= htmlspecialchars((string)$product->Наименование);
				
				if ($product->Артикул) {
					$data['model']			= htmlspecialchars((string)$product->Артикул);
					$data['sku']			= htmlspecialchars((string)$product->Артикул);
				}
				$data['ean'] = $product->Штрихкод ? ((string)$product->Штрихкод) : ($product->ШтрихКод ? $product->ШтрихКод : '');

				// Значения по-умолчанию
				$data['length_class_id']	= $this->config->get('config_length_class_id');
				$data['weight_class_id']	= $this->config->get('config_weight_class_id');

				$data['status']				= 1;

				if ($this->existField('product','noindex')) {
					$data['noindex']		= 1; // В некоторых версиях
				}

				$this->log("------------------------------",2);
				$this->log("Товар '" . $data['name'] . "'",1);
				
				if ($product->БазоваяЕдиница) {
					$data['unit_id'] = $this->parseUnit($product->БазоваяЕдиница);
				}
				
				if ($product->ПолноеНаименование) {
					$data['full_name']		= htmlspecialchars((string)$product->ПолноеНаименование);
				}

				// описание в текстовом формате, нужна опция если описание в формате HTML
				if ($product->Описание)	{
					$description = (string)$product->Описание;
					$data['description']	= $this->config->get('exchange1c_description_html') == 1 ? $description : nl2br(htmlspecialchars($description));
				}

				// Реквизиты (разные версии CML)
				if ($product->ЗначениеРеквизита) {
					$data = $this->parseRequisite($product, $data);					
				}

				// Реквизиты (разные версии CML)
				if ($product->ЗначенияРеквизитов) {
					$data = $this->parseRequisite($product->ЗначенияРеквизитов, $data);
				}

				// Тип номенклатуры читается из реквизитов
				// Если фильтр по типу номенклатуры заполнен, то загружаем указанные там типы
				$exchange1c_parse_only_types_item = $this->config->get('exchange1c_parse_only_types_item');
				if (isset($data['item_type']) && (!empty($exchange1c_parse_only_types_item))) {
					if (mb_stripos($exchange1c_parse_only_types_item, $data['item_type']) === false) {
					 	continue;
					}
				} 

				// Категории
				$data['product_categories']	= array();
				if ($product->Группы) {
					foreach ($product->Группы->Ид as $category_cml_id) {
						$data['product_categories'][] = $this->getCategoryIdBycml_id((string)$category_cml_id);
					}
				}

				// Читаем изготовителя, добавляем/обновляем его в базу 
				if ($product->Изготовитель) {
					$this->log("Загрузка производителя из тега Изготовитель",2);
					$data['manufacturer_id'] = $this->setManufacturer((string)$product->Изготовитель->Наименование, (string)$product->Изготовитель->Ид);
				}

				// Статус по-умолчанию при отсутствии товара на складе
				// Можно реализовать загрузку из свойств 
				if ($default_stock_status) {
					$data['stock_status_id'] = $default_stock_status;
				}
				
				// Свойства
				if ($product->ЗначенияСвойств && isset($classifier['attributes'])) {
					$data = $this->parseProductAttributes($product->ЗначенияСвойств, $classifier['attributes'], $data);
				}

				//$this->log($data, 2);
				
				// Добавляем или обновляем товар в базе
				$this->setProduct($data);
				
				// Удаляем старые опции и характеристики
				//$this->deleteProductFeatures($product_id);
				//$this->deleteProductOptions($product_id);
				
				// Записываем атрибуты в товар
				if (isset($data['product_attributes'])) {
					$this->setProductAttributes($data);
	                unset($data['product_attributes']);
				}
				
				// Заполнение родительских категорий в товаре
				if ($this->config->get('exchange1c_fill_parent_cats') == 1)
					$this->fillParentsCategories($data);
				
				// картинки
				if ($product->Картинка) {
					if (!$this->parseImages($product->Картинка, $data['product_id'])) return false;
				}
				
				unset($product);
			}
		}
		return true;
	} // parseProducts()


	/**
	 * Разбор каталога
	 */
	private function parseDirectory($xml, $classifier) {

		$this->log("==> parseDirectory() --------------------- КАТАЛОГ ТОВАРОВ ---------------------",2);

		$directory					= array();
		$directory['cml_id']		= (string)$xml->Ид;
		$directory['name']			= (string)$xml->Наименование;
		$directory['classifier_id']	= (string)$xml->ИдКлассификатора;
		if (isset($classifier['id'])) {
			if ($directory['classifier_id'] <> $classifier['id']) {
				$this->log->write("[ERROR] Каталог не соответствует классификатору");
				return 0;
			}
		}
		
		// Если полная выгрузка - требуется очистка для текущего магазина: товаров, остатков и пр.
		if ((string)$xml['СодержитТолькоИзменения'] == 'false')  {
			$this->log("[i] Полная выгрузка с 1С",1);
		}

		// Загрузка товаров
		if (!$this->parseProducts($xml->Товары, $classifier)) {
			unset($xml->Товары);
			return 0;
		}
		
		return 1;
	} // parseDirectory()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ПРЕДЛОЖЕНИЙ ****************************** 
	 */

	/**
	 * Добавляет склад в базу данных 
	 */
	private function addWarehouse($cml_id, $name) {
		$this->log("==> addWarehouse()",2);
		$this->db->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET name = '" . $this->db->escape($name) . "', 1c_id = '" . $this->db->escape($cml_id) . "'");
		return $this->db->getLastId();
	} // addWarehouse()
	

	/**
	 * Ищет склад по cml_id 
	 */
	private function getWarehouseBycml_id($cml_id) {
		$this->log("==> getWarehouseBycml_id()",2);
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'warehouse` WHERE `1c_id` = "' . $this->db->escape($cml_id) . '"');
		if ($query->num_rows) {
			return $query->row['warehouse_id'];
		}
		return 0;
	} // getWarehouseBycml_id()
	

	/**
	 * Возвращает id склада
	 */
	private function setWarehouse($cml_id, $name) {
		$this->log("==> setWarehouse()",2);
		// Поищем склад по 1С Ид
		$warehouse_id = $this->getWarehouseBycml_id($cml_id);
		
		if (!$warehouse_id) {
			$warehouse_id = $this->addWarehouse($cml_id, $name);
		}
		return $warehouse_id;
		
	} // setWarehouse()
	

	/**
	 * Получает общий остаток товара
	 */
	private function getQuantity($product_id) {
		
		$this->log("==> getQuantity()",2);
		$sql = "SELECT quantity FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id;
		$this->log($sql,2);
		$this->db->query($sql);
		
	} // getQuantity()


	/**
	 * Добавляет общий остаток товара
	 */
	private function addQuantity($product_id, $quantity) {
		
		$this->log("==> addQuantity()",2);
		$sql = "SELECT quantity FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		$this->log("Остаток старый " . (float)$query->row['quantity'],2);
		$this->log("Остаток новый " . (float)$quantity,2);
		if ($query->num_rows) {
			$quantity += (float)$query->row['quantity'];
		}
		
		$sql = "UPDATE `" . DB_PREFIX . "product` SET `quantity` = " . (float)$quantity . " WHERE `product_id` = " . (int)$product_id;
		$this->log($sql,2);
		$this->db->query($sql);
		
	} // addQuantity()


	/**
	 * Устанавливает общий остаток товара
	 */
	private function setQuantity($product_id, $quantity) {
		
		$this->log("==> setQuantity()",2);
		$sql = "UPDATE `" . DB_PREFIX . "product` SET `quantity` = " . (float)$quantity . " WHERE `product_id` = " . (int)$product_id;
		$this->log($sql,2);
		$this->db->query($sql);
		
	} // setQuantity()

	
	/**
	 * Получает остаток товара складе
	 */
	private function getProductQuantityWarehouse($product_id, $warehouse_id, $product_feature_id=0) {
		
		$this->log("==> getProductQuantityWarehouse()",2);
		
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . (int)$product_id . " AND `warehouse_id` = " . (int)$warehouse_id . " AND `product_feature_id` = " . (int)$product_feature_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		if ($query->num_rows)
			return $query->row['quantity'];
		else
			return 0;
		
	} // getProductQuantityWarehouse()


	/**
	 * Устанавливает остаток товара складе
	 */
	private function setProductQuantityWarehouse($product_id, $warehouse_id, $quantity, $product_feature_id=0) {
		
		$this->log("==> setProductQuantityWarehouse()",2);
		
		$sql = "DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . (int)$product_id . " AND `warehouse_id` = " . (int)$warehouse_id . " AND `product_feature_id` = " . (int)$product_feature_id;
		$this->log($sql,2);
		$this->db->query($sql);
		
		$sql = "INSERT INTO `" . DB_PREFIX . "product_quantity` SET `product_id` = " . (int)$product_id . ", `warehouse_id` = " . (int)$warehouse_id . ", `product_feature_id` = " . (int)$product_feature_id . ", `quantity` = " . (float)$quantity;
		$this->log($sql,2);
		$this->db->query($sql);
		
	} // setProductQuantityWarehouse()


	/**
	 * Устанавливает остаток товара
	 */
	private function setProductQuantity($data) {
		
		$this->log("==> setProductQuantity()",2);
		
		$product_feature_id = isset($data['product_feature_id']) ? $data['product_feature_id'] : 0;
		
		if (isset($data['quantity_warehouse'])) {
			foreach ($data['quantity_warehouse'] as $warehouse_id => $quantity) {
				$quantity_old = $this->getProductQuantityWarehouse($data['product_id'], $warehouse_id, $product_feature_id);
				if ($quantity <> $quantity_old)
					$this->setProductQuantityWarehouse($data['product_id'], $warehouse_id, $quantity, $product_feature_id);
			}
		} elseif (isset($data['quantity'])) {
			$quantity_old = $this->getProductQuantityWarehouse($data['product_id'], 0, $product_feature_id);
			if ($data['product_id'] <> $quantity_old)
				$this->setProductQuantityWarehouse($data['product_id'], 0, $data['quantity'], $product_feature_id);
		}
		
		if (isset($data['quantity'])) {
			if ($data['product_new']) {
				$this->setQuantity($data['product_id'], $data['quantity']);
			} else {
				$this->addQuantity($data['product_id'], (float)$data['quantity']);
			}
			
		}
			
		
	} // setProductQuantity()


	/**
	 * Загружает список складов
	 */
	private function parseWarehouses($xml) {
		$this->log("==> parseWarehouses()",2);
		$data = array(); 
		foreach ($xml->Склад as $warehouse){
			if (isset($warehouse->Ид) && isset($warehouse->Наименование) ){
				$cml_id = (string)$warehouse->Ид;
				$name = trim((string)$warehouse->Наименование);
				$data[$cml_id] = array(
					'name' => $name
				);
				
				$data[$cml_id]['warehouse_id'] = $this->setWarehouse($cml_id, $name);
			}
		}
		return $data;
	} // parseWarehouses()
	

	/**
	 * Загружает остатки по складам
	 */
	private function parseQuantityWarehouse($xml, $offers_pack, &$data) {

		$this->log("==> parseQuantityWarehouse()",2);
		
		$data_quantity = array();

		// Общий остаток
		$quantity = 0;

		foreach ($xml as $warehouse) {

			$warehouse_cml_id = (string)$warehouse['ИдСклада'];
			
			//$this->log($offers_pack['warehouses'][$warehouse_cml_id],2);

			// Загружался ли такой складв классификаторе
			if (isset($offers_pack['warehouses'][$warehouse_cml_id])) {
				
				$this->log("> Остаток на складе '" . $offers_pack['warehouses'][$warehouse_cml_id]['name'] . "': " . (float)$warehouse['КоличествоНаСкладе'],2);
			}
			
			$quantity += (float)$warehouse['КоличествоНаСкладе'];
			
			$data_quantity[$offers_pack['warehouses'][$warehouse_cml_id]['warehouse_id']] = (float)$warehouse['КоличествоНаСкладе'];
		}
		
		$this->log("> Общий остаток по всем складам: " . $quantity,2);
		
		$data['quantity_warehouse'] = $data_quantity;
		$data['quantity'] = $quantity;
	} // parseQuantityWarehouse()


	/**
	 * Возвращает массив данных валюты по id
	 */
	private function getCurrency($currency_id) {
		$this->log("==> getCurrency()",2);
		$sql = "SELECT * FROM `" . DB_PREFIX . "currency` WHERE currency_id = " . (int)$currency_id;
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->row;
		}
		return array();
	} // getCurrency()

		
	/**
	 * Возвращает id валюты по коду
	 */
	private function getCurrencyId($code) {
		$this->log("==> getCurrencyId()",2);
		$sql = "SELECT `currency_id` FROM `" . DB_PREFIX . "currency` WHERE code = '" . (string)$code . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->row['currency_id'];
		}
		return 0;
	} // getCurrencyId()


	/**
	 * Загружает типы цен и сразу определяет к каким группам сопоставлены они
	 * Если не сопоставлен ни один тип цен, то цены не будут загружаться
	 */
	private function parsePriceType($xml) {
		$this->log("==> parsePriceType()",2);
		$config_price_type = $this->config->get('exchange1c_price_type');
		$data = array();
		
		//$this->log("[i] Таблица цен в настройках:",2);
		//$this->log($config_price_type,2);
		
		if (!is_array($config_price_type)) {
			$this->log("[!] ВНИМАНИЕ! Типы цен не указаны в настройках модуля, цены не будут загружены",1);
			return $data;
		}

		foreach ($config_price_type as $config_type) {
			foreach ($xml->ТипЦены as $price_type)  {
				$currency		= isset($price_type->Валюта) ? (string)$price_type->Валюта : "RUB";
				$cml_id			= (string)$price_type->Ид;
			 	$name			= trim((string)$price_type->Наименование);
			 	$code			= $price_type->Код ? $price_type->Код : '';
				
				if (strtolower($name) == strtolower($config_type['keyword'])) {
					if ($code) {
						$currency_id					= $this->getCurrencyId($code);
					} else {
						$currency_id					= $this->getCurrencyId($currency);
					}
					
					$data[$cml_id] 					= $config_type;
					$data[$cml_id]['currency'] 		= $currency;
	                $data[$cml_id]['currency_id'] 	= $currency_id;
					
					if ($currency_id) {
						$currency_data = $this->getCurrency($currency_id);
						$rate = $currency_data['value'];
						$decimal_place = $currency_data['decimal_place'];
					} else {
						$rate = 1;
						$decimal_place = 2;
					}
					 
					$data[$cml_id]['rate'] 			= $rate;
					$data[$cml_id]['decimal_place'] = $decimal_place;
					
					$this->log('Вид цены: ' . $name,1);
				}
			}
		}
		unset($xml);
		unset($config_price_type);
		return $data; 
	} // parsePriceType()


	/**
	 * Устанавливает цену товара
	 */
	private function setProductPrice($data) {
		$this->log("==> setProductPrice()",2);
		
		$price = 0;

		if (!isset($data['prices'])) 
			return false;
		
		if (!isset($data['product_feature_id'])) {
			// Цена бех характеристики
			
			foreach ($data['prices'] as $price_data) {
				if ($price_data['default']) {
					$price = (float)$price_data['value'];
					// Сразу обновляем основную цену в товаре, не производим сравнение
					//$sql = "UPDATE `" . DB_PREFIX . "product` SET price = " . (float)$price_data['value'] . " WHERE product_id = " . (int)$data['product_id'];
					//$this->log($sql,2);
					//$this->db->query($sql);
					// Цену возвращаем для нахождения минимальной цены характеристики
					//$price_default_group = $price_data['value'];
				} else {
					// Есть ли цена
					$sql = "SELECT `product_discount_id` FROM `" . DB_PREFIX . "product_discount` WHERE product_id = " . (int)$data['product_id'] . " AND customer_group_id = " . (int)$price_data['customer_group_id'];
					$this->log($sql,2);
					$query = $this->db->query($sql);
					if ($query->num_rows) {
						// Обновляем
						$sql = "UPDATE `" . DB_PREFIX . "product_discount` SET quantity = " . (float)$price_data['quantity'] . ", priority = " . (int)$price_data['priority'] . " WHERE product_id = " . (int)$data['product_id'] . " AND customer_group_id = " . (int)$price_data['customer_group_id'];
						$this->log($sql,2);
						$query = $this->db->query($sql);
					} else {
						// Добавляем
						$sql = "INSERT INTO `" . DB_PREFIX . "product_discount` SET product_id = " . (int)$data['product_id'] . ", customer_group_id = " . (int)$price_data['customer_group_id'] . ", quantity = " . (float)$price_data['quantity'] . ", priority = " . $price_data['priority'];
						$this->log($sql,2);
						$query = $this->db->query($sql);
					}
				}
			} // foreach
			
		} // isset

		return $price;
		
	} // setProductPrice()
	
	
	/**
	 * Получает по коду его id
	 */
	private function getUnitId($code) {
		$sql = "SELECT `unit_id` FROM `" . DB_PREFIX . "unit` WHERE eng_name2 = '" . (string)$code . "'";
		$this->log($sql,2);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			return $query->row['unit_id'];
		}
		return 0;
	} // getUnitId()


	/**
	 * Загружает все цены только в одной валюте
	 */
	private function parsePrices($xml, $offers_pack) {

		$this->log("==> parsePrices()",2);
		
		//$this->log($offers_pack,2);
		
		$data = array();
		
		foreach ($xml->Цена as $price) {

			$price_cml_id	= (string)$price->ИдТипаЦены;
			
			if (isset($offers_pack['price_types'][$price_cml_id])) {
				// Найдена цена
				
				$data_price = $offers_pack['price_types'][$price_cml_id];
				$data_price['value']		= (float)$price->ЦенаЗаЕдиницу;
				if ($this->config->get('exchange1c_currency_convert') == 1) {
					if ($data_price['rate'] <> 1 && $data_price['rate'] > 0) {
						$data_price['value']		= round((float)$price->ЦенаЗаЕдиницу / (float)$data_price['rate'], $data_price['decimal_place']);
					}
				}
		 		$data_price['quantity']		= (float)$price->Коэффициент;
		 		$data_price['unit_name']	= (string)$price->Единица;
		 		$data_price['name']			= (string)$price->Представление;
		 		//$data_price['currency']		= (string)$price->Валюта;

		 		$data_price['unit_id']		= $this->getUnitId($data_price['unit_name']); 
		 		
		 		if ($data_price['customer_group_id'] == $this->config->get('config_customer_group_id')) {
		 			$data_price['default'] 	= true;
		 		} else {
		 			$data_price['default'] 	= false;
		 		}
		 		
		 		$data[$price_cml_id] = $data_price;
		 		//$this->log($data,2);
			} else {
				
				$this->log('[i] Не найдена цена, Ид: ' . $price_cml_id,1);
			}
 		}

		return $data;
					
 	} // parsePrices()


	/**
	 * ХАРАКТЕРИСТИКИ
	 */


	/**
	 * Добавляет опциию по названию
	 */
	private function addOption($name, $type='select') {
		
		$this->log("==> addOption()",2);
		$sql = "INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $type . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

		$option_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_description` SET option_id = '" . $option_id . "', language_id = '" . $this->LANG_ID . "', name = '" . $this->db->escape($name) . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		return $option_id;  
	
	} // addOption()


	/**
	 * Получает все значения опции товара
	 */
	private function getProductOptionValues($product_id, $product_option_id) {
		
		$this->log("==> getProductOptionValues()",2);
		// Проверяем значение опциии в товаре
		$sql = "SELECT product_option_value_id, quantity, price, price_prefix FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '" . $product_id . "' AND product_option_id = '" . $product_option_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
        	return $query->rows;
       	}
       	
       	return array();
	} // getProductOptionValues()


	/**
	 * Находит или добавляет значение опции в товар
	 */
	private function getProductPrice($product_id) {

		$sql = "SELECT price FROM `" . DB_PREFIX . "product` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
        	return $query->row['price'];
       	}
       	return 0;
	}


	/**
	 * Добавляет или находит опцию в товаре и возвращает ID
     * $data['product_id'], $option_id, $option_name
	 */
	private function setProductOption($product_id, $option_id, $option_name) {
		
		$this->log("==> setProductOption()",2);
		
		$options = array();
		
		// Ищем опцию
		$product_option_id = $this->getProductOption($product_id, $option_id, $option_name);
	        
		if (!$product_option_id) {
			$product_option_id = $this->addProductOption($product_id, $option_id, $option_name);
		}

		return $product_option_id;
		
	} // setProductOption()


	/**
	 * Находит характеристику товара
	 */
	private function getProductFeature($feature_cml_id) {
		
		$this->log("==> getProductFeature()",2);
		// Ищем характеристику по Ид
		$sql = "SELECT product_feature_id FROM `" . DB_PREFIX . "product_feature` WHERE 1c_id = '" . $feature_cml_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			return $query->row['product_feature_id'];
		}
		
		return 0;
	} // getProductFeature()
	

	/**
	 * Находит и обновляет или добавляет характеристику товара
	 */
	private function setProductFeature($data) {
		
		$this->log("==> setProductFeature()",2);
        
		// Ищем характеристику по Ид
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_feature` WHERE 1c_id = '" . $data['feature_cml_id'] . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		//$this->log($query,2);
		
		if ($query->num_rows) {
			$product_feature_id = $query->row['product_feature_id'];

			// Обновляем
			$sql = array();
			if ($query->row['product_id'] <> $data['product_id'])
				$sql[] = "product_id = '" . $data['product_id'] . "'";
			if ($query->row['name'] <> $data['name'])
				$sql[] = "name = '" . $data['name'] . "'";
			if (isset($data['ean'])) {
				if ($query->row['ean'] <> $data['ean'])
					$sql[] = "ean = '" . $data['ean'] . "'";
			}
			if (isset($data['sku'])) {
				if ($query->row['sku'] <> $data['sku'])
					$sql[] = "sku = '" . $data['sku'] . "'";
			}
			$sql = implode(",", $sql);
			if ($sql) {
				$sql = "UPDATE `" . DB_PREFIX . "product_feature` SET " . $sql . " WHERE 1c_id = '" . $this->db->escape($data['feature_cml_id']) . "'";
		 		$this->log($sql,2);
			}
			return $product_feature_id;  
       	}
       	
       	// Добавим характеристику
		$sql = "";
		if (isset($data['ean']))
			$sql .= ", ean = '" . $data['ean'] . "'";
		if (isset($data['sku']))
			$sql .= ", sku = '" . $data['sku'] . "'";
		if (isset($data['feature_name']))
			$sql .= ", name = '" . $this->db->escape($data['feature_name']) . "'";
 		$sql = "INSERT INTO `" . DB_PREFIX . "product_feature` SET product_id = '" . $data['product_id'] . "'" . $sql . ", 1c_id = '" . $data['feature_cml_id'] . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		$product_feature_id = $this->db->getLastId();

		return $product_feature_id;
		
	} // setProductFeature()


	/**
	 * Ищет, проверяет, добавляет значение характеристики товара
	 */
	private function setProductFeatureValue($product_feature_id, $product_option_id, $product_option_value_id, $product_id) {
		
		$this->log("==> setProductFeatureValue()",2);
		// Поищем такое значение
		$sql = "SELECT product_option_id FROM `" . DB_PREFIX . "product_feature_value` WHERE product_feature_id = '" . $product_feature_id . "' AND product_option_value_id = '" . $product_option_value_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			return 1;
		}
		
       	// Добавим значение
		$sql = "INSERT INTO `" . DB_PREFIX . "product_feature_value` SET product_feature_id = '" . $product_feature_id . "', product_id = '" . $product_id . "', product_option_id = '" . $product_option_id . "', product_option_value_id = '" . $product_option_value_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		return 2;
       	
	} // setProductFeatureValue()


	/**
	 * Удаление характеристик и опций у товара 
	 */
	private function deleteProductFeatures($product_id) {
		
		$this->log("==> deleteFeatures()",2);
		
		// Удалим старые характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		// Удалим старые значения характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);

	} // deleteProductFeatures()


	/**
	 * Удаление характеристик и опций у товара 
	 */
	private function deleteProductOptions($product_id) {
		
		$this->log("==> deleteFeatures()",2);
		
		// Удалим старые опции
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
		// Удалим старые значения опции
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql,2);
		$query = $this->db->query($sql);
		
	} // deleteProductOptions()


	/**
	 * Разбор характеристик в товаре для объединенных опций 
	 */
	private function parseProductFeatureRelated($xml, &$data) {
		
		$this->log("==> parseProductFeatureRelated()",2);
		$this->log("data-parseProductFeatureRelated-begin:",2);
		$this->log($data,2);

		foreach ($xml->ХарактеристикаТовара as $productFeature) {
			
		}

		$this->log("data-parseProductFeatureRelated-end:",2);
		$this->log($data,2);

	} // parseProductFeatureRelated()
	
	
	/**
	 * Разбор характеристик в товаре для объединенных опций 
	 */
	private function parseProductFeatureCombine($xml, &$data) {
		
		$this->log("==> parseFeatureCombine()",2);
//		$this->log("data-parseProductFeatureCombine-begin:",2);
//		$this->log($data,2);
		
		// Название опции 
		$feature_name	= array();
		$feature_value	= array();

		// Обрабатываем все доп. свойства характеристики и записываем в массив, формируем название для характеристики, типа: "Наименование: Значение, Наименование: Значение, ..."
		foreach ($xml->ХарактеристикаТовара as $productFeature) {
			
			// Получаем название доп. свойства и значение его, отсекаем лишние пробелы с начала и конца
			$name = trim((string)$productFeature->Наименование);
			$value = trim((string)$productFeature->Значение);
			$this->log("> Свойство характеристики: наименование = " . $name . ", значение = " . $value,2);

			if (!$value)
				continue;

			// Версии 2.05
			$feature_name[]		= $name;
			$feature_value[] 	= $value;
		}
		
		if (sizeof($feature_name)) {
//			$feature_name 	= implode(', ', array_reverse($feature_name));
//			$feature_value 	= implode(', ', array_reverse($feature_value));
			$feature_name 	= implode(', ', $feature_name);
			$feature_value 	= implode(', ', $feature_value);
		} else {
			return 0;
		}
		
		$data['feature_name']	= $feature_name;
		$data['feature_value']	= $feature_value;
		
		// Установим характеристику
		$data['product_feature_id'] = $this->setProductFeature($data);
		
		if ($this->config->get('exchange1c_product_options_name') == 'full_name')
			$option_name = $data['feature_name'];
		else
			$option_name = "Характеристика";
			
		$data['option_id'] = $this->setOption($option_name, $this->config->get('exchange1c_product_options_type'));
		
		// Значение опции в спавочнике
		$data['option_value_id'] = $this->setOptionValue($data['option_id'], $feature_value);
		
		// Опция в товаре
		$data['product_option_id'] = $this->setProductOption($data['product_id'], $data['option_id'], $option_name);
		
		// Добавляем значения, цену и остаток
		$data['product_option_value_id'] = $this->setProductOptionValue($data);
		
//		$this->log("data-parseProductFeatureCombine-end:",2);
//		$this->log($data,2);
		
	} // parseProductFeatureCombine()
	

	/**
	 * Разбор характеристик из offers
	 * Возвращает массив полученных данных
	 */
	private function parseProductFeature($xml, &$data) {
		
		$this->log("==> parseProductFeatures()",2);
		if (!$xml)
			return false;

		if ($this->config->get('exchange1c_product_options_mode') == 'combine') {
			$this->parseProductFeatureCombine($xml, $data);
		} elseif ($this->config->get('exchange1c_product_options_mode') == 'related') {
			$this->parseProductFeatureRelated($xml, $data);
		} else {
			$this->log($this->config->get('exchange1c_product_options_mode'),2);
			$this->log("[!] Не выбран режим загрузки характеристик",2);
		}
	} // parseProductFeature()


	/**
	 * Получение Производителя
	 */
	private function getManufacturer(&$data) {
		
		$this->log("==> getManufacturer()",2);
		if (!$data['manufacturer_id']) return 0;
		
		$query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = " . $data['manufacturer_id']);
		$data['manufacturer'] = isset($query->row['name']) ? $query->row['name'] : "";
		
	} // getManufacturer()


	/**
	 * Получение product_id по Ид
	 */
	private function getProductIdByCML($product_cml_id) {
		
		$this->log("==> getProductIdByCML()",2);
		// Определим product_id
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product_to_1c` WHERE 1c_id = '" . $this->db->escape($product_cml_id) . "'");
		return isset($query->row['product_id']) ? $query->row['product_id'] : 0;
		
	} // getProductIdByCML()
	

	/**
	 * Получение полей товара sku,brand,desc,cats,cat_id
	 */
	private function getProduct(&$data) {

		if (!$data['product_cml_id']) {
			$this->log("[ERROR] Не определен Ид товара из пакета предложений");
			return false;
		}
		
		// Определим product_id
		$data['product_id'] = $this->getProductIdByCML($data['product_cml_id']);
		
		if (!$data['product_id']) {
			$this->log("[ERROR] Не найден product_id товара в таблице product_to_1c по Ид товара");
			return false;			
		}
		
		$query = $this->db->query("SELECT sku, manufacturer_id FROM `" . DB_PREFIX . "product` WHERE product_id = " . $data['product_id']);
		
		// Получим sku если он не задан
		$data['sku'] = isset($query->row['sku']) ? $query->row['sku'] : '';

		// Получим наименование производителя
		if (isset($query->row['manufacturer_id'])) {
			$data['manufacturer_id'] = $query->row['manufacturer_id'];
			$this->getManufacturer($data);
		}
		
		$data['categories'] = $this->getProductCategories($data['product_id']);
		
		// Описание товара
		$query_desc = $this->db->query("SELECT description FROM `" . DB_PREFIX . "product_description` WHERE product_id = " . $data['product_id'] . " AND language_id = " . $this->LANG_ID);
		if (isset($query_desc->row['description'])) 		$data['description'] 		= $query_desc->row['description'];
		
		// id категории товара
		if ($this->existField('product_to_category', 'main_category')) {
			$main_category = " AND main_category = 1";
		} else {
			$main_category = "";
		}
		$query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = " . $data['product_id'] . $main_category . " LIMIT 1");
		if ($query->num_rows) {
			$data['category_id'] = $query->row['category_id'];
		}
		
	} // getProduct()


	/**
	 * Разбор предложений
	 */
	private function parseOffers($xml, $offers_pack) {

		$this->log("==> parseOffers()",2);
		if (!$xml->Предложение) return 1;
		$last_product_id = 0;
		
		foreach ($xml->Предложение as $offer) {
			
			$data = array();
			
			$cml_id 				= explode("#", (string)$offer->Ид);
			$data['product_cml_id']	= $cml_id[0];

			// Если есть характеристики, то это будет названием характеристики 
			$data['name']			= ($offer->Наименование) ? (string)$offer->Наименование : '';

			// Штрихкод
			if (($offer->Штрихкод))
				$data['ean'] 		=  (string)$offer->Штрихкод;
				
			$this->getProduct($data);
			
			//$data['product_id']		= $this->getProductIdByCML($data['product_cml_id']); 		
			if (!$data['product_id'])  {
				continue;
			}
			if ($last_product_id <> $data['product_id'])
				$data['product_new'] = true;
			else
				$data['product_new'] = false;
			
			$this->log("------------------------------",2);
			$this->log("Товар '" . $data['name'] . "'",1);
			
			if ($offer->Цены) {
				$data['prices'] = $this->parsePrices($offer->Цены, $offers_pack);
			}

            $data['quantity'] = 0;
            
			if ($offer->Количество) {
				$data['quantity'] = (int)$offer->Количество;
			}

			// Количество по складам и общее количество
			if ($offer->Склад) {
				$this->parseQuantityWarehouse($offer->Склад, $offers_pack, $data);
			} 

			if (isset($cml_id[1])) {

				$data['feature_cml_id'] = $cml_id[1];
				$this->log("> Характеристика " . $data['feature_cml_id']);
				
				if (isset($offer->ХарактеристикиТовара)) {
					// Возвращает name и value и cml_id
					$data['feature'] = $this->parseProductFeature($offer->ХарактеристикиТовара, $data);
				} else {
					$data['price'] = $this->setProductPrice($data);
				}

				unset($data['name']);
				
			} else {
				$data['price'] = $this->setProductPrice($data);
			}
			
			$this->log($data, 2);
			
			if ($data['quantity']) {
				// Устанавливает общий остаток в товаре
				$this->setProductQuantity($data);
			}

			if ($this->config->get('exchange1c_product_status_disable_if_quantity_zero') == 1) {
				if ($data['quantity'] <= 0) {
					$data['status'] = 0;
					$this->log("> Товар отключен, так как остаток нулевой",1);
				}
			}
			
			$this->updateProduct($data);
			
			$last_product_id = $data['product_id'];
			//$this->log("data-end-offers:",2);
			//$this->log($data,2);
			
		} // foreach()
		
		return 1;
	} // parseOffers()


	/**
	 * Загружает пакет предложений
	 */
	private function parseOffersPack($xml) {
		$this->log("==> parseOffersPack() --------------------- ПАКЕТ ПРЕДЛОЖЕНИЙ ---------------------",2);

		$offers_pack = array();
		$offers_pack['offers_pack_id']	= (string)$xml->Ид;
		$offers_pack['name']			= (string)$xml->Наименование;
		$offers_pack['directory_id']	= (string)$xml->ИдКаталога;
		$offers_pack['classifier_id']	= (string)$xml->ИдКлассификатора;
		
		// Сопоставленные типы цен
		if ($xml->ТипыЦен) {
			$offers_pack['price_types'] = $this->parsePriceType($xml->ТипыЦен);
            //$this->log($offers_pack['price_types'], 'Загруженные типы цен');
			unset($xml->ТипыЦен);
		}
		
		// Загрузка складов
		if ($xml->Склады) {
			$offers_pack['warehouses'] = $this->parseWarehouses($xml->Склады);
			unset($xml->Склады);
		}
		// Загружаем предложения
		if (!$this->parseOffers($xml->Предложения, $offers_pack)) {
			return 0;
		}
		unset($xml->Предложения);
		
		return 1;
	 } // parseOffersPack()
	 
	 
	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ЗАКАЗОВ ****************************** 
	 */

	/**
	 * Меняет статусы заказов 
	 *
	 * @param	int		exchange_status	
	 * @return	bool
	 */
	private function sendMail($subject, $order_info) {
	
		$this->log("==> sendMail()",2);
		$message = 'Изменился статус Вашего заказа!';

		$mail = new Mail();
		$mail->protocol = $this->config->get('config_mail_protocol');
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

		$mail->setTo($order_info['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText($message);
		$mail->send();
		$this->log($mail,2);
	
	} // sendMail()
	
	/**
	 * Меняет статусы заказов 
	 *
	 * @param	int		exchange_status	
	 * @return	bool
	 */
	public function queryOrdersStatus($params) {
		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_status_id = " . $params['exchange_status'] . "");
			$this->log("> Поиск заказов со статусом id: " . $params['exchange_status'],2);
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE date_added >= '" . $params['from_date'] . "'");
			$this->log("> Поиск заказов с даты: " . $params['from_date'],2);
		}
		if ($query->num_rows) {
			foreach ($query->rows as $order_data) {
				
				//$this->log('order_data:',2);
				//$this->log($order_data,2);
				
				if ($order_data['order_status_id'] == $params['new_status']) {
					$this->log("> Cтатус заказа #" . $order_data['order_id'] . " не менялся.",2);
					//continue;
				}
					
				// Меняем статус
				$sql = "UPDATE " . DB_PREFIX . "order SET order_status_id = '" . $params['new_status'] . "' WHERE order_id = '" . $order_data['order_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Изменен статус заказа #" . $order_data['order_id'],1);
				// Добавляем историю в заказ
				$sql = "INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . $order_data['order_id'] . "', comment = 'Ваш заказ обрабатывается', order_status_id = '" . $params['new_status'] . "', notify = '0', date_added = NOW()";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Добавлена история в заказ #" . $order_data['order_id'],2);
				
				// Уведомление
				if ($params['notify']) {
					$this->log("> Отправка уведомления на почту: " . $order_data['email'],2);
					$this->sendMail('Статус Вашего заказа изменен', $order_data);
				}
			}
		}
		return 1;
	}


	/**
	 * Получает CML Ид характеристики по выбранным опциям 
	 *
	 */
	private function getFeatureCML($order_id, $product_id) {
		
		$order_options = $this->model_sale_order->getOrderOptions($order_id, $product_id);
		$options = array();
		foreach ($order_options as $order_option) {
			$options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
		}

		$feature_cml_id = "";
		$features = array();
		foreach ($order_options as $order_option) {
			$sql = "SELECT product_feature_id FROM `" . DB_PREFIX . "product_option_value` WHERE product_option_value_id = " . (int)$order_option['product_option_value_id'];
			$this->log($sql,2);
			$query = $this->db->query($sql);
			
			$product_feature_id = 0;
			if ($query->num_rows) {
				$product_feature_id = $query->row['product_feature_id'];
			}
			
			if (!isset($features[$product_feature_id]) && $product_feature_id > 0) {
					
				// Получаем Ид
				$sql = "SELECT 1c_id FROM `" . DB_PREFIX . "product_feature` WHERE product_feature_id = " . (int)$product_feature_id;
				$this->log($sql,2);
				$query = $this->db->query($sql);
				if ($query->num_rows) {
					$feature_cml_id = $query->row['1c_id'];
				}

				$features[$product_feature_id] = $feature_cml_id;
					
			}
		}
		
		// Если несколько характеристик, то это ошибка, сообщаем и возвращаем первую
		if (sizeof($features) > 1) {
			$this->log("[ERROR] По опциям товара найдено несколько характеристик!");
			$this->log($features);
		}
		
		return $feature_cml_id;
		
	} // getFeatureCML
	

	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ ****************************** 
	 */
	public function queryOrders($params) {
		$this->log("==== Выгрузка заказов ====",2);
		
		$version = $this->config->get('exchange1c_CMS_version');
		if (version_compare($version, '2.0.3.1', '>')) {
			$this->log("customer/customer_group",2);
			$this->load->model('customer/customer_group');
		} else {
			$this->log("sale/customer_group",2);
			$this->load->model('sale/customer_group');
		}
			
		$this->load->model('sale/order');
		
		if ($params['exchange_status'] != 0) {
			// Если указано с каким статусом выгружать заказы
			$sql = "SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = '" . $params['exchange_status'] . "'";
			$this->log($sql,2);
			$query = $this->db->query($sql);
		} else {
			// Иначе выгружаем заказы с последей выгрузки, если не определа то все
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}

		$document = array();
		$document_counter = 0;

		if ($query->num_rows) {
			foreach ($query->rows as $orders_data) {
				$order = $this->model_sale_order->getOrder($orders_data['order_id']);
				$this->log("> Выгружается заказ #" . $order['order_id'],1);
				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));
				if (version_compare($version, '2.0.3.1', '>')) {
					$customer_group = $this->model_customer_customer_group->getCustomerGroup($order['customer_group_id']);
				} else {
					$customer_group = $this->model_sale_customer_group->getCustomerGroup($order['customer_group_id']);
				}
				$document['Документ' . $document_counter] = array(
					 'Ид'          => $order['order_id']
					,'Номер'       => $order['order_id']
					,'Дата'        => $date
					,'Время'       => $time
					,'Валюта'      => $params['currency']
					,'Курс'        => 1
					,'ХозОперация' => 'Заказ товара'
					,'Роль'        => 'Продавец'
					,'Сумма'       => $order['total']
					,'Комментарий' => $order['comment']
					,'Соглашение'  => $customer_group['name']
				);
				
				// Разбирает ФИО
				$user = explode(",", $order['payment_firstname']);
				array_unshift($user, $order['payment_lastname']);
				$username = implode(" ", $user);

				// Контрагент
				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
					 'Ид'                 => $order['customer_id'] . '#' . $order['email']
					,'Наименование'		    => $username
					//+++ Для организаций
					//,'ИНН'		        => $order['payment_company_id']
					//,'КПП'				=>''
					//,'ОфициальноеНаименование' => $order['payment_company']		// Если заполнено, значит ЮрЛицо
					//,'ПолноеНаименование'	=> $order['payment_company']			// Полное наименование организации 
					//---
					,'Роль'               => 'Покупатель'
					,'ПолноеНаименование' => $username
					,'Фамилия'            => isset($user[0]) ? $user[0] : ''
					,'Имя'			      => isset($user[1]) ? $user[1] : ''
					,'Отчество'		      => isset($user[2]) ? $user[2] : ''
					,'АдресРегистрации' => array(
						'Представление'	=> $order['shipping_address_1'].', '.$order['shipping_city'].', '.$order['shipping_postcode'].', '.$order['shipping_country']
					)
					,'Контакты' => array(
						'Контакт1' => array(
							'Тип' => 'ТелефонРабочий'
							,'Значение'	=> $order['telephone']
						)
						,'Контакт2'	=> array(
							 'Тип' => 'Почта'
							,'Значение'	=> $order['email']
						)
					)
				);
				
				// Реквизиты документа передаваемые в 1С
				$document['Документ' . $document_counter]['ЗначенияРеквизитов'] = array(
					'ЗначениеРеквизита0' => array(
						'Наименование' => 'Дата отгрузки',
						'Значение' => $date
					)
//					,'ЗначениеРеквизита1' => array(
//						'Наименование' => 'Организация',
//						'Значение' => 'Тесла-Чита'
//					)
//					,'ЗначениеРеквизита2' => array(
//						'Наименование' => 'Склад',
//						'Значение' => 'Фрунзе 3'
//					)
//					,'ЗначениеРеквизита3' => array(
//						'Наименование' => 'ВидЦен',
//						'Значение' => 'Розница'
//					)
//					,'ЗначениеРеквизита4' => array(
//						'Наименование' => 'Подразделение',
//						'Значение' => 'Интернет-магазин'
//					)
//					,'ЗначениеРеквизита5' => array(
//						'Наименование' => 'Сумма включает НДС',
//						'Значение' => true
//					)
//					,'ЗначениеРеквизита6' => array(
//						'Наименование' => 'Статус заказа',
//						'Значение' => $order_status_description[$language_id]['name']
//					)
//					
				);

				// Товары
				$products = $this->model_sale_order->getOrderProducts($orders_data['order_id']);

				$product_counter = 0;
				foreach ($products as $product) {
					$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
						 'Ид'             => $this->getcml_idByProductId($product['product_id'])
						,'Наименование'   => $product['name']
						,'ЦенаЗаЕдиницу'  => $product['price']
						,'Количество'     => $product['quantity']
						,'Сумма'          => $product['total']
						,'Резерв' 		  	=> $product['quantity']
						,'БазоваяЕдиница' => array('Код' => '796','НаименованиеПолное' => 'Штука')
						,'Скидки'         => array('Скидка' => array(
							'УчтеноВСумме' => 'false'
							,'Сумма' => 0
							)
						)
						
						,'ЗначенияРеквизитов' => array(
							'ЗначениеРеквизита' => array(
								'Наименование' => 'ТипНоменклатуры'
								,'Значение' => 'Товар'
							)
						)
					);
					
					// Характеристики
					$feature_cml_id = $this->getFeatureCML($orders_data['order_id'], $product['order_product_id']);
					if ($feature_cml_id) {
						$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter]['Ид'] .= "#" . $feature_cml_id;
					}

					$product_counter++;
				}

				$document_counter++;

			} // foreach ($query->rows as $orders_data)
		}

		// Формируем заголовок
		$root = '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d', time()) . '" />';
		$xml = $this->array_to_xml($document, new SimpleXMLElement($root));
		
		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (is_writable($cache)) {
			// запись заказа в файл
			$f_order = fopen(DIR_CACHE . 'exchange1c/orders.xml', 'w');
			fwrite($f_order, $xml->asXML());
			fclose($f_order);
		} else {
			$this->log("Папка " . $cache . " не доступна для записи, файл заказов не может быть сохранен!",1);
		}

		return $xml->asXML();
	}
	
	
	/**
	 * Адрес
	 */
	private function parseAddress($xml) {
		if (!$xml) return "";
		return (string)$xml->Представление;
	} // parseAddress()


	/**
	 * Банк
	 */
	private function parseBank($xml) {
		if (!$xml) return "";
		return array(
			'correspondent_account'	=> (string)$xml->СчетКорреспондентский,
			'name'					=> (string)$xml->Наименование,
			'bic'					=> (string)$xml->БИК,
			'address'				=> $this->parseAddress($xml->Адрес)
		);
	} // parseBank()


	/**
	 * Расчетные счета
	 */
	private function parseAccount($xml) {
		if (!$xml) return "";
		$data = array();
		foreach ($xml->РасчетныйСчет as $object) {
			$data[]	= array(
				'number'	=> $object->Номерсчета,
				'bank'		=> $this->parseBank($object->Банк)
			);
		}
		return $data;
	} // parseAccount()


	/**
	 * Владелец
	 */
	private function parseOwner($xml) {
		if (!$xml) return "";
		return array(
			'id'		=> (string)$xml->Ид,
			'name'		=> (string)$xml->Наименование,
			'fullname'	=> (string)$xml->ПолноеНаименование,
			'inn'		=> (string)$xml->ИНН,
			'account'	=> $this->parseAccount($xml->РасчетныеСчета)
		);
	} // parseOwner()


	/**
	 * Разбор классификатора
	 */
	private function parseClassifier($xml) {
		$this->log("==> parseClassifier()",2);
		
		$data = array();
		$data['id']				= (string)$xml->Ид;
		$data['name']			= (string)$xml->Наименование;
		$this->setStore($data['name']);

		// Организация
		if ($xml->Владелец) {
			$this->log("--->>> Загрузка владельца",2);
			$data['owner']			= $this->parseOwner($xml->Владелец);
			unset($xml->Владелец);
			$this->log("<<<--- Владелец загружен",2);
		}

		if ($xml->Группы) {
			$this->log("--->>> Загрузка категорий",2);
			$this->parseCategories($xml->Группы);
			unset($xml->Группы);
			$this->log("<<<--- Категории загружены",2);
		}

		if ($xml->Свойства) {
			$this->log("--->>> Загрузка свойств",2);
			$data['attributes']		= $this->parseAttributes($xml->Свойства);
			unset($xml->Свойства);
			$this->log("<<<--- Свойства загружены",2);
		}

		return $data;
	} // parseClassifier()


	/**
	 * Разбор документа
	 */
	private function parseDocument($xml) {
		$id			= (string)$xml->Ид;
		$header		= $this->parseDocumentHeader($xml);
		$customer	= $this->parseDocumentCustomer($xml->Контрагенты);
		$products	= $this->parseDocumentProducts($xml->Товары);
		$details	= $this->parseDocumentDetails($xml->ЗначенияРеквизитов);
		unset($xml);

		return true;
	} // parseDocument()


	/**
	 * Импорт файла 
	 */
	public function importFile($importFile) {

		// Функция будет сама определять что за файл загружается
		$this->log("==== Начата загрузка данных ====");
		$this->log("[i] Всего доступно памяти: " . sprintf("%.3f", memory_get_peak_usage() / 1024 / 1024) . " Mb",2);
		
		$this->log(">>> Начинается чтение XML",2);
		// Конвертируем XML в массив
		$xml = simplexml_load_file($importFile);
		$this->log("<<< XML прочитан",2);

		// Файл стандарта Commerce ML
		if (!$this->checkCML($xml)) {
			return 0;
		}

		// IMPORT.XML, OFFERS.XML
		if ($xml->Классификатор) {
			$this->log(">>> Загружается классификатор",2);
			$classifier = $this->parseClassifier($xml->Классификатор);
			unset($xml->Классификатор);
			$this->log("<<< Классификатор загружен",2);
		} else {
			$classifier = array();
		}
		
		if ($xml->Каталог) {
			
			$this->log(">>> Загрузка каталога",1);
			if (!isset($classifier)) {
				$this->log("[i] Классификатор не загружен! Все товары из файлов будут загружены в магазин по умолчанию!");
			}
			
			if (!$this->parseDirectory($xml->Каталог, $classifier)) {
				return 0;
			}
			unset($xml->Каталог);
			$this->log("<<< Каталог загружен",1);
		}
		
		// OFFERS.XML
		if ($xml->ПакетПредложений) {
			$this->log(">>> Загрузка пакета предложений");
			
			// Пакет предложений
			if (!$this->parseOffersPack($xml->ПакетПредложений)) {
				return 0;
			}
			unset($xml->ПакетПредложений);
			$this->log("<<< Пакет предложений загружен");
			
			// После загрузки пакета предложений формируем SEO
			//$this->seoGenerate();

		}
		
		// ORDERS.XML
		if ($xml->Документ) {
			$this->log(">>> Загрузка документов");
			if (!isset($classifier)) {
				$this->log("[ERROR] Не загружен классификатор!");
				return 0;
			}
			
			// Документ (заказ)
			foreach ($xml->Документ as $doc) {
				if (!$this->parseDocument($doc)) {
					return 0;
				}
			}
			$this->log("<<< Документы загружены");
		}
		else {
			$this->log("[i] Не обработанные данные XML",2);
			$this->log($xml,2);
		}
		$this->log("==== Окончена загрузка данных ====");
		return 1;
	}


	/**
	 * Устанавливает обновления
	 */
	public function update($settings) {
		$version = $settings['exchange1c_version'];
		$update = true;
		
		$version = $this->version();
		
		$message = "Модуль в обновлении не нуждается";

		//if ($version == '1.6.2.b8') {
		//	$update = $this->update162b9();
		//}

		if ($update) {
			$this->setEvents();
			$settings['exchange1c_version'] = $version;
			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$message = "Модуль успешно обновлен до версии " . $version;
		}		
		return $message;
		
	} // update()


	/**
	 * Устанавливает обновления
	 */
	private function update162b9() {
		
		return false;
	}

	
}

