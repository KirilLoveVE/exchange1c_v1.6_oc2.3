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
		return "1.6.2.b5";
	} // version()
	

	/**
	 * Пишет в файл журнала если включена настройка
	 *
	 * @param	string,array()	Сообщение или объект
	 */
	private function log($message) {
		if ($this->config->get('exchange1c_full_log'))
			$memory = sprintf("%.3f", memory_get_usage() / 1024 / 1024);
			if (is_array($message) || is_object($message)) {
				$this->log->write($memory);
				$this->log->write(print_r($message,true));
			} else {
				list ($di) = debug_backtrace();
				$line = sprintf("%04s",$di["line"]);
				if (isset($memory)) {
					$this->log->write( $memory . " Mb | " . $line . " | " . $message);
				} else {
					$this->log->write($line . " | " . $message);
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
		if ($xml['ВерсияСхемы']) {
			$this->VERSION_XML = (string)$xml['ВерсияСхемы'];
			$this->log("Версия XML: " . $this->VERSION_XML);
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
		// Удаляем товары
		$result = "";

		$this->log("Очистка таблиц товаров...");
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

		$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "relatedoptions%'");
		if ($query->num_rows) {
			$this->log("Очистка таблиц связанных опций...");
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
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
		$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_to_1c`');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%product_id=%"');
		$result .=  "Очищены таблицы товаров, опций\n";

		// Очищает таблицы категорий
		$this->log("Очистка таблиц категорий...");
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
		$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		$result .=  "Очищены таблицы категорий\n";

		// Очищает таблицы от всех производителей
		$this->log("Очистка таблиц производителей...");
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
		$this->log("Очистка таблиц атрибутов...");
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_description');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_to_1c');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group_description');
		$result .=  "Очищены таблицы атрибутов\n";

		// Выставляем кол-во товаров в 0
		$this->log("Очистка остатков...");
		$this->db->query('UPDATE ' . DB_PREFIX . 'product ' . 'SET quantity = 0');
		$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'warehouse');
		$result .=  "Обнулены все остатки\n";

		return $result;
	} // cleanDB()


	/**
	 * Удаляет связи XML_ID -> id
	 */
	public function deleteLinkProduct($product_id) {
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_to_1c` WHERE product_id = '" . $product_id . "'");
			$this->log("Удалена связь товара XML_ID с id: " . $product_id);
		}
		$this->load->model('catalog/product');
		$product = $this->model_catalog_product->getProduct($product_id);
		if ($product['image']) {
			// Удаляем только в папке import_files
			if (substr($product['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $product['image']);
				$this->log("Удален файл: " . $product['image']);
			}
		}
		$productImages = $this->model_catalog_product->getProductImages($product_id);
		foreach ($productImages as $image) {
			// Удаляем только в папке import_files
			if (substr($image['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $image['image']);
				$this->log("Удален файл: " . $image['image']);
			}
		}
	} // deleteLinkProduct()
	

	/**
	 * Удаляет связи XML_ID -> id
	 */
	public function deleteLinkCategory($category_id) {
		// Удаляем линк
		if ($category_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "category_to_1c` WHERE category_id = '" . $category_id . "'");
			$this->log("Удалена связь категории XML_ID с id: " . $category_id);
		}
	} //  deleteLinkCategory()
	

	/**
	 * Удаляет связи XML_ID -> id
	 */
	public function deleteLinkManufacturer($manufacturer_id) {
		// Удаляем линк
		if ($manufacturer_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "manufacturer_to_1c` WHERE manufacturer_id = '" . $manufacturer_id . "'");
			$this->log("Удалена связь производителя XML_ID с id: " . $manufacturer_id);
		}
	} //  deleteLinkManufacturer()


	/**
	 * Удаляет связи XML_ID -> id
	 */
	public function deleteLinkOption($option_id) {
		// Удаляем линк
		if ($option_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "option_to_1c` WHERE option_id = '" . $option_id . "'");
			$this->log("Удалена связь опции XML_ID с id: " . $option_id);
		}
	} //  deleteLinkOption()


	/**
	 * Создает события
	 */
	public function setEvents() {
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
		$query = $this->db->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $lang . "'");
		$this->LANG_ID = $query->row['language_id'];
		return $query->row['language_id'];
	} // getLanguageId()


	/**
	 * Проверяет таблицы модуля
	 */
	public function checkDB() {
		$tables_module = array("product_to_1c","product_quantity","category_to_1c","warehouse","option_to_1c","store_to_1c","attribute_to_1c","manufacturer_to_1c");
		foreach ($tables_module as $table) {
			$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "%" . $table . "'");
			if (!$query->rows) {
				$error = "[ERROR] Таблица " . $table . " в базе отсутствует, переустановите модуль! Все связи будут потеряны!";
				$this->log($error);
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
		//$this->log("[FUNC] setSeoURL()");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $url_type . "=" . $element_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword`='" . $this->transString($element_name) . "'");
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
	 * Очищает количество всех товаров для текщего магазина
	 */
	private function flushQuantity($product_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = 0 WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "'");
		$this->log("> Остатки обнулены");
	} // flushQuantity()


	/**
	 * Поиск XML_ID товара по ID
	 */
	private function getXMLIDByProductId($product_id) {
		$query = $this->db->query('SELECT 1c_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `product_id` = ' . $product_id);
		return isset($query->row['1c_id']) ? $query->row['1c_id'] : '';
	} // getXMLIDByProductId()


	/**
	 * Проверка на существование поля в таблице
	 */
	private function existFiled($table, $field, $value="") {
		$query = $this->db->query("SHOW COLUMNS FROM " . DB_PREFIX . $table . " WHERE `field` = '" . $field . "'");
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
	} // existFiled()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ КАТАЛОГА ****************************** 
	 */


	/**
	 * Формирует строку запроса для категории
	 */
	private function prepareStrQueryCategory($data) {
		if ($data['parent_id'] == 0) {
			$sql = ", top = '1'";
		} else {
			$sql = isset($data['top'])		? ", top = '" . 		(int)$data['top'] . "'" 		: "";
		}
		$sql .= isset($data['column'])		? ", column = '" .		(int)$data['column'] . "'" 		: "";
		$sql .= isset($data['sort_order'])	? ", sort_order = '" . 	(int)$data['sort_order'] . "'" 	: "";
		$sql .= isset($data['status'])		? ", status = '" . 		(int)$data['status'] . "'" 		: "";
		$sql .= isset($data['noindex'])		? ", noindex = '" . 	(int)$data['noindex'] . "'" 	: "";
		return $sql;
	} //prepareStrQueryCategory()
	

	/**
	 * Формирует строку запроса для описания категории
	 */
	private function prepareStrQueryCategoryDesc($data) {
		$sql = isset($data['description'])			? ", description = '" . 		$this->db->escape($data['description']) . "'" 		: "";
		$sql .= isset($data['meta_title'])			? ", meta_title = '" . 			$this->db->escape($data['meta_title']) . "'" 		: "";
		$sql .= isset($data['meta_h1'])				? ", meta_h1 = '" . 			$this->db->escape($data['meta_h1']) . "'" 			: "";
		$sql .= isset($data['meta_description'])	? ", meta_description = '" . 	$this->db->escape($data['meta_description']) . "'" 	: "";
		$sql .= isset($data['description'])			? ", description = '" . 		$this->db->escape($data['description']) . "'" 		: "";
		$sql .= isset($data['meta_keyword'])		? ", meta_keyword = '" . 		$this->db->escape($data['meta_keyword']) . "'" 		: "";
		return $sql;
	} //prepareStrQueryCategoryDesc()

	
	/**
	 * Подготавливает запрос для товара
	 */
	private function prepareQueryProduct($data) {
		$sql  = isset($data['model']) 			? ", model = '" . $this->db->escape($data['model']) . "'" 					: "";
		$sql .= isset($data['sku']) 			? ", sku = '" . $this->db->escape($data['sku']) . "'" 						: "";
		$sql .= isset($data['upc']) 			? ", upc = '" . $this->db->escape($data['upc']) . "'" 						: "";
		$sql .= isset($data['ean']) 			? ", ean = '" . $this->db->escape($data['ean']) . "'" 						: "";
		$sql .= isset($data['jan']) 			? ", jan = '" . $this->db->escape($data['jan']) . "'"						: "";
		$sql .= isset($data['isbn']) 			? ", isbn = '" . $this->db->escape($data['isbn']) . "'"						: "";
		$sql .= isset($data['mpn']) 			? ", mpn = '" . $this->db->escape($data['mpn']) . "'"						: "";
		$sql .= isset($data['mpn']) 			? ", mpn = '" . $this->db->escape($data['mpn']) . "'"						: "";
		$sql .= isset($data['location']) 		? ", location = '" . $this->db->escape($data['location']) . "'"				: "";
		$sql .= isset($data['quantity']) 		? ", quantity = '" . (int)$data['quantity'] . "'"							: "";
		$sql .= isset($data['minimum']) 		? ", minimum = '" . (int)$data['minimum'] . "'"								: "";
		$sql .= isset($data['subtract']) 		? ", subtract = '" . (int)$data['subtract'] . "'"							: "";
		$sql .= isset($data['stock_status_id']) ? ", stock_status_id = '" . (int)$data['stock_status_id'] . "'"				: "";
		$sql .= isset($data['date_available']) 	? ", date_available = '" . $this->db->escape($data['date_available']) . "'" : "";
		$sql .= isset($data['manufacturer_id']) ? ", manufacturer_id = '" . (int)$data['manufacturer_id'] . "'"				: "";
		$sql .= isset($data['shipping']) 		? ", shipping = '" . (int)$data['shipping'] . "'"							: "";
		$sql .= isset($data['price']) 			? ", price = '" . (float)$data['price'] . "'"								: "";
		$sql .= isset($data['points']) 			? ", points = '" . (int)$data['points'] . "'"								: "";
		$sql .= isset($data['weight_class_id']) ? ", weight_class_id = '" . (int)$data['weight_class_id'] . "'"				: "";
		$sql .= isset($data['length']) 			? ", length = '" . (float)$data['length'] . "'"								: "";
		$sql .= isset($data['width']) 			? ", width = '" . (float)$data['width'] . "'"								: "";
		$sql .= isset($data['weight']) 			? ", weight = '" . (float)$data['weight'] . "'"								: "";
		$sql .= isset($data['height']) 			? ", height = '" . (float)$data['height'] . "'"								: "";
		$sql .= isset($data['length_class_id']) ? ", length_class_id = '" . (int)$data['length_class_id'] . "'"				: "";
		$sql .= isset($data['status']) 			? ", status = '" . (int)$data['status'] . "'"								: "";
		$sql .= isset($data['noindex']) 		? ", noindex = '" . (int)$data['noindex'] . "'"								: "";
		$sql .= isset($data['tax_class_id']) 	? ", tax_class_id = '" . (int)$data['tax_class_id'] . "'"					: "";
		$sql .= isset($data['sort_order']) 		? ", sort_order = '" . (int)$data['sort_order'] . "'"						: "";
		return $sql;
	} // prepareQueryProduct()


	/**
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories($data) {
		if (!$data['product_id']) {
			$this->log("[ERROR] Заполнение родительскими категориями отменено, т.к. не указан product_id!");
			return false;
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = " . $data['product_id']);
		foreach ($data['product_category'] as $category_id) {
			$parents_id = array_merge($data['product_category'], $this->findParentsCategories($category_id));
			foreach ($parents_id as $parent_id) {
				if ($parent != 0) {
					if (method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
						$this->db->query("INSERT INTO " .DB_PREFIX . "product_to_category SET product_id = " . $data['product_id'] . ", category_id = " . $parent_id . ", main_category = " . ($data['main_category_id'] == $parent_id ? 1 : 0));
					} else {
						$this->db->query("INSERT INTO " .DB_PREFIX . "product_to_category SET product_id = " . $data['product_id'] . ", category_id = " . $parent_id);
					}
				}
			}
		}
		$this->log("> Заполнены родительские категории");
		return true;
	} // fillParentsCategories()


	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {
		$result = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX ."category WHERE category_id = " . $category_id);
		if (isset($query->row['parent_id'])) {
			$result[] = $query->row['parent_id'];
			$result = array_merge($result, $this->findParentsCategories($query->row['parent_id']));
		}
		return $result;
	} // findParentsCategories()


	/**
	 * Устанавливает в какой магазин загружать данные
	 */
	private function setStore($classifier_name) {
		$config_stores = $this->config->get('exchange1c_stores');
//		$this->log($config_stores);
		if (!$config_stores) {
			$this->STORE_ID = 0;
			return;
		}
		// Если ничего не заполнено - по умолчанию
		foreach ($config_stores as $key => $config_store) {
			if ($classifier_name == "Классификатор (" . $config_store['keyword'] . ")") {
				$this->STORE_ID = $config_store['store_id'];
			}
		}
	} // setStore()
	 

	/**
	 * Поиск категории по XML_ID
	 */
	private function getCategoryByXMLID($xml_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_1c WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
		return isset($query->row['category_id']) ? $query->row['category_id'] : 0;
	} // getCategoryByXMLID()


	/**
	 * Поиск товара по XML_ID
	 */
	private function getProductByXMLID($xml_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_1c WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
		return isset($query->row['product_id']) ? $query->row['product_id'] : 0;
	} // getProductByXMLID()


	/**
	 * Обновляет категорию
	 */
	private function updateCategory($data) {

		// При необходимости меняем родителя
		$sql = "SELECT parent_id FROM " . DB_PREFIX . "category WHERE category_id = '" . (int)$data['category_id'] . "'";
		//$this->log($sql);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$parent_id = $query->row['parent_id'];
			if ($data['parent_id'] <> $parent_id){
				$data['parent_id'] = $parent_id;
				$sql = "UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "'";
				$sql .= $this->prepareStrQueryCategory($data);
				$sql .= ", date_modified = NOW() WHERE category_id = '" . (int)$data['category_id'] . "'";
				$this->log($sql);
				$query = $this->db->query($sql);
			}
		}
		
		// При необходимости меняем название
		$sql = "SELECT name FROM " . DB_PREFIX . "category_description WHERE category_id = '" . (int)$data['category_id'] . "'";
		//$this->log($sql);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$name = $query->row['name'];
			if ($data['name'] <> $name) {
				$data['name'] = $name;
				$sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$data['category_id'] . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $this->db->escape($data['name']) . "'";
				$sql .= $this->prepareStrQueryCategoryDesc($data);
				$this->log($sql);
				$query = $this->db->query($sql);
			}
		}

		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
		}
		
		$this->cache->delete('category');
		
		$this->log("Категория обновлена: '" . $data['name'] . "'");
	} // updateCategory()
	

	/**
	 * Добавляет категорию
	 */
	private function addCategory($data) {
		if ($data == false) return 0;
		$sql = "INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "'";
		$sql .= $this->prepareStrQueryCategory($data);
		$sql .= ", date_modified = NOW(), date_added = NOW()";
		//$this->log($sql);
		$this->db->query($sql);

		$category_id = $this->db->getLastId();
		
		// Описание
		$sql = "INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $this->db->escape($data['name']) . "'";
		$sql .= $this->prepareStrQueryCategoryDesc($data);
		//$this->log($sql);
		$this->db->query($sql);

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_path WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");
		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");
			$level++;
		}
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_path SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$category_id . "', level = '" . (int)$level . "'");
		
		// Магазин
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('category_id', $category_id, $data['seo_url']);
		}
		
		// Чистим кэш
		$this->cache->delete('category');
		
		// Добавим линк
		$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_1c SET category_id = " . $category_id . ", 1c_id = '" . $this->db->escape($data['xml_id']) . "'");
		
		$this->cache->delete('category');
		
		$this->log("Категория добавлена: '" . $data['name'] . "'");
		
		return $category_id;
	} // addCategory()


	/**
	 * Обрабатывает категории
	 */
	private function parseCategories($xml, $parent_id=0) {
		foreach ($xml->Группа as $category){
			if (isset($category->Ид) && isset($category->Наименование) ){
				$data = array();
				$data['xml_id']			= (string)$category->Ид;
				$data['name']			= (string)$category->Наименование;
				$data['category_id']	= $this->getCategoryByXMLID($data['xml_id']);
				$data['parent_id']		= $parent_id;
				$data['status']			= 1;

				if ($this->config->get('exchange1c_seo_url') == 2) {
					$data['seo_url']	= "category-" . $parent_id . "-" . $data['name'];
				}
				
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
	 * Добавляет товар в базу
	 */
	private function addProduct($data) {
//		$this->log($data);
		// товар
		$sql = $this->prepareQueryProduct($data);
		$sql = "INSERT INTO " . DB_PREFIX . "product SET date_added = NOW()" . $sql;
//		$this->log($sql);
		$this->db->query($sql);
		
		$product_id = $this->db->getLastId();
		
		// описание (пока только для одного языка)
		$sql = "product_id = " . (int)$product_id . ", language_id = " . (int)$this->LANG_ID . ", meta_title = '" . $this->db->escape($data['name']) . "'";
		$sql .= ", name = '" . $this->db->escape($data['name']) . "'";
		$sql .= isset($data['description']) ? ", description = '" . $this->db->escape($data['description']) . "'" : "";
		$sql .= isset($data['full_name']) ? ", meta_description = '" . $this->db->escape($data['full_name']) . "'" : "";
		$sql = "INSERT INTO " . DB_PREFIX . "product_description SET " . $sql;
//		$this->log($sql);
		$this->db->query($sql);
		
		// категории продукта
		$main_category = $this->existFiled("product_to_category", "main_category", 1);
		
		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'" . $main_category);
			}
		}
		
		// магазин
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// Связь с 1С
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_1c SET product_id = '" . (int)$product_id . "', 1c_id = '" . $data['xml_id'] . "'");

		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('product_id', $product_id, $data['seo_url']);
		}
		
		$this->cache->delete('product');
		
		$this->log("> Товар добавлен");
		return $product_id; 		
	} // addProduct()
	

	/**
	 * Обновляет описание товара в базе
	 */
	private function updateProductDescription($data, $product_id) {
		// описание (пока только для одного языка)
		$sql  = isset($data['name'])		? ", meta_title = '" . $this->db->escape($data['name']) . "'"				: "";
		$sql .= isset($data['name']) 		? ", name = '" . $this->db->escape($data['name']) . "'" 					: "";
		$sql .= isset($data['description']) ? ", description = '" . $this->db->escape($data['description']) . "'" 		: "";
		$sql .= isset($data['full_name']) 	? ", meta_description = '" . $this->db->escape($data['full_name']) . "'" 	: "";
		if ($sql){
			$sql = "UPDATE " . DB_PREFIX . "product_description SET language_id = '" . (int)$this->LANG_ID . "'" . $sql . " WHERE product_id = '" . (int)$product_id . "'";
//			$this->log($sql);
			$this->db->query($sql);
		} else {
			$this->log("[i] Описание товара не нуждается в обновлении");
		}

	} // updateProductDescription()
	
	
	/**
	 * Обновляет товар в базе
	 */
	private function updateProduct($data, $product_id) {
//		$this->log($data);

		$update_filelds = $this->config->get('exchange1c_product_fields_update');
		// Удаление полей которые обновлять не нужно
		if (!isset($update_filelds['NAME'])) {
			unset($data['name']);
			$this->log("[i] Обновление названия отключено");
		}
		if (!isset($update_filelds['CATEGORY'])) {
			unset($data['product_category']);
			$this->log("[i] Обновление категорий отключено");
		}

		$sql = $this->prepareQueryProduct($data);
		if ($sql) {
			$sql = "UPDATE " . DB_PREFIX . "product SET date_modified = NOW()" . $sql . " WHERE product_id = '" . (int)$product_id . "'";
			//$this->log($sql);
			$this->db->query($sql);
		} else {
			$this->log("[i] Товар не нуждается в обновлении");
		}
		
		$this->updateProductDescription($data, $product_id);
		
		// категории
		$main_category = $this->existFiled("product_to_category", "main_category", 1);
		
		if (isset($data['product_category'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'" . $main_category);
			}
		} else {
			$this->log("[i] Категории товара не нуждаются в обновлении");
		}
		
		// магазин
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('product_id', $product_id, $data['seo_url']);
		}

		$this->cache->delete('product');
		
		$this->log("> Товар обновлен");
	} // updateProduct()


	/**
	 * Обновление или добавление товара
	 */
 	private function setProduct($data) {
 		// Ищем товар...
 		$product_id = $this->getProductByXMLID($data['xml_id']);
		if (!$this->config->get('exchange1c_dont_use_artsync') && !$product_id && isset($data['sku'])) {
			$product_id = $this->getProductBySKU($data['sku']);
 		}
 		// Можно добавить поиск по наименованию или другим полям...
 		
 		// SEO обновляем если только задано имя
 		if (isset($data['name'])) {
			if ($this->config->get('exchange1c_seo_url') == 2) {
	 			$data['seo_url'] = $data['name'];
			}
 		}
 		
 		// Если не найден товар...
 		if (!$product_id) {
 			$product_id = $this->addProduct($data);
 		} else {
 			$this->updateProduct($data, $product_id);
 		}
 		//$this->log($this->db->query("SELECT product_id, manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'"));
 		return $product_id; 
 	} // setProduct()

	
	/**
	 * Загружает реквизиты товара в массив
	 */
	private function parseRequisite($xml, $data) {
		foreach ($xml->ЗначениеРеквизита as $requisite){
			switch ($requisite->Наименование){
				case 'Вес':
					$data['weight'] = $requisite->Значение ? (float)$requisite->Значение : 0;
				break;
				case 'ТипНоменклатуры':
					$data['item_type'] = $requisite->Значение ? (string)$requisite->Значение : '';
				break;
				case 'ВидНоменклатуры':
					$data['item_view'] = $requisite->Значение ? (string)$requisite->Значение : '';
				break;
				case 'ОписаниеВФорматеHTML':
					$data['description'] = $requisite->Значение ? nl2br((string)$requisite->Значение) : '';
				break;
				case 'Полное наименование':
					$data['full_name'] = $requisite->Значение ? htmlspecialchars((string)$requisite->Значение) : '';
					if ($this->config->get('exchange1c_product_name_or_fullname')) {
						$data['name'] = $requisite->Значение ? htmlspecialchars((string)$requisite->Значение) : '';
					}
				break;
//				default:
//					$this->log("[?] Неиспользуемый реквизит: " . (string)$requisite->Наименование. " = " . (string)$requisite->Значение);
			}
		}
//		$this->log($data);
		return $data;
	} // parseRequisite()
	

	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 */
	private function applyWatermark($filename, $wm_filename) {
		$wm_fullname = DIR_IMAGE . $wm_filename;
		$fullname = DIR_IMAGE . $filename;
//		$this->log($wm_fullname);
//		$this->log($fullname);
		if (is_file($wm_fullname) && is_file($fullname)) {
			// Получим расширение файла
			$info = pathinfo($filename);
			$extension = $info['extension'];
//			$this->log($info);
			// Создаем объект картинка из водяного знака и получаем информацию о картинке
			$image = new Image($fullname);
			if (version_compare(VERSION, '2.0.3.1', '>')) {
				$image->watermark(new Image($wm_fullname));
			} else  {
				$image->watermark($wm_fullname);
			}

			// Формируем название для файла с наложенным водяным знаком
			$new_image = utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '_watermark.' . $extension;

			// Сохраняем картинку с водяным знаком
			$image->save(DIR_IMAGE . $new_image);
//			$this->log("> Наложен водяной знак: " . $new_image);
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
				$this->log("> Найдено текстовое описание в файле");
				return 1;
			}
		}
		return 0;
	} // setFile())
	

	/**
	 * Добавляет картинки в товар
	 */
	private function parseImages($xml, $product_id) {
		$watermark = $this->config->get('exchange1c_watermark');
		$index = 0;
		
		// Нужно ли обновлять картинки товара
		$update_filelds = $this->config->get('exchange1c_product_fields_update');
		if (!isset($update_filelds['IMAGES'])) {
			$this->log("[i] Обновление картинок отключено!");
			return true;
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
		foreach ($xml as $image) {
			
			$full_image = DIR_IMAGE . (string)$image;
			
			// не картинки обрабатываем тут
			if (getimagesize($full_image) == NULL) {
				if (!$this->setFile($full_image, $product_id)) {
					$this->log("Файл '" . (string)$image . "' не является картинкой");
				}
				continue;
			}
			
			// накладываем водяные знаки только на существующую картинку 
			if (file_exists($full_image)) {
				$newimage = empty($watermark) ? (string)$image : $this->applyWatermark((string)$image, $watermark);
			} else {
				// если картинки нет подставляем эту
				$newimage = 'no_image.png';
			}
			
			// основная картинка
			if ($index == 0) {
				$sql = "UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($newimage) . "' WHERE product_id = '" . (int)$product_id . "'";
//				$this->log($sql);
				$this->db->query($sql);
				//$this->log("> Картинка основная: '" . $newimage . "'");
			}
			// дополнительные картинки
			else {
				$sql = "INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($newimage) . "', sort_order = '" . (int)$index . "'";
//				$this->log($sql);
				$this->db->query($sql);
				//$this->log("> Картинка дополнительная: '" . $newimage . "'");
			}
			$index ++;
		}
		$this->log("> Картинок: " . $index);
		return true;
	} // parseImages()


	/**
	 * Возвращает id группы для свойств
	 */
	private function setAttributeGroup($name) {
		$query = $this->db->query("SELECT attribute_group_id FROM " . DB_PREFIX . "attribute_group_description WHERE name = '" . $name . "'");
		if ($query->rows) {
			return $query->row['attribute_group_id'];
		}
		
		// Добавляем группу
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group SET sort_order = '1'");
		$attribute_group_id = $this->db->getLastId();
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_group_description SET attribute_group_id = '" . (int)$attribute_group_id . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $name . "'");
		return $attribute_group_id;
	} // setAttributeGroup()


	/**
	 * Возвращает id атрибута из базы
	 */
	private function setAttribute($xml_id, $attribute_group_id, $name, $sort_order) {

		// Ищем свойства по 1С Ид
		$query = $this->db->query("SELECT attribute_id FROM " . DB_PREFIX . "attribute_to_1c WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
		if ($query->num_rows) {
			return $query->row['attribute_id'];
		}

		// Попытаемся найти по наименованию
		$query = $this->db->query("SELECT a.attribute_id FROM " . DB_PREFIX . "attribute a LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE ad.language_id = '" . $this->LANG_ID . "' AND ad.name LIKE '" . $this->db->escape($name) . "' AND a.attribute_group_id = '" . (int)$attribute_group_id . "'");
		if ($query->num_rows) {
			return $query->row['attribute_id'];
		}
		
		// Добавим в базу характеристику
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute SET attribute_group_id = '" . (int)$attribute_group_id . "', sort_order = '" . (int)$sort_order . "'"); 
		$attribute_id = $this->db->getLastId();
		$this->db->query("INSERT INTO " . DB_PREFIX . "attribute_description SET attribute_id = '" . (int)$attribute_id . "', language_id = '" . (int)$this->LANG_ID . "', name = '" . $this->db->escape($name) . "'");
			
		// Добавляем ссылку для 1С Ид
		$this->db->query("INSERT INTO " .  DB_PREFIX . "attribute_to_1c SET attribute_id = '" . (int)$attribute_id . "', 1c_id = '" . $this->db->escape($xml_id) . "'");
			
		return $attribute_id;
	} // setAttribute()


	/**
	 * Загружает значения атрибута (Свойства из 1С)
	 */
	private function parseAttributesValues($xml) {
		$data = array();
//		$this->log($xml);
		if ((string)$xml->ТипЗначений == "Справочник") {
			foreach ($xml->ВариантыЗначений->Справочник as $item) {
//				$this->log($item);
				$data[(string)$item->ИдЗначения] = (string)$item->Значение;
			}
		}
//		$this->log($data);
		return $data;
	}


	/**
	 * Загружает атрибуты (Свойства из 1С)
	 */
	private function parseAttributes($xml) {
		// Установим группу для свойств
		$attribute_group_id = $this->setAttributeGroup('Свойства');
		
		$data = array();
		$sort_order = 0;
		foreach ($xml->Свойство as $property) {
			$xml_id		= (string)$property->Ид;
			$name 	= trim((string)$property->Наименование);
			
			if ($name == 'Производитель') {
				$values = $this->parseAttributesValues($property);
				foreach ($values as $val_xml_id=>$value) {
//					$this->log("[i] val_xml_id: " . $val_xml_id);
//					$this->log("[i] value: " . $value);
					$manufacturer_id = $this->setManufacturer($value, $val_xml_id);
//					$this->log("[i] manufacturer_id: " . $manufacturer_id);
				}
//				continue;
			}
			
			$data[$xml_id] = array(
				'name'			=> $name,
				'attribute_id'	=> $this->setAttribute($xml_id, $attribute_group_id, $name, $sort_order) 
			);

			$this->log("> Свойство '" . $name . "'");

			$values = $this->parseAttributesValues($property);
			if ($values) {
				$data[$xml_id]['values'] = $values;
			}
			 
			
//			$values = array();
//			if ((string)$property->ТипЗначений == "Справочник") {
//				foreach ($property->ВариантыЗначений->Справочник as $item) {
//					$values[(string)$item->ИдЗначения] = (string)$item->Значение;
//				}
//				$data[$xml_id]['values'] = $values;
//			}
			$sort_order ++;
		}

//		$this->log($data);
		return $data;
		
	} // parseAttributes()


	/**
	 * Устанавливает свойства в товар
	 */
	private function setAttributes($xml, $attributes, $product_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");
		foreach ($xml->ЗначенияСвойства as $property) {
			// если есть значения
			$xml_id = (string)$property->Ид;
			$name 	= trim($attributes[$xml_id]['name']);
			$value 	= trim((string)$property->Значение);
//			$this->log($attributes[$xml_id]);
			
			if ($value) {
				if ($attributes[$xml_id]) {
					// агрегатный тип
					if (isset($attributes[$xml_id]['values'])) {
						$value = trim($attributes[$xml_id]['values'][$value]);
					}
				}
			}

			if ($name == 'Производитель' && !empty($value)) {
				$manufacturer_id = $this->setManufacturer($value, $xml_id);
				$sql = "UPDATE " . DB_PREFIX . "product SET manufacturer_id = '" . $manufacturer_id. "' WHERE product_id = '" . (int)$product_id . "'";
//				$this->log($sql);
				$this->db->query($sql);
				$this->log("> Производитель (из свойства): " . $value);
				continue;
			}

			if ($value) {
				$this->log("> Свойство '" . $name . "' : '" . $value . "'");
				// Добавим в товар
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attributes[$xml_id]['attribute_id'] . "', language_id = '" . (int)$this->LANG_ID . "', text = '" .  $this->db->escape($value) . "'");
			}
		}
		return 1;
	} // setAttributes()

	
	/**
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($manufacturer_id, $data) {
		// Обновляем
		$sql   = " name = '" . $this->db->escape($data['name']) . "'";
		$sql   .= isset($data['noindex']) ? ", noindex = '" . (int)$data['noindex'] . "'" : "";
		$sql = "UPDATE " . DB_PREFIX . "manufacturer SET " . $sql . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "'";
//		$this->log($sql);
		$query = $this->db->query($sql);
		
		if (version_compare(VERSION,'2.0.3.1', '<')) {
			$sql    = " meta_title = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_h1 = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_description = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_keyword = '" . $this->db->escape($data['name']) . "'";
			$sql = "UPDATE " . DB_PREFIX . "manufacturer_description SET" . $sql . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "' AND language_id = '" . $this->LANG_ID . "'";
//			$this->log($sql);
			$query = $this->db->query($sql);
		}
		
		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('manufacturer_id', $manufacturer_id, $data['seo_url']);
		}
		$this->log("> Производитель '" . $data['name'] . "' обновлен");
	} // updateManufacturer()
	

	/**
	 * Добавляем производителя
	 */
	private function addManufacturer($data) {
		$sql 	 = " name = '" . $this->db->escape($data['name']) . "'";
		$sql 	.= isset($data['sort_order']) ? ", sort_order = '" . (int)$data['sort_order'] . "'" : "";
		$sql 	.= isset($data['image']) ? ", image = '" . (int)$data['image'] . "'" : "";
		$sql 	.= isset($data['noindex']) ? ", noindex = '" . (int)$data['noindex'] . "'" : "";
		$sql = "INSERT INTO " . DB_PREFIX . "manufacturer SET" . $sql;
//		$this->log($sql);
		$query = $this->db->query($sql);

		$manufacturer_id = $this->db->getLastId();

		if (version_compare(VERSION, '2.0.3.1', '<')) {
//		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_description'");
//		if ($query->num_rows) {
			$sql	= " manufacturer_id = '" . (int)$manufacturer_id . "'";
			$sql   .= ", language_id = '" . (int)$this->LANG_ID . "'";
			$sql   .= ", meta_title = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_h1 = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_description = '" . $this->db->escape($data['description']) . "'";
			$sql   .= ", meta_keyword = '" . $this->db->escape($data['name']) . "'";
			$sql = "INSERT INTO " . DB_PREFIX . "manufacturer_description SET" . $sql;
//			$this->log($sql);
			$query = $this->db->query($sql);
		}
		
		if (isset($data['xml_id'])) {
			// добавляем связь
			$sql 	= "INSERT INTO " . DB_PREFIX . "manufacturer_to_1c SET 1c_id = '" . $this->db->escape($data['xml_id']) . "', manufacturer_id = '" . (int)$manufacturer_id . "'";
//			$this->log($sql);
			$query = $this->db->query($sql);
		}

		// SEO
		if (isset($data['seo_url'])) {
			$this->setSeoURL('manufacturer_id', $manufacturer_id, $data['seo_url']);
		}
		
		$sql 	= "INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$this->STORE_ID . "'";
//		$this->log($sql);
		$query = $this->db->query($sql);
		
		$this->log("> Производитель '" . $data['name'] . "' добавлен");
		return $manufacturer_id; 
	} // addManufacturer()


	/**
	 * Устанавливаем производителя
	 */
	private function setManufacturer($name, $xml_id="") {
		$data = array();
		if ($xml_id) {
			$data['xml_id'] 		= $xml_id;
		}
		$data['name']			= htmlspecialchars($name);
		$data['description'] 	= 'Производитель ' . $data['name'];
		$data['sort_order']		= 1;

		if ($this->existFiled("manufacturer", "noindex")) {
			$data['noindex'] = 1;	// значение по умолчанию
		}

		if (isset($data['xml_id'])) {
			// Поиск изготовителя по 1C Ид
			$sql 	= "SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer_to_1c` WHERE 1c_id = '" . $this->db->escape($data['xml_id']) . "'";
//			$this->log($sql);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$manufacturer_id = $query->row['manufacturer_id'];
			}
		}

		// Поиск изготовителя по имени, если не нашли по Ид
		if (!isset($manufacturer_id)) {
			$sql 	= "SELECT manufacturer_id FROM `" . DB_PREFIX . "manufacturer` WHERE name LIKE '" . $this->db->escape($data['name']) . "'";
//			$this->log($sql);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$manufacturer_id = $query->row['manufacturer_id'];
			}
		}

		// SEO
		if ($this->config->get('exchange1c_seo_url') == 2) {
			$data['seo_url'] = "brand-" . $data['name'];
		}

		if (!isset($manufacturer_id)) {
			// Создаем
			$manufacturer_id = $this->addManufacturer($data);
		} else {
			// Обновляем
			$this->updateManufacturer($manufacturer_id, $data);
		}

