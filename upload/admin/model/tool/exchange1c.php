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
		return "1.6.2.b7";
	} // version()
	

	/**
	 * Пишет в файл журнала если включена настройка
	 *
	 * @param	string,array()	Сообщение или объект
	 */
	private function log($message, $title='') {
		if ($this->config->get('exchange1c_full_log'))
			$memory_usage = sprintf("%.3f", memory_get_usage() / 1024 / 1024);
			if (is_array($message) || is_object($message)) {
				$this->log->write($memory_usage);
				if ($title) $this->log->write($title.':');
				$this->log->write(print_r($message,true));
			} else {
				list ($di) = debug_backtrace();
				$line = sprintf("%04s",$di["line"]);
				if (isset($memory_usage)) {
					if ($title) $this->log->write($title.':');
					$this->log->write( $memory_usage . " Mb | " . $line . " | " . $message);
				} else {
					if ($title) $this->log->write($title.':');
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
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_description`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group`");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group_description`");
		$result .=  "Очищены таблицы атрибутов\n";

		// Выставляем кол-во товаров в 0
		$this->log("Очистка остатков...");
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = 0");
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "warehouse`");
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
	 * Удаляет связи с товара характеристиками 1С
	 */
	public function deleteLinkFeature($product_id) {
		// Удаляем линк
		if ($product_id){
			$this->db->query("DELETE FROM `" .  DB_PREFIX . "product_feature` WHERE product_id = '" . $product_id . "'");
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
		if (version_compare($this->config->get('exchange1c_version'), '1.6.2.b7', '<')) {
			$tables_module = array("product_to_1c","product_quantity","category_to_1c","warehouse","store_to_1c","attribute_to_1c","manufacturer_to_1c");
		} else {
			$tables_module = array("product_to_1c","product_quantity","category_to_1c","warehouse","product_feature","product_feature_value","store_to_1c","attribute_to_1c","manufacturer_to_1c");
		}
		foreach ($tables_module as $table) {
			$query = $this->db->query("SHOW TABLES FROM `" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "%" . $table . "'");
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
		$this->db->query("INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword`='" . $element_name . "'");
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
	 * Поиск XML_ID товара по ID
	 */
	private function getXMLIDByProductId($product_id) {
		$query = $this->db->query("SELECT 1c_id FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . $product_id);
		return isset($query->row['1c_id']) ? $query->row['1c_id'] : '';
	} // getXMLIDByProductId()


	/**
	 * Проверка на существование поля в таблице
	 */
	private function existField($table, $field, $value="") {
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
	 * ****************************** ФУНКЦИИ ДЛЯ SEO ****************************** 
	 */

	/**
	 * Получает все категории продукта
	 */
    private function getProductCategories($product_id)
    {
        $categories = array();
		$query = $this->db->query("SELECT c.category_id, cd.name FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id) INNER JOIN `" . DB_PREFIX . "product_to_category` pc ON (pc.category_id = c.category_id) WHERE cd.language_id = " . $this->LANG_ID . " AND pc.product_id = " . $product_id . " ORDER BY c.sort_order, cd.name ASC");
		foreach ($query->rows as $category) {
			$categories[] = $category['name'];
		}
		return implode(',', $categories);
    }

	/**
	 * Главная функция для генерации SEO
	 */
	private function seoGenerate() {
		$this->log('==> seoGenerate()');
		$this->load->model('setting/setting');

		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);

		// Производители
		$seo_fields_manufacturer = array(
			'seo_url'			=> array('trans' => true)
		);

		// ТОВАРЫ
		$date_start = $this->config->get('exchange1c_date_exchange');
		$date_end = date('Y-m-d H:i:s');
		
		$overwrite = $this->config->get('exchange1c_seo_product_overwrite') == 'overwrite' ? true : false;
		$sql = "SELECT p.product_id, p.sku, p.price, pd.name, pd.description, pm.name as manufacturer, pd.tag, pd.meta_title, pd.meta_description, pd.meta_keyword, pa.keyword as seo_url FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id) LEFT JOIN `" . DB_PREFIX . "manufacturer` pm ON (p.manufacturer_id = pm.manufacturer_id) LEFT JOIN `" . DB_PREFIX . "url_alias` pa ON (CONCAT('product_id=', p.product_id) = pa.query) WHERE pd.language_id = " . $this->LANG_ID . ($overwrite ? "" : " AND pa.query IS NULL") . " AND p.date_modified BETWEEN STR_TO_DATE('" . $date_start . "', '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE('" . $date_end . "', '%Y-%m-%d %H:%i:%s') ORDER BY pd.name ASC";
//		$this->log('SEO Product: '.$sql);
		$query = $this->db->query($sql);
		
		// ТОВАРЫ
		foreach ($query->rows as $data) {
			$data['categories'] = $this->getProductCategories($data['product_id']);
	 		$data = $this->seoGenerateProduct($data, $seo_fields);
	 		$this->updateProduct($data, $data['product_id']);

			if (isset($data['seo_url'])) {
				$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
			}

//	 		unset($data['description']); // временно
//			$this->log($data);
		}
		
		// КАТЕГОРИИ
		$overwrite = $this->config->get('exchange1c_seo_category_overwrite') == 'overwrite' ? true : false;
		$sql = "SELECT c.category_id, cd.name, c.parent_id FROM `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_description` cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "url_alias a ON (CONCAT('category_id=', c.category_id) = a.query) WHERE cd.language_id = " . (int)$this->LANG_ID . ($overwrite ? '' : ' AND a.query IS NULL') . " ORDER BY c.sort_order, cd.name ASC";
//		$this->log('SEO Category: '.$sql);
		$query = $this->db->query($sql);
//		$this->log($query->rows);

		foreach ($query->rows as $data) {
	 		$data = $this->seoGenerateCategory($data, $seo_fields);
	 		$this->updateCategory($data);

			if (isset($data['seo_url'])) {
				$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
			}
		}
		
		// ПРОИЗВОДИТЕЛИ
		$overwrite = $this->config->get('exchange1c_seo_manufacturer_overwrite') == 'overwrite' ? true : false;
		$sql = "SELECT m.manufacturer_id, m.name FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "url_alias a ON (CONCAT('manufacturer_id=', m.manufacturer_id) = a.query)" . ($overwrite ? '' : ' WHERE a.query IS NULL') . " ORDER BY m.name ASC";
//		$this->log('SEO Manufacturer: '.$sql);
		$query = $this->db->query($sql);
		foreach ($query->rows as $data) {
	 		$data = $this->seoGenerateManufacturer($data, $seo_fields);
	 		$this->updateManufacturer($data, $data['manufacturer_id']);

			if (isset($data['seo_url'])) {
				$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
			}
		}
	} // seoGenerate()


	/**
	 * Генерит SEO строк
	 */
	private function seoGenerateString($template, $product_tags, $trans = false) {
//		$this->log('==> seoGenerateString()');
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
//		$this->log('template: '.$template);
//		$this->log($matches[0]);
//		$this->log($values);
		return str_replace($matches[0], $values, $template);
	} // seoGenerateStr()


	/**
	 * Генерит SEO переменные шаблона для товара
	 */
	private function seoGenerateProduct($data, $seo_fields) {
		$this->log('==> seoGenerateProduct()');
//		$this->log('DATA Product: ');
//		$this->log($data);
		
		// Сопоставляем значения к тегам
		$tags = array(
			'{name}'		=> isset($data['name']) 		? $data['name'] 		: '',
			'{sku}'			=> isset($data['sku'])			? $data['sku'] 			: '',
			'{brand}'		=> isset($data['manufacturer'])	? $data['manufacturer'] : '',
			'{desc}'		=> isset($data['description'])	? $data['description'] 	: '',
			'{cats}'		=> isset($data['categories'])	? $data['categories'] 	: '',
			'{price}'		=> isset($data['price'])		? money_format("%i", $data['price']) 		: '',
			'{prod_id}'		=> isset($data['product_id'])	? $data['product_id'] 	: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 	: ''
		);

//		$this->log($data);
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
//				$this->log('TEMPLATE');
				$template = $this->config->get('exchange1c_seo_product_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_product_'.$field) == 'import') {
//				$this->log('IMPORT');
				// из свойства которое считалось при обмене
			}
//			$this->log('Field name: '.$field);
			$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
//			$this->log('Field value: '.$data[$field]);
		}
		
//		$this->log($data);
		return $data;
	} // seoGenerateProduct()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateCategory($data, $seo_fields) {
		$this->log('==> seoGenerateCategory()');
		
		// Сопоставляем значения к тегам
		$tags = array(
			'{cat}'			=> isset($data['name']) 		? $data['name'] 		: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 	: ''
		);

//		$this->log($data);
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_category_'.$field) == 'template') {
//				$this->log('TEMPLATE');
				$template = $this->config->get('exchange1c_seo_category_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_category_'.$field) == 'import') {
//				$this->log('IMPORT');
				// из свойства которое считалось при обмене
			}
//			$this->log('Field name: '.$field);
			$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
//			$this->log('Field value: '.$data[$field]);
		}
		
//		$this->log($data);
		return $data;
	} // seoGenerateCategory()


	/**
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateManufacturer($data, $seo_fields) {
		$this->log('==> seoGenerateCategory()');
//		$this->log('DATA Manufacturer: ');
//		$this->log($data);
		
		// Сопоставляем значения к тегам
		$tags = array(
			'{brand}'		=> isset($data['name']) 			? $data['name'] 			: '',
			'{brand_id}'	=> isset($data['manufacturer_id'])	? $data['manufacturer_id'] 	: ''
		);

//		$this->log($data);
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'template') {
//				$this->log('TEMPLATE');
				$template = $this->config->get('exchange1c_seo_manufacturer_'.$field.'_template');
			} elseif ($this->config->get('exchange1c_seo_manufacturer_'.$field) == 'import') {
//				$this->log('IMPORT');
				// из свойства которое считалось при обмене
			}
//			$this->log('Field name: '.$field);
			$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
//			$this->log('Field value: '.$data[$field]);
		}
		
//		$this->log($data);
		return $data;
	} // seoGenerateManufacturer()
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
		$sql  = isset($data['name'])				? ", name = '" . 				$this->db->escape($data['name']) . "'" 				: "";
		$sql .= isset($data['description'])			? ", description = '" . 		$this->db->escape($data['description']) . "'" 		: "";
		$sql .= isset($data['meta_title'])			? ", meta_title = '" . 			$this->db->escape($data['meta_title']) . "'" 		: "";
		$sql .= isset($data['meta_h1'])				? ", meta_h1 = '" . 			$this->db->escape($data['meta_h1']) . "'" 			: "";
		$sql .= isset($data['meta_description'])	? ", meta_description = '" . 	$this->db->escape($data['meta_description']) . "'" 	: "";
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
		$sql .= isset($data['length']) 			? ", length = '" . (float)$data['length'] . "'"								: "";
		$sql .= isset($data['width']) 			? ", width = '" . (float)$data['width'] . "'"								: "";
		$sql .= isset($data['weight']) 			? ", weight = '" . (float)$data['weight'] . "'"								: "";
		$sql .= isset($data['height']) 			? ", height = '" . (float)$data['height'] . "'"								: "";
		$sql .= isset($data['status']) 			? ", status = '" . (int)$data['status'] . "'"								: "";
		$sql .= isset($data['noindex']) 		? ", noindex = '" . (int)$data['noindex'] . "'"								: "";
		$sql .= isset($data['tax_class_id']) 	? ", tax_class_id = '" . (int)$data['tax_class_id'] . "'"					: "";
		$sql .= isset($data['sort_order']) 		? ", sort_order = '" . (int)$data['sort_order'] . "'"						: "";
		$sql .= ", length_class_id = '" . (isset($data['length_class_id']) ? (int)$data['length_class_id'] 	: $this->config->get('config_length_class_id')) . "'";
		$sql .= ", weight_class_id = '" . (isset($data['weight_class_id']) ? (int)$data['weight_class_id'] 	: $this->config->get('config_weight_class_id')) . "'";
		return $sql;
	} // prepareQueryProduct()


	/**
	 * Формирует строку запроса для описания товара
	 */
	private function prepareStrQueryProductDescription($data) {
		$sql  = isset($data['name']) 				? ", name = '" . $this->db->escape($data['name']) . "'"								: "";
		$sql .= isset($data['meta_title']) 			? ", meta_title = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_keyword']) 		? ", meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['description']) 		? ", description = '" . $this->db->escape($data['description']) . "'" 				: "";
		$sql .= isset($data['meta_description']) 	? ", meta_description = '" . $this->db->escape($data['meta_description']) . "'" 	: "";
		$sql .= isset($data['tag']) 				? ", tag = '" . $this->db->escape($data['tag']) . "'" 								: "";
		return $sql;
	} //prepareStrQueryProductDescription()


	/**
	 * Формирует строку запроса для описания производителя
	 */
	private function prepareStrQueryManufacturerDescription($data) {
		$sql  = isset($data['description']) 		? ", description = '" . $this->db->escape($data['description']) . "'"				: "";
		$sql .= isset($data['name']) 				? ", name = '" . $this->db->escape($data['name']) . "'" 							: "";
		$sql .= isset($data['meta_description']) 	? ", meta_description = '" . $this->db->escape($data['meta_description']) . "'" 	: "";
		$sql .= isset($data['meta_keyword']) 		? ", meta_keyword = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['meta_title']) 			? ", meta_title = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_h1']) 			? ", meta_h1 = '" . $this->db->escape($data['meta_h1']) . "'" 						: "";
		return $sql;
	} //prepareStrQueryManufacturerDescription()


	/**
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories($product_id, $product_categories) {
		if (!$product_id) {
			$this->log("[ERROR] Заполнение родительскими категориями отменено, т.к. не указан product_id!");
			return false;
		}
		$sql = "DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = " . $product_id;
		$this->log($sql);
		$this->db->query($sql);
		
		// Подгружаем только один раз
		$this->load->model('catalog/product');
		
		//$this->log($product_categories, 'product_categories');
		foreach ($product_categories as $category_id) {
			$parents_id = array_merge($product_categories, $this->findParentsCategories($category_id));
			//$this->log($parents_id, 'parents_id:');
			foreach ($parents_id as $parent_id) {
				if ($parent_id != 0) {
					//$this->log('parent_id: ' . $parent_id);
					if (method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
						$this->db->query("INSERT INTO `" .DB_PREFIX . "product_to_category` SET product_id = " . $product_id . ", category_id = " . $parent_id . ", main_category = " . ($category_id == $parent_id ? 1 : 0));
					} else {
						$this->db->query("INSERT INTO `" .DB_PREFIX . "product_to_category` SET product_id = " . $product_id . ", category_id = " . $parent_id);
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
	 * Возвращает id по XML_ID
	 */
	private function getCategoryIdByXMLID($xml_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_to_1c` WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
		return isset($query->row['category_id']) ? $query->row['category_id'] : 0;
	} // getCategoryIdByXMLID()


	/**
	 * Возвращает массив id,name категории по XML_ID
	 */
	private function getCategoryByXMLID($xml_id) {
		$query = $this->db->query("SELECT c.category_id, cd.name FROM `" . DB_PREFIX . "category_to_1c` c LEFT JOIN `" . DB_PREFIX. "category_description` cd ON (c.category_id = cd.category_id) WHERE c.1c_id = '" . $this->db->escape($xml_id) . "' AND cd.language_id = '" . $this->LANG_ID . "'");
		return $query->rows;
	} // getCategoryByXMLID()


	/**
	 * Поиск товара по XML_ID
	 */
	private function getProductByXMLID($xml_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_to_1c` WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
		return isset($query->row['product_id']) ? $query->row['product_id'] : 0;
	} // getProductByXMLID()


	/**
	 * Обновляет категорию
	 */
	private function updateCategory($data) {

		// При необходимости меняем родителя
		$sql = "SELECT parent_id FROM `" . DB_PREFIX . "category` WHERE category_id = '" . (int)$data['category_id'] . "'";
		//$this->log($sql);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$parent_id = $query->row['parent_id'];
			if ($data['parent_id'] <> $parent_id){
				$data['parent_id'] = $parent_id;
				$sql = $this->prepareStrQueryCategory($data);
				$sql = "UPDATE `" . DB_PREFIX . "category` SET parent_id = '" . (int)$data['parent_id'] . "'" . $sql . ", date_modified = NOW() WHERE category_id = '" . (int)$data['category_id'] . "'";
				$this->log($sql);
				$query = $this->db->query($sql);
			}
		}
		
		// При необходимости меняем название
		$sql = "SELECT name FROM `" . DB_PREFIX . "category_description` WHERE category_id = '" . (int)$data['category_id'] . "'";
		//$this->log($sql);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$name = $query->row['name'];
			if ($data['name'] <> $name) {
				$this->log('Старое название категории: ' . $name . ', новое: ' . $data['name']);
				// Изменилось название категории
				$sql = $this->prepareStrQueryCategoryDesc($data);
				$sql = "UPDATE IGNORE `" . DB_PREFIX . "category_description` SET category_id = '" . (int)$data['category_id'] . "', language_id = '" . (int)$this->LANG_ID . "'" . $sql;
				$this->log($sql);
				$query = $this->db->query($sql);
				// Обновляем дату модификации категории
				$sql = "UPDATE `" . DB_PREFIX . "category` SET date_modified = NOW() WHERE category_id = '" . (int)$data['category_id'] . "'";
				$this->log($sql);
				$query = $this->db->query($sql);
				$this->log("Категория обновлена: '" . $data['name'] . "', id: " . $data['category_id'] . ", Ид: " . $data['xmlid']);
			} else {
				$this->log("Категория не нуждается в обновлении: '" . $data['name'] . "', id: " . $data['category_id']);
			}
		}

		$this->cache->delete('category');
		
	} // updateCategory()
	

	/**
	 * Добавляет категорию
	 */
	private function addCategory($data) {
		if ($data == false) return 0;
		$sql = $this->prepareStrQueryCategory($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "category` SET parent_id = '" . (int)$data['parent_id'] . "'" . $sql . ", date_modified = NOW(), date_added = NOW()";
		//$this->log($sql);
		$this->db->query($sql);

		$category_id = $this->db->getLastId();
		
		// Описание
		$sql = $this->prepareStrQueryCategoryDesc($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "category_description` SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$this->LANG_ID . "'" . $sql;
		//$this->log($sql);
		$this->db->query($sql);

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");
		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");
			$level++;
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int)$category_id . "', path_id = '" . (int)$category_id . "', level = '" . (int)$level . "'");
		
		// Магазин
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// Чистим кэш
		$this->cache->delete('category');
		
		// Добавим линк
		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_to_1c` SET category_id = " . $category_id . ", 1c_id = '" . $this->db->escape($data['xmlid']) . "'");
		
//		// SEO
//		if (isset($data['seo_url'])) {
//			$this->setSeoURL('category_id', $category_id, $data['seo_url']);
//		}

		$this->cache->delete('category');
		
		$this->log("Категория добавлена: '" . $data['name'] . "', id: " . $category_id . ", Ид: " . $data['xmlid']);
		
		return $category_id;
	} // addCategory()


	/**
	 * Обрабатывает категории
	 */
	private function parseCategories($xml, $parent_id=0) {
		foreach ($xml->Группа as $category){
			if (isset($category->Ид) && isset($category->Наименование) ){
				$data = array();
				$data['xmlid']			= (string)$category->Ид;
				$data['name']			= (string)$category->Наименование;
				$data['category_id']	= $this->getCategoryIdByXMLID($data['xmlid']);
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
		$this->log('==> addProduct()');
		// товар
		$sql = $this->prepareQueryProduct($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "product` SET date_added = NOW()" . $sql;
		//$this->log($sql);
		$this->db->query($sql);
		
		$product_id = $this->db->getLastId();
		
		// описание (пока только для одного языка)
		$sql = $this->prepareStrQueryProductDescription($data);
		$sql = "INSERT INTO `" . DB_PREFIX . "product_description` SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$this->LANG_ID . "'" . $sql;
		//$this->log($sql);
		$this->db->query($sql);
		
		// категории продукта
		$main_category = $this->existField("product_to_category", "main_category", 1);
		
		if (isset($data['product_categories'])) {
			foreach ($data['product_categories'] as $category_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'" . $main_category);
				$this->log("> Товар добавлен в категорию id: " . $category_id);
			}
		}
		
		// магазин
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		// Связь с 1С
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_1c` SET product_id = '" . (int)$product_id . "', 1c_id = '" . $data['product_xmlid'] . "'");

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
		$this->log('==> updateProductDescription()');
		//$this->log($data);
		$sql = $this->prepareStrQueryProductDescription($data);
//		$this->log('updateProductDescription: '.$sql);
		if ($sql){
			$sql = "UPDATE `" . DB_PREFIX . "product_description` SET language_id = '" . (int)$this->LANG_ID . "'" . $sql . " WHERE product_id = '" . (int)$product_id . "'";
			//$this->log($sql);
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
		$this->log('==> updateProduct()');

		// Обнуляем остаток только у тех товаров что загружаются 
		if ($this->config->get('exchange1c_flush_quantity')) {
			$data['quantity'] = 0;
		}

		// ФИЛЬТР ОБНОВЛЕНИЯ
		$update_filelds = $this->config->get('exchange1c_product_fields_update');
		// Удаление полей которые обновлять не нужно
//		$this->log($update_filelds);
		if (!isset($update_filelds['name'])) {
			unset($data['name']);
			$this->log("[i] Обновление названия отключено");
		}
		if (!isset($update_filelds['category'])) {
			unset($data['product_category']);
			$this->log("[i] Обновление категорий отключено");
		}
		// КОНЕЦ ФИЛЬТРА

		$this->log($data);
		$sql = $this->prepareQueryProduct($data);
//		$this->log('prepareQueryProduct: '.$sql);
		if ($sql) {
			$sql = "UPDATE `" . DB_PREFIX . "product` SET date_modified = NOW()" . $sql . " WHERE product_id = '" . (int)$product_id . "'";
			$this->log($sql);
			$this->db->query($sql);
		} else {
			$this->log("[i] Товар не нуждается в обновлении");
		}
		
		$this->updateProductDescription($data, $product_id);
		
		// категории
		$main_category = $this->existField("product_to_category", "main_category", 1);
		
		if (isset($data['product_categories'])) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE product_id = '" . (int)$product_id . "'");
			foreach ($data['product_categories'] as $category_id) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_category` SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'" . $main_category);
			}
		} else {
			$this->log("[i] Категории товара не нуждаются в обновлении");
		}
		
		// магазин
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_to_store` WHERE product_id = '" . (int)$product_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$this->STORE_ID . "'");
		
		$this->cache->delete('product');
		
		$this->log("> Товар обновлен");
	} // updateProduct()

	
	/**
	 * Обновление или добавление товара
	 */
 	private function setProduct($data) {
		$this->log('==> setProduct()');
		
 		// Ищем товар...
 		$product_id = $this->getProductByXMLID($data['product_xmlid']);
		if (!$this->config->get('exchange1c_dont_use_artsync') && !$product_id && isset($data['sku'])) {
			$product_id = $this->getProductBySKU($data['sku']);
 		}
 		// Можно добавить поиск по наименованию или другим полям...
		
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
						$data['name'] = $data['full_name'];
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
//		$this->log($update_filelds);
		if (!isset($update_filelds['images'])) {
			$this->log("[i] Обновление картинок отключено!");
			return true;
		}
		
		$sql = "DELETE FROM `" . DB_PREFIX . "product_image` WHERE product_id = '" . (int)$product_id . "'";
		$this->log($sql);
		$this->db->query($sql);
		foreach ($xml as $image) {

			$image = (string)$image;
			$this->log('Картинка: ' . $image);
			
			if (empty($image)) {
				continue;
			}
			
			$full_image = DIR_IMAGE . $image;
			
			if (file_exists($full_image)) {
				// не картинки обрабатываем тут
				$this->log('Расположение картинки: ' . $full_image);
				if (getimagesize($full_image) == NULL) {
					if (!$this->setFile($full_image, $product_id)) {
						$this->log("Файл '" . $image . "' не является картинкой");
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
//				$this->log($sql);
				$this->db->query($sql);
				//$this->log("> Картинка основная: '" . $newimage . "'");
			}
			// дополнительные картинки
			else {
				$sql = "INSERT INTO `" . DB_PREFIX . "product_image` SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape($newimage) . "', sort_order = '" . (int)$index . "'";
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
	private function setAttribute($xml_id, $attribute_group_id, $name, $sort_order) {

		// Ищем свойства по 1С Ид
		$query = $this->db->query("SELECT attribute_id FROM `" . DB_PREFIX . "attribute_to_1c` WHERE 1c_id = '" . $this->db->escape($xml_id) . "'");
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
		$this->db->query("INSERT INTO `" .  DB_PREFIX . "attribute_to_1c` SET attribute_id = '" . (int)$attribute_id . "', 1c_id = '" . $this->db->escape($xml_id) . "'");
			
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
	} // parseAttributesValues()


	/**
	 * Загружает атрибуты (Свойства из 1С)
	 */
	private function parseAttributes($xml) {
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
			$xml_id		= (string)$property->Ид;
			$name 	= trim((string)$property->Наименование);
			
			if ($name == 'Производитель') {
				$values = $this->parseAttributesValues($property);
				foreach ($values as $val_xml_id=>$value) {
					$manufacturer_id = $this->setManufacturer($value, $val_xml_id);
				}
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
	private function setAttributes($xml, $attributes, $product_id, $data) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE product_id = '" . (int)$product_id . "'");
		foreach ($xml->ЗначенияСвойства as $property) {
			// если есть значения
			$xml_id = (string)$property->Ид;
			
			// Проверим загружалось ли свойство в классификаторе, а то были случаи...
			if (!isset($attributes[$xml_id])) {
				$this->log('Свойство с Ид ' . $xml_id . ' не было загружено в классификаторе');
				return 1;
			}
			
			$name 	= trim($attributes[$xml_id]['name']);
			$value 	= trim((string)$property->Значение);
			
			if ($value) {
				if ($attributes[$xml_id]) {
					// агрегатный тип
					if (isset($attributes[$xml_id]['values'])) {
						$value = trim($attributes[$xml_id]['values'][$value]);
					}
				}
			}

			if ($name == 'Производитель' && !empty($value)) {
				if (isset($data['manufacturer_xmlid']))
					continue;
				$manufacturer_id = $this->setManufacturer($value);
				$sql = "UPDATE `" . DB_PREFIX . "product` SET manufacturer_id = '" . $manufacturer_id. "' WHERE product_id = '" . (int)$product_id . "'";
				$this->log($sql);
				$this->db->query($sql);
				$this->log("> Производитель (из свойства): " . $value);
				continue;
			}

			if ($value) {
				$this->log("> Свойство '" . $name . "' : '" . $value . "'");
				// Добавим в товар
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$attributes[$xml_id]['attribute_id'] . "', language_id = '" . (int)$this->LANG_ID . "', text = '" .  $this->db->escape($value) . "'");
			}
		}
		return 1;
	} // setAttributes()

	
	/**
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($data, $manufacturer_id) {
		$sql = "SELECT name FROM `" . DB_PREFIX . "manufacturer` WHERE manufacturer_id = '" . (int)$manufacturer_id . "'";
		$this->log($sql);
		$query = $this->db->query($sql);
		
		if (isset($query->row['name'])) {
			$name_old = $query->row['name'];

			if ($name_old == $data['name']) {
				$this->log("> Не требуется обновление производителя '" . $data['name'] . "'");
				return;
			}
		} 
		
		// Обновляем
		$sql  = " name = '" . $this->db->escape($data['name']) . "'";
		$sql .= isset($data['noindex']) ? ", noindex = '" . (int)$data['noindex'] . "'" : "";
		$sql = "UPDATE `" . DB_PREFIX . "manufacturer` SET " . $sql . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "'";
		$this->log($sql);
		$query = $this->db->query($sql);
		
//		if (version_compare(VERSION,'2.0.3.1', '<')) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_description'");
		if ($query->num_rows) {
			$sql = $this->prepareStrQueryManufacturerDescription($data);
			if ($sql) {
				$sql = "UPDATE `" . DB_PREFIX . "manufacturer_description` SET" . $sql . " WHERE manufacturer_id = '" . (int)$manufacturer_id . "' AND language_id = '" . $this->LANG_ID . "'";
				$this->log($sql);
				$this->db->query($sql);
				$this->log("> Обновлено описане производителя '" . $data['name'] . "'");
			} else {
				$this->log("> Не требуется обновлять описание производителя '" . $data['name'] . "'");
			} 
		}
		
		$this->log("> Производитель '" . $data['name'] . "' обновлен");
	} // updateManufacturer()
	

	/**
	 * Добавляем производителя
	 */
	private function addManufacturer($data) {
		$sql 	 = " name = '" . $this->db->escape($data['name']) . "'";
		$sql 	.= isset($data['sort_order']) ? ", sort_order = '" . (int)$data['sort_order'] . "'" : "";
		$sql 	.= isset($data['image']) ? ", image = '" . (int)$data['image'] . "'" : ", image = ''";
		$sql 	.= isset($data['noindex']) ? ", noindex = '" . (int)$data['noindex'] . "'" : "";
		$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer` SET" . $sql;
