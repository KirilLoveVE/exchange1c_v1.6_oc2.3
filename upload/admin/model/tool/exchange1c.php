<?php

class ModelToolExchange1c extends Model {

	private $STORE_ID		= 0;
	private $LANG_ID		= 0;
	private $FULL_IMPORT	= false;
	private $NOW 			= '';
	private $TAB_FIELDS		= array();
	private $ERROR			= "";
	private $ERROR_NO		= '';



	/**
	 * ****************************** ОБЩИЕ ФУНКЦИИ ******************************
	 */


	/**
	 * Номер текущей версии
	 *
	 */
	public function version() {
		return "1.6.3.2";
	} // version()


	/**
	 * ver 1
	 * update 2017-04-08
	 * Пишет ошибку в лог
	 * Возвращает текст ошибки
	 */
	private function error() {
		$this->log($this->ERROR);
		return $this->ERROR;
	} // error()


	/**
	 * Пишет информацию в файл журнала
	 *
	 * @param	int				Уровень сообщения
	 * @param	string,object	Сообщение или объект
	 */
	private function log($message, $level = 1, $line = '') {
		if ($level <= $this->config->get('exchange1c_log_level')) {

			$memory_usage = '';
			if ($this->config->get('exchange1c_log_memory_use_view') == 1) {
				$memory_size = memory_get_usage() / 1024 / 1024;
				$memory_usage = sprintf("%.3f", $memory_size) . " Mb | ";
			}

			if ($this->config->get('exchange1c_log_debug_line_view') == 1) {
				if (!$line) {
					list ($di) = debug_backtrace();
					$line = sprintf("%04s",$di["line"]) . " | ";
				} else {
					$line .= " | ";
				}
			} else {
				$line = '';
			}

			if (is_array($message) || is_object($message)) {
				$this->log->write($memory_usage . $line);
				$this->log->write(print_r($message, true));
			} else {
				$this->log->write($memory_usage . $line . $message);
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
	 * Очистка лога
	 */
	private function clearLog() {

		$file = DIR_LOGS . $this->config->get('config_error_filename');
		$handle = fopen($file, 'w+');
		fclose($handle);

	} // clearLog()


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
	 * Выполняет запрос, записывает в лог в режим отладки и возвращает результат
	 */
	function query($sql){

		if ($this->config->get('exchange1c_log_debug_line_view') == 1) {
			list ($di) = debug_backtrace();
			$line = sprintf("%04s",$di["line"]);
		} else {
			$line = '';
		}

		$this->log($sql, 3, $line);
		return $this->db->query($sql);

	} // query()


	/**
	 * ver 2
	 * update 2017-04-06
	 * Проверим файл на стандарт Commerce ML
	 */
	private function checkCML($xml) {

		if ($xml['ВерсияСхемы']) {
			$this->log("Версия XML: " . (string)$xml['ВерсияСхемы'], 2);
		} else {
			$this->ERROR = "Файл не является стандартом Commerce ML!";
			$this->ERROR_NO = "0101";
			return false;
		}
		return true;

	} // checkCML()


	/**
	 * Очищает базу
	 */
	public function cleanDB() {

		$this->log("Очистка базы данных...",2);
		// Удаляем товары
		$result = "";

		$this->log("[i] Очистка таблиц товаров...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_attribute`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_description`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_discount`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_image`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_option_value`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_related`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_reward`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_special`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_quantity`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
		$result .=  "Товары\n";

		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_category`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_download`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_layout`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_store`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value_description`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_description`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_value`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'order_option`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'option`');
		$this->query('DELETE FROM `' . DB_PREFIX . 'url_alias` WHERE `query` LIKE "product_id=%"');
		$result .=  "Опции товаров\n";

		// Очищает таблицы категорий
		$this->log("Очистка таблиц категорий...",2);
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
		$this->query('DELETE FROM `' . DB_PREFIX . 'url_alias` WHERE `query` LIKE "category_id=%"');
		$result .=  "Категории\n";

  		// Очищает таблицы от всех производителей
		$this->log("Очистка таблиц производителей...",2);
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_1c');
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "` WHERE `Tables_in_" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "manufacturer_description'");
		//$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "manufacturer_description'");
		if ($query->num_rows) {
			$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
		}
		$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
		$this->query('DELETE FROM `' . DB_PREFIX . 'url_alias` WHERE `query` LIKE "manufacturer_id=%"');
		$result .=  "Производители\n";

		// Очищает атрибуты
		$this->log("Очистка таблиц атрибутов...",2);
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute`");
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_description`");
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group`");
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_group_description`");
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "` WHERE `Tables_in_" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "attribute_value'");
		if ($query->num_rows) {
			$this->log("Очистка значения атрибутов",2);
			$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_value');
			$result .=  "Значения атрибутов\n";
		}
		$result .=  "Атрибуты\n";

		// Удаляем все цены
		$this->log("Очистка цен...",2);
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "product_price`");
		$result .=  "Цены товаров\n";

		// Удаляем все характеристики
		$this->log("Очистка характеристик...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		//$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');
		$result .=  "Характеристики\n";

		// Удаляем связи с магазинами
		$this->log("Очистка связей с магазинами...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'store_to_1c`');
		$result .=  "Связи с магазинами\n";

		// Удаляем связи с единицами измерений
		$this->log("Очистка связей с единицами измерений...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'unit_to_1c`');
		$result .=  "Связи с единицами измерений\n";

		// Единицы измерений товара
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "` WHERE `Tables_in_" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "product_unit'");
		if ($query->num_rows) {
			$this->log("Очистка единиц измерений товаров",2);
			$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'product_unit');
			$result .=  "Единицы измерений товаров\n";
		}

		// Доработка от SunLit (Skype: strong_forever2000)
		// Удаляем все отзывы
		$this->log("Очистка отзывов...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'review`');
		$result .=  "Отзывы\n";

		return $result;
	} // cleanDB()


	/**
	 * Очищает базу
	 */
	public function cleanLinks() {
		// Удаляем связи
		$result = "";

		$this->log("[i] Очистка таблиц товаров...", 2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
		$result .=  "Таблица связей товаров '" . DB_PREFIX . "product_to_1c'\n";
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'category_to_1c`');
		$result .=  "Таблица связей категорий '" . DB_PREFIX . "category_to_1c'\n";
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'manufacturer_to_1c`');
		$result .=  "Таблица связей производителей '" . DB_PREFIX . "manufacturer_to_1c'\n";
		$this->query("TRUNCATE TABLE `" . DB_PREFIX . "attribute_to_1c`");
		$result .=  "Таблица связей атрибутов '" . DB_PREFIX . "attribute_to_1c'\n";
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'store_to_1c`');
		$result .=  "Таблица связей с магазинами\n";

		return $result;

	} // cleanLinks()