//		$this->log("> Производитель: '" . $data['name'] . "'");
		return $manufacturer_id;
	} // setManufacturer()


	/**
	 * Обрабатывает товары
	 */
	private function parseProducts($xml, $classifier) {
		if ($this->existFiled("product", "noindex")) {
			$noindex = 1;
		}
		$default_stock_status = $this->config->get('exchange1c_default_stock_status');

		foreach ($xml->Товар as $product){
			if (isset($product->Ид) && isset($product->Наименование) ){
				$data = array();
				$data['xml_id']				= (string)$product->Ид;
				$data['name']				= htmlspecialchars((string)$product->Наименование);

				if ($product->Артикул) {
					$data['model']			= htmlspecialchars((string)$product->Артикул);
					$data['sku']			= htmlspecialchars((string)$product->Артикул);
				}
				if ($product->Штрихкод) {
					$data['ean']			= ((string)$product->Штрихкод);
				}
				$data['status']				= 1;
				if (isset($noindex)) {
					$data['noindex']		= 1; // не во всех версиях
				}
//				$this->log($data);
				$this->log("Товар '" . $data['name'] . "'");
				
				if ((string)$product->ПолноеНаименование) {
					$data['full_name']		= htmlspecialchars((string)$product->ПолноеНаименование);
				}

				// описание
				if ($product->Описание)	{
					$data['description']	= nl2br(htmlspecialchars((string)$product->Описание));
				}

				if ($product->ЗначениеРеквизита) {
					$data = $this->parseRequisite($product, $data);					
				}

				// значения реквизитов
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

				// категории
				$data['product_category']	= array();
				foreach ($product->Группы->Ид as $category_xml_id) {
					$data['product_category'][] = $this->getCategoryByXMLID((string)$category_xml_id);
				}

				// изготовитель 
				if ($product->Изготовитель) {
					$data['manufacturer_id'] = $this->setManufacturer((string)$product->Изготовитель->Наименование, (string)$product->Изготовитель->Ид);
				}

				if ($default_stock_status) {
					$data['stock_status_id'] = $default_stock_status;
				}
				
				// записываем или обновляем товар в базе
				$product_id = $this->setProduct($data);
				
				// Обнуляем остаток только у тех товаров что загружаются 
				if ($this->config->get('exchange1c_flush_quantity')) {
					$this->flushQuantity($product_id);
				}

				// картинки
				if ($product->Картинка) {
					if (!$this->parseImages($product->Картинка, $product_id)) {
						$this->log('[ERROR] parseProducts(): Ошибка загрузки картинок!');
						return false;
					}
				}
				
				// Свойства
				if ($product->ЗначенияСвойств) {
					if (!$this->setAttributes($product->ЗначенияСвойств, $classifier['attributes'], $product_id)) {
						$this->log('[ERROR] parseProducts(): Ошибка загрузки свойств!');
						return false;
					}
				}
//$this->log($this->db->query("SELECT product_id, manufacturer_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'"));

				unset($product);
			}
		}
		return true;
	} // parseProducts()


	/**
	 * Получает product_id по артикулу
	 */
	private function getProductBySKU($sku) {
		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE sku = '" . $this->db->escape($sku) . "'");
        if ($query->num_rows) return $query->row['product_id'];
		else return 0;
	}


	/**
	 * Разбор каталога
	 */
	private function parseDirectory($xml, $classifier) {
		$directory					= array();
		$directory['xml_id']		= (string)$xml->Ид;
		$directory['name']			= (string)$xml->Наименование;
		$directory['classifier_id']	= (string)$xml->ИдКлассификатора;
		if ($directory['classifier_id'] <> $classifier['id']) {
			$this->log->write("[ERROR] Каталог не соответствует классификатору");
			return 0;
		}
		
		// Если полная выгрузка - требуется очистка для текущего магазина: товаров, остатков и пр.
		if ((string)$xml['СодержитТолькоИзменения'] == 'false')  {
			$this->log("[i] Полная выгрузка с 1С");
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
	private function addWarehouse($xml_id, $name) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "warehouse SET name = '" . $this->db->escape($name) . "', 1c_id = '" . $this->db->escape($xml_id) . "'");
		return $this->db->getLastId();
	} // addWarehouse()
	

	/**
	 * Ищет склад по XML_ID 
	 */
	private function getWarehouseByXMLID($xml_id) {
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'warehouse` WHERE `1c_id` = "' . $this->db->escape($xml_id) . '"');
		if ($query->num_rows) {
			return $query->row['warehouse_id'];
		}
		return 0;
	} // getWarehouseByXMLID()
	

	/**
	 * Возвращает id склада
	 */
	private function setWarehouse($xml_id, $name) {
		// Поищем склад по 1С Ид
		$warehouse_id = $this->getWarehouseByXMLID($xml_id);
		
		if (!$warehouse_id) {
			$warehouse_id = $this->addWarehouse($xml_id, $name);
		}
		return $warehouse_id;
		
	} // setWarehouse()
	

	/**
	 * Загружает список складов
	 */
	private function parseWarehouses($xml) {
		$data = array(); 
		foreach ($xml->Склад as $warehouse){
			if (isset($warehouse->Ид) && isset($warehouse->Наименование) ){
				$xml_id = (string)$warehouse->Ид;
				$name = trim((string)$warehouse->Наименование);
				$data[$xml_id] = array(
					'name' => $name
				);
				
				$data[$xml_id]['warehouse_id'] = $this->setWarehouse($xml_id, $name);
			}
		}
		return $data;
	} // parseWarehouses()
	

	/**
	 * Устанавливает общий остаток товара
	 */
	private function setQuantity($product_id, $quantity) {
		$sql = "UPDATE " . DB_PREFIX . "product SET quantity = '" . $quantity . "' WHERE product_id = '" . (int)$product_id . "'";
		$this->db->query($sql);
	} // setQuantity()

	
	/**
	 * Загружает остатки по складам
	 */
	private function parseQuantityWarehouse($xml, $offers_pack, $product_id) {
		$quantity = 0;
		foreach ($xml as $warehouse) {
			$xml_id = (string)$warehouse['ИдСклада'];
			if (isset($offers_pack['warehouses'][$xml_id])) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_quantity WHERE product_id = '" . (int)$product_id . "' AND warehouse_id = '" . (int)$offers_pack['warehouses'][$xml_id]['warehouse_id'] . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_quantity SET product_id = '" . (int)$product_id . "', warehouse_id = '" . (int)$offers_pack['warehouses'][$xml_id]['warehouse_id'] . "', quantity = '" . (int)$warehouse['КоличествоНаСкладе'] . "'");
				$this->log("> Остаток на складе '" . $offers_pack['warehouses'][$xml_id]['name'] . "': " . (int)$warehouse['КоличествоНаСкладе']);
			}
			$quantity += (int)$warehouse['КоличествоНаСкладе'];
		}
		$this->log("> Общий остаток по всем складам: " . $quantity);
		return $quantity;
	} // parseQuantityWarehouse()

		
	/**
	 * Загружает типы цен и сразу определяет к каким группам сопоставлены они
	 * Если не сопоставлен ни один тип цен, то цены не будут загружаться
	 */
	private function parsePriceType($xml) {
		$config_price_type = $this->config->get('exchange1c_price_type');
		$data = array();
		if ($config_price_type)  {
			foreach ($config_price_type as $key => $config_type) {
				foreach ($xml->ТипЦены as $price_type)  {
					$currency 	= isset($price_type->Валюта) ? (string)$price_type->Валюта : "RUB";
					$xml_id 	= (string)$price_type->Ид;
				 	$name 		= trim((string)$price_type->Наименование);
					if (strtolower($name) == strtolower($config_type['keyword'])) {
						$data[$xml_id] = $config_type;
						$data[$xml_id]['currency'] = $currency;
						if ($key == 0) {
							$data['default'] = $xml_id;
						}
					}
				}
			}
		}
		unset($xml);
		unset($config_price_type);
		return $data; 
	} // parsePriceType()


	/**
	 * Загружает все цены только в одной валюте
	 */
	private function parsePrices($xml, $offers_pack, $product_id) {
//$this->log($offers_pack['price_types']);		
		$index = 0;
		foreach ($xml->Цена as $price) {
	 		$xml_id 	= (string)$price->ИдТипаЦены;
	 		$price 	= (float)$price->ЦенаЗаЕдиницу;
	 		
	 		if ($index == 0){
	 			// Обновляем основную цену в товаре
	 			$sql = "UPDATE " . DB_PREFIX . "product SET price = '" . $price . "' WHERE product_id = '" . (int)$product_id . "'";
//$this->log($sql);
	 			$this->db->query($sql);
		 		$this->log("> Основная цена: " . $price);
	 		} else {
	 			// Цены для групп
	 			if (isset($offers_pack['price_types'][$xml_id])) {
	 				$price_type = $offers_pack['price_types'][$xml_id];
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
		 			$sql = "INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$price_type['customer_group_id'] . "', quantity = '" . (int)$price_type['quantity'] . "', priority = '" . (int)$price_type['priority'] . "', price = '" . (float)$price . "', date_start = '', date_end = ''";
//$this->log($sql);
					$this->db->query($sql);
			 		$this->log("> Цена '" . $price_type['keyword'] . "': " . $price);
	 			}
	 		}
	 		$index ++;
 		}

		unset($xml);			
 	} // parsePrices()


	/**
	 * Разбор предложений
	 */
	private function parseOffers($xml, $offers_pack) {
//$this->log('parseOffers():$offers_pack');
//$this->log($offers_pack);
		foreach ($xml->Предложение as $offer){
			$data = array();
			$data['xml_id']	= (string)$offer->Ид;
			$data['name']	= isset($offer->Наименование) ? (string)$offer->Наименование : '';
			$product_id		= $this->getProductByXMLID($data['xml_id']); 		
			if (!$product_id)  {
				continue;
			}
			
			$this->log("Товар '" . $data['name'] . "'");

			if ($offer->Цены) {
				$this->parsePrices($offer->Цены, $offers_pack, $product_id);
			}
			
			$data['quantity'] = 0;
			// Общий остаток по всем складам
			if ($offer->Количество) {
				$data['quantity'] = (int)$offer->Количество;
				$this->log("> Остаток общий: " . $data['quantity']);
			}

			// Остатки по складам
			if ($offer->Склад) {
				$data['quantity'] = $this->parseQuantityWarehouse($offer->Склад, $offers_pack, $product_id);
			}

			// Устанавливает общий остаток в товаре
			$this->setQuantity($product_id, $data['quantity']);
			
			if ($this->config->get('exchange1c_product_status_disable_if_quantity_zero')) {
				if ($data['quantity'] <= 0) {
					$data['status'] = 0;
					$this->log("> Товар отключен");
				}
			}

 		}
//$this->log('parseOffers():$data');
//$this->log($data);
		unset($xml);
		return 1;
	} // parseOffers()


	/**
	 * Загружает пакет предложений
	 */
	private function parseOffersPack($xml) {
		$offers_pack = array();
		$offers_pack['offers_pack_id']	= (string)$xml->Ид;
		$offers_pack['name']			= (string)$xml->Наименование;
		$offers_pack['directory_id']	= (string)$xml->ИдКаталога;
		$offers_pack['classifier_id']	= (string)$xml->ИдКлассификатора;
		
		// Сопоставленные типы цен
		if ($xml->ТипыЦен) {
			$offers_pack['price_types'] = $this->parsePriceType($xml->ТипыЦен);
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
	public function queryOrdersStatus($params) {
		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order WHERE order_status_id = " . $params['exchange_status'] . "");
		} else {
			$query = $this->db->query("SELECT order_id FROM " . DB_PREFIX . "order WHERE date_added >= '" . $params['from_date'] . "'");
		}
//		$this->log("> Поиск заказов со статусом id: " . $params['exchange_status']);
		if ($query->num_rows) {
			foreach ($query->rows as $orders_data) {
				// Меняем статус
				$query = $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = '" . $params['new_status'] . "' WHERE order_id = '" . $orders_data['order_id'] . "'");
				$this->log("> Изменен статус заказа #" . $orders_data['order_id']);
				// Добавляем историю в заказ
				$sql = "INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . $orders_data['order_id'] . "', comment = 'Ваш заказ обрабатывается', order_status_id = '" . $params['new_status'] . "', notify = '0', date_added = NOW()";
//$this->log($sql);
				$query = $this->db->query($sql);
				$this->log("> Добавлена история в заказ #" . $orders_data['order_id']);
			}
		}
		return 1;
	}


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ ****************************** 
	 */
	public function queryOrders($params) {
		$this->log("==== Выгрузка заказов ====");
//		$this->log($params);

		$this->load->model('sale/order');
		if (version_compare(VERSION, '2.0.3.1', '<')) {
			$this->load->model('sale/customer_group');
		} else {
			$this->load->model('customer/customer_group');
		}
		
		if ($params['exchange_status'] != 0) {
			// Если указано с каким статусом выгружать заказы
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status'] . "");
		} else {
			// Иначе выгружаем заказы с последей выгрузки, если не определа то все
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}
//$this->log($query);
		$document = array();
		$document_counter = 0;

		if ($query->num_rows) {
			foreach ($query->rows as $orders_data) {
				$order = $this->model_sale_order->getOrder($orders_data['order_id']);
				$this->log("> Выгружается заказ #" . $order['order_id']);
				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));
				if (version_compare(VERSION, '2.0.3.1', '<')) {
					$customer_group = $this->model_sale_customer_group->getCustomerGroup($order['customer_group_id']);
				} else {
					$customer_group = $this->model_customer_customer_group->getCustomerGroup($order['customer_group_id']);
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
						 'Ид'             => $this->getXMLIDByProductId($product['product_id'])
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
					
					$product_counter++;
				}


			} // foreach ($query->rows as $orders_data)
		}

		// Формируем заголовок
		$root = '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d', time()) . '" />';
		$xml = $this->array_to_xml($document, new SimpleXMLElement($root));
		
		// запись заказа в файл
		$f_order = fopen(DIR_CACHE . 'exchange1c/orders.xml', 'w');
		fwrite($f_order, $xml->asXML());
		fclose($f_order);
		
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
		$data = array();
		$data['id']				= (string)$xml->Ид;
		$data['name']			= (string)$xml->Наименование;
		$this->setStore($data['name']);

		// Организация
		if ($xml->Владелец) {
			$this->log(">>> Загрузка владельца");
			$data['owner']			= $this->parseOwner($xml->Владелец);
			unset($xml->Владелец);
			$this->log("<<< Владелец загружен");
		}

		if ($xml->Группы) {
			$this->log(">>> Загрузка категорий");
			$this->parseCategories($xml->Группы);
			unset($xml->Группы);
			$this->log("<<< Категории загружены");
		}

		if ($xml->Свойства) {
			$this->log(">>> Загрузка свойств");
			$data['attributes']		= $this->parseAttributes($xml->Свойства);
			unset($xml->Свойства);
			$this->log("<<< Свойства загружены");
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

//$this->log('importFile():$importFile');			
//$this->log($importFile);			
		// Функция будет сама определять что за файл загружается
		$this->log("==== Начата загрузка данных ====");
		$this->log("[i] Всего доступно памяти: " . sprintf("%.3f", memory_get_peak_usage() / 1024 / 1024) . " Mb");
		
		$this->log(">>> Начинается чтение XML");
		// Конвертируем XML в массив
		$xml = simplexml_load_file($importFile);
		$this->log("<<< XML прочитан");

		// Файл стандарта Commerce ML
		if (!$this->checkCML($xml)) {
			return 0;
		}

		// IMPORT.XML, OFFERS.XML
		if ($xml->Классификатор) {
			$this->log(">>> Загружается классификатор");
			$classifier = $this->parseClassifier($xml->Классификатор);
			unset($xml->Классификатор);
			$this->log("<<< Классификатор загружен");
		}
		
		if ($xml->Каталог) {
			$this->log(">>> Загрузка каталога");
			if (!isset($classifier)) {
				$this->log->write("[ERROR] Классификатор не загружен!");
				return 0;
			}
			
			if (!$this->parseDirectory($xml->Каталог, $classifier)) {
				return 0;
			}
			unset($xml->Каталог);
			$this->log("<<< Каталог загружен");
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
		}
		
		// ORDERS.XML
		if ($xml->Документ) {
			$this->log(">>> Загрузка документов");
			if (!isset($classifier)) {
				$this->log->write("[ERROR] Не загружен классификатор!");
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
//		else {
//			$this->log("Не распознаные данные:");
//			$this->log($xml);
//		}
		$this->log("==== Окончена загрузка данных ====");
		return 1;
	}


	/**
	 * Устанавливает обновления
	 */
	public function update($settings) {
		$version = $settings['exchange1c_version'];
		$update = false;
		
		$message = "Модуль в обновлении не нуждается";
		if (version_compare($version, '1.6.2.b4', '<')) {
			if ($this->update162b4()) {
				$version = '1.6.2.b4';
				$update = true;
			}
		}
		
		if (version_compare($version, '1.6.2.b5', '<')) {
			$version = '1.6.2.b5';
			$update = true;
		}
		
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
	public function update162b4() {
		// Добавление таблицы manufacturer_to_1c
		$new_version = '1.6.2.b4';

		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_to_1c'");
		if(!$query->num_rows) {
			$this->db->query(
					'CREATE TABLE
						`' . DB_PREFIX . 'manufacturer_to_1c` (
							`manufacturer_id` int(11) NOT NULL,
							`1c_id` varchar(255) NOT NULL,
							PRIMARY KEY (`manufacturer_id`),
							KEY `1c_id` (`1c_id`),
							FOREIGN KEY (`manufacturer_id`) REFERENCES `'. DB_PREFIX .'manufacturer`(`manufacturer_id`) ON DELETE CASCADE
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		if (!$this->existFiled('category_to_1c', '1c_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "category_to_1c CHANGE 1c_category_id 1c_id VARCHAR(255)");
		}
		
		if (!$this->existFiled('attribute_to_1c', '1c_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "attribute_to_1c CHANGE 1c_attribute_id 1c_id VARCHAR(255)");
		}
		return 1;
	}
	
	
	
}

