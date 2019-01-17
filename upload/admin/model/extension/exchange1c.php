<?php

class ModelExtensionExchange1c extends Model {

	// VARS
	private $STORE_ID		= 0;
	private $LANG_ID		= 0;
	private $FULL_IMPORT	= false;
	private $NOW 			= '';
	private $TAB_FIELDS		= array();
	private $ERROR			= 0;
	private $XML_VER		= "";

	// Классификатор
	private $TAXES			= array();
	private	$MANUFACTURERS	= array();
	private	$CATEGORIES		= array();
	private	$ATTRIBUTES		= array();
	private	$ATTRIBUTE_GROUPS	= array();
	private $PRODUCT_CATEGORIES = array();

	// Статистика
	private $STAT			= array();

	/**
	 * ****************************** ОБЩИЕ ФУНКЦИИ ******************************
	 */


	/**
	 * ver 1
	 * update 2017-04-08
	 * Пишет ошибку в лог
	 * Возвращает текст ошибки
	 */
	private function error() {
		$this->log->write("ОШИБКА " . $this->ERROR . ". Смотрите описание ошибки в справке модуля обмена.");
		return $this->ERROR;
	} // error()


	/**
	 * ver 4
	 * update 2018-06-17
	 * Пишет информацию в файл журнала
	 *
	 * @param	int				Уровень сообщения
	 * @param	string,object	Сообщение или объект
	 */
	private function log($message, $level = 1, $line = '') {
		if ($level <= $this->config->get('exchange1c_log_level')) {

			if ($this->config->get('exchange1c_log_debug_line_view') == 1) {
				if (!$line) {
					list ($di) = debug_backtrace();
					$line = sprintf("%04s",$di["line"]);
				}
			} else {
				$line = '';
			}

			if (is_array($message) || is_object($message)) {
				$this->log->write($line . "M:");
				$this->log->write(print_r($message, true));
			} else {
				if (mb_substr($message,0,1) == '~') {
					$this->log->write('~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~');
					$this->log->write($line . "M " . mb_substr($message, 1));
				} else {
					$this->log->write($line . "M " . $message);
				}
			}
		}
	} // log()


	/**
	 * Формирует сообщение ошибки и описание ошибки
	 *
	 * @param	int				Номер ошибки
	 * @param	string,object	Сообщение или объект
	 */
	private function errorLog($error_num, $arg1 = '', $arg2 = '', $arg3 = '') {
		$this->ERROR = $error_num;
		$message = $this->language->get('error_' . $error_num . '_log');
		if (!$message) {
			$this->language->get('error_' . $error_num);
		}
		if ($message && $this->config->get('exchange1c_log_level') > 0) {
			list ($di) = debug_backtrace();
			$debug = "Строка ошибки: " . sprintf("%04s",$di["line"]) . " - ";
			$this->log->write(sprintf($debug . $message, $arg1, $arg2, $arg3));
		}
	} // log()


	/**
	 * ver 1
	 * update 2017-09-13
	 */
	function logStat($str) {
		if (isset($this->STAT[$str])) {
			$end = microtime(true);
			$lenght = $end - $this->STAT[$str];
			$this->STAT[$str] = $lenght;
			$this->log("Время обработки " . $str . ": " . $lenght . " сек");
		}
	}


	/**
	 * ver 2
	 * update 2017-12-25
	 */
	function statStart($str) {
		$this->STAT[$str] = microtime(true);
	}


	/**
	 * ver 2
	 * update 2017-12-25
	 */
	function statStop($str) {
		if (isset($this->STAT[$str])) {
			$end = microtime(true);
			$lenght = $end - $this->STAT[$str];
			$this->STAT[$str] = $lenght;
			$this->log("Время обработки " . $str . ": " . $lenght . " сек");
		}
	}


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
	 * ver 4
	 * update 2017-08-01
	 * Проверим файл на стандарт Commerce ML
	 */
	private function checkCML($xml) {

		if ($xml['ВерсияСхемы']) {
			$this->XML_VER = (string)$xml['ВерсияСхемы'];
			$this->log("Версия XML: " . $this->XML_VER, 2);
		} else {
			$this->errorLog(2100);
			return false;
		}
		return true;

	} // checkCML()


	/**
	 * ver 3
	 * update 2018-11-07
	 * Очищает базу
	 * Вызывается из контроллера, manualCleaning()
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
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_to_1c`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_category`');
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
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'option_to_product`');
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
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "` WHERE `Tables_in_" . DB_DATABASE . "` LIKE '" . DB_PREFIX . "attribute_value_to_1c'");
		if ($query->num_rows) {
			$this->log("Очистка значения атрибутов",2);
			$this->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_value_to_1c');
			$result .=  "Значения атрибутов\n";
		}
		$result .=  "Атрибуты\n";

		// Удаляем все характеристики
		$this->log("Очистка характеристик...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature`');
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'product_feature_value`');
		$result .=  "Характеристики\n";

		// Удаляем связи с магазинами
		$this->log("Очистка связей с магазинами...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'store_to_1c`');
		$result .=  "Связи с магазинами\n";

		// Доработка от SunLit (Skype: strong_forever2000)
		// Удаляем все отзывы
		$this->log("Очистка отзывов...",2);
		$this->query('TRUNCATE TABLE `' . DB_PREFIX . 'review`');
		$result .=  "Отзывы\n";

		return $result;

	} // cleanDB()


	/**
	 * Возвращает информацию о синхронизированных объектов с 1С товарок, категорий, атрибутов
	 * Вызывается из контроллера, index()
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
	 * ver 1
	 * update 2018-03-21
	 * Удаляет все дубли SEO URL
	 * Вызывается из контроллера, manualRemoveUnisedManufacturers()
	 */
	public function removeUnisedManufacturers() {

		$this->log("Начато удаление дублей SEO URL");

		$total = 0;
		$delete = 0;

		$query = $this->query("SELECT `manufacturer_id`,`name` FROM `" .  DB_PREFIX . "manufacturer`");
		if ($query->num_rows) {
			foreach ($query->rows as $manufacturer_info) {
				$total++;
				// Проверяем использование только в товарах
				$query_count = $this->query("SELECT COUNT(*) as total FROM `" .  DB_PREFIX . "product` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
				if ($query_count->num_rows) {
					if ($query_count->row['total']) {
						$this->log("Производитель '" . $manufacturer_info['name'] . "' используется в " . $query_count->row['total'] . " товарах");
						continue;
					}
					$this->log("Производителя '" . $manufacturer_info['name'] . "' можно удалить, manufacturer_id = " . $manufacturer_info['manufacturer_id']);
					$this->query("DELETE FROM `" . DB_PREFIX . "manufacturer` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
					$this->query("DELETE FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
					$this->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_1c` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
					if (isset($this->TAB_FIELDS['manufacturer_to_layout'])) {
						$this->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_layout` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
					}
					$this->query("DELETE FROM `" . DB_PREFIX . "manufacturer_to_store` WHERE `manufacturer_id` = " . $manufacturer_info['manufacturer_id']);
					$delete++;
				}
			}
		}

		return array(
			'total'		=> $total,
			'delete'	=> $delete,
			'error'		=> ''
		);

	} // removeUnisedManufacturers()


	/**
	 * ver 1
	 * update 2018-03-21
	 * Удаляет все дубли SEO URL
	 * Вызывается из контроллера, manualDeleteDoubleUrlAlias()
	 */
	public function deleteDoubleUrlAlias() {

		$this->log("Начато удаление дублей SEO URL");

		$total = 0;
		$product_doubles = 0;
		$product_doubles_total = 0;
		$category_doubles = 0;
		$category_doubles_total = 0;

		// Проверка товаров
		// Получим список всех товаров
		$query = $this->query("SELECT `product_id` FROM `" .  DB_PREFIX . "product`");
		if ($query->num_rows) {

			foreach ($query->rows as $product_info) {
				$query_url = $this->query("SELECT `url_alias_id` FROM `" .  DB_PREFIX . "url_alias` WHERE `query` = 'product_id=" . $product_info['product_id'] . "' ORDER BY `keyword`");

				if ($query_url->num_rows > 1) {
					$this->log("У товара product_id=" . $product_info['product_id'] . " найдено " . $query_url->num_rows . " SEO URL записей!");
					$product_doubles++;

					// Оставим только последний
					$num = 1;
					foreach ($query_url->rows as $url_info) {
						if ($num < $query_url->num_rows) {
							$this->query("DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `url_alias_id` = " . $url_info['url_alias_id']);
							$this->log("Удалена запись " . $num);
							$product_doubles_total++;
						}
						$num++;
					}
				}
			}
			$this->log("Удалено дублей SEO URL: " . $product_doubles_total . " в товарах " . $product_doubles);
		}
		$total += $product_doubles_total;

		// Проверка категорий
		// Получим список всех товаров
		$query = $this->query("SELECT `category_id` FROM `" .  DB_PREFIX . "category`");
		if ($query->num_rows) {

			foreach ($query->rows as $category_info) {
				$query_url = $this->query("SELECT `url_alias_id` FROM `" .  DB_PREFIX . "url_alias` WHERE `query` = 'category_id=" . $category_info['category_id'] . "' ORDER BY `keyword`");

				if ($query_url->num_rows > 1) {
					$this->log("У категории category_id=" . $category_info['category_id'] . " найдено " . $query_url->num_rows . " SEO URL записей!");
					$category_doubles++;

					// Оставим только последний
					$num = 1;
					foreach ($query_url->rows as $url_info) {
						if ($num < $query_url->num_rows) {
							$this->query("DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `url_alias_id` = " . $url_info['url_alias_id']);
							$this->log("Удалена запись " . $num);
							$category_doubles_total++;
						}
						$num++;
					}
				}
			}
			$this->log("Удалено дублей SEO URL: " . $category_doubles_total . " в категориях " . $category_doubles);
		}

		$total += $category_doubles_total;

		$this->log("Завершено удаление всех дублей SEO URL, удалено: " . $total . " дублей");
		$result = array(
			'error'						=> $this->ERROR,
			'total'			 			=> $total,
			'product_doubles'			=> $product_doubles,
			'category_doubles'			=> $category_doubles,
			'product_doubles_total'		=> $product_doubles_total,
			'category_doubles_total'	=> $category_doubles_total
		);
		return $result;

	} // deleteDoubleUrlAlias()


	/**
	 * ver 1
	 * update 2017-12-11
	 * Удаляет все товары загруженные через модуль
	 * Вызывается из контроллера, функция manualDeleteImportData()
	 */
	public function deleteImportData() {

		$this->log("Удаление данных которые были загружены с УС, то есть которые имеют связи");
		$result = array(
			'error' 		=> "",
			'product'		=> 0,
			'attribute'		=> 0,
			'manufacturer'	=> 0,
			'category'		=> 0
		);

		$this->load->model('catalog/product');
		$query = $this->query("SELECT `product_id` FROM `" .  DB_PREFIX . "product_to_1c`");
		if ($query->num_rows) {
			$this->log("Удаление товаров...");
			$result['product'] = $query->num_rows;
			foreach ($query->rows as $row) {
				$this->model_catalog_product->deleteProduct($row['product_id']);
				$this->deleteLinkProduct($row['product_id']);
				$this->log("Удален товар product_id = " . $row['product_id']);
			}
		}

		$this->load->model('catalog/category');
		$query = $this->query("SELECT `category_id` FROM `" .  DB_PREFIX . "category_to_1c`");
		if ($query->num_rows) {
			$this->log("Удаление категорий...");
			$result['category'] = $query->num_rows;
			foreach ($query->rows as $row) {
				$this->model_catalog_category->deleteCategory($row['category_id']);
				$this->deleteLinkCategory($row['category_id']);
				$this->log("Удалена категория category_id = " . $row['category_id']);
			}
		}

		$this->load->model('catalog/manufacturer');
		$query = $this->query("SELECT `manufacturer_id` FROM `" .  DB_PREFIX . "manufacturer_to_1c`");
		if ($query->num_rows) {
			$this->log("Удаление производителей...");
			$result['manufacturer'] = $query->num_rows;
			foreach ($query->rows as $row) {
				$this->model_catalog_manufacturer->deleteManufacturer($row['manufacturer_id']);
				$this->deleteLinkManufacturer($row['manufacturer_id']);
				$this->log("Удален производитель manufacturer_id = " . $row['manufacturer_id']);
			}
		}

		$this->load->model('catalog/attribute');
		$query = $this->query("SELECT `attribute_id` FROM `" .  DB_PREFIX . "attribute_to_1c`");
		if ($query->num_rows) {
			$this->log("Удаление атрибутов...");
			$result['attribute'] = $query->num_rows;
			foreach ($query->rows as $row) {
				$this->model_catalog_attribute->deleteAttribute($row['attribute_id']);
				$this->deleteLinkAttribute($row['attribute_id']);
				$this->log("Удален атрибут attribute_id = " . $row['attribute_id']);
			}
		}

		return $result;

	} // deleteImportData()


	/**
	 * ver 4
	 * update 2018-05-23
	 * Удаляет все связи с товаром
	 */
	public function deleteLinkProduct($product_id) {

		$this->log("Удаление связей у товара product_id: " . $product_id, 2);

		// Удаляем линк
		if ($product_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
			$this->log("Удалена связь с товаром ID - GUID", 2);
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
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_feature` WHERE `product_id` = " . (int)$product_id);
		$this->query("DELETE FROM `" .  DB_PREFIX . "product_feature_value` WHERE `product_id` = " . (int)$product_id);
		$this->log("Удалены характеристики", 2);

		// Удалим связи опции с товарами
		$this->query("DELETE FROM `" .  DB_PREFIX . "option_to_product` WHERE `product_id` = " . (int)$product_id);
		$this->log("Удалены связи опции с товарами", 2);

	} // deleteLinkProduct()


	/**
	 * ver 3
	 * update 2017-11-05
	 * Удаляет все связи у категории
	 */
	public function deleteLinkCategory($category_id) {

		// Удаляем линк
		if ($category_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "category_to_1c` WHERE `category_id` = " . (int)$category_id);
			$this->log("Удалена связь с категорией category_id = " . $category_id, 2);
		}

	} //  deleteLinkCategory()


	/**
	 * ver 3
	 * update 2017-11-05
	 * Удаляет все связи у производителя
	 */
	public function deleteLinkManufacturer($manufacturer_id) {

		// Удаляем линк
		if ($manufacturer_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "manufacturer_to_1c` WHERE `manufacturer_id` = " . (int)$manufacturer_id);
			$this->log("Удалена связь с производителем manufacturer_id = " . $manufacturer_id, 2);
		}

	} //  deleteLinkManufacturer()


	/**
	 * ver 1
	 * update 2017-11-05
	 * Удаляет все связи с атрибутами
	 */
	public function deleteLinkAttribute($attribute_id) {

		// Удаляем линк
		if ($attribute_id){
			$this->query("DELETE FROM `" .  DB_PREFIX . "attribute_to_1c` WHERE `attribute_id` = " . (int)$attribute_id);
			$this->query("DELETE FROM `" .  DB_PREFIX . "attribute_value_to_1c` WHERE `attribute_id` = " . (int)$attribute_id);
			$this->log("Удалена связь с атрибутом attribute_id = " . $attribute_id, 2);
		}

	} //  deleteLinkAttribute()


	/**
	 * ver 2
	 * update 2017-10-30
	 * Создает события
	 * Вызывается из контроллера, функция install()
	 */
	public function setEvents() {

		// Установка событий
		$this->load->model('extension/event');
		// Удалим все события
		$this->model_extension_event->deleteEvent('exchange1c');
		// Добавим удаление связей при удалении товара
		$this->model_extension_event->addEvent('exchange1c', 'admin/model/catalog/product/deleteProduct/after', 'extension/module/exchange1c/eventDeleteProduct');
		// Добавим удаление связей при удалении категории
		$this->model_extension_event->addEvent('exchange1c', 'admin/model/catalog/category/deleteCategory/after', 'extension/module/exchange1c/eventDeleteCategory');
		// Добавим удаление связей при удалении Производителя
		$this->model_extension_event->addEvent('exchange1c', 'admin/model/catalog/manufacturer/deleteManufacturer/after', 'extension/module/exchange1c/eventDeleteManufacturer');
		// Добавим удаление связей при удалении Атрибута
		$this->model_extension_event->addEvent('exchange1c', 'admin/model/catalog/attribute/deleteAttribute/after', 'extension/module/exchange1c/eventDeleteAttribute');

	} // setEvents()


	/**
	 * Получает language_id из code (ru, en, etc)
	 * Как ни странно, подходящей функции в API не нашлось
	 *
	 * @param	string
	 * @return	int
	 */
	public function getLanguageId($lang) {

		if ($this->LANG_ID) {
			return $this->LANG_ID;
		}
		$query = $this->query("SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = '" . $this->db->escape($lang) . "'");
		$this->LANG_ID = $query->row['language_id'];
		return $this->LANG_ID;

	} // getLanguageId()


	/**
	 * ver 5
	 * update 2017-05-02
	 * Проверяет таблицы модуля
	 * Вызывается из контроллера, фунция index()
	 */
	public function checkDB() {

		$tables_db = array();
		$query = $this->query("SHOW TABLES FROM `" . DB_DATABASE . "`");
		if ($query->num_rows) {
			foreach ($query->rows as $table) {
				$tables_db[] = substr(array_shift($table), strlen(DB_PREFIX));
			}
		}

		$tables_module = array("product_to_1c","category_to_1c","product_feature","product_feature_value","attribute_to_1c","manufacturer_to_1c","attribute_value_to_1c");
		$tables_diff = array_diff($tables_module, $tables_db);

		if ($tables_diff) {
			$error = "Таблица(ы) " . implode(", ", $tables_diff) . " в базе отсутствует(ют)";
			$this->log($error);
			return $error;
		}
		return "";

	} // checkDB()


	/**
	 * Поиск guid товара по ID
	 */
	public function getGuidByProductId($product_id) {

		$query = $this->query("SELECT `guid` FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
		if ($query->num_rows) {
			return $query->row['guid'];
		}
		return '';

	} // getGuidByProductId()


	// ADD Modificator
	/**
	 * ****************************** ФУНКЦИИ ДЛЯ СКЛАДОВ ******************************
	 */

	/**
	 * ver 2
	 * update 2017-09-23
	 * Добавляет склад в базу данных
	 */
	private function addWarehouse($warehouse_guid, $name) {

		$this->query("INSERT INTO `" . DB_PREFIX . "warehouse` SET `name` = '" . $this->db->escape($name) . "', `guid` = '" . $this->db->escape($warehouse_guid) . "'");
		$warehouse_id = $this->db->getLastId();
		$this->log("Склад добавлен '" . $name . "'");

		$this->WAREHOUSES[$warehouse_guid] = array(
			'warehouse_id'	=> $warehouse_id,
			'name'			=> $name
		);

		return $warehouse_id;

	} // addWarehouse()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ SEO ******************************
	 */


	/**
	 * ver 2
	 * update 2017-08-11
	 * Получает SEO_URL данные
	 */
	private function getSeoUrl($element, $id, $last_symbol = "") {

    	$result = array(
   			'url_alias_id'	=> 0,
   			'keyword'		=> ""
		);
		$query = $this->query("SELECT `url_alias_id`,`keyword` FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $element . "=" . (string)$id . "'");
    	if ($query->num_rows) {
    		$result = array(
    			'url_alias_id'	=> $query->row['url_alias_id'],
    			'keyword'		=> $query->row['keyword'] . $last_symbol
			);
    		return $result;
    	}
    	return $result;

	} // getSeoUrl()


	/**
	 * ver 2
	 * update 2017-08-10
	 * Устанавливает SEO URL (ЧПУ) для заданного товара
	 */
	private function setSeoURL($url_type, $element_id, $element_name, $old_element) {

		if (empty($old_element['keyword']) && empty($element_name)) {
			$this->log("ВНИМАНИЕ! старое и новое значение SEO URL пустое!");
			return false;
		}

		$this->log("SEO URL старое: '" . $old_element['keyword'] . "', новое '" . $element_name . "'", 2);

		// Проверка на одинаковые keyword
		$keyword = $element_name;

		// Получим все названия начинающиеся на $element_name
		$keywords = array();
		$query = $this->query("SELECT `url_alias_id`,`keyword` FROM `" . DB_PREFIX . "url_alias` WHERE `query` <> '" . $url_type . "=" . $element_id . "' AND `keyword` LIKE '" . $this->db->escape($keyword) . "-%'");
		foreach ($query->rows as $row) {
			$keywords[$row['url_alias_id']] = $row['keyword'];
		}
		// Проверим на дубли
		$key = array_search($keyword, $keywords);
		$num = 0;
		while ($key) {
			// Есть дубли
			$this->log("SeoUrl занято: '" . $keyword . "'");
			$num ++;
			$keyword = $element_name . "-" . (string)$num;
			$key = array_search($keyword, $keywords);
			if ($num > 200) {
				$this->log("[!] больше 200 дублей!", 2);
				$this->errorLog(2500);
			}
		}

		// Обновляем если только были изменения и существует запись
		if ($old_element['keyword'] != $keyword && $old_element['url_alias_id']) {

			$this->query("UPDATE `" . DB_PREFIX . "url_alias` SET `keyword` = '" . $this->db->escape($keyword) . "' WHERE `url_alias_id` = " . $old_element['url_alias_id']);

		} else {

			$this->query("INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword` = '" . $this->db->escape($keyword) . "'");

		}

	} // setSeoURL()


	/**
	 * ver 3
	 * update 2017-06-12
	 * Транслиетрирует RUS->ENG
	 * @param string $aString
	 * @return string type
	 * Автор: Константин Кирилюк
	 * url: http://www.chuvyr.ru/2013/11/translit.html
	 */
	private function translit($s, $space = '-') {

		$s = (string) $s; // преобразуем в строковое значение
		$s = strip_tags($s); // убираем HTML-теги
		$s = str_replace(array('\n', '\r'), ' ', $s); // убираем перевод каретки
		$s = trim($s); // убираем пробелы в начале и конце строки
		$s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
		$s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
		$s = preg_replace('/[^0-9a-z-_ ]/i', '', $s); // очищаем строку от недопустимых символов
  		$s = preg_replace('/\s+/', ' ', $s); // удаляем повторяющие пробелы
		$s = str_replace(' ', $space, $s); // заменяем пробелы знаком минус
  		return $s; // возвращаем результат

	} // translit()


	/**
	 * ver 2
	 * update 2017-06-12
	 * Получает все категории продукта в строку для SEO
	 */
    private function getProductCategoriesString($product_id) {

 		$categories = array();

		$query = $this->query("SELECT `c`.`category_id`, `cd`.`name` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) INNER JOIN `" . DB_PREFIX . "product_to_category` `pc` ON (`pc`.`category_id` = `c`.`category_id`) WHERE `cd`.`language_id` = " . $this->LANG_ID . " AND `pc`.`product_id` = " . (int)$product_id . " ORDER BY `c`.`sort_order`, `cd`.`name` ASC");
		foreach ($query->rows as $category) {
			$categories[] = $category['name'];
		}
		$cat_string = implode(',', $categories);
		return $cat_string;

      } // getProductCategoriesString()


	/**
	 * ver 1
	 * update 2018-11-05
	 * Получает все товарные категории в массив
	 */
    private function getProductCategories2() {

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_category`");

		foreach ($query->rows as $product_category) {
			$this->PRODUCT_CATEGORIES[$product_category['guid']] = array(
				'product_category_id'	=> $product_category['product_category_id'],
				'name'					=> $product_category['name']
			);
		}

    } // getProductCategories2()


	/**
	 * ver 2
	 * update 2017-06-12
	 * Получает все категории продукта в массив
	 * первым в массиме будет главная категория
	 */
    private function getProductCategories($product_id, $limit = 0) {

 		// Ограничение по количеству категорий
		$sql_limit = $limit > 0 ? ' LIMIT ' . $limit : '';

		$main_category = isset($this->TAB_FIELDS['product_to_category']['main_category']) ? ",`main_category`" : "";
		$query = $this->query("SELECT `category_id`" . $main_category . " FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . (int)$product_id . $sql_limit);
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
		//$s = preg_replace("![^\w\d\s]*!", "", $s); // очищаем строку от недопустимых символов
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
	 * ver 5
	 * update 2018-06-14
	 * Генерит SEO переменные шаблона для товара
	 * С версии 4 функция теперь не читает данные из базы
	 */
	private function seoGenerateProduct($product_id, $data) {

		$result = array();

		// Товары, Категории
		$seo_fields = array('tag');
		if (isset($this->TAB_FIELDS['product_description']['meta_title'])) {
			$seo_fields[] = 'meta_title';
		}
		if (isset($this->TAB_FIELDS['product_description']['meta_description'])) {
			$seo_fields[] = 'meta_description';
		}
		if (isset($this->TAB_FIELDS['product_description']['meta_keyword'])) {
			$seo_fields[] = 'meta_keyword';
		}
		//$this->log($this->TAB_FIELDS, 2);

		// Сопоставляем значения к паттернам
		$tags = array(
			'{name}'		=> isset($data['name']) 			? $data['name']					: '',
			'{sku}'			=> isset($data['sku'])				? $data['sku']					: '',
			'{model}'		=> isset($data['model'])			? $data['model']				: '',
			'{brand}'		=> isset($data['manufacturer'])		? $data['manufacturer']['name'] : '',
			'{cats}'		=> $this->getProductCategoriesString($product_id),
			'{prod_id}'		=> isset($product_id)				? $product_id					: '',
			'{cat_id}'		=> isset($data['category_id'])		? $data['category_id']			: ''
		);
		if (isset($this->TAB_FIELDS['product_description']['meta_h1'])) {
			$seo_fields[] = 'meta_h1';
		}

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field) {
			$template = '';

			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
				$template = $this->config->get('exchange1c_seo_product_'.$field.'_template');

				// Если выбран шаблон, но он пустой, пропускаем
				if (!$template) {
					$this->log("Шаблон пустой - пропускаем");
					continue;
				}

				if ($this->config->get('exchange1c_seo_product_mode') == 'overwrite') {
					// Перезаписывать

					if ($field == 'meta_keyword' || $field == 'tag') {
						$value = $this->seoGenerateString($template, $tags, false, true);
					} else {
						$value = $this->seoGenerateString($template, $tags);
					}

					// Если вдруг по каким-либо причинам это поле отсутствует, будем считать что оно есть, но пустое
					// Вот тут может быть когда-либо ошибка...
					if (!isset($data[$field])) {
						$data[$field] = "";
					}

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($value == $data[$field]) {
						$this->log("Поле '" . $field . "' не изменилось: " . $data[$field], 2);
						continue;
					}

					// Нужно обновить поле
					$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "'", 2);
					$result[$field] = $value;

				} else {
					// Только если поле пустое
					if (empty($data[$field])) {
						$value = $this->seoGenerateString($template, $tags);
						$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "'", 2);
						$result[$field] = $value;
					} else {
						$this->log("Пропускаем '" . $field . "', т.к. не пустое: '" . $data[$field] . "'", 2);
					}
				}
			} else {
				$this->log("Шаблон для поля '" . $field . "' не найден!");
				continue;
			}
		}

		if ($this->config->get('exchange1c_seo_product_seo_url') == 'template') {
			// Сформируем SEO URL
			$template = $this->config->get('exchange1c_seo_product_seo_url_template');
			$keyword = $this->seoGenerateString($template, $tags, true);

			// Получим старый SeoUrl
			$seo_url = $this->getSeoUrl("product_id", $product_id);

			// обновляем если только были изменения
			if ($this->config->get('exchange1c_seo_product_mode') == 'overwrite' || ($this->config->get('exchange1c_seo_product_mode') == 'if_empty' && empty($seo_url['keyword']))) {
				if ($seo_url['keyword'] != $keyword) {
					$this->setSeoURL('product_id', $product_id, $keyword, $seo_url);
				}
			}
		}

		$this->log("SEO товара обновлено полей: " . count($result));
		return $result;

	} // seoGenerateProduct()


	/**
	 * ver 6
	 * update 2018-06-14
	 * Генерит SEO переменные шаблона для категории
	 */
	private function seoGenerateCategory($category_id, &$data) {

		$seo_fields = array(
			'meta_title',
			'meta_description',
			'meta_keyword'
		);
		if (isset($this->TAB_FIELDS['category_description']['meta_h1'])) {
			$seo_fields[] = 'meta_h1';
		}

		// Сопоставляем значения к паттернам
		$tags = array(
			'{cat}'			=> isset($data['name']) 		? $data['name'] 		: '',
			'{cat_id}'		=> $category_id
		);

		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field) {

			if (!isset($data[$field])) {
				$data[$field] = "";
			}

			if ($this->config->get('exchange1c_seo_product_'.$field) == 'template') {
				// Если включено формирование по шаблону

				$template = $this->config->get('exchange1c_seo_category_'.$field.'_template');

				// Если выбран шаблон, но он пустой, пропускаем
				if (!$template) {
					unset($data[$field]);
					continue;
				}

				if ($this->config->get('exchange1c_seo_category_mode') == 'overwrite') {
					// Перезаписывать

					$value = $this->seoGenerateString($template, $tags);

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($value == $data[$field]) {
						$this->log("Поле '" . $field . "' не изменилось: " . $data[$field], 2);
						unset($data[$field]);
						continue;
					}

					// Нужно обновить поле
					$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "', шаблон: " . $template, 2);
					$data[$field] = $value;

				} elseif ($this->config->get('exchange1c_seo_category_mode') == 'if_empty' && empty($data[$field])) {
					// Только если поле пустое

					$value = $this->seoGenerateString($template, $tags);
					$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "'", 2);
					$data[$field] = $value;

				} else {
					unset($data[$field]);
				}

			} else {

				// Не изменяем это поле
				unset($data[$field]);
				continue;
			}
		} // foreach

		if ($this->config->get('exchange1c_seo_category_seo_url') == 'template') {
			// Сформируем SEO URL
			$template = $this->config->get('exchange1c_seo_category_seo_url_template');
			$keyword = $this->seoGenerateString($template, $tags, true);

			// Получим старый SeoUrl
			$seo_url = $this->getSeoUrl("category_id", $category_id);

			// обновляем если только были изменения
			if ($this->config->get('exchange1c_seo_category_mode') == 'overwrite' || ($this->config->get('exchange1c_seo_category_mode') == 'if_empty' && empty($seo_url['keyword']))) {
				if ($seo_url['keyword'] != $keyword) {
					$this->setSeoURL('category_id', $category_id, $keyword, $seo_url);
				}
			}
		}

		$this->log("Сформировано SEO для категории");

	} // seoGenerateCategory()


	/**
	 * ver 10
	 * update 2018-06-14
	 * Генерит SEO переменные шаблона для производетеля
	 */
	private function seoGenerateManufacturer($manufacturer_id, &$data) {

		if (!isset($this->TAB_FIELDS['manufacturer_description'])) {
			$this->log("В базе отсутствует таблица manufacturer_description, SEO не будет сформировано");
			return false;
		}

		$seo_fields = array();

		if (isset($this->TAB_FIELDS['product_description'])) {
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_h1'])) {
				$seo_fields[] = 'meta_h1';
			}
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_title'])) {
				$seo_fields[] = 'meta_title';
			}
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_description'])) {
				$seo_fields[] = 'meta_description';
			}
			if (isset($this->TAB_FIELDS['manufacturer_description']['meta_keyword'])) {
				$seo_fields[] = 'meta_keyword';
			}

			// Получим поля для сравнения
			$fields	= implode($seo_fields,', ');

			$query = $this->query("SELECT " . $fields . " FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . (int)$manufacturer_id . " AND `language_id` = " . $this->LANG_ID);
			foreach ($seo_fields as $field) {
				$data[$field] = isset($query->row[$field]) ?  $query->row[$field] : "";
			}
		}

		// Сопоставляем значения к тегам
		$tags = array(
			'{brand}'		=> isset($data['name']) 			? $data['name'] 			: '',
			'{brand_id}'	=> (string)$manufacturer_id
		);

		$update = false;
		// Формируем массив с замененными значениями
		foreach ($seo_fields as $field) {
			$template = '';
			if ($this->config->get('exchange1c_seo_manufacturer_' . $field) == 'template') {
				$template = $this->config->get('exchange1c_seo_manufacturer_' . $field . '_template');

				if (!$template) {
					unset($data[$field]);
					continue;
				}

				if ($this->config->get('exchange1c_seo_manufacturer_mode') == 'overwrite') {

					// Перезаписывать
					$value = $this->seoGenerateString($template, $tags);

					// Если поле не изменилось, нет смысла его перезаписывать
					if ($value == $data[$field]) {
						$this->log("Поле '" . $field . "' не изменилось: " . $data[$field], 2);
						unset($data[$field]);
						continue;
					}

					// Нужно обновить поле
					$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "'", 2);
					$data[$field] = $value;
					$update = true;

				} else {
					// Только если поле пустое
					if (empty($data[$field])) {
						$value = $this->seoGenerateString($template, $tags);
						$this->log("Поле: '" . $field . "' старое: '" . $data[$field] . "', новое: '" . $value . "'", 2);
						$data[$field] = $value;
						$update = true;
					} else {
						$this->log("Пропускаем '" . $field . "', т.к. не пустое: '" . $data[$field] . "'", 2);
						unset($data[$field]);
					}
				}

			} else {

				// Не изменяем это поле
				unset($data[$field]);
				continue;
			}

		}

		if ($this->config->get('exchange1c_seo_manufacturer_seo_url') == 'template') {
			// Сформируем SEO URL
			$template = $this->config->get('exchange1c_seo_manufacturer_seo_url_template');
			$keyword = $this->seoGenerateString($template, $tags, true);

			// Получим старый SeoUrl
			$seo_url = $this->getSeoUrl("manufacturer_id", $manufacturer_id);

			// обновляем если только были изменения
			if ($this->config->get('exchange1c_seo_manufacturer_mode') == 'overwrite' || ($this->config->get('exchange1c_seo_manufacturer_mode') == 'if_empty' && empty($seo_url['keyword']))) {
				if ($seo_url['keyword'] != $keyword) {
					$this->setSeoURL('manufacturer_id', $manufacturer_id, $keyword, $seo_url);
				}
			}
		}

		$this->log("Сформировано SEO для производителя");

		return $update;

	} // seoGenerateManufacturer()


	/**
	 * ver 4
	 * update 2018-06-14
	 * Генерит SEO переменные шаблона для товара
	 */
	public function seoGenerate() {

        $now = date('Y-m-d H:i:s');
		$result = array(
			'error'			=> '',
			'product'		=> 0,
			'category'		=> 0,
			'manufacturer'	=> 0
		);

		$language_id = $this->getLanguageId($this->config->get('config_language'));

		if ($this->config->get('exchange1c_seo_product_mode') != 'disable') {
			// Выбрать все товары, нужны поля:
			// name, sku, model, manufacturer_id, description, product_id, category_id
			$no_update_description = array();
			if (isset($this->TAB_FIELDS['product_description']['meta_h1'])) {
				$sql = "SELECT `p`.`product_id`, `p`.`sku`, `p`.`model`, `p`.`manufacturer_id`, `pd`.`name`, `pd`.`tag`, `pd`.`meta_title`, `pd`.`meta_description`, `pd`.`meta_keyword`, `pd`.`meta_h1` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `pd.`language_id` = " . $language_id;
			} else {
				$sql = "SELECT `p`.`product_id`, `p`.`sku`, `p`.`model`, `p`.`manufacturer_id`, `pd`.`name`, `pd`.`tag`, `pd`.`meta_title`, `pd`.`meta_description`, `pd`.`meta_keyword` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id`) WHERE `pd`.`language_id` = " . $language_id;
				array_push($no_update_description, 'meta_h1');
			}

			$query = $this->query($sql);
			if ($query->num_rows) {
				foreach ($query->rows as $data) {

					$result['product']++;
	 				$data_old = $data;
	 				if ($this->config->get('exchange1c_seo_product_mode') != 'disable')
						$update = $this->seoGenerateProduct($data['product_id'], $data);

					if (!$update)
						continue;

					// Сравнение
					$no_update = array('sku','model','manufacturer_id');
					$update_fields = $this->compareArraysData($data_old, $data, $no_update);

					// Если есть что обновлять
					if ($update_fields) {
						$sql_set = $this->prepareQuery($update_fields, 'set');
						$this->query("UPDATE `" . DB_PREFIX . "product` SET " . $sql_set . ", `date_modified` = '" . $now . "' WHERE `product_id` = " . (int)$data['product_id']);
					}

					// Сравнение
					$update_fields = $this->compareArraysData($data_old, $data, $no_update_description);

					// Если есть что обновлять
					if ($update_fields) {
						$sql_set = $this->prepareQuery($update_fields, 'set');
						$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $sql_set . " WHERE `product_id` = " . (int)$data['product_id'] . " AND `language_id` = " . $language_id);
					}
				}
			}
		}

		// Категории

		if ($this->config->get('exchange1c_seo_category_mode') != 'disable') {
			// Выбрать все категории, нужны поля:
			// name, sku, model, manufacturer_id, description, product_id, category_id
			$no_update_description = array();
			if (isset($this->TAB_FIELDS['category_description']['meta_h1'])) {
				$sql = "SELECT `c`.`category_id`, `cd`.`name`, `cd`.`meta_title`, `cd`.`meta_description`, `cd`.`meta_keyword`, `cd`.`meta_h1` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`language_id` = " . $language_id;
			} else {
				$sql = "SELECT `c`.`category_id`, `cd`.`name`, `cd`.`meta_title`, `cd`.`meta_description`, `cd`.`meta_keyword` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`language_id` = " . $language_id;
				array_push($no_update_description, 'meta_h1');
			}

			$query = $this->query($sql);
			if ($query->num_rows) {
				foreach ($query->rows as $data) {

					$result['category']++;
					if ($this->config->get('exchange1c_seo_category_mode') != 'disable')
						$this->seoGenerateCategory($data['category_id'], $data);

					// Сравнение
					$update_fields = $this->compareArraysData($data_old, $data, $no_update_description);

					// Если есть что обновлять
					if ($update_fields) {
						$sql_set = $this->prepareQuery($update_fields, 'set');
						$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $sql_set . " WHERE `category_id` = " . (int)$data['category_id'] . " AND `language_id` = " . $language_id);
						$this->query("UPDATE `" . DB_PREFIX . "category` SET `date_modified` = '" . $now . "' WHERE `category_id` = " . (int)$data['category_id']);
					}
				}
			}
		}