	/**
	 * Возвращает информацию о синхронизированных объектов с 1С товарок, категорий, атрибутов
	 */
	public function linksInfo() {

		$data = array();
		$query = $this->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'product_to_1c`');
		$data['product_to_1c'] = $query->row['num'];
		$query = $this->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'category_to_1c`');
		$data['category_to_1c'] = $query->row['num'];
		$query = $this->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'manufacturer_to_1c`');
		$data['manufacturer_to_1c'] = $query->row['num'];
		$query = $this->query('SELECT count(*) as num FROM `' . DB_PREFIX . 'attribute_to_1c`');
		$data['attribute_to_1c'] = $query->row['num'];

		return $data;

	} // linksInfo()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Удаляет все связи с товаром
	 */
	public function deleteLinkProduct($product_id) {

		$this->log("Удаление связей у товара product_id: " . $product_id, 2);

		// Удаляем линк
		if ($product_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
			$this->log("Удалена связь с товаром ID - GUID", 2);
		}

		$this->load->model('catalog/product');

		// Удаляет связи и сами файлы
		$product = $this->model_catalog_product->getProduct($product_id);
		if ($product['image']) {
			// Удаляем только в папке import_files
			if (substr($product['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $product['image']);
				$this->log("Удален файл основной картинки: " . $product['image'], 2);
			}
		}

		// Удаляет связи и сами файлы
		$productImages = $this->model_catalog_product->getProductImages($product_id);
		foreach ($productImages as $image) {
			// Удаляем только в папке import_files
			if (substr($image['image'], 0, 12) == "import_files") {
				unlink(DIR_IMAGE . $image['image']);
				$this->log("Удален файл дополнительной картинки: " . $image['image'],2);
			}
		}

		// Удалим характеристики
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_feature` WHERE `product_id` = " . $product_id);
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $product_id);
		$this->log("Удалены характеристики", 2);

		// Удалим остатки
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id);
		$this->log("Удалены остатки", 2);

		// Удалим единицы измерений
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_unit` WHERE `product_id` = " . $product_id);
		$this->log("Удалены единицы измерения", 2);

		// Описания к картинкам
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_image_description` WHERE `product_id` = " . $product_id);
		$this->log("Удалены описания к картинкам", 2);

	} // deleteLinkProduct()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Удаляет все связи у категории
	 */
	public function deleteLinkCategory($category_id) {

		// Удаляем линк
		if ($category_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "category_to_1c` WHERE `category_id` = " . (int)$category_id);
			$this->log("Удалена связь у категории category_id: " . $category_id,2);
		}

	} //  deleteLinkCategory()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Удаляет все связи у производителя
	 */
	public function deleteLinkManufacturer($manufacturer_id) {

		// Удаляем линк
		if ($manufacturer_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "manufacturer_to_1c` WHERE `manufacturer_id` = " . $manufacturer_id);
			$this->log("Удалена связь у производителя manufacturer_id: " . $manufacturer_id,2);
		}

	} //  deleteLinkManufacturer()


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

		$query = $this->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $this->db->escape($lang) . "'");
		$this->LANG_ID = $query->row['language_id'];
		$this->log("Определен язык language_id: " . $this->LANG_ID, 2);
		return $this->LANG_ID;

	} // getLanguageId()


	/**
	 * ver 5
	 * update 2017-05-02
	 * Проверяет таблицы модуля
	 */
	public function checkDB() {

		$tables_db = array();
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "`");
		if ($query->num_rows) {
			foreach ($query->rows as $table) {
				$tables_db[] = substr(array_shift($table), strlen(DB_PREFIX));
			}
		}

		$tables_module = array("product_to_1c","product_quantity","product_price","product_unit","category_to_1c","warehouse","product_feature","product_feature_value","store_to_1c","attribute_to_1c","manufacturer_to_1c","unit","attribute_value","product_image_description");
		$tables_diff = array_diff($tables_module, $tables_db);

		if ($tables_diff) {
			$error = "Таблица(ы) " . implode(", ", $tables_diff) . " в базе отсутствует(ют)";
			$this->log($error);
			return $error;
		}
		return "";

	} // checkDB()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Формирует строку запроса при наличии переменной
	 */
	private function setStrQuery($field_name, $type) {

		switch ($type){
			case 'string':
				return isset($data[$field_name]) ? ", " . $field_name . " = '" . $this->db->escape($data[$field_name]) . "'" : "";
			case 'int':
				return isset($data[$field_name]) ? ", " . $field_name . " = " . (int)$data[$field_name] : "";
			case 'float':
				return isset($data[$field_name]) ? ", " . $field_name . " = " . (float)$data[$field_name] : "";
		}
		return "";

	} //setStrQuery()


	/**
	 * Поиск guid товара по ID
	 */
	public function getGuidByProductId($product_id) {

		$query = $this->query("SELECT `guid` FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . $product_id);
		if ($query->num_rows) {
			return $query->row['guid'];
		}
		return '';

	} // getGuidByProductId()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ SEO ******************************
	 */


	/**
	 * Устанавливает SEO URL (ЧПУ) для заданного товара
	 * @param 	inf
	 * @param 	string
	 */
	private function setSeoURL($url_type, $element_id, $element_name) {

		// Проверка на одинаковые keyword
		$keyword = $element_name;

		// Получим все названия начинающиеся на $element_name
		$keywords = array();
		$query = $this->query("SELECT `url_alias_id`,`keyword` FROM `" . DB_PREFIX . "url_alias` WHERE `query` <> '" . $url_type . "=" . $element_id . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "%'");
		foreach ($query->rows as $row) {
			$keywords[$row['url_alias_id']] = $row['keyword'];
		}
		// Проверим на дубли
		$key = array_search($keyword, $keywords);
		$num = 0;
		while ($key) {
			// Есть дубли
			$num ++;
			$keyword = $element_name . "-" . (string)$num;
			$key = array_search($keyword, $keywords);
			if ($num > 100) {
				$this->log("[!] больше 100 дублей!", 2);
				break;
			}
		}

		$query = $this->query("SELECT `url_alias_id`,`keyword` FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $url_type . "=" . $element_id . "'");
		if ($query->num_rows) {
			// Обновляем если только были изменения
			$this->log("Старое keyword: " . $query->row['keyword'] . ", новое: " . $keyword);
			if ($query->row['keyword'] != $keyword) {
				$this->query("UPDATE `" . DB_PREFIX . "url_alias` SET `keyword` = '" . $this->db->escape($keyword) . "' WHERE `url_alias_id` = " . $query->row['url_alias_id']);
			}
		} else {
			$this->query("INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword` = '" . $this->db->escape($keyword) . "'");
		}
		$this->log("SeoURL сформирован для категории, keyword " . $keyword);

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
	 * Транслиетрирует RUS->ENG
	 * @param string $aString
	 * @return string type
	 * Автор: Константин Кирилюк
	 * url: http://www.chuvyr.ru/2013/11/translit.html
	 */
	function translit($s) {

		$s = (string) $s; // преобразуем в строковое значение
		$s = strip_tags($s); // убираем HTML-теги
		$s = str_replace(array("\n", "\r"), " ", $s); // убираем перевод каретки
		$s = trim($s); // убираем пробелы в начале и конце строки
		$s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
		$s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
		$s = preg_replace("/[^0-9a-z-_ ]/i", "", $s); // очищаем строку от недопустимых символов
  		$s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
		$s = str_replace(" ", "-", $s); // заменяем пробелы знаком минус
  		return $s; // возвращаем результат

	} // translit()


	/**
	 * Получает SEO_URL
	 */
	private function getSeoUrl($element, $id, $last_symbol = "") {

    	$query = $this->query("SELECT `keyword` FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $element . "=" . (string)$id . "'");
    	if ($query->num_rows) {
    		return $query->row['keyword'] . $last_symbol;
    	}
    	return "";

	} // getSeoUrl()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Получает название производителя в строку для SEO
	 */
    private function getProductManufacturerString($manufacturer_id) {

		$name = "";
		if (isset($this->TAB_FIELDS['manufacturer_description']['name'])) {
			$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer_description` WHERE `language_id` = " . $this->LANG_ID . " AND `manufacturer_id` = " . $manufacturer_id);
			if ($query->num_rows) {
				if ($query->row['name']) {
					$name = $query->row['name'];
				}
			}
		}
		if (!$name) {
			$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $manufacturer_id);
			if ($query->num_rows) {
				if ($query->row['name']) {
					$name = $query->row['name'];
				}
			}
		}
		return $name;

      } // getProductManufacturerString()


	/**
	 * Получает все категории продукта в строку для SEO
	 */
    private function getProductCategoriesString($product_id) {

 		$categories = array();
		$query = $this->query("SELECT `c`.`category_id`, `cd`.`name` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) INNER JOIN `" . DB_PREFIX . "product_to_category` `pc` ON (`pc`.`category_id` = `c`.`category_id`) WHERE `cd`.`language_id` = " . $this->LANG_ID . " AND `pc`.`product_id` = " . $product_id . " ORDER BY `c`.`sort_order`, `cd`.`name` ASC");
		foreach ($query->rows as $category) {
			$categories[] = $category['name'];
		}
		$cat_string = implode(',', $categories);
		return $cat_string;

      } // getProductCategoriesString()


	/**
	 * Получает все категории продукта в массив
	 * первым в массиме будет главная категория
	 */
    private function getProductCategories($product_id) {

		$main_category = isset($this->TAB_FIELDS['product_to_category']['main_category']) ? ",`main_category`" : "";
		$query = $this->query("SELECT `category_id`" . $main_category . " FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $product_id);
		$categories = array();
		foreach ($query->rows as $category) {
			if ($main_category && $category['main_category']) {
				// главную категорию добавляем в начало массива
				array_unshift($categories, $category['category_id']);
			} else {
				$categories[] = $category['category_id'];
			}
		}
		return $categories;

    } // getProductCategories()


	/**
	 * ver 2
	 * update 2017-04-18
	 * Генерит SEO строк. Заменяет паттерны на их значения
	 */
	private function seoGenerateString($template, $product_tags, $trans = false, $split = false) {

		// Выберем все теги которые используются в шаблоне
		preg_match_all('/\{(\w+)\}/', $template, $matches);
		$values = array();

		foreach ($matches[0] as $match) {
			$value = isset($product_tags[$match]) ? $product_tags[$match] : '';
			if ($trans) {
				$values[] = $this->translit($value);
			} else {
				$values[] = $value;
			}
		}
		$seo_string = trim(str_replace($matches[0], $values, $template));
		if ($split) {
			$seo_string = $this->getKeywordString($seo_string);
		}
		return $seo_string;

	} // seoGenerateString()


	/**
	 * Генерит ключевую строку из строки
	 */
	private function getKeywordString($str) {

		// Переведем в массив по пробелам
		$s = strip_tags($str); // убираем HTML-теги
  		$s = preg_replace("/\s+/", " ", $s); // удаляем повторяющие пробелы
  		$s = preg_replace("/\,+/", "", $s); // удаляем повторяющие запятые
  		$s = preg_replace("~(&lt;)([^&]+)(&gt;)~isu", "", $s); // удаляем HTML символы
		$s = preg_replace("![^\w\d\s]*!", "", $s); // очищаем строку от недопустимых символов
		$in_obj = explode(' ', $s);
		$out_obj = array();
		foreach ($in_obj as $s) {
			if (function_exists('mb_strlen')) {
				if (mb_strlen($s) < 3) {
					// пропускаем слова длиной менее 3 символов
					continue;
				}
			}
			$out_obj[] = $s;
		}
		// Удаляем повторяющиеся значения
		$out_obj = array_unique($out_obj);
		$str_out = implode(', ', $out_obj);

		return $str_out;

	} // getKeywordString()


	/**
	 * Генерит SEO переменные шаблона для товара
	 */
	private function seoGenerateProduct(&$data) {

		if ($this->config->get('exchange1c_seo_product_mode') == 'disable') {
			return;
		}
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
			'{name}'		=> isset($data['name']) 			? $data['name']			: '',
			'{fullname}'	=> isset($data['full_name']) 		? $data['full_name']	: $data['name'],
			'{sku}'			=> isset($data['sku'])				? $data['sku']			: '',
			'{model}'		=> isset($data['model'])			? $data['model']		: '',
			'{brand}'		=> isset($data['manufacturer_id'])	? $this->getProductManufacturerString($data['manufacturer_id']) : '',
			'{cats}'		=> $this->getProductCategoriesString($data['product_id']),
			'{prod_id}'		=> isset($data['product_id'])		? $data['product_id']	: '',
			'{cat_id}'		=> isset($data['category_id'])		? $data['category_id']	: ''
		);
		if (isset($this->TAB_FIELDS['product_description']['meta_h1'])) {
			$seo_fields['meta_h1'] = array();
		}
		// Получим поля для сравнения
		$fields_list = array();

		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') {
				continue;
			}
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');

		if (!isset($data['name']))
			$fields .= ", name";
		$query = $this->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID);

		foreach ($fields_list as $field) {
			$data[$field] = isset($query->row[$field]) ?  $query->row[$field] : "";
		}

		if (!isset($data['name']) && isset($query->row['name'])) {
			$data['name'] = $query->row['name'];
			$tags['{name}']	= $data['name'];
		}

		// Прочитаем старый SEO URL
		if (isset($seo_fields['seo_url'])) {
			$data['seo_url'] = $this->getSeoUrl("product_id", $data['product_id']);
			$data['seo_url_old'] = $data['seo_url'];
		}

		$update = false;
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_product_'.$field.'_template');

				if (!$template) {
					unset($data[$field]);
					continue;
				}

				if ($this->config->get('exchange1c_seo_product_mode') == 'overwrite') {
					// Перезаписывать
					$old_value = '';
					if (isset($data[$field])) {
						$old_value = $data[$field];
					}
					if ($field == 'meta_keyword' || $field == 'tag') {
						$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']), true);
					} else {
						$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
					}

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($old_value == $data[$field]) {
						unset($data[$field]);
					} else {
						$update = true;
						$this->log("Старое значение '".$field."' = '" . $old_value . "'", 2);
						$this->log("Новое значение '" . $field . "' = '" . $data[$field] . "'", 2);
					}

				} else {
					if (!isset($data[$field])) {
						continue;
					}
					// Только если поле пустое
					$this->log("Старое значение '".$field."' = '" . $data[$field] . "'", 2);
					if (empty($data[$field])) {
						$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
						$update = true;
					} else {
						$this->log("Пропускаем '" . $field . "', т.к. не пустое", 2);
						unset($data[$field]);
						continue;
					}
				}
			} else {
				unset($data[$field]);
				continue;
			}
		}
		if (isset($data['seo_url']) && $data['product_id']) {
			if ($data['seo_url_old'] != $data['seo_url']) {
				$this->setSeoURL('product_id', $data['product_id'], $data['seo_url']);
			}
		}
		if (isset($data['seo_url_old'])) {
			unset($data['seo_url_old']);
		}
		$this->log("Сформировано SEO для товара product_id: " . $data['product_id']);
		return $update;

	} // seoGenerateProduct()


	/**
	 * ver 2
	 * update 2017-04-30
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateCategory(&$data) {

		if ($this->config->get('exchange1c_seo_category_mode') == 'disable') {
			return false;
		}

		// Товары, Категории
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);

		if (isset($this->TAB_FIELDS['category_description']['meta_h1'])) {
			$seo_fields['meta_h1'] = array();
		}

		// Получим поля для сравнения
		$fields_list = array();
		foreach ($seo_fields as $field=>$param) {
			if ($field == 'seo_url') {
				continue;
			}
			$fields_list[] = $field;
		}
		$fields	= implode($fields_list,', ');
		$query = $this->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID);

		// Если записей вообще небыло, присваиваем пустые
		foreach ($fields_list as $field) {
			$data[$field] = isset($query->row[$field]) ?  $query->row[$field] : "";
		}

		// Прочитаем старый SEO URL
		if (isset($seo_fields['seo_url'])) {
			$data['seo_url'] = $this->getSeoUrl("category_id", $data['category_id']);
			$data['seo_url_old'] = $data['seo_url'];
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{cat}'			=> isset($data['name']) 		? $data['name'] 		: '',
			'{cat_id}'		=> isset($data['category_id'])	? $data['category_id'] 	: ''
		);

		$update = false;
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_category_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_category_'.$field.'_template');

				if (!$template) {
					unset($data[$field]);
					continue;
				}

				if ($this->config->get('exchange1c_seo_category_mode') == 'overwrite') {

					$old_value = $data[$field];

					// Перезаписывать
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($old_value == $data[$field]) {
						unset($data[$field]);
					} else {
						$this->log("Поле: '" . $field . "' старое: '" . $old_value . "', новое: " . $data[$field] . "'", 2);
						$update = true;
					}

				} else {
					if (!isset($data[$field])) {
						continue;
					}
					// Только если поле пустое
					$this->log("Старое значение '" . $field . "' = '" . $data[$field] . "'", 2);
					if (empty($data[$field])) {
						$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
						$update = true;
					} else {
						$this->log("Пропускаем '" . $field . "', т.к. не пустое", 2);
						unset($data[$field]);
					}
				}

			} else {
				unset($data[$field]);
				continue;
			}
		}

		if (isset($data['seo_url']) && $data['category_id']) {
			if ($data['seo_url_old'] != $data['seo_url']) {
				$this->setSeoURL('category_id', $data['category_id'], $data['seo_url']);
			}
			unset($data['seo_url_old']);
		}
		if (isset($data['seo_url_old'])) {
			unset($data['seo_url_old']);
		}

		$this->log("Сформировано SEO для категории category_id: " . $data['category_id']);
		return $update;

	} // seoGenerateCategory()


	/**
	 * ver 4
	 * update 2017-04-30
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateManufacturer(&$data) {

		if ($this->config->get('exchange1c_seo_manufacturer_mode') == 'disable') {
			return false;
		}

		// Производители
		$seo_fields = array(
			'seo_url'			=> array('trans' => true),
			'meta_title'		=> array(),
			'meta_description'	=> array(),
			'meta_keyword'		=> array(),
		);

		if (isset($this->TAB_FIELDS['product_description'])) {
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_h1'])) {
				$seo_fields['meta_h1'] = array();
			}
			// Получим поля для сравнения
			$fields_list = array();
			foreach ($seo_fields as $field=>$param) {
				if ($field == 'seo_url') {
					continue;
				}
				$fields_list[] = $field;
			}
			$fields	= implode($fields_list,', ');

			if (isset($this->TAB_FIELDS['manufacturer_description'])) {
				$query = $this->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID);
				foreach ($fields_list as $field) {
					$data[$field] = isset($query->row[$field]) ?  $query->row[$field] : "";
				}
			}
		}

		// Прочитаем старый SEO URL
		if (isset($seo_fields['seo_url'])) {
			$data['seo_url'] = $this->getSeoUrl("manufacturer_id", $data['manufacturer_id']);
			$data['seo_url_old'] = $data['seo_url'];
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{brand}'		=> isset($data['name']) 			? $data['name'] 			: '',
			'{brand_id}'	=> isset($data['manufacturer_id'])	? $data['manufacturer_id'] 	: ''
		);

		$update = false;
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field=>$param) {
			$template = '';
			if ($this->config->get('exchange1c_seo_manufacturer_' . $field) == 'template') {
				$template = $this->config->get('exchange1c_seo_manufacturer_' . $field . '_template');

				if (!$template) {
					unset($data[$field]);
					continue;
				}

				if ($this->config->get('exchange1c_seo_manufacturer_mode') == 'overwrite') {

					$old_value = $data[$field];

					// Перезаписывать
					$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($old_value == $data[$field]) {
						unset($data[$field]);
					} else {
						$this->log("Значение поля:  '" . $field . "', старое:  '" . $old_value . "', новое: " . $data[$field], 2);
						$update = true;
					}

				} else {
					if (!isset($data[$field])) {
						continue;
					}
					// Только если поле пустое
					$this->log("Старое значение '" . $field . "' = '" . $data[$field] . "'", 2);
					if (empty($data[$field])) {
						$data[$field] = $this->seoGenerateString($template, $tags, isset($param['trans']));
						$update = true;
					} else {
						$this->log("Пропускаем '" . $field . "', т.к. не пустое", 2);
						unset($data[$field]);
					}
				}
			} else {
				unset($data[$field]);
				continue;
			}

		}

		if (isset($data['seo_url']) && $data['manufacturer_id']) {
			if ($data['seo_url_old'] != $data['seo_url']) {
				$this->setSeoURL('manufacturer_id', $data['manufacturer_id'], $data['seo_url']);
			}
			unset($data['seo_url_old']);
		}
		if (isset($data['seo_url_old'])) {
			unset($data['seo_url_old']);
		}

		if ($update) {
			$this->log("Сформировано SEO для производителя: " . $data['name']);
		}
		return $update;

	} // seoGenerateManufacturer()


	/**
	 * ver 2
	 * update 2017-05-03
	 * Генерит SEO переменные шаблона для товара
	 */
	public function seoGenerate() {

        $now = date('Y-m-d H:i:s');
		$result = array('error'=>'','product'=>0,'category'=>0,'manufacturer'=>0);

		$language_id = $this->getLanguageId($this->config->get('config_language'));

		// Выбрать все товары, нужны поля:
		// name, sku, model, manufacturer_id, description, product_id, category_id
		if (isset($this->TAB_FIELDS['product_description']['meta_h1'])) {
			$sql = "SELECT `p`.`product_id`, `p`.`sku`, `p`.`model`, `p`.`manufacturer_id`, `pd`.`name`, `pd`.`tag`, `pd`.`meta_title`, `pd`.`meta_description`, `pd`.`meta_keyword`, `pd`.`meta_h1` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `pd.`language_id` = " . $language_id;
			$fields_include = 'name,tag,meta_title,meta_description,meta_keyword,meta_h1';
		} else {
			$sql = "SELECT `p`.`product_id`, `p`.`sku`, `p`.`model`, `p`.`manufacturer_id`, `pd`.`name`, `pd`.`tag`, `pd`.`meta_title`, `pd`.`meta_description`, `pd`.`meta_keyword` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `pd`.`language_id` = " . $language_id;
			$fields_include = 'name,tag,meta_title,meta_description,meta_keyword';
		}

		$query = $this->query($sql);
		if ($query->num_rows) {
			foreach ($query->rows as $data) {

				$result['product']++;
 				$data_old = $data;
				$update = $this->seoGenerateProduct($data);

				if (!$update) {
					continue;
				}

				// Сравнение
				$fields = $this->compareArraysNew($data_old, $data, 'sku,model,manufacturer_id');

				// Если есть что обновлять
				if ($fields) {
					$this->query("UPDATE `" . DB_PREFIX . "product` SET " . $fields . ", `date_modified` = '" . $now . "' WHERE `product_id` = " . $data['product_id']);
				}

				// Сравнение
				$fields = $this->compareArraysNew($data_old, $data, $fields_include);

				// Если есть что обновлять
				if ($fields) {
					$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $fields . " WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $language_id);
				}
			}
		}

		// Категории

		// Выбрать все категории, нужны поля:
		// name, sku, model, manufacturer_id, description, product_id, category_id
		if (isset($this->TAB_FIELDS['category_description']['meta_h1'])) {
			$sql = "SELECT `c`.`category_id`, `cd`.`name`, `cd`.`meta_title`, `cd`.`meta_description`, `cd`.`meta_keyword`, `cd`.`meta_h1` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`language_id` = " . $language_id;
			$fields_include = 'name,meta_title,meta_description,meta_keyword,meta_h1';
		} else {
			$sql = "SELECT `c`.`category_id`, `cd`.`name`, `cd`.`meta_title`, `cd`.`meta_description`, `cd`.`meta_keyword` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`language_id` = " . $language_id;
			$fields_include = 'name,meta_title,meta_description,meta_keyword';
		}

		$query = $this->query($sql);
		if ($query->num_rows) {
			foreach ($query->rows as $data) {

				$result['category']++;
				$this->seoGenerateCategory($data);

				// Сравнение
				$fields = $this->compareArraysNew($data_old, $data, $fields_include);

				// Если есть что обновлять
				if ($fields) {
					$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $language_id);
					$this->query("UPDATE `" . DB_PREFIX . "category` SET `date_modified` = '" . $now . "' WHERE `category_id` = " . $data['category_id']);
				}
			}
		}

		// Производители

		if (isset($this->TAB_FIELDS['manufacturer_description'])) {
			// Выбрать все категории, нужны поля:
			// name, sku, model, manufacturer_id, description, product_id, category_id
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_h1'])) {
				$sql = "SELECT `m`.`manufacturer_id`, `md`.`name`, `md`.`meta_title`, `md`.`meta_description`, `md`.`meta_keyword`, `md`.`meta_h1` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_description` `md` ON (`m`.`manufacturer_id` = `md`.`manufacturer_id`) WHERE `md`.`language_id` = " . $language_id;
				$fields_include = 'name,meta_title,meta_description,meta_keyword,meta_h1';
			} else {
				$sql = "SELECT `m`.`manufacturer_id`, `md`.`name`, `md`.`meta_title`, `md`.`meta_description`, `md`.`meta_keyword` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_description` `md` ON (`m`.`manufacturer_id` = `md`.`manufacturer_id`) WHERE `md`.`language_id` = " . $language_id;
				$fields_include = 'name,meta_title,meta_description,meta_keyword';
			}

			$query = $this->query($sql);
			if ($query->num_rows) {
				foreach ($query->rows as $data) {

					$result['manufacturer']++;

					$data_old = $data;
					$update = $this->seoGenerateManufacturer($data);

					if (!$update) {
						continue;
					}

					// Сравнение
					$fields = $this->compareArraysNew($data_old, $data, $fields_include);

					// Если есть что обновлять
					if ($fields) {
						$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $language_id);
						$this->query("UPDATE `" . DB_PREFIX . "category` SET `date_modified` = '" . $now . "' WHERE `category_id` = " . $data['category_id']);
					}
				}
			}

		}

		return $result;

	} // seoGenerate()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ КАТАЛОГА ******************************
	 */

	/**
	 * Формирует строку запроса для категории
	 */
	private function prepareStrQueryCategory($data, $mode = 'set') {

		$sql = array();

		if (isset($data['top']))
			$sql[] = $mode == 'set' ? "`top` = " .			(int)$data['top']								: "top";
		if (isset($data['column']))
			$sql[] = $mode == 'set' ? "`column` = " .		(int)$data['column']							: "column";
		if (isset($data['sort_order']))
			$sql[] = $mode == 'set' ? "`sort_order` = " . 	(int)$data['sort_order']						: "sort_order";
		if (isset($data['status']))
			$sql[] = $mode == 'set' ? "`status` = " . 		(int)$data['status']							: "status";
		if (isset($data['noindex']))
			$sql[] = $mode == 'set' ? "`noindex` = " . 		(int)$data['noindex']							: "noindex";
		if (isset($data['parent_id']))
			$sql[] = $mode == 'set' ? "`parent_id` = " . 	(int)$data['parent_id']							: "parent_id";
		if (isset($data['image']))
			$sql[] = $mode == 'set' ? "`image` = '" . 		$this->db->escape((string)$data['image']) . "'"	: "image";

		return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

	} //prepareStrQueryCategory()


	/**
	 * Формирует строку запроса для описания категорий и товаров
	 */
	private function prepareStrQueryDescription($data, $mode = 'set') {

		$sql = array();
		if (isset($data['name']))
			$sql[] = $mode == 'set' 	? "`name` = '" .				$this->db->escape($data['name']) . "'"				: "`name`";
		if (isset($data['description']))
			$sql[] = $mode == 'set' 	? "`description` = '" .			$this->db->escape($data['description']) . "'"		: "`description`";
		if (isset($data['meta_title']))
			$sql[] = $mode == 'set' 	? "`meta_title` = '" .			$this->db->escape($data['meta_title']) . "'"		: "`meta_title`";
		if (isset($data['meta_h1']))
			$sql[] = $mode == 'set' 	? "`meta_h1` = '" .				$this->db->escape($data['meta_h1']) . "'"			: "`meta_h1`";
		if (isset($data['meta_description']))
			$sql[] = $mode == 'set' 	? "`meta_description` = '" .	$this->db->escape($data['meta_description']) . "'"	: "`meta_description`";
		if (isset($data['meta_keyword']))
			$sql[] = $mode == 'set' 	? "`meta_keyword` = '" .		$this->db->escape($data['meta_keyword']) . "'"		: "`meta_keyword`";
		if (isset($data['tag']))
			$sql[] = $mode == 'set' 	? "`tag` = '" .					$this->db->escape($data['tag']) . "'"				: "`tag`";

		return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

	} //prepareStrQueryDescription()


	/**
	 * ver 2
	 * update 2017-04-10
	 * Подготавливает запрос для товара
	 */
	private function prepareQueryProduct($data, $mode = 'set') {

		$sql = array();
		if (isset($data['model']))
	 		$sql[] = $mode == 'set'		? "`model` = '" .				$this->db->escape($data['model']) . "'"				: "`model`";
		if (isset($data['sku']))
	 		$sql[] = $mode == 'set'		? "`sku` = '" .					$this->db->escape($data['sku']) . "'"				: "`sku`";
		if (isset($data['upc']))
	 		$sql[] = $mode == 'set'		? "`upc` = '" .					$this->db->escape($data['upc']) . "'"				: "`upc`";
		if (isset($data['ean']))
	 		$sql[] = $mode == 'set'		? "`ean` = '" .					$this->db->escape($data['ean']) . "'"				: "`ean`";
		if (isset($data['jan']))
	 		$sql[] = $mode == 'set'		? "`jan` = '" .					$this->db->escape($data['jan']) . "'"				: "`jan`";
		if (isset($data['isbn']))
	 		$sql[] = $mode == 'set'		? "`isbn` = '" .				$this->db->escape($data['isbn']) . "'"				: "`isbn`";
		if (isset($data['mpn']))
	 		$sql[] = $mode == 'set'		? "`mpn` = '" .					$this->db->escape($data['mpn']) . "'"				: "`mpn`";
		if (isset($data['location']))
	 		$sql[] = $mode == 'set'		? "`location` = '" .			$this->db->escape($data['location']) . "'"			: "`location`";
		if (isset($data['quantity']))
	 		$sql[] = $mode == 'set'		? "`quantity` = '" .			(float)$data['quantity'] . "'"						: "`quantity`";
		if (isset($data['minimum']))
	 		$sql[] = $mode == 'set'		? "`minimum` = '" .				(float)$data['minimum'] . "'"						: "`minimum`";
		if (isset($data['subtract']))
	 		$sql[] = $mode == 'set'		? "`subtract` = '" .			(int)$data['subtract'] . "'"						: "`subtract`";
		if (isset($data['stock_status_id']))
	 		$sql[] = $mode == 'set'		? "`stock_status_id` = '" .		(int)$data['stock_status_id'] . "'"					: "`stock_status_id`";
		if (isset($data['image']))
	 		$sql[] = $mode == 'set'		? "`image` = '" .				$this->db->escape($data['image']) . "'"				: "`image`";
		if (isset($data['date_available']))
	 		$sql[] = $mode == 'set'		? "`date_available` = '" .		$this->db->escape($data['date_available']) . "'"	: "`date_available`";
		if (isset($data['manufacturer_id']))
	 		$sql[] = $mode == 'set'		? "`manufacturer_id` = '" .		(int)$data['manufacturer_id'] . "'"					: "`manufacturer_id`";
		if (isset($data['shipping']))
	 		$sql[] = $mode == 'set'		? "`shipping` = '" .			(int)$data['shipping'] . "'"						: "`shipping`";
		if (isset($data['price']))
	 		$sql[] = $mode == 'set'		? "`price` = '" .				(float)$data['price'] . "'"							: "`price`";
		if (isset($data['points']))
	 		$sql[] = $mode == 'set'		? "`points` = '" .				(int)$data['points'] . "'"							: "`points`";
		if (isset($data['length']))
	 		$sql[] = $mode == 'set'		? "`length` = '" .				(float)$data['length'] . "'"						: "`length`";
		if (isset($data['width']))
	 		$sql[] = $mode == 'set'		? "`width` = '" .				(float)$data['width'] . "'"							: "`width`";
		if (isset($data['weight']))
	 		$sql[] = $mode == 'set'		? "`weight` = '" .				(float)$data['weight'] . "'"						: "`weight`";
		if (isset($data['height']))
	 		$sql[] = $mode == 'set'		? "`height` = '" .				(float)$data['height'] . "'"						: "`height`";
		if (isset($data['status']))
	 		$sql[] = $mode == 'set'		? "`status` = '" .				(int)$data['status'] . "'"							: "`status`";
		if (isset($data['noindex']))
	 		$sql[] = $mode == 'set'		? "`noindex` = '" .				(int)$data['noindex'] . "'"							: "`noindex`";
		if (isset($data['tax_class_id']))
	 		$sql[] = $mode == 'set'		? "`tax_class_id` = '" .		(int)$data['tax_class_id'] . "'"					: "`tax_class_id`";
		if (isset($data['sort_order']))
	 		$sql[] = $mode == 'set'		? "`sort_order` = '" .			(int)$data['sort_order'] . "'"						: "`sort_order`";
		if (isset($data['length_class_id']))
	 		$sql[] = $mode == 'set'		? "`length_class_id` = '" .		(int)$data['length_class_id'] . "'"					: "`length_class_id`";
		if (isset($data['weight_class_id']))
	 		$sql[] = $mode == 'set'		? "`weight_class_id` = '" .		(int)$data['weight_class_id'] . "'"					: "`weight_class_id`";

		return implode(($mode = 'set' ? ', ' : ' AND '),$sql);

	} // prepareQueryProduct()



	/**
	 * Формирует строку запроса для описания производителя
	 */
	private function prepareStrQueryManufacturerDescription($data) {

		$sql  = isset($data['description']) 		? ", `description` = '" . $this->db->escape($data['description']) . "'"					: "";
		if (isset($this->TAB_FIELDS['manufacturer_description']['name'])) {
			$sql .= isset($data['name']) 				? ", `name` = '" . $this->db->escape($data['name']) . "'" 							: "";
		}
		$sql .= isset($data['meta_description']) 	? ", `meta_description` = '" . $this->db->escape($data['meta_description']) . "'" 		: "";
		$sql .= isset($data['meta_keyword']) 		? ", `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['meta_title']) 			? ", `meta_title` = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_h1']) 			? ", `meta_h1` = '" . $this->db->escape($data['meta_h1']) . "'" 						: "";

		return $sql;

	} //prepareStrQueryManufacturerDescription()


	/**
	 * Сравнивает запрос с массивом данных и формирует список измененных полей
	 */
	private function compareArrays($query, $data) {

		// Сравниваем значения полей, если есть изменения, формируем поля для запроса
		$upd_fields = array();
		if ($query->num_rows) {
			foreach($query->row as $key => $row) {
				if (!isset($data[$key])) continue;
				if ($row <> $data[$key]) {
					$upd_fields[] = "`" . $key . "` = '" . $this->db->escape($data[$key]) . "'";
					$this->log("[i] Отличается поле '" . $key . "', старое: " . $row . ", новое: " . $data[$key], 2);
				}
			}
		}

		return implode(', ', $upd_fields);

	} // compareArrays()


	/**
	 * ver 2
	 * update 2017-04-08
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories($product_categories) {

		// Подгружаем только один раз
		if (empty($product_categories)) {
			$this->ERROR = "fillParentsCategories() - нет категорий";
			return false;;
		}

		$this->load->model('catalog/product');

		foreach ($product_categories as $category_id) {
			$parents = $this->findParentsCategories($category_id);
			foreach ($parents as $parent_id) {
				$key = array_search($parent_id, $product_categories);
				if ($key === false)
					$product_categories[] = $parent_id;
			}
		}

		return $product_categories;

	} // fillParentsCategories()


	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {

		$result = array();
		$query = $this->query("SELECT * FROM `" . DB_PREFIX ."category` WHERE `category_id` = " . (int)$category_id);
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
		$this->log("Установлен магазин store_id: " . $this->STORE_ID);

	} // setStore()


	/**
	 * Возвращает id по GUID
	 */
	private function getCategoryIdByGuid($guid) {

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_to_1c` WHERE `guid` = '" . $this->db->escape($guid) . "'");
		$category_id = isset($query->row['category_id']) ? $query->row['category_id'] : 0;

		// Проверим существование такого товара
		if ($category_id) {
			$query = $this->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` = " . (int)$category_id);
			if (!$query->num_rows) {

				// Удалим неправильную связь
				$this->deleteLinkCategory($category_id);
				$category_id = 0;
			}
		}
		return $category_id;

	} // getCategoryIdByGuid()


	/**
	 * Возвращает id по коду
	 */
	private function getCategoryIdByCode($code) {

		$query = $this->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `category_id` = " . (int)$code);
		if (isset($query->row['category_id'])) {
			return $query->row['category_id'];
		} else {
			return 0;
		}

	} // getCategoryIdByCode()


	/**
	 * Возвращает id по названию и уровню категории
	 */
	private function getCategoryIdByName($name, $parent_id = 0) {

		$query = $this->query("SELECT `c`.`category_id` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX. "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`name` = LOWER('" . $this->db->escape(strtolower($name)) . "') AND `cd`.`language_id` = " . $this->LANG_ID . " AND `c`.`parent_id` = " . $parent_id);
		return $query->num_rows ? $query->row['category_id'] : 0;

	} // getCategoryIdByName()


	/**
	 * Возвращает массив id,name категории по GUID
	 */
	private function getCategoryByGuid($guid) {

		$query = $this->query("SELECT `c`.`category_id`, `cd`.`name` FROM `" . DB_PREFIX . "category_to_1c` `c` LEFT JOIN `" . DB_PREFIX. "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `c`.`guid` = '" . $this->db->escape($guid) . "' AND `cd`.`language_id` = " . $this->LANG_ID);
		return $query->num_rows ? $query->rows : 0;

	} // getCategoryByGuid()


	/**
	 * ver 2
	 * update 2017-05-02
	 * Обновляет описание категории
	 */
	private function updateCategoryDescription($data) {

		// Надо ли обновлять
		$fields = $this->prepareStrQueryDescription($data, 'get');
		if ($fields) {
			$query = $this->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "category_description` `cd` LEFT JOIN `" . DB_PREFIX . "category_to_store` `cs` ON (`cd`.`category_id` = `cs`.`category_id`) WHERE `cd`.`category_id` = " . $data['category_id'] . " AND `cd`.`language_id` = " . $this->LANG_ID . " AND `cs`.`store_id` = " . $this->STORE_ID);
			if (!$query->num_rows) {
				$set_fields = $this->prepareStrQueryDescription($data, 'set');
				$this->query("INSERT INTO `" . DB_PREFIX . "category_description` SET " . $set_fields . ", `category_id` = " . $data['category_id'] . ", `language_id` = " . $this->LANG_ID);
			}
		} else {
			// Нечего даже обновлять
			return false;
		}

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

		// Если есть расхождения, производим обновление
		if ($fields) {
			$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID);
			$this->query("UPDATE `" . DB_PREFIX . "category` SET `date_modified` = '" . $this->NOW . "' WHERE `category_id` = " . $data['category_id']);
			$this->log("> Обновлены поля категории: '" . $fields . "'", 2);
			return true;
		}
		return false;

	} // updateCategoryDescription()


	/**
	 * Добавляет иерархию категории
	 */
	private function addHierarchical($category_id, $data) {

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");
		foreach ($query->rows as $result) {
			$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_id . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);
			$level++;
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_id . ", `path_id` = " . $category_id . ", `level` = " . $level);

	} // addHierarchical()


	/**
	 * Обновляет иерархию категории
	 */
	private function updateHierarchical($data) {

		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `path_id` = " . $data['category_id'] . " ORDER BY `level` ASC");

		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " AND `level` < " . (int)$category_path['level']);
				$path = array();
				// Get the nodes new parents
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");
				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}
				// Get whats left of the nodes current path
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $category_path['category_id'] . " ORDER BY `level` ASC");
				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}
				// Combine the paths with a new level
				$level = 0;
				foreach ($path as $path_id) {
					$this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $category_path['category_id'] . ", `path_id` = " . $path_id . ", `level` = " . $level);

					$level++;
				}
			}
		} else {
			// Delete the path below the current one
			$this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['category_id']);
			// Fix for records with no paths
			$level = 0;
			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . $data['parent_id'] . " ORDER BY `level` ASC");
 			foreach ($query->rows as $result) {
				$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $data['category_id'] . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);

				$level++;
			}
 			$this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . $data['category_id'] . ", `path_id` = " . $data['category_id'] . ", `level` = " . $level);
		}

		$this->log("<== updateHierarchical()", 2);

	} // updateHierarchical()


	/**
	 * Обновляет категорию
	 */
	private function updateCategory($data) {

		// Читаем старые данные
		$sql = $this->prepareStrQueryCategory($data, 'get');
		if ($sql) {
			$query = $this->query("SELECT " . $sql . " FROM `" . DB_PREFIX . "category` WHERE `category_id` = " . $data['category_id']);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$fields = $this->compareArrays($query, $data);

			if ($fields) {
				$this->query("UPDATE `" . DB_PREFIX . "category` SET " . $fields . ", `date_modified` = '" . $this->NOW . "' WHERE `category_id` = " . $data['category_id']);

				$this->log("Обновлена категория '" . $data['name'] . "'", 2);

				// Запись иерархии категорий если были изменения
				$this->updateHierarchical($data);
			}
		} else {
			$this->log("Нет данных для обновления категории", 2);
			return false;
		}

		// SEO
		$this->seoGenerateCategory($data);

		// Если было обновление описания
		$this->updateCategoryDescription($data);

		// Очистка кэша
		$this->cache->delete('category');

	} // updateCategory()


	/**
	 * Добавляет связь категории с ТС
	 */
	private function insertCategoryLinkToGuid($category_id, $guid) {

		$this->query("INSERT INTO `" . DB_PREFIX . "category_to_1c` SET `category_id` = " . (int)$category_id . ", `guid` = '" . $this->db->escape($guid) . "'");

	}


	/**
	 * Добавляет категорию
	 */
	private function addCategory($data) {

		if ($data == false) return 0;

		$data['status'] = $this->config->get('exchange1c_status_new_category') == 1 ? 1 : 0;

		$sql = $this->prepareStrQueryCategory($data);
		if ($this->config->get('exchange1c_synchronize_by_code') == 1) {
			$query_category_id = isset($data['code']) ? ", `category_id` = " . (int)$data['code'] : "";
		} else {
			$query_category_id = "";
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "category` SET " . $sql . $query_category_id . ", `date_modified` = '" . $this->NOW . "', `date_added` = '" . $this->NOW . "'");
		$data['category_id'] = $this->db->getLastId();

		// Формируем SEO
 		$this->seoGenerateCategory($data);

		// Подготовим строку запроса для описания категории
		$fields = $this->prepareStrQueryDescription($data, 'set');

		if ($fields) {
			$query = $this->query("SELECT category_id FROM `" . DB_PREFIX . "category_description` WHERE `category_id` = " . $data['category_id'] . " AND `language_id` = " . $this->LANG_ID);
			if (!$query->num_rows) {
				$this->query("INSERT INTO `" . DB_PREFIX . "category_description` SET `category_id` = " . $data['category_id'] . ", `language_id` = " . $this->LANG_ID . ", " . $fields);
			}
		}

		// Запись иерархии категорий для админки
		$this->addHierarchical($data['category_id'], $data);

		// Магазин
		$this->query("INSERT INTO `" . DB_PREFIX . "category_to_store` SET `category_id` = " . $data['category_id'] . ", `store_id` = " . $this->STORE_ID);

		// Добавим линк
		$this->insertCategoryLinkToGuid($data['category_id'], $data['guid']);

		// Чистим кэш
		$this->cache->delete('category');

		$this->log("Добавлена категория: '" . $data['name'] . "'");

		return $data['category_id'];

	} // addCategory()


	/**
	 * ver 1
	 * update 2017-04-24
	 * Парсит свойства товарных категорий из XML
	 */
	private function parseCategoryAttributes($xml, $data, $attributes) {

		if (!isset($attributes)) {
			$this->ERROR = "parseCategoryAttributes() - Классификатор не содержит атрибутов";
			return false;
		}

		foreach ($xml->Ид as $attribute) {
			$guid = (string)$xml->Ид;
			$this->log("> Свойство, Ид: " . $guid, 2);
			//if (isset($attributes[$guid])) {
			//	$this->log($attributes[$guid], 2);
			//}
		}
		return true;

	} //parseCategoryAttributes()


	/**
	 * ver 3
	 * update 2017-05-02
	 * Парсит товарные категории из классификатора
	 */
	private function parseClassifierProductCategories($xml, $parent_id = 0, $classifier) {

		$this->log($classifier, 2);

		if (!$xml->Категория) {
			$this->ERROR = "parseClassifierProductCategories() - Элемент с названием 'Категория' не найдена";
			return false;
		}
		foreach ($xml->Категория as $category){
			$data = array();
			$data['guid']			= (string)$category->Ид;
			$data['name']			= (string)$category->Наименование;
			$data['parent_id']		= $parent_id;
			$data['status']			= 1;
			if ($parent_id == 0)
				$data['top']		= 1;
			$data['category_id']	= $this->getCategoryIdByGuid($data['guid']);
			if (!$data['category_id']) {
				$this->addCategory($data);
			} else {
				$this->updateCategory($data);
			}

			if ($category->Категории) {
				$this->parseClassifierProductCategories($category->Категории, $data['category_id'], $classifier);
				if ($this->ERROR) return false;
			}

			// Свойства для категории
			if ($category->Свойства && isset($classifier['attributes'])) {
				if (!$this->parseCategoryAttributes($category->Свойства, $data, $classifier['attributes'])) {
					return false;
				}
			}
			unset($data);
		}
		return true;

	} // parseClassifierProductCategories()


	/**
	 * ver 3
	 * update 2017-04-26
	 * Парсит группы в классификаторе в XML
	 */
	private function parseClassifierCategories($xml, $parent_id = 0, $classifier) {

		$result = array();
		$array = $this->config->get('exchange1c_parse_categories_in_memory');

		foreach ($xml->Группа as $category) {
			if (isset($category->Ид) && isset($category->Наименование) ){

				$data = array();
				$data['guid']			= (string)$category->Ид;
				if ($category->Код && $this->config->get('exchange1c_synchronize_by_code') == 1) {
					$data['code'] 		= (int)$category->Код;
					$data['category_id'] = $this->getCategoryIdByCode($data['code']);
				} else {
					$data['category_id']= $this->getCategoryIdByGuid($data['guid']);
				}
				$data['parent_id']		= $parent_id;

				// По умолчанию включена категория
				$data['status']			= 1;

				// Сортировка категории (по просьбе Val)
				if ($category->Сортировка) {
					$data['sort_order']	= (int)$category->Сортировка;
				}

				// Картинка категории (по просьбе Val)
				if ($category->Картинка) {
					$data['image']		= (string)$category->Картинка;
				}

				// Если пометка удаления есть, значит будет отключен
				if ((string)$category->ПометкаУдаления == 'true') {
					$data['status'] = 0;
				}


				if ($parent_id == 0)
					$data['top']		= 1;

				// Определяем наименование и порядок, сортировка - число до точки, наименование все что после точки
				$data['name'] = (string)$category->Наименование;
				$split = $this->splitNameStr($data['name'], false);
				if ($split['order']) {
					$data['sort_order']	= $split['order'];
				}
				if ($split['name']) {
					$data['name']	= $split['name'];
				}

				// Свойства для группы
				if ($category->ЗначенияСвойств && isset($classifier['attributes'])) {
					if (!$this->parseAttributes($category->ЗначенияСвойств, $data, $classifier['attributes'])) {
						return false;
					}
				}

				// Обработка свойств
				if (isset($data['attributes'])) {
					foreach ($data['attributes'] as $attribute) {
						if ($attribute['name'] == 'Картинка') {
							$data['image'] = "catalog/" . $attribute['value'];
							$this->log("Установлена картинка для категории из свойства = " . $data['image']);
						} elseif ($attribute['name'] == 'Сортировка') {
							$data['sort_order'] = $attribute['value'];
							$this->log("Установлена сортировка для категории из свойства = " . $data['sort_order']);
						}
					}
				}

				$this->log("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 2);
				$this->log("КАТЕГОРИЯ: '" . $data['name'] . "', Ид: " . $data['guid'], 2);

				// Если не нашли категорию по Ид, пытаемся найти по имени учитывая id родительской категории
				if (!$data['category_id']) {
					$data['category_id'] = $this->getCategoryIdByName($data['name'], $parent_id);
					// Если нашли, добавляем связь
					if ($data['category_id'])
						$this->insertCategoryLinkToGuid($data['category_id'], $data['guid']);
				}

				if (!$data['category_id']) {
					if ($this->config->get('exchange1c_create_new_category') == 1) {
						$data['category_id'] = $this->addCategory($data);
					}
				} else {
					$this->updateCategory($data);
				}
				if ($array == 1) {
					$result[$data['guid']] = $data['category_id'];
				}
			}

			// Обнуляем остаток у товаров в этой категории
			if ($this->config->get('exchange1c_flush_quantity_category') == 1) {
				// Обнуляем остаток только в текущем магазине
				$query = $this->query("SELECT `p`.`product_id` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p`.`product_id` = `p2c`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) WHERE `p2c`.`category_id` = " . $data['category_id'] . " AND `p2s`.`store_id` = " . $this->STORE_ID);
				if ($query->num_rows) {
					if ($this->config->get('exchange1c_product_disable_if_quantity_zero') == 1) {
						$status = ", `status` = 0";
					} else {
						$status = "";
					}
					foreach ($query->rows as $row) {
						$this->query("UPDATE `" . DB_PREFIX . "product` SET `quantity` = 0 " . $status . " WHERE `product_id` = " . $row['product_id']);
						$this->query("UPDATE `" . DB_PREFIX . "product_quantity` SET `quantity` = 0 WHERE `product_id` = " . $row['product_id']);
					}
				}
			}

			if ($category->Группы) {
				$result1 = $this->parseClassifierCategories($category->Группы, $data['category_id'], $classifier);
				if ($this->ERROR) return false;

				if ($array == 1) {
					$result = array_merge($result, $result1);
				}
			}
		}
		return $result;

	} // parseClassifierCategories()


	/**
	 * ******************************************* ОПЦИИ *********************************************
	 */


	/**
	 * Добавляет или получает значение опции по названию
	 */
	private function setOptionValue($option_id, $value, $sort_order = "", $image = '') {

		$option_value_id = 0;

		// Проверим есть ли такое значение
		$query = $this->query("SELECT `ovd`.`option_value_id`,`ov`.`sort_order` FROM `" . DB_PREFIX . "option_value_description` `ovd` LEFT JOIN `" . DB_PREFIX . "option_value` `ov` ON (`ovd`.`option_value_id` = `ov`.`option_value_id`) WHERE `ovd`.`language_id` = " . $this->LANG_ID . " AND `ovd`.`option_id` = " . $option_id . " AND `ovd`.`name` = '" . $this->db->escape($value) . "'");
		if ($query->num_rows) {
			$option_value_id = $query->row['option_value_id'];

			// если изменилась сортировка
			if ($sort_order) {
				if ($query->row['sort_order'] <> $sort_order) {
					$this->query("UPDATE `" . DB_PREFIX . "option_value` SET `sort_order` = " . $sort_order . " WHERE `option_value_id` = " . $option_value_id);
					$this->log("Значение опции обновлено: '" . $value . "'");
				}
			}

		}
		if ($option_value_id)
			return $option_value_id;

		$sql = $sort_order == "" ? "" : ", `sort_order` = " . (int)$sort_order;
		$query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = " . $option_id . ", `image` = '" . $this->db->escape($image) . "'" . $sql);
		$option_value_id = $this->db->getLastId();

		if ($option_value_id) {
 			$query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_id` = " . $option_id . ", `option_value_id` = " . $option_value_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($value) . "'");
			$this->log("Значение опции добавлено: '" . $value . "'");
		}

		return $option_value_id;

	} // setOptionValue()



	/**
	 * Установка опции
	 */
	private function setOption($name, $type = 'select', $sort_order = 0) {

		$query = $this->query("SELECT `o`.`option_id`, `o`.`type`, `o`.`sort_order` FROM `" . DB_PREFIX . "option` `o` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`o`.`option_id` = `od`.`option_id`) WHERE `od`.`name` = '" . $this->db->escape($name) . "' AND `od`.`language_id` = " . $this->LANG_ID);
        if ($query->num_rows) {

			$option_id = $query->row['option_id'];

			$fields = array();
        	if ($query->row['type'] <> $type) {
        		$fields[] = "`type` = '" . $type . "'";
        	}

        	if ($sort_order) {
				if ($query->row['sort_order'] <> $sort_order) {
	        		$fields[] = "`sort_order` = " . (int)$sort_order;
	        	}
        	}
         	$fields = implode(', ', $fields);
        	if ($fields) {
				$this->query("UPDATE `" . DB_PREFIX . "option` SET " . $fields . " WHERE `option_id` = " . $option_id);
				$this->log("Опция обновлена: '" . $name . "'");
        	}

        } else {
			// Если опции нет, добавляем
			$option_id = $this->addOption($name, $type);
        }
		return $option_id;

	} // setOption()


	/**
	 * **************************************** ОПЦИИ ТОВАРА ******************************************
	 */


	/**
	 * ver 9
	 * update 2017-04-16
	 * Добавляет или обновляет опции в товаре
	 */
	private function setProductOptions($options, $product_id, $product_feature_id = 0, $new = false) {

		if (empty($options)) {
			$this->ERROR = "setProductOptions() - нет опций";
			return false;
		}

//		if (!$new) {
//			$old_option = array();
//			$old_option_value = array();
//			// Читаем старые опции товара текущей характеристики
//			$query = $this->query("SELECT `product_option_value_id`,`product_option_id` FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = " . $product_id);
//			foreach ($query->rows as $field) {
//				$old_option[$field['product_option_id']] 				= $field['product_option_id'];
//				$old_option_value[$field['product_option_value_id']]	= $field['product_option_value_id'];
//			}
//			$this->log($old_option, 2);
//			$this->log($old_option_value, 2);
//		}

		foreach ($options as $option_value_id => $option) {

			// Запишем опции в товар
			$option['product_option_id'] = $this->setProductOption($option['option_id'], $product_id, 1, $new);
//			if (!$new) {
//				$key = array_search($option['product_option_id'], $old_option);
//				if ($key !== false) {
//					unset($old_option[$key]);
//				}
//			}

			// Запишем значения опции в товар
			$product_option_value_id = $this->setProductOptionValue($option, $product_id, $option_value_id, $new);
//			if (!$new) {
//				$key = array_search($product_option_value_id, $old_option_value);
//				if ($key !== false) {
//					unset($old_option_value[$key]);
//				}
//			}

			// Запишем значения значения характеристики
			$this->setProductFeatureValue($product_feature_id, $product_id, $product_option_value_id, $new);
//			if (!$new) {
//				$key = array_search($product_option_value_id, $old_option_value);
//				if ($key !== false) {
//					unset($old_option_value[$key]);
//				}
//			}

		}

//		if (!$new) {
//			$this->log($old_option, 2);
//			$this->log($old_option_value, 2);
//			// Удалим старые значения опции из характеристики
//			if (count($old_option)) {
//				$this->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_option_id` IN (" . implode(",",$old_option) . ")");
//			}
//			if (count($old_option_value)) {
//				$this->query("DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_value_id` IN (" . implode(",",$old_option_value) . ")");
//			}
//		}

		return true;

	}  // setProductOptions()


	/**
	 * ver 3
	 * update 2017-04-17
	 * Устанавливает опцию в товар и возвращает ID
	 */
	private function setProductOption($option_id, $product_id, $required = 1, $new = false) {

		$product_option_id = 0;
		if (!$new) {
			$query = $this->query("SELECT `product_option_id` FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $product_id . " AND `option_id` = " . $option_id);
			if ($query->num_rows) {
				$product_option_id = $query->row['product_option_id'];
			}
		}
		if (!$product_option_id) {
			$this->query("INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = " . $product_id . ", `option_id` = " . $option_id . ", `required` = " . $required);
			$product_option_id = $this->db->getLastId();
		}
		return $product_option_id;

	} // setProductOption()


	/**
	 * ver 6
	 * update 2017-04-18
	 * Устанавливаем значение опции в товар
	 */
	private function setProductOptionValue($option, $product_id, $option_value_id, $new = false) {

		$product_option_value_id = 0;
		if (!$new) {
			$query = $this->query("SELECT `product_option_value_id`,`quantity`,`price_prefix`,`price` FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_id` = " . $option['product_option_id'] . " AND `product_id` = " . $product_id . " AND `option_id` = " . $option['option_id'] . " AND option_value_id = " . $option_value_id);
			if ($query->num_rows) {
				$product_option_value_id = $query->row['product_option_value_id'];
			}
			// изменения
			$fields = $this->compareArrays($query, $option);
			if ($fields) {
				$this->query("UPDATE `" . DB_PREFIX . "product_option_value` SET " . $fields . " WHERE `product_option_value_id` = " . $product_option_value_id);
			}
		}
		if (!$product_option_value_id) {
			$this->query("INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . $option['product_option_id'] . ", `product_id` = " . $product_id . ", `option_id` = " . $option['option_id'] . ", `option_value_id` = " . $option_value_id . ", quantity = " . (float)$option['quantity'] . ", `subtract` = " . $option['subtract']);
	 		$product_option_value_id = $this->db->getLastId();
		}
		return $product_option_value_id;

	} // setProductOptionValue()


	/**
	 * ************************************ ФУНКЦИИ ДЛЯ РАБОТЫ С ХАРАКТЕРИСТИКАМИ *************************************
	 */


	/**
	 * ver 9
	 * update 2017-04-17
	 * Создает или возвращает характеристику по Ид
	 * устанавливает цены в таблицу product_price
	 * устанавливает стандартные опции товара
	 * устанавливает остатки характеристики в таблицу product
	 */
	private function setProductFeature($feature_data, $product_id, $feature_guid, $new = false) {

		if (!$feature_guid) {
			$this->ERROR = "setProductFeature() - Не указан Ид характеристики";
			return false;
		}

		$product_feature_id = 0;
		if (!$new) {
			$query = $this->query("SELECT `product_feature_id`,`ean`,`name`,`sku` FROM `" . DB_PREFIX . "product_feature` WHERE `guid` = '" . $this->db->escape($feature_guid) . "' AND `product_id` = " . $product_id);
			if ($query->num_rows) {
				$product_feature_id = $query->row['product_feature_id'];
			}
			if ($product_feature_id) {
				// Сравнивает запрос с массивом данных и формирует список измененных полей
				$fields = $this->compareArrays($query, $feature_data);

				if ($fields) {
					$this->query("UPDATE `" . DB_PREFIX . "product_feature` SET " . $fields . " WHERE `product_feature_id` = " . $product_feature_id);
				}
			}
		}
		if (!$product_feature_id) {
			// добавляем
			$this->query("INSERT INTO `" . DB_PREFIX . "product_feature` SET `product_id` = " . $product_id . ", `guid` = '" . $this->db->escape($feature_guid) . "'");
			$product_feature_id = $this->db->getLastId();
		}

		// Опции в характеристике
		if (isset($feature_data['options'])) {
			$this->setProductOptions($feature_data['options'], $product_id, $product_feature_id, $new);
		}

		// Единицы измерения
		if (isset($feature_data['unit'])) {
			$this->setProductUnit($feature_data['unit'], $product_id, $product_feature_id, $new);
  		}

		// Цены
		if (isset($feature_data['prices'])) {
			$this->setProductFeaturePrices($feature_data['prices'], $product_id, $product_feature_id, $new);
		}

		// Остатки по складам
		if (isset($feature_data['quantities'])) {
			$this->setProductQuantities($feature_data['quantities'], $product_id, $product_feature_id, $new);
			if ($this->ERROR) return false;
		}

		return $product_feature_id;

	} // setProductFeature()


	/**
	 * ver 1
	 * update 2017-04-18
	 * Устанавливаем значение характеристики
	 */
	private function setProductFeatureValue($product_feature_id, $product_id, $product_option_value_id, $new = false) {

		if (!$new) {
			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_id` = " . $product_feature_id . " AND `product_id` = " . $product_id . " AND `product_option_value_id` = " . $product_option_value_id);
			if ($query->num_rows) {
				return false;
			}
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "product_feature_value` SET `product_feature_id` = " . $product_feature_id . ", `product_id` = " . $product_id . ", `product_option_value_id` = " . $product_option_value_id);
 		$product_option_value_id = $this->db->getLastId();
		return true;

	} // setProductFeatureValue()


	/**
	 * Находит характеристику товара по GUID
	 */
	private function getProductFeatureIdByGUID($feature_guid) {

		// Ищем характеристику по Ид
		$query = $this->query("SELECT `product_feature_id` FROM `" . DB_PREFIX . "product_feature` WHERE `guid` = '" . $this->db->escape($feature_guid) . "'");
		if ($query->num_rows) {
			return $query->row['product_feature_id'];
		}
		return 0;

	} // getProductFeatureIdByGUID()


	/**
	 * **************************************** ФУНКЦИИ ДЛЯ РАБОТЫ С ТОВАРОМ ******************************************
	 */


	/**
	 * ver 8
	 * update 2017-04-30
	 * Добавляет товар в базу
	 */
	private function addProduct(&$data) {

		$data['status'] = $this->config->get('exchange1c_status_new_product') == 1 ? 1 : 0;

		// Подготовим список полей по которым есть данные
		$fields = $this->prepareQueryProduct($data);
		if ($fields) {
			if ($this->config->get('exchange1c_synchronize_by_code') == 1) {
				$query_product_id = isset($data['code']) ? ", `product_id` = " . (int)$data['code'] : "";
			} else {
				$query_product_id = "";
			}

			$this->query("INSERT INTO `" . DB_PREFIX . "product` SET " . $fields . $query_product_id . ", `date_added` = '" . $this->NOW . "', `date_modified` = '" . $this->NOW . "'");
			$data['product_id'] = $this->db->getLastId();
		} else {
			// Если нет данных - выходим
			$this->ERROR = "addProduct() - нет данных";
			return false;
		}

//		// Полное наименование из 1С в товар
		if ($this->config->get('exchange1c_import_product_name') == 'fullname' && !empty($data['full_name'])) {
			if ($data['full_name'])
				$data['name'] = $data['full_name'];
		}

		// Связь с 1С только по Ид объекта из торговой системы
		$this->query("INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = " . $data['product_id'] . ", `guid` = '" . $this->db->escape($data['product_guid']) . "'");

		// Устанавливаем магазин
		$this->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . $data['product_id'] . ", `store_id` = " . $this->STORE_ID);

		// Записываем атрибуты в товар
		if (isset($data['attributes'])) {
			foreach ($data['attributes'] as $attribute) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . $data['product_id'] . ", `attribute_id` = " . $attribute['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" .  $this->db->escape($attribute['value']) . "'");
			}
		}

		// Отзывы парсятся с Яндекса в 1С, а затем на сайт
		// Доработка от SunLit (Skype: strong_forever2000)
		// Записываем отзывы в товар
		if (isset($data['review'])) {
			$this->setProductReview($data, $data['product_id']);
			if ($this->ERROR) return false;
		}

		// Категории
		if (isset($data['product_categories'])) {
			// Заполнение родительских категорий в товаре
			if ($this->config->get('exchange1c_fill_parent_cats') == 1) {
				$data['product_categories'] = $this->fillParentsCategories($data['product_categories']);
				if ($this->ERROR) return false;
			}
			$this->addProductCategories($data['product_categories'], $data['product_id']);
			if ($this->ERROR) return false;
		}

		// Картинки
		if (isset($data['images'])) {
			$this->setProductImages($data['images'], $data['product_id'], true);
			if ($this->ERROR) return false;
		}

		if (isset($data['features'])) {
			// Несколько характеристик
			foreach ($data['features'] as $feature_guid => $feature_data) {
				$this->setProductFeature($feature_data, $data['product_id'], $feature_guid, true);
				if ($this->ERROR) return false;
			}
		} elseif ($data['feature_guid']) {
			// Предложение является одной из характеристик товара
			$this->setProductFeature($data, $data['product_id'], $data['feature_guid']);
			if ($this->ERROR) return false;
		} else {
			// БЕЗ ХАРАКТЕРИСТИК
		}

		// Очистим кэш товаров
		$this->cache->delete('product');
		$this->log("Товар добавлен, product_id: " . $data['product_id'],2);
		return true;

	} // addProduct()


	/**
	 * ver 2
	 * update 2017-04-16
	 * Устанавливает товар в магазин который производится загрузка
	 * Если това в этом магазине не найден, то добавляем
	 */
	private function setProductShop($product_id) {

		$query = $this->query("SELECT `store_id`  FROM `" . DB_PREFIX . "product_to_store` WHERE `product_id` = " . $product_id . " AND `store_id` = " . $this->STORE_ID);
		if (!$query->num_rows) {
			$this->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . $product_id . ", `store_id` = " . $this->STORE_ID);
			$this->log("> Добавлена привязка товара к магазину store_id: " . $this->STORE_ID,2);
		}

	} // setProductShop()


	/**
	 * ver 3
	 * update 2017-04-17
	 * Устанавливает единицу измерения товара
	 */
	private function setProductUnit($unit_data, $product_id, $product_feature_id = 0, $new = false) {

		$product_unit_id = 0;
		if (!$new) {
			$old_units = array();
			$query = $this->query("SELECT `product_unit_id`,`unit_id`,`ratio` FROM `" . DB_PREFIX . "product_unit` WHERE `product_id` = " . $product_id . " AND `product_feature_id` = " . $product_feature_id . " AND `unit_id` = " . $unit_data['unit_id'] . " AND `ratio` = " . $unit_data['ratio']);
			if ($query->num_rows) {
				$product_unit_id = $query->row['product_unit_id'];
			}
		}
		if (!$product_unit_id) {
			$this->query("INSERT INTO `" . DB_PREFIX . "product_unit` SET `product_id` = " . $product_id . ", `product_feature_id` = " . $product_feature_id . ", `unit_id` = " . $unit_data['unit_id'] . ", `ratio` = " . $unit_data['ratio']);
			$product_unit_id = $this->db->getLastId();
		}
		return $product_unit_id;

	} // setProductUnit()


	/**
	 * ver 1
	 * update 2017-04-14
	 * Добавляет в товаре категории
	 */
	private function addProductCategories($product_categories, $product_id) {

		// если в CMS ведется учет главной категории
		$main_category = isset($this->TAB_FIELDS['product_to_category']['main_category']);

		foreach ($product_categories as $index => $category_id) {
			// старой такой нет категориии
			$sql  = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $product_id . ", `category_id` = " . $category_id;
			if ($main_category) {
				$sql .= $index == 0 ? ", `main_category` = 1" : ", `main_category` = 0";
			}
			$this->query($sql);
		}

		$this->log("Категории добавлены в товар");
		return true;

	} // addProductCategories()


	/**
	 * ver 4
	 * update 2017-04-16
	 * Обновляет в товаре категории
	 */
	private function updateProductCategories($product_categories, $product_id) {

		// если в CMS ведется учет главной категории
		$main_category = isset($this->TAB_FIELDS['product_to_category']['main_category']);

		$field = "";
		if (isset($this->TAB_FIELDS['product_to_category']['main_category'])) {
			$field = ", `main_category`";
			$order_by = " ORDER BY `main_category` DESC";
		}

		$old_categories = array();
		$sql  = "SELECT `category_id`";
		$sql .= $main_category ? ", `main_category`": "";
		$sql .= "  FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $product_id;
		$sql .= $main_category ? " ORDER BY `main_category` DESC" : "";
		$query = $this->query($sql);

		foreach ($query->rows as $category) {
			$old_categories[] = $category['category_id'];
		}

		foreach ($product_categories as $index => $category_id) {
			$key = array_search($category_id, $old_categories);
			if ($key !== false) {
				unset($old_categories[$key]);
				$this->log("Категория уже есть в товаре, id: " . $category_id, 2);
			} else {
				// старой такой нет категориии
				$sql  = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . $product_id . ", `category_id` = " . $category_id;
				if ($main_category) {
					$sql .= $index == 0 ? ", `main_category` = 1" : ", `main_category` = 0";
				}
				$this->query($sql);
				$this->log("Категория добавлена в товар, id: " . $category_id, 2);
			}
		}

		// Старые неиспользуемые категории удаляем
		if (count($old_categories) > 0) {
			$this->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . $product_id . " AND `category_id` IN (" . implode(",",$old_categories) . ")");
			$this->log("Удалены старые категории товара, id: " . implode(",",$old_categories), 2);
		}
		return true;

	} // updateProductCategories()


	/**
	 * ver 2
	 * update 2017-04-14
	 * Отзывы парсятся с Яндекса в 1С, а затем на сайт
	 * Доработка от SunLit (Skype: strong_forever2000)
	 * Устанавливает отзывы в товар из массива
	 */
	private function setProductReview($data, $product_id) {

		// Проверяем
		$product_review = array();
		$query = $this->query("SELECT `guid` FROM `" . DB_PREFIX . "review` WHERE `product_id` = " . $product_id);
		foreach ($query->rows as $review) {
			$product_review[$review['guid']] = "";
		}

		foreach ($data['review'] as $property) {

			if (isset($product_review[$property['id']])) {

				$this->log("[i] Отзыв с id: '" . $property['id'] . "' есть в базе сайта. Пропускаем.",2);
				unset($product_review[$property['id']]);
			} else {
				// Добавим в товар
				$text = '<i class="fa fa-plus-square"></i> ' .$this->db->escape($property['yes']).'<br><i class="fa fa-minus-square"></i> '.$this->db->escape($property['no']).'<br>'.$this->db->escape($property['text']);
				$this->query("INSERT INTO `" . DB_PREFIX . "review` SET `guid` = '".$property['id']."',`product_id` = " . $product_id . ", `status` = 1, `author` = '" . $this->db->escape($property['name']) . "', `rating` = " . $property['rate'] . ", `text` = '" .  $text . "', `date_added` = '".$property['date']."'");
				$this->log("Отзыв от '" . $this->db->escape($property['name']) . "' записан в товар id: " . $product_id,2);
			}
		}
		$this->log("Отзывы товаров обработаны", 2);

	} // setProductReview()


	/**
	 * ver 11
	 * update 2017-05-05
	 * Обновляет товар в базе поля в таблице product
	 * Если есть характеристики, тогда получает общий остаток по уже загруженным характеристикам прибавляет текущий и обновляет в таблице product
	 */
	private function updateProduct($data) {

		// Если товар существует и полная выгрузка, и не является характеристикой
		// очистка будет происходить даже если из import были прочитаны несколько характеристик, в этом случае старые же не нужны.
 		if ($data['product_id'] && $this->FULL_IMPORT && !$data['feature_guid'])  {
			$this->cleanProductData($data['product_id']);
 		}

		//if ($this->config->get('exchange1c_disable_product_full_import') == 1) {
		//	$this->log("[!] Перед полной загрузкой товар отключается");
		//	$data['status'] = 0;
		//}

		$update = false;

		// ФИЛЬТР ОБНОВЛЕНИЯ
		// Наименование товара
		if (isset($data['name'])) {
			if ($this->config->get('exchange1c_import_product_name') == 'disable' || $data['name'] == '') {
				unset($data['name']);
				$this->log("[i] Обновление названия отключено",2);
			}
		}
		// КОНЕЦ ФИЛЬТРА

		// Записываем атрибуты в товар
		if (isset($data['attributes'])) {
			$this->updateProductAttributes($data['attributes'], $data['product_id']);
			if ($this->ERROR) return false;
		}

		// Отзывы парсятся с Яндекса в 1С, а затем на сайт
		// Доработка от SunLit (Skype: strong_forever2000)
		// Записываем отзывы в товар
		if (isset($data['review'])) {
			$this->setProductReview($data);
			if ($this->ERROR) return false;
		}

		// Категории
		if (isset($data['product_categories'])) {
			// Заполнение родительских категорий в товаре
			if ($this->config->get('exchange1c_fill_parent_cats') == 1) {
				$data['product_categories'] = $this->fillParentsCategories($data['product_categories']);
				if ($this->ERROR) return false;
			}
			$this->updateProductCategories($data['product_categories'], $data['product_id']);
			if ($this->ERROR) return false;
		}

		// Картинки
		if (isset($data['images'])) {
			if ($this->config->get('exchange1c_import_images') == 1) {
				$this->setProductImages($data['images'], $data['product_id']);
				if ($this->ERROR) return false;
			} else {
				$this->log("[i] Обновление картинок отключено!");
				unset($data['images']);
			}
		}

		// Основная картинка
		if (isset($data['image']) && $this->config->get('exchange1c_import_images') != 1) {
			$this->log("[i] Обновление картинок отключено!");
			unset($data['image']);
		}

		// Предложение является одной характеристикой
		$product_feature_id = 0;
		if ($data['feature_guid']) {
			// Предложение является одной из характеристик товара
			$product_feature_id = $this->setProductFeature($data, $data['product_id'], $data['feature_guid']);
			if ($this->ERROR) return false;
		} else {
			// В предложении несколько характеристик, обычно там только опции
			if (isset($data['features'])) {
				foreach ($data['features'] as $feature_guid => $feature_data) {
					$this->setProductFeature($feature_data, $data['product_id'], $feature_guid);
					if ($this->ERROR) return false;
				}
			}
		}

		// Остатки товара по складам
		if (isset($data['quantities'])) {
			$this->setProductQuantities($data['quantities'], $data['product_id'], $product_feature_id);
			if ($this->ERROR) return false;

			// Получим общий остаток товара
			$quantity_total = $this->getProductQuantityTotal($data['product_id']);
			if ($quantity_total !== false) {
				$this->log("Остаток общий: " . $quantity_total);
				$data['quantity'] = $quantity_total;

				if ($this->config->get('exchange1c_product_disable_if_quantity_zero') == 1 && $data['quantity'] <= 0) {
					$data['status'] = 0;
					$this->log("Товар отключен, так как общий остаток товара <= 0");
				}
			}

			//unset($data['quantities']);
		}

		// цены по складам, характеристикам
		if (isset($data['prices'])) {
			// Записываем цены в акции или скидки и возвращает цену для записи в товар
			$data['price'] = $this->setProductPrices($data['prices'], $data['product_id'], $product_feature_id);
			if ($this->ERROR) return false;

			// Если это характеристика
			if ($data['feature_guid']) {
				$price = $this->getProductPriceMin($data['product_id']);
				if ($price !== false) {
					$data['price'] = $price;
					$this->log("Основная цена (мин): " . $data['price'], 2);
				}
			}
			//unset($data['prices']);
			// Отключим товар если не показывать с нулевой ценой
			if ($this->config->get('exchange1c_product_disable_if_price_zero') == 1 && $data['price'] <= 0 ) {
				$data['status'] = 0;
				$this->log("Товар отключен, так как цена <= 0");
			}
		}

		// Полное наименование из 1С в товар
		if ($this->config->get('exchange1c_import_product_name') == 'fullname' && isset($data['full_name'])) {
			if ($data['full_name']) {
				$data['name'] = $data['full_name'];
			}
		}

		// Читаем только те данные, которые получены из файла
		$fields = $this->prepareQueryProduct($data, 'get');
		if ($fields) {
			$query = $this->query("SELECT " . $fields . "  FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . $data['product_id']);
		}

		// Сравнивает запрос с массивом данных и формирует список измененных полей
		$fields = $this->compareArrays($query, $data);

		// Если есть что обновлять
		if ($fields) {
			$this->query("UPDATE `" . DB_PREFIX . "product` SET " . $fields . ", `date_modified` = '" . $this->NOW . "' WHERE `product_id` = " . $data['product_id']);
			$this->log("Товар обновлен, product_id = " . $data['product_id'], 2);
			$update = true;
		} else {
			// Обновляем date_modified для того чтобы отключить те товары которые не были в выгрузке при полном обмене
			if ($this->FULL_IMPORT) {
				$this->query("UPDATE `" . DB_PREFIX . "product` SET `date_modified` = '" . $this->NOW . "' WHERE `product_id` = " . $data['product_id']);
				$this->log("В товаре обновлено поле date_modified", 2);
			}
		}

		// Устанавливаем магазин
		$this->setProductShop($data['product_id']);

		// Очистим кэш товаров
		$this->cache->delete('product');
		return $update;

	} // updateProduct()


	/**
	 * Устанавливает описание товара в базе для одного языка
	 */
	private function setProductDescription($data, $new = false) {

		$this->log("Обновление описания товара");

		if (!$new) {
			$select_fields = $this->prepareStrQueryDescription($data, 'get');
			if ($select_fields) {
				$query = $this->query("SELECT " . $select_fields . " FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID);
				// Сравнивает запрос с массивом данных и формирует список измененных полей
				$update_fields = $this->compareArrays($query, $data);
			}
			// Если есть расхождения, производим обновление
			if ($update_fields) {
				$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $update_fields . " WHERE `product_id` = " . $data['product_id'] .  " AND `language_id` = " . $this->LANG_ID);
				$this->log("Описание товара обновлено, поля: '" . $update_fields . "'",2);
				return true;
			}
		} else {
			$insert_fields = $this->prepareStrQueryDescription($data, 'set');
			$this->query("INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = " . $data['product_id'] . ", `language_id` = " . $this->LANG_ID . ", " . $insert_fields);
		}

		return false;

	} // setProductDescription()


	/**
	 * Получает product_id по артикулу
	 */
	private function getProductBySKU($sku) {

		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($sku) . "'");
		if ($query->num_rows) {
	 		$this->log("Найден product_id: " . $query->row['product_id'] . " по артикулу '" . $sku . "'",2);
			return $query->row['product_id'];
		}
		$this->log("Не найден товар по артикулу '" . $sku . "'",2);
		return 0;

	} // getProductBySKU()


	/**
	 * Получает product_id по наименованию товара
	 */
	private function getProductByName($name) {

		$query = $this->query("SELECT `pd`.`product_id` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `name` = LOWER('" . $this->db->escape(strtolower($name)) . "')");
		if ($query->num_rows) {
	 		$this->log("Найден product_id: " . $query->row['product_id'] . " по названию '" . $name . "'",2);
			return $query->row['product_id'];
		}
		$this->log("Не найден товар по названию '" . $name . "'",2);
		return 0;

	} // getProductByName()


	/**
	 * Получает product_id по наименованию товара
	 */
	private function getProductByEAN($ean) {

		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `ean` = '" . $ean . "'");
		if ($query->num_rows) {
	 		$this->log("Найден товар по штрихкоду, product_id: " . $query->row['product_id'] . " по штрихкоду '" . $ean . "'",2);
			return $query->row['product_id'];
		}
		$this->log("Не найден товар по штрихкоду '" . $ean . "'",2);
		return 0;

	} // getProductByEAN()


	/**
	 * ver 6
	 * update 2017-05-04
	 * Обновление или добавление товара
	 * вызывается при обработке каталога
	 */
 	private function setProduct(&$data) {

		// Проверка на ошибки
		if (empty($data)) {
			$this->ERROR = "setProduct() - Нет входящих данных";
			return false;
		}

		if (!$data['product_id']) {
			// Поиск существующего товара
			if (isset($data['code']) && $this->config->get('exchange1c_synchronize_by_code') == 1) {
				// Синхронизация по Коду с 1С
				$data['product_id'] = $this->getProductIdByCode($data['code']);
				$this->log("Синхронизация товара по Коду: " . $data['code'], 2);
			}
		}

		// Синхронизация по Ид
		if (!$data['product_id']) {
			if (!$data['product_guid']) {
				$this->ERROR = "setProduct() - Не задан Ид товара из торговой системы";
				return false;
			} else {
				$data['product_id'] = $this->getProductIdByGuid($data['product_guid']);
			}
		}

		if (!$data['product_id']) {
			// Синхронизация по артикулу
	 		if ($this->config->get('exchange1c_synchronize_new_product_by') == 'sku') {
				if (empty($data['sku'])) {
 					$this->log("setProduct() - При синхронизации по артикулу, артикул не должен быть пустым! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					// Пропускаем товар
 					return false;
 				}
				$data['product_id'] = $this->getProductBySKU($data['sku']);
			// Синхронизация по наименованию
			} elseif ($this->config->get('exchange1c_synchronize_new_product_by') == 'name') {
				if (empty($data['name'])) {
 					$this->log("setProduct() - При синхронизации по наименованию, наименование не должно быть пустым! Товар пропущен. Проверьте товар Ид: " . $data['product_guid'], 2);
					// Пропускаем товар
					return false;
				}
				$data['product_id'] = $this->getProductByName($data['name']);
			// Синхронизация по штрихкоду
			} elseif ($this->config->get('exchange1c_synchronize_new_product_by') == 'ean') {
 				if (empty($data['ean'])) {
 					$this->log("setProduct() - При синхронизации по штрихкоду, штрихкод не должен быть пустым! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					return false;
 				}
				$data['product_id'] = $this->getProductByEan($data['name']);
 			}
 			// Если нашли, создадим связь
			if ($data['product_id']) {
				// Связь с 1С только по Ид объекта из торговой системы
				$this->query("INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = " . $data['product_id'] . ", `guid` = '" . $this->db->escape($data['product_guid']) . "'");
			}
		}

		$new = false;
		// Если не найден товар...
 		if (!$data['product_id']) {
 			if ($this->config->get('exchange1c_create_new_product') == 1) {
 				$new = $this->addProduct($data);
 				if ($this->ERROR) return false;

 			} else {
				$this->log("Отключено добавление новых товаров!");
 			}
 		} else {
 			$this->updateProduct($data);
			if ($this->ERROR) return false;
 		}

		// SEO формируем когда известен product_id и товар записан
		$update = $this->seoGenerateProduct($data);
		if ($this->ERROR) return false;

		if ($update || $new) {
			// Обновляем описание товара после генерации SEO
			$this->setProductDescription($data, $new);
		}

 		return true;

 	} // setProduct()


	/**
	 * Читает реквизиты товара из XML в массив
	 */
	private function parseRequisite($xml, &$data) {

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
					if ($value && $this->config->get('exchange1c_import_product_description') == 1) {
						$data['description'] =  (string)$value;
						$this->log("> Реквизит: " . $name. " => description (HTML format)",2);
					}
				break;
				case 'Полное наименование':
					$data['full_name'] = $value ? htmlspecialchars((string)$value) : '';
					$this->log("> Реквизит: " . $name. " => full_name",2);
				break;
				case 'ОписаниеФайла':
					$this->parseDescriptionFile((string)$value, $data);
					if ($this->ERROR) return false;
					$this->log("> Реквизит: " . $name, 2);
				break;
				case 'Производитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if ($this->config->get('exchange1c_import_product_manufacturer') == 1) {
						if (!isset($data['manufacturer_id'])) {
							$data['manufacturer_id'] = $this->setManufacturer($value);
							$this->log("> Производитель (из реквизита): '" . $value . "', id: " . $data['manufacturer_id'],2);
						}
					}
				break;
				case 'Код':
					$this->log("> Реквизит: " . $name. " => " . (string)$value, 2);
				break;
				default:
					$this->log("[!] Неиспользуемый реквизит: " . $name. " = " . (string)$value,2);
			}
		}

	} // parseRequisite()


	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 */
	private function applyWatermark($filename, $watermark, $name_wm) {

		$watermark_path = DIR_IMAGE . $watermark;
		$filename_path = DIR_IMAGE . $filename;

		if (is_file($watermark_path) && is_file($filename_path)) {

			// Получим расширение файла
			$info = pathinfo($filename);
			$extension = $info['extension'];

			// Создаем объект картинка из водяного знака и получаем информацию о картинке
			$image = new Image($filename_path);
			$image->watermark(new Image($watermark_path));

			// Формируем название для файла с наложенным водяным знаком
			$new_image = $info['dirname'] . "/" . $name_wm;

			// Сохраняем картинку с водяным знаком
			$image->save(DIR_IMAGE . $new_image);

			return true;
		}
		else {
			return false;
		}

	} // applyWatermark()


	/**
	 * ver 2
	 * update 2017-04-18
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
				$this->setProductDescription(array('description'	=> $description, 'product_id' => $product_id));
				$this->log("> Добавлено описание товара из файла: " . $info['basename'],1);
				return true;
			}
		}
		return false;

	} // setFile())


	/**
	 * ver 2
	 * update 2017-04-10
	 * Накладывает водяной знак на картинку и возвращает картинку
	 */
	private function setWatermarkImage($image) {

		$watermark = $this->config->get('exchange1c_watermark');
		if (empty($watermark)) {
			$this->ERROR = "setWatermarkImage() - файл водяных знаков пустой";
			return false;
		}

		// если надо наложить водяные знаки, проверим накладывали уже их ранее, т.е. имеется ли такой файл
		// Файл с водяными знаками имеет название /path/image_wm.ext
		$path_parts = pathinfo($image);
		$wm_filename = $path_parts['filename'] . "_wm." . $path_parts['extension'];
		$new_image = $path_parts['dirname'] . "/" . $wm_filename;
		if (!file_exists(DIR_IMAGE . $new_image)) {

			// Если нет файла, тогда создаем его и удаляем старый
			if ($this->applyWatermark($image, $watermark, $wm_filename)) {
				$this->log("> Сформирован файл с водяным знаком: " . $wm_filename);
			} else {
				$this->ERROR = "setWatermarkImage() - Ошибка наложения водяного знака на картинку: '" . $image . "'";
				return false;
			}
		}

		// Удаляем старый файл
		if (file_exists(DIR_IMAGE . $image)) {
			unlink(DIR_IMAGE . $image);
			$this->log("> Удален старый файл: " . $image, 2);
		} else {
			$this->ERROR = "setWatermarkImage() - Невозможно удалить старый файл: '" . $image . "'";
			return false;
		}

		return $new_image;

	} // setWatermarkImage()


	/**
	 * ver 6
	 * update 2017-05-04
	 * Устанавливает дополнительные картинки в товаре
	 */
	private function setProductImages($images_data, $product_id, $new = false) {

		$old_images = array();
		if (!$new) {
			// Прочитаем  все старые картинки
			$query = $this->query("SELECT `product_image_id`,`image` FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = " . $product_id);
			foreach ($query->rows as $image) {
				$old_images[$image['product_image_id']] = $image['image'];
			}
		}

		foreach ($images_data as $index => $image_data) {

			// Основная картинка
			if ($index == 0) continue;

			$image 			= $image_data['file'];
			$description 	= $image_data['description'];
			$this->log("Картинка: " . $image, 2);
			$this->log("Описание: " . $description, 2);

			// Накладываем водяные знаки
			if ($this->config->get('exchange1c_watermark')) {
				$image = $this->setWatermarkImage($image);
				if ($this->ERROR) return false;
			}

			// Установим картинку в товар, т.е. если нет - добавим, если есть возвратим product_image_id
			$product_image_id = array_search($image, $old_images);
			if (!$product_image_id) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_image` SET `product_id` = " . $product_id . ", `image` = '" . $this->db->escape($image) . "', `sort_order` = " . $index);
				//$product_image_id = $this->db->getLastId();
				//$this->query("INSERT INTO `" . DB_PREFIX . "product_image_description` SET `product_id` = " . $product_id . ", `product_image_id` = " . $product_image_id . ", `name` = '" . $this->db->escape($description) . "', `language_id` = " . $this->LANG_ID);
				//$this->log("> Картинка дополнительная: '" . $image . "'", 2);
			} else {
				unset($old_images[$product_image_id]);
			}

		} // foreach ($images_data as $index => $image_data)

		if (!$new) {
			// Удалим старые неиспользованные картинки
			$delete_images = array();
			foreach ($old_images as $product_image_id => $image) {
				//$this->log($image, 2);
				$delete_images[] = $product_image_id;
				if (is_file(DIR_IMAGE . $image)) {
					// Также удалим файл с диска
					unlink(DIR_IMAGE . $image);
					$this->log("> Удалена старая картинка: " . DIR_IMAGE . $image);
				}
			}
			if (count($delete_images)) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_image_id` IN (" . implode(",",$delete_images) . ")");
			}
		}

	} // setProductImages()


	/**
	 * Читает описание файла из XML в массив
	 */
	private function parseDescriptionFile($value, &$data) {

		if (!$value) {
			$this->ERROR = "Описание пустое";
			return false;
		}

		if (!isset($data['description_files'])) {
			$data['description_files'] = array();
		}

		$value_array 	= explode("#", (string)$value);
		$file			= $value_array[0];
		$description 	= isset($value_array[1]) ? $value_array[1] : '';

		$data['description_files'][$file] = $description;

	} // parseDescriptionFile()


	/**
	 * ver 2
	 * update 2017-04-10
	 * Читает картинки из XML в массив
	 */
	private function parseImages($xml, $data) {

		if (!$xml) {
			$this->ERROR = "parseImages() - Нет картинок в XML";
			return false;
		}

		$data_images = array();

		foreach ($xml as $image) {

			$image = (string)$image;
			if (empty($image)) continue;

			// Обрабатываем только картинки
			$image_info = @getimagesize(DIR_IMAGE . $image);
			if ($image_info == NULL) {
				$this->log("Это не картинка: " . DIR_IMAGE . $image);
			};

			$description = "";
			if (isset($data['description_files'][$image])) {
				$description = $data['description_files'][$image];
			}

			$this->log("Картинка: " . $image, 2);
			$this->log("Описание файла: " . $description, 2);
			$data_images[] = array(
				'file'			=> $image,
				'description'	=> $description
			);

		}
		return $data_images;

	} // parseImages()


	/**
	 * Возвращает id группы для свойств
	 */
	private function setAttributeGroup($name) {

		$query = $this->query("SELECT `attribute_group_id` FROM `" . DB_PREFIX . "attribute_group_description` WHERE `name` = '" . $this->db->escape($name) . "'");
		if ($query->rows) {
	   		$this->log("Группа атрибута: '" . $name . "'", 2);
			return $query->row['attribute_group_id'];
		}

		// Добавляем группу
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_group` SET `sort_order` = 1");

		$attribute_group_id = $this->db->getLastId();
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_group_description` SET `attribute_group_id` = " . $attribute_group_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");

   		$this->log("Группа атрибута добавлена: '" . $name . "'", 2);
		return $attribute_group_id;

	} // setAttributeGroup()


	/**
	 * Возвращает id атрибута из базы
	 */
	private function setAttribute($guid, $attribute_group_id, $name, $sort_order) {

		// Ищем свойства по 1С Ид
		$attribute_id = 0;
		if ($guid && $this->config->get('exchange1c_synchronize_attribute_by') == 'guid') {
			$query = $this->query("SELECT `attribute_id` FROM `" . DB_PREFIX . "attribute_to_1c` WHERE `guid` = '" . $this->db->escape($guid) . "'");
			if ($query->num_rows) {
				$attribute_id = $query->row['attribute_id'];
			}
		} else {
			// Попытаемся найти по наименованию
			$query = $this->query("SELECT `a`.`attribute_id` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID . " AND `ad`.`name` LIKE '" . $this->db->escape($name) . "' AND `a`.`attribute_group_id` = " . $attribute_group_id);
			if ($query->num_rows) {
				$attribute_id = $query->row['attribute_id'];
			}
		}

		// Обновление
		if ($attribute_id) {
			$query = $this->query("SELECT `a`.`attribute_group_id`,`ad`.`name` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID . " AND `a`.`attribute_id` = " . $attribute_id);
			if ($query->num_rows) {
				// Изменилась группа свойства
				if ($query->row['attribute_group_id'] <> $attribute_group_id) {
					$this->query("UPDATE `" . DB_PREFIX . "attribute` SET `attribute_group_id` = " . (int)$attribute_group_id . " WHERE `attribute_id` = " . $attribute_id);
					$this->log("Группа атрибута обновлена: " . $attribute_id, 2);
				}
				// Изменилось имя
				if ($query->row['name'] <> $name) {
					$this->query("UPDATE `" . DB_PREFIX . "attribute_description` SET `name` = '" . $this->db->escape($name) . "' WHERE `attribute_id` = " . $attribute_id . " AND `language_id` = " . $this->LANG_ID);
					$this->log("Атрибут обновлен: '" . $name . "'", 2);
				}
			}

			return $attribute_id;
		}

		// Добавим в базу характеристику
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute` SET `attribute_group_id` = " . $attribute_group_id . ", `sort_order` = " . $sort_order);
		$attribute_id = $this->db->getLastId();
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_description` SET `attribute_id` = " . $attribute_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");
		$this->log("Атрибут добавлен: '" . $name . "'", 2);


		if ($this->config->get('exchange1c_synchronize_attribute_by') == 'guid') {
			// Добавляем ссылку для 1С Ид
			$this->query("INSERT INTO `" .  DB_PREFIX . "attribute_to_1c` SET `attribute_id` = " . $attribute_id . ", `guid` = '" . $this->db->escape($guid) . "'");
		}

		return $attribute_id;

	} // setAttribute()


	/**
	 * ver 2
	 * update 2017-04-27
	 * Загружает значения атрибута (Свойства из 1С)
	 */
	private function parseAttributesValues($xml, $attribute_id = 0) {

		$data = array();
		if (!$xml) {
			return $data;
		}

		if ($xml->ПометкаУдаления) {
			$delete = (string)$xml->ПометкаУдаления == 'true' ? true : false;
		} else {
			$delete = false;
		}

		if ($xml->ВариантыЗначений) {
			if ($xml->ВариантыЗначений->Справочник) {
				foreach ($xml->ВариантыЗначений->Справочник as $item) {
					$value = trim(htmlspecialchars((string)$item->Значение, 2));
					$guid = (string)$item->ИдЗначения;

					if (!$value) {
						continue;
					}

					$query = $this->query("SELECT `attribute_value_id`,`name` FROM `" . DB_PREFIX . "attribute_value` WHERE `guid` = '" . $this->db->escape($guid) . "'");
					if ($query->num_rows) {
						if ($delete) {
							$this->query("DELETE FROM `" . DB_PREFIX . "attribute_value` WHERE `guid` = '" . $this->db->escape($guid) . "'");
							$value_id = 0;
							$this->log("Значение атрибута удалено (пометка удаления в ТС): " . $value,2);
						} else {
							if ($query->row['name'] <> $value) {
								$this->query("UPDATE `" . DB_PREFIX . "attribute_value` SET `name` = '" . $this->db->escape($value) . "' WHERE `attribute_value_id` = " . $query->row['attribute_value_id']);
								$this->log("Значение атрибута обновлено: " . $value, 2);
							}
							$value_id = $query->row['attribute_value_id'];
						}

					} else {
						if (!$delete) {
							if ($attribute_id) {
								$query = $this->query("INSERT INTO `" . DB_PREFIX . "attribute_value` SET `attribute_id` = " . $attribute_id . ", `guid` = '" . $this->db->escape($guid) . "', `name` = '" . $this->db->escape($value) . "'");
								$value_id = $this->db->getlastId();
								$this->log("Значение атрибута добавлено: " . $value, 2);
							} else {
								$value_id = 0;
							}
						} else {
							$this->log("Значение атрибута было удалено (помечен на удаление в ТС): " . $value, 2);
							$value_id = 0;
						}
					}

					$data[$guid] = array(
						'name'		=> $value,
						'value_id'	=> $value_id
					);

				}
			}
		}
		return $data;

	} // parseAttributesValues()


	/**
	 * Загружает атрибуты (Свойства из 1С) в классификаторе
	 */
	private function parseClassifierAttributes($xml) {

		$data = array();
		$sort_order = 0;
		if ($xml->Свойство) {
			$properties = $xml->Свойство;
		} else {
			$properties = $xml->СвойствоНоменклатуры;
		}

		foreach ($properties as $property) {

			$name 		= trim((string)$property->Наименование);
			$guid		= (string)$property->Ид;

			// Название группы свойств по умолчанию (в дальнейшем сделать определение в настройках)
			$group_name = "Свойства";

			// Определим название группы в название свойства в круглых скобках в конце названия
			$name_split = $this->splitNameStr($name);
			//$this->log($name_split, 2);
			if ($name_split['option']) {
				$group_name = $name_split['option'];
				$this->log("> Группа свойства: " . $group_name, 2);
			}
			$name = $name_split['name'];
			// Установим группу для свойств
			$attribute_group_id = $this->setAttributeGroup($group_name);

			// Использование
			if ($property->ИспользованиеСвойства) {
				$status = (string)$property->ИспользованиеСвойства == 'true' ? 1 : 0;
			} else {
				$status = 1;
			}

			// Для товаров
			if ($property->ДляТоваров) {
				$for_product = (string)$property->ДляТоваров == 'true' ? 1 : 0;
			} else {
				$for_product = 1;
			}

			// Обязательное
			if ($property->Обязательное) {
				$required = (string)$property->Обязательное == 'true' ? 1 : 0;
			} else {
				$required = 0;
			}

			// Множественное
			if ($property->Множественное) {
				$multiple = (string)$property->Множественное == 'true' ? 1 : 0;
			} else {
				$multiple = 0;
			}

			if ($property->ДляПредложений) {
				// Свойства для характеристик скорее всего
				if ((string)$property->ДляПредложений == 'true') {
					$this->log("> Свойство '" . $name . "' только для предложений, в атрибуты не будет добавлено", 2);
					continue;
				}
			}

			switch ($name) {
				case 'Производитель':
					$values = $this->parseAttributesValues($property);
					foreach ($values as $manufacturer_guid => $value) {
						$this->setManufacturer($value['name'], $manufacturer_guid);
					}
				//break;
				case 'Изготовитель':
					$values = $this->parseAttributesValues($property);
					foreach ($values as $manufacturer_guid => $value) {
						$this->setManufacturer($value['name'], $manufacturer_guid);
					}
				//break;
				default:
					$attribute_id = $this->setAttribute($guid, $attribute_group_id, $name, $sort_order);
					$values = $this->parseAttributesValues($property, $attribute_id);
					$data[$guid] = array(
						'name'			=> $name,
						'attribute_id'	=> $attribute_id,
						'values'		=> $values,
						'for_product'	=> $for_product,
						'status'		=> $status,
						'required'		=> $required,
						'multiple'		=> $multiple
					);

					$sort_order ++;
			}

		}

		$this->log("Атрибутов прочитано: " . sizeof($properties), 2);
		return $data;

	} // parseClassifierAttributes()


	/**
	 * Читает свойства из базы данных в массив
	 */
	private function getAttributes() {

		$data = array();

		$query_attribute = $this->query("SELECT `a`.`attribute_id`, `ad`.`name`, `a2c`.`guid` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) LEFT JOIN `" . DB_PREFIX . "attribute_to_1c` `a2c` ON (`a`.`attribute_id` = `a2c`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID);
		if ($query_attribute->num_rows) {
			foreach ($query_attribute->rows as $row_attribute) {

				$attribute_guid = $row_attribute['guid'];
				$attribute_id = $row_attribute['attribute_id'];
				if (!isset($data[$attribute_guid])) {
					$data[$attribute_guid] = array(
						'name'			=> $row_attribute['name'],
						'attribute_id'	=> $attribute_id,
						'values'		=> array()
					);
				}

				$query_value = $this->query("SELECT `attribute_value_id`, `name`, `guid` FROM `" . DB_PREFIX . "attribute_value` WHERE `attribute_id` = " . $attribute_id);

				if ($query_value->num_rows) {
					foreach ($query_value->rows as $row_value) {

						$values = &$data[$attribute_guid]['values'];

						$attribute_value_guid = $row_value['guid'];
						if (!isset($values[$attribute_value_guid])) {
							$values[$attribute_value_guid] = array(
								'name'		=> $row_value['name'],
								'value_id'	=> $row_value['attribute_value_id']
							);
						}
					}
				}
			}
		}

		$this->log("Свойства (атрибуты) получены из БД",2);
		return $data;

	}  // getAttributes()


	/**
	 * Читает свойства из объектов (товар, категория) и записывает их в массив
	 */
	private function parseAttributes($xml, &$data, &$classifier) {

		$product_attributes = array();
        $error = "";

		if (!isset($classifier['attributes'])) {
			$classifier['attributes'] = $this->getAttributes();
			if ($this->ERROR) {
				return false;
			}
		}
		$attributes = $classifier['attributes'];

		foreach ($xml->ЗначенияСвойства as $property) {

			// Ид объекта в 1С
			$guid = (string)$property->Ид;

			// Загружаем только те что в классификаторе
			if (!isset($attributes[$guid])) {
				$this->log("[i] Свойство не было загружено в классификаторе, Ид: " . $guid, 2);
				continue;
			}

			$name 	= trim($attributes[$guid]['name']);
			$value 	= trim((string)$property->Значение);
			$value_id = 0;

			if ($value) {
				if ($attributes[$guid]) {
					// агрегатный тип, под value подразумеваем Ид объекта
					if (!empty($attributes[$guid]['values'][$value])) {
						$values = $attributes[$guid]['values'][$value];
						$value = trim($values['name']);
						$value_id = $values['value_id'];
					}
				}
			}

			// Фильтруем по таблице свойств
			$attributes_filter = $this->config->get('exchange1c_properties');
			if (is_array($attributes_filter)) {
				foreach ($attributes_filter as $attr_filter) {
					if ($attr_filter['name'] == $name && $attr_filter['product_field_name'] == '') {
						$value = "";
						$this->log("Свойство отключено для загрузки в товар: '" . $attr_filter['name'] . "'", 2);
						break;
					}
				}
			}

			// Пропускаем с пустыми значениями
			if (empty($value)) {
				$this->log("[i] У свойства '" . $name . "' нет значения, не будет обработано", 2);
				continue;
			}

			switch ($name) {
				case 'Производитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if ($this->config->get('exchange1c_import_product_manufacturer') == 1) {
						if (!isset($data['manufacturer_id'])) {
							$data['manufacturer_id'] = $this->setManufacturer($value);
							$this->log("> Производитель (из свойства): '" . $value . "', id: " . $data['manufacturer_id'],2);
						}
					}
				break;
				case 'Изготовитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if ($this->config->get('exchange1c_import_product_manufacturer') == 1) {
						if (!isset($data['manufacturer_id'])) {
							$data['manufacturer_id'] = $this->setManufacturer($value);
							$this->log("> Изготовитель (из свойства): '" . $value . "', id: " . $data['manufacturer_id'],2);
						}
					}
				break;
				case 'Вес':
					$data['weight'] = round((float)str_replace(',','.',$value), 3);
					$this->log("> Вес => weight = ".$data['weight'],2);
				break;
				case 'Ширина':
					$data['width'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Ширина => width",2);
				break;
				case 'Высота':
					$data['height'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Высота => height",2);
				break;
				case 'Длина':
					$data['length'] = round((float)str_replace(',','.',$value), 2);
					$this->log("> Длина => length",2);
				break;
				case 'Модель':
					$data['model'] = (string)$value;
					$this->log("> Модель => model",2);
				break;
				case 'Артикул':
					$data['sku'] = (string)$value;
					$this->log("> Артикул => sku",2);
				break;
				default:
					$product_attributes[$attributes[$guid]['attribute_id']] = array(
						'name'			=> $name,
						'value'			=> $value,
						'guid'			=> $guid,
						'value_id'		=> $value_id,
						'attribute_id'	=> $attributes[$guid]['attribute_id']
					);
					$this->log("> " . $name . " = '" . $value . "'",2);
			}
		}
		$data['attributes'] = $product_attributes;
		$this->log("> свойства в товаре прочитаны",2);
		return true;

	} // parseProductAttributes()


	/**
	 * ver 4
	 * update 2017-04-16
	 * Обновляет свойства в товар из массива
	 */
	private function updateProductAttributes($attributes, $product_id) {

		// Проверяем
		$product_attributes = array();
		$query = $this->query("SELECT `attribute_id`,`text` FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . $product_id . " AND `language_id` = " . $this->LANG_ID);
		foreach ($query->rows as $attribute) {
			$product_attributes[$attribute['attribute_id']] = $attribute['text'];
		}

		foreach ($attributes as $attribute) {
			// Проверим есть ли такой атрибут

			if (isset($product_attributes[$attribute['attribute_id']])) {

				// Проверим значение и обновим при необходимости
				if ($product_attributes[$attribute['attribute_id']] != $attribute['value']) {
					$this->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '" . $this->db->escape($attribute['value']) . "' WHERE `product_id` = " . $product_id . " AND `attribute_id` = " . $attribute['attribute_id'] . " AND `language_id` = " . $this->LANG_ID);
					$this->log("Атрибут товара обновлен'" . $this->db->escape($attribute['name']) . "' = '" . $this->db->escape($attribute['value']) . "' записано в товар id: " . $product_id, 2);
				}

				unset($product_attributes[$attribute['attribute_id']]);
			} else {
				// Добавим в товар
				$this->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . $product_id . ", `attribute_id` = " . $attribute['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" .  $this->db->escape($attribute['value']) . "'");
				$this->log("Атрибут товара добавлен '" . $this->db->escape($attribute['name']) . "' = '" . $this->db->escape($attribute['value']) . "' записано в товар id: " . $product_id, 2);
			}
		}

		// Удалим неиспользованные
		if (count($product_attributes)) {
			$delete_attribute = array();
			foreach ($product_attributes as $attribute_id => $attribute) {
				$delete_attribute[] = $attribute_id;
			}
			$this->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . $product_id . " AND `language_id` = " . $this->LANG_ID . " AND `attribute_id` IN (" . implode(",",$delete_attribute) . ")");
			$this->log("Старые атрибуты товара удалены", 2);
		}

	} // updateProductAttributes()


	/**
	 * ver 2
	 * update 2017-04-29
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($data) {

		$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $data['manufacturer_id']);
		if ($query->row['name'] <> $data['name']) {
			// Обновляем
			$sql  = " `name` = '" . $this->db->escape($data['name']) . "'";
			$sql .= isset($data['noindex']) ? ", `noindex` = " . $data['noindex'] : "";
			$this->query("UPDATE `" . DB_PREFIX . "manufacturer` SET " . $sql . " WHERE `manufacturer_id` = " . $data['manufacturer_id']);
			$this->log("Производитель обновлен: '" . $data['name'] . "'", 2);
		}

		if (isset($this->TAB_FIELDS['manufacturer_description'])) {

			$this->seoGenerateManufacturer($data);
			$select_name = isset($this->TAB_FIELDS['manufacturer_description']['name']) ? ", `name`" : "";
			$query = $this->query("SELECT `description`,`meta_title`,`meta_description`,`meta_keyword`" . $select_name . " FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$update_fields = $this->compareArrays($query, $data);

			if ($update_fields) {
				$this->query("UPDATE `" . DB_PREFIX . "manufacturer_description` SET " . $update_fields . " WHERE `manufacturer_id` = " . $data['manufacturer_id'] . " AND `language_id` = " . $this->LANG_ID);
			}
		}
		return true;

	} // updateManufacturer()


	/**
	 * ver 2
	 * update 2017-04-29
	 * Добавляем производителя
	 */
	private function addManufacturer(&$manufacturer_data) {

		$sql = array();
		if (!isset($this->TAB_FIELDS['manufacturer_description']['name']) && isset($manufacturer_data['name'])) {
			$sql[] = "`name` = '" . $this->db->escape($manufacturer_data['name']) . "'";
		}
		if (isset($manufacturer_data['sort_order'])) {
			$sql[] = "`sort_order` = " . $manufacturer_data['sort_order'];
		}
		if (isset($manufacturer_data['image'])) {
			$sql[] = "`image` = '" . $this->db->escape($manufacturer_data['image']) . "'";
		}
		if (isset($manufacturer_data['noindex'])) {
			$sql[] = "`noindex` = " . $manufacturer_data['noindex'];
		}
		if (!$sql) {
			$this->log("Производитель не добавлен, так как нет данных!");
			$this->log($manufacturer_data);
			return true;
		}

		$query = $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET" . implode(", ", $sql));

		$manufacturer_data['manufacturer_id'] = $this->db->getLastId();
        $this->seoGenerateManufacturer($manufacturer_data);

		if (isset($this->TAB_FIELDS['manufacturer_description'])) {
			$sql = $this->prepareStrQueryManufacturerDescription($manufacturer_data);
			if ($sql) {
				$this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET `manufacturer_id` = " . $manufacturer_data['manufacturer_id'] . ", `language_id` = " . $this->LANG_ID . $sql);
			}
		}

		// добавляем связь
		if (isset($manufacturer_data['guid'])) {
			$this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_1c` SET `guid` = '" . $this->db->escape($manufacturer_data['guid']) . "', `manufacturer_id` = " . $manufacturer_data['manufacturer_id']);
		}

		$this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id` = " . $manufacturer_data['manufacturer_id'] . ", `store_id` = " . $this->STORE_ID);
 		$this->log("Производитель добавлен: '" . $manufacturer_data['name'] . "'");

	} // addManufacturer()


	/**
	 * Устанавливаем производителя
	 */
	private function setManufacturer($name, $manufacturer_guid = '') {

		$manufacturer_data = array();
		$manufacturer_data['name']			= (string)$name;
		$manufacturer_data['description'] 	= 'Производитель ' . $manufacturer_data['name'];
		$manufacturer_data['sort_order']	= 1;
		$manufacturer_data['guid']			= (string)$manufacturer_guid;

		if (isset($this->FIELDS['manufacturer']['noindex'])) {
			$manufacturer_data['noindex'] = 1;	// значение по умолчанию
		}

		if ($manufacturer_guid) {
			// Поиск (производителя) изготовителя по 1C Ид
			$query = $this->query("SELECT mc.manufacturer_id FROM `" . DB_PREFIX . "manufacturer_to_1c` mc LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (mc.manufacturer_id = ms.manufacturer_id) WHERE mc.guid = '" . $this->db->escape($manufacturer_data['guid']) . "' AND ms.store_id = " . $this->STORE_ID);
		} else {
			// Поиск по имени
			$query = $this->query("SELECT m.manufacturer_id FROM `" . DB_PREFIX . "manufacturer` m LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` ms ON (m.manufacturer_id = ms.manufacturer_id) WHERE m.name LIKE '" . $this->db->escape($manufacturer_data['name']) . "' AND ms.store_id = " . $this->STORE_ID);
		}

		if ($query->num_rows) {
			$manufacturer_data['manufacturer_id'] = $query->row['manufacturer_id'];
		}

		if (!isset($manufacturer_data['manufacturer_id'])) {
			// Создаем
			$this->addManufacturer($manufacturer_data);
		} else {
			// Обновляем
			$this->updateManufacturer($manufacturer_data);
		}

		return $manufacturer_data['manufacturer_id'];

	} // setManufacturer()


	/**
	 * Обрабатывает единицу измерения товара
	 */
	private function parseProductUnit($xml) {

		$unit_data = array();

		if (!$xml) {
			$unit_data['ratio'] 		= 1;
			$unit_data['code']		= "796";
			$unit_data['unit_id']	= $this->getUnitId($unit_data['code']);
			$unit_data['full_name']	= "Штука";
			$unit_data['code_eng']	= "PCE";
			return $unit_data;
		}

		// Коэффициент пересчета от базовой единицы
		if (isset($xml->Пересчет)) {
			$unit_data['ratio']	= (float)$xml->Пересчет->Коэффициент;
		} else {
			$unit_data['ratio']	= 1;
		}

		// Если единица не назначена, устанавливается по умолчанию штука
		$unit_data['code'] = isset($xml['Код']) ? (string)$xml['Код'] : "796";
		$unit_data['unit_id'] = $this->getUnitId($unit_data['code']);

		if (isset($xml['НаименованиеПолное'])) {
			$unit_data['full_name'] = htmlspecialchars((string)$xml['НаименованиеПолное']);
		}
		if (isset($xml['МеждународноеСокращение'])) {
			$unit_data['code_eng'] = (string)$xml['МеждународноеСокращение'];
		}

		if (!isset($data['name'])) {
			// Если имя не задаоно в xml получим из таблицы
			$query = $this->query("SELECT `rus_name1` FROM `" . DB_PREFIX . "unit` WHERE `number_code` = '" . $unit_data['code'] . "'");
			if ($query->num_rows) {
				$unit_data['name'] = $query->row['rus_name1'];
			} else {
				$unit_data['name'] = "шт.";
			}
		}
		$this->log("> Единица измерения: '" . $unit_data['name'] . "'");

		return $unit_data;

	} // parseProductUnit()


	/**
	 * ver 3
	 * update 2017-04-26
	 * Обрабатывает единицы измерения в классификаторе XML <= 2.09
	 */
	private function parseClassifierUnits($xml) {

		$result = array();
		$old_inits = array();

		// Прочитаем старые соответствия единиц измерения
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "unit_to_1c`");
		if ($query->num_rows) {
			$old_inits[$query->row['unit_id']] = $query->row['guid'];
		}

		foreach ($xml->ЕдиницаИзмерения as $unit) {
			// Сопоставляет Ид с id единицей измерения CMS
			$guid		= (string)$unit->Ид;
			$delete		= isset($unit->ПометкаУдаления) ? (string)$unit->ПометкаУдаления : "false";
			$name		= (string)$unit->НаименованиеКраткое;
			$code		= (string)$unit->Код;
			$fullname	= (string)$unit->НаименованиеПолное;
			$code_eng	= (string)$unit->МеждународноеСокращение;

			$key = array_search($guid, $old_inits);
			if (false !== $key) {
				unset($old_inits[$key]);
			} else {
				$unit_id = $this->getUnitId($code);
				// Проверим есть ли такая в базе
				$query = $this->query("SELECT `unit_id` FROM `" . DB_PREFIX . "unit_to_1c` WHERE `guid` = '" . $this->db->escape($guid) . "' AND `unit_id` = " . $unit_id);
				if ($query->num_rows) {
					// Есть такая, позже проверка будет на изменения других столбцов
				} else {
					$this->query("INSERT INTO `" . DB_PREFIX . "unit_to_1c` SET `guid` = '" . $this->db->escape($guid) . "', `unit_id` = " . $unit_id);
					$unit_id = $this->db->getlastId();
				}
			}
			if ($this->config->get('exchange1c_parse_unit_in_memory') == 1) {
				$result[$guid] = $unit_id;
			}
		}

		// удаляем неиспользуемые
		if (count($old_inits)) {
			$delete_units = array();
			foreach ($old_inits as $unit_id => $old_init) {
				$delete_units[] = $unit_id;
			}
			$this->query("DELETE FROM `" . DB_PREFIX . "unit_to_1c` WHERE `unit_id` IN (" . implode(",",$delete_units) . ")");
			$this->log("Удалены неиспользуемые единицы, unit_id: " . implode(",",$delete_units), 2);
		}
		$this->log("Прочитаны единицы измерения в классификаторе (XML <= 2.09)", 2);
		return $result;

	} // parseClassifierUnits()


	/**
	 * ver 2
	 * update 2017-04-14
	 * Отзывы парсятся с Яндекса в 1С, а затем на сайт
	 * Доработка от SunLit (Skype: strong_forever2000)
	 * Читает отзывы из классификатора и записывает их в массив
	 */
	private function parseReview($xml) {

		$product_review = array();
		foreach ($xml->Отзыв as $property) {
			$product_review[trim((string)$property->Ид)] = array(
				'id'	=> trim((string)$property->Ид),
				'name'	=> trim((string)$property->Имя),
				'yes'	=> trim((string)$property->Да),
				'no'	=> trim((string)$property->Нет),
				'text'	=> trim((string)$property->Текст),
				'rate'	=> (int)$property->Рейтинг,
				'date'	=> trim((string)$property->Дата),
			);
			$this->log("> " . trim((string)$property->Имя) . "'",2);
		}
		$this->log("Отзывы прочитаны",2);
		return $product_review;

	} // parseReview()


	/**
	 * Удаляет старые неиспользуемые картинки
	 * Сканирует все файлы в папке import_files и ищет где они указаны в товаре, иначе удаляет файл
	 */
	public function cleanOldImages($folder) {

		$result = array('error' => "", 'num' => 0);
		if (!file_exists(DIR_IMAGE . $folder)) {
			$result['error'] = "Папка не существует: /image/" . $folder;
			return $result;
		}
		$dir = dir(DIR_IMAGE . $folder);
		while ($file = $dir->read()) {

			if ($file == '.' || $file == '..') {
				continue;
			}

			$path = $folder . $file;

			if (file_exists(DIR_IMAGE . $path)) {
				if (is_file(DIR_IMAGE . $path)) {

					// это файл, проверим его причастность к товару
					$query = $this->query("SELECT `product_id`,`image` FROM `" . DB_PREFIX . "product` WHERE `image` LIKE '". $path . "'");
					if ($query->num_rows) {
						$this->log("> файл: '" . $path . "' принадлежит товару: " . $query->row['product_id'], 2);
						continue;
					} else {
						$this->log("> Не найден в базе, нужно удалить файл: " . $path, 2);
						$result_ = @unlink(DIR_IMAGE . $path);
						if ($result_) {
							$result['num']++;
						} else {
							$this->log("[!] Ошибка удаления файла: " . $path, 2);
							$result['error'] .= "Ошибка удаления файла: " . $path . "\n";
							return $result;
						}
					}

				} elseif (is_dir(DIR_IMAGE . $path)) {
					$this->cleanOldImages($path . '/', $result['num']);
					// Попытка удалить папку, если она не пустая, то произойдет удаление
					$result_ = @rmdir(DIR_IMAGE . $path);
					if ($result_) {
						$this->log("> Удалена пустая папка: " . $path, 2);
					}
					continue;
				}
			}

		}
		return $result;

	} // cleanOldImages()



	/**
	 * Отключает все товары, можно сделать опцию удаления ненужных и их связи и так далее
	 */
	private function cleanProductData($product_id) {

		if ($this->config->get('exchange1c_clean_options') == 1) {
			$this->log("[!] Перед полной загрузкой удаляются у товара все характеристики, опции, цены, остатки и единицы измерений");
			$this->query("DELETE FROM `" . DB_PREFIX . "product_feature` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_price` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_unit` WHERE `product_id` = " . $product_id);
		}

	} // cleanProductData()


	/**
	 * Удаляет все дубли связей с торговой системой
	 */
	public function removeDoublesLinks() {

		$tables = array('attribute','category','manufacturer','product','store','unit');
		$result = array('error'=>"");

		// начинаем работать с каждой таблицей
		foreach ($tables as $table) {
			$field_id = $table . "_id";
			$result[$table] = 0;
			$query = $this->query("SELECT `" . $field_id . "`, `guid`, COUNT(*) as `count` FROM `" . DB_PREFIX . $table . "_to_1c` GROUP BY `" . $field_id . "`,`guid` HAVING COUNT(*)>1 ORDER BY COUNT(*) DESC");
			if ($query->num_rows) {
				$this->log("Есть дубликаты GUID", 2);
				$this->log($query, 2);
				foreach ($query->rows as $row) {
					$limit = (int)$row['count'] - 1;
					$result[$table] += $limit;
					$this->query("DELETE FROM `" . DB_PREFIX . $table . "_to_1c` WHERE `" . $field_id . "` = " . $row[$field_id] . " AND `guid` = '" . $this->db->escape($row['guid']) . "' LIMIT " . $limit);
				}
			}

		}
		$this->log("Дубли ссылок удалены");
		return $result;

	} // removeDoublesLinks()


	/**
	 * Возвращает название товара
	 */
	private function parseProductName($product, &$data) {

		$name = "";

		if ($product->ПолноеНаименование) {
			$data['fullname']		= htmlspecialchars((string)$product->ПолноеНаименование);
			$this->log("> Найдено полное наименование: '" . $data['fullname'] . "'", 2);
		}

		// Название поля наименования
		$field_name = $this->config->get('exchange1c_import_product_name_field');

		if ($this->config->get('exchange1c_import_product_name') == "manually") {
			$name = htmlspecialchars((string)$product->$field_name);
		} elseif ($this->config->get('exchange1c_import_product_name') == "fullname") {
			$name = $data['fullname'];
		}
		if ($name) {
			$data['name'] = $name;
		}

	} // parseProductName()


	/**
	 * Возвращает название модели
	 */
	private function parseProductModel($product, $data) {

		if ($product->Модель) {
			return (string)$product->Модель;
		}
		return	isset($data['sku']) ? $data['sku'] : $data['product_guid'];

	} // parseProductModel()


	/**
	 * Возвращает преобразованный числовой id из Код товара торговой системы
	 */
	private function parseCode($code) {

		$out = "";
		// Пока руки не дошли до преобразования, надо откидывать префикс, а после лидирующие нули
		$length = mb_strlen($code);
		$begin = -1;
		for ($i = 0; $i <= $length; $i++) {
			$char = mb_substr($code,$i,1);
			// ищем первую цифру не ноль
			if ($begin == -1 && is_numeric($char) && $char != '0') {
				$begin = $i;
				$out = $char;
			} else {
				// начало уже определено, читаем все цифры до конца
				if (is_numeric($char)) {
					$out .= $char;
				}
			}
		}
		return	(int)$out;

	} // parseCode()


	/**
	 * Возвращает id производителя
	 */
	private function getProductManufacturerId($product) {

		// Читаем изготовителя, добавляем/обновляем его в базу
		if ($product->Изготовитель) {
			return $this->setManufacturer($product->Изготовитель->Наименование, $product->Изготовитель->Ид);
		}
		// Читаем производителя из поля Бренд <Бренд>Denny Rose</Бренд>
		if ($product->Бренд) {
			return $this->setManufacturer($product->Бренд);
		}

	} // getProductManufacturerId()


	/**
	 * ver 2
	 * update 2017-04-26
	 * Возвращает id категорий по GUID
	 */
	private function parseProductCategories($categories, $classifier_categories = array()) {

		$result = array();
		if ($this->config->get('exchange1c_synchronize_by_code') == 1) {
			foreach ($categories->Код as $category_code) {
				$category_id = $this->parseCode($category_code);
				if ($category_id) {
					$result[] = $category_id;
				}
			}
			if (count($result)) {
				$this->log("Категории прочитаны по Коду",2);
				return $result;
			}
		}
		foreach ($categories->Ид as $category_guid) {
			$guid = (string)$category_guid;
			if ($classifier_categories) {
				// Ищем в массиве
				if (isset($classifier_categories[$guid])) {
					$category_id = $classifier_categories[$guid];
					$this->log("Категория найдена в массиве, category_id = " . $category_id);
				}
			} else {
				// Ищем в базе данных
				$category_id = $this->getCategoryIdByGuid($guid);
			}
			if ($category_id) {
				$result[] = $category_id;
			} else {
				$this->log("[!] Категория не найдена по Ид: " . $guid);
			}
		}
		$this->log("Категории товаров прочитаны.",2);
		return $result;

	} // parseProductCategories()


	/**
	 * ver 5
	 * update 2017-04-20
	 * Обрабатывает товары из раздела <Товары> в XML
	 * При порционной выгрузке эта функция запускается при чтении каждого файла
	 * При полной выгрузке у товара очищаются все и загружается по новой.
	 * В формате 2.04 характеристики названия характеристике и их значение для данного товара передается тут
	 * Начиная с версии 1.6.3 читается каждая характеристика по отдельности, так как некоторые системы рвут товары с характеристиками
	 */
	private function parseProducts($xml, $classifier) {

		if (!$xml->Товар) {
			$this->ERROR = "parseProducts() - empty XML";
			return false;
		}

		foreach ($xml->Товар as $product){

			$data = array();

			// Получаем Ид товара и характеристики
			$guid_full = explode("#", (string)$product->Ид);
			$data['product_guid']	= $guid_full[0];
			$data['feature_guid']	= isset($guid_full[1]) ? $guid_full[1] : '';
			$data['product_id'] 	= 0;
//			$data['mpn']			= $data['product_guid'];
			$data['name']			= htmlspecialchars((string)$product->Наименование);

			// Единица измерения длины товара
			if ($this->config->get('config_length_class_id')) {
				$data['length_class_id']	= $this->config->get('config_length_class_id');
			}

			// Единица измерения веса товара
			if ($this->config->get('config_weight_class_id')) {
				$data['weight_class_id']	= $this->config->get('config_weight_class_id');
			}

			$this->log("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 2);
			if ($data['feature_guid']) {
				$this->log("ТОВАР: Ид: '" . $data['product_guid'] . "', ХАРАКТЕРИСТИКА Ид: '" . $data['feature_guid'] . "'");
			} else {
				$this->log("ТОВАР: Ид: '" . $data['product_guid'] . "'");
			}

			// есть ли в предложении характеристики
			if ($product->ХарактеристикиТовара) {
				$result = $this->parseFeatures($product->ХарактеристикиТовара, $data);
				if (count($result)) $data['features'] = $result;
				if ($this->ERROR) return false;
			}

			// Артикул товара или характеристики
			if ($product->Артикул) {
				$data['sku']		= htmlspecialchars((string)$product->Артикул);
			}

			// Код товара для прямой синхронизации
			if ($product->Код) {
				$data['code']		= $this->parseCode((string)$product->Код);
			}

			// Пометка удаления, товар будет отключен
			if ((string)$product->ПометкаУдаления == 'true') {
				$data['status'] = 0;
			}

			// Синхронизация с Яндекс маркет
			// В некоторых CMS имеется поле для синхронизаци, например с Yandex
			if (isset($this->TAB_FIELDS['product']['noindex'])) {
				$data['noindex']		= 1; // В некоторых версиях
			}

			// Описание товара в текстовом формате, нужна опция если описание в формате HTML
			if ($product->Описание && $this->config->get('exchange1c_import_product_description') == 1)	{
				if ($this->config->get('exchange1c_description_html') == 1) {
					$data['description']	=  nl2br(htmlspecialchars((string)$product->Описание));
				} else {
					$data['description'] 	= (string)$product->Описание;
				}
			}

			// Реквизиты товара из торговой системы (разные версии CML)
			if ($product->ЗначениеРеквизита) {
				$this->parseRequisite($product, $data);
				if ($this->ERROR) {
					return false;
				}

			} elseif ($product->ЗначенияРеквизитов) {
				// Реквизиты (разные версии CML)
				$this->parseRequisite($product->ЗначенияРеквизитов, $data);
				if ($this->ERROR) {
					return false;
				}
			}

			// Модель товара
			// Читается из поля товара "SKU" или из реквизита "Модель" в зависимости от настроек
			$data['model']	= $this->parseProductModel($product, $data);

			// Наименование товара или характеристики
			// Если надо меняет наименование товара из полного или из поля пользователя
			$this->parseProductName($product, $data);
			$this->log("> наименование: '" . $data['name'] . "'");

			// Тип номенклатуры ()читается из реквизитов)
			// Если фильтр по типу номенклатуры заполнен, то загружаем указанные там типы
			$exchange1c_parse_only_types_item = $this->config->get('exchange1c_parse_only_types_item');
			if (isset($data['item_type']) && (!empty($exchange1c_parse_only_types_item))) {
				if (mb_stripos($exchange1c_parse_only_types_item, $data['item_type']) === false) {
				 	continue;
				}
			}

			// Категории товара (Группы в 1С)
			if ($this->config->get('exchange1c_import_product_categories') == 1) {
				if ($product->Группы) {
					if (isset($classifier['categories'])) {
						$data['product_categories']	= $this->parseProductCategories($product->Группы, $classifier['categories']);
					} else {
						$data['product_categories']	= $this->parseProductCategories($product->Группы);
					}
				}
			}

			if ($this->config->get('exchange1c_import_product_manufacturer') == 1) {
				$manufacturer_id = $this->getProductManufacturerId($product);
				if ($manufacturer_id) {
					$data['manufacturer_id'] = $manufacturer_id;
				}
			}

			// Статус, только для товара
			// Статус по-умолчанию при отсутствии товара на складе
			// Можно реализовать загрузку из свойств
			if ($this->config->get('exchange1c_default_stock_status')) {
				$data['stock_status_id'] = $this->config->get('exchange1c_default_stock_status');
			}

			// Свойства, только для товара
			//if ($product->ЗначенияСвойств && isset($classifier['attributes']))
			if ($product->ЗначенияСвойств) {
				$this->parseAttributes($product->ЗначенияСвойств, $data, $classifier);
				if ($this->ERROR) return false;
			}

			// Картинки, только для товара
			if ($product->Картинка) {
				$data['images'] = $this->parseImages($product->Картинка, $data);
				if ($this->ERROR) return false;
			}

			// Картинки, только для товара (CML 2.04)
			if ($product->ОсновнаяКартинка) {
				$data['images'] = $this->parseImages($product->ОсновнаяКартинка, $data);
				if ($this->ERROR) return false;
				// дополнительные, когда элементы в файле называются <Картинка1>, <Картинка2>...
				$cnt = 1;
				$var = 'Картинка'.$cnt;
				while (!empty($product->$var)) {
					$data['images'] = $this->parseImages($product->$var, $data);
					if ($this->ERROR) return false;
					$cnt++;
					$var = 'Картинка'.$cnt;
				}
			}

			// Основная картинка
			if (isset($data['images'][0])) {
				$data['image'] = $data['images'][0]['file'];
			} else {
				// если картинки нет подставляем эту
				$data['image'] = 'no_image.png';
			}

			// Штрихкод
			if ($product->Штрихкод) {
				$data['ean'] =  (string)$product->Штрихкод;
			}

			// Единица товара
			if ($product->БазоваяЕдиница) {
				$data['unit'] = $this->parseProductUnit($product->БазоваяЕдиница);
				if ($this->ERROR) return false;
			} else {
				$data['unit'] = $this->parseProductUnit("шт");
				if ($this->ERROR) return false;
			}

			// Отзывы парсятся с Яндекса в 1С, а затем на сайт
			// Доработка от SunLit (Skype: strong_forever2000)
			// Отзывы
			if ($product->ЗначенияОтзывов) {
				$data['review'] = $this->parseReview($data, $product->ЗначенияОтзывов);
				if ($this->ERROR) return false;
			}

			// Добавляем или обновляем товар в базе
			$this->setProduct($data);
			if ($this->ERROR) return false;

			unset($data);

			if (file_exists(DIR_CACHE . 'exchange1c/break')) {
				$this->ERROR = "parseProducts() - file exists 'break'";
				return false;
			}
		} // foreach

		// Отключим товары не попавшие в обмен только при полной выгрузке
		if ($this->config->get('exchange1c_product_not_import_disable') == 1 && $this->FULL_IMPORT) {
			$products_disable = array();
			$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `date_modified` < '" . $this->NOW . "'");
			$num = 0;
			// Эта переменная указывает сколько товаров может отключать за один запрос.
			$num_part = 1000;
			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					$products_disable[] = $row['product_id'];
					if (count($products_disable) >= $num_part) {
						$this->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0 WHERE `product_id` IN (" . (implode(",",$products_disable)) . ")");
						$products_disable = array();
						$num += count($products_disable);
					}
				}
				if ($products_disable) {
					$this->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0 WHERE `product_id` IN (" . (implode(",",$products_disable)) . ")");
					$num += count($products_disable);
				}
			}
			$this->log("Отключены товары которых нет в выгрузке: " . $num);
		}

		return true;

	} // parseProducts()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Разбор каталога
	 */
	private function parseDirectory($xml, $classifier) {

 		$this->checkFullImport($xml);

		$directory					= array();
		$directory['guid']			= (string)$xml->Ид;
		$directory['name']			= (string)$xml->Наименование;
		$directory['classifier_id']	= (string)$xml->ИдКлассификатора;
		if (isset($classifier['id'])) {
			if ($directory['classifier_id'] <> $classifier['id']) {
				$this->ERROR = "Загружаемый каталог не соответствует классификатору";
				return false;
			}
		}

		if ($this->config->get('exchange1c_flush_quantity') == 1 && $this->FULL_IMPORT) {
			$this->clearProductsQuantity();
		}

		// Загрузка товаров
		$this->parseProducts($xml->Товары, $classifier);
		if ($this->ERROR) return false;

		return true;

	} // parseDirectory()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ПРЕДЛОЖЕНИЙ ******************************
	 */

	/**
	 * Добавляет склад в базу данных
	 */
	private function addWarehouse($warehouse_guid, $name) {

		$this->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET `name` = '" . $this->db->escape($name) . "', `guid` = '" . $this->db->escape($warehouse_guid) . "'");
		$warehouse_id = $this->db->getLastId();
		$this->log("> Склад добавлен, warehouse_id = " . $warehouse_id, 2);
		return $warehouse_id;

	} // addWarehouse()


	/**
	 * Ищет warehouse_id по GUID
	 */
	private function getWarehouseByGuid($warehouse_guid) {

		$query = $this->query('SELECT `warehouse_id` FROM `' . DB_PREFIX . 'warehouse` WHERE `guid` = "' . $this->db->escape($warehouse_guid) . '"');
		if ($query->num_rows) {
			return $query->row['warehouse_id'];
		}
		$this->log("Склад не найден в базе", 2);
		return 0;

	} // getWarehouseByGuid()


	/**
	 * Возвращает id склада
	 */
	private function setWarehouse($warehouse_guid, $name) {

		// Поищем склад по 1С Ид
		$warehouse_id = $this->getWarehouseByGuid($warehouse_guid);
		if (!$warehouse_id) {
			$warehouse_id = $this->addWarehouse($warehouse_guid, $name);
			$this->log("> Склад добавлен, warehouse_id = " . $warehouse_id,2);
		}
  		return $warehouse_id;

	} // setWarehouse()


	/**
	 * Получает остаток товара по фильтру
	 * ver 2
	 * update 2017-04-05
	 */
	private function getProductQuantityTotal($product_id) {

		$query = $this->query("SELECT SUM(`quantity`) as `quantity` FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id);
		if ($query->num_rows) {
			return (float)$query->row['quantity'];
		} else {
			return false;
		}

	} // getProductQuantityTotal()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Получает все цены характеристик товара и возвращает минимальную среди цен которые больше нуля
	 */
	private function getProductPriceMin($product_id) {

		$default_customer_group_id = $this->config->get('config_customer_group_id');
		$query = $this->query("SELECT MIN(`price`) as `price` FROM `" . DB_PREFIX . "product_price` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $default_customer_group_id . " AND `price` > 0");
		if ($query->num_rows) {
			return (float)$query->row['price'];
		} else {
			return false;
		}

	} // getProductPriceMin()


	/**
	 * Получает остаток товара по фильтру
	 */
	private function getProductQuantity($product_quantity_filter) {

		$result = array();
		$where = "`product_id` = " . $product_quantity_filter['product_id'];
		$where .= " AND `product_feature_id` = " . (isset($product_quantity_filter['product_feature_id']) ?  $product_quantity_filter['product_feature_id'] : 0);
		$where .= " AND `warehouse_id` = " . (isset($product_quantity_filter['warehouse_id']) ?  $product_quantity_filter['warehouse_id'] : 0);

		$query = $this->query("SELECT `product_quantity_id`,`quantity` FROM `" . DB_PREFIX . "product_quantity` WHERE " . $where);
		if ($query->num_rows) {
			$result['quantity'] 			= $query->row['quantity'];
			$result['product_quantity_id']	= $query->row['product_quantity_id'];
			return $result;
		} else {
			$result['product_quantity_id']	= 0;
			return $result;
		}

	} // getProductQuantity()


	/**
	 * Добавляет остаток товара по фильтру
	 */
	private function addProductQuantity($product_quantity_filter, $quantity) {

		$set = "";
		foreach ($product_quantity_filter as $field => $value) {
			$set .= ", `" . $field . "` = " . $value;
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "product_quantity` SET `quantity` = '" . (float)$quantity . "'" . $set);
		$product_quantity_id = $this->db->getLastId();
		$this->log("> Добавлены остатки в товар, product_quantity_id = " . $product_quantity_id, 2);
		return $product_quantity_id;

	} // addProductQuantity()


	/**
	 * Сравнивает массивы и формирует список измененных полей для запроса
	 */
	private function compareArraysNew($data1, $data2, $filelds_include = "") {

		//$result = array_diff_assoc($data1, $data2);
		//$filelds_include_obj = explode(",", $filelds_include);

		$upd_fields = array();
		if (count($data1)) {
			foreach($data1 as $key => $row) {
				if (!isset($data2[$key])) continue;
				if (!empty($filelds_include) && strripos($filelds_include, $key) === false) continue;
				if ($row <> $data2[$key]) {
					$upd_fields[] = "`" . $key . "` = '" . $this->db->escape($data2[$key]) . "'";
					$this->log("[i] Отличается поле '" . $key . "', старое: " . $row . ", новое: " . $data2[$key], 2);
				} else {
					$this->log("Поле '" . $key . "' не имеет отличий", 2);
				}
			}
		}
		return implode(', ', $upd_fields);

	} // compareArraysNew()


	/**
	 * Ищет совпадение данных в массиве данных, при совпадении значений, возвращает ключ второго массива
	 */
	private function findMatch($data1, $data_array) {

		$bestMatch = 0;
		foreach ($data_array as $key2 => $data2) {
			$matches = 0;
			$fields = 0;
			foreach ($data1 as $key1 => $value) {
				if (isset($data2[$key1])) {
					$fields++;
					if ($data2[$key1] == $value) {
						$matches++;
					}
 				}
			}
			// у всех найденых полей совпали значения
			if ($matches == $fields){
				return $key2;
			}
		}
		return false;

	} // findMatch()


	/**
	 * ver 1
	 * update 2017-04-21
	 * Сбрасывает
	 */
	private function setProductQuantity($product_quantity_filter, $quantity) {

		$result = $this->getProductQuantity($product_quantity_filter);
		if ($result['product_quantity_id']) {
			// Есть цена
			if ($result['quantity'] != $quantity) {
				$query = $this->query("UPDATE `" . DB_PREFIX . "product_quantity` SET `quantity` = " . $quantity . " WHERE `product_quantity_id` = " . $result['product_quantity_id']);
			}
		} else {
			$this->addProductQuantity($product_quantity_filter, $quantity);
		}

	} // setProductQuantity()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Устанавливает остаток товара
	 */
	private function clearProductsQuantity() {

		$this->query("UPDATE `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) SET `p`.`quantity` = 0 WHERE `p2s`.`store_id` = " . $this->STORE_ID);

	} // setProductQuantity()


	/**
	 * ver 2
	 * update 2017-04-09
	 * Устанавливает остатки товара
	 */
	private function setProductQuantities($quantities, $product_id, $product_feature_id = 0, $new = false) {

		foreach ($quantities as $warehouse_id => $quantity) {
			$product_filter = array(
				'product_id'			=> $product_id,
				'warehouse_id'			=> $warehouse_id,
				'product_feature_id'	=> $product_feature_id
			);
			$this->setProductQuantity($product_filter, $quantity);

			$this->log("Остаток на складе (warehouse_id=" . $warehouse_id . ") = " . $quantity);
		}

	} // setProductQuantities()


	/**
	 * Удаляет склад и все остатки поо нему
	 */
	private function deleteWarehouse($warehouse_guid) {

		$warehouse_id = $this->getWarehouseByGuid($warehouse_guid);
		if ($warehouse_id) {
			// Удаляем все остатки по этму складу
			$this->deleteStockWarehouse($warehouse_id);
			// Удалим остатки по этому складу
			$this->query("DELETE FROM `" . DB_PREFIX . "product_quantity ` WHERE `warehouse_id` = " . (int)$warehouse_id);
			// Удаляем склад
			$this->query("DELETE FROM `" . DB_PREFIX . "warehouse ` WHERE `guid` = '" . $this->db->escape($warehouse_guid) . "'");
			$this->log("Удален склад, GUID '" . $warehouse_guid . "' и все остатки на нем.",2);
		}

	} // deleteWarehouse()


	/**
	 * ver 2
	 * update 2017-04-26
	 * Загружает список складов из классификатора
	 */
	private function parseClassifierWarehouses($xml) {

		$data = array();
		foreach ($xml->Склад as $warehouse){
			if (isset($warehouse->Ид) && isset($warehouse->Наименование) ){
				$warehouse_guid = (string)$warehouse->Ид;
				$name = trim((string)$warehouse->Наименование);
				$delete = isset($warehouse->ПометкаУдаления) ? $warehouse->ПометкаУдаления : "false";
				if ($delete == "false") {
					$data[$warehouse_guid] = array(
						'name' => $name
					);
					$data[$warehouse_guid]['warehouse_id'] = $this->setWarehouse($warehouse_guid, $name);
					$this->log("Склад: '" . $name . "', id = " . $data[$warehouse_guid]['warehouse_id'], 2);
				} else {
					// Удалить склад
					$this->log("[!] Склад помечен на удаление в торговой системе и будет удален");
					$this->deleteWarehouse($warehouse_guid);
				}
			}
		}
		$this->log("Складов в классификаторе: " . count($xml->Склад), 2);
		return $data;

	} // parseClassifierWarehouses()


	/**
	 * Загружает остатки по складам
	 * Возвращает остатки по складам
	 * где индекс - это warehouse_id, а значение - это quantity (остаток)
	 * ver 2
	 * update 2017-04-05
	 */
	private function parseQuantity($xml, $data) {

		$data_quantity = array();

		if (!$xml) {
			$this->ERROR = "parseQuantity() - нет данных в XML";
			return false;
		}

		// есть секция с остатками, обрабатываем
		if ($xml->Остаток) {
			foreach ($xml->Остаток as $quantity) {
				// есть секция со складами
				if ($quantity->Склад->Ид) {
					$warehouse_guid = (string)$quantity->Склад->Ид;
					$warehouse_id = $this->getWarehouseByGuid($warehouse_guid);
					if (!$warehouse_id) {
						$this->ERROR = "parseQuantity() - Склад не найден по Ид '" . $warehouse_guid . "'";
						return false;
					}
				} else {
					$warehouse_id = 0;
				}
				if ($quantity->Склад->Количество) {
					$quantity = (float)$quantity->Склад->Количество;
				}
				$data_quantity[$warehouse_id] = $quantity;
			}
		} elseif ($xml->Склад) {
			foreach ($xml->Склад as $quantity) {
				// есть секция со складами
				$warehouse_guid = (string)$quantity['ИдСклада'];
				if ($warehouse_guid) {
					$warehouse_id = $this->getWarehouseByGuid($warehouse_guid);
					if (!$warehouse_id) {
						$this->ERROR = "parseQuantity() - Склад не найден по Ид '" . $warehouse_guid . "'";
						return false;
					}
				} else {
					$this->ERROR = "parseQuantity() - Не указан Ид склада!";
					return false;
				}
				$quantity = (float)$quantity['КоличествоНаСкладе'];
				$data_quantity[$warehouse_id] = $quantity;
			}
		}
		return $data_quantity;

	} // parseQuantity()


	/**
	 * Возвращает массив данных валюты по id
	 */
	private function getCurrency($currency_id) {

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "currency` WHERE `currency_id` = " . $currency_id);
		if ($query->num_rows) {
			return $query->row;
		}
		return array();

	} // getCurrency()


	/**
	 * Возвращает id валюты по коду
	 */
	private function getCurrencyId($code) {

		$query = $this->query("SELECT `currency_id` FROM `" . DB_PREFIX . "currency` WHERE `code` = '" . $this->db->escape($code) . "'");
		if ($query->num_rows) {
			$this->log("Валюта, currency_id = " . $query->row['currency_id'], 2);
			return $query->row['currency_id'];
		}

		// Попробуем поискать по символу справа
		$query = $this->query("SELECT `currency_id` FROM `" . DB_PREFIX . "currency` WHERE `symbol_right` = '" . $this->db->escape($code) . "'");
		if ($query->num_rows) {
			$this->log("Валюта, currency_id = " . $query->row['currency_id'], 2);
			return $query->row['currency_id'];
		}

		return 0;

	} // getCurrencyId()


	/**
	 * Сохраняет настройки сразу в базу данных
	 */
	private function configSet($key, $value, $store_id=0) {

		if (!$this->config->has('exchange1c_'.$key)) {
			$this->query("INSERT INTO `" . DB_PREFIX . "setting` SET `value` = '" . $value . "', `store_id` = " . $store_id . ", `code` = 'exchange1c', `key` = '" . $key . "'");
		}

	} // configSet()


	/**
	 * Получает список групп покупателей
	 */
	private function getCustomerGroups() {

		$query = $this->query("SELECT `customer_group_id` FROM `" . DB_PREFIX. "customer_group` ORDER BY `sort_order`");
		$data = array();
		foreach ($query->rows as $row) {
			$data[] = $row['customer_group_id'];
		}
		return $data;

	} // getCustomerGroups()


	/**
	 * Загружает типы цен автоматически в таблицу которых там нет
	 */
	private function autoLoadPriceType($xml) {

		$this->log("autoLoadPriceType() - Автозагрузка цен из XML...", 2);
		$config_price_type = $this->config->get('exchange1c_price_type');

		if (empty($config_price_type)) {
			$config_price_type = array();
		}

		$update = false;
		$default_price = -1;

		// список групп покупателей
		$customer_groups = $this->getCustomerGroups();

		$index = 0;
		foreach ($xml->ТипЦены as $price_type)  {
			$name = trim((string)$price_type->Наименование);
			$delete = isset($price_type->ПометкаУдаления) ? $price_type->ПометкаУдаления : "false";
			$guid = (string)$price_type->Ид;
			$priority = 0;
			$found = -1;
			foreach ($config_price_type as $key => $cpt) {
				if (!empty($cpt['id_cml']) && $cpt['id_cml'] == $guid) {
					$this->log("autoLoadPriceType() - Найдена цена по Ид: '" . $guid . "'", 2);
					$found = $key;
					break;
				}
				if (strtolower(trim($cpt['keyword'])) == strtolower($name)) {
					$this->log("autoLoadPriceType() - Найдена цена по наименованию: '" . $name . "'", 2);
					$found = $key;
					break;
				}
				$priority = max($priority, $cpt['priority']);
			}

			if ($found >= 0) {
				// Если тип цены помечен на удаление, удалим ее из настроек
				if ($delete == "true") {
					$this->log("autoLoadPriceType() - Тип цены помечен на удаление, не будет загружен и будет удален из настроек", 2);
					unset($config_price_type[$found]);
					$update = true;
				} else {
					// Обновим Ид
					if ($config_price_type[$found]['guid'] != $guid) {
						$config_price_type[$found]['guid'] = $guid;
						$update = true;
					}
				}

			} else {
				// Добавим цену в настройку если он ане помечена на удаление
				if ($default_price == -1) {
					$table_price = "product";
					$default_price = count($config_price_type)+1;
				} else {
					$table_price = "discount";
				}
				$customer_group_id = isset($customer_groups[$index]) ? $customer_groups[$index] : $this->config->get('config_customer_group_id');
				if ($delete == "false") {
					$config_price_type[] = array(
						'keyword' 				=> $name,
						'guid' 					=> $guid,
						'table_price'			=> $table_price,
						'customer_group_id' 	=> $customer_group_id,
						'quantity' 				=> 1,
						'priority' 				=> $priority
					);
					$update = true;

				}
			} // if
			$index++;
		} // foreach

        if ($update) {
			if ($this->config->get('exchange1c_price_type')) {
				$this->query("UPDATE `". DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($config_price_type)) . "', `serialized` = 1 WHERE `key` = 'exchange1c_price_type'");
				$this->log("autoLoadPriceType() - Настройки цен обновлены", 2);
	        } else {
				$this->query("INSERT `". DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($config_price_type)) . "', `serialized` = 1, `code` = 'exchange1c', `key` = 'exchange1c_price_type'");
				$this->log("autoLoadPriceType() - Настройки цен добавлены", 2);
	        }
        }
		return $config_price_type;

	} // autoLoadPriceType()


	/**
	 * ver 3
	 * update 2017-04-26
	 * Загружает типы цен из классификатора
	 * Обновляет Ид если найдена по наименованию
	 * Сохраняет настройки типов цен
	 */
	private function parseClassifierPriceType($xml) {

		// Автозагрузка цен
		if ($this->config->get('exchange1c_price_types_auto_load')) {
			$config_price_type = $this->autoLoadPriceType($xml);
		} else {
			$config_price_type = $this->config->get('exchange1c_price_type');
		}

		$data = array();

		if (empty($config_price_type)) {
			$this->ERROR = "parseClassifierPriceType() - В настройках модуля не указаны цены";
			return false;
		}

		// Перебираем все цены из CML
		foreach ($xml->ТипЦены as $price_type)  {
			$currency		= isset($price_type->Валюта) ? (string)$price_type->Валюта : "RUB";
			$guid			= (string)$price_type->Ид;
		 	$name			= trim((string)$price_type->Наименование);
		 	$code			= $price_type->Код ? $price_type->Код : ($price_type->Валюта ? $price_type->Валюта : '');

			// Найденный индекс цены в настройках
			$found = -1;

			// Перебираем все цены из настроек модуля
			foreach ($config_price_type as $index => $config_type) {

				if ($found >= 0)
					break;

				if (!empty($config_type['guid']) && $config_type['guid'] == $guid) {
					$found = $index;
					break;
				} elseif (strtolower($name) == strtolower($config_type['keyword'])) {
					$found = $index;
					break;
				}

			} // foreach ($config_price_type as $config_type)

			if ($found >= 0) {
				if ($code) {
					$currency_id				= $this->getCurrencyId($code);
				} else {
					$currency_id				= $this->getCurrencyId($currency);
				}
				$data[$guid] 					= $config_type;
				$data[$guid]['currency'] 		= $currency;
				$data[$guid]['currency_id'] 	= $currency_id;
				if ($currency_id) {
					$currency_data = $this->getCurrency($currency_id);
					$rate = $currency_data['value'];
					$decimal_place = $currency_data['decimal_place'];
				} else {
					$rate = 1;
					$decimal_place = 2;
				}
				$data[$guid]['rate'] 			= $rate;
				$data[$guid]['decimal_place'] = $decimal_place;
				$this->log('Вид цены: ' . $name,2);
			} else {
				$this->ERROR = "parseClassifierPriceType() - Цена '" . $name . "' не найдена в настройках модуля, Ид = '" . $guid . "'";
				return false;
			}

		} // foreach ($xml->ТипЦены as $price_type)
		return $data;

	} // parseClassifierPriceType()


	/**
	 * ver 4
	 * update 2017-04-18
	 * Устанавливает цену скидки или акции товара
	 */
	private function setProductPrice($price_data, $product_id, $new = false) {

		$price_id = 0;
		if ($price_data['table_price'] == 'discount') {
			if (!$new) {
				$query = $this->query("SELECT `product_discount_id`,`customer_group_id`,`price`,`quantity`,`priority` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id'] . " AND `quantity` = " . $price_data['quantity']);
				if ($query->num_rows) {
					$price_id = $query->row['product_discount_id'];
					$update_fields = $this->compareArrays($query, $price_data);
					// Если есть расхождения, производим обновление
					if ($update_fields) {
						$this->query("UPDATE `" . DB_PREFIX . "product_discount` SET " . $update_fields . " WHERE `product_discount_id` = " . $price_id);
						$this->log("> Cкидка обновлена: " . $price_data['price'], 2);
					}
				}
			}
			if (!$price_id) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = " . $product_id . ", `quantity` = " . $price_data['quantity'] . ", `priority` = " . $price_data['priority'] . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `price` = '" . (float)$price_data['price'] . "'");
				$price_id = $this->db->getLastId();
				$this->log("> Cкидка добавлена: " . $price_data['price'], 2);
			}


		} elseif ($price_data['table_price'] == 'special') {
			if (!$new) {
				$query = $this->query("SELECT `product_special_id`,`customer_group_id`,`price` FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = " . $product_id . " AND `customer_group_id` = " . $price_data['customer_group_id']);
				if ($query->num_rows) {
					$price_id = $query->row['product_special_id'];
					$update_fields = $this->compareArrays($query, $price_data);
					// Если есть расхождения, производим обновление
					if ($update_fields) {
						$this->query("UPDATE `" . DB_PREFIX . "product_special` SET " . $update_fields . " WHERE `product_special_id` = " . $price_id);
						$this->log("> Акция обновлена: " . $price_data['price'], 2);
					}
				}
			}
			if (!$price_id) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_special` SET `product_id` = " . $product_id . ", `priority` = " . $price_data['priority'] . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `price` = '" . (float)$price_data['price'] . "'");
				$update_fields = $this->db->getLastId();
				$this->log("> Акция добавлена: " . $price_data['price'], 2);
			}

		}
		return $price_id;

	} // setProductPrice()


	/**
	 * ver 8
	 * update 2017-05-07
	 * Устанавливает цены товара
	 */
	private function setProductPrices($prices_data, $product_id, $product_feature_id = 0, $new = false) {

		$price = 0;

		if (!$new) {
			$old_discount = array();
			if ($this->FULL_IMPORT && !$product_feature_id) {
				if ($this->config->get('exchange1c_clean_prices_full_import') == 1) {
					// При полной выгрузке удаляем все старые скидки товара
					$this->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id);
				}
			} else {
				// Читаем старые скидки товара
				$query = $this->query("SELECT `product_discount_id` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id);
				foreach ($query->rows as $row) {
					$old_discount[] = $row['product_discount_id'];
				}
			}
			$old_special = array();
			if ($this->FULL_IMPORT && !$product_feature_id) {
				if ($this->config->get('exchange1c_clean_prices_full_import') == 1) {
					// При полной выгрузке удаляем все старые скидки товара
					$this->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = " . $product_id);
				}
			} else {
				// Читаем старые акции товара
				$query = $this->query("SELECT `product_special_id` FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = " . $product_id);
				foreach ($query->rows as $row) {
					$old_special[] = $row['product_special_id'];
				}
			}
		}

		foreach ($prices_data as $price_data) {

			if ($price_data['table_price'] == 'product') {

				$price = $price_data['price'];
				$this->log("> Цена для записи в товар: " . $price . " для одной базовой единицы товара", 2);

			} else {
				// Если есть характеристики, то скидки и акции не пишем
				if ($product_feature_id) {
					continue;
				}
				// устанавливает цену скидки или акции в зависимости от настроек
				$price_id = $this->setProductPrice($price_data, $product_id, $new);
				if ($price_id) {
					if ($price_data['table_price'] == 'discount') {
						$key = array_search($price_id, $old_discount);
						if ($key !== false) {
							unset($old_discount[$key]);
						}
					} elseif ($price_data['table_price'] == 'discount') {
						$key = array_search($price_id, $old_special);
						if ($key !== false) {
							unset($old_special[$key]);
						}
					}
				}
			}
		}

		if (!$new) {
			if (count($old_discount)) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id . " AND `product_discount_id` IN (" . implode(",",$old_discount) . ")");
			}
			if (count($old_special)) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = " . $product_id . " AND `product_special_id` IN (" . implode(",",$old_special) . ")");
			}
		}
		return $price;

	} // setProductPrices()


	/**
	 * ver 8
	 * update 2017-05-07
	 * Устанавливает цены характеристик товара базовой единицы товара
	 * поле action имеет значение:
	 * 0 - без акции и без скидки
	 * 1 - акция (special)
	 * 2 - скидка (discount)
	 */
	private function setProductFeaturePrices($prices_data, $product_id, $product_feature_id = 0, $new = false) {

		$old_prices = array();
		if (!$new) {
			// Читаем старые цены этой характеристики
			$query = $this->query("SELECT `product_price_id`,`price`,`customer_group_id`,`action` FROM `" . DB_PREFIX . "product_price` WHERE `product_feature_id` = " . $product_feature_id . " AND `product_id` = " . $product_id);
			foreach ($query->rows as $row) {

				$old_prices[$row['product_price_id']] = array(
					'price'				=> $row['price'],
					'customer_group_id'	=> $row['customer_group_id'],
					'action'			=> $row['action'],
				);
			}
		}

		$this->log($old_prices, 2);
		$this->log($prices_data, 2);
		// пробежимся по ценам
		foreach ($prices_data as $price_guid => $price_data) {

			if ($price_data['quantity'] != 1) {
				continue;
			}

			if ($price_data['table_price'] == 'product') {
				$action = 0;
			} elseif ($price_data['table_price'] == 'special') {
				$action = 1;
			} elseif ($price_data['table_price'] == 'discount') {
				$action = 2;
			}

			$product_price_id = 0;
			if (!$new) {
				foreach ($old_prices as $product_price_id => $old_price) {
					if ($old_price['customer_group_id'] == $price_data['customer_group_id'] && $action == $old_price['action']) {
						if ($old_price['price'] != $price_data['price']) {
							// Если цена отличается
							$this->query("UPDATE `" . DB_PREFIX . "product_price` SET `price` = '" . $price_data['price'] . "' WHERE `product_price_id` = " . $product_price_id);
							$this->log("> Цена характеристики обновлена: " . $price_data['price']);
						}
						break;
					}
					$product_price_id = 0;
				}

				if ($product_price_id) {
					unset($old_prices[$product_price_id]);
				}
			}
			if (!$product_price_id) {
				$query = $this->query("INSERT INTO `" . DB_PREFIX . "product_price` SET `product_id` = " . $product_id . ", `product_feature_id` = " . $product_feature_id . ", `customer_group_id` = " . $price_data['customer_group_id'] . ", `action` = " . $action . ", `price` = '" . (float)$price_data['price'] . "'");
				$product_price_id = $this->db->getLastId();
			}
		}

		if (!$new) {
			// Удаляем отсутствующие цены этой характеристики
			if (count($old_prices)) {
				$fields = array();
				foreach ($old_prices as $product_price_id => $price_data) {
					$fields[] = $product_price_id;
				}
				$this->query("DELETE FROM `" . DB_PREFIX . "product_price` WHERE `product_price_id` IN (" . implode(",",$fields) . ")");
			}
		}
		return true;

	} // setProductFeaturePrices()


	/**
	 * Получает по коду его id
	 */
	private function getUnitId($number_code) {

		$query = $this->query("SELECT `unit_id` FROM `" . DB_PREFIX . "unit` WHERE `number_code` = '" . $this->db->escape($number_code) . "' OR `rus_name1` = '" . $this->db->escape($number_code) . "'");
		if ($query->num_rows) {
			return $query->row['unit_id'];
		}
		return 0;

	} // getUnitId()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Загружает все цены только в одной валюте
	 */
	private function parsePrice($xml, $data) {

		if (!$xml) {
			$this->ERROR = "XML не содержит данных";
			return false;
		}

		// Читаем типы цен из настроек
		$price_types = $this->config->get('exchange1c_price_type');
		if (!$price_types) {
			$this->ERROR = "Настройки цен пустые, настройте типы цен и повторите загрузку!";
			return false;
		}

		// Массив хранения цен
		$data_prices = array();

		// Читем цены в том порядке в каком заданы в настройках
		foreach ($price_types as $config_price_type) {

			foreach ($xml->Цена as $price_data) {
				$guid		= (string)$price_data->ИдТипаЦены;

				// Цена
				$price	= $price_data->ЦенаЗаЕдиницу ? (float)$price_data->ЦенаЗаЕдиницу : 0;

				if ($config_price_type['guid'] != $guid) {
					continue;
				}

				// Курс валюты
				//$rate = $price_data->Валюта ? $this->getCurrencyValue((string)$price_data->Валюта) : 1;
				// Валюта
				// автоматическая конвертация в основную валюту CMS
				//if ($this->config->get('exchange1c_currency_convert') == 1) {
				//	if ($rate != 1 && $rate > 0) {
				//		$price = round((float)$price_data->ЦенаЗаЕдиницу / (float)$rate, $decimal_place);
				//	}
				//}
				//$data_prices[$guid]['rate'] = $rate;

				if ($this->config->get('exchange1c_ignore_price_zero') == 1 && $price == 0) {
					$this->log("Включена опция при нулевой цене не менять старую");
					continue;
				}

				// Единица измерения цены
				$unit_data = array(
					'name'		=> "шт.",
					'ratio'		=> $price_data->Коэффициент ? (float)$price_data->Коэффициент : 1,
					'unit_id'	=> $price_data->Единица ? $this->getUnitId((string)$price_data->Единица) : 0
				);

				// Копируем данные с настроек
				$data_prices[$guid] 			= $config_price_type;
				$data_prices[$guid]['unit']		= $unit_data;
				$data_prices[$guid]['price']	= $price;
				$this->log("> Цена: " . $price . ", GUID: " . $guid, 2);


			} // foreach ($xml->Цена as $price_data)
		} // foreach ($price_types as $config_price_type)

		return $data_prices;

 	} // parsePrices()


	/**
	 * ====================================== ХАРАКТЕРИСТИКИ ======================================
	 */


	/**
	 * Добавляет опциию по названию
	 */
	private function addOption($name, $type='select') {

		$this->query("INSERT INTO `" . DB_PREFIX . "option` SET `type` = '" . $this->db->escape($type) . "'");
		$option_id = $this->db->getLastId();
		$this->query("INSERT INTO `" . DB_PREFIX . "option_description` SET `option_id` = '" . $option_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");

		$this->log("Опция добавлена: '" . $name. "'", 2);
		return $option_id;

	} // addOption()


	/**
	 * Получение наименования производителя по manufacturer_id
	 */
	private function getManufacturerName($manufacturer_id, &$error) {

		if (!$manufacturer_id) {
			$error = "Не указан manufacturer_id";
			return "";
		}
		$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $manufacturer_id);
		$name = isset($query->row['name']) ? $query->row['name'] : "";
		$this->log("Производитель: '" . $name . "' по id: " . $manufacturer_id, 2);
		return $name;

	} // getManufacturerName()


	/**
	 * Получение product_id по GUID
	 */
	private function getProductIdByGuid($product_guid) {

		// Определим product_id
		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product_to_1c` WHERE `guid` = '" . $this->db->escape($product_guid) . "'");
		$product_id = isset($query->row['product_id']) ? $query->row['product_id'] : 0;
		// Проверим существование такого товара
		if ($product_id) {
			$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id);
			if (!$query->num_rows) {
				// Удалим неправильную связь
				$this->query("DELETE FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
				$product_id = 0;
			}
		}
		if ($product_id) {
			$this->log("Найден товар по GUID, product_id = " . $product_id);
		} else {
			$this->log("Не найден товар по GUID " . $product_guid, 2);
		}
		return $product_id;

	} // getProductIdByGuid()


	/**
	 * Проверка существования товара по product_id
	 */
	private function getProductIdByCode($code) {

		// Определим product_id
		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$code);
		$product_id = isset($query->row['product_id']) ? $query->row['product_id'] : 0;

		if ($product_id) {
			$this->log("Найден товар по <Код>, product_id = " . $product_id, 2);
		} else {
			$this->log("Не найден товар по <Код> " . $code, 2);
		}

		return $product_id;

	} // getProductIdByCode()


	/**
	 * Разбивает название по шаблону "[order].[name] [option]"
	 */
	private function splitNameStr($str, $opt_yes = true) {

		$str = trim(str_replace(array("\r","\n"),'',$str));
		$length = mb_strlen($str);
		$data = array(
			'order' 	=> "",
			'name' 		=> "",
			'option' 	=> ""
		);

        $pos_name_start = 0;
		$pos_opt_end = 0;
		$pos_opt_start = $length;

		if ($opt_yes) {
			// Поищем опцию
			$level = 0;
			for ($i = $length; $i > 0; $i--) {
				$char = mb_substr($str,$i,1);
				if ($char == ")") {
					$level++;
					if (!$pos_opt_end)
						$pos_opt_end = $i;
				}
				if ($char == "(") {
					$level--;
					if ($level == 0) {
						$pos_opt_start = $i+1;
						$data['option'] = mb_substr($str, $pos_opt_start, $pos_opt_end-$pos_opt_start);
						$pos_opt_start -= 2;
						break;
					}
				}
			}
		}

		// Поищем порядок сортировки, order (обязательно после цифры должна идти точка а после нее пробел!)
		$pos_order_end = 0;
		for ($i = 0; $i < $length; $i++) {
			if (is_numeric(mb_substr($str,$i,1))) {
				$pos_order_end++;
				if ($i+1 <= $length && mb_substr($str, $i+1, 1) == ".") {
					$data['order'] = (int)mb_substr($str, 0, $pos_order_end);
					$pos_name_start = $i+2;
				}
			} else {
				// Если первая не цифра, дальше не ищем
				break;
			}
		}

		// Наименование
		$data['name'] = trim(mb_substr($str, $pos_name_start, $pos_opt_start-$pos_name_start));
		return $data;

	} // splitNameStr()


	/**
	 * ver 4
	 * update 2017-04-07
	 * Разбор характеристики из файла
	 * Читает характеристики из файла(offers.xml или import.xml)
	 * Возвращает массив с элементами [features],[quantity],[price],[error]
	 * если в характеристиках нет остатков и цен, тогда не будет елементов [quantity],[price]
	 */
	private function parseFeatures($xml, $data) {

		// массив с данными
		$features 	= array();

		if (!$xml) {
			$this->ERROR = "В XML нет данных";
			return false;
		}

		if ($xml->ХарактеристикаТовара) {

			// остаток характеристики (при наличии складов по всем складам)
			if (isset($data['quantities'])) {
				$quantity = array_sum($data['quantities']);
			} else {
				$quantity = 0;
			};

			// Когда не указан Ид характеристики, значит несколько характеристик
			// Обычно так указываются в XML 2.07 в файле import_*.xml
			if (!$data['feature_guid']) {
				// ХАРАКТЕРИСТИКИ В СЕКЦИИ <ТОВАР>
				$feature_name_obj = array();
				$feature_name = "Характеристика";

				// Название характеристики нет
				foreach ($xml->ХарактеристикаТовара as $product_feature) {
					$feature_guid 		= (string)$product_feature->Ид;
					$option_value		= (string)$product_feature->Значение;
					$feature_name_obj[]	= $option_value;
					$this->log("> прочитана характеристика, Ид: '" . $feature_guid . "', значение: '" . $option_value . "'");

					// Ищем опцию и значение опции
					$option_id 			= $this->setOption("Характеристика");
					$option_value_id 	= $this->setOptionValue($option_id, $option_value);

                    $options = array();
					$options[$option_value_id] = array(
						'option_id'			=> $option_id,
						'subtract'			=> $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0,
						'quantity'			=> 0
					);
					$features[$feature_guid] = array(
						'name'			=> $feature_name,
						'options'		=> $options
					);
				}

			} elseif ($this->config->get('exchange1c_product_options_mode') == 'feature') {

				// РЕЖИМ "ХАРАКТЕРИСТИКА"
				$option_name_obj = array();
				$option_value_obj = array();

				foreach ($xml->ХарактеристикаТовара as $feature_option) {
					// разбиваем название опции
					$option_name_split = $this->splitNameStr(htmlspecialchars(trim((string)$feature_option->Наименование)));
					$option_name_obj[] = $option_name_split['name'];
					$option_value_split = $this->splitNameStr(htmlspecialchars(trim((string)$feature_option->Значение)));
					$option_value_obj[] = $option_value_split['name'];
				}
				$option_name = implode(", ", $option_name_obj);
				$option_value = implode(", ", $option_value_obj);

				$option_id 			= $this->setOption($option_name);
				$option_value_id 	= $this->setOptionValue($option_id, $option_value);
				$options[$option_value_id]	= array(
					'option_id'			=> $option_id,
					'subtract'			=> $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0,
					'quantity'			=> $quantity
				);
				$features[$data['feature_guid']]['options'] = $options;
				$features[$data['feature_guid']]['name'] = $option_value;
				$this->log("Опция: '" . $option_name . "' = '" . $option_value . "'");

			} elseif ($this->config->get('exchange1c_product_options_mode') == 'certine') {
				// Отдельные товары
				// НЕ РЕАЛИЗОВАННО
				$this->log("Режим характеристики как отдельный товар пока не реализован", 2);

			} elseif ($this->config->get('exchange1c_product_options_mode') == 'related') {
				// РЕЖИМ - СВЯЗАННЫЕ ОПЦИИ
				foreach ($xml->ХарактеристикаТовара as $feature_option) {
					// ЗНАЧЕНИЕ ОПЦИИ
					$value_obj = $this->splitNameStr((string)$feature_option->Значение);
					$image	= '';

					// ОПЦИЯ
					$option_obj = $this->splitNameStr((string)$feature_option->Наименование);

					// Тип по-умолчанию, если не будет переопределен
					$option_type = "select";
					switch($option_obj['option']) {
						case 'select':
							$option_type 	= 'select';
							break;
						case 'radio':
							$option_type 	= 'radio';
							break;
						case 'checkbox':
							$option_type 	= 'checkbox';
							break;
						case 'image':
							$option_type 	= 'image';
							$image			= $value_obj['option'] ? "options/" . $value_obj['option'] : "";
							break;
						default:
							$option_type 	= "select";
					}

					$option_id			= $this->setOption($option_obj['name'], $option_type, $option_obj['order']);
					$option_value_id    = $this->setOptionValue($option_id, $value_obj['name'], $value_obj['order'], $image);

					$options[$option_value_id] = array(
						'option_guid'			=> $feature_option->Ид ? (string)$feature_option->Ид : '',
						'subtract'				=> $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0,
						'option_id'				=> $option_id,
						'type'					=> $option_type,
						'quantity'				=> $quantity
					);
					$this->log("Опция: '" . $option_obj['name'] . "' = '" . $value_obj['name'] . "'");
				}
				$features[$data['feature_guid']]['options'] = $options;
			}

		} else {

			// нет секции характеристика (XML 2.07, УТ 11.3)
			// нет секции характеристика (XML 2.03, 2.04 УТ для Украины)
			$this->log("> нет секции <ХарактеристикаТовара> - обрабатываем как обычный товар", 2);
		}

		return $features;

	} // parseFeature()


	/**
	 * ver 6
	 * update 2017-05-04
	 * Разбор предложений
	 */
	private function parseOffers($xml) {

		if (!$xml->Предложение) {
			$this->log("[!] Пустое предложение, пропущено");
			return true;
		}

		foreach ($xml->Предложение as $offer) {

			// Массив для хранения данных об одном предложении товара
			$data = array();

			// Получаем Ид товара и характеристики
			$guid 					= explode("#", (string)$offer->Ид);
			$data['product_guid']	= $guid[0];
			$data['feature_guid'] 	= isset($guid[1]) ? $guid[1] : '';

			$data['product_id'] = 0;

			$this->log("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -", 2);
			// Если указан код, ищем товар по коду
			if ($this->config->get('exchange1c_synchronize_by_code') == 1 && $offer->Код) {
				$code	= (int)$offer->Код;
				$this->log("> Синхронизация товара по коду: " . $code, 2);
				$data['product_id'] = $this->getProductIdByCode($code);
			}

			// Пустые предложения игнорируются
			if (!$data['product_id']) {
				if (empty($data['product_guid'])) {
					// Проверка на пустое предложение
					$this->log("[!] Ид товара пустое, предложение игнорируется!", 2);
					continue;
				}
				// Читаем product_id, если нет товара выходим с ошибкой, значит что-то не так
				$data['product_id'] = $this->getProductIdByGuid($data['product_guid']);
			}

			// Нет товара, просто пропускаем
			if (!$data['product_id']) {
				$this->log("parseOffers() - Не найден товар в базе по Ид: '" . $data['product_guid'] . "'", 2);
				continue;
			}

			if ($offer->Наименование) {
				$data['feature_name'] = (string)$offer->Наименование;
			}

			$this->log("ПРЕДЛОЖЕНИЕ ТОВАРА ИД: " . $data['product_guid'] . ", product_id = " . $data['product_id'], 2);
			if ($data['feature_guid']) {
				$this->log("ХАРАКТЕРИСТИКА ИД: " . $data['feature_guid'], 2);
			}

			// Базовая единица измерения товара или характеристики
			if ($offer->БазоваяЕдиница) {
				$data['unit'] = $this->parseProductUnit($offer->БазоваяЕдиница);
			}

			// Штрихкод товара или характеристики
			if ($offer->Штрихкод) {
				$data['ean'] = (string)$offer->Штрихкод;
			}

			// По-умолчанию статус включаем, дальше по коду будет только отключение.
			$data['status'] = 1;

			// ОСТАТКИ (offers, rests)
			if ($offer->Склад) {
				// Остатки характеристики по складам
				$result = $this->parseQuantity($offer, $data);
				if ($this->ERROR) return false;
				if (count($result)) $data['quantities'] = $result;

			} elseif ($offer->Остатки) {
				// остатки характеристики (CML >= 2.09) файл rests_*.xml
				$result = $this->parseQuantity($offer->Остатки, $data, $error);
				if ($this->ERROR) return false;
				if (count($result)) $data['quantities'] = $result;

			} else {
				// Нет складов
				// Общий остаток предложения по всем складам
				if ($offer->Количество) {
					$data['quantities'][0] = (float)$offer->Количество;
				}
			}

			// Есть характеристики
			if ($offer->ХарактеристикиТовара) {
				$result = $this->parseFeatures($offer->ХарактеристикиТовара, $data);
				if ($this->ERROR) return false;
				if (count($result)) {
					if ($data['feature_guid']) {
						// Когда предложение является одной характеристикой
						$data['options'] = $result[$data['feature_guid']]['options'];
					} else {
						// Когда в предложении несколько характеристик
						$data['features'] = $result;
					}
				}
			}

			// Цены товара или характеристики (offers*.xml, prices*.xml)
			if ($offer->Цены) {
				$result = $this->parsePrice($offer->Цены, $data);
				if ($this->ERROR) return false;
				if (count($result)) $data['prices'] = $result;
			}

			unset($result);

			// Обновляем товар
			$this->updateProduct($data);
			if ($this->ERROR) return false;

			unset($data);
			if (file_exists(DIR_CACHE . 'exchange1c/break')) {
				$this->ERROR = "parseOffers() - остановлен по наличию файла break";
				return false;
			}
		} // foreach()

		return true;

	} // parseOffers()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Проверяет на наличие полной выгрузки в каталоге или в предложениях
	 */
	private function checkFullImport($xml) {

		if ((string)$xml['СодержитТолькоИзменения'] == "false") {
			$this->FULL_IMPORT = true;
			$this->log("[!] Загрузка полная...");
		} else {
			if ((string)$xml->СодержитТолькоИзменения == "false") {
				$this->FULL_IMPORT = true;
			} else {
				$this->log("[!] Загрузка только изменений...");
			}
		}

	} // checkFullImport()

	/**
	 * ver 3
	 * update 2017-04-26
	 * Загружает пакет предложений
	 */
	private function parseOffersPack($xml) {

		$offers_pack = array();
		$offers_pack['offers_pack_id']	= (string)$xml->Ид;
		$offers_pack['name']			= (string)$xml->Наименование;
		$offers_pack['directory_id']	= (string)$xml->ИдКаталога;
		$offers_pack['classifier_id']	= (string)$xml->ИдКлассификатора;

		$this->checkFullImport($xml);

		// Сопоставленные типы цен
		if ($xml->ТипыЦен) {
			$offers_pack['price_types'] = $this->parseClassifierPriceType($xml->ТипыЦен);
			if ($this->ERROR) return false;
		}

		// Загрузка складов
		if ($xml->Склады) {
			$offers_pack['warehouses'] = $this->parseClassifierWarehouses($xml->Склады);
			if ($this->ERROR) return false;
		}

		// Загружаем предложения
		if ($xml->Предложения) {
			$this->parseOffers($xml->Предложения, $offers_pack);
			if ($this->ERROR) return false;
		}

		return true;

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
	private function sendMail($subject, $message, $order_info) {

		$this->log("==> sendMail()",2);

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
		//$mail->send();
		$this->log($mail, 2);

	} // sendMail()


	/**
	 * ver 2
	 * updare 2017-05-08
	 * Меняет статусы заказов
	 *
	 * @param	int		exchange_status
	 * @return	bool
	 */
	public function queryOrdersStatus($params) {
		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status']);
			$this->log("> Поиск заказов со статусом id: " . $params['exchange_status'],2);
		} else {
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
			$this->log("> Поиск заказов с даты: " . $params['from_date'],2);
		}
		if ($query->num_rows) {

			$this->NOW = date('Y-m-d H:i:s');

			foreach ($query->rows as $order_data) {

				if ($order_data['order_status_id'] == $params['new_status']) {
					$this->log("> Cтатус заказа #" . $order_data['order_id'] . " не менялся.",2);
					continue;
				}
				// Если статус новый пустой, тогда не меняем, чтобы не породить ошибку
				if (!$params['new_status']) {
					continue;
				}

				// Меняем статус
				$sql = "UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = '" . $params['new_status'] . "' WHERE `order_id` = '" . $order_data['order_id'] . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Изменен статус заказа #" . $order_data['order_id'],1);
				// Добавляем историю в заказ
				$sql = "INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . $order_data['order_id'] . "', `comment` = 'Ваш заказ обрабатывается', `order_status_id` = " . $params['new_status'] . ", `notify` = 0, `date_added` = '" . $this->NOW . "'";
				$this->log($sql,2);
				$query = $this->db->query($sql);
				$this->log("> Добавлена история в заказ #" . $order_data['order_id'],2);

				// Уведомление
				if ($params['notify']) {
					$this->log("> Отправка уведомления на почту: " . $order_data['email'],2);
					$this->sendMail('Заказ обновлен', 'Статус Вашего заказа изменен', $order_data);
				}
			}
		}
		return 1;
	}


	/**
	 * Получает название статуса документа на текущем языке
	 *
	 */
	private function getOrderStatusName($order_staus_id) {
		if (!$this->LANG_ID) {
			$this->LANG_ID = $this->getLanguageId($this->config->get('config_language'));
		}
		$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "order_status` WHERE `order_status_id` = " . $order_staus_id . " AND `language_id` = " . $this->LANG_ID);
		if ($query->num_rows) {
			return $query->row['name'];
		}
		return "";
	} // getOrderStatusName()


	/**
	 * Получает название цены из настроек по группе покупателя
	 *
	 */
	private function getPriceTypeName($customer_group_id) {

		if (!$customer_group_id)
			return "";

		$config_price_type = $this->config->get('exchange1c_price_type');
		if (!$config_price_type)
			return "";

		foreach ($config_price_type as $price_type) {
			if ($price_type['customer_group_id'] == $customer_group_id)
				return $price_type['keyword'];
		}

		return "";

	} // getPriceTypeName()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Получает GUID характеристики по выбранным опциям
	 */
	private function getFeatureGUID($product_id, $order_id) {

		$order_options = $this->model_sale_order->getOrderOptions($order_id, $product_id);
		$options = array();
		foreach ($order_options as $order_option) {
			$options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
		}

		$product_feature_id = 0;
		foreach ($order_options as $order_option) {
			$sql = "SELECT `product_feature_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_option_value_id` = " . (int)$order_option['product_option_value_id'];
			$this->log($sql,2);
			$query = $this->db->query($sql);

			if ($query->num_rows) {
				if ($product_feature_id) {
					if ($product_feature_id != $query->row['product_feature_id']) {
						$this->ERROR = "[ОШИБКА] По опциям товара найдено несколько характеристик!";
						return false;
					}
				} else {
					$product_feature_id = $query->row['product_feature_id'];
				}
			}
		}

		$feature_guid = "";
		if ($product_feature_id) {
			// Получаем Ид
			$sql = "SELECT `guid` FROM `" . DB_PREFIX . "product_feature` WHERE `product_feature_id` = " . (int)$product_feature_id;
			$this->log($sql,2);
			$query = $this->db->query($sql);
			if ($query->num_rows) {
				$feature_guid = $query->row['guid'];
			}
			$features[$product_feature_id] = $feature_guid;
		}

		return $feature_guid;

	} // getFeatureGUID


	/**
	 * ver 2
	 * update 2017-05-05
	 * ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ ******************************
	 */
	public function queryOrders($params) {

		$this->log("==== Выгрузка заказов ====",2);

		$version = $this->config->get('exchange1c_CMS_version');
		$this->log("customer/customer_group",2);
		$this->load->model('customer/customer_group');

		$this->load->model('sale/order');
		$order_export = array();

//		// Выгрузка заказов по статусам
//		if ($params['exchange_status'] != 0) {
//			// Если указано с каким статусом выгружать заказы
//			$query = $this->query("SELECT `order_id`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status']);
//		} else {
//			// Иначе выгружаем заказы с последей выгрузки, если не определа то все
//			$query = $this->query("SELECT `order_id`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
//		}
//		if ($query->num_rows) {
//			foreach ($query->rows as $row) {
//				$order_export[$row['order_id']] = 1;
//			}
//		}

		// Получим статусы заказов которые будем выгружать
		$config_order_export = $this->config->get('exchange1c_order_export');
		$export_order_statuses = array();
		$order_statuses = array();
		if (is_array($config_order_export)) {
			foreach($config_order_export as $export) {
				$export_order_statuses[$export['order_status']] = array(
					'notify'	=> isset($export['notify']) ? true : false,
					'subject'	=> $export['subject'],
					'text'		=> $export['text']
				);
				$order_statuses[] = $export['order_status'];
			}
		}
		$where_order_statuses = count($order_statuses) ? " AND `order_status_id` IN (" . implode(",",$order_statuses) . ")" : "";

		// Выгрузка измененных заказов
		if ($this->config->get('exchange1c_order_modify_exchange') ==  1) {
			$query = $this->query("SELECT `order_id`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE DATE(`date_modified`) >= DATE('" . $this->config->get('exchange1c_order_date') . "')" . $where_order_statuses);
		}
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$order_export[$row['order_id']] = $row['order_status_id'];
			}
		}

		$document = array();
		$document_counter = 0;

		if (count($order_export)) {
			foreach ($order_export as $order_id => $order_status_id) {
				// $order_status_id - пока не используется
				$order = $this->model_sale_order->getOrder($order_id);
				$this->log("> Выгружается заказ #" . $order['order_id']);
				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));
				$customer_group = $this->model_customer_customer_group->getCustomerGroup($order['customer_group_id']);
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
				//array_unshift($user, $order['payment_patronymic']);
				$username = implode(" ", $user);

				// Контрагент
				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
					 'Ид'                 => $order['customer_id'] . '#' . $order['email']
					//,'РасчетныеСчета'		=> array(
					//	'НомерСчета'			=> '12345678901234567890'
					//	,'Банк'					=> ''
					//	,'БанкКорреспондент'	=> ''
					//	,'Комментарий'			=> ''
					//)
					//---
					,'Роль'               => 'Покупатель'
					,'ПолноеНаименование' => $username
					,'Фамилия'            => $order['payment_lastname']
					,'Имя'			      => $order['payment_firstname']
					,'Отчество'		      => $order['payment_patronymic']
					,'АдресРегистрации' => array(
						//'Представление'	=> $order['shipping_address_1'].', '.$order['shipping_city'].', '.$order['shipping_postcode'].', '.$order['shipping_country']
						// Посоветовал yuriygr c GitHub
						'Представление'	=> $order['shipping_postcode'] . ', ' . $order['shipping_zone'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_address_1'] . ', '.$order['shipping_address_2']
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

				// если плательщиком является организация
				$current_customer = &$document['Документ' . $document_counter]['Контрагенты']['Контрагент'];
				if ($order['payment_company']) {
					$current_customer['ИНН'] 						= $order['payment_inn'];
					$current_customer['ОфициальноеНаименование'] 	= $order['payment_company'];
					$current_customer['ПолноеНаименование'] 		= $order['payment_company'];
					//$current_customer['ОКПО'] 					= $order['payment_okpo'];
				} elseif ($order['shipping_company']) {
					$current_customer['ИНН'] 						= $order['shipping_inn'];
					$current_customer['ОфициальноеНаименование'] 	= $order['shipping_company'];
					$current_customer['ПолноеНаименование'] 		= $order['shipping_company'];
					//$current_customer['ОКПО'] 					= $order['shipping_okpo'];
				} else {

					$current_customer['Наименование'] = $username;
				}

    				// Реквизиты документа передаваемые в 1С
				$document['Документ' . $document_counter]['ЗначенияРеквизитов'] = array(
					'ЗначениеРеквизита0' => array(
						'Наименование' => 'Дата отгрузки',
						'Значение' => $date
					)
					,'ЗначениеРеквизита1' => array(
						'Наименование' => 'Статус заказа',
						'Значение' => $this->getOrderStatusName($order['order_status_id'])
					)
					,'ЗначениеРеквизита2' => array(
						'Наименование' => 'Вид цен',
						'Значение' => $this->getPriceTypeName($order['customer_group_id'])
					)
					,'ЗначениеРеквизита3' => array(
						'Наименование' => 'Контрагент',
						'Значение' => $username
					)
//					,'ЗначениеРеквизита3' => array(
//						'Наименование' => 'Склад',
//						'Значение' => $this->getWarehouseName($order['warehouse_id']);
//					)
//					,'ЗначениеРеквизита4' => array(
//						'Наименование' => 'Организация',
//						'Значение' => $this->getOrganizationName($order['organization_id']);
//					)
//					,'ЗначениеРеквизита5' => array(
//						'Наименование' => 'Подразделение',
//						'Значение' => 'Интернет-магазин'
//					)
//					,'ЗначениеРеквизита6' => array(
//						'Наименование' => 'Сумма включает НДС',
//						'Значение' => true
//					)
//					,'ЗначениеРеквизита7' => array(
//						'Наименование' => 'Договор контрагента',
//						'Значение' => 'Основной договор'
//					)
					//(Розница ищет по наименованию название магазина)
//					,'ЗначениеРеквизита8' => array(
//						'Наименование' => 'ТочкаСамовывоза',
//						'Значение' => 'Название магазина'
//					)
//Розница: "ВидЦенНаименование", "СуммаВключаетНДС", "НаименованиеСкидки", "ПроцентСкидки", "СуммаСкидки", "СкладНаименование", "ПодразделениеНаименование", "Склад", "Контрагент"
				);

				// Товары
				$products = $this->model_sale_order->getOrderProducts($order_id);

				$product_counter = 0;
				foreach ($products as $product) {
					$product_guid = $this->getGuidByProductId($product['product_id']);
					$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
						 'Ид'             => $product_guid
						,'Наименование'   => $product['name']
						,'ЦенаЗаЕдиницу'  => $product['price']
						,'Количество'     => $product['quantity']
						,'Сумма'          => $product['total']
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
					$current_product = &$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter];
					// Резервирование товаров
					if ($this->config->get('exchange1c_order_reserve_product') == 1) {
						$current_product['Резерв'] = $product['quantity'];
					}
					// Базовая единица будет выгружаться из таблицы product_unit
					$current_product['БазоваяЕдиница'] = array(
						'Код' 					=> '796',
						'НаименованиеПолное' 	=> 'Штука'
					);

					// Характеристики
					$feature_guid = $this->getFeatureGUID($product['order_product_id'], $order_id);
					if ($feature_guid) {
						$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter]['Ид'] .= "#" . $feature_guid;
					}

					$product_counter++;
				}

				$document_counter++;

				// Уведомление
				if ($export_order_statuses[$order['order_status_id']]['notify']) {
					$this->sendMail($export_order_statuses[$order['order_status_id']]['subject'], $export_order_statuses[$order['order_status_id']]['text'], $order);
				}

			} // foreach ($query->rows as $orders_data)
		}

		// Формируем заголовок
		$root = '<?xml version="1.0" encoding="utf-8"?><КоммерческаяИнформация ВерсияСхемы="2.07" ДатаФормирования="' . date('Y-m-d', time()) . '" />';

		$root_xml = new SimpleXMLElement($root);
		$xml = $this->array_to_xml($document, $root_xml);

		// Проверка на запись файлов в кэш
		$cache = DIR_CACHE . 'exchange1c/';
		if (@is_writable($cache)) {
			// запись заказа в файл
			$f_order = @fopen($cache . 'orders.xml', 'w');
			if (!$f_order) {
				$this->log("Нет доступа для записи в папку: " . $cache);
			} else {
				fwrite($f_order, $xml->asXML());
				fclose($f_order);
			}
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
	 * Возвращает курс валюты
	 */
	private function getCurrencyValue($code) {
		$query = $this->query("SELECT `value` FROM `" . DB_PREFIX . "currency` WHERE `code` = '" . $code . "'");
		if ($query->num_rows) {
			return $query->row['value'];
		}
		return 1;
	} // getCurrencyValue()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Возвращает валюту по коду
	 * Это временное решение
	 */
	private function getCurrencyByCode($code) {

		$data = array();
		if ($code == "643") {
			$data['currency_id'] = $this->getCurrencyId("RUB");
			$data['currency_code'] = "RUB";
			$data['currency_value'] = $this->getCurrencyValue("RUB");
		}
		return $data;

	} // getCurrencyByCode()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Устанавливает опции заказа в товаре
	 */
	private function setOrderProductOptions($order_id, $product_id, $order_product_id, $product_feature_id = 0) {

		// удалим на всякий случай если были
		$this->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE `order_product_id` = " . $order_product_id);

		// если есть, добавим
		if ($product_feature_id) {
			$query_feature = $this->query("SELECT `pfv`.`product_option_value_id`,`pf`.`name` FROM `" . DB_PREFIX . "product_feature_value` `pfv` LEFT JOIN `" . DB_PREFIX . "product_feature` `pf` ON (`pfv`.`product_feature_id` = `pf`.`product_feature_id`) WHERE `pfv`.`product_feature_id` = " . $product_feature_id . " AND `pfv`.`product_id` = " . $product_id);
			$this->log($query_feature,2);
			foreach ($query_feature->rows as $row_feature) {
				$query_options = $this->query("SELECT `pov`.`product_option_id`,`pov`.`product_option_value_id`,`po`.`value`,`o`.`type` FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "product_option` `po` ON (`pov`.`product_option_id` = `po`.`product_option_id`) LEFT JOIN `" . DB_PREFIX . "option` `o` ON (`o`.`option_id` = `pov`.`option_id`) WHERE `pov`.`product_option_value_id` = " . $row_feature['product_option_value_id']);
				$this->log($query_options,2);
				foreach ($query_options->rows as $row_option) {
					$this->query("INSERT INTO `" . DB_PREFIX . "order_option` SET `order_id` = " . $order_id . ", `order_product_id` = " . $order_product_id . ", `product_option_id` = " . $row_option['product_option_id'] . ", `product_option_value_id` = " . $row_option['product_option_value_id'] . ", `name` = '" . $this->db->escape($row_option['value']) . "', `value` = '" . $this->db->escape($row_feature['name']) . "', `type` = '" . $row_option['type'] . "'");
					$order_option_id = $this->db->getLastId();
					$this->log("order_option_id: ".$order_option_id,2);
				}
			}
		}
		$this->log("Записаны опции в заказ",2);

	} // setOrderProductOptions()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Добавляет товар в заказ
	 */
	private function addOrderProduct($order_id, $product_id, $price, $quantity, $total, $tax = 0, $reward = 0) {

		$query = $this->query("SELECT `pd`.`name`,`p`.`model` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `p`.`product_id` = " . $product_id);
		if ($query->num_rows) {
			$name = $query->row['name'];
			$model = $query->row['model'];

			$sql = "";
			$sql .= ($tax) ? ", `tax` = " . $tax : "";
			$sql .= ($reward) ? ", `reward` = " . $reward : "";
			$this->query("INSERT INTO `" . DB_PREFIX . "order_product` SET `product_id` = " . $product_id . ",
				`order_id` = " . $order_id . ",
				`name` = '" . $this->db->escape($name) . "',
				`model` = '" . $this->db->escape($model) . "',
				`price` = " . $price . ",
				`quantity` = " . $quantity . ",
				`total` = " . $total . $sql);
			return $this->db->getLastId();
		}
		return 0;
		$this->log("Записаны товары в заказ",2);

	} // addOrderProduct()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Удаляем товар из заказа со всеми опциями
	 */
	private function deleteOrderProduct($order_product_id) {

		$this->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE `order_product_id` = " . $order_product_id);
		$this->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE `order_product_id` = " . $order_product_id);
		$this->log("Удалены товары и опции в заказе",2);

	} // deleteOrderProduct()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Меняет статус заказа
	 */
	private function getOrderStatusLast($order_id) {

		$order_status_id = 0;
		$query = $this->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_history` WHERE `order_id` = " . $order_id . " ORDER BY `date_added` DESC LIMIT 1");
		if ($query->num_rows) {
			$this->log("<== getOrderStatusLast() return: " . $query->row['order_status_id'],2);
			$order_status_id = $query->row['order_status_id'];
		}
		$this->log("Получен статус заказа = " . $order_status_id, 2);
		return $order_status_id;
	}


	/**
	 * ver 2
	 * update 2017-04-05
	 * Меняет статус заказа
	 */
	private function changeOrderStatus($order_id, $status_name) {

		$query = $this->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_status` WHERE `language_id` = " . $this->LANG_ID . " AND `name` = '" . $this->db->escape($status_name) . "'");
		if ($query->num_rows) {
			$new_order_status_id = $query->row['order_status_id'];
		} else {
			$this->ERROR = "changeOrderStatus() - Статус с названием '" . $status_name . "' не найден";
			return false;
 		}
		$this->log("[i] Статус id у названия '" . $status_name . "' определен как " . $new_order_status_id,2);

		// получим старый статус
		$order_status_id = $this->getOrderStatusLast($order_id);
		if (!$order_status_id) {
			$this->ERROR = "changeOrderStatus() - Ошибка получения старого статуса документа!";
			return 0;
		}

		if ($order_status_id == $new_order_status_id) {
			$this->log("Статус документа не изменился", 2);
			return true;
		}

		// если он изменился, изменим в заказе
		$this->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = " . $order_id . ", `order_status_id` = " . $new_order_status_id . ", `date_added` = '" . $this->NOW . "'");

		$this->log("Изменен статус документа",2);
		return true;

	} // changeOrderStatus()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Обновляет документ
	 */
	private function updateDocument($doc, $order, $products) {

		$order_fields = array();

		// обновим входящий номер
		if (!empty($doc['invoice_no'])) {
			$order_fields['invoice_no'] = $doc['invoice_no'];
		}

		// проверим валюту
		if (!empty($doc['currency'])) {
			$currency = $this->getCurrencyByCode($doc['currency']);
			$order_fields['currency_id'] = $currency['currency_id'];
			$order_fields['currency_code'] = $currency['currency_code'];
			$order_fields['currency_value'] = $currency['currency_value'];
		}

		// проверим сумму
		if (!empty($doc['total'])) {
			if ($doc['total'] != $order['total']) {
				$order_fields['total'] = $doc['total'];
			}
		}

		// статус заказа
		if (!empty($doc['status'])) {
			$this->changeOrderStatus($doc['order_id'], $doc['status']);
			if ($this->ERROR) return false;
		}

		$old_products = $products;

		// проверим товары, порядок должен быть такой же как и в 1С
		if (!empty($doc['products'])) {
			foreach ($doc['products'] as $key => $doc_product) {

            	$this->log("Товар: ".$doc_product['name'],2);

				$order_product_fields = array();
				$order_option_fields = array();
				$update = false;
				$product_feature_id = isset($doc_product['product_feature_id']) ? $doc_product['product_feature_id'] : 0;

				if (isset($products[$key])) {
					$product = $products[$key];
					$order_product_id = $product['order_product_id'];

					unset($old_products[$key]);

					// получим характеристику товара в заказе
					$old_feature_guid = $this->getFeatureGuid($doc['order_id'], $order_product_id);
					$this->log("old_feature_guid: " . $old_feature_guid,2);
					$this->log("new_feature_guid: " . $doc_product['product_feature_guid'],2);

					// сравним
					if ($doc_product['product_id'] == $product['product_id']) {
						$update = true;
						if ($old_feature_guid != $doc_product['product_feature_guid']) {
							// изменить характеристику
							$this->setOrderProductOptions($doc['order_id'], $doc_product['product_id'], $order_product_id, $product_feature_id);
							if ($this->ERROR) return false;
						}
						// обновим если менялось количество или цена
						if ($product['quantity'] != $doc_product['quantity'] || $product['price'] != $doc_product['price']) {
							$order_product_fields[] = "`quantity` = " . $doc_product['quantity'];
							$order_product_fields[] = "`price` = " . $doc_product['price'];
							$order_product_fields[] = "`total` = " . $doc_product['total'];
							//$order_product_fields[] = "`tax` = " . $doc_product['tax'];
							//$order_product_fields[] = "`reward` = " . $doc_product['reward'];
						}
					} else {
						// товар отличается, заменить полностью
						$order_product_fields[] = "`product_id` = " . $doc_product['product_id'];
						$order_product_fields[] = "`name` = '" . $this->db->escape($doc_product['product_id']) . "'";
						$order_product_fields[] = "`model` = '" . $this->db->escape($doc_product['model']) . "'";
						$order_product_fields[] = "`price` = " . $doc_product['price'];
						$order_product_fields[] = "`quantity` = " . $doc_product['quantity'];
						$order_product_fields[] = "`total` = " . $doc_product['total'];
						$order_product_fields[] = "`tax` = " . $doc_product['tax'];
						// бонусные баллы
						$order_product_fields[] = "`reward` = " . $doc_product['reward'];

						// заменить опции, если есть
						// считать опции с характеристики и записать в заказ у товара $order_product_id
						$this->setOrderProductOptions($doc['order_id'], $doc_product['product_id'], $order_product_id, $product_feature_id);
						if ($this->ERROR) return false;

					} // if
				} else {
					// Добавить товар в документ
					$order_product_id = $this->addOrderProduct($doc['order_id'], $doc_product['product_id'], $doc_product['price'], $doc_product['quantity'], $doc_product['total']);
					if ($order_product_id && $product_feature_id) {
						// добавлен товар и есть опции
						$this->setOrderProductOptions($doc['order_id'], $doc_product['product_id'], $order_product_id, $product_feature_id);
						if ($this->ERROR) return false;
					}

				}// if (isset($products[$key]))
				$this->log("update: ".$update,2);
				$this->log("fields: ",2);
				$this->log($order_product_fields,2);
				// если надо обновить поля товара в заказе
				if ($order_product_fields) {
					$fields = implode(", ", $order_product_fields);
					if ($update) {
						$this->query("UPDATE `" . DB_PREFIX . "order_product` SET " . $fields . " WHERE `order_product_id` = " . $products[$key]['order_product_id']);
						$this->log("Товар '" . $doc_product['name'] . "' обновлен в заказе",2);
					} else {

					}
				} else {
					$this->log("Товар '" . $doc_product['name'] . "' в заказе не изменился",2);
				}
			} // foreach

			foreach ($old_products as $product) {
				$this->deleteOrderProduct($product['order_product_id']);
				if ($this->ERROR) return false;
			}
		} // if

		$this->log("Докумен обновлен",2);
		return "";

	} // updateDocument()


	/**
	 * ver 2
	 * update
	 * Читает их XML реквизиты документа
	 */
	private function parseDocumentRequisite($xml, &$doc) {

		foreach ($xml->ЗначениеРеквизита as $requisite) {
			// обрабатываем только товары
			$name 	= (string)$requisite->Наименование;
			$value 	= (string)$requisite->Значение;
			$this->log("> Реквизит документа: " . $name. " = " . $value,2);
			switch ($name){
				case 'Номер по 1С':
					$doc['invoice_no'] = $value;
				break;
				case 'Дата по 1С':
					$doc['datetime'] = $value;
				break;
				case 'Проведен':
					$doc['posted'] = $value;
				break;
				case 'Статус заказа':
					$doc['status'] = $value;
				break;
				case 'Номер оплаты по 1С':
					$doc['NumPay'] = $value;
				break;
				case 'Дата оплаты по 1С':
					$doc['DataPay'] = $value;
				break;
				case 'Номер отгрузки по 1С':
					$doc['NumSale'] = $value;
				break;
				case 'Дата отгрузки по 1С':
					$doc['DateSale'] = $value;
				break;
				case 'ПометкаУдаления':
					$doc['DeletionMark'] = $value;
				break;
				case 'Проведен':
					$doc['Posted'] = $value;
				break;
				default:
			}
		}
		$this->log("Реквизиты документа прочитаны",2);

	} // parseDocumentRequisite()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Контрагент
	 * Получает ID покупателя и адреса
	 */
	private function parseDocumentCustomer($xml, &$doc) {

		if (!$xml) {
			$this->ERROR = "parseDocumentCustomer() - Нет данных в XML";
			return false;
		}

		$doc['customer_id']	= 0;
		$doc['address_id']	= 0;

		$customer_name	= (string)$xml->Контрагент->Наименование;
		$customer_name_split	= explode(" ", $customer_name);
		//$this->log($customer_name_split,2);
		$lastname				= isset($customer_name_split[0]) ? $customer_name_split[0] : "";
		$firstname				= isset($customer_name_split[1]) ? $customer_name_split[1] : "";

		// поиск покупателя по имени получателя
		if (!$doc['customer_id']) {
			$query = $this->query("SELECT `address_id`,`customer_id` FROM `" . DB_PREFIX . "address` WHERE `firstname` = '" . $this->db->escape($firstname) . "' AND `lastname` = '" . $this->db->escape($lastname) . "'");
			if ($query->num_rows) {
				$doc['customer_id'] = $query->row['customer_id'];
				$doc['address_id'] = $query->row['address_id'];
			}
		}

		// поиск покупателя по имени
		if (!$doc['customer_id']) {
			$query = $this->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE `firstname` = '" . $this->db->escape($firstname) . "' AND `lastname` = '" . $this->db->escape($lastname) . "'");
			if ($query->num_rows) {
				$doc['customer_id'] = $query->row['customer_id'];
			}
		}

		if (!$doc['customer_id']) {
			$this->ERROR = "parseDocumentCustomer() - Покупатель '" . $customer_name . "' не найден в базе";
			return false;
		}

		$this->log("Покупатель в документе прочитан",2);
		return true;

	} // parseDocumentCustomer()


	/**
	 * ver 3
	 * update 2017-04-16
	 * Товары документа
	 */
	private function parseDocumentProducts($xml, &$doc) {

		if (!$xml) {
			$this->ERROR = "parseDocumentProducts() - Нет данных в XML";
			return false;
		}

		foreach ($xml->Товар as $product) {
			$guid		= explode("#", (string)$product->Ид);
			if (!$guid) {
				$this->ERROR = "parseDocumentProducts - не определен GUID товара";
				return false;
			}

			$data = array();

			if ($product->Наименование) {
				$data['name'] = (string)$product->Наименование;
			}

			if (isset($guid[0])) {
				$data['product_guid'] = $guid[0];
				$data['product_id'] = $this->getProductIdByGuid($data['product_guid']);
				if (!$data['product_id'])
					$this->ERROR = "parseDocumentProducts - Товар '" . $data['name'] . "' не найден в базе по Ид '" . $data['product_guid'] . "'";
					return false;
			} else {
				$this->ERROR = "parseDocumentProducts - Товар '" . $data['name'] . "' не может быть найден в базе по пустому Ид";
				return false;
			}

			if (isset($guid[1])) {
				$data['product_feature_guid'] = $guid[1];
				$data['product_feature_id'] = $this->getProductFeatureIdByGuid($data['product_feature_guid']);
				if (!$data['product_feature_id'])
					$this->ERROR = "parseDocumentProducts - Характеристика товара '" . $data['name'] . "' не найдена в базе по Ид '" . $data['product_feature_guid'] . "'";
					return false;
			} else {
				$data['product_feature_id'] = 0;
			}

			if ($product->Артикул) {
				$data['sku'] = (string)$product->Артикул;
				$data['model'] = (string)$product->Артикул;
			}
			if ($product->БазоваяЕдиница) {
				$data['unit0'] = array(
					'code'		=> $product->БазоваяЕдиница->Наименование['Код'],
					'name'		=> $product->БазоваяЕдиница->Наименование['НаименованиеПолное'],
					'eng'		=> $product->БазоваяЕдиница->Наименование['МеждународноеСокращение']
				);
			}
			if ($product->ЦенаЗаЕдиницу) {
				$data['price'] = (float)$product->ЦенаЗаЕдиницу;
			}
			if ($product->Количество) {
				$data['quantity'] = (float)$product->Количество;
			}
			if ($product->Сумма) {
				$data['total'] = (float)$product->Сумма;
				// налог временно нулевой
				$data['tax'] = 0;
			}
			if ($product->Единица) {
				$data['unit'] = array(
					'unit_id'	=> $this->getUnitId((string)$product->Единица),
					'ratio'		=> (string)$product->Коэффициент
				);

			}

			$doc['products'][] = $data;
		}

		$this->log("Товары документа прочитаны", 2);
		return true;

	} // parseDocumentProducts()


	/**
	 * ver 2
	 * update 2017-04-26
	 * Разбор классификатора
	 */
	private function parseClassifier($xml) {

		$data = array();
		$data['guid']			= (string)$xml->Ид;
		$data['name']			= (string)$xml->Наименование;
		$this->setStore($data['name']);

		// Организация
		if ($xml->Владелец) {
			$this->log("Организация", 2);
			$data['owner']			= $this->parseOwner($xml->Владелец);
			unset($xml->Владелец);
		}

		if ($xml->ТипыЦен) {
			$this->log("Типы цен с классификатора (CML >= v2.09)", 2);
			$data['price_types'] = $this->parseClassifierPriceType($xml->ТипыЦен);
			if ($this->ERROR) return false;
			unset($xml->ТипыЦен);
		}

		if ($xml->Склады) {
			$this->log("Склады из классификатора (CML >= v2.09)", 2);
			$this->parseClassifierWarehouses($xml->Склады);
			if ($this->ERROR) return false;
			unset($xml->Склады);
		}

		if ($xml->ЕдиницыИзмерения) {
			$this->log("Единицы измерений из классификатора (CML >= v2.09)",2);
			$units = $this->parseClassifierUnits($xml->ЕдиницыИзмерения);
			if ($this->config->get('exchange1c_parse_unit_in_memory') == 1) {
				$data['units'] = $units;
				$this->log($units, 2);
			}
			if ($this->ERROR) return false;
			unset($xml->ЕдиницыИзмерения);
		}

		if ($xml->Свойства) {
			$this->log("Атрибуты (Свойства в ТС) из классификатора загружены",2);
			$data['attributes']	= $this->parseClassifierAttributes($xml->Свойства);
			if ($this->ERROR) return false;
			unset($xml->Свойства);
		}

		if ($this->config->get('exchange1c_import_categories') == 1) {
			// Товарные категории
			if ($xml->Категории) {
				$this->parseClassifierProductCategories($xml->Категории, 0, $data);
				if ($this->ERROR) return false;
				unset($xml->Категории);
				$this->log("Категории товаров из классификатора загружены",2);
			} elseif ($xml->Группы) {
				$categories = $this->parseClassifierCategories($xml->Группы, 0, $data);
				if ($this->ERROR) return false;
				unset($xml->Группы);
				if ($this->config->get('exchange1c_parse_categories_in_memory') == 1) {
					$data['categories'] = $categories;
				}
				$this->log("Группы товаров из классификатора загружены",2);
			}
		}

		$this->log("Классификатор загружен", 2);
		return $data;

	} // parseClassifier()


	/**
	 * Разбор документа
	 */
	private function parseDocument($xml) {

		$order_guid		= (string)$xml->Ид;
		$order_id		= (string)$xml->Номер;

		$doc = array(
			'order_id'		=> $order_id,
			'date'			=> (string)$xml->Дата,
			'time'			=> (string)$xml->Время,
			'currency'		=> (string)$xml->Валюта,
			'total'			=> (float)$xml->Сумма,
			'doc_type'		=> (string)$xml->ХозОперация,
			'date_pay'		=> (string)$xml->ДатаПлатежа
		);

		// Просроченный платеж если date_pay будет меньше текущей
		if ($doc['date_pay']) {
			$this->log("По документу просрочена оплата");
		}

		$error = $this->parseDocumentCustomer($xml->Контрагенты, $doc);
		if ($error)
			return $error;

		$error = $this->parseDocumentProducts($xml->Товары, $doc);
		if ($error)
			return $error;

		$this->parseDocumentRequisite($xml->ЗначенияРеквизитов, $doc);

		$this->load->model('sale/order');
		$order = $this->model_sale_order->getOrder($order_id);
		if ($order) {
			$products = $this->model_sale_order->getOrderProducts($order_id);
		} else {
			return "Заказ #" . $doc['order_id'] . " не найден в базе";
		}

		$error = $this->updateDocument($doc, $order, $products);
		if ($error)
			return $error;

		$this->log("[i] Прочитан документ: Заказ #" . $order_id . ", Ид '" . $order_guid . "'");
		return "";

	} // parseDocument()


	/**
	 * ver 3
	 * update 2017-04-16
	 * Импорт файла
	 */
	public function importFile($importFile, $type) {

		// Функция будет сама определять что за файл загружается
		$this->log(">>>>>>>>>>>>>>>>>>>> НАЧАЛО ЗАГРУЗКИ ДАННЫХ <<<<<<<<<<<<<<<<<<<<");
		$this->log("Доступно памяти: " . sprintf("%.3f", memory_get_peak_usage() / 1024 / 1024) . " Mb",2);

        // Записываем единое текущее время обновления для запросов в базе данных
		$this->NOW = date('Y-m-d H:i:s');

		// Определение дополнительных полей
		$this->TAB_FIELDS = $this->config->get('exchange1c_table_fields');

		// Читаем XML
		libxml_use_internal_errors(true);
		$path_parts = pathinfo($importFile);
		$this->log("Файл: " . $path_parts['basename'], 2);
		$xml = @simplexml_load_file($importFile);
		if (!$xml) {
			$this->ERROR = "Файл не является стандартом XML, подробности в журнале\n";
			$this->ERROR .= implode("\n", libxml_get_errors());
			$this->log("Ошибка при загрузке файла: " . $importFile);
			return $this->error();
		}

		// Файл стандарта Commerce ML
		$this->checkCML($xml);
		if ($this->ERROR) return $this->error();

		// IMPORT.XML, OFFERS.XML
		if ($xml->Классификатор) {
			$this->log(">>>>>>>>>>>>>>>>>>>> ЗАГРУЗКА КЛАССИФИКАТОРА <<<<<<<<<<<<<<<<<<<<",2);
			$classifier = $this->parseClassifier($xml->Классификатор);
			if ($this->ERROR) return $this->error();
			unset($xml->Классификатор);
		} else {
			// CML 2.08 + Битрикс
			$classifier = array();
		}

		if ($xml->Каталог) {
			// Запишем в лог дату и время начала обмена

			$this->log(">>>>>>>>>>>>>>>>>>>> ЗАГРУЗКА КАТАЛОГА <<<<<<<<<<<<<<<<<<<<",2);
			if (!isset($classifier)) {
				$this->log("[i] Классификатор отсутствует! Все товары будут загружены в магазин по умолчанию!");
			}

			$this->parseDirectory($xml->Каталог, $classifier);
			if ($this->ERROR) return $this->error();
			unset($xml->Каталог);
		}

		// OFFERS.XML
		if ($xml->ПакетПредложений) {
			$this->log(">>>>>>>>>>>>>>>>>>>> ЗАГРУЗКА ПАКЕТА ПРЕДЛОЖЕНИЙ <<<<<<<<<<<<<<<<<<<<", 2);

			// Пакет предложений
			$this->parseOffersPack($xml->ПакетПредложений);
			if ($this->ERROR) return $this->error();
			unset($xml->ПакетПредложений);
		}

		// ORDERS.XML
		if ($xml->Документ) {
			$this->log(">>>>>>>>>>>>>>>>>>>> ЗАГРУЗКА ДОКУМЕНТОВ <<<<<<<<<<<<<<<<<<<<", 2);

			$this->clearLog();

			// Документ (заказ)
			foreach ($xml->Документ as $doc) {
				$this->parseDocument($doc);
				if ($this->ERROR) return $this->error();
			}
			unset($xml->Документ);
		}
		else {
			$this->log("[i] Не обработанные данные XML", 2);
			$this->log($xml,2);
		}

		$this->log(">>>>>>>>>>>>>>>>>>>> КОНЕЦ ЗАГРУЗКИ ДАННЫХ <<<<<<<<<<<<<<<<<<<<");
		return "";
	}


	/**
	 * ver 3
	 * update 2017-05-07
	 * Определение дополнительных полей и запись их в глобальную переменную типа массив
	 */
	public function defineTableFields() {

		$result = array();

		$this->log("Поиск в базе данных дополнительных полей",2);

		$tables = array(
			'manufacturer'				=> array('noindex'=>1),
			'product_to_category'		=> array('main_category'=>1),
			'product_description'		=> array('meta_h1'=>''),
			'category_description'		=> array('meta_h1'=>''),
			'manufacturer_description'	=> array('name'=>'','meta_h1'=>'','meta_title'=>''),
			'product'					=> array('noindex'=>1),
			'order'						=> array('payment_inn'=>'','shipping_inn'=>'','patronymic'=>'','payment_patronymic'=>'','shipping_patronymic'=>''),
			'customer'					=> array('patronymic'=>''),
			'cart'						=> array('product_feature_id'=>0,'unit_id'=>0),
			'attributes_value'			=> array(),
			'attributes_value_to_1c'	=> array(),
			'cart'						=> array('product_feature_id'=>'', 'unit_id'=>''),
			'product_price'				=> array('special'=>'', 'discount'=>'')
		);

		foreach ($tables as $table => $fields) {

			$query = $this->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
			if (!$query->num_rows) continue;

			$result[$table] = array();

			foreach ($fields as $field => $value) {

				$query = $this->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` WHERE `field` = '" . $field . "'");
				if (!$query->num_rows) continue;

				$result[$table][$field] = $value;
			}
		}
		return $result;

	} // defineTableFields()


	/**
	 * ver 6
	 * update 2017-05-07
	 * Устанавливает обновления
	 */
	public function checkUpdates($settings) {

		$table_fields = $this->defineTableFields();
		$message = "";
		if (isset($settings['exchange1c_version'])) {
			$version = $settings['exchange1c_version'];
			if ($version == '1.6.3.2') {
				$version = $this->update1_6_3_3($version, $message, $table_fields);
			}
			if ($version == '1.6.3.3') {
				$version = $this->update1_6_3_4($version, $message, $table_fields);
			}
			if ($version == '1.6.3.4') {
				$version = $this->update1_6_3_5($version, $message, $table_fields);
			}
			if ($version == '1.6.3.5') {
				$version = $this->update1_6_3_6($version, $message, $table_fields);
			}
			if ($version == '1.6.3.6') {
				$version = $this->update1_6_3_7($version, $message, $table_fields);
			}
		//$version = "1.6.3.7";
			if ($version == '1.6.3.7') {
				$version = $this->update1_6_3_8($version, $message, $table_fields);
			}
		}
		if (!$this->ERROR) {
			if ($version != $settings['exchange1c_version']) {
				$settings['exchange1c_table_fields']	= $this->defineTableFields();
				$this->setEvents();
				$settings['exchange1c_version'] = $version;
				$this->model_setting_setting->editSetting('exchange1c', $settings);
				$message .= "<br /><strong>ВНИМАНИЕ! после обновления необходимо проверить все настройки и сохранить!</strong>";
			}
		}

		return array('error'=>$this->ERROR, 'success'=>$message);

	} // checkUpdates()


	/**
	 * Устанавливает обновления
	 */
	private function update1_6_3_b1($old_version, &$message, $table_fields) {

		$resultOK = 1; // включено обновление
		$version = '1.6.3.b1';

		$result = @$this->query("ALTER TABLE  `" . DB_PREFIX . "product_feature` ADD  `product_id` INT( 11 ) NOT NULL AFTER `product_feature_id`");
		$message .= ($result ? "Успешно добавлено поле " : "Ошибка при добавлении поля ") . "product_id в таблицу product_feature<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "attribute_value` CHANGE `cml_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "cml_id в таблице attribute_value<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "attribute_to_1c` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице attribute_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "category_to_1c` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице category_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "unit_to_1c` CHANGE `cml_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "cml_id в таблице unit_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "product_to_1c` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице product_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "store_to_1c` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице store_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "product_feature` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице product_feature<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "review` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице review<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "manufacturer_to_1c` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице manufacturer_to_1c<br />";

		$result = @$this->query("ALTER TABLE `" . DB_PREFIX . "warehouse` CHANGE `1c_id` `guid` VARCHAR( 64 ) NOT NULL COMMENT 'GUID'");
		$message .= ($result ? "Успешно изменено поле " : "Ошибка при изменении поля ") . "1c_id в таблице warehouse<br />";

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_image_description`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_image_description` (
				`product_image_id` 			INT(11) 		NOT NULL 				COMMENT 'Ссылка на картинку',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'Ссылка на товар',
				`language_id` 				INT(11) 		NOT NULL 				COMMENT 'Ссылка на язык',
				`name` 						VARCHAR(255) 	NOT NULL DEFAULT '' 	COMMENT 'Название',
				KEY (`product_image_id`, `product_id`, `language_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		if ($resultOK) {
			$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
			return $version;
		} else {
			$message .= "При обновлении с версии " . $old_version . " на версию " . $version ." произошла ошибка!";
			return $old_version;
		}

	} // update1_6_3_b1()


	/**
	 * Устанавливает обновления
	 */
	private function update1_6_3_2($old_version, &$message, $table_fields) {

		$resultOK = 1; // включено обновление
		$version = '1.6.3.2';

		// Нет изменений в структуре БД

		if ($resultOK) {
			$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
			return $version;
		} else {
			$message .= "При обновлении с версии " . $old_version . " на версию " . $version ." произошла ошибка!";
			return $old_version;
		}

	} // update1_6_3_2()


	/**
	 * Устанавливает обновления
	 */
	private function update1_6_3_3($old_version, &$message, $table_fields) {

		$result = 1; // включено обновление
		$version = '1.6.3.3';

		// Нет изменений в структуре БД

		$result = @$this->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$message .= ($result ? "Успешно удалена таблица " : "Ошибка при удалении таблицы ") . "product_feature_value<br />";

		// Значения характеристики товара(доп. значения)
		// Если характеристики не используются, эта таблица будет пустая
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_feature_value`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_feature_value` (
				`product_feature_id` 		INT(11) 		NOT NULL 				COMMENT 'ID характеристики товара',
				`product_option_id` 		INT(11) 		NOT NULL 				COMMENT 'ID опции товара',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_option_value_id` 	INT(11) 		NOT NULL 				COMMENT 'ID значения опции товара',
				UNIQUE KEY `product_feature_value_key` (`product_feature_id`, `product_id`, `product_option_value_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`product_option_id`) 		REFERENCES `" . DB_PREFIX . "product_option`(`product_option_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_option_value_id`)	REFERENCES `" . DB_PREFIX . "product_option_value`(`product_option_value_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		if ($result) {
			$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
			return $version;
		} else {
			$message .= "При обновлении с версии " . $old_version . " на версию " . $version ." произошла ошибка!";
			return $old_version;
		}

	} // update1_6_3_3()


	/**
	 * Устанавливает обновления на версию 1.6.3.4
	 */
	private function update1_6_3_4($old_version, &$message, $table_fields) {

		$version = '1.6.3.4';

		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_4()


	/**
	 * Устанавливает обновления на версию 1.6.3.5
	 */
	private function update1_6_3_5($old_version, &$message, $table_fields) {

		$version = '1.6.3.5';

		$message .= "Исправлены ошибки (5), доработки (1), изменений (1)<br>";
		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_5()


	/**
	 * Устанавливает обновления на версию 1.6.3.6
	 */
	private function update1_6_3_6($old_version, &$message, $table_fields) {

		$version = '1.6.3.6';
		$message .= "Исправлены ошибки (1), доработки (0):<br>";
		$message .= "1. Ошибка при ручной генерации SEO, если в базе нет таблицы manufacturer_description <br>";
		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_6()


	/**
	 * Устанавливает обновления на версию 1.6.3.7
	 */
	private function update1_6_3_7($old_version, &$message, $table_fields) {

		$version = '1.6.3.7';
		$message .= "Исправлены ошибки E(3), обновления U(2):<br>";
		$message .= "E1. Затирание основнлй картинки при отключенном обновлении картинок<br>";
		$message .= "E2. При синхронизации по полям Артикул, Штрихкод или Наименование при пустом поле теперь не прерывается обмен<br>";
		$message .= "E3. Если предложение не найдено, обмен теперь не прерывается<br>";
		$message .= "U1. Добавлена кнопка применить<br>";
		$message .= "U2. Добавлена настройка - резервировать товары в заказе<br>";
		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_7()


	/**
	 * Устанавливает обновления на версию 1.6.3.8
	 */
	private function update1_6_3_8($old_version, &$message, $table_fields) {

		if (!isset($table_fields['product_price']['action'])) {
			$result = @$this->query("ALTER TABLE  `" . DB_PREFIX . "product_price` ADD  `action` INT( 1 ) NOT NULL AFTER `customer_group_id`");
			$message .= ($result ? "Успешно добавлено поле " : "Ошибка при добавлении поля ") . "'action' в таблицу 'product_price'<br />";
			if (!$result) return $old_version;
		}
		// Пересоздадим индекс
		$result = @$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_price` DROP INDEX `product_price_key`, ADD UNIQUE INDEX  `product_price_key` (`product_id`,`product_feature_id`,`customer_group_id`,`action`)");
		$message .= ($result ? "Успешно пересоздан индекс " : "Ошибка при пересоздании индекса ") . "'product_price_key' в таблице 'product_price'<br />";
		if (!$result) return $old_version;

		$version = '1.6.3.8';
		$message .= "Исправлены ошибки E(1), обновления U(3):<br>";
		$message .= "E1. Несколько ошибок при выгрузке заказов<br>";
		$message .= "U1. Добавлено удаление файла перед распаковкой XML из архива<br>";
		$message .= "U2. Добавлены акция и скидка в цены при загрузке характеристик<br>";
		$message .= "U3. Добавлены опция отключения очистки акций и скидок при полной выгрузке<br>";
		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_8()


	/**
	 * Устанавливает обновления на версию 1.6.3.9
	 */
	private function update1_6_3_9($old_version, &$message, $table_fields) {

		$version = '1.6.3.9';
		$message .= "Исправлены ошибки E(1), обновления U(1):<br>";
		$message .= "E1. Исправлено описание списка складов<br>";
		$message .= "U1. Добавлено описание списка складов для языка Enlish<br>";
		$message .= "Обновление с версии " . $old_version . " на версию " . $version ." прошло успешно";
		return $version;

	} // update1_6_3_9()

}