//		$this->log($sql);
		$query = $this->db->query($sql);

		$manufacturer_id = $this->db->getLastId();

//		if (version_compare(VERSION, '2.0.3.1', '<')) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "manufacturer_description'");
		if ($query->num_rows) {
			$sql = $this->prepareStrQueryManufacturerDescription($data);
			if ($sql) {
				$sql = "INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET manufacturer_id = '" . (int)$manufacturer_id . "', language_id = '" . (int)$this->LANG_ID . "'" . $sql;
				$this->log($sql);
				$this->db->query($sql);
			}
		}
		
		if (isset($data['xmlid'])) {
			// добавляем связь
			$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_1c` SET 1c_id = '" . $this->db->escape($data['xmlid']) . "', manufacturer_id = '" . (int)$manufacturer_id . "'";
			$this->log($sql);
			$this->db->query($sql);
		}

		$sql 	= "INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$this->STORE_ID . "'";
		$this->log($sql);
		$this->db->query($sql);
		
		$this->log("> Производитель '" . $data['name'] . "' добавлен, id: " . $manufacturer_id);
		return $manufacturer_id; 
	} // addManufacturer()


	/**
	 * Устанавливаем производителя
	 */
	private function setManufacturer($name, $xmlid='') {
		$data = array();
		$data['name']			= htmlspecialchars($name);
		$data['description'] 	= 'Производитель ' . $data['name'];
		$data['sort_order']		= 1;
		$data['xmlid']			= $xmlid;		

		if ($this->existField("manufacturer", "noindex")) {
			$data['noindex'] = 1;	// значение по умолчанию
		}

		if ($xmlid) {
			// Поиск (производителя) изготовителя по 1C Ид
			$where = "mc.1c_id = '" . $this->db->escape($xmlid) . "'";
		} else {
			// Поиск по имени
			$where = "m.name LIKE '" . $this->db->escape($data['name']) . "'";
		}
		$where .= " AND ms.store_id = '" . $this->STORE_ID . "'";
		
		// Если есть таблица manufacturer_description тогда нужно условие  
		// AND language_id = '" . $this->LANG_ID . "'

		$sql 	= "SELECT m.manufacturer_id FROM `" . DB_PREFIX . "manufacturer_to_1c` mc LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (mc.manufacturer_id = ms.manufacturer_id) LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.manufacturer_id = mc.manufacturer_id) WHERE " . $where;
		$this->log($sql);
		$query = $this->db->query($sql);
		if ($query->num_rows) {
			$manufacturer_id = $query->row['manufacturer_id'];
		}
		
//		$this->log($data, 'Данные производителя');

		if (!isset($manufacturer_id)) {
			// Создаем
			$manufacturer_id = $this->addManufacturer($data);
		} else {
			// Обновляем
			$this->updateManufacturer($data, $manufacturer_id);
		}

//		$this->log("> Производитель: '" . $data['name'] . "'");
		return $manufacturer_id;
	} // setManufacturer()


	/**
	 * Обрабатывает единицу измерения
	 * в стадии разработки
	 */
	private function parseUnit($xml) {
		//$this->log($xml, 'parseUnit()');
		return array();
	}


	/**
	 * Обрабатывает товары
	 */
	private function parseProducts($xml, $classifier) {
		if ($this->existField("product", "noindex")) {
			$noindex = 1;
		}
		$default_stock_status = $this->config->get('exchange1c_default_stock_status');

		foreach ($xml->Товар as $product){
			if (isset($product->Ид) && isset($product->Наименование) ){
				$data = array();

				$xmlid = explode("#", (string)$product->Ид);
				//$this->log($xmlid);
				$data['product_xmlid'] = $xmlid[0];
				$data['feature_xmlid'] = isset($xmlid[1]) ? $xmlid[1] : '';

				$data['mpn']				= $data['product_xmlid'];
				$data['name']				= htmlspecialchars((string)$product->Наименование);

				if ($product->Артикул) {
					$data['model']			= htmlspecialchars((string)$product->Артикул);
					$data['sku']			= htmlspecialchars((string)$product->Артикул);
				}

				$data['ean'] = $product->Штрихкод ? ((string)$product->Штрихкод) : '';

				$data['status']				= 1;
				if (isset($noindex)) {
					$data['noindex']		= 1; // не во всех версиях
				}
//				$this->log($data);
				$this->log("------------------------------");
				$this->log("Товар '" . $data['name'] . "'");
				
				if ($product->БазоваяЕдиница) {
					$data['unit'] = $this->parseUnit($product->БазоваяЕдиница);
				}
				
				if ($product->ПолноеНаименование) {
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
				if ($product->Группы) {
					$data['product_categories']	= array();
					foreach ($product->Группы->Ид as $category_xml_id) {
						$data['product_categories'][] = $this->getCategoryIdByXMLID((string)$category_xml_id);
					}
				}

				// изготовитель 
				if ($product->Изготовитель) {
					$this->log("Загрузка производителя из реквизита Изготовитель");
					$data['manufacturer_name'] = (string)$product->Изготовитель->Наименование;
					$data['manufacturer_xmlid'] = (string)$product->Изготовитель->Ид;
					$data['manufacturer_id'] = $this->setManufacturer($data['manufacturer_name'], $data['manufacturer_xmlid']);
				}

				if ($default_stock_status) {
					$data['stock_status_id'] = $default_stock_status;
				}
				
				// записываем или обновляем товар в базе
				$product_id = $this->setProduct($data);
				
				if ($this->config->get('exchange1c_fill_parent_cats'))
					$this->fillParentsCategories($product_id, $data['product_categories']);
				
				// картинки
				if ($product->Картинка) {
					if (!$this->parseImages($product->Картинка, $product_id)) {
						$this->log('[ERROR] parseProducts(): Ошибка загрузки картинок!');
						return false;
					}
				}
				
				// Свойства
				if ($product->ЗначенияСвойств) {
					if (!$this->setAttributes($product->ЗначенияСвойств, $classifier['attributes'], $product_id, $data)) {
						$this->log('[ERROR] parseProducts(): Ошибка загрузки свойств!');
						return false;
					}
				}
				
				//$this->log('Данные товара перед характеристиками:');
				//$this->log($data);
				
				// Характеристики
				if ($product->ХарактеристикиТовара) {
					$this->log('Загружаются характеристики при обработке товара...');
					$this->parseFeature($product->ХарактеристикиТовара, $product_id, $data);
				}

				unset($product);
				unset($data);
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
		$directory['xmlid']			= (string)$xml->Ид;
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
		$this->db->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET name = '" . $this->db->escape($name) . "', 1c_id = '" . $this->db->escape($xml_id) . "'");
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
	 * Устанавливает остаток товара
	 */
	private function setProductQuantity($product_id, $quantity, $warehouse_id='', $product_feature_id='') {
		
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE product_id ='" . $product_id . "'";
		$sql .= $warehouse_id ? " AND warehouse_id ='" . $warehouse_id . "'" : "";
		$sql .= $product_feature_id ? " AND product_feature_id ='" . $product_feature_id . "'" : "";
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			$quantity_old = $query->row['quantity'];
		}
		
		if (isset($quantity_old)) {
			if ($quantity_old <> $quantity) {
				$sql = "UPDATE `" . DB_PREFIX . "product_quantity` SET product_id ='" . $product_id . "', quantity = '" . $quantity . "'";
				$sql .= $warehouse_id ? ", warehouse_id ='" . $warehouse_id . "'" : "";
				$sql .= $product_feature_id ? ", product_feature_id ='" . $product_feature_id . "'" : "";
				$this->db->query($sql);
			}
		} else {
			$sql = "INSERT INTO `" . DB_PREFIX . "product_quantity` SET product_id ='" . $product_id . "', quantity = '" . $quantity . "'";
			$sql .= $warehouse_id ? ", warehouse_id ='" . $warehouse_id . "'" : "";
			$sql .= $product_feature_id ? ", product_feature_id ='" . $product_feature_id . "'" : "";
			$this->db->query($sql);
		}
		
	}


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
		
		$sql = "UPDATE `" . DB_PREFIX . "product` SET quantity = '" . $quantity . "' WHERE product_id = '" . (int)$product_id . "'";
		$this->db->query($sql);
		
	} // setQuantity()

	
	/**
	 * Загружает остатки по складам
	 */
	private function parseQuantityWarehouse($xml, $offers_pack, $product_id, $product_feature_id = '') {
		$quantity = 0;
		foreach ($xml as $warehouse) {
			$xml_id = (string)$warehouse['ИдСклада'];
			if (isset($offers_pack['warehouses'][$xml_id])) {
				$this->db->query("DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE product_id = '" . (int)$product_id . "' AND product_feature_id = '" . $product_feature_id . "' AND warehouse_id = '" . (int)$offers_pack['warehouses'][$xml_id]['warehouse_id'] . "'");
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_quantity` SET product_id = '" . (int)$product_id . "', product_feature_id = '" . $product_feature_id . "', warehouse_id = '" . (int)$offers_pack['warehouses'][$xml_id]['warehouse_id'] . "', quantity = '" . (float)$warehouse['КоличествоНаСкладе'] . "'");
				$this->log("> Остаток на складе '" . $offers_pack['warehouses'][$xml_id]['name'] . "': " . (float)$warehouse['КоличествоНаСкладе']);
			}
			$quantity += (float)$warehouse['КоличествоНаСкладе'];
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

		foreach ($config_price_type as $config_type) {
			foreach ($xml->ТипЦены as $price_type)  {
				$currency		= isset($price_type->Валюта) ? (string)$price_type->Валюта : "RUB";
                $currency_id	= 0;
				$xml_id			= (string)$price_type->Ид;
			 	$name			= trim((string)$price_type->Наименование);
				if (strtolower($name) == strtolower($config_type['keyword'])) {
					$data[$xml_id] = $config_type;
					$data[$xml_id]['currency'] = $currency;
					$this->log('Найдена цена: ' . $name);
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
	private function setProductPrice($product_id, $data, $data_price) {
		
		if (!isset($data['product_feature_id'])) {
			// Цена бех характеристики
			// Группа покупателей по-умолчанию
			$default_customer_group_id = $this->config->get('config_customer_group_id');
			
			if ($default_customer_group_id == $data_price['customer_group_id']) {
				// Обновляем основную цену в товаре
				$sql = "UPDATE `" . DB_PREFIX . "product` SET price = '" . $data_price['value'] . "' WHERE product_id = '" . (int)$product_id . "'";
				$this->log($sql);
				$this->db->query($sql);
			} else {
				// Есть ли цена
				$sql = "SELECT product_discount_id FROM `" . DB_PREFIX . "product_discount` WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . $data_price['customer_group_id'] . "'";
				$this->log($sql);
				$query = $this->db->query($sql);
				
				if ($query->num_rows) {
					// Обновляем
					$sql = "UPDATE `" . DB_PREFIX . "product_discount` SET quantity = '" . $data_price['quantity'] . "', priority = '" . $data_price['priority'] . "' WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . $data_price['customer_group_id'] . "'";
					$this->log($sql);
					$query = $this->db->query($sql);
				} else {
					// Добавляем
					$sql = "INSERT INTO `" . DB_PREFIX . "product_discount` SET product_id = '" . $product_id . "', customer_group_id = '" . $data_price['customer_group_id'] . "', quantity = '" . $data_price['quantity'] . "', priority = '" . $data_price['priority'] . "'";
					$this->log($sql);
					$query = $this->db->query($sql);
				}
			}
		}

		// Есть ли старая цена
		$sql = "SELECT * FROM `" . DB_PREFIX . "product_price` WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . $data_price['customer_group_id'] . "'";
		$sql .= isset($data['product_feature_id']) ? " AND product_feature_id = '" . $data['product_feature_id'] . "'" : ""; 
		$this->log($sql);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
        	if ($query->row['price'] <> $data_price['value']) {
				$sql = "UPDATE `" . DB_PREFIX . "product_price` SET price = '" . $data_price['value'] . "' WHERE product_id = '" . $product_id . "' AND customer_group_id = '" . $data_price['customer_group_id'] . "'";
				$sql .= isset($data['product_feature_id']) ? " AND product_feature_id = '" . $data['product_feature_id'] . "'" : ""; 
				$this->log($sql);
				$query = $this->db->query($sql);
        	}
			return;
		}

		// Добавляем
		$sql = "INSERT INTO `" . DB_PREFIX . "product_price` SET product_id = '" . $product_id . "', price = '" . $data_price['value'] . "', customer_group_id = '" . $data_price['customer_group_id'] . "'";
		$sql .= isset($data['product_feature_id']) ? ", product_feature_id = '" . $data['product_feature_id'] . "'" : ""; 
		$this->log($sql);
		$query = $this->db->query($sql);
		
		return;
		
	} // setProductFeaturePrice()
	
	
	/**
	 * Загружает все цены только в одной валюте
	 */
	private function parsePrices($xml, $offers_pack, $product_id, $data) {

		foreach ($xml->Цена as $price) {

			$xml_id	= (string)$price->ИдТипаЦены;
			
			if (isset($offers_pack['price_types'][$xml_id])) {
				// Найдена цена
				
				$data_price = $offers_pack['price_types'][$xml_id];
		 		$data_price['value'] 	= (float)$price->ЦенаЗаЕдиницу;
		 		$data_price['quantity'] = (float)$price->Коэффициент;
		 		$data_price['unit_name'] = (string)$price->Единица;
		 		$data_price['name'] = (string)$price->Представление;

				$this->setProductPrice($product_id, $data, $data_price);
				
			} else {
				
				$this->log('[i] Не найдена в настройках вид цен с Ид: ' . $xml_id);
			}
 		}

		unset($xml);			
 	} // parsePrices()


	/**
	 * ХАРАКТЕРИСТИКИ
	 */


	/**
	 * Поиск опции по названию
	 */
	private function getOptionByName($name) {
		
		$sql = "SELECT option_id FROM `" . DB_PREFIX . "option_description` WHERE language_id = '" . $this->LANG_ID . "' AND name = '" . $name . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
 			$this->log('option_id = ' . $query->row['option_id']);
        	return $query->row['option_id'];
        } 

       	return 0;
		
	} // getOptionByName()


	/**
	 * Поиск значения опции по названию
	 */
	private function getOptionValueByName($option_id, $name) {
		
		$sql = "SELECT option_value_id FROM `" . DB_PREFIX . "option_value_description` WHERE language_id = '" . $this->LANG_ID . "' AND option_id = '" . $option_id . "' AND name = '" . $name . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
 			$this->log('option_value_id = ' . $query->row['option_value_id']);
        	return $query->row['option_value_id'];
       	}
		
		return 0;
		
	} // getOptionValueByName()


	/**
	 * Добавляет опциию по названию
	 */
	private function addOption($name, $type = 'select') {
		
		$sql = "INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $type . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);

		$option_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_description` SET option_id = '" . $option_id . "', language_id = '" . $this->LANG_ID . "', name = '" . $name . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		return $option_id;  
	
	} // addOption()


	/**
	 * Добавляет или получает значение опциию по названию
	 */
	private function setOptionValue($option_id, $value, $image = '') {
		
		// Проверим есть ли такое значение
		$option_value_id = $this->getOptionValueByName($option_id, $value);
		
		if ($option_value_id)
			return $option_value_id;
		
		$sql = "INSERT INTO `" . DB_PREFIX . "option_value` SET option_id = '" . $option_id . "', image = '" . $image . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);

		$option_value_id = $this->db->getLastId();

		$sql = "INSERT INTO `" . DB_PREFIX . "option_value_description` SET option_id = '" . $option_id . "', option_value_id = '" . $option_value_id . "', language_id = '" . $this->LANG_ID . "', name = '" . $value . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		return $option_value_id;  
	
	} // setOptionValue()


	/**
	 *  Добавляет или находит опцию в товаре и возвращает ID
	 */
	private function setProductOption($product_id, $option_id) {
		
		// Проверяем опцию в товаре
		$sql = "SELECT product_option_id FROM `" . DB_PREFIX . "product_option` WHERE product_id = '" . $product_id . "' AND option_id = '" . $option_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
        	return $query->row['product_option_id'];
       	}
       	
       	// Добавляем опцию в товар
		$sql = "INSERT INTO `" . DB_PREFIX . "product_option` SET product_id = '" . $product_id . "', option_id = '" . $option_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		$product_option_id = $this->db->getLastId();

		return $product_option_id;
		
	} // setProductOption()


	/**
	 * Находит или добавляет значение опции в товар
	 */
	private function setProductOptionValue($product_id, $product_option_id, $option_id, $value_id) {
		
		// Проверяем значение опциии в товаре
		$sql = "SELECT product_option_value_id FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '" . $product_id . "' AND product_option_id = '" . $product_option_id . "' AND option_id = '" . $option_id . "' AND option_value_id = '" . $value_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
        if ($query->num_rows) {
        	return $query->row['product_option_value_id'];
       	}
       	
       	// Добавляем значение опции в товар
		$sql = "INSERT INTO `" . DB_PREFIX . "product_option_value` SET product_option_id = '" . $product_option_id . "', product_id = '" . $product_id . "', option_id = '" . $option_id . "', option_value_id = '" . $value_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		$product_option_value_id = $this->db->getLastId();

		return $product_option_value_id;
		
	} // setProductOptionValue()


	/**
	 * Находит характеристику товара
	 */
	private function getProductFeature($feature_xmlid) {
		
		// Ищем характеристику по Ид
		$sql = "SELECT product_feature_id FROM `" . DB_PREFIX . "product_feature` WHERE 1c_id = '" . $feature_xmlid . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			return $query->row['product_feature_id'];
		}
		
		return 0;
	} // getProductFeature()
	

	/**
	 * Находит и обновляет или добавляет характеристику товара
	 */
	private function setProductFeature($product_id, $feature_name, $data) {
		
		// Ищем характеристику по Ид
		$sql = "SELECT product_feature_id FROM `" . DB_PREFIX . "product_feature` WHERE 1c_id = '" . $data['feature_xmlid'] . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			
			$product_feature_id = $query->row['product_feature_id'];
			
			// Обновляем, вдруг там что-то изменилось, пока не проверяем, если будет необходимость сделаем проверку
			$sql = "UPDATE `" . DB_PREFIX . "product_feature` SET product_id = '" . $product_id . "', name = '" . $feature_name . "', ean =  '" . $data['ean'] . "' WHERE 1c_id = '" . $data['feature_xmlid'] . "'";
	 		$this->log($sql);
			$query = $this->db->query($sql);

//			// Если характеристики удалялись из 1С, то мы не будем проверять какие есть в 1С а какие в базе, просто удалим и заново запишем значения
//			// Удаляем все значения характеристик
//			$sql = "DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE product_feature_id = '" . $product_feature_id . "'";
//	 		$this->log($sql);
//			$query = $this->db->query($sql);
		
			return $product_feature_id;  
       	}
       	
       	// Добавим характеристику
 		$sql = "INSERT INTO `" . DB_PREFIX . "product_feature` SET product_id = '" . $product_id . "', 1c_id = '" . $data['feature_xmlid'] . "', name = '" . $feature_name . "', ean = '" . $data['ean'] . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		$product_feature_id = $this->db->getLastId();

		return $product_feature_id;
		
	} // setProductFeature()


	/**
	 * Ищет, проверяет, добавляет значение характеристики товара
	 */
	private function setProductFeatureValue($product_feature_id, $option_id, $option_value_id) {
		
		// Поищем такое значение
		$sql = "SELECT option_id FROM `" . DB_PREFIX . "product_feature_value` WHERE product_feature_id = '" . $product_feature_id . "' AND option_value_id = '" . $option_value_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		if ($query->num_rows) {
			return 1;
		}
		
       	// Добавим значение
		$sql = "INSERT INTO `" . DB_PREFIX . "product_feature_value` SET product_feature_id = '" . $product_feature_id . "', option_id = '" . $option_id . "', option_value_id = '" . $option_value_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		return 2;
       	
	} // setProductFeatureValue()

	/**
	 * Установка опции
	 */
	private function setOption($name, $value, $product_id, $product_feature_id) {

		// Поищем такую опцию в базе и добавим значение, если нету.
		$option_id = $this->getOptionByName($name);
		if ($option_id) {
			// Добавить значение
			$option_value_id = $this->setOptionValue($option_id, $value);
		} else {
			// Новая опция
			$option_id = $this->addOption($name);
			$option_value_id = $this->setOptionValue($option_id, $value);
		}

		// Устанавливаем опцию и значение в товар
		$product_option_id = $this->setProductOption($product_id, $option_id);
		if ($product_option_id) {
			$product_option_value_id = $this->setProductOptionValue($product_id, $product_option_id, $option_id, $option_value_id);
		}
		$this->log('->product_option_id = ' . $product_option_id);
		$this->log('->product_option_value_id = ' . $product_option_value_id); 

		// Устанавливаем значение опции в характеристику товара 
		$this->setProductFeatureValue($product_feature_id, $option_id, $option_value_id);
		
	} // setOption()


	/**
	 * Разбор характеристик
	 */
	private function parseFeature($xml, $product_id, $data) {
		
		if (!$xml) return 0;
		
		// Удалим старые характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		// Удалим старые значения характеристики
		$sql = "DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);

		// Удалим старые опции
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		// Удалим старые значения опции
		$sql = "DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE product_id = '" . $product_id . "'";
 		$this->log($sql);
		$query = $this->db->query($sql);
		
		// Формируется из доп. свойств или же из названия, при отстутствии доп. свойств
		$features = array();
		$feature_name = '';
		$option_name = '';
		$options = array();
		
		// Обрабатываем все доп. свойства характеристики и записываем в массив, формируем название для характеристики, типа: "Наименование: Значение, Наименование: Значение, ..."
		foreach ($xml->ХарактеристикаТовара as $productFeature){
			
			// в версии 2.07 или старше это Ид характеристики в Предложении, 
			$feature_xmlid = isset($productFeature->Ид) ? (string)$productFeature->Ид : '';
			// Если есть этот Ид значит все что ниже это одна характеристика
			
			// Получаем название доп. свойства и значение его, отсекаем лишние пробелы с начала и конца
			$name = trim((string)$productFeature->Наименование);
			$value = trim((string)$productFeature->Значение);

			// Название характеристики из значений через запятую 
			$feature_name 	.= (empty($feature_name) ? '' : ', ') . $value;
			// Название опции
			$option_name 	.= (empty($option_name) ? '' : ', ') . $name;
			
			if ($feature_xmlid) {

				// Должна быть в import - это Ид характеристики товара в offers.
				// Встречается в версии 2.07
				$features[$feature_xmlid] = array(
					'name'		=> $name,
					'value'		=> $value
				);
				
			} else {
				
				// Обычно так загружается из offers v2.03
				if ($this->config->get('exchange1c_product_option_mode') == 'related') {
					// Если связанные опции
					$options[] = array(
						'name'		=> $name,
						'value'		=> $value
					);
				}
				
				 
			}
			
		}

		$feature_name = "Характеристика";
		if ($this->config->get('exchange1c_product_option_mode') == 'combine') {
			$options[] = array(
				'name'		=> $option_name,
				'value'		=> $feature_name
			);
			$this->log($options, "2271 Опция");
		}
			
		if (count($features)) {
			foreach ($features as $feature_xmlid => $feature) {
				$data['feature_xmlid'] = $feature_xmlid;
				$product_feature_id = $this->setProductFeature($product_id, $feature['name'], $data);
				$this->setOption($feature_name, $feature['value'], $product_id, $product_feature_id);
			}
			
		} else {
			
			// Найдем или добавим одну характеристику
			$product_feature_id = $this->setProductFeature($product_id, $feature_name, $data);
			foreach ($options as $option) {
				$this->setOption($option['name'], $option['value'], $product_id, $product_feature_id);
			}
		}
		
		return $product_feature_id;
		
	} // parseFeature())


	/**
	 * Разбор предложений
	 */
	private function parseOffers($xml, $offers_pack) {
//$this->log('parseOffers():$offers_pack');
//$this->log($offers_pack);
		if (!$xml->Предложение) return 1;
		
		foreach ($xml->Предложение as $offer){
			
			$data = array();
			
			$xmlid = explode("#", (string)$offer->Ид);
			$data['product_xmlid']	= $xmlid[0];
			if (isset($xmlid[1])) {
				$data['feature_xmlid'] = $xmlid[1];
			}
			
			$data['name']	= ($offer->Наименование) ? (string)$offer->Наименование : '';
			$product_id		= $this->getProductByXMLID($data['product_xmlid']); 		
			if (!$product_id)  {
				continue;
			}
			
			$this->log("------------------------------");
			$this->log("Товар '" . $data['name'] . "'");
			
			// Штрихкод
			$data['ean'] = ($offer->Штрихкод) ? (string)$offer->Штрихкод : '';
				
			if (isset($data['feature_xmlid'])) {
				// Характеристика товара есть

				$data['product_feature_id'] = 0;
				if (isset($offer->ХарактеристикиТовара)) {
					$data['product_feature_id'] = $this->parseFeature($offer->ХарактеристикиТовара, $product_id, $data);
				}
				
				if (!$data['product_feature_id']) {
					$data['product_feature_id'] = $this->getProductFeature($data['feature_xmlid']);
				}
				
//				if (!isset($data['product_feature_id'])) {
//					// Определим имя характеристики из наименования товара и добавим характеристику
//					$sql = "SELECT name FROM `" . DB_PREFIX . "product_description` WHERE product_id = '" . $product_id . "' AND language_id = '" . $this->LANG_ID . "'";
//					$this->log($sql);
//					$query = $this->db->query($sql);
//		
//					if ($query->num_rows) {
//						$old_name = $query->row['name'];
//						$feature_name = trim(str_replace($old_name, '', $data['name']));
//						$feature_name = substr($feature_name, 1, strlen($feature_name) - 2);
//						
//						if (empty($feature_name)){
//							$this->log('[!] Не удалось определить название характеристики, в название будет записан Ид характеристики: ' . $data['feature_xmlid']);
//							$feature_name = $data['feature_xmlid'];
//						}
//						
//						// Найдем или добавим характеристику по Ид в XML
//						$data['product_feature_id'] = $this->setProductFeature($product_id, $feature_name, $data);
//						$this->log('[i] Установлена характеристика с наименования, Ид характеристики: ' . $data['product_feature_id']);
//					} else {
//						$this->log('[ERROR] Товар по Ид: ' . $data['product_xmlid'] . ' не найден');
//						return 0;
//					}
//				}

				if ($offer->Цены) {
					$this->parsePrices($offer->Цены, $offers_pack, $product_id, $data);
				}

				// Количество характеристики
				if ($offer->Склад) {
					$data['quantity'] = $this->parseQuantityWarehouse($offer->Склад, $offers_pack, $product_id, $data['product_feature_id']);
				}
				
				if ($offer->Количество) {
					if (!isset($data['quantity'])) {
						$data['quantity'] = (int)$offer->Количество;
						$this->setProductQuantity($product_id, $data['quantity'], '', $data['product_feature_id']);
					}
				}
				
			} else {
				// Без характеристик

				$this->log("> Без характеристик");

				if ($offer->Цены) {
					$this->parsePrices($offer->Цены, $offers_pack, $product_id, $data);
				}
			
				$data['quantity'] = 0;
				// Общий остаток по всем складам
				if ($offer->Количество) {
					$data['quantity'] = (float)$offer->Количество;
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
			//$this->log("> Поиск заказов со статусом id: " . $params['exchange_status']);
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE date_added >= '" . $params['from_date'] . "'");
			//$this->log("> Поиск заказов с даты: " . $params['from_date']);
		}
		if ($query->num_rows) {
			foreach ($query->rows as $order_data) {
				
				//$this->log('order_data:');
				//$this->log($order_data);
				
				if ($order_data['order_status_id'] == $params['new_status']) {
					$this->log("> Cтатус заказа #" . $order_data['order_id'] . " не менялся.");
					//continue;
				}
					
				// Меняем статус
				$sql = "UPDATE " . DB_PREFIX . "order SET order_status_id = '" . $params['new_status'] . "' WHERE order_id = '" . $order_data['order_id'] . "'";
				//$this->log($sql);
				$query = $this->db->query($sql);
				$this->log("> Изменен статус заказа #" . $order_data['order_id']);
				// Добавляем историю в заказ
				$sql = "INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . $order_data['order_id'] . "', comment = 'Ваш заказ обрабатывается', order_status_id = '" . $params['new_status'] . "', notify = '0', date_added = NOW()";
				//$this->log($sql);
				$query = $this->db->query($sql);
				$this->log("> Добавлена история в заказ #" . $order_data['order_id']);
				
				// Уведомление
				if ($params['notify']) {
					$this->log("> Отправка уведомления на почту: " . $order_data['email']);
					$this->sendMail('Статус Вашего заказа изменен', $order_data);
				}
			}
		}
		return 1;
	}


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ ****************************** 
	 */
	public function queryOrders($params) {
		$this->log("==== Выгрузка заказов ====");
//		$this->log('Параметры:');
//		$this->log($params);

		$this->load->model('sale/order');
		if (version_compare(VERSION, '2.0.3.1', '<=')) {
			$this->log('sale/customer_group');
			$this->load->model('sale/customer_group');
		} else {
			$this->log('customer/customer_group');
			$this->load->model('customer/customer_group');
		}
		
		if ($params['exchange_status'] != 0) {
			// Если указано с каким статусом выгружать заказы
			$sql = "SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = '" . $params['exchange_status'] . "'";
			$this->log($sql);
			$query = $this->db->query($sql);
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
				if (version_compare(VERSION, '2.0.3.1', '<=')) {
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
		
		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (is_writable($cache)) {
			// запись заказа в файл
			$f_order = fopen(DIR_CACHE . 'exchange1c/orders.xml', 'w');
			fwrite($f_order, $xml->asXML());
			fclose($f_order);
		} else {
			$this->log('Папка ' . $cache . ' не доступна для записи, файл заказов не может быть сохранен!');
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
			
			// Запишем в настройки время начала загрузки каталога
			$this->load->model('setting/setting');
			$config = $this->model_setting_setting->getSetting('exchange1c');
			$config['exchange1c_date_exchange'] = date('Y-m-d H:i:s');
			$this->model_setting_setting->editSetting('exchange1c', $config);
			
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
			
			// После загрузки пакета предложений формируем SEO
			$this->seoGenerate();
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
		
		if (version_compare($version, '1.6.2.b4', '=')) {
			$version = '1.6.2.b5';
			$update = true;
		}
		
		if (version_compare($version, '1.6.2.b5', '=')) {
			$settings =  $this->update162b6($settings);
			$version = '1.6.2.b6';
			$update = true;
		}

		if (version_compare($version, '1.6.2.b6', '=')) {
			if ($this->update162b7()) {
				$version = '1.6.2.b7';
				$update = true;
			}
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
	 * Обновляет версию до 1.6.2.b4
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
							FOREIGN KEY (`manufacturer_id`) REFERENCES `'. DB_PREFIX .'manufacturer`(`manufacturer_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8'
			);
		}

		if (!$this->existField('category_to_1c', '1c_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "category_to_1c CHANGE 1c_category_id 1c_id VARCHAR(255)");
		}
		
		if (!$this->existField('attribute_to_1c', '1c_id')) {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "attribute_to_1c CHANGE 1c_attribute_id 1c_id VARCHAR(255)");
		}
		return 1;
	}
	

	/**
	 * Обновляет версию до 1.6.2.b6
	 */
	public function update162b6($settings) {
		// Добавление таблицы manufacturer_to_1c
		$new_version = '1.6.2.b6';
		$settings['exchange1c_seo_category_name'] = '[category_name]';
		$settings['exchange1c_seo_parent_category_name'] = '[parent_category_name]';
		$settings['exchange1c_seo_product_name'] = '[product_name]';
		$settings['exchange1c_seo_product_price'] = '[product_price]';
		$settings['exchange1c_seo_manufacturer'] = '[manufacturer]';
		$settings['exchange1c_seo_sku'] = '[sku]';
		$this->model_setting_setting->editSetting('exchange1c', $settings);
		return $settings;
	}
	
	/**
	 * Обновляет версию до 1.6.2.b7
	 */
	public function update162b7() {
		// Добавление таблицы manufacturer_to_1c
		$new_version = '1.6.2.b7';

//		if ($this->existField('product_quantity', 'quantity')) {
//			$this->db->query("ALTER TABLE " . DB_PREFIX . "product_quantity CHANGE quantity quantity decimal(10,3) NOT NULL DEFAULT '0' COMMENT 'Количество'");
//		}
		
		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE " . DB_PREFIX . "product CHANGE quantity quantity decimal(15,3) NOT NULL DEFAULT '0' COMMENT 'Количество'");

		// Общее количество теперь можно хранить не только целое число (для совместимости)
		$this->db->query("ALTER TABLE " . DB_PREFIX . "currency ADD COLUMN 1c_name varchar(16) NOT NULL DEFAULT '' COMMENT 'Название в 1С' AFTER title");

		// Удалим старую не нужную таблицу
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "option_to_1c`");

		// Характеристики товара
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_feature` (
				`product_feature_id` 	INT(11) 		NOT NULL AUTO_INCREMENT,
				`product_id` 			INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`ean` 					VARCHAR(14) 	NOT NULL DEFAULT '' 	COMMENT 'Штрихкод',
				`name` 					VARCHAR(255) 	NOT NULL DEFAULT '' 	COMMENT 'Название',
				`sku` 					VARCHAR(128) 	NOT NULL DEFAULT '' 	COMMENT 'Артикул',
				`1c_id` 				VARCHAR(64) 	NOT NULL 				COMMENT 'Ид характеристики в 1С',
				PRIMARY KEY (`product_feature_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);
	

		// Значения характеристики товара(доп. значения)
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_feature_value` (
				`product_feature_id` 	INT(11) 		NOT NULL 				COMMENT 'ID характеристики товара',
				`product_id` 			INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`option_id` 			INT(11) 		NOT NULL 				COMMENT 'ID опции',
				`option_value_id` 		INT(11) 		NOT NULL 				COMMENT 'ID значения опции',
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`option_id`) 				REFERENCES `" . DB_PREFIX . "option`(`option_id`),
				FOREIGN KEY (`option_value_id`) 		REFERENCES `" . DB_PREFIX . "option_value`(`option_value_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Цены, если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_price` (
				`product_id` 			INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_feature_id` 	INT(11) 		NOT NULL DEFAULT '0' 	COMMENT 'ID характеристики товара',
				`customer_group_id`		INT(11) 		NOT NULL DEFAULT '0'	COMMENT 'ID группы покупателя',
				`unit_id` 				INT(11) 		NOT NULL DEFAULT '0'	COMMENT 'ID единицы измерения',
				`price` 				DECIMAL(15,4) 	NOT NULL DEFAULT '0'	COMMENT 'Цена',
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `" . DB_PREFIX . "unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Остатки товара
		// Хранятся остатки товара как с характеристиками, так и без. 
		// Если склады и характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_quantity` (
				`product_id` 			INT(11) 		NOT NULL 				COMMENT 'Ссылка на товар',
				`product_feature_id` 	INT(11) 		DEFAULT '0' NOT NULL	COMMENT 'Ссылка на характеристику товара',
				`warehouse_id` 			INT(11) 		DEFAULT '0' NOT NULL 	COMMENT 'Ссылка на склад',
				`unit_id` 				INT(11) 		DEFAULT '0' NOT NULL 	COMMENT 'Ссылка на единицу измерения',
				`quantity` 				DECIMAL(10,3) 	DEFAULT '0' 			COMMENT 'Остаток',
				FOREIGN KEY (`product_id`) 			REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 	REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`warehouse_id`) 		REFERENCES `" . DB_PREFIX . "warehouse`(`warehouse_id`),
				FOREIGN KEY (`unit_id`) 			REFERENCES `" . DB_PREFIX . "unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Единицы измерения товара (упаковки товара)
		// Если используются упаковки, то в эту таблицу записываются дополнительные единицы измерения
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_unit` (
				`product_id` 			INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`unit_id` 				INT(11) 		DEFAULT '0' NOT NULL 	COMMENT 'ID единицы измерения',
				`ratio` 				INT(9) 			DEFAULT '1' NOT NULL	COMMENT 'Коэффициент пересчета количества',
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `" . DB_PREFIX . "unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);


		// Классификатор единиц измерения
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit` (
				`unit_id` 				SMALLINT(6) 	NOT NULL AUTO_INCREMENT COMMENT 'pk',
				`name` 					VARCHAR(255) 	NOT NULL 				COMMENT 'Наименование единицы измерения',
				`number_code` 			VARCHAR(5) 		NOT NULL 				COMMENT 'Код',
				`rus_name1` 			VARCHAR(50) 	DEFAULT '' NOT NULL		COMMENT 'Условное обозначение национальное',
				`eng_name1` 			VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Условное обозначение международное',
				`rus_name2` 			VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Кодовое буквенное обозначение национальное',
				`eng_name2` 			VARCHAR(50) 	DEFAULT '' NOT NULL 	COMMENT 'Кодовое буквенное обозначение международное',
				`unit_group_id`  		TINYINT(4) 		NOT NULL 				COMMENT 'Группа единиц измерения',
				`unit_type_id` 			TINYINT(4) 		NOT NULL 				COMMENT 'Раздел/приложение в которое входит единица измерения',
				`visible` 				TINYINT(4) 		DEFAULT '1' NOT NULL 	COMMENT 'Видимость',
				`comment` 				VARCHAR(255) 	DEFAULT '' NOT NULL 	COMMENT 'Комментарий',
				PRIMARY KEY (`unit_id`),
				UNIQUE KEY number_code (`number_code`),
  				KEY unit_group_id (`unit_group_id`),
  				KEY unit_type_id (`unit_type_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Общероссийский классификатор единиц измерения ОКЕИ'"
		);

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_group`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit_group` (
				`unit_group_id` 		TINYINT(4) 		NOT NULL AUTO_INCREMENT COMMENT 'pk',
				`name` 					VARCHAR(255) 	NOT NULL 				COMMENT 'Наименование группы',
				PRIMARY KEY (`unit_group_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Группы единиц измерения'"
		);

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_type`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "unit_type` (
				`unit_type_id` 		TINYINT(4) 			NOT NULL AUTO_INCREMENT COMMENT 'pk',
				`name` 				VARCHAR(255) 		NOT NULL 				COMMENT 'Наименование раздела/приложения',
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
		
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(1, 'Миллиметр', '003', 'мм', 'mm', 'ММ', 'MMT', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(2, 'Сантиметр', '004', 'см', 'cm', 'СМ', 'CMT', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(3, 'Дециметр', '005', 'дм', 'dm', 'ДМ', 'DMT', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(4, 'Метр', '006', 'м', 'm', 'М', 'MTR', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(5, 'Километр; тысяча метров', '008', 'км; 10^3 м', 'km', 'КМ; ТЫС М', 'KMT', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(6, 'Мегаметр; миллион метров', '009', 'Мм; 10^6 м', 'Mm', 'МЕГАМ; МЛН М', 'MAM', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(7, 'Дюйм (25,4 мм)', '039', 'дюйм', 'in', 'ДЮЙМ', 'INH', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(8, 'Фут (0,3048 м)', '041', 'фут', 'ft', 'ФУТ', 'FOT', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(9, 'Ярд (0,9144 м)', '043', 'ярд', 'yd', 'ЯРД', 'YRD', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(10, 'Морская миля (1852 м)', '047', 'миля', 'n mile', 'МИЛЬ', 'NMI', 1, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(11, 'Квадратный миллиметр', '050', 'мм2', 'mm2', 'ММ2', 'MMK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(12, 'Квадратный сантиметр', '051', 'см2', 'cm2', 'СМ2', 'CMK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(13, 'Квадратный дециметр', '053', 'дм2', 'dm2', 'ДМ2', 'DMK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(14, 'Квадратный метр', '055', 'м2', 'm2', 'М2', 'MTK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(15, 'Тысяча квадратных метров', '058', '10^3 м^2', 'daa', 'ТЫС М2', 'DAA', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(16, 'Гектар', '059', 'га', 'ha', 'ГА', 'HAR', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(17, 'Квадратный километр', '061', 'км2', 'km2', 'КМ2', 'KMK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(18, 'Квадратный дюйм (645,16 мм2)', '071', 'дюйм2', 'in2', 'ДЮЙМ2', 'INK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(19, 'Квадратный фут (0,092903 м2)', '073', 'фут2', 'ft2', 'ФУТ2', 'FTK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(20, 'Квадратный ярд (0,8361274 м2)', '075', 'ярд2', 'yd2', 'ЯРД2', 'YDK', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(21, 'Ар (100 м2)', '109', 'а', 'a', 'АР', 'ARE', 2, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(22, 'Кубический миллиметр', '110', 'мм3', 'mm3', 'ММ3', 'MMQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(23, 'Кубический сантиметр; миллилитр', '111', 'см3; мл', 'cm3; ml', 'СМ3; МЛ', 'CMQ; MLT', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(24, 'Литр; кубический дециметр', '112', 'л; дм3', 'I; L; dm^3', 'Л; ДМ3', 'LTR; DMQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(25, 'Кубический метр', '113', 'м3', 'm3', 'М3', 'MTQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(26, 'Децилитр', '118', 'дл', 'dl', 'ДЛ', 'DLT', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(27, 'Гектолитр', '122', 'гл', 'hl', 'ГЛ', 'HLT', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(28, 'Мегалитр', '126', 'Мл', 'Ml', 'МЕГАЛ', 'MAL', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(29, 'Кубический дюйм (16387,1 мм3)', '131', 'дюйм3', 'in3', 'ДЮЙМ3', 'INQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(30, 'Кубический фут (0,02831685 м3)', '132', 'фут3', 'ft3', 'ФУТ3', 'FTQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(31, 'Кубический ярд (0,764555 м3)', '133', 'ярд3', 'yd3', 'ЯРД3', 'YDQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(32, 'Миллион кубических метров', '159', '10^6 м3', '10^6 m3', 'МЛН М3', 'HMQ', 3, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(33, 'Гектограмм', '160', 'гг', 'hg', 'ГГ', 'HGM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(34, 'Миллиграмм', '161', 'мг', 'mg', 'МГ', 'MGM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(35, 'Метрический карат', '162', 'кар', 'МС', 'КАР', 'CTM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(36, 'Грамм', '163', 'г', 'g', 'Г', 'GRM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(37, 'Килограмм', '166', 'кг', 'kg', 'КГ', 'KGM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(38, 'Тонна; метрическая тонна (1000 кг)', '168', 'т', 't', 'Т', 'TNE', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(39, 'Килотонна', '170', '10^3 т', 'kt', 'КТ', 'KTN', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(40, 'Сантиграмм', '173', 'сг', 'cg', 'СГ', 'CGM', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(41, 'Брутто-регистровая тонна (2,8316 м3)', '181', 'БРТ', '-', 'БРУТТ. РЕГИСТР Т', 'GRT', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(42, 'Грузоподъемность в метрических тоннах', '185', 'т грп', '-', 'Т ГРУЗОПОД', 'CCT', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(43, 'Центнер (метрический) (100 кг); гектокилограмм; квинтал1 (метрический); децитонна', '206', 'ц', 'q; 10^2 kg', 'Ц', 'DTN', 4, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(44, 'Ватт', '212', 'Вт', 'W', 'ВТ', 'WTT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(45, 'Киловатт', '214', 'кВт', 'kW', 'КВТ', 'KWT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(46, 'Мегаватт; тысяча киловатт', '215', 'МВт; 10^3 кВт', 'MW', 'МЕГАВТ; ТЫС КВТ', 'MAW', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(47, 'Вольт', '222', 'В', 'V', 'В', 'VLT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(48, 'Киловольт', '223', 'кВ', 'kV', 'КВ', 'KVT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(49, 'Киловольт-ампер', '227', 'кВ.А', 'kV.A', 'КВ.А', 'KVA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(50, 'Мегавольт-ампер (тысяча киловольт-ампер)', '228', 'МВ.А', 'MV.A', 'МЕГАВ.А', 'MVA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(51, 'Киловар', '230', 'квар', 'kVAR', 'КВАР', 'KVR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(52, 'Ватт-час', '243', 'Вт.ч', 'W.h', 'ВТ.Ч', 'WHR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(53, 'Киловатт-час', '245', 'кВт.ч', 'kW.h', 'КВТ.Ч', 'KWH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(54, 'Мегаватт-час; 1000 киловатт-часов', '246', 'МВт.ч; 10^3 кВт.ч', 'МW.h', 'МЕГАВТ.Ч; ТЫС КВТ.Ч', 'MWH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(55, 'Гигаватт-час (миллион киловатт-часов)', '247', 'ГВт.ч', 'GW.h', 'ГИГАВТ.Ч', 'GWH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(56, 'Ампер', '260', 'А', 'A', 'А', 'AMP', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(57, 'Ампер-час (3,6 кКл)', '263', 'А.ч', 'A.h', 'А.Ч', 'AMH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(58, 'Тысяча ампер-часов', '264', '10^3 А.ч', '10^3 A.h', 'ТЫС А.Ч', 'TAH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(59, 'Кулон', '270', 'Кл', 'C', 'КЛ', 'COU', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(60, 'Джоуль', '271', 'Дж', 'J', 'ДЖ', 'JOU', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(61, 'Килоджоуль', '273', 'кДж', 'kJ', 'КДЖ', 'KJO', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(62, 'Ом', '274', 'Ом', '<омега>', 'ОМ', 'OHM', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(63, 'Градус Цельсия', '280', 'град. C', 'град. C', 'ГРАД ЦЕЛЬС', 'CEL', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(64, 'Градус Фаренгейта', '281', 'град. F', 'град. F', 'ГРАД ФАРЕНГ', 'FAN', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(65, 'Кандела', '282', 'кд', 'cd', 'КД', 'CDL', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(66, 'Люкс', '283', 'лк', 'lx', 'ЛК', 'LUX', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(67, 'Люмен', '284', 'лм', 'lm', 'ЛМ', 'LUM', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(68, 'Кельвин', '288', 'K', 'K', 'К', 'KEL', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(69, 'Ньютон', '289', 'Н', 'N', 'Н', 'NEW', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(70, 'Герц', '290', 'Гц', 'Hz', 'ГЦ', 'HTZ', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(71, 'Килогерц', '291', 'кГц', 'kHz', 'КГЦ', 'KHZ', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(72, 'Мегагерц', '292', 'МГц', 'MHz', 'МЕГАГЦ', 'MHZ', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(73, 'Паскаль', '294', 'Па', 'Pa', 'ПА', 'PAL', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(74, 'Сименс', '296', 'См', 'S', 'СИ', 'SIE', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(75, 'Килопаскаль', '297', 'кПа', 'kPa', 'КПА', 'KPA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(76, 'Мегапаскаль', '298', 'МПа', 'MPa', 'МЕГАПА', 'MPA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(77, 'Физическая атмосфера (101325 Па)', '300', 'атм', 'atm', 'АТМ', 'ATM', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(78, 'Техническая атмосфера (98066,5 Па)', '301', 'ат', 'at', 'АТТ', 'ATT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(79, 'Гигабеккерель', '302', 'ГБк', 'GBq', 'ГИГАБК', 'GBQ', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(80, 'Милликюри', '304', 'мКи', 'mCi', 'МКИ', 'MCU', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(81, 'Кюри', '305', 'Ки', 'Ci', 'КИ', 'CUR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(82, 'Грамм делящихся изотопов', '306', 'г Д/И', 'g fissile isotopes', 'Г ДЕЛЯЩ ИЗОТОП', 'GFI', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(83, 'Миллибар', '308', 'мб', 'mbar', 'МБАР', 'MBR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(84, 'Бар', '309', 'бар', 'bar', 'БАР', 'BAR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(85, 'Гектобар', '310', 'гб', 'hbar', 'ГБАР', 'HBA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(86, 'Килобар', '312', 'кб', 'kbar', 'КБАР', 'KBA', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(87, 'Фарад', '314', 'Ф', 'F', 'Ф', 'FAR', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(88, 'Килограмм на кубический метр', '316', 'кг/м3', 'kg/m3', 'КГ/М3', 'KMQ', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(89, 'Беккерель', '323', 'Бк', 'Bq', 'БК', 'BQL', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(90, 'Вебер', '324', 'Вб', 'Wb', 'ВБ', 'WEB', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(91, 'Узел (миля/ч)', '327', 'уз', 'kn', 'УЗ', 'KNT', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(92, 'Метр в секунду', '328', 'м/с', 'm/s', 'М/С', 'MTS', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(93, 'Оборот в секунду', '330', 'об/с', 'r/s', 'ОБ/С', 'RPS', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(94, 'Оборот в минуту', '331', 'об/мин', 'r/min', 'ОБ/МИН', 'RPM', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(95, 'Километр в час', '333', 'км/ч', 'km/h', 'КМ/Ч', 'KMH', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(96, 'Метр на секунду в квадрате', '335', 'м/с2', 'm/s2', 'М/С2', 'MSK', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(97, 'Кулон на килограмм', '349', 'Кл/кг', 'C/kg', 'КЛ/КГ', 'CKG', 5, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(98, 'Секунда', '354', 'с', 's', 'С', 'SEC', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(99, 'Минута', '355', 'мин', 'min', 'МИН', 'MIN', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(100, 'Час', '356', 'ч', 'h', 'Ч', 'HUR', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(101, 'Сутки', '359', 'сут; дн', 'd', 'СУТ; ДН', 'DAY', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(102, 'Неделя', '360', 'нед', '-', 'НЕД', 'WEE', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(103, 'Декада', '361', 'дек', '-', 'ДЕК', 'DAD', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(104, 'Месяц', '362', 'мес', '-', 'МЕС', 'MON', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(105, 'Квартал', '364', 'кварт', '-', 'КВАРТ', 'QAN', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(106, 'Полугодие', '365', 'полгода', '-', 'ПОЛГОД', 'SAN', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(107, 'Год', '366', 'г; лет', 'a', 'ГОД; ЛЕТ', 'ANN', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(108, 'Десятилетие', '368', 'деслет', '-', 'ДЕСЛЕТ', 'DEC', 6, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(109, 'Килограмм в секунду', '499', 'кг/с', '-', 'КГ/С', 'KGS', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(110, 'Тонна пара в час', '533', 'т пар/ч', '-', 'Т ПАР/Ч', 'TSH', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(111, 'Кубический метр в секунду', '596', 'м3/с', 'm3/s', 'М3/С', 'MQS', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(112, 'Кубический метр в час', '598', 'м3/ч', 'm3/h', 'М3/Ч', 'MQH', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(113, 'Тысяча кубических метров в сутки', '599', '10^3 м3/сут', '-', 'ТЫС М3/СУТ', 'TQD', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(114, 'Бобина', '616', 'боб', '-', 'БОБ', 'NBB', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(115, 'Лист', '625', 'л.', '-', 'ЛИСТ', 'LEF', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(116, 'Сто листов', '626', '100 л.', '-', '100 ЛИСТ', 'CLF', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(117, 'Тысяча стандартных условных кирпичей', '630', 'тыс станд. усл. кирп', '-', 'ТЫС СТАНД УСЛ КИРП', 'MBE', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(118, 'Дюжина (12 шт.)', '641', 'дюжина', 'Doz; 12', 'ДЮЖИНА', 'DZN', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(119, 'Изделие', '657', 'изд', '-', 'ИЗД', 'NAR', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(120, 'Сто ящиков', '683', '100 ящ.', 'Hbx', '100 ЯЩ', 'HBX', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(121, 'Набор', '704', 'набор', '-', 'НАБОР', 'SET', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(122, 'Пара (2 шт.)', '715', 'пар', 'pr; 2', 'ПАР', 'NPR', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(123, 'Два десятка', '730', '20', '20', '2 ДЕС', 'SCO', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(124, 'Десять пар', '732', '10 пар', '-', 'ДЕС ПАР', 'TPR', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(125, 'Дюжина пар', '733', 'дюжина пар', '-', 'ДЮЖИНА ПАР', 'DPR', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(126, 'Посылка', '734', 'посыл', '-', 'ПОСЫЛ', 'NPL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(127, 'Часть', '735', 'часть', '-', 'ЧАСТЬ', 'NPT', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(128, 'Рулон', '736', 'рул', '-', 'РУЛ', 'NPL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(129, 'Дюжина рулонов', '737', 'дюжина рул', '-', 'ДЮЖИНА РУЛ', 'DRL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(130, 'Дюжина штук', '740', 'дюжина шт', '-', 'ДЮЖИНА ШТ', 'DPC', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(131, 'Элемент', '745', 'элем', 'CI', 'ЭЛЕМ', 'NCL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(132, 'Упаковка', '778', 'упак', '-', 'УПАК', 'NMP', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(133, 'Дюжина упаковок', '780', 'дюжина упак', '-', 'ДЮЖИНА УПАК', 'DZP', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(134, 'Сто упаковок', '781', '100 упак', '-', '100 УПАК', 'CNP', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(135, 'Штука', '796', 'шт', 'pc; 1', 'ШТ', 'PCE; NMB', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(136, 'Сто штук', '797', '100 шт', '100', '100 ШТ', 'CEN', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(137, 'Тысяча штук', '798', 'тыс. шт; 1000 шт', '1000', 'ТЫС ШТ', 'MIL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(138, 'Миллион штук', '799', '10^6 шт', '10^6', 'МЛН ШТ', 'MIO', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(139, 'Миллиард штук', '800', '10^9 шт', '10^9', 'МЛРД ШТ', 'MLD', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(140, 'Биллион штук (Европа); триллион штук', '801', '10^12 шт', '10^12', 'БИЛЛ ШТ (ЕВР); ТРИЛЛ ШТ', 'BIL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(141, 'Квинтильон штук (Европа)', '802', '10^18 шт', '10^18', 'КВИНТ ШТ', 'TRL', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(142, 'Крепость спирта по массе', '820', 'креп. спирта по массе', '% mds', 'КРЕП СПИРТ ПО МАССЕ', 'ASM', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(143, 'Крепость спирта по объему', '821', 'креп. спирта по объему', '% vol', 'КРЕП СПИРТ ПО ОБЪЕМ', 'ASV', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(144, 'Литр чистого (100%) спирта', '831', 'л 100% спирта', '-', 'Л ЧИСТ СПИРТ', 'LPA', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(145, 'Гектолитр чистого (100%) спирта', '833', 'Гл 100% спирта', '-', 'ГЛ ЧИСТ СПИРТ', 'HPA', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(146, 'Килограмм пероксида водорода', '841', 'кг H2О2', '-', 'КГ ПЕРОКСИД ВОДОРОДА', '-', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(147, 'Килограмм 90%-го сухого вещества', '845', 'кг 90% с/в', '-', 'КГ 90 ПРОЦ СУХ ВЕЩ', 'KSD', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(148, 'Тонна 90%-го сухого вещества', '847', 'т 90% с/в', '-', 'Т 90 ПРОЦ СУХ ВЕЩ', 'TSD', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(149, 'Килограмм оксида калия', '852', 'кг К2О', '-', 'КГ ОКСИД КАЛИЯ', 'KPO', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(150, 'Килограмм гидроксида калия', '859', 'кг КОН', '-', 'КГ ГИДРОКСИД КАЛИЯ', 'KPH', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(151, 'Килограмм азота', '861', 'кг N', '-', 'КГ АЗОТ', 'KNI', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(152, 'Килограмм гидроксида натрия', '863', 'кг NaOH', '-', 'КГ ГИДРОКСИД НАТРИЯ', 'KSH', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(153, 'Килограмм пятиокиси фосфора', '865', 'кг Р2О5', '-', 'КГ ПЯТИОКИСЬ ФОСФОРА', 'KPP', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(154, 'Килограмм урана', '867', 'кг U', '-', 'КГ УРАН', 'KUR', 7, 1, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(155, 'Погонный метр', '018', 'пог. м', NULL, 'ПОГ М', NULL, 1, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(156, 'Тысяча погонных метров', '019', '10^3 пог. м', NULL, 'ТЫС ПОГ М', NULL, 1, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(157, 'Условный метр', '020', 'усл. м', NULL, 'УСЛ М', NULL, 1, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(158, 'Тысяча условных метров', '048', '10^3 усл. м', NULL, 'ТЫС УСЛ М', NULL, 1, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(159, 'Километр условных труб', '049', 'км усл. труб', NULL, 'КМ УСЛ ТРУБ', NULL, 1, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(160, 'Тысяча квадратных дециметров', '054', '10^3 дм2', NULL, 'ТЫС ДМ2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(161, 'Миллион квадратных дециметров', '056', '10^6 дм2', NULL, 'МЛН ДМ2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(162, 'Миллион квадратных метров', '057', '10^6 м2', NULL, 'МЛН М2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(163, 'Тысяча гектаров', '060', '10^3 га', NULL, 'ТЫС ГА', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(164, 'Условный квадратный метр', '062', 'усл. м2', NULL, 'УСЛ М2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(165, 'Тысяча условных квадратных метров', '063', '10^3 усл. м2', NULL, 'ТЫС УСЛ М2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(166, 'Миллион условных квадратных метров', '064', '10^6 усл. м2', NULL, 'МЛН УСЛ М2', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(167, 'Квадратный метр общей площади', '081', 'м2 общ. пл', NULL, 'М2 ОБЩ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(168, 'Тысяча квадратных метров общей площади', '082', '10^3 м2 общ. пл', NULL, 'ТЫС М2 ОБЩ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(169, 'Миллион квадратных метров общей площади', '083', '10^6 м2 общ. пл', NULL, 'МЛН М2. ОБЩ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(170, 'Квадратный метр жилой площади', '084', 'м2 жил. пл', NULL, 'М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(171, 'Тысяча квадратных метров жилой площади', '085', '10^3 м2 жил. пл', NULL, 'ТЫС М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(172, 'Миллион квадратных метров жилой площади', '086', '10^6 м2 жил. пл', NULL, 'МЛН М2 ЖИЛ ПЛ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(173, 'Квадратный метр учебно-лабораторных зданий', '087', 'м2 уч. лаб. здан', NULL, 'М2 УЧ.ЛАБ ЗДАН', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(174, 'Тысяча квадратных метров учебно-лабораторных зданий', '088', '10^3 м2 уч. лаб. здан', NULL, 'ТЫС М2 УЧ. ЛАБ ЗДАН', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(175, 'Миллион квадратных метров в двухмиллиметровом исчислении', '089', '10^6 м2 2 мм исч', NULL, 'МЛН М2 2ММ ИСЧ', NULL, 2, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(176, 'Тысяча кубических метров', '114', '10^3 м3', NULL, 'ТЫС М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(177, 'Миллиард кубических метров', '115', '10^9 м3', NULL, 'МЛРД М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(178, 'Декалитр', '116', 'дкл', NULL, 'ДКЛ', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(179, 'Тысяча декалитров', '119', '10^3 дкл', NULL, 'ТЫС ДКЛ', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(180, 'Миллион декалитров', '120', '10^6 дкл', NULL, 'МЛН ДКЛ', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(181, 'Плотный кубический метр', '121', 'плотн. м3', NULL, 'ПЛОТН М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(182, 'Условный кубический метр', '123', 'усл. м3', NULL, 'УСЛ М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(183, 'Тысяча условных кубических метров', '124', '10^3 усл. м3', NULL, 'ТЫС УСЛ М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(184, 'Миллион кубических метров переработки газа', '125', '10^6 м3 перераб. газа', NULL, 'МЛН М3 ПЕРЕРАБ ГАЗА', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(185, 'Тысяча плотных кубических метров', '127', '10^3 плотн. м3', NULL, 'ТЫС ПЛОТН М3', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(186, 'Тысяча полулитров', '128', '10^3 пол. л', NULL, 'ТЫС ПОЛ Л', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(187, 'Миллион полулитров', '129', '10^6 пол. л', NULL, 'МЛН ПОЛ Л', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(188, 'Тысяча литров; 1000 литров', '130', '10^3 л; 1000 л', NULL, 'ТЫС Л', NULL, 3, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(189, 'Тысяча каратов метрических', '165', '10^3 кар', NULL, 'ТЫС КАР', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(190, 'Миллион каратов метрических', '167', '10^6 кар', NULL, 'МЛН КАР', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(191, 'Тысяча тонн', '169', '10^3 т', NULL, 'ТЫС Т', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(192, 'Миллион тонн', '171', '10^6 т', NULL, 'МЛН Т', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(193, 'Тонна условного топлива', '172', 'т усл. топл', NULL, 'Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(194, 'Тысяча тонн условного топлива', '175', '10^3 т усл. топл', NULL, 'ТЫС Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(195, 'Миллион тонн условного топлива', '176', '10^6 т усл. топл', NULL, 'МЛН Т УСЛ ТОПЛ', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(196, 'Тысяча тонн единовременного хранения', '177', '10^3 т единовр. хран', NULL, 'ТЫС Т ЕДИНОВР ХРАН', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(197, 'Тысяча тонн переработки', '178', '10^3 т перераб', NULL, 'ТЫС Т ПЕРЕРАБ', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(198, 'Условная тонна', '179', 'усл. т', NULL, 'УСЛ Т', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(199, 'Тысяча центнеров', '207', '10^3 ц', NULL, 'ТЫС Ц', NULL, 4, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(200, 'Вольт-ампер', '226', 'В.А', NULL, 'В.А', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(201, 'Метр в час', '231', 'м/ч', NULL, 'М/Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(202, 'Килокалория', '232', 'ккал', NULL, 'ККАЛ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(203, 'Гигакалория', '233', 'Гкал', NULL, 'ГИГАКАЛ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(204, 'Тысяча гигакалорий', '234', '10^3 Гкал', NULL, 'ТЫС ГИГАКАЛ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(205, 'Миллион гигакалорий', '235', '10^6 Гкал', NULL, 'МЛН ГИГАКАЛ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(206, 'Калория в час', '236', 'кал/ч', NULL, 'КАЛ/Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(207, 'Килокалория в час', '237', 'ккал/ч', NULL, 'ККАЛ/Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(208, 'Гигакалория в час', '238', 'Гкал/ч', NULL, 'ГИГАКАЛ/Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(209, 'Тысяча гигакалорий в час', '239', '10^3 Гкал/ч', NULL, 'ТЫС ГИГАКАЛ/Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(210, 'Миллион ампер-часов', '241', '10^6 А.ч', NULL, 'МЛН А.Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(211, 'Миллион киловольт-ампер', '242', '10^6 кВ.А', NULL, 'МЛН КВ.А', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(212, 'Киловольт-ампер реактивный', '248', 'кВ.А Р', NULL, 'КВ.А Р', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(213, 'Миллиард киловатт-часов', '249', '10^9 кВт.ч', NULL, 'МЛРД КВТ.Ч', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(214, 'Тысяча киловольт-ампер реактивных', '250', '10^3 кВ.А Р', NULL, 'ТЫС КВ.А Р', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(215, 'Лошадиная сила', '251', 'л. с', NULL, 'ЛС', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(216, 'Тысяча лошадиных сил', '252', '10^3 л. с', NULL, 'ТЫС ЛС', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(217, 'Миллион лошадиных сил', '253', '10^6 л. с', NULL, 'МЛН ЛС', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(218, 'Бит', '254', 'бит', NULL, 'БИТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(219, 'Байт', '255', 'бай', NULL, 'БАЙТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(220, 'Килобайт', '256', 'кбайт', NULL, 'КБАЙТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(221, 'Мегабайт', '257', 'Мбайт', NULL, 'МБАЙТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(222, 'Бод', '258', 'бод', NULL, 'БОД', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(223, 'Генри', '287', 'Гн', NULL, 'ГН', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(224, 'Тесла', '313', 'Тл', NULL, 'ТЛ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(225, 'Килограмм на квадратный сантиметр', '317', 'кг/см^2', NULL, 'КГ/СМ2', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(226, 'Миллиметр водяного столба', '337', 'мм вод. ст', NULL, 'ММ ВОД СТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(227, 'Миллиметр ртутного столба', '338', 'мм рт. ст', NULL, 'ММ РТ СТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(228, 'Сантиметр водяного столба', '339', 'см вод. ст', NULL, 'СМ ВОД СТ', NULL, 5, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(229, 'Микросекунда', '352', 'мкс', NULL, 'МКС', NULL, 6, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(230, 'Миллисекунда', '353', 'млс', NULL, 'МЛС', NULL, 6, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(231, 'Рубль', '383', 'руб', NULL, 'РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(232, 'Тысяча рублей', '384', '10^3 руб', NULL, 'ТЫС РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(233, 'Миллион рублей', '385', '10^6 руб', NULL, 'МЛН РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(234, 'Миллиард рублей', '386', '10^9 руб', NULL, 'МЛРД РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(235, 'Триллион рублей', '387', '10^12 руб', NULL, 'ТРИЛЛ РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(236, 'Квадрильон рублей', '388', '10^15 руб', NULL, 'КВАДР РУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(237, 'Пассажиро-километр', '414', 'пасс.км', NULL, 'ПАСС.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(238, 'Пассажирское место (пассажирских мест)', '421', 'пасс. мест', NULL, 'ПАСС МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(239, 'Тысяча пассажиро-километров', '423', '10^3 пасс.км', NULL, 'ТЫС ПАСС.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(240, 'Миллион пассажиро-километров', '424', '10^6 пасс. км', NULL, 'МЛН ПАСС.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(241, 'Пассажиропоток', '427', 'пасс.поток', NULL, 'ПАСС.ПОТОК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(242, 'Тонно-километр', '449', 'т.км', NULL, 'Т.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(243, 'Тысяча тонно-километров', '450', '10^3 т.км', NULL, 'ТЫС Т.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(244, 'Миллион тонно-километров', '451', '10^6 т. км', NULL, 'МЛН Т.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(245, 'Тысяча наборов', '479', '10^3 набор', NULL, 'ТЫС НАБОР', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(246, 'Грамм на киловатт-час', '510', 'г/кВт.ч', NULL, 'Г/КВТ.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(247, 'Килограмм на гигакалорию', '511', 'кг/Гкал', NULL, 'КГ/ГИГАКАЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(248, 'Тонно-номер', '512', 'т.ном', NULL, 'Т.НОМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(249, 'Автотонна', '513', 'авто т', NULL, 'АВТО Т', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(250, 'Тонна тяги', '514', 'т.тяги', NULL, 'Т ТЯГИ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(251, 'Дедвейт-тонна', '515', 'дедвейт.т', NULL, 'ДЕДВЕЙТ.Т', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(252, 'Тонно-танид', '516', 'т.танид', NULL, 'Т.ТАНИД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(253, 'Человек на квадратный метр', '521', 'чел/м2', NULL, 'ЧЕЛ/М2', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(254, 'Человек на квадратный километр', '522', 'чел/км2', NULL, 'ЧЕЛ/КМ2', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(255, 'Тонна в час', '534', 'т/ч', NULL, 'Т/Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(256, 'Тонна в сутки', '535', 'т/сут', NULL, 'Т/СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(257, 'Тонна в смену', '536', 'т/смен', NULL, 'Т/СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(258, 'Тысяча тонн в сезон', '537', '10^3 т/сез', NULL, 'ТЫС Т/СЕЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(259, 'Тысяча тонн в год', '538', '10^3 т/год', NULL, 'ТЫС Т/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(260, 'Человеко-час', '539', 'чел.ч', NULL, 'ЧЕЛ.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(261, 'Человеко-день', '540', 'чел.дн', NULL, 'ЧЕЛ.ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(262, 'Тысяча человеко-дней', '541', '10^3 чел.дн', NULL, 'ТЫС ЧЕЛ.ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(263, 'Тысяча человеко-часов', '542', '10^3 чел.ч', NULL, 'ТЫС ЧЕЛ.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(264, 'Тысяча условных банок в смену', '543', '10^3 усл. банк/ смен', NULL, 'ТЫС УСЛ БАНК/СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(265, 'Миллион единиц в год', '544', '10^6 ед/год', NULL, 'МЛН ЕД/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(266, 'Посещение в смену', '545', 'посещ/смен', NULL, 'ПОСЕЩ/СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(267, 'Тысяча посещений в смену', '546', '10^3 посещ/смен', NULL, 'ТЫС ПОСЕЩ/ СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(268, 'Пара в смену', '547', 'пар/смен', NULL, 'ПАР/СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(269, 'Тысяча пар в смену', '548', '10^3 пар/смен', NULL, 'ТЫС ПАР/СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(270, 'Миллион тонн в год', '550', '10^6 т/год', NULL, 'МЛН Т/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(271, 'Тонна переработки в сутки', '552', 'т перераб/сут', NULL, 'Т ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(272, 'Тысяча тонн переработки в сутки', '553', '10^3 т перераб/ сут', NULL, 'ТЫС Т ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(273, 'Центнер переработки в сутки', '554', 'ц перераб/сут', NULL, 'Ц ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(274, 'Тысяча центнеров переработки в сутки', '555', '10^3 ц перераб/ сут', NULL, 'ТЫС Ц ПЕРЕРАБ/СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(275, 'Тысяча голов в год', '556', '10^3 гол/год', NULL, 'ТЫС ГОЛ/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(276, 'Миллион голов в год', '557', '10^6 гол/год', NULL, 'МЛН ГОЛ/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(277, 'Тысяча птицемест', '558', '10^3 птицемест', NULL, 'ТЫС ПТИЦЕМЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(278, 'Тысяча кур-несушек', '559', '10^3 кур. несуш', NULL, 'ТЫС КУР. НЕСУШ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(279, 'Минимальная заработная плата', '560', 'мин. заработн. плат', NULL, 'МИН ЗАРАБОТН ПЛАТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(280, 'Тысяча тонн пара в час', '561', '10^3 т пар/ч', NULL, 'ТЫС Т ПАР/Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(281, 'Тысяча прядильных веретен', '562', '10^3 пряд.верет', NULL, 'ТЫС ПРЯД ВЕРЕТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(282, 'Тысяча прядильных мест', '563', '10^3 пряд.мест', NULL, 'ТЫС ПРЯД МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(283, 'Доза', '639', 'доз', NULL, 'ДОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(284, 'Тысяча доз', '640', '10^3 доз', NULL, 'ТЫС ДОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(285, 'Единица', '642', 'ед', NULL, 'ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(286, 'Тысяча единиц', '643', '10^3 ед', NULL, 'ТЫС ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(287, 'Миллион единиц', '644', '10^6 ед', NULL, 'МЛН ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(288, 'Канал', '661', 'канал', NULL, 'КАНАЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(289, 'Тысяча комплектов', '673', '10^3 компл', NULL, 'ТЫС КОМПЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(290, 'Место', '698', 'мест', NULL, 'МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(291, 'Тысяча мест', '699', '10^3 мест', NULL, 'ТЫС МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(292, 'Тысяча номеров', '709', '10^3 ном', NULL, 'ТЫС НОМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(293, 'Тысяча гектаров порций', '724', '10^3 га порц', NULL, 'ТЫС ГА ПОРЦ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(294, 'Тысяча пачек', '729', '10^3 пач', NULL, 'ТЫС ПАЧ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(295, 'Процент', '744', '%', NULL, 'ПРОЦ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(296, 'Промилле (0,1 процента)', '746', 'промилле', NULL, 'ПРОМИЛЛЕ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(297, 'Тысяча рулонов', '751', '10^3 рул', NULL, 'ТЫС РУЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(298, 'Тысяча станов', '761', '10^3 стан', NULL, 'ТЫС СТАН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(299, 'Станция', '762', 'станц', NULL, 'СТАНЦ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(300, 'Тысяча тюбиков', '775', '10^3 тюбик', NULL, 'ТЫС ТЮБИК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(301, 'Тысяча условных тубов', '776', '10^3 усл.туб', NULL, 'ТЫС УСЛ ТУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(302, 'Миллион упаковок', '779', '10^6 упак', NULL, 'МЛН УПАК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(303, 'Тысяча упаковок', '782', '10^3 упак', NULL, 'ТЫС УПАК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(304, 'Человек', '792', 'чел', NULL, 'ЧЕЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(305, 'Тысяча человек', '793', '10^3 чел', NULL, 'ТЫС ЧЕЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(306, 'Миллион человек', '794', '10^6 чел', NULL, 'МЛН ЧЕЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(307, 'Миллион экземпляров', '808', '10^6 экз', NULL, 'МЛН ЭКЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(308, 'Ячейка', '810', 'яч', NULL, 'ЯЧ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(309, 'Ящик', '812', 'ящ', NULL, 'ЯЩ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(310, 'Голова', '836', 'гол', NULL, 'ГОЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(311, 'Тысяча пар', '837', '10^3 пар', NULL, 'ТЫС ПАР', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(312, 'Миллион пар', '838', '10^6 пар', NULL, 'МЛН ПАР', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(313, 'Комплект', '839', 'компл', NULL, 'КОМПЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(314, 'Секция', '840', 'секц', NULL, 'СЕКЦ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(315, 'Бутылка', '868', 'бут', NULL, 'БУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(316, 'Тысяча бутылок', '869', '10^3 бут', NULL, 'ТЫС БУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(317, 'Ампула', '870', 'ампул', NULL, 'АМПУЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(318, 'Тысяча ампул', '871', '10^3 ампул', NULL, 'ТЫС АМПУЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(319, 'Флакон', '872', 'флак', NULL, 'ФЛАК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(320, 'Тысяча флаконов', '873', '10^3 флак', NULL, 'ТЫС ФЛАК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(321, 'Тысяча тубов', '874', '10^3 туб', NULL, 'ТЫС ТУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(322, 'Тысяча коробок', '875', '10^3 кор', NULL, 'ТЫС КОР', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(323, 'Условная единица', '876', 'усл. ед', NULL, 'УСЛ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(324, 'Тысяча условных единиц', '877', '10^3 усл. ед', NULL, 'ТЫС УСЛ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(325, 'Миллион условных единиц', '878', '10^6 усл. ед', NULL, 'МЛН УСЛ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(326, 'Условная штука', '879', 'усл. шт', NULL, 'УСЛ ШТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(327, 'Тысяча условных штук', '880', '10^3 усл. шт', NULL, 'ТЫС УСЛ ШТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(328, 'Условная банка', '881', 'усл. банк', NULL, 'УСЛ БАНК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(329, 'Тысяча условных банок', '882', '10^3 усл. банк', NULL, 'ТЫС УСЛ БАНК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(330, 'Миллион условных банок', '883', '10^6 усл. банк', NULL, 'МЛН УСЛ БАНК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(331, 'Условный кусок', '884', 'усл. кус', NULL, 'УСЛ КУС', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(332, 'Тысяча условных кусков', '885', '10^3 усл. кус', NULL, 'ТЫС УСЛ КУС', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(333, 'Миллион условных кусков', '886', '10^6 усл. кус', NULL, 'МЛН УСЛ КУС', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(334, 'Условный ящик', '887', 'усл. ящ', NULL, 'УСЛ ЯЩ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(335, 'Тысяча условных ящиков', '888', '10^3 усл. ящ', NULL, 'ТЫС УСЛ ЯЩ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(336, 'Условная катушка', '889', 'усл. кат', NULL, 'УСЛ КАТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(337, 'Тысяча условных катушек', '890', '10^3 усл. кат', NULL, 'ТЫС УСЛ КАТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(338, 'Условная плитка', '891', 'усл. плит', NULL, 'УСЛ ПЛИТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(339, 'Тысяча условных плиток', '892', '10^3 усл. плит', NULL, 'ТЫС УСЛ ПЛИТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(340, 'Условный кирпич', '893', 'усл. кирп', NULL, 'УСЛ КИРП', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(341, 'Тысяча условных кирпичей', '894', '10^3 усл. кирп', NULL, 'ТЫС УСЛ КИРП', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(342, 'Миллион условных кирпичей', '895', '10^6 усл. кирп', NULL, 'МЛН УСЛ КИРП', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(343, 'Семья', '896', 'семей', NULL, 'СЕМЕЙ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(344, 'Тысяча семей', '897', '10^3 семей', NULL, 'ТЫС СЕМЕЙ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(345, 'Миллион семей', '898', '10^6 семей', NULL, 'МЛН СЕМЕЙ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(346, 'Домохозяйство', '899', 'домхоз', NULL, 'ДОМХОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(347, 'Тысяча домохозяйств', '900', '10^3 домхоз', NULL, 'ТЫС ДОМХОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(348, 'Миллион домохозяйств', '901', '10^6 домхоз', NULL, 'МЛН ДОМХОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(349, 'Ученическое место', '902', 'учен. мест', NULL, 'УЧЕН МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(350, 'Тысяча ученических мест', '903', '10^3 учен. мест', NULL, 'ТЫС УЧЕН МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(351, 'Рабочее место', '904', 'раб. мест', NULL, 'РАБ МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(352, 'Тысяча рабочих мест', '905', '10^3 раб. мест', NULL, 'ТЫС РАБ МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(353, 'Посадочное место', '906', 'посад. мест', NULL, 'ПОСАД МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(354, 'Тысяча посадочных мест', '907', '10^3 посад. мест', NULL, 'ТЫС ПОСАД МЕСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(355, 'Номер', '908', 'ном', NULL, 'НОМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(356, 'Квартира', '909', 'кварт', NULL, 'КВАРТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(357, 'Тысяча квартир', '910', '10^3 кварт', NULL, 'ТЫС КВАРТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(358, 'Койка', '911', 'коек', NULL, 'КОЕК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(359, 'Тысяча коек', '912', '10^3 коек', NULL, 'ТЫС КОЕК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(360, 'Том книжного фонда', '913', 'том книжн. фонд', NULL, 'ТОМ КНИЖН ФОНД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(361, 'Тысяча томов книжного фонда', '914', '10^3 том. книжн. фонд', NULL, 'ТЫС ТОМ КНИЖН ФОНД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(362, 'Условный ремонт', '915', 'усл. рем', NULL, 'УСЛ РЕМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(363, 'Условный ремонт в год', '916', 'усл. рем/год', NULL, 'УСЛ РЕМ/ГОД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(364, 'Смена', '917', 'смен', NULL, 'СМЕН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(365, 'Лист авторский', '918', 'л. авт', NULL, 'ЛИСТ АВТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(366, 'Лист печатный', '920', 'л. печ', NULL, 'ЛИСТ ПЕЧ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(367, 'Лист учетно-издательский', '921', 'л. уч.-изд', NULL, 'ЛИСТ УЧ.ИЗД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(368, 'Знак', '922', 'знак', NULL, 'ЗНАК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(369, 'Слово', '923', 'слово', NULL, 'СЛОВО', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(370, 'Символ', '924', 'символ', NULL, 'СИМВОЛ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(371, 'Условная труба', '925', 'усл. труб', NULL, 'УСЛ ТРУБ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(372, 'Тысяча пластин', '930', '10^3 пласт', NULL, 'ТЫС ПЛАСТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(373, 'Миллион доз', '937', '10^6 доз', NULL, 'МЛН ДОЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(374, 'Миллион листов-оттисков', '949', '10^6 лист.оттиск', NULL, 'МЛН ЛИСТ.ОТТИСК', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(375, 'Вагоно(машино)-день', '950', 'ваг (маш).дн', NULL, 'ВАГ (МАШ).ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(376, 'Тысяча вагоно-(машино)-часов', '951', '10^3 ваг (маш).ч', NULL, 'ТЫС ВАГ (МАШ).Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(377, 'Тысяча вагоно-(машино)-километров', '952', '10^3 ваг (маш).км', NULL, 'ТЫС ВАГ (МАШ).КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(378, 'Тысяча место-километров', '953', '10 ^3мест.км', NULL, 'ТЫС МЕСТ.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(379, 'Вагоно-сутки', '954', 'ваг.сут', NULL, 'ВАГ.СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(380, 'Тысяча поездо-часов', '955', '10^3 поезд.ч', NULL, 'ТЫС ПОЕЗД.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(381, 'Тысяча поездо-километров', '956', '10^3 поезд.км', NULL, 'ТЫС ПОЕЗД.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(382, 'Тысяча тонно-миль', '957', '10^3 т.миль', NULL, 'ТЫС Т.МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(383, 'Тысяча пассажиро-миль', '958', '10^3 пасс.миль', NULL, 'ТЫС ПАСС.МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(384, 'Автомобиле-день', '959', 'автомоб.дн', NULL, 'АВТОМОБ.ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(385, 'Тысяча автомобиле-тонно-дней', '960', '10^3 автомоб.т.дн', NULL, 'ТЫС АВТОМОБ.Т.ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(386, 'Тысяча автомобиле-часов', '961', '10^3 автомоб.ч', NULL, 'ТЫС АВТОМОБ.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(387, 'Тысяча автомобиле-место-дней', '962', '10^3 автомоб.мест. дн', NULL, 'ТЫС АВТОМОБ.МЕСТ. ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(388, 'Приведенный час', '963', 'привед.ч', NULL, 'ПРИВЕД.Ч', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(389, 'Самолето-километр', '964', 'самолет.км', NULL, 'САМОЛЕТ.КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(390, 'Тысяча километров', '965', '10^3 км', NULL, 'ТЫС КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(391, 'Тысяча тоннаже-рейсов', '966', '10^3 тоннаж. рейс', NULL, 'ТЫС ТОННАЖ. РЕЙС', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(392, 'Миллион тонно-миль', '967', '10^6 т. миль', NULL, 'МЛН Т. МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(393, 'Миллион пассажиро-миль', '968', '10^6 пасс. миль', NULL, 'МЛН ПАСС. МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(394, 'Миллион тоннаже-миль', '969', '10^6 тоннаж. миль', NULL, 'МЛН ТОННАЖ. МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(395, 'Миллион пассажиро-место-миль', '970', '10^6 пасс. мест. миль', NULL, 'МЛН ПАСС. МЕСТ. МИЛЬ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(396, 'Кормо-день', '971', 'корм. дн', NULL, 'КОРМ. ДН', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(397, 'Центнер кормовых единиц', '972', 'ц корм ед', NULL, 'Ц КОРМ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(398, 'Тысяча автомобиле-километров', '973', '10^3 автомоб. км', NULL, 'ТЫС АВТОМОБ. КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(399, 'Тысяча тоннаже-сут', '974', '10^3 тоннаж. сут', NULL, 'ТЫС ТОННАЖ. СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(400, 'Суго-сутки', '975', 'суго. сут.', NULL, 'СУГО. СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(401, 'Штук в 20-футовом эквиваленте (ДФЭ)', '976', 'штук в 20-футовом эквиваленте', NULL, 'ШТ В 20 ФУТ ЭКВИВ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(402, 'Канало-километр', '977', 'канал. км', NULL, 'КАНАЛ. КМ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(403, 'Канало-концы', '978', 'канал. конц', NULL, 'КАНАЛ. КОНЦ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(404, 'Тысяча экземпляров', '979', '10^3 экз', NULL, 'ТЫС ЭКЗ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(405, 'Тысяча долларов', '980', '10^3 доллар', NULL, 'ТЫС ДОЛЛАР', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(406, 'Тысяча тонн кормовых единиц', '981', '10^3 корм ед', NULL, 'ТЫС Т КОРМ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(407, 'Миллион тонн кормовых единиц', '982', '10^6 корм ед', NULL, 'МЛН Т КОРМ ЕД', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(408, 'Судо-сутки', '983', 'суд.сут', NULL, 'СУД.СУТ', NULL, 7, 2, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(409, 'Гектометр', '017', NULL, 'hm', NULL, 'HMT', 1, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(410, 'Миля (уставная) (1609,344 м)', '045', NULL, 'mile', NULL, 'SMI', 1, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(411, 'Акр (4840 квадратных ярдов)', '077', NULL, 'acre', NULL, 'ACR', 2, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(412, 'Квадратная миля', '079', NULL, 'mile2', NULL, 'MIK', 2, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(413, 'Жидкостная унция СК (28,413 см3)', '135', NULL, 'fl oz (UK)', NULL, 'OZI', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(414, 'Джилл СК (0,142065 дм3)', '136', NULL, 'gill (UK)', NULL, 'GII', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(415, 'Пинта СК (0,568262 дм3)', '137', NULL, 'pt (UK)', NULL, 'PTI', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(416, 'Кварта СК (1,136523 дм3)', '138', NULL, 'qt (UK)', NULL, 'QTI', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(417, 'Галлон СК (4,546092 дм3)', '139', NULL, 'gal (UK)', NULL, 'GLI', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(418, 'Бушель СК (36,36874 дм3)', '140', NULL, 'bu (UK)', NULL, 'BUI', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(419, 'Жидкостная унция США (29,5735 см3)', '141', NULL, 'fl oz (US)', NULL, 'OZA', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(420, 'Джилл США (11,8294 см3)', '142', NULL, 'gill (US)', NULL, 'GIA', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(421, 'Жидкостная пинта США (0,473176 дм3)', '143', NULL, 'liq pt (US)', NULL, 'PTL', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(422, 'Жидкостная кварта США (0,946353 дм3)', '144', NULL, 'liq qt (US)', NULL, 'QTL', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(423, 'Жидкостный галлон США (3,78541 дм3)', '145', NULL, 'gal (US)', NULL, 'GLL', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(424, 'Баррель (нефтяной) США (158,987 дм3)', '146', NULL, 'barrel (US)', NULL, 'BLL', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(425, 'Сухая пинта США (0,55061 дм3)', '147', NULL, 'dry pt (US)', NULL, 'PTD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(426, 'Сухая кварта США (1,101221 дм3)', '148', NULL, 'dry qt (US)', NULL, 'QTD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(427, 'Сухой галлон США (4,404884 дм3)', '149', NULL, 'dry gal (US)', NULL, 'GLD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(428, 'Бушель США (35,2391 дм3)', '150', NULL, 'bu (US)', NULL, 'BUA', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(429, 'Сухой баррель США (115,627 дм3)', '151', NULL, 'bbl (US)', NULL, 'BLD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(430, 'Стандарт', '152', NULL, '-', NULL, 'WSD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(431, 'Корд (3,63 м3)', '153', NULL, '-', NULL, 'WCD', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(432, 'Тысячи бордфутов (2,36 м3)', '154', NULL, '-', NULL, 'MBF', 3, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(433, 'Нетто-регистровая тонна', '182', NULL, '-', NULL, 'NTT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(434, 'Обмерная (фрахтовая) тонна', '183', NULL, '-', NULL, 'SHT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(435, 'Водоизмещение', '184', NULL, '-', NULL, 'DPT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(436, 'Фунт СК, США (0,45359237 кг)', '186', NULL, 'lb', NULL, 'LBR', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(437, 'Унция СК, США (28,349523 г)', '187', NULL, 'oz', NULL, 'ONZ', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(438, 'Драхма СК (1,771745 г)', '188', NULL, 'dr', NULL, 'DRI', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(439, 'Гран СК, США (64,798910 мг)', '189', NULL, 'gn', NULL, 'GRN', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(440, 'Стоун СК (6,350293 кг)', '190', NULL, 'st', NULL, 'STI', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(441, 'Квартер СК (12,700586 кг)', '191', NULL, 'qtr', NULL, 'QTR', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(442, 'Центал СК (45,359237 кг)', '192', NULL, '-', NULL, 'CNT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(443, 'Центнер США (45,3592 кг)', '193', NULL, 'cwt', NULL, 'CWA', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(444, 'Длинный центнер СК (50,802345 кг)', '194', NULL, 'cwt (UK)', NULL, 'CWI', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(445, 'Короткая тонна СК, США (0,90718474 т) [2*]', '195', NULL, 'sht', NULL, 'STN', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(446, 'Длинная тонна СК, США (1,0160469 т) [2*]', '196', NULL, 'lt', NULL, 'LTN', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(447, 'Скрупул СК, США (1,295982 г)', '197', NULL, 'scr', NULL, 'SCR', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(448, 'Пеннивейт СК, США (1,555174 г)', '198', NULL, 'dwt', NULL, 'DWT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(449, 'Драхма СК (3,887935 г)', '199', NULL, 'drm', NULL, 'DRM', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(450, 'Драхма США (3,887935 г)', '200', NULL, '-', NULL, 'DRA', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(451, 'Унция СК, США (31,10348 г); тройская унция', '201', NULL, 'apoz', NULL, 'APZ', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(452, 'Тройский фунт США (373,242 г)', '202', NULL, '-', NULL, 'LBT', 4, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(453, 'Эффективная мощность (245,7 ватт)', '213', NULL, 'B.h.p.', NULL, 'BHP', 5, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(454, 'Британская тепловая единица (1,055 кДж)', '275', NULL, 'Btu', NULL, 'BTU', 5, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(455, 'Гросс (144 шт.)', '638', NULL, 'gr; 144', NULL, 'GRO', 7, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(456, 'Большой гросс (12 гроссов)', '731', NULL, '1728', NULL, 'GGR', 7, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(457, 'Короткий стандарт (7200 единиц)', '738', NULL, '-', NULL, 'SST', 7, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(458, 'Галлон спирта установленной крепости', '835', NULL, '-', NULL, 'PGL', 7, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(459, 'Международная единица', '851', NULL, '-', NULL, 'NIU', 7, 3, 1, NULL)");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "unit` (unit_id, name, number_code, rus_name1, eng_name1, rus_name2, eng_name2, unit_group_id, unit_type_id, visible, comment) VALUES(460, 'Сто международных единиц', '853', NULL, '-', NULL, 'HIU', 7, 3, 1, NULL)");

		return 1;
	}	

}