		// Производители

		if ($this->config->get('exchange1c_seo_manufacturer_mode') != 'disable') {
			if (isset($this->TAB_FIELDS['manufacturer_description'])) {
				// Выбрать все категории, нужны поля:
				// name, sku, model, manufacturer_id, description, product_id, category_id
				$no_update_description = array();
				if (isset($this->TAB_FIELDS['manufacturer_description']['meta_h1'])) {
					$sql = "SELECT `m`.`manufacturer_id`, `md`.`name`, `md`.`meta_title`, `md`.`meta_description`, `md`.`meta_keyword`, `md`.`meta_h1` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_description` `md` ON (`m`.`manufacturer_id` = `md`.`manufacturer_id`) WHERE `md`.`language_id` = " . $language_id;
				} else {
					$sql = "SELECT `m`.`manufacturer_id`, `md`.`name`, `md`.`meta_title`, `md`.`meta_description`, `md`.`meta_keyword` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_description` `md` ON (`m`.`manufacturer_id` = `md`.`manufacturer_id`) WHERE `md`.`language_id` = " . $language_id;
					array_push($no_update_description, 'meta_h1');
				}

				$query = $this->query($sql);
				if ($query->num_rows) {
					foreach ($query->rows as $data) {

						$result['manufacturer']++;

						$data_old = $data;

						if ($this->config->get('exchange1c_seo_manufacturer_mode') != 'disable')
							$update = $this->seoGenerateManufacturer($data['manufacturer_id'], $data);

						if (!$update)
							continue;

						// Сравнение
						$update_fields = $this->compareArraysData($data_old, $data, $no_update_description);

						// Если есть что обновлять
						if ($update_fields) {
							$sql_set = $this->prepareQuery($update_fields, 'set');
							$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $sql_set . " WHERE `category_id` = " . (int)$data['category_id'] . " AND `language_id` = " . $language_id);
							$this->query("UPDATE `" . DB_PREFIX . "category` SET `date_modified` = '" . $now . "' WHERE `category_id` = " . (int)$data['category_id']);
						}
					}
				}

			}
		}
		return $result;

	} // seoGenerate()


	/**
	 * ****************************** ПОДГОТОВКА ЗАПРОСОВ ******************************
	 */

	/**
	 * ver 3
	 * update 2017-08-28
	 * Формирует строку запроса для категории
	 */
	private function prepareQueryCategory($data, $mode = 'set') {

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

		$result = implode(($mode = 'set' ? ', ' : ' AND '), $sql);
		return $result ? $result . ", " : "";

	} //prepareQueryCategory()


	/**
	 * ver 2
	 * update 2018-03-14
	 * Формирует строку запроса для описания категорий и товаров
	 */
	private function prepareQueryDescription($data, $mode = 'set') {

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

	} //prepareQueryDescription()


	/**
	 * ver 3
	 * update 2018-03-25
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
		if (isset($data['unit_id']))
	 		$sql[] = $mode == 'set'		? "`unit_id` = '" .				(int)$data['unit_id'] . "'"							: "`unit_id`";
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
		if (isset($data['date_modified']))
	 		$sql[] = $mode == 'set'		? "`date_modified` = '" .		$this->db->escape($data['date_modified']) . "'"		: "`date_modified`";
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
	private function prepareQueryManufacturerDescription($data) {

		$sql  = isset($data['description']) 		? ", `description` = '" . $this->db->escape($data['description']) . "'"					: "";
		if (isset($this->TAB_FIELDS['manufacturer_description']['name'])) {
			$sql .= isset($data['name']) 				? ", `name` = '" . $this->db->escape($data['name']) . "'" 							: "";
		}
		$sql .= isset($data['meta_description']) 	? ", `meta_description` = '" . $this->db->escape($data['meta_description']) . "'" 		: "";
		$sql .= isset($data['meta_keyword']) 		? ", `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'"				: "";
		$sql .= isset($data['meta_title']) 			? ", `meta_title` = '" . $this->db->escape($data['meta_title']) . "'"					: "";
		$sql .= isset($data['meta_h1']) 			? ", `meta_h1` = '" . $this->db->escape($data['meta_h1']) . "'" 						: "";

		return $sql;

	} //prepareQueryManufacturerDescription()


	/**
	 * ****************************** ПРОЧЕЕ ******************************
	 */

	/**
	 * ver 3
	 * update 2015-03-24
	 * Разбивает название по шаблону "[число].[строка] [(строка в скобках)]"
	 */
	private function splitNameStr($str, $order = false, $option = false) {

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

		if ($option) {
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
		if ($order) {
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
		}

		// Наименование
		$data['name'] = trim(mb_substr($str, $pos_name_start, $pos_opt_start-$pos_name_start));
		return $data;

	} // splitNameStr()


	/**
	 * ver 3
	 * update 2017-08-20
	 * Сравнивает запрос с массивом данных и формирует список измененных полей
	 */
	private function compareArrays($query, $data, $no_update = array()) {

		// Сравниваем значения полей, если есть изменения, формируем поля для запроса
		$upd_fields = array();
		if ($query->num_rows) {
			foreach($query->row as $key => $row) {
				if (!isset($data[$key]) || isset($no_update[$key])) continue;
				if ($row <> $data[$key]) {
					$upd_fields[] = "`" . $key . "` = '" . $this->db->escape($data[$key]) . "'";
					$this->log("[i] Отличается поле '" . $key . "', старое: '" . $row . "', новое: '" . $data[$key] . "'", 2);
				}
			}
		}

		return implode(', ', $upd_fields);

	} // compareArrays()


	/**
	 * ver 3
	 * update 2017-09-08
	 * Сравнивает массивы и формирует список измененных полей для запроса
	 * newdata - новые данные
	 * olddata - старые данные
	 */
	private function compareArraysData(&$data_new, $data_old, $ignore_fields = array(), $merge = true) {

		//$this->log("Сравнение массивов...", 2);
		//$this->log("data_new:", 2);
		//$this->log($data_new, 2);
		//$this->log("data_old:", 2);
		//$this->log($data_old, 2);
		$result = array();

		if (count($data_old)) {

			foreach($data_old as $field => $value) {

				if (!isset($data_new[$field])) {
					// Если включено объединение, то записываем в новый массив старые данные полей которых нет в новом
					if ($merge) {
						$data_new[$field] = $value;
					}
					continue;
				}

				// Пропускаем те поля которые не нужно обновлять
				if ($ignore_fields) {
					$key = array_search($field, $ignore_fields);
					if ($key !== false) {
						continue;
					}
				}

				if ($value != $data_new[$field]) {

					$result[$field] = $this->db->escape($data_new[$field]);
					$this->log("[i] Отличается поле '" . $field . "', старое: " . $value . ", новое: " . $data_new[$field], 2);

				}
			}
		}
		return $result;

	} // compareArraysData()


	/**
	 * ******************************************* РОДИТЕЛЬСКИЕ КАТЕГОРИИ *********************************************
	 */

	/**
	 * ver 3
	 * update 2017-005-17
	 * Заполняет родительские категории у продукта
	 */
	public function fillParentsCategories(&$product_categories) {

		// Подгружаем только один раз
		if (empty($product_categories)) {
			$this->log("fillParentsCategories() - нет категорий, заполнение родительских категорий отменено", 2);
			return $product_categories;
		}

		foreach ($product_categories as $category_id) {
			$parents = $this->findParentsCategories($category_id);
			foreach ($parents as $parent_id) {
				$key = array_search($parent_id, $product_categories);
				if ($key === false)
					$product_categories[] = $parent_id;
			}
		}

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
	 * ******************************************* ОПЦИИ *********************************************
	 */


	/**
	 * ver 3
	 * update 2018-11-09
	 * Добавляет или получает значение опции по названию
	 */
	private function setOptionValue($value, $option_id, $image = '', $sort_order = 0) {

		$option_value_id = 0;

		$data = array();
		if ($sort_order) {
			$data['sort_order'] = $sort_order;
		}
		if ($image) {
			$data['image'] = $image;
		}

		// Проверим есть ли такое значение
		$query = $this->query("SELECT `ovd`.`option_value_id`,`ov`.`sort_order`,`ov`.`image` FROM `" . DB_PREFIX . "option_value_description` `ovd` LEFT JOIN `" . DB_PREFIX . "option_value` `ov` ON (`ovd`.`option_value_id` = `ov`.`option_value_id`) WHERE `ovd`.`language_id` = " . $this->LANG_ID . " AND `ovd`.`option_id` = " . $option_id . " AND `ovd`.`name` = '" . $this->db->escape($value) . "'");
		if ($query->num_rows) {

			$option_value_id = $query->row['option_value_id'];

			$this->log("Найдено значение опции '" . $value . "', option_value_id = " . $option_value_id, 2);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$fields = $this->compareArrays($query, $data);

			// Если есть расхождения, производим обновление
			if ($fields) {
				$this->query("UPDATE `" . DB_PREFIX . "option_value` SET " . $fields . " WHERE `option_value_id` = " . (int)$option_value_id);
				$this->log("Значение опции обновлено: '" . $value . "'");
			}

			return $option_value_id;
		}

		$sql = $sort_order == "" ? "" : ", `sort_order` = " . (int)$sort_order;
		$query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value` SET `option_id` = " . (int)$option_id . ", `image` = '" . $this->db->escape($image) . "'" . $sql);
		$option_value_id = $this->db->getLastId();

		if ($option_value_id) {
 			$query = $this->query("INSERT INTO `" . DB_PREFIX . "option_value_description` SET `option_id` = " . (int)$option_id . ", `option_value_id` = " . (int)$option_value_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($value) . "'");
			$this->log("Значение опции добавлено: '" . $value . "', option_value_id = " . $option_value_id);
		}

		return $option_value_id;

	} // setOptionValue()


	/**
	 * ver 3
	 * update 2018-11-09
	 * Установка опции
	 */
	private function setOption($name, $type = 'select', $guid = 0) {

		$sql = "SELECT `o`.`option_id`, `o`.`type`, `o`.`sort_order` FROM `" . DB_PREFIX . "option` `o` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`o`.`option_id` = `od`.`option_id`)";
		$where =  " WHERE `od`.`name` = '" . $this->db->escape($name) . "' AND `od`.`language_id` = " . $this->LANG_ID;

		$query = $this->query($sql . $where);
        if ($query->num_rows) {

			$option_id = $query->row['option_id'];
			$this->log("Найдена опция '" . $name . "', option_id = " .  $option_id);

			$update_fields = array();
        	if ($query->row['type'] != $type) {
        		$update_fields[] = "`type` = '" . $type . "'";
        	}

         	$sql_fields = implode(', ', $update_fields);
        	if ($sql_fields) {
				$this->query("UPDATE `" . DB_PREFIX . "option` SET " . $sql_fields . " WHERE `option_id` = " . (int)$option_id);
				$this->log("Опция обновлена: '" .  $name . "', option_id = " . $option_id);
        	}

        } else {

			// Если опции нет, добавляем
			$this->query("INSERT INTO `" . DB_PREFIX . "option` SET `type` = '" . $type . "', `sort_order` = 0");
			$option_id = $this->db->getLastId();
			$this->query("INSERT INTO `" . DB_PREFIX . "option_description` SET `option_id` = '" . (int)$option_id . "', `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");
			$this->log("Добавлена опция '" . $name . "', option_id = " . $option_id);

        }

        if ($guid) {
        	$query = $this->query("SELECT * FROM `" . DB_PREFIX . "option_to_1c` WHERE `guid` = '" . $this->db->escape($guid) . "'");
        	if (!$query->num_rows) {
        		$this->query("INSERT INTO `" . DB_PREFIX . "option_to_1c` SET `option_id` = " . $option_id . ", `guid` = '" . $this->db->escape($guid) . "'");
        	}
        }

		return $option_id;

	} // setOption()


	/**
	 * ************************************ ФУНКЦИИ ДЛЯ РАБОТЫ С ХАРАКТЕРИСТИКАМИ *************************************
	 */


	/**
	 * ver 4
	 * update 2018-04-02
	 * Устанавливаем значение характеристики
	 */
	private function setProductFeatureValue($product_feature_id, $product_id, $product_option_id, $product_option_value_id) {

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_id` = " . (int)$product_feature_id . " AND `product_id` = " . (int)$product_id . " AND `product_option_id` = " . (int)$product_option_id . " AND `product_option_value_id` = " . (int)$product_option_value_id);
		if ($query->num_rows) {
			$this->query("UPDATE `" . DB_PREFIX . "product_feature_value` SET `date_modified` = '" . $this->NOW . "' WHERE `product_feature_id` = " . (int)$product_feature_id . " AND `product_id` = " . (int)$product_id . " AND `product_option_id` = " . (int)$product_option_id . " AND `product_option_value_id` = " . (int)$product_option_value_id);
			return false;
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "product_feature_value` SET `product_feature_id` = " . (int)$product_feature_id . ", `product_id` = " . (int)$product_id . ", `product_option_id` = " . (int)$product_option_id . ", `product_option_value_id` = " . (int)$product_option_value_id . ", `date_modified` = '" . $this->NOW . "'");
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
	 * ver 20
	 * update 2018-11-12
	 * Добавляет товар в базу
	 * Возвращает product_id
	 */
	private function addProduct(&$data) {

		$data['status'] = $this->config->get('exchange1c_product_new_status_disable') ? 0 : 1;

		// ЕДИНИЦА ДЛИНЫ
		if ($this->config->get('config_length_class_id')) {
			$data['length_class_id']	= $this->config->get('config_length_class_id');
		}

		// ЕДИНИЦА ВЕСА
		if ($this->config->get('config_weight_class_id')) {
			$data['weight_class_id']	= $this->config->get('config_weight_class_id');
		}

		// ПРОИЗВОДИТЕЛЬ
		if (isset($data['manufacturer_name']))
			$data['manufacturer_id'] = $this->setManufacturer(htmlspecialchars($data['manufacturer_name']));

		// Статус на складе
		if ((int)$this->config->get('exchange1c_product_stock_status_off')) {
			$data['stock_status_id'] = (int)$this->config->get('exchange1c_product_stock_status_off');
		}

		$this->log($data, 2);

		// Чтобы на ocstore не было ошибок если в поле image будет NULL
		if (empty($data['image'])) {
			$data['image'] = 'no_image.png';
			$this->log("Нет картинки");
		}

		// Подготовим список полей по которым есть данные
		$fields = $this->prepareQueryProduct($data);

		if ($fields) {
			if (!empty($data['product_id'])) {
				$fields = "`product_id` = " . $data['product_id'] . (empty($fields) ? "" : ", " . $fields);
			}
			$this->query("INSERT INTO `" . DB_PREFIX . "product` SET " . $fields . ", `date_added` = '" . $this->NOW . "', `date_modified` = '" . $this->NOW . "'");
			$product_id = $this->db->getLastId();
		} else {
			// Если нет данных - выходим
			$this->log("addProduct() - нет данных");
			return 0;
		}

		// Сформируем SEO
		if ($this->config->get('exchange1c_seo_product_mode') != 'disable') {
			$update_fields = $this->seoGenerateProduct($product_id, $data);
			if ($update_fields)
				$this->compareArraysData($data, $update_fields);
		}

		$fields = $this->prepareQueryDescription($data, "set");
		$this->query("INSERT INTO `" . DB_PREFIX . "product_description` SET `product_id` = " . (int)$product_id . ", `language_id` = " . $this->LANG_ID . ", " . $fields);
		$this->log("Товар добавлен, product_id = " . $product_id, 2);

		// Связь с 1С только по Ид объекта из торговой системы
		$sql = "INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = " . (int)$product_id . ", `guid` = '" . $this->db->escape($data['product_guid']) . "'";
		if (isset($data['delete'])) {
			$sql .= ", `delete` = " . $data['delete'];
		}
		if (isset($data['version'])) {
			$sql .= ", `version` = '" . $this->db->escape($data['version']) ."'";
			$this->log("Добавлена версия товара, version = " . $data['version'], 2);
		}
		$this->query($sql);

		// Пропишем товар в магазин
		$this->query("INSERT INTO `" . DB_PREFIX . "product_to_store` SET `product_id` = " . (int)$product_id . ", `store_id` = " . $this->STORE_ID);
		$this->log("Товар добавлен в магазин, store_id = " . $this->STORE_ID, 2);

		// Записываем атрибуты в товар
		if (isset($data['attributes']) && $this->config->get('exchange1c_product_attribute_not_import') != 1) {
			foreach ($data['attributes'] as $attribute) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . (int)$product_id . ", `attribute_id` = " . (int)$attribute['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" .  $this->db->escape($attribute['value']) . "'");
				$attribute_id = $this->db->getLastId();
				$this->log("Добавлен атрибут товара, attribute_id = " . $attribute_id, 2);
			}
		}

		// Отзывы парсятся с Яндекса в 1С, а затем на сайт
		// Доработка от SunLit (Skype: strong_forever2000)
		// Записываем отзывы в товар
		if (isset($data['review'])) {
			$this->setProductReview($product_id, $data);
			if ($this->ERROR) return false;
		}

		// Категории
		if (isset($data['categories'])) {

			// Заполнение родительских категорий в товаре
			if ($this->config->get('exchange1c_fill_parent_cats') == 1) {
				$this->fillParentsCategories($data['categories']);
				if ($this->ERROR) return false;
			}

			$this->addProductCategories($product_id, $data['categories']);
			if ($this->ERROR) return false;

		}

		// Картинки
		if (!empty($data['images'])) {

			$this->setProductImages($product_id, $data['images'], true);
			if ($this->ERROR) return false;

		}

		// Очистим кэш товаров
		//$this->cache->delete('product');

		return $product_id;

	} // addProduct()


	/**
	 * ver 1
	 * update 2017-09-07
	 * Получает данные товара в массив
	 */
	private function getProduct($product_id) {

		$data = array();

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id);
		if ($query->num_rows) {
			foreach ($query->row as $key => $value) {
				$data[$key] = $value;
			}
		} else {
			$this->errorLog(2301);
			return false;
		}

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . (int)$product_id . " AND `language_id` = " . $this->LANG_ID);
		if ($query->num_rows) {
			//$data['product_description'] = array();
			foreach ($query->row as $key => $value) {
				//$data['product_description'][$key] = $value;
				$data[$key] = $value;
			}
		}

		return $data;

	} // getProduct()


	/**
	 * ver 2
	 * update 2017-09-07
	 * Добавляет в товаре категории
	 */
	private function addProductCategories($product_id, $product_categories) {

		// если в CMS ведется учет главной категории
		$main_category = isset($this->TAB_FIELDS['product_to_category']['main_category']);

		foreach ($product_categories as $index => $category_id) {
			// старой такой нет категориии
			$sql  = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . (int)$product_id . ", `category_id` = " . (int)$category_id;
			if ($main_category) {
				$sql .= $index == 0 ? ", `main_category` = 1" : ", `main_category` = 0";
			}
			$this->query($sql);
		}

		$this->log("Категории добавлены в товар");
		return true;

	} // addProductCategories()


	/**
	 * ver 6
	 * update 2017-09-07
	 * Обновляет в товаре категории
	 */
	private function updateProductCategories($product_id, $product_categories) {

		//$this->log($product_categories, 2);

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
		$sql .= "  FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . (int)$product_id;
		$sql .= $main_category ? " ORDER BY `main_category` DESC" : "";
		$query = $this->query($sql);

		foreach ($query->rows as $category) {
			$old_categories[] = $category['category_id'];
		}

		foreach ($product_categories as $index => $category_id) {
			$key = array_search($category_id, $old_categories);
			if ($key !== false) {
				unset($old_categories[$key]);
				$this->log("Категория уже есть в товаре, category_id=" . $category_id, 2);
			} else {
				// старой такой нет категориии
				$sql  = "INSERT INTO `" . DB_PREFIX . "product_to_category` SET `product_id` = " . (int)$product_id . ", `category_id` = " . (int)$category_id;
				if ($main_category) {
					$sql .= $index == 0 ? ", `main_category` = 1" : ", `main_category` = 0";
				}
				$this->query($sql);
				$this->log("Категория добавлена в товар, category_id=" . $category_id, 2);
			}
		}

		// Если категории товара перезаписывать, тогда удаляем которых нет в торговой системе
		//if ($this->config->get('exchange1c_product_categories') == 'overwrite') {
			// Старые неиспользуемые категории удаляем
			if (count($old_categories) > 0) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " . (int)$product_id . " AND `category_id` IN (" . implode(",",$old_categories) . ")");
				$this->log("Удалены старые категории товара, id: " . implode(",",$old_categories), 2);
			}
		//}

		return true;

	} // updateProductCategories()


	/**
	 * ver 3
	 * update 2017-09-07
	 * Отзывы парсятся с Яндекса в 1С, а затем на сайт
	 * Доработка от SunLit (Skype: strong_forever2000)
	 * Устанавливает отзывы в товар из массива
	 */
	private function setProductReview($product_id, $data) {

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
				$this->log("Отзыв от '" . $this->db->escape($property['name']) . "' записан в товар id: " . $product_id, 2);
			}
		}
		$this->log("Отзывы товаров обработаны", 2);

	} // setProductReview()


	/**
	 * ver 21
	 * update 2018-11-05
	 * Обновляет товар в базе поля в таблице product
	 * Если есть характеристики, тогда получает общий остаток по уже загруженным характеристикам прибавляет текущий и обновляет в таблице product
	 */
	private function updateProduct($product_id, $data) {

		$old_data = $this->getProduct($product_id);
		$this->log($old_data, 2);

		$no_update = array();

		// Перед загрузкой товара, нужно удалить все старые характеристики, цены, остатки и опции в товаре
		if (!$data['delete']) {
			$this->query("UPDATE `" . DB_PREFIX . "product_feature` SET `status` = 0 WHERE `product_id` = " . $product_id);
			$this->query("UPDATE `" . DB_PREFIX . "product_feature_value` SET `status` = 0 WHERE `product_id` = " . $product_id);
			$this->query("DELETE FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = '" . (int)$product_id . "'");
			$this->query("DELETE FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = '" . (int)$product_id . "'");
			$this->query("UPDATE `" . DB_PREFIX . "product_to_1c` SET `delete` = 0 WHERE `product_id` = " . $product_id);
		} else {
			if ($this->config->get('exchange1c_product_delete_mode') == 'disable') {
				// Товар помеченный на удаление не обрабатываем, просто отключаем
				$this->query("UPDATE `" . DB_PREFIX . "product` SET `status` = 0 WHERE `product_id` = " . $product_id);
				$this->query("UPDATE `" . DB_PREFIX . "product_to_1c` SET `delete` = 1 WHERE `product_id` = " . $product_id);
				return;
			}
		}

		// Статус на складе при наличии и отсутствии
		if ($old_data['quantity']) {
			if ((int)$this->config->get('exchange1c_product_stock_status_on')) {
				$data['stock_status_id'] = (int)$this->config->get('exchange1c_product_stock_status_on');
			}
		} else {
			if ((int)$this->config->get('exchange1c_product_stock_status_off')) {
				$data['stock_status_id'] = (int)$this->config->get('exchange1c_product_stock_status_off');
			}
		}

		// ФИЛЬТР ОБНОВЛЕНИЯ
		// Наименование товара
		if ($data['name'] == '' || $this->config->get('exchange1c_product_name') == 'disable') {
			$no_update[] = 'name';
			$this->log("[i] Обновление названия отключено", 2);
		}

		// ПРОИЗВОДИТЕЛИ ТОВАРА
		if ($this->config->get('exchange1c_product_manufacturer_no_import') == 1) {
			$this->log("[i] Обновление производителя отключено", 2);
		} elseif (isset($data['manufacturer_name'])) {
			$data['manufacturer_id'] = $this->setManufacturer(htmlspecialchars($data['manufacturer_name']));
		}

		// КАРТИНКИ
		if ($this->config->get('exchange1c_product_images_no_import') == 1) {
			if (isset($data['image']))
				unset($data['image']);
			if (isset($data['images']))
				unset($data['images']);
			$this->log("[i] Обновление картинок отключено", 2);
		}

		// Штрихкод для характеристики не обновляем в товаре
		if (isset($data['ean']) && $data['feature_guid']) {
			$no_update[] = 'ean';
			$this->log("[i] Штрихкод для характеристики '" . $data['feature_guid'] . "', в товар не записваем", 2);
		}
		// КОНЕЦ ФИЛЬТРА

		// Чтобы на ocstore не было ошибок если в поле image будет NULL
		if (empty($old_data['image']) && empty($data['image'])) {
			$data['image'] = 'no_image.png';
		}

		// Сверим что нужно обновдять
		$modify_fields1 = $this->compareArraysData($data, $old_data, $no_update);

		// Формируем SEO для товара и получаем поля которые изменились
		if ($this->config->get('exchange1c_seo_manufacturer_mode') != 'disable') {
			$modify_fields2 = $this->seoGenerateProduct($product_id, $data);
		} else {
			$modify_fields2 = array();
		}

		$modify_fields = array_merge($modify_fields1, $modify_fields2);

		// Формируем поля для обновления таблицы product
		$update_fields = $this->prepareQueryProduct($modify_fields, 'set');
		//$this->log($update_fields, 2);

		if ($update_fields) {
			$this->query("UPDATE `" . DB_PREFIX . "product` SET " . $update_fields . ", `date_modified` = '" . $this->NOW . "' WHERE `product_id` = " . (int)$product_id);
			$this->log("В таблице product обновлены поля: " . $update_fields);
		} elseif ($modify_fields) {
			// Если было хоть одно изменение, пропишем дату обновления товара
			$this->query("UPDATE `" . DB_PREFIX . "product` SET `date_modified` = '" . $this->NOW . "' WHERE `product_id` = " . (int)$product_id);
		}
		$this->log($modify_fields, 2);

		// Обновляем описание
		$update_fields = $this->prepareQueryDescription($modify_fields, 'set');
		//$this->log($update_fields, 2);
		if ($update_fields) {
			$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $update_fields . " WHERE `product_id` = " . $product_id);
			$this->log("В таблице product_description обновлены поля: " . $update_fields);
		}

		// Освободим память
		unset($update_fields);

		// Записываем атрибуты в товар
		if (isset($data['attributes']) && $this->config->get('exchange1c_product_attribute_not_import') != 1) {
			$this->updateProductAttributes($product_id, $data['attributes']);
			if ($this->ERROR) return false;
		}

		// Отзывы парсятся с Яндекса в 1С, а затем на сайт
		// Доработка от SunLit (Skype: strong_forever2000)
		// Записываем отзывы в товар
		if (isset($data['review'])) {
			$this->setProductReview($product_id, $data);
			if ($this->ERROR) return false;
		}

		// КАТЕГОРИИ
		if (isset($data['categories'])) {
			// Заполнение родительских категорий в товаре
			if ($this->config->get('exchange1c_fill_parent_cats') == 1) {
				$this->fillParentsCategories($data['categories']);
				if ($this->ERROR) return false;
			}
			$this->updateProductCategories($product_id, $data['categories']);
			if ($this->ERROR) return false;
		}

		// ДОПОЛНИТЕЛЬНЫЕ КАРТИНКИ
		if (isset($data['images'])) {
			$this->setProductImages($product_id, $data['images']);
			if ($this->ERROR) return false;
		}

		// Если есть характеристика
		$product_feature_id = isset($data['product_feature_id']) ? $data['product_feature_id'] : 0;

		// Очистим кэш товаров
		//$this->cache->delete('product');

	} // updateProduct()


	/**
	 * ver 8
	 * update 2018-11-07
	 * Устанавливает цены товара (акции, скидки)
	 */
	private function setProductPrice($table_price, $price, $product_id, $customer_group_id, $old_prices) {

		if ($table_price == 'discount') {

			// Поищем старую цену
			foreach ($old_prices['discount'] as $old_price) {
				if ($old_price['customer_group_id'] == $customer_group_id) {
					if ($old_price['price'] != $price) {
						$this->query("UPDATE `" . DB_PREFIX . "product_discount` SET `price` = '" . $data_price['price'] . "' WHERE `product_discount_id` = '" . $old_price['product_discount_id'] . "'");
					}
					return;
				}
			}
			$this->log("Цена скидки '" . $data_price['keyword'] . "' = " . $data_price['price']);
			$this->query("INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = " . (int)$product_id . ", `quantity` = " . (float)$data_price['quantity'] . ", `priority` = " . (int)$data_price['priority'] . ", `customer_group_id` = " . (int)$customer_group_id . ", `price` = '" . (float)$price . "'");

		} elseif ($table_price == 'special') {

			// Поищем старую цену
			foreach ($old_prices['special'] as $old_price) {
				if ($old_price['customer_group_id'] == $customer_group_id) {
					if ($old_price['price'] != $price) {
						$this->query("UPDATE `" . DB_PREFIX . "product_special` SET `price` = '" . $data_price['price'] . "' WHERE `product_special_id` = '" . $old_price['product_special_id'] . "'");
					}
					return;
				}
			}
			$this->log("Цена акции '" . $data_price['keyword'] . "' = " . $data_price['price']);
			$this->query("INSERT INTO `" . DB_PREFIX . "product_special` SET `product_id` = " . (int)$product_id . ", `priority` = " . (int)$data_price['priority'] . ", `customer_group_id` = " . (int)$data_price['customer_group_id'] . ", `price` = '" . (float)$data_price['price'] . "', `date_start` = '" . $this->NOW . "'");

		}

	} // setProductPrice()


	/**
	 * ver 6
	 * update 2018-11-07
	 */
	private function getProductPrices($product_id) {

		$data_price = array();
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = " . $product_id);
		$data_price['discount'] = array();
		foreach ($query->rows as $discount) {
			$data_price['discount'][$discount['product_discount_id']] = array(
				'product_discount_id'		=> $discount['product_discount_id'],
				'customer_group_id'		=> $discount['customer_group_id'],
				'quantity'				=> $discount['quantity'],
				'priority'				=> $discount['priority'],
				'price'					=> $discount['price']
			);
		}

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_special` WHERE `product_id` = " . $product_id);
		$data_price['special'] = array();
		foreach ($query->rows as $special) {
			$data_price['special'][$special['product_special_id']] = array(
				'product_special_id'		=> $special['product_special_id'],
				'customer_group_id'		=> $special['customer_group_id'],
				'price'					=> $special['price'],
				'date_start'			=> $special['date_start'],
				'date_end'				=> $special['date_end']
			);
		}

		return $data_price;

	} // getProductPrices()


    /**
     * ver 1
     * update 2017-11-27
     * Читает из базы значения опций товара
     */
    private function getProductOptionsValues($product_id) {

        $options = array();
        $query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`pov`.`option_value_id` = `ovd`.`option_value_id`) WHERE `pov`.`product_id` = " . (int)$product_id);
        foreach ($query->rows as $option) {

			$options[$option['option_id']] = array(
				'option_id'			=> $option['option_id'],
				'option_value_id'	=> $option['option_value_id'],
				'quantity'			=> $option['quantity'],
				'subtract'			=> $option['subtract'],
				'price'				=> $option['price'],
				'price_prefix'		=> $option['price_prefix'],
				'points'			=> $option['points'],
				'points_prefix'		=> $option['points_prefix'],
				'weight'			=> $option['weight'],
				'weight_prefix'		=> $option['weight_prefix']
			);
        }

        $this->log($options, 2);
        return $options;

	} // getProductOptionsValues()


    /**
     * ver 2
     * update 2018-06-20
     * Анализирует изменения цены товара и пересчитывает опции
     * $price - текущая цена товара
     */
    private function setProductMinPrice($product_id, $price) {

		$product_options = $this->getProductOptions($product_id);
		$value_prices = array();
		$price_min = 0;
		foreach ($product_options as $value_id => $option) {
			foreach ($option['values'] as $value) {
				$value_price = ($value['price_prefix'] == '-') ? -$value['price'] : $value['price'];
				$value_prices[$value_id] = $value_price;

				if ($value_price > 0) {
					if ($price_min == 0) {
						$price_min = $value_price;
					} else {
						$price_min = min($price_min, $value_price);
					}
				}
			}
			$this->log("Минимальная цена: " . $price_min);

			// Если изменилась минимальная цена, пересчитываем опции
			if ($price_min < $price || ($price == 0 && $price_min > $price)) {
				$option_value = array();
				$price_offset = $price - $price_min;
				$this->log("Цена товара изменилась на: " . $price_offset, 2);
				foreach ($value_prices as $value_id => $value_price) {
					$option_price = $value_price + $price_offset;
					if ($option_price < 0) {
						$option_value['price_prefix'] = '-';
					} elseif ($option_price == 0) {
						$option_value['price_prefix'] = '';
					} elseif ($option_price > 0) {
						$option_value['price_prefix'] = '+';
					}
					$option_value['price'] = abs($option_price);
					$product_option_value_id = $this->setProductOptionValue(
						$product_id,
						$option['product_option_id'],
						$option['option_id'],
						$option['values'][$value_id]['option_value_id'],
						$option_value
					);
				}

				// Минимальную цену отправим в товар
				$price = $price_min;
			}

		}
		return $price;

	} // setProductMinPrice()


	/**
	 * ver 1
	 * update 2018-10-22
	 */
	private function setProductFeatureQuantity($product_id, $product_feature_id, $quantity) {
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id . " AND `product_feature_id` = " . $product_feature_id);
		if ($query->num_rows) {
			if ($query->row['quantity'] != $quantity) {
				// Если отличется количество, обновляем
				$this->query("UPDATE `" . DB_PREFIX . "product_quantity` SET `quantity` = ". $quantity ." WHERE `product_quantity_id` = " . $query->row['quantity']);
			}
			return $query->row['quantity'];
		} else {
			// Нет остатков, добавляем
			$this->query("INSERT INTO `" . DB_PREFIX . "product_quantity` SET `quantity` = ". $quantity .", `product_id` = " . $product_id . ", `product_feature_id` = " . $product_feature_id);
			return $this->db->getLastId();
		}
	} // setProductFeatureQuantity()


	/**
	 * ver 1
	 * update 2018-10-22
	 */
	private function getProductFeatureQuantity($product_id) {
		$query = $this->query("SELECT SUM(`quantity`) as `total` FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . $product_id);
		if ($query->num_rows) {
			return $query->row['total'];
		} else {
			return 0;
		}
	} // getProductFeatureQuantity()


	/**
	 * ver 23
	 * update 2018-11-07
	 * Обновляет товар в базе поля в таблице product
	 * Если есть характеристики, тогда получает общий остаток по уже загруженным характеристикам прибавляет текущий и обновляет в таблице product
	 */
	private function updateOffers($product_id, $data, $old_data) {

		$this->log("~");

		$this->log("Старые данные:", 2);
		$this->log($old_data, 2);

		$data['date_modified'] = $this->NOW;

		$this->log("Новые данные:", 2);
		$this->log($data, 2);

		if ($data['feature_guid']) {
			$no_update = array('name');
		} else {
			$no_update = array();
		}

		$update_fields = $this->compareArraysData($data, $old_data, $no_update);

		$this->log("Обновляемые поля:", 2);
		$this->log($update_fields, 2);

		if ($update_fields) {
			$sql = $this->prepareQueryProduct($update_fields, 'set');
			if ($sql) {
				$this->query("UPDATE `" . DB_PREFIX . "product` SET " . $sql . " WHERE `product_id` = " . $product_id);
			}
			$sql = $this->prepareQueryDescription($update_fields, 'set');
			if ($sql) {
				$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $sql . " WHERE `product_id` = " . $product_id . " AND `language_id` = " . $this->LANG_ID);
			}
		}

	} // updateOffers()


	/**
	 * Устанавливает описание товара в базе для одного языка
	 */
	private function setProductDescription($data, $new = false) {

		$this->log("Обновление описания товара");

		if (!$new) {
			$select_fields = $this->prepareQueryDescription($data, 'get');
			$update_fields = false;
			if ($select_fields) {
				$query = $this->query("SELECT " . $select_fields . " FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = " . $data['product_id'] . " AND `language_id` = " . $this->LANG_ID);
				if ($query->num_rows) {
					// Сравнивает запрос с массивом данных и формирует список измененных полей
					$update_fields = $this->compareArrays($query, $data);
				} else {
					$new = true;
				}
			}
			// Если есть расхождения, производим обновление
			if ($update_fields) {
				$this->query("UPDATE `" . DB_PREFIX . "product_description` SET " . $update_fields . " WHERE `product_id` = " . $data['product_id'] .  " AND `language_id` = " . $this->LANG_ID);
				$this->log("Описание товара обновлено, поля: '" . $update_fields . "'",2);
				return true;
			}
		}
		if ($new) {
			$insert_fields = $this->prepareQueryDescription($data, 'set');
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
	 * Получает product_id по модели
	 */
	private function getProductByModel($model) {

		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `model` = '" . $this->db->escape($model) . "'");
		if ($query->num_rows) {
	 		$this->log("Найден product_id: " . $query->row['product_id'] . " по модели '" . $model . "'",2);
			return $query->row['product_id'];
		}
		$this->log("Не найден товар по модели '" . $model . "'",2);
		return 0;

	} // getProductByModel()


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
	 * ver 13
	 * update 2018-11-12
	 * Обновление или добавление товара
	 * вызывается при обработке каталога
	 */
 	private function setProduct(&$data) {

		// СВЯЗЬ
		$product_id = $this->searchProduct($data);
		$check_link = false;

		if (!$product_id) {
			// Синхронизация по артикулу
	 		if ($this->config->get('exchange1c_product_sync_mode') == 'sku') {
	 			$this->log("Поиск товара по артикулу: " . $data['sku'], 2);
				if (empty($data['sku'])) {
 					$this->log("ВНИМАНИЕ! Артикул пустой! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					return false;
 				}
				$product_id = $this->getProductBySKU($data['sku']);
				if ($product_id)
					$check_link = true;

			// Синхронизация по модели
	 		} elseif ($this->config->get('exchange1c_product_sync_mode') == 'model' && isset($data['model'])) {
	 			$this->log("Поиск товара по модели: " . $data['model'], 2);
				if (empty($data['model'])) {
 					$this->log("ВНИМАНИЕ! пустое поле Модель! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					return false;
 				}
				$product_id = $this->getProductByModel($data['model']);
				if ($product_id)
					$check_link = true;

			// Синхронизация по наименованию
			} elseif ($this->config->get('exchange1c_product_sync_mode') == 'name') {
				$this->log("Поиск товара по наименованию: " . $data['name'], 2);
				if (empty($data['name'])) {
 					$this->log("ВНИМАНИЕ! Наименование пустое! Товар пропущен. Проверьте товар Ид: " . $data['product_guid'], 2);
					// Пропускаем товар
					return false;
				}
				$product_id = $this->getProductByName($data['name']);
				if ($product_id)
					$check_link = true;

			// Синхронизация по штрихкоду
			} elseif ($this->config->get('exchange1c_product_sync_mode') == 'ean') {
				$this->log("Поиск товара по штрихкоду: " . $data['ean'], 2);
 				if (empty($data['ean'])) {
 					$this->log("ВНИМАНИЕ! Штрихкод пустой! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					return false;
 				}
				$product_id = $this->getProductByEan($data['ean']);
				if ($product_id)
					$check_link = true;

			// Синхронизация по коду
			} elseif ($this->config->get('exchange1c_product_sync_mode') == 'code' && isset($data['code'])) {
				$this->log("Поиск товара по коду: " . $data['code'], 2);
 				if (empty($data['code'])) {
 					$this->log("ВНИМАНИЕ! Код товара пустой! Товар пропущен. Проверьте товар " . $data['name'], 2);
 					return false;
 				}
				//$product_id = $this->getProductById($data['code']);
 			}
		}

		// ОСНОВНУЮ КАРТИНКУ ЗАПИШЕМ В ТОВАР
		if (isset($data['images'][0])) {
			$data['image'] = $data['images'][0];
			unset($data['images'][0]);
		}

		// Если не найден товар...
 		if (!$product_id) {

 			if ($this->config->get('exchange1c_product_no_create') == 1) {
				$this->log("Отключено добавление новых товаров!");
				return false;

			} else {

 				$product_id = $this->addProduct($data);
 				if ($this->ERROR) return false;
				$this->log("Это новый товар, product_id = " . $product_id);
			}

 		} else {

			unset($data['product_id']);
 			$this->log("Товар существует");

 			$this->updateProduct($product_id, $data);
			if ($this->ERROR) return false;

			if ($check_link) {

				$this->log("Проверка связи id->Ид");
				// Проверим связь
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = '" . (int)$product_id . "'");
				if (!$query->num_rows > 1) {
					$this->log("ВНИМАНИЕ! у товара большей одной связи с ИД учетной системы (1С):");
					foreach ($query->rows as $row) {
						$this->log("GUID: " . $row['guid']);
					}
				}
				if (!$query->num_rows) {
					// Связь с 1С только по Ид объекта из торговой системы
					$sql = "INSERT INTO `" . DB_PREFIX . "product_to_1c` SET `product_id` = " . (int)$product_id . ", `guid` = '" . $this->db->escape($data['product_guid']) . "'";
					if (isset($data['version'])) {
						$sql .= ", `version` = '" . $this->db->escape($data['version']) ."'";
						$this->log("Версия товара в УС: " . $data['version'], 2);
					}
					$this->query($sql);
				} else {
					// связь есть, проверим
					if ($query->row['guid'] != $data['product_guid']) {
						$this->query("UPDATE `" . DB_PREFIX . "product_to_1c` SET `guid` = '" . $this->db->escape($data['product_guid']) . "' WHERE `product_id` = " . (int)$product_id);
					}
				}

			}
 		}

		// SEO формируем когда известен product_id и товар записан
		//$update = $this->seoGenerateProduct($data);
		//if ($this->ERROR) return false;

//		if ($update || $new) {
//			// Обновляем описание товара после генерации SEO
//			$this->setProductDescription($data, $new);
//		}

 		return $product_id;

 	} // setProduct()


	/**
	 * ver 4
	 * update 2018-05-09
	 * Читает реквизиты товара из XML в массив данных
	 */
	private function parseRequisite($xml, &$data) {

		$this->log("Начато чтение реквизитов...", 2);
		//$this->log($xml, 2);
		$count = 0;
		foreach ($xml->ЗначениеРеквизита as $requisite) {
			//$this->log($requisite, 2);
			$count	++;
			$name 	= trim((string)$requisite->Наименование);
			$value 	= trim((string)$requisite->Значение);

			switch ($name){
				case 'Вес':
					$data['weight'] = $value ? (float)str_replace(',','.',$value) : 0;
					$this->log("> Реквизит: " . $name. " => weight",2);
				break;
				case 'ОписаниеВФорматеHTML':
					if ($value && $this->config->get('exchange1c_product_description_no_import') != 1) {
						$data['description'] =  $value;
						$this->log("> Реквизит: " . $name, 2);
					}
				break;
				case 'Полное наименование':
					if ($value && $this->config->get('exchange1c_product_name') == 'fullname') {
						$data['name'] = htmlspecialchars($value);
						$this->log("Наименование товара установлено из реквизита: " . $name . " = " . $value, 2);
					}
				break;
				case 'Производитель':
					// Устанавливаем производителя из свойства только если он не был еще загружен в секции Товар
					if ($this->config->get('exchange1c_product_manufacturer_no_change') == 0 && empty($data['manufacturer_id'])) {
						$data['manufacturer_name'] = htmlspecialchars($value);
						$data['manufacturer_id'] = $this->setManufacturer($data['manufacturer_name']);
						$this->log("> Реквизит: " . $name . " = " . $data['manufacturer_name'], 2);
					}
				break;
				case 'Код':
					$data['code'] = $this->parseCode($value);
					$this->log("> Реквизит: " . $name . " преобразован в " . $data['code'], 2);
				break;
				case 'ISBN':
					$data['isbn'] = htmlspecialchars($value);
					$this->log("> Реквизит: " . $name . " = " . $data['isbn'], 2);
				break;
			} // switch
		} // foreach()

		$this->log("Реквизитов прочитано: " . $count, 2);

	} // parseRequisite()


	/**
	 * ver 12
	 * update 2018-10-09
	 * Устанавливает дополнительные картинки в товаре
	 */
	private function setProductImages($product_id, $images_data, $new = false) {

		$old_images = array();
		if (!$new) {
			if ($this->FULL_IMPORT) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = " . (int)$product_id);
				$this->log("Удалены старые картинки из товара", 2);
			} else {
				// Прочитаем  все старые картинки
				$query = $this->query("SELECT `product_image_id`,`image` FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = " . (int)$product_id);
				foreach ($query->rows as $image) {
					$old_images[$image['product_image_id']] = $image['image'];
				}
			}
		}

		foreach ($images_data as $index => $image) {

			if (file_exists(DIR_IMAGE . $image)) {
				// Удалим эту картинку в кэше
				$image_info = pathinfo(DIR_IMAGE . $image);
				$this->deleteCacheImage($image_info);
			}

			// Основная картинка
			if ($index == 0) continue;

			$this->log("Картинка: " . $image);

			// Установим картинку в товар, т.е. если нет - добавим, если есть возвратим product_image_id
			$product_image_id = array_search($image, $old_images);
			if (!$product_image_id) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_image` SET `product_id` = " . $product_id . ", `image` = '" . $this->db->escape($image) . "', `sort_order` = " . $index);
				$this->log("Добавлена картинка: " . $image);
			} else {
				if (!$new) {
					unset($old_images[$product_image_id]);
				}
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
					$this->log("Удален старый файл: " . DIR_IMAGE . $image);
				}
			}
			$count_image = count($delete_images);
			if ($count_image) {
				$this->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE `product_image_id` IN (" . implode(",",$delete_images) . ")");
				$this->log("Удалены старые картинки: " . $count_image);
			}
		}

	} // setProductImages()


	/**
	 * ver 3
	 * update 2017-06-04
	 * Удаляет в кэше эту картинку
	 */
	private function deleteCacheImage($image_info) {

		if (!$image_info) {
			// Нечего удалять
			return false;
		}

		// Путь в папке кэш к картинке
		$path = str_replace(DIR_IMAGE, DIR_IMAGE . "cache/" , $image_info['dirname']);

		// Откроем папку для чтения
		$delete_files = array();
		$dh = @opendir($path);

		// Если каталог не открывается
		if (!$dh) {
			$this->log("Каталог не существует: " . $path);
			return false;
		}

		while(($file = readdir($dh)) !== false) {
			$find = strstr($file, $image_info['filename']);
			if ($find != "") {
				$delete_files[] = $find;
			}
		}
		closedir($dh);

		if ($delete_files) {
			foreach ($delete_files as $filename) {
				if (file_exists($path . "/" . $filename)) {
					unlink($path . "/" . $filename);
				}
				$this->log("Удалена картинка из кэша: " . $filename);
			}
		}

		return true;

	} // deleteCacheImage()


	/**
	 * ver 9
	 * update 2018-05-14
	 * Читает картинки из XML в массив
	 */
	private function parseImages($xml) {

		$images = array();

		foreach ($xml as $image) {

			$image = (string)$image;

			// Пропускаем файл с пустым именем
			if (empty($image)) {
				$this->log("Пустое наименование картинки, пропуск.", 2);
				continue;
			}

			if (!file_exists(DIR_IMAGE . $image) && $this->config->get('exchange1c_product_images_check')) {

				// Пропускаем несуществующие файлы если включено в настройках
				$this->log("файл не существует, согласно настройкам не будет записан в товар", 2);
				continue;

			}

			$this->log("Картинка: " . $image, 2);
			$images[] = $image;

		} // foreach()

		return $images;

	} // parseImages()


	/**
	 * ver 1
	 * update 2017-06-24
	 * Добавляет группу атрибутов
	 */
	private function addAttributeGroup($name) {

		// Добавляем группу
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_group` SET `sort_order` = 1");

		// Получаем id добавленной группы
		$attribute_group_id = $this->db->getLastId();

		// Добавляем наименование для текущего языка
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_group_description` SET `attribute_group_id` = " . $attribute_group_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($name) . "'");

   		$this->log("Группа атрибута добавлена: '" . $name . "', attribute_group_id = " . $attribute_group_id, 2);

		return $attribute_group_id;

	} // addAttributeGroup()


	/**
	 * ver 4
	 * update 2017-08-23
	 * Читает все категории из базы данных в массив, где ключем является GUID
	 */
	private function getCategories() {

		$categories = array();
		//$query = $this->query("SELECT `c`.`category_id`,`c1c`.`guid`,`cd`.`name`,`c`.`status`,`c`.`parent_id`,`c`.`top`,`c1c`.`version` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_to_1c` `c1c` ON (`c`.`category_id` = `c1c`.`category_id`) LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) WHERE `cd`.`language_id` = " . $this->LANG_ID);
		$query = $this->query("SELECT `category_id`,`guid`,`version` FROM `" . DB_PREFIX . "category_to_1c`");
		if ($query->num_rows) {
			foreach($query->rows as $row) {
				$categories[$row['guid']] = array(
					'category_id'	=> $row['category_id'],
					'version'		=> $row['version']
				);
			}
		}
		if ($categories) {
			$this->log("Категорий в базе: " . count($categories));
		}
		return $categories;

	} // getCategories()


	/**
	 * ver 7
	 * update 2018-11-05
	 * Читает свойства из базы данных в массив
	 */
	private function getAttributes() {

		$data = array();

		//$query_attribute = $this->query("SELECT `a`.`attribute_id`, `a`.`attribute_group_id`, `ad`.`name`, `a2c`.`guid`,`a2c`.`type`,`a2c`.`version` FROM `" . DB_PREFIX . "attribute` `a` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) LEFT JOIN `" . DB_PREFIX . "attribute_to_1c` `a2c` ON (`a`.`attribute_id` = `a2c`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID);

		$query = $this->query("SELECT `a1c`.`attribute_id`, `a1c`.`guid`, `a1c`.`version`, `ad`.`name` FROM `" . DB_PREFIX . "attribute_to_1c` `a1c` LEFT JOIN `" . DB_PREFIX . "attribute_description` `ad` ON (`a1c`.`attribute_id` = `ad`.`attribute_id`) WHERE `ad`.`language_id` = " . $this->LANG_ID);

		if ($query->num_rows) {

			foreach ($query->rows as $attribute) {

				if (!isset($data[$attribute['guid']])) {

					$data[$attribute['guid']] = array(
						'attribute_id'			=> $attribute['attribute_id'],
						'version'				=> $attribute['version'],
						'name'					=> $attribute['name'],
						'values'				=> array()
					);

				} // if (!isset($data[$attribute['guid']]))

			} // foreach ($query->rows as $attribute)

		}

		if ($data) {
			$this->log("Атрибутов в базе: " . count($data));
		}

		return $data;

	}  // getAttributes()


	/**
	 * ver 5
	 * update 2018-11-07
	 * Добавляет атрибут в базу
	 */
	private function addAttribute($data, $values) {

		// Добавляем
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute` SET `attribute_group_id` = " . $data['attribute_group_id'] . ", `sort_order` = 0");

		// Получим id добавленного атрибута
		$attribute_id = $this->db->getLastId();

		$this->ATTRIBUTES[$data['guid']] = array(
			'attribute_id'			=> $attribute_id,
			'version'				=> $data['version'],
			'name'					=> $data['name']
		);

		// Добавляем наименование атрибута на текущем языке
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_description` SET `attribute_id` = " . $attribute_id . ", `language_id` = " . $this->LANG_ID . ", `name` = '" . $this->db->escape($data['name']) . "'");

        $this->log("Атрибут добавлен: attribute_id = " . $attribute_id, 2);

		// Добавим связь с УС
		if ($data['values_type'] == 'Справочник') {
			$type = "R";
		} elseif ($data['values_type'] == 'Строка') {
			$type = "S";
		} elseif ($data['values_type'] == 'Число') {
			$type = "N";
		} else {
			$type = "U";
		}
		$this->query("INSERT INTO `" . DB_PREFIX . "attribute_to_1c` SET `attribute_id` = " . $attribute_id . ", `guid` = '" . $this->db->escape($data['guid']) . "', `type` = '" . $type . "', `version` = '" . $data['version'] . "'");

		// Значения атрибута
		if ($values) {
			$this->log($values, 2);
			$this->query("DELETE FROM `" . DB_PREFIX . "attribute_value_to_1c` WHERE `attribute_id` = '" . $attribute_id . "'");
			foreach ($values as $guid => $name) {
				$this->query("INSERT INTO `" . DB_PREFIX . "attribute_value_to_1c` SET `attribute_id` = '" . $attribute_id . "', `guid` = '" . $this->db->escape($guid) . "', `name` = '" . $this->db->escape($name) . "'");
			}
		}

		return $attribute_id;

	} // addAttribute()


	/**
	 * ver 2
	 * update 2018-11-06
	 * Получает атрибут из базы
	 */
	private function getAttribute($attribute_id) {

		$data = array();
		$query = $this->query("SELECT `a`.`attribute_group_id`, `a`.`sort_order`, `ad`.`name` FROM `" .  DB_PREFIX . "attribute` `a` LEFT JOIN `" .  DB_PREFIX . "attribute_description` `ad` ON (`a`.`attribute_id` = `ad`.`attribute_id`) WHERE `a`.`attribute_id` = " . $attribute_id . " AND `ad`.`language_id` = " . $this->LANG_ID);
		if ($query->num_rows) {
			$data['attribute_group_id'] = $query->row['attribute_group_id'];
			$data['sort_order'] 		= $query->row['sort_order'];
			$data['name'] 				= $query->row['name'];
		}

		return $data;

	} // getAttribute()


	/**
	 * ver 1
	 * update 2018-11-06
	 * Получает значения атрибута из базы
	 */
	private function getAttributeValues($attribute_id) {

		$data = array();
		$query = $this->query("SELECT * FROM `" .  DB_PREFIX . "attribute_value_to_1c` WHERE `attribute_id` = " . $attribute_id);
		foreach ($query->rows as $row) {
			$data[$row['guid']] = $row;
		}

		return $data;

	} // getAttributeValues()


	/**
	 * ver 5
	 * update 2018-05-15
	 * Обновляет атрибут в базе если есть изменения
	 */
	private function updateAttribute($attribute_id, $data, $values) {

		// Удалим старые значения атрибута
		$this->query("DELETE FROM `" . DB_PREFIX . "attribute_value_to_1c` WHERE `attribute_id` = '" . $attribute_id . "'");

		// Получим старые данные
		$old_data = $this->getAttribute($attribute_id);
		//$this->log($old_data, 2);

		// Сравним имя атрибута
		if ($data['name'] != $old_data['name']) {
			$this->log("Старый атрибут '" . $old_data['name'] . "'");
			$this->log("Новый атрибут '" . $data['name'] . "'");
			$this->query("UPDATE `" . DB_PREFIX . "attribute_description` SET `name` = '" . $this->db->escape($data['name']) . "' WHERE `attribute_id` = " . $attribute_id);
		}

		foreach ($values as $guid => $value) {
			// Добавить значение
			$this->query("INSERT INTO `" . DB_PREFIX . "attribute_value_to_1c` SET `name` = '" . $this->db->escape($value) . "', `guid` = '" . $this->db->escape($guid) . "', `attribute_id` = " . (int)$attribute_id);
			$attribute_value_id = $this->db->getLastId();
		}

		// Если включено всегда обновлять группу атрибута
		if ($this->config->get('exchange1c_attribute_group_mode') == 'always' && $old_data['attribute_group_id'] != $data['attribute_group_id']) {
			if (isset($data['attribute_group_id'])) {
				$this->query("UPDATE `" . DB_PREFIX . "attribute` SET `attribute_group_id` = '" . $data['attribute_group_id'] . "' WHERE `attribute_id` = " . $attribute_id);
			} else {
				$this->log("ВНИМАНИЕ! Невозможно обновить группу атрибута, так как не передан id группы");
				$this->errorLog(2050, $attribute_id);
			}
		}

	} // updateAttribute()


	/**
	 * ver 1
	 * update 2017-06-24
	 * Читает группы свойств из базы данных в массив
	 */
	private function getAttributeGroups() {

		$data = array();

		//$query = $this->query("SELECT `ag`.`attribute_group_id`, `ag`.`sort_order`, `agd`.`name` FROM `" . DB_PREFIX . "attribute_group` `ag` LEFT JOIN `" . DB_PREFIX . "attribute_group_description` `agd` ON (`ag`.`attribute_group_id` = `agd`.`attribute_group_id`) WHERE `agd`.`language_id` = " . $this->LANG_ID);
		$query = $this->query("SELECT `attribute_group_id`, `name` FROM `" . DB_PREFIX . "attribute_group_description` WHERE `language_id` = " . $this->LANG_ID);
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$data[$row['attribute_group_id']] = $row['name'];
			}
		}

		if ($data) {
			$this->log("Группы атрибутов в базе: " . count($data));
		}
		return $data;

	}  // getAttributeGroups()


	/**
	 * ver 1
	 * update 2018-03-06
	 * Загружает свойства для предложений в опции
	 */
	private function parseClassifierOptions($xml) {

    	foreach ($xml->Свойство as $property) {
   			$this->log('Свойство');
    		if ($property->ДляПредложений) {
	    		$name = htmlspecialchars((string)$property->Наименование);
	    		$type = (string)$property->ТипЗначений;
	    		if ($type == 'Справочник') {
	    			if (count($property->ВариантыЗначений) > 0) {
	    				$this->log("У свойства " . $name . " найдено " . count($property->ВариантыЗначений) . " значений");
	    			}
	    		}
    		}
   		}
	}  // parseClassifierOptions()


	/**
	 * ver 11
	 * update 2018-11-08
	 * Загружает атрибуты (Свойства из 1С) в классификаторе
	 */
	private function parseClassifierAttributes($xml) {

		$this->log("********************************************");
		// Читаем из базы группы атрибутов
		if (empty($this->ATTRIBUTE_GROUPS)) {
			$this->ATTRIBUTE_GROUPS = $this->getAttributeGroups();
		}

		// Для разных версий XML
		if ($xml->Свойство) {
			$properties = $xml->Свойство;
		} else {
			$properties = $xml->СвойствоНоменклатуры;
		}

		// Настройки фильтров значений свойств
		$types = $this->config->get("exchange1c_product_property_type_no_import");

		if (count($types) == 3) {
			$this->errorLog(2040);
			return 0;
		}

		// Копируем массив, чтобы понять какие атрибуты не используются
		if ($this->FULL_IMPORT) {
			$delete_attributes = $this->ATTRIBUTES;
		} else {
			$delete_attributes = array();
		}

		$manufacturer_tag = 'Производитель';
		if ($this->config->get('exchange1c_product_manufacturer_tag'))
			$manufacturer_tag = $this->config->get('exchange1c_product_manufacturer_tag');


		$this->log("Свойств в файле: " . count($properties));
		$this->STAT['attribute_num'] = count($properties);

		foreach ($properties as $property) {

			$this->log("********************************************");
			$guid			= (string)$property->Ид;

			$attribute_id	= isset($this->ATTRIBUTES[$guid]) ? $this->ATTRIBUTES[$guid]['attribute_id'] : 0;

			if ($property->ПометкаУдаления) {
				$delete = (string)$xml->ПометкаУдаления == 'true' ? true : false;
			} else {
				$delete = false;
			}

			$data = array(
				'name' 			=> htmlspecialchars(trim((string)$property->Наименование)),
				'version'		=> (string)$property->НомерВерсии,
				'external'		=> (string)$property->Внешний == 'true' ? true : false,
				'delete' 		=> $delete,
				'information' 	=> (string)$property->Информационное == 'true' ? true : false,
				'values_type'	=> (string)$property->ТипЗначений,
				'guid'			=> $guid
			);
			$this->log("Свойство: " . $data['name'] . ', GUID: ' . $data['guid']);

			// Для товаров
			if ($property->ДляТоваров) {
				if ((string)$property->ДляТоваров == 'true') {
					$this->log("Для товаров", 2);
				}
			}

			// Для предложений не записываем в атрибуты, а записываем в опции
			if ($property->ДляПредложений) {
				// Это своства характеристики
				if ((string)$property->ДляПредложений == 'true') {
					$this->log("Свойство " . $data['name'] . " для предложений, в атрибуты не будет загружено");
					continue;
				}
			}

			// Фильтр типов значений
			if ($types) {
				// Старые загруженные атрибуты будут удалены
				if ($data['values_type'] == "Число" && in_array("digit", $types)) {
					$this->log("Тип число - пропущено", 2);
					continue;
				} elseif ($data['values_type'] == "Строка" && in_array("string", $types)) {
					$this->log("Тип строка - пропущено", 2);
					continue;
				} elseif ($data['values_type'] == "Справочник" && in_array("reference", $types)) {
					$this->log("Тип справочник - пропущено", 2);
					continue;
				}
			}

			// Фильтр по таблице свойств
			$attribute_filter = $this->config->get('exchange1c_property');
			// Если есть хотя бы одна строка
			if ($attribute_filter) {
				foreach($attribute_filter as $filter) {
					if ($filter['name'] == $data['name'] && $filter['import'] != 1) {
						$this->log("Атрибут в таблице отключен");
						continue;
					}
				}
			}

			// Группа атрибутов
			$group_name = $this->config->get("exchange1c_attribute_group_name");

			// Определим название группы в название свойства в круглых скобках в конце названия
			if ($this->config->get('exchange1c_attribute_group_name_mode') == 'brackets') {
				$name_split = $this->splitNameStr($data['name'], false, true);
				// Если есть в конце текст в скобочках
				if ($name_split['option']) {
					// Название атрибуиа присвоим без скобочек
					$data['name']	= $name_split['name'];
					$group_name 	= $name_split['option'];
					$this->log("Название группы взято из круглых скобок: " . $group_name, 2);
				}
			}

			// Поищем группу для свойств по имени
			$attribute_group_id = array_search($group_name, $this->ATTRIBUTE_GROUPS);
			$this->log("Группа атрибута: " . $group_name . ", id: " . $attribute_group_id, 2);
			if (!$attribute_group_id) {
				$attribute_group_id = $this->addAttributeGroup($group_name);
				$this->ATTRIBUTE_GROUPS[$attribute_group_id] = $group_name;
			}
			$data['attribute_group_id'] = $attribute_group_id;

			// Пропускаем которые не используются
			if ($property->ИспользованиеСвойства) {
				if ((string)$property->ИспользованиеСвойства == 'false') {
					continue;
				}
			}

			// Обязательное
			if ($property->Обязательное) {
				if ((string)$property->Обязательное == 'true') {
					$this->log("Обязательное", 2);
				}
			}

			// Множественное
			if ($property->Множественное) {
				if ((string)$property->Множественное == 'true') {
					$this->log("Множественное", 2);
				}
			}

			$values = array();
			if ($property->ВариантыЗначений->Справочник) {
				foreach ($property->ВариантыЗначений->Справочник as $value) {
	 				$values[(string)$value->ИдЗначения] = htmlspecialchars(trim((string)$value->Значение));
				}
			}
			//$this->log($values, 2);

			switch ($data['name']) {

				// Прочитаем производителя
				case $manufacturer_tag:
					foreach ($values as $manufacturer_guid => $value) {
						$this->setManufacturer($value);
					}

				default:

					if ($attribute_id) {

						if (!$delete) {
							$this->log("Атрибут существует, attribute_id = " . $attribute_id);
							$this->updateAttribute($attribute_id, $data, $values);
							unset($delete_attributes[$guid]);
						}

					} else {

						$this->log("Атрибут отстутствует, добавляем...", 2);
						$this->log($data);
						$attribute_id = $this->addAttribute($data, $values);
						$data[$guid] = array(
							'attribute_id'	=> $attribute_id,
							'version'		=> $data['version'],
							'name'			=> $data['name']
						);
						$this->log("Атрибут добавлен, attribute_id = " . $attribute_id);
						$this->log($data[$guid], 2);

					}

			} // switch

		} // foreach ($properties as $property)


		if (count($delete_attributes)) {

			$this->log("********************************************");

			$delete_obj = array();
			$this->log($delete_attributes, 2);

			foreach ($delete_attributes as $attribute) {
				$delete_obj[] = $attribute['attribute_id'];
			}

			$where =  " WHERE `attribute_id` IN (" . implode(",",$delete_obj) . ")";

			$this->query("DELETE FROM `" .  DB_PREFIX . "attribute`" . $where);
			$this->query("DELETE FROM `" .  DB_PREFIX . "attribute_description`" . $where);
			$this->query("DELETE FROM `" .  DB_PREFIX . "attribute_value_to_1c`" . $where);

			$this->log("Атрибутов удалено: " . count($delete_attributes));
			$this->log("********************************************");
		}

		$this->log("Завершено чтение атрибутов из файла", 2);
		$this->log("********************************************");

	} // parseClassifierAttributes()


	/**
	 * ver 10
	 * update 2018-11-06
	 * Читает свойства товара из XML (товар, категория) и записывает их в массив
	 * $data - прочитанные данные о продукте из XML
	 */
	private function parseProductAttributes($xml, &$data) {

		$product_attributes = array();

		//$this->log($data, 2);

		if (empty($this->ATTRIBUTES)) {
			$this->ATTRIBUTES = $this->getAttributes(true);
			$this->ATTRIBUTE_GROUPS = $this->getAttributeGroups();
		}

		// Название элемента с производителем
		$manufacturer_tag = 'Производитель';
		if ($this->config->get('exchange1c_product_manufacturer_tag'))
			$manufacturer_tag = $this->config->get('exchange1c_product_manufacturer_tag');

		foreach ($xml->ЗначенияСвойства as $property) {

			// Ид объекта в 1С
			$attribute_guid 	= (string)$property->Ид;
			$attribute_value 	= htmlspecialchars(trim((string)$property->Значение));

			$this->log('attribute_guid = ' . $attribute_guid, 2);
			$this->log('attribute_value = ' . $attribute_value, 2);

			// Пропускаем с пустыми значениями
			if (empty($attribute_value)) {
				continue;
			}

			// Загружаем только те что в классификаторе
			if (!isset($this->ATTRIBUTES[$attribute_guid])) {

				$this->log("[i] Свойство не было загружено в классификаторе, Ид = " . $attribute_guid, 2);
				continue;

			} else {

				$attribute = $this->ATTRIBUTES[$attribute_guid];
				$attribute_values = $this->getAttributeValues($attribute['attribute_id']);

				// Проверим, является ли значение Ид, проверим его по связям
				if (isset($attribute_values[$attribute_value])) {
					$attribute_value_guid = $attribute_value;
					$attribute_value 	= $attribute_values[$attribute_value_guid]['name'];
					$attribute_value_id = $attribute_values[$attribute_value_guid]['attribute_value_id'];
				} else {
					$attribute_value_id = 0;
				}
			}

			// Фильтруем по таблице свойств
			$import = true;
			$attributes_filter = $this->config->get('exchange1c_properties');
			if (is_array($attributes_filter)) {

				foreach ($attributes_filter as $attr_filter) {

					if ($attr_filter['name'] != $attribute['name']) {
						continue;
					}

					if (!isset($attr_filter['import'])) {
						$import = false;
					}

					if ($attr_filter['product_field_name'] == '') {

						$this->log("Свойство отключено: '" . $attr_filter['name'] . "'", 2);
						break;

					} // $attr_filter['product_field_name'] == ''

				} // foreach

			} // is_array($attributes_filter)

			switch ($attribute['name']) {

				case 'Производитель':
					$this->log("Производитель из свойства: 'Производитель'");

					// Устанавливаем производителя из свойства если только он не был ранее прочитан
					if ($this->config->get('exchange1c_product_manufacturer_no_change') != 1 && empty($data['manufacturer'])) {

						$data['manufacturer_name']	= $attribute_value;
						$data['manufacturer_id'] 	= $this->setManufacturer($data['manufacturer_name']);

					}
				break;

				case $manufacturer_tag:
					$this->log("Производитель из свойства: '" . $manufacturer_tag . "'");

					// Устанавливаем производителя из свойства если только он не был ранее прочитан
					if ($this->config->get('exchange1c_product_manufacturer_no_change') != 1 && empty($data['manufacturer'])) {

						$data['manufacturer_name']	= $attribute_value;
						$data['manufacturer_id'] 	= $this->setManufacturer($data['manufacturer_name']);

					}
				break;

				case 'Вес':
					$this->log("Вес из свойства");
					$search = array(',', ' ');
					$replace = array('.', '');
					$data['weight'] = str_replace($search, $replace, $attribute_value);
				break;

				case 'Длина':
					$this->log("Длина из свойства");
					$search = array(',', ' ');
					$replace = array('.', '');
					$data['length'] = str_replace($search, $replace, $attribute_value);
				break;

				case 'Ширина':
					$this->log("Ширина из свойства");
					$search = array(',', ' ');
					$replace = array('.', '');
					$data['width'] = str_replace($search, $replace, $attribute_value);
				break;

				case 'Высота':
					$this->log("Высота из свойства");
					$search = array(',', ' ');
					$replace = array('.', '');
					$data['height'] = str_replace($search, $replace, $attribute_value);
				break;

				default:
					if ($import) {
						$product_attributes[] = array(
							'attribute_id'			=> $attribute['attribute_id'],
							'value'					=> $attribute_value
						);
						$this->log("Свойство '" . $attribute['name'] . "' = '" . $attribute_value . "'", 2);
					}
			}
		} // foreach

		$data['attributes'] = $product_attributes;

	} // parseProductAttributes()


	/**
	 * ver 5
	 * update 2017-09-03
	 * Обновляет свойства в товар из массива
	 */
	private function updateProductAttributes($product_id, $attributes) {

		// Читаем старые атрибуты
		$product_attributes = array();
		$query = $this->query("SELECT `attribute_id`,`text` FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . (int)$product_id . " AND `language_id` = " . $this->LANG_ID);
		foreach ($query->rows as $attribute) {
			$product_attributes[$attribute['attribute_id']] = $attribute['text'];
		}

		foreach ($attributes as $attribute) {

			// Проверим есть ли такой атрибут
			if (isset($product_attributes[$attribute['attribute_id']])) {

				// Проверим значение и обновим при необходимости
				if ($product_attributes[$attribute['attribute_id']] != $attribute['value']) {
					$this->query("UPDATE `" . DB_PREFIX . "product_attribute` SET `text` = '" . $this->db->escape($attribute['value']) . "' WHERE `product_id` = " . (int)$product_id . " AND `attribute_id` = " . (int)$attribute['attribute_id'] . " AND `language_id` = " . $this->LANG_ID);
					$this->log("Атрибут товара обновлен'" . $this->db->escape($attribute['name']) . "' = '" . $this->db->escape($attribute['value']) . "' записано в товар id: " . (int)$product_id, 2);
				}

				unset($product_attributes[$attribute['attribute_id']]);
			} else {
				// Добавим в товар
				$this->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SET `product_id` = " . (int)$product_id . ", `attribute_id` = " . (int)$attribute['attribute_id'] . ", `language_id` = " . $this->LANG_ID . ", `text` = '" .  $this->db->escape($attribute['value']) . "'");
				$this->log("Атрибут товара добавлен '" . $this->db->escape($attribute['name']) . "' = '" . $this->db->escape($attribute['value']) . "' записано в товар id: " . (int)$product_id, 2);
			}
		}

		// Удалим неиспользованные
		if (count($product_attributes)) {
			$delete_attribute = array();
			foreach ($product_attributes as $attribute_id => $attribute) {
				$delete_attribute[] = $attribute_id;
			}
			$this->query("DELETE FROM `" . DB_PREFIX . "product_attribute` WHERE `product_id` = " . (int)$product_id . " AND `language_id` = " . $this->LANG_ID . " AND `attribute_id` IN (" . implode(",",$delete_attribute) . ")");
			$this->log("Старые атрибуты товара удалены", 2);
		}

	} // updateProductAttributes()


	/**
	 * ver 5
	 * update 2018-06-14
	 * Обновляем производителя в базе данных
	 */
	private function updateManufacturer($manufacturer_id, $data, $name) {

		if ($data['name'] == $name) {
			return false;
		} else {
			$data['name'] = $name;
		}

		if (isset($this->TAB_FIELDS['manufacturer_description'])) {

			$query = $this->query("SELECT `name`, `description`, `meta_title`, `meta_description`, `meta_keyword` FROM `" . DB_PREFIX . "manufacturer_description` WHERE `manufacturer_id` = " . (int)$manufacturer_id . " AND `language_id` = " . $this->LANG_ID);

			// SEO
			if ($this->config->get('exchange1c_seo_manufacturer_mode') != 'disable')
				$this->seoGenerateManufacturer($manufacturer_id, $data);

			// Сравнивает запрос с массивом данных и формирует список измененных полей
			$update_fields = $this->compareArrays($query, $data);

			if ($update_fields)
				$this->query("UPDATE `" . DB_PREFIX . "manufacturer_description` SET " . $update_fields . " WHERE `manufacturer_id` = " . (int)$manufacturer_id . " AND `language_id` = " . $this->LANG_ID);

		} else {

			$sql = isset($data['noindex']) ? ", `noindex` = " . $data['noindex'] : "";
			$this->query("UPDATE `" . DB_PREFIX . "manufacturer` SET `name` = '" . $this->db->escape($data['name']) . "'" . $sql . " WHERE `manufacturer_id` = " . (int)$manufacturer_id);

			$this->log("Производитель обновлен: '" . $data['name'] . "'", 2);

		}
		return true;

	} // updateManufacturer()


	/**
	 * ver 9
	 * update 2018-11-11
	 * Добавляем производителя
	 * Отказ от использования GUID
	 */
	private function addManufacturer($data) {

		// ДОБАВЛЯЕМ
		$sql_set = "";
		if (isset($this->TAB_FIELDS['manufacturer']['noindex'])) {
			$sql_set = ", `noindex` = 1";	// значение по умолчанию
		}

		$query = $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer` SET `name` = '" . $this->db->escape($data['name']) . "', `image` = '" . $this->db->escape($data['image']) . "', `sort_order` = " . $data['sort_order'] . $sql_set);

		$manufacturer_id = $this->db->getLastId();

		$this->MANUFACTURERS[$manufacturer_id] = array(
			'name'			=> $data['name'],
			'image'			=> $data['image'],
			'sort_order'	=> $data['sort_order']
		);

		if (isset($this->TAB_FIELDS['manufacturer_description'])) {

			// SEO
			if ($this->config->get('exchange1c_seo_manufacturer_mode') != 'disable')
				$this->seoGenerateManufacturer($manufacturer_id, $data);

			if (!isset($this->TAB_FIELDS['manufacturer_description']['name']))
				unset($data['name']);

			$sql = $this->prepareQueryDescription($data, "set");

			if ($sql) {
				$query = $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET " . $sql . ", `language_id` = " . $this->LANG_ID . ", `manufacturer_id` = " . (int)$manufacturer_id);
			} else {
				$query = $this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_description` SET `manufacturer_id` = " . (int)$manufacturer_id .", `language_id` = " . $this->LANG_ID . ", `description` = ''");
			}
		}

		// СВЯЗЬ
		if ($data['guid'])
			$this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_1c` SET `guid` = '" . $this->db->escape($data['guid']) . "', `manufacturer_id` = " . (int)$manufacturer_id);

		// МАГАЗИН
		$this->query("INSERT INTO `" . DB_PREFIX . "manufacturer_to_store` SET `manufacturer_id` = " . (int)$manufacturer_id . ", `store_id` = " . $this->STORE_ID);

 		$this->log("Добавлен производитель: '" . $data['name'] . "', manufacturer_id = " . $manufacturer_id, 2);

 		return $manufacturer_id;

	} // addManufacturer()


	/**
	 * ver 7
	 * update 2018-10-14
	 * Устанавливаем производителя
	 * Ид производителя нужен для XML 2.09 и выше
	 */
	private function setManufacturer($name, $guid = '') {

		$this->log('Производитель: ' . $name, 2);
		$manufacturer_id = 0;

		if (empty($this->MANUFACTURERS)) {
			$this->MANUFACTURERS = $this->getManufacturers();
		}
		//$this->log($this->MANUFACTURERS, 2);

		foreach ($this->MANUFACTURERS as $found_manufacturer_id => $manufacturer_data) {
			if ($guid) {
				if (mb_strtolower($name) == mb_strtolower($manufacturer_data['name']) && $guid == $manufacturer_data['guid']) {
					$manufacturer_id = $found_manufacturer_id;
					break;
				}
			} else {
				if (mb_strtolower($name) == mb_strtolower($manufacturer_data['name'])) {
					$manufacturer_id = $found_manufacturer_id;
					break;
				}
			}
		}

		if ($manufacturer_id) {
			$this->log("Производитель '" . $name . "' в базе '" . $manufacturer_data['name'] . "', manufacturer_id = " . $manufacturer_id, 2);
			return $manufacturer_id;
		}

		$this->log('Производитель не найден, добавляем...', 2);
		// Добавим производителя
		$data = array(
			'name'			=> $name,
			'image'			=> "",
			'sort_order'	=> 0,
			'guid'			=> $guid,
			'description'	=> ''
		);
		$manufacturer_id = $this->addManufacturer($data);

		return $manufacturer_id;

	} // setManufacturer()


	/**
	 * Формирует строку запроса
	 */
	private function prepareQuery($data, $mode = 'set', $table = '') {

		// Удаляет поля которых нет в указанной таблице
		if ($table) {
			$query = $this->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "`");
			$fields = array();
			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					$fields[$row['Field']] = $row['Type'];
				}
				$this->log($fields, 2);
				$this->log($data, 2);
			}
			foreach ($data as $field => $row) {
				if (!isset($fields[$field])) {
					unset($data[$field]);
					$this->log("Удалено поле " . $field);
				}
			}
		}

		// Формируем строку запроса
		$sql = array();
		foreach ($data as $field => $value) {
			$sql[] = $mode == 'set' ? "`" . $field . "` = " . (is_numeric($value) ? $value : "'" . $this->db->escape($value) . "'") : "`" . $field . " `";
		}

		return implode(($mode = 'set' ? ', ' : ' AND '), $sql);

	} // prepareQuery()


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
	 * ver 2
	 * update 2017-11-01
	 * Удаляет старые неиспользуемые картинки
	 * Сканирует все файлы в папке import_files и ищет где они указаны в товаре, иначе удаляет файл
	 * Вызывается из контроллера, manualCleaningOldImages()
	 */
	public function cleanOldImages($folder) {

		$result = array('error'=>"", 'num'=>0);
		if (!file_exists(DIR_IMAGE . $folder)) {
			return "Папка не существует: /image/" . $folder;
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
						$success = @unlink(DIR_IMAGE . $path);
						if ($success) {
							$result['num']++;
						} else {
							$this->log("[!] Ошибка удаления файла: " . $path, 2);
							$result['error'] = "Ошибка удаления файла: " . $path;
							return $result;
						}
					}

				} elseif (is_dir(DIR_IMAGE . $path)) {

					$result_ = $this->cleanOldImages($path . '/');

					// Обработка результатов
					$result['num'] += $result_['num'];
					if ($result_['error']) {
						$result['error'] = $result_['error'];
						return $result;
					}

					// Попытка удалить папку, если она не пустая, то произойдет удаление
					$success = @rmdir(DIR_IMAGE . $path);
					if ($success) {
						$this->log("> Удалена пустая папка: " . $path, 2);
					}
					continue;
				}
			}

		}
		return $result;

	} // cleanOldImages()


	/**
	 * Удаляет связи все
	 * Вызывается из контроллера, manualCleaningLinks()
	 */
	public function cleanLinks() {

		$clear = array('product_to_1c','category_to_1c','attribute_to_1c','manufacturer_to_1c','store_to_1c','store_to_1c');
		$result = "";
		foreach ($clear as $table) {
			$this->query("DELETE FROM `" . DB_PREFIX . $table . "`");
			$result .= $table . "\n";
		}
		return $result;

	} // cleanLinks()


	/**
	 * ver 2
	 * update 2017-06-13
	 * Удаляет все дубли связей с торговой системой
	 * Вызывается из контроллера, manualRemoveDoublesLinks()
	 */
	public function removeDoublesLinks() {

		$tables = array('attribute','category','manufacturer','product','store');
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
	 * ver 4
	 * update 2017-08-14
	 * Возвращает id категорий по GUID
	 */
	private function parseProductCategories($categories) {

		$result = array();

		foreach ($categories->Ид as $category_guid) {
			$guid = (string)$category_guid;

			if (isset($this->CATEGORIES[$guid])) {
				$result[] = $this->CATEGORIES[$guid]['category_id'];
			} else {
				$this->log("[!] Категория не найдена по Ид = " . $guid);
			}
		}

		$this->log("Найдено категорий: " . count($result), 2);
		return $result;

	} // parseProductCategories()


	/**
	 * ver 3
	 * update 2018-03-14
	 * Читает всех производителей из базы в массив
	 */
	private function getManufacturers() {

		if (isset($this->TAB_FIELDS['manufacturer_description']['name'])) {
			$query = $this->query("SELECT `m`.`manufacturer_id`, `m`.`name`, `m1c`.`guid` FROM `" . DB_PREFIX . "manufacturer_description` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_1c` `m1c` ON (`m`.`manufacturer_id` = `m1c`.`manufacturer_id`) LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
			//$query = $this->query("SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer_description` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
		} else {
			$query = $this->query("SELECT `m`.`manufacturer_id`, `m`.`name`, `m1c`.`guid` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_1c` `m1c` ON (`m`.`manufacturer_id` = `m1c`.`manufacturer_id`) LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
			//$query = $this->query("SELECT `manufacturer_id`, `name` FROM `" . DB_PREFIX . "manufacturer` `m` LEFT JOIN `" . DB_PREFIX . "manufacturer_to_store` `ms` ON (`m`.`manufacturer_id` = `ms`.`manufacturer_id`) WHERE `ms`.`store_id` = " . (int)$this->STORE_ID);
		}

        $data = array();

    	foreach ($query->rows as $row) {
    		$data[$row['manufacturer_id']] = array(
    			'name'		=> $row['name'],
   				'guid'		=> $row['guid']
			);
    		//$data[$row['manufacturer_id']] = $row['name'];

    	} // foreach

    	$this->log("Производителей всего в базе: " . count($data));

		return $data;

	} // getManufacturers()


	/**
	 * ver 3
	 * update 2017-12-24
	 * Читает из XML данные о налогах
	 */
	private function parseProductTaxes($product) {

		$tax_class_id = 0;
		foreach ($product->СтавкаНалога as $product_tax) {
			$name = trim((string)$product_tax->Наименование);
			$new_rate = 0;

			if ($product_tax->Ставка) {
				$new_rate = (float)$product_tax->Ставка;
				$sql_where = " AND `rate`.`rate` = " . $new_rate;
				$name = $name . " " . $new_rate . "%";
			}

			if ($new_rate == 0) {
				// значит налог не используем
				continue;
			}

			// Найдем налог по наименованию в базе
			$query = $this->query("SELECT `rate`.`rate`, `rate`.`tax_rate_id`, `rule`.`tax_class_id` FROM `" . DB_PREFIX . "tax_rate` `rate` LEFT JOIN `" . DB_PREFIX . "tax_rule` `rule` ON (`rate`.`tax_rate_id` = `rule`.`tax_rate_id`) WHERE `rate`.`name` = '" . $this->db->escape($name) . "'" . $sql_where);
			$this->log($query, 2);

			if ($query->num_rows) {
				$tax_class_id = $query->row['tax_class_id'];
				$rate = $query->row['rate'];
			} else {
				$this->errorLog(2010, $name);
				return 0;
			}

			if (!$rate) {
				$this->errorLog(2011, $new_rate, $name);
				return 0;
			}

			if (!$tax_class_id) {
				$this->errorLog(2012, $name);
				return 0;
			}

			$this->log("Налог товара найден: '" . $name . "', ставка: '" . $rate . "'");
		}

		return $tax_class_id;

	} // parseProductTaxes()


	/**
	 * ver 5
	 * update 2018-05-11
	 * Проверяет товар на существование в базе и проверяет связи с Ид
	 */
	private function searchProduct(&$data) {

		$product_id = 0;
		$version = '';

		if ($data['product_id']) {

			// Проверим связи по ID
			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$data['product_id']);
  			if ($query->num_rows) {

				$product_id 	= $query->row['product_id'];
				$version 		= $query->row['version'];
				// Если Ид отличается
				if ($query->row['guid'] != $data['product_guid']) {
					$this->query("UPDATE `" . DB_PREFIX . "product_to_1c` SET `guid` = '" . $this->db->escape($data['product_guid']) . "' WHERE `product_id` = " . (int)$data['product_id']);
				}
			}

  		} else {

			// Проверим связи по Ид
			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_to_1c` WHERE `guid` = '" . $this->db->escape($data['product_guid']) . "'");
			if ($query->num_rows) {
				$product_id 	= $query->row['product_id'];
				$version 		= $query->row['version'];
			}

  		}

   		//$this->log($product_id);
		$data['old_version'] = $version;

		// Проверим существование товара
		if ($product_id) {
			$query_product = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$product_id);
			if (!$query_product->num_rows && $product_id) {
				// Удалим связь на несуществующий товар
				$this->query("DELETE FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$product_id);
				$product_id = 0;
			}
		}

		return $product_id;

	} // searchProduct()


	/**
	 * ver 3
	 * update 2018-06-28
	 *
	 */
	private function parseProductRules($xml, &$data) {

		$rules = $this->config->get('exchange1c_product_rules_pre_parse');
		if (!$rules)
			return;

		$this->log($rules, 2);
		$rules = explode("\r\n", $rules);
		$num = 0;

		foreach ($rules as $rule_str) {

			$rule_str = trim($rule_str);

			if (empty($rule_str)) {
				continue;
			}

			$num++;
			$rule_data = explode('#', $rule_str);
			if (count($rule_data) != 3) {
				$this->log("Неверный формат правил в строке " . $num . " правило '" . $rule_str . "'");
				continue;
			}
			$result = '';
			//$this->log($rule_data, 2);
			if (isset($rule_data[0])) {
				$tag = trim($rule_data[0]);
				if ($xml->$tag) {
					$result = trim((string)$xml->$tag);
					//$this->log($result,2);
				};
			}
			$script = trim($rule_data[1]);
			if (!empty($script) && $result) {

				//ob_start();
				$return = eval("\$result= $script;");
				if ( $return === false && ( $error = error_get_last() ) ) {
					$this->log($error, 2);
				}
				//$result = ob_get_contents();
				//ob_end_clean();
				//$this->log($result, 2);
			}
			if (isset($rule_data[2])) {
				$field = trim($rule_data[2]);
				$data[$field] = $result;
				$this->log($data[$field], 2);
			}
		}

	} // parseProductRules()


	/**
	 * ver 1
	 * update 2018-06-21
	 */
	private function getCategoriesEmpty($only_enabled = true) {

		$all_categories = array();
		$not_empty_categories = array();

		$query = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category`" . ($only_enabled? " WHERE `status` = 1" : ""));

		foreach ($query->rows as $result) {
			$all_categories[] = $result['category_id'];
		}

		$query = $this->db->query("SELECT DISTINCT(`parent_id`) as category_id FROM `" . DB_PREFIX . "category` WHERE `parent_id` > 0" . ($only_enabled? " AND `status` = 1" : ""));

		foreach ($query->rows as $result) {
			$not_empty_categories[] = $result['category_id'];
		}

		$query = $this->db->query("SELECT DISTINCT(pc.`category_id`) FROM `" . DB_PREFIX . "category` as c,`" . DB_PREFIX . "product_to_category` as pc, `" . DB_PREFIX . "product` as p WHERE pc.`category_id` = c.`category_id` AND pc.`product_id` = p.`product_id` AND p.`status` = 1" . ($only_enabled? " AND c.`status` = 1" : ""));

		foreach ($query->rows as $result) {
			$not_empty_categories[] = $result['category_id'];
		}

		$empty_categories = array_diff($all_categories, $not_empty_categories);

		return $empty_categories;

	} // getCategoriesEmpty()


	/**
	 * ver 1
	 * update 2018-06-21
	 */
	private function getCategoriesNotEmpty() {

		$all_categories = array();
		$not_empty_categories = array();

		$query = $this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "category` WHERE `status` = 0");
		foreach ($query->rows as $result) {
			$all_categories[] = $result['category_id'];
		}
		//$this->log($all_categories,2);

		$query = $this->db->query("SELECT DISTINCT(pc.`category_id`) FROM `" . DB_PREFIX . "category` as c,`" . DB_PREFIX . "product_to_category` as pc, `" . DB_PREFIX . "product` as p WHERE pc.`category_id` = c.`category_id` AND pc.`product_id` = p.`product_id` AND p.`status` = 1 AND c.`status` = 1");
		foreach ($query->rows as $result) {
			$not_empty_categories[] = $result['category_id'];
		}
		//$this->log($not_empty_categories,2);

		$enable_categories = array_diff($all_categories, $not_empty_categories);
		return $enable_categories;

	} // getCategoriesNotEmpty()


	/**
	 * ver 1
	 * update 2018-06-21
	 */
	private function disableCategoriesEmpty() {

		$empty_categories = $this->getCategoriesEmpty(false);
		$count = count($empty_categories);
		$result_count = $count;

		if ($count > 0) {
			$query = $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 1 WHERE `sort_order` >= 0 AND `status` = 0");
			$query = $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 0 WHERE `category_id` IN (" . join(', ', $empty_categories) . ")");

   			while ($count > 0) {
				$empty_categories = $this->getCategoriesEmpty(true);
				$count = count($empty_categories);

				if ($count > 0) {
					$result_count += $count;
					$query = $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 0 WHERE `category_id` IN (" . join(', ', $empty_categories) . ")");
				}
			}
		}
		return $result_count;

	} // disableCategoriesEmpty()


	/**
	 * ver 1
	 * update 2018-06-21
	 */
	private function enableCategoriesEmpty() {

		$no_empty_categories = $this->getCategoriesNotEmpty();
		$count = count($no_empty_categories);
		$this->log($no_empty_categories, 2);

		if ($count > 0) {
			$query = $this->db->query("UPDATE `" . DB_PREFIX . "category` SET `status` = 1 WHERE `category_id` IN (" . join(', ', $no_empty_categories) . ")");
		}

		return $count;

	} // enableCategoriesEmpty()


	/**
	 * ver 30
	 * update 2018-11-06
	 * Обрабатывает товары из раздела <Товары> в XML
	 * При порционной выгрузке эта функция запускается при чтении каждого файла
	 * При полной выгрузке у товара очищаются все и загружается по новой.
	 * В формате 2.04 характеристики названия характеристике и их значение для данного товара передается тут
	 * Начиная с версии 1.6.3 читается каждая характеристика по отдельности, так как некоторые системы рвут товары с характеристиками
	 */
	private function parseProducts($xml) {

		if (!$xml->Товар) {
			$this->log("Нет товаров, проверьте XML файл");
			return false;
		}

		if (empty($this->CATEGORIES)) {
			$this->log("ВНИМАНИЕ! Категории отсутствуют, новые товары будут без категорий!");
		}

		$this->log("Товаров в файле: " . count($xml->Товар));
		$this->STAT['product_num'] = count($xml->Товар);

		foreach ($xml->Товар as $num => $product) {

			$data = array();
			$data['name']			= htmlspecialchars(trim((string)$product->Наименование));
			$guid = explode("#", (string)$product->Ид);
			$data['product_guid']	= $guid[0];
			$data['feature_guid']	= isset($guid[1]) ? $guid[1] : '';
			$data['product_id']		= 0;
			$data['status']			= 1;

			$this->log("~ТОВАР: '" . $data['name'] . "', GUID: '" . $data['product_guid'] . "'");
			if ($data['feature_guid']) {
				$this->log("ХАРАКТЕРИСТИКА GUID: '" . $data['feature_guid'] . "'");
			}

			// ВЕРСИЯ
			if ($product->НомерВерсии) {
				$data['version'] = (string)$product->НомерВерсии;
			}

			// ТОВАР ПОМЕЧЕН НА УДАЛЕНИЕ В УЧЕТНОЙ СИСТЕМЕ И СКОРО БУДЕТ УДАЛЕН
			$data['delete'] = 0;
			if ($product->ПометкаУдаления) {
				$data['delete'] = trim((string)$product->ПометкаУдаления) == 'true' ? 1 : 0;
			}
			// Из УНФ передается статус Удален
			if ($product['Статус'] == 'Удален') {
				$data['delete'] = 1;
			}
			if ($product->Статус) {
				$data['delete'] = trim((string)$product->Статус) == 'Удален' ? 1 : 0;
			}
			if ($data['delete']) {
				$this->log('ВНИМАНИЕ! Товар помечен на удаление в УС!');
			}

			// ШТРИХКОД
			$data['ean'] = $product->Штрихкод ? trim((string)$product->Штрихкод) : "";
			if (!$data['ean']) {
				$data['ean'] = $product->ШтрихКод ? trim((string)$product->ШтрихКод) : "";
			}

			// АРТИКУЛ
			$data['sku'] = $product->Артикул ? htmlspecialchars(trim((string)$product->Артикул)) : "";

			// YANDEX MARKET
			if (isset($this->TAB_FIELDS['product']['noindex'])) {
				$data['noindex']		= 1; // В некоторых версиях
			}

			// ОПИСАНИЕ (не учавствует в SEO поэтому можно отключать при чтении для экономии памяти)
			if ($product->Описание && $this->config->get('exchange1c_product_description_no_import') != 1)	{
				//$data['description']	=  nl2br(htmlspecialchars((string)$product->Описание));
				$description			=  htmlspecialchars(trim((string)$product->Описание));
				$data['description']	=  str_replace(array("\r\n", "\r", "\n"), "<br />", $description);
				//$this->log($data['description'], 2);
			}

			// ПРОИЗВОДИТЕЛЬ
			$manufacturer_tag = 'Производитель';
			if ($this->config->get('exchange1c_product_manufacturer_tag'))
				$manufacturer_tag = $this->config->get('exchange1c_product_manufacturer_tag');

			// Читаем изготовителя, добавляем/обновляем его в базу
			if ($product->Изготовитель) {
				$data['manufacturer_name'] = trim((string)$product->Изготовитель->Наименование);
			} elseif ($product->Производитель) {
				$data['manufacturer_name'] = trim((string)$product->Производитель);
			} elseif ($product->$manufacturer_tag) {
	        	$data['manufacturer_name'] = trim((string)$product->$manufacturer_tag->Наименование);
			}

			// РЕКВИЗИТЫ
			if ($product->ЗначениеРеквизита) {
				$this->parseRequisite($product, $data);
			} elseif ($product->ЗначенияРеквизитов) {
				$this->parseRequisite($product->ЗначенияРеквизитов, $data);
			}

			// МОДЕЛЬ
			if ($product->Модель) {
				$data['model'] = htmlspecialchars(trim((string)$product->Модель));
			}

			// НАИМЕНОВАНИЕ
			if ($product->Наименование && $this->config->get('exchange1c_import_product_name') == "name") {
				$data['name'] = htmlspecialchars(trim((string)$product->Наименование));
				$this->log("Наименование установлено из элемента 'Наименование'", 2);

			} elseif ($product->ПолноеНаименование && $this->config->get('exchange1c_import_product_name') == "fullname") {
				$data['name'] = htmlspecialchars(trim((string)$product->ПолноеНаименование));
				$this->log("Наименование установлено из элемента 'ПолноеНаименование'", 2);

			} elseif ($this->config->get('exchange1c_import_product_name') == "manually") {
				// Название поля наименования
				$field_name = $this->config->get('exchange1c_import_product_name_field');
				$data['name'] = htmlspecialchars(trim((string)$product->$field_name));
				$this->log("Наименование установлено из элемента '" . $field_name . "'", 2);
			}

			// ГРУППЫ ТОВАРОВ
			if ($product->Группы && $this->config->get('exchange1c_product_category_no_import') != 1) {
				// Если надо обновлять категории товара
				$data['categories']	= $this->parseProductCategories($product->Группы);
				if (empty($data['categories'])) {
					$this->errorLog(2004);
					return false;
				}
				$this->log("Категорий прочитано: " . count($data['categories']));
			}

			// ТОВАРНАЯ КАТЕГОРИЯ
			$data['product_category'] = ($product->Категория) ? (string)$product->Категория : "";

			// АТРИБУТЫ
			if ($product->ЗначенияСвойств && $this->config->get('exchange1c_product_attribute_not_import') != 1) {
				$this->parseProductAttributes($product->ЗначенияСвойств, $data);
				if ($this->ERROR) return false;
				$this->log("Атрибутов прочитано: " . count($data['attributes']));
			}

			// КАРТИНКИ
			if ($product->Картинка) {
				$data['images'] = $this->parseImages($product->Картинка);
				if ($this->ERROR) return false;
				$this->log("Картинок прочитано: " . count($data['images']));
			} // if ($product->Картинка)

			// CML 2.04
			if ($product->ОсновнаяКартинка) {
				$data['images'] = $this->parseImages($product->ОсновнаяКартинка);
				if ($this->ERROR) return false;

				// дополнительные, когда элементы в файле называются <Картинка1>, <Картинка2>...
				$cnt = 1;
				$var = 'Картинка'.$cnt;

				while (!empty($product->$var)) {
					$images = $this->parseImages($product->$var);
					if ($this->ERROR) return false;
					$cnt++;
					$var = 'Картинка'.$cnt;
				}

				$this->log("Картинок прочитано: " . count($data['images']));
			} // if ($product->ОсновнаяКартинка)

			// НАЛОГИ
			if ($product->СтавкиНалогов && $this->config->get('exchange1c_product_taxes_no_import') != 1) {
				$data['tax_class_id'] = $this->parseProductTaxes($product->СтавкиНалогов);
				if ($this->ERROR) return false;
				$this->log("Налоговая ставка tax_class_id = " . $data['tax_class_id'], 2);
			}

			$this->parseProductRules($product, $data);

			if ($this->config->get('exchange1c_product_disable_if_price_zero') == 1 || $this->config->get('exchange1c_product_disable_if_quantity_zero') == 1) {
				$data['status'] = 0;
			}

			$this->log("Перед функцией setProduct()", 2);
			$this->log($data, 2);
			// ЗАПИСЬ ТОВАРА
			$product_id = $this->setProduct($data);
			if ($this->ERROR) return false;

			// ОТЗЫВЫ
			// Отзывы парсятся с Яндекса в 1С, а затем на сайт
			// Доработка от SunLit (Skype: strong_forever2000)
			if ($product->ЗначенияОтзывов) {
				$this->log("ЗначенияОтзывов...", 2);
				$data['review'] = $this->parseReview($data, $product->ЗначенияОтзывов);
				if ($this->ERROR) return false;
			}

			// ОПЦИИ
			// такое встречается в старых версиях XML 2.03, 2.04, 2.05
			// Тут перечисляются все характеристики товара
			if ($product->ХарактеристикиТовара && $this->config->get('exchange1c_product_feature_import') == 1) {

				// Прочитаем все характеристики товара
				$product_feature = $this->getProductFeature($data['feature_guid']);

				if ($product_feature) {
					$this->log("Характеристика в базе:", 2);
					$this->log($product_feature, 2);
					$product_feature_id = $product_feature['product_feature_id'];
				} else {
					$this->log("Характеристик в базе нет", 2);
					$product_feature_id = $this->addFeature($product_id, $data['feature_guid'], $data['ean'], $data['sku']);
					$this->log("Добавлена характеристика product_feature_id = " . $product_feature_id, 2);
				}

				if (!$product_feature_id) {
					$this->errorLog(2002);
					return false;
				}

				$product_options = $this->getProductOptions($product_id);
				if ($product_options) {
					$this->log("Опции товара:", 2);
					$this->log($product_options, 2);
				} else {
					$this->log("У товара нет опций в базе", 2);
				}

				// Считаем опции с файла
				$data_options = $this->parseProductOptions($product->ХарактеристикиТовара);
				$this->log("Опции прочитанные из файла:", 2);
				$this->log($data_options, 2);

				if (empty($data_options)) {
					$this->errorLog(2003);
					return false;
				}

				$options = $this->setProductOptions($product_id, $data_options);
				$this->log("Опции которых нет в файле:", 2);
				$this->log($options, 2);
				if ($this->ERROR) return false;
			}

			unset($data);

		} // foreach

		// После загрузки каталога проверим на пустые папки и отключим их
		if ($this->config->get('exchange1c_category_empty_disable') == 1) {
			$count_disable = $this->disableCategoriesEmpty();
			$this->log("Отключено пустых категорий: " . $count_disable);
		}

		// Включение не пустых категорий
		$count_enable = $this->enableCategoriesEmpty();
		$this->log("Включено не пустых категорий: " . $count_enable);

		$this->log("ТОВАРЫ ПРОЧИТАНЫ", 2);
		$this->log("********************************************");
		$this->log("Налоги на сайте:", 2);
		$this->log($this->TAXES, 2);
		$this->log("Текущее время: " . $this->NOW, 2);

		return true;

	} // parseProducts()


	/**
	 * ver 11
	 * update 2018-10-22
	 * Разбор каталога из файла XML
	 * $xml->Каталог
	 */
	private function parseDirectory($xml) {

		$directory					= array();
		$directory['guid']			= (string)$xml->Ид;
		$directory['name']			= (string)$xml->Наименование;
		$directory['classifier_id']	= (string)$xml->ИдКлассификатора;

		$this->checkFullImport($xml);

		// Если есть товары в файле
		if ($xml->Товары) {

            if ($this->FULL_IMPORT && $this->config->get('exchange1c_category_disable_before_full_import') == 1) {
				// Отключим все категории в магазине
				if ($this->STORE_ID) {
					$this->query("UPDATE `" . DB_PREFIX . "category` c LEFT JOIN `" . DB_PREFIX . "category_to_store` c2s ON (c.category_id = c2s.category_id) SET c.status = 0 WHERE c2s.store_id = " . (int)$this->STORE_ID . " AND c.status = 1");
				} else {
					$this->query("UPDATE `" . DB_PREFIX . "category` SET status = 0 WHERE status = 1");
				}
			}

			if ($this->FULL_IMPORT && $this->config->get('exchange1c_product_disable_before_full_import') == 1) {
				// Отключить все товары перед загрузкой
				// Если магазин определен то только в этом магазине, если не определен, то во всех магазинах
				if ($this->STORE_ID) {
					$this->query("UPDATE `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) SET p.status = 0 WHERE p2s.store_id = " . (int)$this->STORE_ID . " AND p.status = 1");
				} else {
					$this->query("UPDATE `" . DB_PREFIX . "product` SET status = 0 WHERE status = 1");
				}
			}

			if (empty($this->CATEGORIES)) {
				$this->statStart('category_parse');
				$this->CATEGORIES = $this->getCategories();
				$this->statStop('category_parse');
			}

			// Загрузка товаров
			$this->statStart('product_parse');
			$this->parseProducts($xml->Товары);
			$this->statStop('product_parse');
			if ($this->ERROR) return false;

		}

		return true;

	} // parseDirectory()


	/**
	 * ****************************** ФУНКЦИИ ДЛЯ ЗАГРУЗКИ ПРЕДЛОЖЕНИЙ ******************************
	 */


	/**
	 * ver 3
	 * update 2017-08-01
	 * Устанавливает нулевой остаток у всех товаров
	 */
	private function clearProductsQuantity() {

		$this->query("UPDATE `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p`.`product_id` = `p2s`.`product_id`) SET `p`.`quantity` = 0 WHERE `p2s`.`store_id` = " . $this->STORE_ID);
		$this->log("Обнулены все остатки у товаров");

	} // clearProductsQuantity()


	/**
	 * ver 8
	 * update 2018-05-24
	 * Читает остатки общие, если остатки по складам тогда суммируются
	 */
	private function parseQuantity($xml) {

		$quantity = 0;

		// есть секция с остатками, обрабатываем (XML 2.09, 2.10)
		if ($xml->Остатки) {
			foreach ($xml->Остатки->Остаток as $product_quantity) {
				// Если нет складов или общий остаток предложения
				if ($xml->Остаток->Количество) {
					$quantity = (float)$product_quantity->Количество;

				// есть секция со складами, посчитаем общее количество по складам
				} elseif ($product_quantity->Склад) {
					foreach ($product_quantity->Склад as $quantity_warehouse) {
						$quantity += (float)$product_quantity->Склад->Количество;
					}
				}
			}
		}

		if ($xml->Количество) {
			$quantity = (float)$xml->Количество;

		} elseif ($xml->Склад) {
			// Секция с остатками по складам, читаем если нет секции Количество
			foreach ($xml->Склад as $product_quantity) {
				$quantity += (float)$product_quantity['КоличествоНаСкладе'];
			} // foreach

		}
		return $quantity;

	} // parseQuantity()


	/**
	 * ver 2
	 * update 2018-06-15
	 * Возвращает массив данных валюты по id
	 */
	private function getCurrencyConfig($config, $name) {

		if (empty($config)) {
			$currency_default = $this->config->get('config_currency');
			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "currency` WHERE `code` = '" . $currency_default . "'");
			if ($query->num_rows) {
				return $query->row;
			}
		}

		$data = array();
		if (!$name) return $data;

		$currency_id = 0;
		foreach ($config as $config_obj) {
			if ($name = $config_obj['name']) {
				$currency_id = $config_obj['currency_id'];
			}
		}

		if (!$currency_id) return $data;

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "currency` WHERE `currency_id` = " . $currency_id);

		if ($query->num_rows) {
			return $query->row;
		}

		return $data;

	} // getCurrency()


	/**
	 * ver 2
	 * update 2017-09-08
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

		$this->errorLog(2030, $code);
		return 0;

	} // getCurrencyId()


	/**
	 * ver 2
	 * update 2017-11-04
	 * Установка значений в настройку модуля
	 */
	private function setConfig($key, $value, $serialized = 0, $code = 'exchange1c', $clean = false) {

		if ($clean) {
			$this->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $code . "'");
		}

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE `code` = '" . $code . "' AND `key` = '" . $key . "'");
		if ($query->num_rows) {
			if ($query->row['value'] != $value) {
				$this->query("UPDATE `" . DB_PREFIX . "setting` SET `value` = '" . $value . "' WHERE `setting_id` = " . $query->row['setting_id']);
			}
		} else {
			$this->query("INSERT INTO `" . DB_PREFIX . "setting` SET `store_id` = " . $this->STORE_ID . ", `code` = '" . $code . "', `key` = '" . $key . "', `value` = '" . $value . "', `serialized` = " . $serialized);
		}

	} // setConfig()


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
	 * ver 3
	 * update 2018-11-08
	 * Загружает типы цен автоматически в таблицу которых там нет
	 */
	private function autoLoadPriceType($xml) {

		$this->log("Автозагрузка цен из XML...", 2);
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
					$this->log("autoLoadPriceType() - Найдена цена по Ид = '" . $guid . "'", 2);
					$found = $key;
				}
				if (strtolower(trim($cpt['keyword'])) == strtolower($name)) {
					$this->log("autoLoadPriceType() - Найдена цена по наименованию = '" . $name . "'", 2);
					$found = $key;
				}
				$priority = max($priority, $cpt['priority']);
			}

			if ($found >= 0) {
				// Если тип цены помечен на удаление, удалим ее из настроек
				if ($delete == "true") {
					$this->log("autoLoadPriceType() - Тип цены помечен на удаление, не будет загружен и будет удален из настроек");
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
	        } else {
				$this->query("INSERT `". DB_PREFIX . "setting` SET `value` = '" . $this->db->escape(json_encode($config_price_type)) . "', `serialized` = 1, `code` = 'exchange1c', `key` = 'exchange1c_price_type'");
	        }
        }
		$this->log("Автоматическа загрузка видов цен из XML успешно завершена");
		return $config_price_type;

	} // autoLoadPriceType()


	/**
	 * ver 7
	 * update 2018-11-08
	 * Загружает типы цен из классификатора
	 * Обновляет Ид если найдена по наименованию
	 * Сохраняет настройки типов цен
	 */
	private function parseClassifierPriceType($xml) {

        $config_currency = $this->config->get('exchange1c_currency');
        $this->log("Настройки валюты в модуле:", 2);
        $this->log($config_currency, 2);

		// Автозагрузка цен
		if ($this->config->get('exchange1c_price_types_auto_load') == 1) {
			$config_price_type = $this->autoLoadPriceType($xml);
		} else {
			$config_price_type = $this->config->get('exchange1c_price_type');
		}

		if (empty($config_price_type)) {
			$this->errorLog(2031);
			return false;
		}

		// Перебираем все цены из CML
		foreach ($xml->ТипЦены as $price_type)  {
			$currency		= $this->getCurrencyConfig($config_currency, (string)$price_type->Валюта);
			$guid			= (string)$price_type->Ид;
		 	$name			= trim((string)$price_type->Наименование);
		 	$code			= $price_type->Код ? $price_type->Код : ($price_type->Валюта ? $price_type->Валюта : '');

			$found = false;
			// Перебираем все цены из настроек модуля
			foreach ($config_price_type as $index => $config_type) {

				// Заполним Ид, если они пустые при совпадении названия вида цен
				if (empty($config_type['guid'])) {
					if($config_type['keyword'] == $name) {
						$config_price_type[$index]['guid'] = $guid;
						$found = true;
					}

				} else {
					if ($config_type['guid'] == $guid) {
						$found = true;
					}
				}

			} // foreach ($config_price_type as $config_type)

			if (!$found) {
				$this->log("Не найден вид цены " . $name . " в настройках модуля");
				$this->errorLog(2033, $name, $guid);
				return false;
			}

		} // foreach ($xml->ТипЦены as $price_type)

	} // parseClassifierPriceType()


	/**
	 * ver 7
	 * update 2018-11-07
	 * Загружает все цены
	 */
	private function parsePrices($xml, $product_id) {

		$this->STAT['parse_price'] = microtime(true);

			// Читаем типы цен из настроек
		$config_prices = $this->config->get('exchange1c_price_type');

		if (!$config_prices) {
			$this->errorLog(2034);
			return false;
		}

		// Старые цены
		$old_prices = $this->getProductPrices($product_id);

		// Цена товара
		$price_product = 0;

		// Читем цены из настроек модуля сверху вниз
		foreach ($config_prices as $config_price_type) {

			if (!isset($config_price_type['guid'])) {
				$config_price_type['guid'] = "";
			}

			foreach ($xml->Цена as $price_data) {

				$guid	= (string)$price_data->ИдТипаЦены;
				$price	= $price_data->ЦенаЗаЕдиницу ? (float)$price_data->ЦенаЗаЕдиницу : 0;

				if ($config_price_type['guid'] != $guid) {
					continue;
				}

				$this->log("Найдена цена " . $price . " (" . $config_price_type['keyword'] . ")");

				// КУРС ВАЛЮТЫ
//				$rate = 1;
//				if ($price_data->Валюта) {
//					if ($price_data->Курс) {
//						$rate = (float)$price_data->Курс;
//					} else {
//						$config_currency = $this->config->get('exchange1c_currency');
//						if (!empty($config_currency)) {
//							// Поищем в настройках модуля
//							$currency_data		= $this->getCurrencyConfig($config_currency, (string)$price_data->Валюта);
//							$rate =  $currency_data['value'];
//						} else {
//							// Поищем в opencart таблице currency
//							$rate = $this->getCurrencyValue((string)$price_data->Валюта);
//						}
//					}
//				}

				// КОНВЕРТАЦИЯ ВАЛЮТ в основную валюту CMS
				$rate = 1;
				if ($this->config->get('exchange1c_currency_convert') == 1) {

					// КУРС
					if (isset($config_price_type['currency']['value'])) {
						$rate = $config_price_type['currency']['value'];
					}

					// ПЕРЕСЧЕТ ЦЕНЫ ПО КУРСУ
					if ($rate != 1 && $rate != 0) {
						if (!empty($config_price_type['currency']['decimal_place'])) {
							$decimal_place = $config_price_type['currency']['decimal_place'];
						} else {
							$decimal_place = 2;
						}
						if ($price)
							$price = round($price / (float)$rate, $decimal_place);
							$this->log("Цена пересчитана по курсу " . $rate . " валюты " . $config_price_type['currency']['name']);
					}
				}

				if ($this->config->get('exchange1c_ignore_price_zero') == 1 && $price == 0) {
					$this->log("Включена опция при нулевой цене не менять старую");
					continue;
				}

				// Основная цена товара
				if ($config_price_type['table_price'] == 'product'
					&& $this->config->get('config_customer_group_id') == $config_price_type['customer_group_id']) {

					// Группа покупателей по-умолчанию
					$this->log("Цена товара " . $price);
					$price_product = $price;

				} else {

					// Другие группы
					if ($config_price_type['table_price'] == 'discount') {

						// Поищем старую цену
						foreach ($old_prices['discount'] as $old_price) {
							if ($old_price['customer_group_id'] == $config_price_type['customer_group_id']) {
								if ($old_price['price'] != $price) {
									$this->query("UPDATE `" . DB_PREFIX . "product_discount` SET `price` = '" . $price . "' WHERE `product_discount_id` = '" . $old_price['product_discount_id'] . "'");
								}
							}
						}
						$this->log("Цена скидки '" . $config_price_type['keyword'] . "' = " . $price);
						$this->query("INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = " . (int)$product_id . ", `quantity` = " . (float)$config_price_type['quantity'] . ", `priority` = " . (int)$config_price_type['priority'] . ", `customer_group_id` = " . (int)$config_price_type['customer_group_id'] . ", `price` = '" . (float)$price . "'");

					} elseif ($config_price_type['table_price'] == 'special') {

						// Поищем старую цену
						foreach ($old_prices['special'] as $old_price) {
							if ($old_price['customer_group_id'] == $config_price_type['customer_group_id']) {
								if ($old_price['price'] != $price) {
									$this->query("UPDATE `" . DB_PREFIX . "product_special` SET `price` = '" . $price . "' WHERE `product_special_id` = '" . $old_price['product_special_id'] . "'");
								}
							}
						}
						$this->log("Цена акции '" . $config_price_type['keyword'] . "' = " . $price);
						$this->query("INSERT INTO `" . DB_PREFIX . "product_special` SET `product_id` = " . (int)$product_id . ", `priority` = " . (int)$config_price_type['priority'] . ", `customer_group_id` = " . (int)$config_price_type['customer_group_id'] . ", `price` = '" . (float)$price . "', `date_start` = '" . $this->NOW . "'");

					}
				}

			} // foreach ($xml->Цена as $price_data)

		} // foreach ($price_types as $config_price_type)

		$this->logStat('parse_price');

		return $price_product;

 	} // parsePrices()


	/**
	 * ====================================== ХАРАКТЕРИСТИКИ ======================================
	 */


	/**
	 * ver 3
	 * update 2018-10-31
	 * Получение данных о ранее связанном товаре по (Ид)GUID
	 * Возвращает false если нет связи либо массив данных с таблицы связей по данному товару
	 */
	private function getProductByGUID($product_guid) {

		// Определим product_id
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_to_1c` WHERE `guid` = '" . $this->db->escape($product_guid) . "'");
		if (!$query->num_rows) {
			return false;
		}

		$result = $query->row;
		$this->log($result);

		// Проверим существование такого товара в базе по product_id
		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$result['product_id']);
		if (!$query->num_rows) {
			// Удалим неправильную связь
			$this->query("DELETE FROM `" . DB_PREFIX . "product_to_1c` WHERE `product_id` = " . (int)$result['product_id']);
			return false;
		}

		$product_info = $this->getProduct($result['product_id']);
		$result = array_merge($result, $product_info);

		return $result;

	} // getProductByGUID()


	/**
	 * Проверка существования товара по product_id
	 * НЕИСПОЛЬЗУЕТСЯ!
	 */
	private function getProductIdByCode($code) {

		// Определим product_id
		$query = $this->query("SELECT `product_id` FROM `" . DB_PREFIX . "product` WHERE `product_id` = " . (int)$code);
		$product_id = isset($query->row['product_id']) ? $query->row['product_id'] : 0;

		if ($product_id) {
			$this->log("Найден товар по <Код>, product_id = " . $product_id, 2);
		} else {
			$this->log("Не найден товар по <Код>, code = " . $code, 2);
		}

		return $product_id;

	} // getProductIdByCode()


	/**
	 * ver 2
	 * update 2018-06-17
	 * Читает все существующие опции товара из базы
	 */
	private function getProductOptions($product_id) {

		$data = array();
		// Запрос без связи опции к товару
		$query_option = $this->query("SELECT `po`.`option_id`, `po`.`product_option_id`, `od`.`name`, `po`.`required` FROM `" . DB_PREFIX . "product_option` `po` LEFT JOIN `" . DB_PREFIX . "option_description` `od` ON (`po`.`option_id` = `od`.`option_id`) WHERE `po`.`product_id` = " . (int)$product_id . " AND `od`.`language_id` = " . $this->LANG_ID);

		if ($query_option->num_rows) {
			// Получим значения этих опций
			foreach ($query_option->rows as $row_option) {

				$product_option_id = $row_option['product_option_id'];
				$data[$product_option_id] = array(
					'product_option_id'	=> $row_option['product_option_id'],
					'option_id'			=> $row_option['option_id'],
					'name'				=> $row_option['name'],
					'required'			=> $row_option['required']
				);

				$query_value = $this->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "option_value_description` `ovd` ON (`pov`.`option_value_id` = `ovd`.`option_value_id`) WHERE `pov`.`product_option_id` = " . (int)$product_option_id . " AND `ovd`.`language_id` = " . $this->LANG_ID);

				if ($query_value->num_rows) {
					$values = array();

					foreach ($query_value->rows as $row_value) {
						$values[$row_value['product_option_value_id']] = array(
							'product_option_value_id' => $row_value['product_option_value_id'],
							'option_value_id'	=> $row_value['option_value_id'],
							'name'				=> $row_value['name'],
							'quantity'			=> $row_value['quantity'],
							'subtract'			=> $row_value['subtract'],
							'price'				=> $row_value['price'],
							'price_prefix'		=> $row_value['price_prefix'],
							'points'			=> $row_value['points'],
							'points_prefix'		=> $row_value['points_prefix'],
							'weight'			=> $row_value['weight'],
							'weight_prefix'		=> $row_value['weight_prefix']
						);
					}
					$data[$product_option_id]['values'] = $values;
				}

			}
		}
		return $data;

	} // getProductOptions()


	/**
	 * ver 5
	 * update 2018-11-09
	 * Читает по Ид характеристику товара из базы
	 */
	private function getProductFeature($product_feature_guid) {

		$data = array();
		// Запрос без связи опции к товару
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_feature` WHERE `guid` = '" . $this->db->escape($product_feature_guid) . "'");

		if ($query->num_rows) {

			$data['product_feature_id'] = $query->row['product_feature_id'];
			$data['ean'] 				= $query->row['ean'];
			$data['sku'] 				= $query->row['sku'];
			$data['price'] 				= $query->row['price'];
			$data['quantity'] 			= $query->row['quantity'];

			$values = array();

			$query_value = $this->query("SELECT `product_option_id`, `product_option_value_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_id` = " . (int)$data['product_feature_id']);

			foreach ($query_value->rows as $row_value) {

				$values[$row_value['product_option_id']] = $row_value['product_option_value_id'];

			} // foreach (value)
			$data['values'] = $values;
		}

		return $data;

	} // getProductFeature()


	/**
	 * ver 1
	 * update 2018-11-09
	 */
	private function getProductFeaturesQuantityPrice($product_id) {

		$data_quantity = array();
		$data_price = array();
		$price_min = 0;
		$price_max = 0;
		$quantity_total = 0;

		$query = $this->query("SELECT `product_feature_id`,`quantity`,`price` FROM `" . DB_PREFIX . "product_feature` WHERE `product_id` = '" . $product_id . "'");
    	if ($query->num_rows) {

    		foreach ($query->rows as $feature) {

    			// Значения
   				$data_value = array();
    			$query_value = $this->query("SELECT `product_option_id`,`product_option_value_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_feature_id` = '" . $feature['product_feature_id'] . "'");
   				foreach ($query_value->rows as $value) {
    				$data_value[$value['product_option_value_id']] = array(
    					'product_option_id'			=> $value['product_option_id'],
    					'product_option_value_id'	=> $value['product_option_value_id'],
    					'price'						=> $feature['price']
					);
					if ($price_min > $feature['price'] || $price_min == 0) {
						$price_min = $feature['price'];
					}
					if ($price_max < $feature['price']) {
						$price_max = $feature['price'];
					}
   				}
    			$data_quantity[$feature['product_feature_id']] = $feature['quantity'];
    			$quantity_total += $feature['quantity'];
				$data_price[$feature['product_feature_id']] = $data_value;

    		}
    	}

    	$data = array(
    		'quantity'	=> $data_quantity,
    		'price'		=> $data_price,
    		'price_min'	=> $price_min,
    		'price_max'	=> $price_max,
    		'quantity_total' => $quantity_total
		);

		return $data;

	} // getProductFeaturesQuantityPrice()


	/**
	 * ver 5
	 * update 2018-12-02
	 */
	private function setProductFeature($product_id, $data) {

		// Включаем характеристику, так как она есть в загрузке
		$data['status'] = 1;
		$data['sku'] = isset($data['sku']) ? $data['sku'] : "";
		$data['ean'] = isset($data['ean']) ? $data['ean'] : "";
		if (isset($data['price'])) {
			$this->log("Цена предложения: " . $data['price']);
		}

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_feature` WHERE `guid` = '" . $this->db->escape($data['feature_guid']) . "'");
    	if ($query->num_rows) {
    		$update_fields = $this->compareArrays($query,$data);
    		if ($update_fields) {
    			$this->query("UPDATE `" . DB_PREFIX . "product_feature` SET " . $update_fields . " WHERE `product_feature_id` = '" . $query->row['product_feature_id'] . "'");
    		}
    		return $query->row['product_feature_id'];
    	}

		$this->query("INSERT INTO `" . DB_PREFIX . "product_feature`
			SET `product_id` = " . $product_id . ",
			 `name` = '" . $data['feature_name'] . "',
			 `guid` = '" . $this->db->escape($data['feature_guid']) . "',
			 `ean` = '" . $this->db->escape($data['ean']) . "',
			 `sku` = '" . $this->db->escape($data['sku']) . "',
			 `price` = '" . (float)$data['price'] . "',
			 `quantity` = '" . (float)$data['quantity'] . "',
			 `status` = 1");

		return $this->db->getLastId();

	} // setProductFeature()


	/**
	 * ver 2
	 * update 2018-03-23
	 * Разбор характеристики из файла
	 * Исключает дубли в опциях
	 */
	private function parseProductOptions($xml) {

		$options = array();

		if ($this->config->get('exchange1c_product_options_mode') == 'feature') {
			$feature_name = '';
			$feature_value = '';

			$parse_options = array();
			foreach ($xml->ХарактеристикаТовара as $product_option) {
				$name = trim(htmlspecialchars((string)$product_option->Наименование));
				if ($this->config->get('exchange1c_delete_text_in_brackets_option') == 1) {
					$name_split = $this->splitNameStr($name,false,true);
					$name = $name_split['name'];
				}

				$value = trim(htmlspecialchars((string)$product_option->Значение));

				// пропускаем дубликаты
				if (isset($parse_options[$name])) {
					if ($parse_options[$name] == $value)
						continue;
				}
				$parse_options[$name] = $value;

				$feature_name .= ($feature_name) ? ', ' . $name : $name;
				$feature_value .= ($feature_value) ? ', ' . $value : $value;
			}

			$options[$feature_name] = array(
				'value'		=> $feature_value,
				'guid'		=> ''
			);

			$this->log("Опция: '" . $name . "' = '" . $value . "'", 2);

		} elseif ($this->config->get('exchange1c_product_options_mode') == 'related') {

			foreach ($xml->ХарактеристикаТовара as $product_option) {
				$name = trim(htmlspecialchars((string)$product_option->Наименование));
				$value = trim(htmlspecialchars((string)$product_option->Значение));
				$options[$name] = array(
					'guid'			=> (string)$product_option->Ид,
					'value'			=> $value
				);
				$this->log("Опция товара: '" . $name . "' = '" . $value . "'", 2);
			}

		} // if

		return $options;

	} // parseProductOptions()


	/**
	 * ver 4
	 * update 2018-11-09
	 * Устанавливает опции в товаре
	 */
	private function setProductOptions($product_id, $options, $quantity = 0, $image = '', $product_feature_id = 0) {

		if (empty($options)) {
			$this->errorLog(2310);
			return false;
		}

		$type = $this->config->get('exchange1c_product_options_type');

		foreach ($options as $option_name => $option_value_data) {

			$data = array();
			$option_id = $this->setOption($option_name, $type, $product_id);
			$option_value_id = $this->setOptionValue($option_value_data['value'], $option_id, $image);
			$data['subtract'] = $this->config->get('exchange1c_product_options_subtract') == 1 ? 1 : 0;
			$data['quantity'] = $quantity;

			// Пропишем опцию в товар
			if ($product_id) {


				$product_option_id = 0;
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $product_id . " AND `option_id` = " . $option_id);
				if ($query->num_rows) {
					$product_option_id = $query->row['product_option_id'];
				} else {
					$this->query("INSERT INTO `" . DB_PREFIX . "product_option` SET `product_id` = " . $product_id . ", `option_id` = " . $option_id);
					$product_option_id = $this->db->getLastId();
				}

				// Пропишем значение опции в товар
				$product_option_value_id = 0;
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_option_value` WHERE `product_id` = " . $product_id . " AND `option_value_id` = " . $option_value_id);
				if ($query->num_rows) {
					$product_option_value_id = $query->row['product_option_value_id'];
					$update_fields = $this->compareArrays($query, $data);
					if ($update_fields) {
						$this->query("UPDATE `" . DB_PREFIX . "product_option_value` SET " . $update_fields . " WHERE `product_option_value_id` = " . $query->row['product_option_value_id']);
					}
				} else {
					$this->query("INSERT INTO `" . DB_PREFIX . "product_option_value` SET `product_option_id` = " . $product_option_id . ", `product_id` = " . $product_id . ", `option_id` = " . $option_id . ", `option_value_id` = " . $option_value_id . ", `quantity` = " . $quantity);
					$product_option_value_id = $this->db->getLastId();
				}

				// Пропишем значения опции в значения характеристики
				if ($product_feature_id) {
					$query = $this->query("SELECT * FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_id` = " . $product_id . " AND `product_option_value_id` = " . $product_option_value_id);
					if ($query->num_rows) {
						$this->query("UPDATE `" . DB_PREFIX . "product_feature_value` SET `status` = 1 WHERE `product_option_value_id` = " . $product_option_value_id);
					} else {
						$this->query("INSERT INTO `" . DB_PREFIX . "product_feature_value` SET `product_feature_id` = " . $product_feature_id . ", `product_option_id` = " . $product_option_id . ", `product_id` = " . $product_id . ", `product_option_value_id` = " . $product_option_value_id . ", `status` = " . 1);
					}
				}

			}


		} // foreach

	} // setProductOptions()


	/**
	 * ver 25
	 * update 2018-11-02
	 * Разбор предложений
	 */
	private function parseOffers($xml) {

		$this->log("~Начало разбора предложений");

		if (!$xml->Предложение) {
			$this->log("parseOffers(): Пустое предложение, пропущено");
			return 0;
		}

		$count_offers = count($xml->Предложение);
		$this->statStart('offers');
		$this->log("Предложений в файле: " . $count_offers);
		$this->STAT['offers_num'] = count($xml->Предложение);

		$num_offer = 0;

		// Перебираем все предложения
		foreach ($xml->Предложение as $offer) {

			$this->log("~ПРЕДЛОЖЕНИЕ");

			// Получаем Ид товара и характеристики
			$guid = explode("#", (string)$offer->Ид);

			// Массив для хранения данных об одном предложении товара
			$data = array(
				'product_guid'	=> $guid[0],
				'feature_guid'	=> isset($guid[1]) ? $guid[1] : ''
			);

			// Есть ли связь Ид с товаром в таблице product_to_1c
			// Получим старые данные товара
			// Также получаем данные о всех характеристиках товара с остатками и ценами
			// остатки нужны для расчета общего остатка, а цены для определения основной цены
			$old_product = $this->getProductByGUID($data['product_guid'], $data['feature_guid']);
			if ($this->ERROR) return $num_offer;

			// Если товар не найден
			if ($old_product == false) {
				if ($this->config->get('exchange1c_product_not_found_stop_error')) {
					$this->errorLog(2300, $data['product_guid']);
					return $num_offer;
				} else {
					$this->log("Товар не найден по Ид " . $data['product_guid'] . ", предложение пропущено");
					continue;
				}
			}

			$product_id = $old_product['product_id'];

			// Если товар помечен на удаление, отключаем его
			$data['delete'] = 0;
			if ($offer->ПометкаУдаления) {
				if ((string)$offer->ПометкаУдаления == 'true') {
					$data['delete'] = 1;
				}
			} elseif ($offer['Статус'] == "Удален") {
				// 1С:УНФ
				$this->log("У предложения Статус = Удален");
				$data['delete'] = 1;
			} else {
				$data['delete'] = $old_product['delete'];
			}
			if ($data['delete']) {
				$this->log("Предложение пропущено, так как помечено на удаление");
				if ($old_product['status'] == 1) {
					$data['status'] = 0;
					// Обновляем товар
					$update = $this->updateOffers($product_id, $data, $old_product);
					if ($this->ERROR) return $num_offer;
				}
				continue;
			}

			$data['status'] = 1;

			$this->log("Предложение Ид: " . $data['product_guid'] . ", product_id = " . $product_id, 2);

			// Штрихкод
			if ($offer->Штрихкод) {
				$data['ean'] = trim((string)$offer->Штрихкод);
			}

			// Артикул
			if ($offer->Артикул) {
				$data['sku'] = htmlspecialchars(trim((string)$offer->Артикул));
			}

			// ОСТАТКИ
			if ($this->config->get('exchange1c_export_system') == '1c_ut10.3') {
				$data['quantity'] = 0;
			}
			if ($offer->Остатки || $offer->Количество || $offer->Склад) {
				$data['quantity'] = $this->parseQuantity($offer, $data);
				if ($this->ERROR) return $num_offer;
			}

			// ЦЕНЫ
			if ($offer->Цены && $this->config->get('exchange1c_product_price_no_import') != 1) {
				$data['price'] = $this->parsePrices($offer->Цены, $product_id);
				if ($this->ERROR) return $num_offer;
				if ($data['price'] == 0) {
					$data['price'] = $old_product['price'];
				}
			}

			$this->log($data, 2);

			// ЭТО ХАРАКТЕРИСТИКА
			if ($data['feature_guid']) {
				$this->log("Характеристика Ид: " . $data['feature_guid'], 2);

				// Если включено не загружать пустые характеристики! По идее такого не должно быть, но если так бывает
				if ($this->config->get('exchange1c_product_options_empty_ignore')) {
					if (!$data['quantity']) {
						continue;
					}
				}

				// Название характеристики из разницы наименований без скобок
				// В зависимости он настроек наименование может быть из других полей! ДОРАБОТАТЬ
				$data['feature_name'] = htmlspecialchars(trim((string)$offer->Наименование));
				$data['feature_name'] = mb_substr($data['feature_name'], mb_strlen($old_product['name'])+2, -1);
				$this->log($data['feature_name']);

				$product_feature_id = $this->setProductFeature($product_id, $data);

				if ($offer->ХарактеристикиТовара) {

					// Читаем опции из файла
					$data_options = $this->parseProductOptions($offer->ХарактеристикиТовара);
					if ($this->ERROR) return $num_offer;

					// Картинка для характеристики, берется только первая
					if ($offer->Картинка && $this->config->get('exchange1c_option_image_import') != 1) {
						$feature_image = (string)$offer->Картинка;
					} else {
						$feature_image = '';
					}

					// Сопоставим option_id и option_value_id значеням
					$this->setProductOptions($product_id, $data_options, $data['quantity'], $feature_image, $product_feature_id);
					if ($this->ERROR) return $num_offer;

				}

				// Необходимо рассчитать общий остаток по всем характеристикам
				$data_quantity_price = $this->getProductFeaturesQuantityPrice($product_id);
				$data['price_max'] = $data_quantity_price['price_max'];
				$data_price = $data_quantity_price['price'];
				// Получим массив остатков всех характеристик
				$data_quantity = $data_quantity_price['quantity'];

				// Остаток текущей характеристики
				$data_quantity[$product_feature_id] = $data['quantity'];
				// Если характеристика, остаток считаем по всем характеристикам
				$data['quantity'] = $data_quantity_price['quantity_total'];

				// Не показывать товар если цены нулевые
				if ($this->config->get('exchange1c_product_disable_if_price_zero') == 1 && $data['price_max'] == 0) {
					$this->log("Товар отключен так как цена нулевая");
					$data['status'] = 0;
					$update = $this->updateOffers($product_id, $data, $old_product);
					if ($this->ERROR) return $num_offer;
					continue;
				}

				if ($this->config->get('exchange1c_product_price_no_import') != 1) {

					// Необходимо рассчитать основную цену товара и пересчитать разницу цен опций
					if ($old_product['price']) {
						$data['price'] = $old_product['price'];
					}

					// Расчет цен опций
					if ($this->config->get('exchange1c_feature_price_mode') == 'mode2') {
						// Установим в качестве основной цены предложение с минимальной ценой
						$data['price'] = $data_quantity_price['price_min'];
					} elseif ($this->config->get('exchange1c_feature_price_mode') == 'mode3') {
						// Установим в качестве основной цены предложение с минимальной ценой только если основная цена не установлена
						if ($data['price'] == 0) {
							$data['price'] = $data_quantity_price['price_min'];
						}
					}

					// Пересчитываем все цены у существующих опций
					foreach ($data_price as $feature_price) {
						if (count($feature_price) > 1) {
							$this->log("ВНИМАНИЕ! Опций в товаре = " . count($feature_price) . ", цены и остатки у опций будут по последнему предложению! Необходим модуль связанные опции!");
						}
						foreach ($feature_price as $feature_value) {
							$option_price = $feature_value['price'] - $data['price'];
							$price_prefix = ($option_price >= 0) ? '+' : '-';
							$this->query("UPDATE `" . DB_PREFIX . "product_option_value` SET `price` = '" . $option_price . "', `price_prefix` = '" . $price_prefix . "' WHERE `product_option_value_id` = " . $feature_value['product_option_value_id']);

						} // foreach
					} // foreach
				} // if

			} else {
				$data['feature_guid'] = '';
			}

			// Статус на складе при наличии и отсутствии
			if ($data['quantity']) {
				if ((int)$this->config->get('exchange1c_product_stock_status_on')) {
					$data['stock_status_id'] = (int)$this->config->get('exchange1c_product_stock_status_on');
				}
			} else {
				if ((int)$this->config->get('exchange1c_product_stock_status_off')) {
					$data['stock_status_id'] = (int)$this->config->get('exchange1c_product_stock_status_off');
				}
			}

			// Обновляем товар
			$update = $this->updateOffers($product_id, $data, $old_product);
			if ($this->ERROR) return $num_offer;

			// Удалим пустые опции у товара
			$query = $this->query("SELECT `product_option_id` FROM `" . DB_PREFIX . "product_option` WHERE `product_id` = " . $product_id);
			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					$query_value = $this->query("SELECT `product_option_value_id` FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_id` = " . $row['product_option_id']);
					if (!$query_value->num_rows) {
						// нет значений, тогда удалим опцию
						$this->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_option_id` = " . $row['product_option_id']);
						$this->log("Удалены пустая опциия в товаре, product_option_id = " . $row['product_option_id']);
					}
				}
			}

			unset($data);
			$num_offer++;

		} // foreach()

		// После загрузки всех предложений удалим неиспользуемые опции
		$query = $this->query("SELECT `product_option_id`, `product_option_value_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `status` = 0");
		if ($query->num_rows) {
			$delete_options = array();
			foreach ($query->rows as $feature_value) {
				$delete_options[$feature_value['product_option_value_id']] = $feature_value['product_option_id'];
			}
			$this->log("К удалению опции:");
			$this->log($delete_options);
			foreach ($delete_options as $product_option_value_id => $product_option_id) {
				if ($product_option_value_id == 0) {
					$this->query("DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_option_value_id` = 0");
				} else {
					$this->query("DELETE FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_option_value_id` = " . $product_option_value_id);
					$this->query("DELETE FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_value_id` = " . $product_option_value_id);

					// После удаления значений, если опция пустая удалим опцию
					$query_count_options = $this->query("SELECT count(*) as `count` FROM `" . DB_PREFIX . "product_option_value` WHERE `product_option_id` = " . $product_option_id);
					if ($query_count_options->row['count'] == 0) {
						$this->query("DELETE FROM `" . DB_PREFIX . "product_option` WHERE `product_option_id` = " . $product_option_id);
					}
				}
			}


		}

		$this->logStat('offers');

		$this->log("Загружено предложений " . $num_offer . " из " . $count_offers);

		return $num_offer;

	} // parseOffers()


	/**
	 * ver 3
	 * update 2017-08-18
	 * Проверяет на наличие полной выгрузки в каталоге или в предложениях
	 */
	private function checkFullImport($xml) {

		if ($xml['СодержитТолькоИзменения']) {

			$this->FULL_IMPORT = (string)$xml['СодержитТолькоИзменения'] == "false" ? true : false;

		} elseif ($xml->СодержитТолькоИзменения) {

			$this->FULL_IMPORT = (string)$xml->СодержитТолькоИзменения == "false" ? true : false;

		}

		if ($this->FULL_IMPORT) {
			$this->log("ЗАГРУЗКА ПОЛНАЯ");
		} else {
			$this->log("ЗАГРУЗКА ТОЛЬКО ИЗМЕНЕНИЙ");
		}

	} // checkFullImport()


	/**
	 * ver 7
	 * update 2018-11-08
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
		if ($this->config->get('exchange1c_price_import_mode') != 'disable') {
			if ($xml->ТипыЦен) {
				$this->parseClassifierPriceType($xml->ТипыЦен);
				if ($this->ERROR) return false;
			}
		}

		// Обнуление остатков
		if ($this->config->get('exchange1c_flush_quantity') && $this->FULL_IMPORT) {
			$this->clearProductsQuantity();
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
	 * ver 6
	 * update 2018-03-10
	 * Меняет статусы у новых заказов заказов
	 *
	 * @param	int		exchange_status
	 * @return	bool
	 */
	public function queryOrdersChangeStatus($orders) {

		// Если статус новый пустой, тогда не меняем, чтобы не породить ошибку
		$new_status = $this->config->get('exchange1c_order_status_exported');
		if (!$new_status) {
			$this->errorLog(2101, $new_status);
			return false;
		}

		// Уведомление при смене статуса
		$notify = 0;

		if ($orders) {

			$this->NOW = date('Y-m-d H:i:s');

			foreach ($orders as $order_id => $order_status_id) {

				// Пропускаем те у кого статус не равен "Статус для выгрузки"
				if ($order_status_id != $this->config->get('exchange1c_order_status_export')) {
					$this->log("> Cтатус заказа #" . $order_id . " не менялся.", 2);
					continue;
				}

				// Меняем статус
				$query = $this->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = " . (int)$new_status . " WHERE `order_id` = " . (int)$order_id);
				$this->log("> Изменен статус заказа #" . $order_id);

				// Добавляем историю в заказ
				$query = $this->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = " . (int)$order_id . ", `comment` = 'Заказ выгружен в учетную систему', `order_status_id` = " . (int)$new_status . ", `notify` = " . $notify . ", `date_added` = '" . $this->NOW . "'");
				$this->log("> Добавлена история в заказ (изменен статус) #" . $order_id, 2);
			}
		}

		return true;

	}  // queryOrdersStatus()


	/**
	 * Получает название статуса документа на текущем языке
	 *
	 */
	private function getOrderStatusName($order_staus_id) {
		if (!$this->LANG_ID) {
			$this->LANG_ID = $this->getLanguageId($this->config->get('config_language'));
		}
		$query = $this->query("SELECT `name` FROM `" . DB_PREFIX . "order_status` WHERE `order_status_id` = " . (int)$order_staus_id . " AND `language_id` = " . $this->LANG_ID);
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
	 * ver 4
	 * update 2017-06-19
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
			$query = $this->query("SELECT `product_feature_id` FROM `" . DB_PREFIX . "product_feature_value` WHERE `product_option_value_id` = " . (int)$order_option['product_option_value_id']);

			if ($query->num_rows) {
				if ($product_feature_id) {
					if ($product_feature_id != $query->row['product_feature_id']) {
						$this->errorLog(2006);
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
			$query = $this->query("SELECT `guid` FROM `" . DB_PREFIX . "product_feature` WHERE `product_feature_id` = " . (int)$product_feature_id);
			if ($query->num_rows) {
				$feature_guid = $query->row['guid'];
			}
		}

		return $feature_guid;

	} // getFeatureGUID


	/** ****************************** ФУНКЦИИ ДЛЯ ВЫГРУЗКИ ЗАКАЗОВ *******************************/


	/**
	 * ver 2
	 * update 2018-04-09
	 * Формирует адрес с полями и представлением в виде массива
	 */
	private function setCustomerAddress($order, $mode = 'shipping') {

		// Соответствие полей в XML и в базе данных
		$fields = array(
			'Почтовый индекс' 	=> 'postcode',
			//'Страна' 			=> 'country',
			'Регион'			=> 'zone',
			'Район'				=> 'none',
			'Населенный пункт'	=> 'none',
			'Город'				=> 'city',
			'Адрес'				=> 'address_1',
			'Улица'				=> 'street',
			'Дом'				=> 'house',
			'Корпус'			=> 'building',
			'Квартира'			=> 'flat'
		);
		// Представление: Индекс, Город, Улица, Дом, Корпус, Квартира
		// Представление: Индекс, Город, Улица, Дом, Квартира
		// Представление: Индекс, Город, Улица, Дом
		//'Представление'	=> $order['shipping_postcode'] . ', ' . $order['shipping_zone'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_address_1'] . ', '.$order['shipping_address_2'],

		$address = array();
		$counter = 0;

		// Представление
		$arName = array();

		// Формирование полей
		foreach ($fields as $type => $field) {

			if (isset($order[$mode . '_' . $field])) {

				// Формируем типы полей
				//$address['АдресноеПоле' . $counter] = array(
				//	'Тип'		=> $type,
				//	'Значение' 	=> $order[$mode . '_' . $field]
				//);

				// формируем наименование
				$arName[] = $order[$mode . '_' . $field];

			}
		}

		$address['Представление'] = implode(', ', $arName);

		return $address;

	} // setCustomerAddress()


	/**
	 * ver 2
	 * update 2018-04-02
	 * Формирует контактные данные контрагента
	 */
	private function setCustomerContacts($order) {
        $this->log($order, 2);
		// Соответствие полей в XML и в базе данных
		$fields = array(
			'Телефон Рабочий'	=> 'telephone',
			'Телефон'			=> 'telephone',
			'Почта'				=> 'email'
		);

		$contact = array();
		$counter = 0;

		// Формирование полей
		foreach ($fields as $type => $field) {

			if (isset($order[$field])) {

				// Формируем типы полей
				$contact['Контакт' . $counter] = array(
					'Тип'				=> $type,
					'Значение' 			=> $order[$field]
				);
			}
			$counter++;
		}
		return $contact;

	} // setCustomerContacts()


	/**
	 * ver 2
	 * update 2017-06-03
	 * Формирует реквизиты документа
	 */
	private function setDocumentRequisites($order, $document) {

		$requisites = array();
		// Счетчик
		$counter = 0;

		$requisites['Дата отгрузки'] 				= $order['date'];
		$requisites['Статус заказа'] 				= $this->getOrderStatusName($order['order_status_id']);
		$requisites['Вид цен'] 						= $this->getPriceTypeName($order['customer_group_id']);
		$requisites['Контрагент'] 					= $order['username'];
//		$requisites['Склад'] 						= $this->getWarehouseName($order['warehouse_id']);
//		$requisites['Организация'] 					= 'Наша фирма';
//		$requisites['Подразделение'] 				= 'Интернет-магазин';
//		$requisites['Сумма включает НДС'] 			= 'true';
//		$requisites['Договор контрагента'] 			= 'Основной договор';
//		$requisites['Метод оплаты'] 				= 'Заказ по телефону';

		// Для 1С:Розница
//		$requisites['ТочкаСамовывоза'] 				= 'Название магазина';
//		$requisites['ВидЦенНаименование'] 			= 'Розничная';
//		$requisites['СуммаВключаетНДС'] 			= 'true';
//		$requisites['НаименованиеСкидки'] 			= 'Скидка 5%';
//		$requisites['ПроцентСкидки']				= 5;
//		$requisites['СуммаСкидки']					= 1000;
//		$requisites['СкладНаименование']			= 'Основной склад';
//		$requisites['ПодразделениеНаименование']	= 'Основное подразделение';
//		$requisites['Склад']						= 'Основной склад'

		// Для УНФ XML 2.08
//		$requisites['ВидЦен'] 						= 'Розничная';
//		$requisites['СкладДляПодстановкиВЗаказы'] 	= 'Склад основной';


		$data = array();
		foreach ($requisites as $name => $value) {

			// Пропускаем пустые значения
			if (!$value) continue;

			$data['ЗначениеРеквизита'.$counter]	= array(
				'Наименование'		=> $name,
				'Значение'			=> $value
			);

			$counter ++;

		} // foreach

		return $data;

	} // setDocumentRequisites()


	/**
	 * ver 3
	 * update 2018-04-09
	 * Получает информацию о покупателе (организации и физ.лице)
	 */
	public function getCustomerInfo(&$order) {

		$query = $this->query("SELECT `firstname`,`lastname`,`middlename`,`company`,`company_inn`,`company_kpp` FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '" . (int)$order['customer_id'] . "'");
		if ($query->num_rows) {
			$order['firstname'] = $query->row['firstname'];
			$order['lastname'] = $query->row['lastname'];
			$order['middlename'] = $query->row['middlename'];
			$order['company'] = $query->row['company'];
			$order['company_inn'] = $query->row['company_inn'];
			$order['company_kpp'] = $query->row['company_kpp'];
		}

	} // getCustomerInfo()


	/**
	 * ver 6
	 * update 2018-07-11
	 * Формирует Контрагента
	 */
	private function setCustomer($order) {

		$customer = array();
		$this->log("Фамилия: " . $order['lastname'], 2);
		$this->log("Имя: " . $order['firstname'], 2);
		$this->log("ФИО: " . $order['username'], 2);

		if (empty($order['username'])) {
			$this->errorLog(2110);
			return false;
		}

		// Проверка на ошибки, если вбито например Иванов Иван Иванович в одно поле "Имя"
		$fio = explode(" ", trim($order['username']));

		if (empty($order['firstname'])) {
			if (count($fio) == 1) {
				$this->errorLog(2111);
				return false;
			}
		}

		if (empty($order['lastname'])) {
			if (count($fio) == 1) {
				$this->errorLog(2112);
				return false;

			} else if (count($fio) == 2) {
				$order['lastname'] = $fio[1];
				$order['firstname'] = $fio[0];

			} else if (count($fio) == 3) {
				$order['lastname'] = $fio[0];
				$order['firstname'] = $fio[1];
				$order['middlename'] = $fio[2];
			}
		}

		// Обязательные поля покупателя для торговой системы
		$customer = array(
			'Ид'					=> $order['customer_id'] . '#' . $order['email'],
			'Роль'					=> 'Покупатель',
			'Наименование'			=> trim($order['username']),
			'ПолноеНаименование'	=> trim($order['username']),
			'Фамилия'				=> trim($order['lastname']),
			'Имя'					=> trim($order['firstname']),
			'Телефон'				=> array(
				'Представление' => $order['telephone']
				),
			'Email'					=> array(
				'Представление' => $order['email']
				),
			'АдресРегистрации'		=> $this->setCustomerAddress($order),
		);

		// Для УТ 10.3, 11.x это фактический адрес физ. лица
		if (in_array($this->config->get('exchange1c_export_system'), array('1c_ut11','1c_ut10.3'))) {

			/** ФИЗИЧЕСКОЕ ЛИЦО */

			$customer['Адрес']		= $customer['АдресРегистрации'];
			$customer['Контакты'] = array(
				'Контакт1' => array(
					'Тип'			=> "Телефон Рабочий",
					'Значение' 		=> $order['telephone'],
					'Комментарий'	=> "Загружено с сайта"
				),
				'Контакт2' => array(
					'Тип'			=> "Почта",
					'Значение' 		=> $order['email'],
					'Комментарий'	=> "Загружено с сайта"
				),
				'Контакт3' => array(
					'Тип'			=> "Фактический адрес",
					'Значение' 		=> $customer['АдресРегистрации'],
					'Комментарий'	=> "Загружено с сайта"
				)
			);

			/** ОРГАНИЗАЦИЯ	*/

            if ($order['payment_company']) {
            	// Наименование организации
            	$customer['ОфициальноеНаименование']	= $order['payment_company'];
				$customer['Представители']				= array(
					'Представитель' => array(
						'Отношение'			=> 'Контактное лицо',
						'Наименование'		=> $order['username']
					)
				);
				if (isset($order['payment_company_inn'])) {
					$customer['ИНН'] = $order['payment_company_inn'];
				}
				if (isset($order['payment_company_kpp'])) {
					$customer['КПП'] = $order['payment_company_kpp'];
				}
            	$customer['ЮридическийАдрес']	= $customer['АдресРегистрации'];
            	$customer['АдресРегистрации']	= $customer['АдресРегистрации'];
			}

		} elseif (in_array($this->config->get('exchange1c_export_system'), array('1c_unf16'))) {
			// УНФ

			/** ОРГАНИЗАЦИЯ	*/

			if ($order['payment_company']) {
	//			$customer['Адрес']						= $this->setCustomerAddress($order);
				$customer['ЮридическийАдрес']			= $customer['АдресРегистрации'];
				$customer['ПолноеНаименование']			= $order['username'];
			}
		}


		$this->log('setCustomer():', 2);
		$this->log($customer, 2);
		return $customer;

	} // setCustomer()


	/**
	 * ver 1
	 * update 2018-03-10
	 * Получает список заказов на экспорт
	 */
	public function queryOrdersExport() {
		$orders_export = array();

		// Выгрузка измененных заказов
		if ($this->config->get('exchange1c_orders_export_modify')) {

			$this->log($this->config->get('exchange1c_order_date'), 2);
			if ($this->config->get('exchange1c_order_date')) {
				$from_date = str_replace('T',' ',$this->config->get('exchange1c_order_date')) . ":00";
			} else {
				// При первом обмене это поле будет пустым, если не изменено вручную. Для пустого поля зададим начало столетия
				$from_date = '2001-01-01 00:00:00';
			}
			$this->log($from_date , 2);

			// По текущую дату и время
			$to_date = date('Y-m-d H:i:s');

			// Этот запрос будет использовать индексы поля date_modified
			$query = $this->query("SELECT `order_id`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE `date_modified` BETWEEN STR_TO_DATE('" . $from_date . "', '%Y-%m-%d %H:%i:%s') AND STR_TO_DATE('" . $to_date . "', '%Y-%m-%d %H:%i:%s')");

			if ($query->num_rows) {
				foreach ($query->rows as $row) {
					$orders_export[$row['order_id']] = $row['order_status_id'];
				}
			}
		}

		// Выгрузка заказов со статусом
		if ($this->config->get('exchange1c_order_status_export') != 0) {

			$query = $this->query("SELECT `order_id`,`order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . (int)$this->config->get('exchange1c_order_status_export'));

			if ($query->num_rows) {

				foreach ($query->rows as $row) {

					// Пропускаем если такой заказ уже выгружается
					if (isset($orders_export[$row['order_id']])) {
						continue;
					}

					$orders_export[$row['order_id']] = $row['order_status_id'];
				}
			}
		}

		$this->log('queryOrdersExport():', 2);
		return $orders_export;

	} // queryOrdersExport()


	/**
	 * ver 14
	 * update 2018-10-05
	 * Выгружает заказы в торговую систему
	 */
	public function queryOrders() {

		$this->log("~ЭКСПОРТ ЗАКАЗОВ В УЧЕТНУЮ СИСТЕМУ",2);

		$orders_export = $this->queryOrdersExport();

		// Валюта документа
		$currency = $this->config->get('exchange1c_order_currency') ? $this->config->get('exchange1c_order_currency') : 'руб.';

		$document = array();

		if (count($orders_export)) {

			$document_counter = 0;

			$this->load->model('customer/customer_group');
			$this->load->model('sale/order');

			foreach ($orders_export as $order_id => $order_status_id) {

				$order = $this->model_sale_order->getOrder($order_id);
				$this->log("Заказ #" . $order['order_id']);
				$this->log($order, 2);


				// Если при оформлении заказа покупатель зарегистрировался
				if ($order['customer_id']) {
					$this->getCustomerInfo($order);
				}

				$order['date'] = date('Y-m-d', strtotime($order['date_added']));
				$order['time'] = date('H:i:s', strtotime($order['date_added']));
				$customer_group = $this->model_customer_customer_group->getCustomerGroup($order['customer_group_id']);

				// Шапка документа
				$document['Документ' . $document_counter] = array(
					 'Ид'          => $order['order_id']
					,'Номер'       => $order['order_id']
					,'Дата'        => $order['date']
					,'Время'       => $order['time']
					,'Валюта'      => $currency
					,'Курс'        => 1
					,'ХозОперация' => 'Заказ товара'
					,'Роль'        => 'Продавец'
					,'Сумма'       => $order['total']
					,'Комментарий' => $order['comment']
					//,'Соглашение'  => $customer_group['name'] // the agreement
				);

				if ($this->config->get('exchange1c_order_customer_default')) {
					$order['username'] = htmlspecialchars(trim($this->config->get('exchange1c_order_customer_default')));
					$order['firstname'] = $order['username'];
					$order['lastname'] = "";
				} else {
					// Первая буква должна быть заглавной и убираем лишние пробелы сдева и справа
					// ТОЛЬКО ДЛЯ САЙТА РАБОТАЮЩЕГО НА КОДИРОВКЕ UTF-8
					$order['lastname'] = mb_convert_case(trim($order['lastname']), MB_CASE_TITLE, "UTF-8");
					$order['firstname'] = mb_convert_case(trim($order['firstname']), MB_CASE_TITLE, "UTF-8");
					if (isset($order['middlename']))
						$order['middlename'] = mb_convert_case(trim($order['middlename']), MB_CASE_TITLE, "UTF-8");
					else
						$order['middlename'] = '';

					// Собираем полное наименование покупателя, ФИО
					$order['username'] =  $order['lastname'] . ' ' . $order['firstname'] . ($order['middlename'] ? ' ' . $order['middlename'] : '');
				}

				// ПОКУПАТЕЛЬ (КОНТРАГЕНТ)
				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = $this->setCustomer($order);
				if ($this->ERROR) return false;

				// РЕКВИЗИТЫ ДОКУМЕНТА
				$document['Документ' . $document_counter]['ЗначенияРеквизитов'] = $this->setDocumentRequisites($order, $document);
				if ($this->ERROR) return false;

				// ТОВАРЫ ДОКУМЕНТА
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

					// Если не заданы единицы измерений товара, выгружаем базовую
					if ($this->config->get('exchange1c_export_system') == '1c_ut11') {
						$current_product['БазоваяЕдиница'] = array(
							'Код' 					=> '796',
							'НаименованиеПолное' 	=> 'Штука'
						);
					}

					// Характеристики
					$feature_guid = $this->getFeatureGuid($product['order_product_id'], $order_id);
					if ($feature_guid) {
						$current_product['Ид'] .= "#" . $feature_guid;
					}

					// Доставка в комментарий
					$query = $this->query("SELECT `title` FROM `" . DB_PREFIX . "order_total` WHERE `order_id` = " . $order_id . " AND `code` = 'shipping'");
					if ($query->num_rows) {
						$document['Документ' . $document_counter]['Комментарий'] .= "\nДоставка: " . $query->row['title'];
					}
					// Доставка в комментарий

					$product_counter++;
				}

				$document_counter++;

			} // foreach ($query->rows as $orders_data)

		} // if (count($orders_export))
		//$this->log($document, 2);

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

	} // queryOrders()


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
	 * ver 4
	 * update 2017-09-08
	 * Возвращает валюту по коду
	 * НЕ ИСПОЛЬЗУЕТСЯ
	 */
	private function getCurrencyByCode($code) {

		$data = array();

		if ($code == "643") {

			// Это временнон решение
			$data['currency_id'] = $this->getCurrencyId("RUB");
			if ($this->ERROR) return false;

			$data['currency_code'] = "RUB";
			$data['currency_value'] = $this->getCurrencyValue("RUB");


		} else {

			$data['currency_id'] = $this->getCurrencyId($code);
			if ($this->ERROR) return false;

			$data['currency_code'] = $code;
			$data['currency_value'] = $this->getCurrencyValue($code);

		}

		$this->log('getCurrencyByCode():', 2);
		$this->log($data, 2);
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
			$query_feature = $this->query("SELECT `pfv`.`product_option_value_id`,`pf`.`name` FROM `" . DB_PREFIX . "product_feature_value` `pfv` LEFT JOIN `" . DB_PREFIX . "product_feature` `pf` ON (`pfv`.`product_feature_id` = `pf`.`product_feature_id`) WHERE `pfv`.`product_feature_id` = " . (int)$product_feature_id . " AND `pfv`.`product_id` = " . (int)$product_id);
			$this->log($query_feature,2);
			foreach ($query_feature->rows as $row_feature) {
				$query_options = $this->query("SELECT `pov`.`product_option_id`,`pov`.`product_option_value_id`,`po`.`value`,`o`.`type` FROM `" . DB_PREFIX . "product_option_value` `pov` LEFT JOIN `" . DB_PREFIX . "product_option` `po` ON (`pov`.`product_option_id` = `po`.`product_option_id`) LEFT JOIN `" . DB_PREFIX . "option` `o` ON (`o`.`option_id` = `pov`.`option_id`) WHERE `pov`.`product_option_value_id` = " . (int)$row_feature['product_option_value_id']);
				$this->log($query_options,2);
				foreach ($query_options->rows as $row_option) {
					$this->query("INSERT INTO `" . DB_PREFIX . "order_option` SET `order_id` = " . (int)$order_id . ", `order_product_id` = " . (int)$order_product_id . ", `product_option_id` = " . (int)$row_option['product_option_id'] . ", `product_option_value_id` = " . (int)$row_option['product_option_value_id'] . ", `name` = '" . $this->db->escape($row_option['value']) . "', `value` = '" . $this->db->escape($row_feature['name']) . "', `type` = '" . $this->db->escape($row_option['type']) . "'");
					$order_option_id = $this->db->getLastId();
					$this->log("order_option_id: ".$order_option_id,2);
				}
			}
		}
		$this->log("Записаны опции в заказ",2);

	} // setOrderProductOptions()


	/**
	 * ver 1
	 * update 2018-03-20
	 * Обновляет товар в заказе
	 */
	private function updateOrderProduct($order_id, $order_product_data, $order_product_id) {

		$this->log($order_product_data, 2);

		$this->query("UPDATE `" . DB_PREFIX . "order_product`
			SET `product_id` = " . (int)$order_product_data['product_id'] . ",
			`order_id` = " . (int)$order_id . ",
			`name` = '" . $this->db->escape($order_product_data['name']) . "',
			`model` = '" . $this->db->escape($order_product_data['model']) . "',
			`price` = " . (float)$order_product_data['price'] . ",
			`quantity` = " . (float)$order_product_data['quantity'] . ",
			`total` = " . (float)$order_product_data['total'] . ",
			`tax` = " . (float)$order_product_data['tax'] . ",
			`reward` = " . (int)$order_product_data['reward'] . "
			WHERE `order_product_id` = " . (int)$order_product_id
		);
		$this->log("Товар '" . $order_product_data['name'] . "' обновлен в заказе #" . $order_id . ", order_product_id = " . $order_product_id, 2);

		// ОПЦИИ ТОВАРА
		if ($order_product_data['product_feature_id']) {

			// Получим все опции товара
			$this->load->model('catalog/product');
			$product_options_data = $this->model_catalog_product->getProductOptions($order_product_data['product_id']);

			//$product_options_data = $this->getProductOptions($order_product_data['product_id']);
			$this->log($product_options_data, 2);
			if (count($product_options_data) == 0) {
				// Опции в товаре нет
				$this->errorLog(2400);
				return false;
			}

			// Получим опции в заказе
			$order_product_options_data = $this->model_sale_order->getOrderOptions($order_id, $order_product_id);
			$this->log($order_product_options_data, 2);

			// Получим опции по характеристике, то есть по product_feature_id
			$query_feature_value = $this->query("SELECT pfv.product_option_id, pfv.product_option_value_id, od.name, ovd.name as value, o.type FROM `" . DB_PREFIX . "product_feature_value` pfv
				LEFT JOIN `" . DB_PREFIX . "product_option_value` pov ON (pfv.product_option_value_id = pov.product_option_value_id)
				LEFT JOIN `" . DB_PREFIX . "option` o ON (pov.option_id = o.option_id)
				LEFT JOIN `" . DB_PREFIX . "option_description` od ON (pov.option_id = od.option_id)
				LEFT JOIN `" . DB_PREFIX . "option_value_description` ovd ON (pov.option_value_id = ovd.option_value_id)
				WHERE pfv.product_feature_id = " . (int)$order_product_data['product_feature_id']);
			$this->log($query_feature_value, 2);

			// Сохраним order_option_id во временный массив
			$old_order_option_values = array();
			foreach ($order_product_options_data as $order_product_option) {
				$old_order_option_values[$order_product_option['order_option_id']] = $order_product_option['order_option_id'];
				$this->log($order_product_option, 2);
			}

			// ПОИЩЕМ ОПЦИИ В ЗАКАЗЕ
			foreach ($query_feature_value->rows as $option) {
				$order_option_id = 0;
				foreach ($order_product_options_data as $order_option) {
					if ($option['product_option_id'] == $order_option['product_option_id'] && $option['product_option_value_id'] == $order_option['product_option_value_id']) {
						$order_option_id = $order_option['order_option_id'];
						$found = true;
						unset($old_order_option_values[$order_option_id]);
					}
				}
				if (!$order_option_id) {
					// Добавим
					$this->query("INSERT INTO `" . DB_PREFIX . "order_option` SET order_id = " . (int)$order_id . ", order_product_id = " . (int)$order_product_id . ", product_option_id = " . (int)$option['product_option_id'] . ", product_option_value_id " . (int)$option['product_option_value_id'] . ", name = '" . $this->db->escape($option['name']) . "', value = '" . $option['value'] . "', type = '" . $option['type'] . "'");
					$order_option_id = $this->db->getLastId();
					$this->log("Добавлена опция в заказ, order_option_id = " . $order_option_id);
				}
			}

			// УДАЛЕНИЕ СТАРЫХ НЕИСПОЛЬЗУЕМЫХ ОПЦИЙ ИЗ ЗАКАЗА
			if (count($old_order_option_values)) {
				foreach($old_order_option_values as $order_option_id) {
					$this->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE order_option_id = " . (int)$order_option_id);
				}
			}
		} // if ($order_product_data['product_feature_id'])
		//ОПЦИИ ТОВАРА

	} // updateOrderProduct()


	/**
	 * ver 2
	 * update 2017-04-05
	 * Меняет статус заказа
	 */
	private function getOrderStatusLast($order_id) {

		$order_status_id = 0;
		$query = $this->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_history` WHERE `order_id` = " . (int)$order_id . " ORDER BY `date_added` DESC LIMIT 1");
		if ($query->num_rows) {
			$this->log("<== getOrderStatusLast() return: " . $query->row['order_status_id'],2);
			$order_status_id = $query->row['order_status_id'];
		}
		$this->log("Получен статус заказа = " . $order_status_id, 2);
		return $order_status_id;
	}


	/**
	 * ver 6
	 * update 2018-04-23
	 * Если изменился статус заказа, добавляем в историю
	 */
	private function changeOrderStatus($order_id, $status_name, $canceled = false) {

		if ($canceled) {
			// Устанавливаем статус отмененного заказа
			$new_order_status_id = $this->config->get('exchange1c_order_status_canceled');

		} else {

			$query = $this->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order_status` WHERE `language_id` = " . $this->LANG_ID . " AND `name` = '" . $this->db->escape($status_name) . "'");
			if ($query->num_rows) {
				$new_order_status_id = (int)$query->row['order_status_id'];
			} else {
				$this->log("Статус заказа '" . $status_name . "' не найден!");
				$this->errorLog(2207, $order_id, $status_name);
				return false;
	 		}
			$this->log("[i] Найден status_id=" . $new_order_status_id . " по названию '" . $status_name . "'", 2);

		}

		// получим старый статус
		$order_status_id = $this->getOrderStatusLast($order_id);
		if (!$order_status_id) {
			$this->log("ВНИМАНИЕ! У заказа еще нет ни одной записи в истории статуса заказа!");
		}

		if ($order_status_id == $new_order_status_id) {
			$this->log("Статус документа не изменился");
			return 0;
		}

		// Меняем статус если он равен начальному
		//if ((int)$this->config->get('exchange1c_order_status_export') != (int)$order_status_id) {
		//	$this->log("Статус документа не меняем так как он уже не имеет статуса указанного для выгрузки");
		//	return 0;
		//}

		// если он изменился, изменим в заказе
		$this->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = " . (int)$order_id . ", `order_status_id` = " . (int)$new_order_status_id . ", `date_added` = '" . $this->NOW . "', `comment` = 'Change auto from trade system'");

		// Обновим статус в заказе
		//$this->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = " . (int)$new_order_status_id . ", `date_modified` = '" . $this->NOW . "' WHERE `order_id` = " . (int)$order_id);
		$this->query("UPDATE `" . DB_PREFIX . "order` SET `order_status_id` = " . (int)$new_order_status_id . " WHERE `order_id` = " . (int)$order_id);

		$this->log("Изменен статус документа",2);
		return $order_status_id;

	} // changeOrderStatus()


	/**
	 * ver 7
	 * update 2018-06-09
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

			$order_fields['currency_id'] = $doc['currency']['currency_id'];
			$order_fields['currency_code'] = $doc['currency']['code'];
			$order_fields['currency_value'] = $doc['currency']['value'];
		}

		// проверим сумму
		if (!empty($doc['total'])) {
			if ($doc['total'] != $order['total']) {
				$order_fields['total'] = $doc['total'];
			}
		}

		// Временная заплатка!!!
		// Проверим ФИО
		if (isset($doc['firstname']) && isset($order['firstname'])) {
			if ($doc['firstname'] != $order['firstname']) {
				$order_fields['firstname'] = $doc['firstname'];
			}
		}

		if (isset($doc['lastname']) && isset($order['lastname'])) {
			if ($doc['lastname'] != $order['lastname']) {
				$order_fields['lastname'] = $doc['lastname'];
			}
		}

		if (isset($doc['middlename']) && isset($order['middlename'])) {
			if ($doc['middlename'] != $order['middlename']) {
				$order_fields['middlename'] = $doc['middlename'];
			}
		}

		// статус заказа
		if (!empty($doc['status'])) {

			// Заказ был завершен со статусом отмены в учетной системе
			$canceled = false;
			if (isset($doc['canceled'])) {
				if ($doc['canceled'] == 'true') {
					$this->log("Заказ был отменен в учетной системе", 2);
					$canceled = true;
				}
			}

			$this->changeOrderStatus($doc['order_id'], $doc['status'], $canceled);
			if ($this->ERROR) return false;
		}

		$update = false;

		$old_products = $products;

		// Сумма товаров, нужна для расчета стоимости доставки
		$product_total = 0;

		// проверим товары, порядок должен быть такой же как и в торговой системе
		// Если порядок будет отличаться, то товары будут заменены
		if (!empty($doc['products'])) {
			$this->log("Обработка товаров документа...");

			foreach ($doc['products'] as $key => $doc_product) {

            	$this->log("Товар: ".$doc_product['name'],2);

				$order_product_fields = array();
				$order_option_fields = array();
				$product_total += $doc_product['total'];

				if (isset($products[$key])) {
					// проверим товар
					$product = $products[$key];
					$this->log($product, 2);

					// Сравним товар
					if ($product['product_id'] != $doc_product['product_id']) {
						// заменим товар
					} else {
						$num_str = $key+1;
						$this->log("В строке " . $num_str . " товар не изменился");

						// Проверим цену, количество, налоги, сумму
						if ($product['price'] != $doc_product['price']) {
							$this->log("Изменена цена");
							$update = true;
						}
						if ($product['quantity'] != $doc_product['quantity']) {
							$this->log("Изменено количество");
							$update = true;
						}
						if ($product['tax'] != $doc_product['tax']) {
							$this->log("Изменена ставка налога");
							$update = true;
						}
						$this->log($doc_product, 2);
						if ($update) {
							$this->updateOrderProduct($doc['order_id'], $doc_product, $product['order_product_id']);
							if ($this->ERROR) return false;
						}
					}
					// Тестовая строка для принудительного обновления товаров в документе
					//$this->updateOrderProduct($doc['order_id'], $doc_product, $product['order_product_id']);
					//$this->errorLog(5000);

				} else {
					// Добавить строчку
					$this->log("Добавление товара '" . $doc_product['name'] . "' в документ");

					$this->query("INSERT INTO `" . DB_PREFIX . "order_product`
						SET `product_id` = " . (int)$doc_product['product_id'] . ",
						`order_id` = " . (int)$doc['order_id'] . ",
						`name` = '" . $this->db->escape($doc_product['name']) . "',
						`model` = '" . $this->db->escape($doc_product['model']) . "',
						`price` = " . (float)$doc_product['price'] . ",
						`quantity` = " . (float)$doc_product['quantity'] . ",
						`total` = " . (float)$doc_product['total']
					);
					$this->log("Товар '" . $doc_product['name'] . "' добавлен в заказ #" . $doc['order_id'], 2);
					$order_product_id = $this->db->getLastId();

					$update = true;
				}


			} // foreach

//			foreach ($old_products as $product) {
//				$this->query("DELETE FROM `" . DB_PREFIX . "order_product` WHERE `order_product_id` = " . (int)$product['order_product_id']);
//				$this->query("DELETE FROM `" . DB_PREFIX . "order_option` WHERE `order_product_id` = " . (int)$product['order_product_id']);
//				$this->log("Удалены товары и опции в заказе",2);
//				if ($this->ERROR) return false;
//			}
		} // if

		if ($doc['total'] != $order['total']) {
			$order_fields['total'] = $doc['total'];
		}

		$sql = "UPDATE `" . DB_PREFIX . "order` SET ";
		if ($order_fields){
			$sql_set = "";
			foreach ($order_fields as $field => $value) {
				$sql_set .= ($sql_set ? ", `" : "`") . $field . "` = '" . $value . "'";
			}
			$this->log($sql_set, 2);
			$this->query($sql . $sql_set . " WHERE `order_id` = " . $order['order_id']);

			// Обновим сумму заказа и дату модификации
//			$this->query("UPDATE `" . DB_PREFIX . "order` SET
//			`total` = " . (float)$doc['total'] . ",
//			`date_modified` = NOW()
//			WHERE `order_id` = " . (int)$order['order_id']);
//			$this->log("Обновлено в документе: Итого",2);

			// ИТОГИ
			// Вычислим сумму доставки
			$shipping_total = $doc['total'] - $product_total;
			$this->query("UPDATE `" . DB_PREFIX . "order_total` SET
			`value` = " . (float)$shipping_total . "
			WHERE `order_id` = " . (int)$order['order_id'] . " AND
			`code` = 'shipping'");
			$this->log("Сумма доставки = " . $shipping_total, 2);

			// Итоги по таблице товаров
			$this->query("UPDATE `" . DB_PREFIX . "order_total` SET
			`value` = " . (float)$product_total . "
			WHERE `order_id` = " . (int)$order['order_id'] . " AND
			`code` = 'sub_total'");
			$this->log("Сумма товаров = " . $product_total, 2);

			// Обновим тоталы, разницу между суммой товаров закинем в доставку
			$this->query("UPDATE `" . DB_PREFIX . "order_total` SET
			`value` = " . (float)$doc['total'] . "
			WHERE `order_id` = " . (int)$order['order_id'] . " AND
			`code` = 'total'");
			$this->log("Всего = " . $doc['total'], 2);

		}


		$this->log("Документ обновлен",2);

		return true;

	} // updateDocument()


	/**
	 * ver 4
	 * update 2018-08-30
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
				case 'Статус заказа':
					$doc['status'] = $value;
				break;
				case 'ПометкаУдаления':
					$doc['DeletionMark'] = $value;
				break;
				case 'Проведен':
					$doc['Posted'] = $value;
				break;
				case 'Отменен':
					$doc['canceled'] = $value;
				break;

				// Оплата (в процессе реализации)
				case 'Оплачен':
					$doc['Paid'] = $value;
				break;
				case 'Номер оплаты по 1С':
					$doc['NumPay'] = $value;
				break;
				case 'Дата оплаты по 1С':
					$doc['DataPay'] = $value;
				break;

				// Отгрузка (в процессе реализации)
				case 'Отгружен':
					$doc['Shipped'] = $value;
				break;
				case 'Номер отгрузки по 1С':
					$doc['NumSale'] = $value;
				break;
				case 'Дата отгрузки по 1С':
					$doc['DateSale'] = $value;
				break;

				// Доставка (в процессе реализации)
				case 'Идентификатор отправления':
					$doc['DeliveryID'] = $value;
				break;
				case 'Комментарий доставки':
					$doc['DeliveryComment'] = $value;
				break;
				case 'Адрес доставки':
					$doc['DeliveryAddress'] = $value;
				break;
				case 'Способ доставки':
					$doc['DeliveryMethod'] = $value;
				break;
				case 'Стоимость доставки':
					$doc['DeliveryAmount'] = $value;
				break;
				case 'Ставка НДС доставки':
					$doc['DeliveryTax'] = $value;
				break;
				case 'Получатель':
					$doc['DeliveryRecipient'] = $value;
				break;
				case 'Контактный телефон':
					$doc['DeliveryContactPhone'] = $value;
				break;
				case 'Почта получателя':
					$doc['DeliveryContactEmail'] = $value;
				break;

				default:
			}
		}
		$this->log("Реквизиты документа прочитаны",2);

	} // parseDocumentRequisite()


	/**
	 * ver 1
	 * update 2018-04-08
	 * Контрагент из строки: Организация [Контакт]
	 * Пример1: Фамилия Имя Отчество [Фамилия Имя Оотчество]
	 * Пример2: Наименование организации [Фамилия Имя Оотчество]
	 * Получает ID покупателя и адреса
	 */
	private function parseCustomerStr($customer_name) {

		$this->log($customer_name, 2);
		$customer_name_split = explode(" ", $customer_name);
		$this->log($customer_name_split, 2);

		$customer_info = array();
		$customer_info['company'] = '';
		$customer_info['customer'] = array();

		// Определим есть ли в названии квадратные скобки, то есть есть ли организация.
		$pos = mb_stripos($customer_name, '[');
		if ($pos === false) {
			// Это физическое лицо
			foreach ($customer_name_split as $str) {
				$str = trim($str);

				// Пропускаем пустые, если между словами было больше одного пробела
				if (empty($str))
					continue;

				// Если сайт работает на кодировке UTF-8
				$str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");

				$customer_info['customer'][] = $str;
			}

		} else {
	 		// Это организация
			$type = 'company';
			foreach ($customer_name_split as $str) {
				$str = trim($str);

				if (mb_substr($str,0,1) == '[') {
					$type = 'customer';
					$str = str_replace('[','',$str);
				}

				if (mb_substr($str,-1,1) == ']') {
					$str = str_replace(']','',$str);
				}

				// Пропускаем пустые, если между словами было больше одного пробела
				if (empty($str))
					continue;

				if ($type == 'customer') {
					// Если сайт работает на кодировке UTF-8
					// Только для ФИО
					$str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
					$customer_info[$type][] = $str;
				} else {
					$customer_info[$type] .= ' ' . $str;
				}

			}
		}

		$this->log($customer_info, 2);
		return $customer_info;

	} // parseCustomerStr()


	/**
	 * ver 7
	 * update 2018-08-07
	 * Контрагент
	 * Получает ID покупателя и адреса
	 */
	private function parseDocumentCustomer($xml, &$doc) {

		// Читаем контрагента, определим где организация а где контактное лицо
		$this->log($xml, 2);

		$doc['customer_id']	= 0;
		$doc['address_id']	= 0;

		$customer_guid = (string)$xml->Контрагент->Ид;

		// Определение типа покупателя: Организация или физ.лицо
		// Поиск организации будет осуществлен, если заполнено поле "ОфициальноеНаименование" и указан ИНН, иначе будет прочитано как физ.лицо
		if ($xml->Контрагент->ОфициальноеНаименование && $xml->Контрагент->ИНН) {
			$company_name = trim((string)$xml->Контрагент->ОфициальноеНаименование);
			$company_inn = trim((string)$xml->Контрагент->ИНН);
			$company_kpp = trim((string)$xml->Контрагент->КПП);

			$customer_type = (strlen($company_inn) == 12) ? 3 : 2;

			// Поиск по организации по ИНН
			$this->log("Поиск организации по ИНН: " . $company_inn);
			$query = $this->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE `company_inn` = '" . $this->db->escape($company_inn) . "'");
			if ($query->num_rows) {
				$doc['payment_company'] = $company_name;
				$doc['shipping_company'] = $company_name;
				$doc['customer_id'] = $query->row['customer_id'];

				$query_address = $this->query("SELECT `address_id` FROM `" . DB_PREFIX . "address` WHERE `customer_id` = '" . (int)$doc['customer_id'] . "'");
				if ($query_address->num_rows) {
					$doc['address_id'] = $query_address->row['address_id'];
				}
			}

			// Если не найдено по реквизитам, значит изменилось наименование или переименован в название организации.
			// В этом случае пропишем название организации, ИНН и КПП
			if (!$doc['customer_id']) {
				$doc['company'] = $company_name;
				$doc['company_inn'] = $company_inn;
				$doc['company_kpp'] = $company_kpp;
			}

			$this->log("В ПРОЦЕССЕ РЕАЛИЗАЦИИ");

		} else {
			if ($xml->Контрагент->ПолноеНаименование) {
				// Тогда ФИО покупателя будет сначала а в квадратных скобках ФИО получателя в таблице address
				// В квадратных скобках указывается если пользователь регистрировался на сайте.
				$customer_info = $this->parseCustomerStr(trim((string)$xml->Контрагент->ПолноеНаименование));
			} else {
				$customer_info = $this->parseCustomerStr(trim((string)$xml->Контрагент->Наименование));
			}

			// Поиск по ФИО
			$customer = $customer_info['customer'];

			$customer_fullname	= implode(" ", $customer);
			$this->log($customer_fullname, 2);
			$lastname				= isset($customer[0]) ? trim($customer[0]) : '';
			$firstname				= isset($customer[1]) ? trim($customer[1]) : '';
			$middlename				= isset($customer[2]) ? trim($customer[2]) : '';

			// Покупатель
			if (!$doc['customer_id']) {

				$doc['firstname'] = $firstname;
				$doc['lastname'] = $lastname;
				$doc['middlename'] = $middlename;
				$this->log("Покупатель не найден в базе, возможно были изменены ФИО");

			}

			if (!$doc['customer_id']) {
				// поиск в адресах
				if (!$doc['customer_id']) {
					$query = $this->query("SELECT `address_id`,`customer_id` FROM `" . DB_PREFIX . "address` WHERE `firstname` = '" . $this->db->escape($firstname) . "' AND `lastname` = '" . $this->db->escape($lastname) . "'");
					if ($query->num_rows) {
						$doc['customer_id'] = $query->row['customer_id'];
						$doc['address_id'] = $query->row['address_id'];
					}
				}
			}

			if (!$doc['customer_id']) {

				// Поиск в покупателях
				$sql = "SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE `firstname` = '" . $this->db->escape($firstname) . "' AND `lastname` = '" . $this->db->escape($lastname) . "'";
				if ($middlename) {
					$sql .=  " AND `middlename` = '" . $this->db->escape($middlename) . "'";
				}
				$query = $this->query($sql);
				if ($query->num_rows) {
					$doc['customer_id'] = $query->row['customer_id'];
				}
			} // if (!$doc['customer_id'])

		} // if ($xml->Контрагент->ОфициальноеНаименование)

		if (!$doc['customer_id'] && empty($doc['firstname']) && empty($doc['lastname'])) {
			$this->log($doc, 2);
			//$this->errorLog(2202);
			//return false;
		}
		$this->log("Покупатель в документе прочитан",2);
		return true;

	} // parseDocumentCustomer()


	/**
	 * ver 7
	 * update 2018-10-31
	 * Товары документа
	 */
	private function parseDocumentProducts($xml, &$doc) {

		//$this->log($xml, 2);

		foreach ($xml->Товар as $product) {
			$guid		= explode("#", (string)$product->Ид);
			$this->log($guid, 2);

			if (!$guid) {
				$this->errorLog(2203);
				return false;
			}

			$data = array();

			// Сначала наименование подставляем из файла
			if ($product->Наименование) {
				$data['name'] = trim((string)$product->Наименование);
			} else {
				$this->errorLog(2208);
				return false;
			}

			if (isset($guid[0])) {

				$data['product_guid'] = $guid[0];

				// Доставка Ид = ORDER_DELIVERY
				if ($data['product_guid'] == 'ORDER_DELIVERY') {
					// Доставка в процессе реализации
					continue;
				}

				$product_info = $this->getProductByGUID($data['product_guid']);
				if ($product_info == false) {
					//$this->errorLog(2204, $data['name'], $data['product_guid']);
					$this->Log("Не найден товар на сайте '" . $data['name'] . "' по Ид " . $data['product_guid']);
					continue;
					//return false;
				}
			} else {
				$this->errorLog(2205, $data['name']);
				return false;
			}

			$data['product_id'] = $product_info['product_id'];

			// Меняем наименование на то которое в базе, потому-что в базу могли записать полное наименование, а в заказе только короткое
			if ($product->Наименование) {
				$data['name'] = $product_info['name'];
			}

			if (isset($guid[1])) {
				$data['product_feature_guid'] = $guid[1];
				$data['product_feature_id'] = $this->getProductFeatureIdByGuid($data['product_feature_guid']);
				if (!$data['product_feature_id']) {
					$this->errorLog(2206, $data['name'], $data['product_feature_guid']);
					return false;
				}
			} else {
				$data['product_feature_id'] = 0;
			}

			if ($product->Артикул) {
				$data['sku'] = (string)$product->Артикул;
				$data['model'] = (string)$product->Артикул;
			}

			$data['ratio'] = (float)$product->Коэффициент;

			if ($product->ЦенаЗаЕдиницу) {
				$data['price'] = (float)$product->ЦенаЗаЕдиницу;
			}
			if ($product->Количество) {
				$data['quantity'] = (float)$product->Количество;
			}

			// Вычисление суммы налогов пока в разработке
			$data['tax'] = 0;
			$data['reward'] = 0;

			if ($product->Сумма) {
				$data['total'] = (float)$product->Сумма;
			}

			if (!isset($doc['products'])) {
				$doc['products'] = array();
			}
			$doc['products'][] = $data;
		}

		//$this->log($doc, 2);
		$this->log("Товары документа прочитаны", 2);
		return true;

	} // parseDocumentProducts()


	/**
	 * ******************************************* КАТЕГОРИИ *********************************************
	 */


	/**
	 * ver 2
	 * update 2017-08-25
	 * Обновляет иерархию категории
	 */
	private function updateHierarchical($category_id, $parent_id) {

		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `path_id` = " . (int)$category_id . " ORDER BY `level` ASC");

		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " AND `level` < " . (int)$category_path['level']);

				$path = array();

				// Get the nodes new parents
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$parent_id . " ORDER BY `level` ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Get whats left of the nodes current path
				$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_path['category_id'] . " ORDER BY `level` ASC");

				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}

				// Combine the paths with a new level
				$level = 0;

				foreach ($path as $path_id) {
					$this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_path['category_id'] . ", `path_id` = " . (int)$path_id . ", `level` = " . $level);
					$level++;
				}
			}

		} else {
			// Delete the path below the current one
			$this->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$category_id);

			// Fix for records with no paths
			$level = 0;

			$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = " . (int)$parent_id . " ORDER BY `level` ASC");

 			foreach ($query->rows as $result) {
				$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_id . ", `path_id` = " . (int)$result['path_id'] . ", `level` = " . $level);
				$level++;
			}

 			$this->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET `category_id` = " . (int)$category_id . ", `path_id` = " . (int)$category_id . ", `level` = " . $level);
		}

		$this->log("Обновлена иерархия у категории", 2);

	} // updateHierarchical()


	/**
	 * ver 2
	 * update 2017-08-26
	 * Добавляет категорию
	 */
	private function getCategory($category_id) {

		//$query = $this->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id AND cp.category_id != cp.path_id) WHERE cp.category_id = c.category_id AND cd1.language_id = '" . (int)$this->LANG_ID . "' GROUP BY cp.category_id) AS path FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) LEFT JOIN " . DB_PREFIX . "category_to_1c c1c ON (c.category_id = c1c.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->LANG_ID . "'");
		$query = $this->query("SELECT DISTINCT * FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (c.category_id = cd2.category_id) LEFT JOIN " . DB_PREFIX . "category_to_1c c1c ON (c.category_id = c1c.category_id) WHERE c.category_id = '" . (int)$category_id . "' AND cd2.language_id = '" . (int)$this->LANG_ID . "'");

		return $query->row;

	} // getCategory()


	/**
	 * ver 3
	 * update 2018-06-14
	 * Добавляет категорию
	 */
	private function addCategory($data) {

		$this->log($data, 2);
		// Отключено создание новых категорий
		if ($this->config->get('exchange1c_category_new_no_create') == 1) {
			return false;
		}

		if (!isset($data['image'])) 		$data['image'] = "";
		if (!isset($data['top'])) 			$data['top'] = 1;
		if (!isset($data['column'])) 		$data['column'] = 1;
		if (!isset($data['sort_order'])) 	$data['sort_order'] = 1;
		if (!isset($data['status']))		$data['status'] = $this->config->get('exchange1c_category_new_status_disable') == 1 ? 0 : 1;

		$this->query("INSERT INTO `" . DB_PREFIX . "category` SET `image` = '" . $this->db->escape($data['image']) . "', `parent_id` = " . (int)$data['parent_id'] . ", `top` = " . (int)$data['top'] . ", `column` = " . (int)$data['column'] . ", `sort_order` = " . (int)$data['sort_order'] . ", `status` = " . (int)$data['status'] . ", `date_added` = '" . $this->NOW . "', `date_modified` = '" . $this->NOW . "'");

		$category_id = $this->db->getLastId();

		// SEO
		if ($this->config->get('exchange1c_seo_category_mode') != 'disable')
			$this->seoGenerateCategory($category_id, $data);

		// Описание категории
		if (!isset($data['description'])) 		$data['description'] = "";
		if (!isset($data['meta_title'])) 		$data['meta_title'] = "";
		if (!isset($data['meta_description'])) 	$data['meta_description'] = "";
		if (!isset($data['meta_keyword'])) 		$data['meta_keyword'] = "";

		$this->query("INSERT INTO `" . DB_PREFIX . "category_description` SET `category_id` = " . (int)$category_id . ", `language_id` = " . (int)$this->LANG_ID . ", `name` = '" . $this->db->escape($data['name']) . "', `description` = '" . $this->db->escape($data['description']) . "', `meta_title` = '" . $this->db->escape($data['meta_title']) . "', `meta_description` = '" . $this->db->escape($data['meta_description']) . "', `meta_keyword` = '" . $this->db->escape($data['meta_keyword']) . "'");

		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;

		$query = $this->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");

		foreach ($query->rows as $result) {
			$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");

			$level++;
		}

		$this->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");

		// Добавляем связь
		$this->query("INSERT INTO `" . DB_PREFIX . "category_to_1c` SET `category_id` = '" . (int)$category_id . "', `guid` = '" . $this->db->escape($data['guid']) . "', `version` = '" . $this->db->escape($data['version']) . "'");

		if (isset($data['keyword'])) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}

		// Магазин
		$this->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = " . (int)$category_id . ",  store_id = " . (int)$this->STORE_ID);

		//$this->cache->delete('category');

		return $category_id;

	} // addCategory()


	/**
	 * ver 5
	 * update 2018-06-14
	 * Обновляет категорию
	 */
	private function updateCategory($category_id, $data) {

		// Получим старые данные
		$old = $this->getCategory($category_id);
		//$this->log($old, 2);

		// Используется версионирование
		if (!empty($data['version'])) {
			if ($data['version'] == $old['version']) {
				return false;
			}
			$this->query("UPDATE `" . DB_PREFIX . "category_to_1c` SET `version` = '" . $this->db->escape($data['version']) . "' WHERE `category_id` = " . (int)$category_id);
		}

		// Объеденим массивы
		//$this->log($data, 2);
		//$this->log($change2, 2);
		$data = array_merge($old, $data);
		//$this->log($data, 2);

		// SEO
		if ($this->config->get('exchange1c_seo_category_mode') != 'disable')
			$this->seoGenerateCategory($category_id, $data);

		// Указываем поля которые не нужно обновлять
		$no_update_fields = array();

		// Надо проверить поля
		$data_update = $this->compareArraysData($data, $old, $no_update_fields);
		//$this->log($data_update, 2);

		if ($data_update) {

			// Если было обновлено описание
			$fields = $this->prepareQueryDescription($data_update);

			if ($fields) {
				$this->query("UPDATE `" . DB_PREFIX . "category_description` SET " . $fields . " WHERE `category_id` = " . (int)$category_id . " AND `language_id` = " . $this->LANG_ID);
				$update = true;
			}

			$fields_category = $this->prepareQueryCategory($data_update);

			if ($update || $fields_category) {

				$this->query("UPDATE `" . DB_PREFIX . "category` SET " . $fields_category . "`date_modified` = '" . $this->NOW . "' WHERE `category_id` = " . (int)$category_id);
				$this->log("Обновлена категория '" . $data['name'] . "'", 2);

				// Обновляем иерархию, если поменялась позиция
				if ($data['parent_id'] != $old['parent_id']) {
					// Изменилась структура, нужно обновить иерархию
					$this->updateHierarchical($category_id, $data['parent_id']);
				}

			} else {

				$this->log("После подготовки данных нечего обновлять, возможно тут ошибка");
				return false;

			}

		} else {

			$this->log("Нет изменений", 2);
			return false;

		}

		// Очистка кэша
		//$this->cache->delete('category');

		return true;

	} // updateCategory()


	/**
	 * ver 1
	 * update 2018-11-05
	 * Парсит товарные категории
	 * Они нужны для опций, то есть опции будут относиться к категории и две одинаковые опции для разных категорий не будут перезаписываться
	 * актуально если картинки есть в опциях
	 * ну и в дальнейшем нужны для формирования фильтров
	 * Функция циклической обработки дерева категорий
	 */
	private function parseClassifierProductCategories($xml, $parent_id = 0, &$num_categories) {
		foreach ($xml->Категория as $xml_category) {
			$num_categories++;
			$guid = (string)$xml_category->Ид;
			$product_category_id = isset($this->PRODUCT_CATEGORIES[$guid]) ? $this->PRODUCT_CATEGORIES[$guid]['product_category_id'] : 0;
			$name = htmlspecialchars(trim((string)$xml_category->Наименование));

			if (!$product_category_id) {
				$this->query("INSERT INTO `" . DB_PREFIX . "product_category` SET `name` = '" . $this->db->escape($name) . "', `guid` = '" . $this->db->escape($guid) . "'");
			}

			// Свойства категорий
			if ($xml_category->Свойства) {
				foreach ($xml_category->Свойства->Ид as $property) {
					$guid = (string)$property;
					if (isset($this->ATTRIBUTES[$guid])) {
						$attribute_id = $this->ATTRIBUTES[$guid]['attribute_id'];
						$query = $this->query("SELECT * FROM `".DB_PREFIX."attribute_to_1c_category` WHERE `attribute_id` = '".$attribute_id."' AND `product_category_id` = '".$product_category_id."'");
						if ($query->num_rows)
							continue;

						$this->query("INSERT INTO `".DB_PREFIX."attribute_to_1c_category` VALUES ('".$attribute_id."', '".$product_category_id."')");
					}
				}
			} // if

		} // foreach
	}


	/**
	 * ver 9
	 * update 2018-06-11
	 * Парсит группы в классификаторе в XML
	 */
	private function parseClassifierCategories($xml, $parent_id = 0, &$num_categories) {

		foreach ($xml->Группа as $xml_category) {
			if ($xml_category->Ид && $xml_category->Наименование) {

				$num_categories++;

				$guid = (string)$xml_category->Ид;

				$category_id = isset($this->CATEGORIES[$guid]) ? $this->CATEGORIES[$guid]['category_id'] : 0;

				$data = array(
					'parent_id'		=> $parent_id,
					'name'			=> htmlspecialchars(trim((string)$xml_category->Наименование)),
					'version'		=> $xml_category->НомерВерсии ? (string)$xml_category->НомерВерсии : "",
					'guid'			=> $guid
				);

				// Сортировка категории (по просьбе Val)
				if ($xml_category->Сортировка) {
					$data['sort_order']	= (int)$xml_category->Сортировка;
				}

				// Картинка категории (по просьбе Val)
				if ($xml_category->Картинка) {
					$data['image']		= (string)$xml_category->Картинка;
				}

				// Если пометка удаления есть, значит будет отключен
				if ((string)$xml_category->ПометкаУдаления == 'true') {
					$data['status']		= 0;
				} elseif ($category_id && $this->config->get('exchange1c_category_exist_status_enable') == 1) {
					// Включить существующие категории
					$data['status'] = 1;
				}

				if ($category_id) {
					// Прочитаем данные существующей категории
					$this->updateCategory($category_id, $data);
					$this->CATEGORIES[$guid]['update'] = true;

				} else {

					if ($this->config->get('exchange1c_category_new_no_create') == 1) {
						$this->log("Включен запрет на создание новых категорий", 2);
						continue;

					} else {

						//$this->log($data, 2);
						$category_id = $this->addCategory($data);

						$this->CATEGORIES[$guid] = array(
							'category_id'	=> $category_id,
							'version'		=> $data['version'],
							'update'		=> true
						);
					}
				}
			} // if ($xml_category->Ид && $xml_category->Наименование)

			if ($xml_category->Группы) {
				$this->parseClassifierCategories($xml_category->Группы, $category_id, $num_categories);
				if ($this->ERROR) return false;
			}

			$this->log("Категория: '" . $data['name'] . "'");

		} // foreach

		return true;

	} // parseClassifierCategories()


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
	 * ver 12
	 * update 2018-11-08
	 * Разбор классификатора
	 */
	private function parseClassifier($xml) {

		$this->STAT['classifier_parse'] = microtime(true);
		$data = array();
		$data['guid']			= (string)$xml->Ид;
		$data['name']			= (string)$xml->Наименование;

		$this->setStore($data['name']);

		// Классификатор содержит только изменения или полная выгрузка
		$data['update'] = (string)$xml['СодержитТолькоИзменения'] == 'true' ? true : false;

		// Группы из файла -> категории opencart
		if ($xml->Группы && $this->config->get('exchange1c_categories_no_import') != 1) {

			$this->log("~ЧТЕНИЕ КАТЕГОРИЙ");

			if (empty($this->CATEGORIES)) {
				$this->CATEGORIES = $this->getCategories();
			}

			if ($this->config->get('exchnge1c_category_attributes_parse') == 1) {
				if (empty($this->ATTRIBUTE_GROUPS)) {
					$this->ATTRIBUTE_GROUPS = $this->getAttributeGroups();
				}
				if (empty($this->ATTRIBUTES)) {
					$this->ATTRIBUTES = $this->getAttributes(true);
				}
			}

			$this->statStart('classifier_categories_parse');
			$num_categories = 0;
			$this->parseClassifierCategories($xml->Группы, 0, $num_categories);

			// Удалим старые категории, которых нет в файле, если полная выгрузка
			if ($this->FULL_IMPORT) {
				$delete_categories = array();
				foreach ($this->CATEGORIES as $cat) {
					if (!isset($cat['update'])) {
						$delete_categories[$cat['guid']] = $cat['category_id'];
					}
				}
				if (!empty($delete_categories)) {
					$sql_delete_categories = implode(",",$delete_categories);
					$this->query("DELETE FROM `" . DB_PREFIX . "category` WHERE `category_id` IN (" . $sql_delete_categories . ")");
					$this->query("DELETE FROM `" . DB_PREFIX . "category_description` WHERE `category_id` IN (" . $sql_delete_categories . ")");
					$this->query("DELETE FROM `" . DB_PREFIX . "category_to_1c` WHERE `category_id` IN (" . $sql_delete_categories . ")");

					// Удалим из прочитанных
					foreach ($delete_categories as $guid => $cat) {
						unset($this->CATEGORIES[$guid]);
					}
				}
			}

			$this->statStop('classifier_categories_parse');

			if ($this->ERROR) return false;

			unset($xml->Группы);

			$this->STAT['classifier_category_num'] = $num_categories;
			$this->log("Категорий обработано: " . $num_categories, 2);

		}

		if ($xml->ТипыЦен) {

			$this->log("~ВИДЫ ЦЕН");
			$this->parseClassifierPriceType($xml->ТипыЦен);
			if ($this->ERROR) return false;
			unset($xml->ТипыЦен);

		}

		// Необходим дополнительный модуль
		if ($xml->ЕдиницыИзмерения) {

			$this->log("~ЧТЕНИЕ ЕДИНИЦ ИЗМЕРЕНИЙ");

			//$num = $this->parseClassifierUnits($xml->ЕдиницыИзмерения);

			//if ($this->ERROR) return false;

			unset($xml->ЕдиницыИзмерения);

			//$this->log("Единиц измерений загружено(CML >= v2.09): " . count($num), 2);

		}

		if ($xml->Свойства && $this->config->get('exchange1c_product_attribute_mode_import') != 'not_import') {

			$this->log("~ЧТЕНИЕ СВОЙСТВ");

			if (empty($this->ATTRIBUTES)) {
				$this->ATTRIBUTES = $this->getAttributes(true);
			}

			$this->statStart('attributes_parse');
			$num = $this->parseClassifierAttributes($xml->Свойства);
			$this->statStop('attributes_parse');

			if ($this->ERROR) return false;

			unset($xml->Свойства);

			$this->log("Атрибутов загружено: " . count($num), 2);

		}

		// Товарные категории
		if ($xml->Категории) {
			$this->log("~ТОВАРНЫЕ КАТЕГОРИИ");
			$this->getProductCategories2();
			$num_categories = 0;
			$this->parseClassifierProductCategories($xml->Категории, 0, $num_categories);
			if ($this->ERROR) return false;
		}

		$this->log("~Классификатор успешно прочитан", 2);
		$this->logStat('classifier_parse');
		return $data;

	} // parseClassifier()


	/**
	 * ver 3
	 * update 2018-05-30
	 * Разбор документа
	 */
	private function parseDocument($xml) {

		$order_guid		= (string)$xml->Ид;
		$order_id		= (string)$xml->Номер;

		$this->log("~ЗАГРУЖАЕТСЯ ЗАКАЗ #" . $order_id);
		//$this->log($xml, 2);

		$config_currency = $this->config->get('exchange1c_currency');
		if (empty($config_currency)) {
			$this->errorLog(2032);
			return false;
		}

		$doc = array(
			'order_id'		=> $order_id,
			'date'			=> (string)$xml->Дата,
			'time'			=> (string)$xml->Время,
			'currency'		=> $this->getCurrencyConfig($config_currency, (string)$xml->Валюта),
			'total'			=> (float)$xml->Сумма,
			'doc_type'		=> (string)$xml->ХозОперация,
			'date_pay'		=> (string)$xml->ДатаПлатежа
		);

		// Просроченный платеж если date_pay будет меньше текущей
		if ($doc['date_pay']) {
			$this->log("По документу просрочена оплата");
		}

		// УНФ
		if ($xml->СрокПлатежа) {
			$doc['time_payment'] = (string)$xml->СрокПлатежа;
		}

		$this->parseDocumentCustomer($xml->Контрагенты, $doc);
		if ($this->ERROR) return;

		// Налоги документа
		if ($xml->Налоги) {
			$this->load->model('localisation/tax_class');
			$doc['taxes'] = array();
			foreach ($xml->Налоги->Налог as $tax_xml) {
				$this->log($tax_xml, 2);
				$taxes_class = $this->model_localisation_tax_class->getTaxClasses();
				$this->log($taxes_class, 2);
				foreach ($taxes_class as $tax_class) {
					$tax = array();
					$tax['name'] = trim((string)$tax_xml->Наименование);
					$tax['in_sum'] = ((string)$tax_xml->УчтеноВСумме == 'true' ? true : false);
					$tax['sum'] = (float)$tax_xml->Сумма;
					$this->log($tax, 2);
					if ($tax_class['title'] == $tax['name']) {
						$doc['taxes'][] = $tax;
					}
				}
			}
		}

		$success = $this->parseDocumentProducts($xml->Товары, $doc);
		if ($this->ERROR) return;

		$this->parseDocumentRequisite($xml->ЗначенияРеквизитов, $doc);
		if ($this->ERROR) return;

		$this->load->model('sale/order');
		$order = $this->model_sale_order->getOrder($order_id);
		if ($order) {
			$products = $this->model_sale_order->getOrderProducts($order_id);
			$this->log("Заказ на сайте:", 2);
			$this->log($order, 2);
			$this->log("Товары заказа на сайте:", 2);
			$this->log($products, 2);
		} else {
			return "Заказ #" . $doc['order_id'] . " не найден в базе";
		}

		$this->log("Документ прочитанный из файла:", 2);
		$this->log($doc, 2);

		$this->updateDocument($doc, $order, $products);
		if ($this->ERROR) return;

   		//$this->errorLog(5000);

		$this->log("[i] Прочитан документ: Заказ #" . $order_id . ", Ид '" . $order_guid . "'");

		return true;

	} // parseDocument()


	/**
	 * Очистка лога
	 * НЕИСПОЛЬЗУЕТСЯ
	 */
	private function clearLog() {

		$file = DIR_LOGS . $this->config->get('config_error_filename');
		$handle = fopen($file, 'w+');
		fclose($handle);

	} // clearLog()


	/**
	 * ver 7
	 * update 2018-05-09
	 * Импорт файла
	 */
	public function importFile($importFile, $type) {

		// Функция будет сама определять что за файл загружается
		$this->STAT['exchange'] = microtime(true);
		$this->log("~НАЧАЛО ЗАГРУЗКИ ДАННЫХ");
		//$this->log("Доступно памяти: " . sprintf("%.3f", memory_get_peak_usage() / 1024 / 1024) . " Mb", 2);

		// Определим язык
		$this->getLanguageId($this->config->get('config_language'));
		$this->log("Язык загрузки, id: " . $this->LANG_ID, 2);

        // Записываем единое текущее время обновления для запросов в базе данных
		$this->NOW = date('Y-m-d H:i:s');

		// Определение дополнительных полей
		$this->TAB_FIELDS = $this->config->get('exchange1c_table_fields');

		// Читаем XML
		libxml_use_internal_errors(true);
		$path_parts = pathinfo($importFile);

		//$this->log($path_parts, 2);
		$filename = $path_parts['basename'];
		$this->log("Читается XML файл: '" . $filename . "'", 2);


		if (is_file($importFile)) {

			$this->STAT['xml_load'] = microtime(true);
			$xml = @simplexml_load_file($importFile);
			$this->logStat('xml_load');
			if (!$xml) {
				$this->errorLog(3000, implode("\n", libxml_get_errors()));
				$this->log(implode("\n", libxml_get_errors()));
				return $this->error();
			}

		} else {
			$this->errorLog(3001);
			return $this->error();
		}

		// Файл стандарта Commerce ML
		$this->checkCML($xml);
		if ($this->ERROR) return $this->error();
		$xml_date = (string)$xml['ДатаФормирования'];
		$this->STAT['date'] = $xml_date;

		// IMPORT.XML, OFFERS.XML
		if ($xml->Классификатор) {
			$this->log("~ЗАГРУЗКА КЛАССИФИКАТОРА",2);
			$classifier = $this->parseClassifier($xml->Классификатор);
			//$this->log($classifier, 2);
			if ($this->ERROR) return $this->error();
			unset($xml->Классификатор);
			$this->setConfig('exchange1c_xml_date', $xml_date);
		}

		if ($xml->Каталог) {
			$this->log("~ЗАГРУЗКА КАТАЛОГА",2);
			$this->parseDirectory($xml->Каталог);
			if ($this->ERROR) return $this->error();
			unset($xml->Каталог);
		}

		// OFFERS.XML
		if ($xml->ПакетПредложений) {
			$this->log("~ЗАГРУЗКА ПАКЕТА ПРЕДЛОЖЕНИЙ", 2);
			// Пакет предложений
			$this->parseOffersPack($xml->ПакетПредложений);
			if ($this->ERROR) return $this->error();
			unset($xml->ПакетПредложений);
		}

		// ORDERS.XML
		if ($xml->Документ) {
			$this->log("~ЗАГРУЗКА ДОКУМЕНТОВ", 2);
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

		$this->log("~КОНЕЦ ЗАГРУЗКИ ДАННЫХ");
		$this->logStat('exchange');
		$this->log($this->STAT, 2);
		$this->setConfig('stat_'.$filename, json_encode($this->STAT), 1, 'exchange1c-stat');
		return "";

	} // importFile()


	/**
	 * ver 2
	 * update 2017-11-04
	 * Получение статистики
	 */
	public function getStatistics() {

		$result = array();
		//$query = $this->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE `key` LIKE 'stat_%'");
		$query = $this->query("SELECT `key`, `value` FROM `" . DB_PREFIX . "setting` WHERE `code` = 'exchange1c-stat'");
		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$filename = substr($row['key'], 5);
				$result[$filename] = json_decode($row['value']);
			}
		}
		return $result;

	} // getStatistics()


	/**
	 * ver 9
	 * update 2018-07-07
	 * Определение дополнительных полей и запись их в глобальную переменную типа массив
	 */
	public function defineTableFields() {

		$result = array();

		$this->log("Поиск в базе данных дополнительных полей",2);

		$tables = array(
			'manufacturer'				=> array('noindex'=>1),
			'product_to_category'		=> array('main_category'=>1),
			'product_description'		=> array('meta_h1'=>'','meta_title'=>'','meta_description'=>'','meta_keyword'=>''),
			'category_description'		=> array('meta_h1'=>'','meta_title'=>'','meta_description'=>'','meta_keyword'=>''),
			'manufacturer_description'	=> array('name'=>'','meta_h1'=>'','meta_title'=>'','meta_description'=>'','meta_keyword'=>''),
			'manufacturer_to_layout'	=> array(),
			'product'					=> array('noindex'=>1,'unit_id'=>0),
			'order'						=> array('middlename'=>'','shipping_middlename'=>'','payment_middlename'=>''),
			'order_product'				=> array('product_feature_id'=>0),
			'customer'					=> array('middlename'=>'','company_inn'=>'','company_kpp'=>''),
			'cart'						=> array('product_feature_id'=>0),
			'attributes_value'			=> array(),
			'attributes_value_to_1c'	=> array(),
			'url_alias'					=> array('seomanager'=>'')
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
		//$this->log($result, 2);
		return $result;

	} // defineTableFields()


	/**
	 * ver 13
	 * update 2018-10-21
	 * Устанавливает обновления
	 */
	public function checkUpdates($settings) {

		$message = "";
		if (isset($settings['exchange1c_version'])) {
			$version = $settings['exchange1c_version'];
		} else {
			$version = '1.6.4.1';
			$settings['exchange1c_version'] = $version;
		}
		$version = "1.6.4.1";
		$beta = '';

		if ($version == '1.6.4.1') {
			$this->log("Обновление до версии 1.6.4.2...'");
			$success = $this->update_1_6_4_2();
	        if ($this->ERROR) return false;
	   		if ($success) {
	   			$version = '1.6.4.2';
	   			$message .= "Успешно обновлено до версии " . $version;
			}
		}
		if ($version == '1.6.4.2') {
			$this->log("Обновление до версии 1.6.4.3...'");
			$success = $this->update_1_6_4_3();
	        if ($this->ERROR) return false;
	   		if ($success) {
	   			$version = '1.6.4.3';
	   			$message .= "Успешно обновлено до версии " . $version;
			}
		}
		if ($version == '1.6.4.3') {
			$this->log("Обновление до версии 1.6.4.4...'");
			$success = $this->update_1_6_4_4();
	        if ($this->ERROR) return false;
	   		if ($success) {
	   			$version = '1.6.4.4';
	   			$message .= "Успешно обновлено до версии " . $version;
			}
		}
		if (version_compare($version, '1.6.4.4', '=')) {
			$this->log("Обновление до версии 1.6.4.5...'");
			$success = $this->update_1_6_4_5();
		        if ($this->ERROR) return false;
		   	if ($success) {
		   		$version = '1.6.4.5';
		   		$message .= "Успешно обновлено до версии " . $version;
			}
		}
		if (version_compare($version, '1.6.4.5', '=')) {
			$this->log("Обновление до версии 1.6.4.6...'");
			$success = $this->update_1_6_4_6();
		        if ($this->ERROR) return false;
		   	if ($success) {
		   		$version = '1.6.4.6';
		   		$message .= "Успешно обновлено до версии " . $version;
			}
		}
		if (version_compare($version, '1.6.4.6', '=')) {
			$this->log("Обновление до версии 1.6.4.7...'");
			$success = $this->update_1_6_4_7();
		        if ($this->ERROR) return false;
		   	if ($success) {
		   		$version = '1.6.4.7';
		   		$message .= "Успешно обновлено до версии " . $version;
			}
		}

		$pos = strrpos($version, 'b');
		if ($beta) {
			$old_version = $version;
			if ($pos === false) {
				$version .= 'b' . $beta;
			} else {
				$version = substr($version, 0, $pos) . 'b' . $beta;
			}
			if ($old_version != $version)
				$message .= ($message ? '<br />' : '') . 'Обновление до beta версии ' . $version;
		} else {
			if ($pos !== false) {
				$version = substr($version, 0, $pos);
			}
		}

		if ($version != $settings['exchange1c_version']) {
			//$this->setEvents();
			$settings['exchange1c_version'] = $version;
			$this->model_setting_setting->editSetting('exchange1c', $settings);
			$message .= '<br /><strong>ВНИМАНИЕ! после обновления необходимо проверить все настройки и сохранить!</strong>';
		}

		return $message;

	} // checkUpdates()


	/**
	 * Обновление до версии 1.6.4.2
	 */
	private function update_1_6_4_2() {

		 // Добавим поле модификации в значения характеристик для удаления старых опций
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature_value` WHERE `field` = 'date_modified'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature_value` ADD `date_modified` DATETIME NOT NULL");
		}
		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.2'");
			return false;
		}

		return true;

	} // update_1_6_4_2()


	/**
	 * Обновление до версии 1.6.4.3
	 */
	private function update_1_6_4_3() {

		 // Увеличим строку так как некоторые организации имеют длинное наименование
		$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "address` CHANGE `company` `company` VARCHAR(128)");
		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.3'");
			return false;
		}

		return true;

	} // update_1_6_4_3()


	/**
	 * Обновление до версии 1.6.4.4
	 */
	private function update_1_6_4_4() {

		// Добавим поле модификации в значения характеристик для удаления старых опций
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category_to_1c` WHERE `field` = 'version'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "category_to_1c` ADD `version` VARCHAR(32)");
		}
		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.4'");
			return false;
		}

		return true;

	} // update_1_6_4_4()


	/**
	 * Обновление до версии 1.6.4.5
	 */
	private function update_1_6_4_5() {

		$result = true;
		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.5'");
			return false;
		}

		return true;

	} // update_1_6_4_5()


	/**
	 * Обновление до версии 1.6.4.6
	 */
	private function update_1_6_4_6() {

		$result = true;
		 // Добавим поле delete
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category_to_1c` WHERE `field` = 'delete'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "category_to_1c` ADD `delete` INT(1)");
		}
		 // Добавим поле delete
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_to_1c` WHERE `field` = 'delete'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_to_1c` ADD `delete` INT(1)");
		}
		 // Добавим поле version
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_to_1c` WHERE `field` = 'version'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_to_1c` ADD `version` VARCHAR (32)");
		}
		 // Добавим поле version
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category_to_1c` WHERE `field` = 'version'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "category_to_1c` ADD `version` VARCHAR (32)");
		}
		 // Добавим поле version
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "attribute_to_1c` WHERE `field` = 'version'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "attribute_to_1c` ADD `version` VARCHAR (32)");
		}
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.6'");
			return false;
		}

		return true;

	} // update_1_6_4_6()


	/**
	 * Обновление до версии 1.6.4.7
	 */
	private function update_1_6_4_7() {

		$result = true;
		// Связь товаров с 1С
		$result = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "exchange1c`");
		$result = $this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "exchange1c` (
				`exchange1c_id` 			INT(11) 		NOT NULL AUTO_INCREMENT,
				`filename` 					VARCHAR(128) 	NOT NULL,
				`datetime` 					DATETIME 		NOT NULL,
				`xml_version` 				VARCHAR(8) 		NOT NULL,
				`only_changes` 				INT(1)		 	NOT NULL,
				`system` 					VARCHAR(32)		NOT NULL,
				`status` 					INT(2)			NOT NULL,
				`duration` 					INT(11)			NOT NULL,
				PRIMARY KEY (`exchange1c_id`),
				INDEX `status_key` (`status`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		$result = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "attribute_to_1c_category`");
		$result = $this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "attribute_to_1c_category` (
				`attribute_id` 				INT(11) 		NOT NULL,
				`product_category_id` 		INT(11)			NOT NULL,
				INDEX `key` (`attribute_id`, `product_category_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Восстанавливаем таблицу product_attribute
		$result = @$this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_attribute` WHERE `field` = 'attribute_value_id'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` DROP `attribute_value_id`");
		}

		$result = @$this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "product_attribute` WHERE `key_name` = 'value_id'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_attribute` DROP INDEX `value_id`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_description` DROP INDEX `name_key`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "option_value_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "option_value_description` DROP INDEX `name_key`");
		}

		// Удалим добавленный нами индекс
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "attribute_description` WHERE `key_name` = 'name_key'");
		if ($result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "attribute_description` DROP INDEX `name_key`");
		}

		// Переименуем таблицу
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "attribute_value'");
		if ($query->num_rows) {
			$query2 = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "attribute_value_to_1c'");
			if (!$query2->num_rows) {
				$this->db->query("RENAME TABLE `" . DB_PREFIX . "attribute_value` TO `" . DB_PREFIX . "attribute_value_to_1c`");
			}
		}

		 // Добавим поле price
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature` WHERE `field` = 'price'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature` ADD `price` DECIMAL (15,4) DEFAULT 0");
		}

		 // Добавим поле quantity
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature` WHERE `field` = 'quantity'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature` ADD `quantity` INT (4) DEFAULT 0");
		}

		 // Добавим поле staus
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature` WHERE `field` = 'status'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature` ADD `status` INT (1) DEFAULT 0");
		}

		 // Добавим поле staus
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature_value` WHERE `field` = 'status'");
		if (!$result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature_value` ADD `status` INT (1) DEFAULT 0");
		}

		 // Удалим поле date_modified
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_feature_value` WHERE `field` = 'date_modified'");
		if ($result->num_rows) {
			$result = $this->db->query("ALTER TABLE  `" . DB_PREFIX . "product_feature_value` DROP `date_modified`");
		}

		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_price`");
		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_quantity`");

		if (!$result) {
			$this->ERROR = 4000;
			$this->log("Error update to 1.6.4.7'");
			return false;
		}

		return true;

	} // update_1_6_4_7()

}
?>