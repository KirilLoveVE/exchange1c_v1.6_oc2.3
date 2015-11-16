<?php

class ModelToolExchange1c extends Model {

	private $VERSION_XML 	= '';
	private $CATEGORIES 	= array();
	private $PROPERTIES 	= array();
	private $WAREHOUSES 	= array();
	private $LANG 			= 0;
	private $STORES			= array();
	private $COMPANY 		= '';
	private $PRODUCT		= array();
	private $PRICE_TYPES	= array();

	//	********************************************** Общие функции ********************************************** 
	/**
	 * Пишет в файл журнала если включена настройка
	 *
	 * @param	string,array()	Сообщение или объект
	 */
	private function log($message) {
		if ($this->config->get('exchange1c_full_log')) $this->log->write(print_r($message,true));
	} // log()

	/**
	 * Определяет в какой магазин будет производится загрузка (в стадии разработки)
	 */
	private function getStores($xml) {
		foreach ($xml as $store) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "store_to_1c WHERE 1c_store_id = '" . (string)$store->Ид . "'");
			if ($query->num_rows > 0) {
				$this->STORES[$query->row['1c_store_id']] = $query->row['store_id'];
				$this->log("Связь Каталога 1С и Магазина для: " . $query->row['1c_store_id']);
			}
		}
 		return 1;
	} // getStores()

	/**
	 * Определяет владельца (в стадии разработки)
	 */
	private function getCompany($xml) {
		return 1;
		$query = $this->db->query("SELECT company_id FROM " . DB_PREFIX . "company_to_1c WHERE 1c_company_id = '" . $this->db->escape((string)$xml->Ид) . "'");
		if ($query->num_rows > 0) {
			$this->COMPANY = $query->row['1c_company_id'];
		}
		$this->COMPANY = 0;
		$this->log("Организация, UUID: " . $this->COMPANY);
		return 1;
	} // getCompany()

	/**
	 * Конвертирует XML в массив 
	 *
	 * @param	array	data	
	 * @param	XML		xml	
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

	//	********************************************** Выгрузка заказов ********************************************** 

	/**
	 * Генерирует xml с заказами
	 *
	 * @param	int	статус выгружаемых заказов
	 * @param	int	новый статус заказов
	 * @param	bool	уведомлять пользователя
	 * @return	string
	 */
	public function queryOrders($params) {

		$this->load->model('sale/order');

		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status'] . "");
		} else {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}

		$document = array();
		$document_counter = 0;

		if ($query->num_rows) {

			foreach ($query->rows as $orders_data) {

				$order = $this->model_sale_order->getOrder($orders_data['order_id']);

				$date = date('Y-m-d', strtotime($order['date_added']));
				$time = date('H:i:s', strtotime($order['date_added']));

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
				);

				$document['Документ' . $document_counter]['Контрагенты']['Контрагент'] = array(
					 'Ид'                 => $order['customer_id'] . '#' . $order['email']
					,'Наименование'		    => $order['payment_lastname'] . ' ' . $order['payment_firstname']
					,'Роль'               => 'Покупатель'
					,'ПолноеНаименование'	=> $order['payment_lastname'] . ' ' . $order['payment_firstname']
					,'Фамилия'            => $order['payment_lastname']
					,'Имя'			          => $order['payment_firstname']
					,'Адрес' => array(
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

				// Товары
				$products = $this->model_sale_order->getOrderProducts($orders_data['order_id']);

				$product_counter = 0;
				foreach ($products as $product) {
					$id = $this->get1CProductIdByProductId($product['product_id']);
					
					$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
						 'Ид'             => $id
						,'Наименование'   => $product['name']
						,'ЦенаЗаЕдиницу'  => $product['price']
						,'Количество'     => $product['quantity']
						,'Сумма'          => $product['total']
					);
					
					if ($this->config->get('exchange1c_relatedoptions')) {
						$this->load->model('module/related_options');
						if ($this->model_module_related_options->get_product_related_options_use($product['product_id'])) {
							$order_options = $this->model_sale_order->getOrderOptions($orders_data['order_id'], $product['order_product_id']);
							$options = array();
							foreach ($order_options as $order_option) {
								$options[$order_option['product_option_id']] = $order_option['product_option_value_id'];
							}
							if (count($options) > 0) {
								$ro = $this->model_module_related_options->get_related_options_set_by_poids($product['product_id'], $options);
								if ($ro != FALSE) {
									$char_id = $this->model_module_related_options->get_char_id($ro['relatedoptions_id']);
									if ($char_id != FALSE) {
										$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter]['Ид'] .= "#".$char_id;
									}
								}
							}
							
						}
						
					}

					$product_counter++;
				}

				//Доставка
				$totals = $this->model_sale_order->getOrderTotals($orders_data['order_id']);

				foreach ($totals as $total) {
					if ($total['code']=='shipping') {
						$document['Документ' . $document_counter]['Товары']['Товар' . $product_counter] = array(
							 'Ид'         => ''
							,'Наименование' => 'Доставка'
							,'ЦенаЗаЕдиницу'=> $total['value']
							,'Количество' => 1
							,'Сумма'       => $total['value']
						);
					}
				}
				$document_counter++;
			}
		}

		$root = '<?xml version="1.0" encoding="UTF-8"?><КоммерческаяИнформация ВерсияСхемы="2.04" ДатаФормирования="' . date('Y-m-d', time()) . '" />';
		$xml = $this->array_to_xml($document, new SimpleXMLElement($root));

		return $xml->asXML();
	} // queryOrders()

	/**
	 * Меняет статусы заказов 
	 *
	 * @param	int		exchange_status	
	 * @return	bool
	 */
	public function queryOrdersStatus($params){

		$this->load->model('sale/order');

		if ($params['exchange_status'] != 0) {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `order_status_id` = " . $params['exchange_status'] . "");
		} else {
			$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $params['from_date'] . "'");
		}

		if ($query->num_rows) {
			foreach ($query->rows as $orders_data) {
				$this->model_sale_order->addOrderHistory($orders_data['order_id'], array(
					'order_status_id' => $params['new_status'],
					'comment'         => '',
					'notify'          => $params['notify']
				));
			}
		}

		return true;
	} // queryOrdersStatus()


	//	********************************************** Загрузка заказов ********************************************** 

	/**
	 * Загружает заказы из файла (в проекте)
	 *
	 * @param    string    наименование типа цены
	 */
	public function parseOrders($filename, $config_price_type) {
		$this->log("========== Начат разбор заказов из файла: " . $filename . " ==========");
		$this->PRODUCT = array();
 		
		$this->load->model('sale/order');
		$this->load->model('localisation/order_status');

 		if ($xml->Документ) {
			foreach ($xml->Документ as $order) {
				$this->log("Ид документа: " . $order->Ид);
				$this->log("Номер: " . $order->Номер);
				$this->log("Дата: " . $order->Дата);
				$this->log("ХозОперация: " . $order->ХозОперация);
				$this->log("Роль: " . $order->Роль);
				$this->log("Сумма: " . $order->Сумма);
				$this->log("Время: " . $order->Время);
				$this->log("Срок платежа: " . $order->СрокПлатежа);

				$order_db = $this->model_sale_order->getOrder((int)$order->Номер);
				$order_status_info = $this->model_localisation_order_status->getOrderStatus((int)$order_db['order_status_id']); 
				$this->log('$order_db:');
				$this->log($order_db);
				$this->log('$order_status_info:');
				$this->log($order_status_info);

			}
		}

		$this->log("========== Окончен разбор заказов ==========");
	}
	
	//	********************************************** Загрузка предложений ********************************************** 
	
	/**
	 * Инициализация типов цен из файла и указанные в настройках модуля
	 * Вызывается из parseOffers()
	 *
	 * @param	SimpleXMLElement
	 * @param	array		Массив с настройками типов цен
	 */
	 private function initPriceType($xml, $config_price_type) {
		$this->log("	--- Начата загрузка типов цен: initPriceType()");

		// Если не будет указана ни один тип цен, то модуль в дефолтную группу запишет первую цену в товаре
		// Перебираем все цены указанные в настройках
		foreach ($config_price_type as $key => $cprice_type) {
			if ($key == 0) {
				// Это дефолтный тип цены, проверим на заполнение
				if (empty($cprice_type['keyword'])) {
					$this->log("	[ERR] В настройках модуля не заполнен тип цен для группы по умолчанию! Цены не будут загружены!");
					return 0;
				}
			}
			foreach ($xml as $type) {
	
				// если уже такой тип цены задан, пропускаем
				if (isset($this->PRICE_TYPES[(string)$type->Ид]))
					continue;
	
				if ($cprice_type['keyword'] == (string)$type->Наименование) {
					if (!isset($cprice_type['customer_group_id'])) {
						$this->log("	[ERR] Проверьте настройки типов цен! Не указана группа покупателей для цены: " . $cprice_type['keyword']);
						continue; 
					}
					$this->PRICE_TYPES[(string)$type->Ид] = array(
						'keyword' 			=> (string)$type->Наименование,  
						'customer_group_id' => $cprice_type['customer_group_id'],
						'quantity' 			=> $cprice_type['quantity'] > 0 ? $cprice_type['quantity'] : 1,
						'priority' 			=> $cprice_type['priority'],
						'default'			=> $key == 0 ? true : false
					);
					$this->log("	[+] Найден в настройках тип цены: " . (string)$type->Наименование . ", Ид: " . (string)$type->Ид . " с группой id: " . $cprice_type['customer_group_id']);
				}
				
			}

		}

		return 1;
	} // initPriceType()

	/**
	 * Загружает все склады из XML
	 * Вызывается из parseOffers()
	 *
	 * @param	SimpleXMLElement
	 */
	private function parseWarehouses($xml) {
		$this->log("	--- Начата загрузка складов: parseWarehouses()");

		foreach ($xml as $warehouse){
			if (isset($warehouse->Ид) && isset($warehouse->Наименование) ){
				$warehouse_uuid = (string)$warehouse->Ид;
				$name = (string)$warehouse->Наименование;
				
				$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'warehouse` WHERE `1c_warehouse_id` = "' . $this->db->escape($warehouse_uuid) . '"');
				if ($query->num_rows) {
					$warehouse_id = (int)$query->row['warehouse_id'];
					$this->log("	[=] Найден склад: " . (string)$query->row['name']) . ", Ид: " . $warehouse_uuid . ", id: " . $warehouse_id;
				} else {
					$this->db->query('INSERT INTO `' . DB_PREFIX . 'warehouse` SET name = "' . $name . '", `1c_warehouse_id` = "' . $this->db->escape($warehouse_uuid) . '"');
					$warehouse_id = $this->db->getLastId();
					$this->log("	[+] Добавлен склад: " . $name. ", Ид: " . $warehouse_uuid . ", id: " . $warehouse_id);
				}
			}
			
			$this->WAREHOUSES[$warehouse_uuid] = array(
				'warehouse_id'	=> $warehouse_id,
				'name'			=> $name
				);             	
		}
		unset($xml);
	} // parseWarehouses()

	/**
	 * Записывает остаток в таблицы по складам
	 * Вызывается из setQuantity()
	 *
	 * @param	int		id склада
	 * @param	float	остаток
	 */
 	private function setQuantityWarehouse($warehouse_id, $quantity) {
		$this->log("	--- Запись остатков на склад: setQuantityWarehouse()");
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE product_id = " . $this->PRODUCT['product_id'] . " AND warehouse_id = " . $warehouse_id);
		if ($query->num_rows) {
			// ранее установленный остаток
			$this->db->query("UPDATE  `" . DB_PREFIX . "product_quantity` SET warehouse_id = " . $warehouse_id . ", quantity = " . $quantity ." WHERE product_id = " . $this->PRODUCT['product_id']);
			$this->log("		[=] Склад id: " . $warehouse_id . ", остаток: " . $quantity);
		} else {
			// Добавляем остаток на склад
			$this->db->query("INSERT INTO  `" . DB_PREFIX . "product_quantity` SET warehouse_id = " . $warehouse_id . ", product_id = " . $this->PRODUCT['product_id'] . ", quantity = " . $quantity);
			$this->log("		[+] Склад id: " . $warehouse_id . ", остаток: " . $quantity);
		}
 	} // setQuantityWarehouse())
 

	/**
	 * Функция устанавливает общий остаток и по складам
	 * Вызывается из parseOffers()
	 *
	 * @param	SimpleXMLElement
	 */
	private function setQuantity($offer) {
		$this->log("	--- Начата установка остатков: setQuantity()");
		
		if (!isset($this->PRODUCT['product_id'])) {
			$this->log("	[ERR] Не определен product_id. Запись остатков невозможна!");
			return 0;
		}
		
		$this->PRODUCT['quantity'] = 0;
		$this->PRODUCT['product_quantity'] = array();

		if ($offer->Склад) {
			foreach ($offer->Склад as $warehouse) {
				$this->PRODUCT['quantity'] += (float)$warehouse['КоличествоНаСкладе'];
				
				if (isset($this->WAREHOUSES[(string)$warehouse['ИдСклада']]['name'])) {
					$this->PRODUCT['product_quantity'][(string)$warehouse['ИдСклада']] = array(
						'warehouse_id' => $this->WAREHOUSES[(string)$warehouse['ИдСклада']]['warehouse_id'],
						'quantity' => (float)$warehouse['КоличествоНаСкладе']
					);
					$this->setQuantityWarehouse($this->WAREHOUSES[(string)$warehouse['ИдСклада']]['warehouse_id'], (float)$warehouse['КоличествоНаСкладе']);
				}
			}
		} else {
			// Если нет складов
			$this->PRODUCT['quantity'] = isset($offer->Количество) ? (float)$offer->Количество : 0; 			
		}
		$this->log("		[i] Общий остаток по всем складам: " . $this->PRODUCT['quantity']);

	} // setQuantity()

	/**
	 * Загружает цены из XML
	 * Вызывается из parseOffers()
	 *
	 * @param	SimpleXMLElement
	 */
	private function parsePrice($xml){
		$this->log("	--- Начата установка цен на товар: parsePrice()");

		// Инициализируем переменные
		$this->PRODUCT['price'] = 0;
		$this->PRODUCT['product_discount'] = array();
		 
		foreach ($xml as $price) {
			$uuid = (string)$price->ИдТипаЦены;
			if (isset($this->PRICE_TYPES[$uuid])) {
				if ($this->PRICE_TYPES[$uuid]['default']) {
					$this->PRODUCT['price'] = (float)$price->ЦенаЗаЕдиницу;
					$this->log("		[=] Установлена основная цена: " . $this->PRODUCT['price'] . ", валюта: " . (string)$price->Валюта);
				} else {
					$this->PRODUCT['product_discount'][] = array(
						'customer_group_id'	=> $this->PRICE_TYPES[$uuid]['customer_group_id'],
						'quantity'      => $this->PRICE_TYPES[$uuid]['quantity'],
						'priority'      => $this->PRICE_TYPES[$uuid]['priority'],
						'price'         => (float)$price->ЦенаЗаЕдиницу,
						'date_start'    => '0000-00-00',
						'date_end'      => '0000-00-00'
					);
					$this->log("		[=] Установлена цена: " . $this->PRODUCT['price'] . ", валюта: " . (string)$price->Валюта) . " для группы покупателей id: " . $this->PRICE_TYPES[$uuid]['customer_group_id'];
				}
			} else {
				$this->log("		[!] Тип цены Ид: '" . $uuid . "' не найден в настройках!");
			}
		}
	} // parsePrice()

	/**
	 * Получает или добавляет опцию
	 * Вызывается из parseСharacteristics()
	 *
	 * @param    string		Тип
	 * @param    int		Порядок сортировки
	 * @param    string		Наименование опции
	 */
	private function setOption($type, $sort_order, $name){
		$this->log("	--- Начато добавление опции: setOption()");
		
		$this->load->model('catalog/option');
		
		// Проверим есть ли такая опция
		$results = $this->model_catalog_option->getOptions(array('name' => $name));
		
		if (count($results)){
			// существует одна или более
			$this->log("		[=] Найдена опция id: " . $results[0]['option_id']);
			return $results[0]['option_id'];
		} else {
			// Нет такой опции
			// Подготавливаем данные
			$data_option = array();
			$data_option['type'] 				= $type;
			$data_option['sort_order']			= $sort_order;
			$data_option['option_description']	= array(
				$this->LANG => array(
					'name'	=> $name
				)
			);
			$option_id = $this->model_catalog_option->addOption($data_option);
			$this->log("		[+] Добавлена опция id: " . $option_id);
		}
		return $option_id;
	} // setOption($name)

	/**
	 * Получает или добавляет значение в опцию
	 * Вызывается из parseСharacteristics()
	 *
	 * @param    string		наименование значения опции
	 * @param    string		картинка
	 * @param    int		порядок сортировки
	 * @param    int		id опции в которую записывается значение
	 */
	private function setOptionValue($name, $image='', $sort_order=0, $option_id){
		$this->log("	--- Начато получение/добавление значения в опцию: setOptionValue()");
		
		// Поиск
		$query = $this->db->query("SELECT option_value_id FROM ". DB_PREFIX ."option_value_description WHERE name='". $this->db->escape($value) ."' AND option_id='". $option_id ."'");
		if ($query->num_rows > 0) {
			$this->log("		[=] Найдено значение опции id=" . $query->row['option_value_id']);
			return $query->row['option_value_id'];
		}
		
		// Добавление
		$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($image, ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$sort_order . "'");
		$option_value_id = $this->db->getLastId();
		$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$this->LANG . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($name) . "'");
		$this->log("		[+] Добавлено значение в опцию id=" . $option_value_id);
		
		return $option_value_id;
	} // setOptionValue($name)

	/**
	 * Загружает Характеристики из 1С в опции в OpenCart
	 * Вызывается из parseOffers()
	 *
	 * @param	SimpleXMLElement
	 */
	private function parseСharacteristics($xml) {
		$this->log("	--- Начата загрука характеристик: parseСharacteristics()");
		
		// Количество характеристик
		$count = count($xml);
		
		// Подготавливаем данные
		$product_option_data = array();
		$product_option_value_data = array();
		
		// Перебираем все характеристики			
		foreach ($xml as $i => $opt) {
			$name_1c	= (string)$opt->Наименование; 
			$value_1c	= (string)$opt->Значение;
			
			// Если задана характеристика и значение
			if (!empty($name_1c) && !empty($value_1c)) {
				$uuid = explode("#", $offer->Ид);
				$this->PRODUCT['1с_id'] = $uuid[0];
				if (isset($uuid[1])) {
					$this->log("		[=] Найдены характеристики Ид: " . $uuid[1] . " : " . $name_1c . " -> " . $value_1c);
				} else {
					$this->log("		[ERR] Характеристики товара есть, а Ид не указаны! Обратитесь к разработчику. Характеристика не загружена!");
					return 0;
				}
				// Ищем такую опцию и значение, если нету создаем
				$option_id = $this->setOption($name_1c);
				$option_value_id = $this->setOptionValue($value_1c, $option_id);
				
				$product_option_value_data[]  = array(
					'product_option_value_id'	=> '',
					'product_option_id'			=> '',
					'option_value_id'			=> $option_value_id,
					'name'						=> $value_1c,
					'image'						=> '',
					'quantity'					=> isset($this->PRODUCT['quantity']) ? (float)$this->PRODUCT['quantity'] : 0,
					'subtract'					=> '',
					'price'						=> isset($this->PRODUCT['price']) ? (float)$this->PRODUCT['price'] : 0,
					'price_prefix'				=> '+',
					'points'					=> '',
					'points_prefix'				=> '+',
					'weight'					=> 0,
					'weight_prefix'				=> '+'
				);
				if (!isset($product_option[$i])) {
					$product_option_data[] = array(
						'product_option_id'		=> '',
						'option_id'				=> $option_id,
						'name'					=> $value_1c,
						'type'					=> 'select',
						'product_option_value'	=> $product_option_value_data,
						'required'				=> 1
					);
				}
			}
		}
	} // parseСharacteristics()
	
	/**
	 * Парсит цены и количество
	 *
	 * @param    string    наименование типа цены
	 */
	public function parseOffers($filename, $config_price_type, $language_id) {
		$this->log("========== Начат разбор предложений (остатки и цены) ==========");
		$this->LANG = $language_id;
		if (!is_file(DIR_CACHE . 'exchange1c/' . $filename)) {
			$this->log("[ERR] Не найден файл предложений!");
			return 0;
		}
		$xml = simplexml_load_file(DIR_CACHE . 'exchange1c/' . $filename);

		// Определение магазина
		// в стадии разработки
		
		// Загрузка и определение валют
		$this->load->model('localisation/currency');
		$currencies = $this->model_localisation_currency->getCurrencies(); 

		// Загрузка складов
		if ($xml->ПакетПредложений->Склады->Склад) $this->parseWarehouses($xml->ПакетПредложений->Склады->Склад);

		// Инициализация типов цен
		if ($xml->ПакетПредложений->ТипыЦен->ТипЦены) $this->initPriceType($xml->ПакетПредложений->ТипыЦен->ТипЦены, $config_price_type);
		
		$offer_cnt = 0;

		$this->load->model('catalog/option');
		//Загрузка предложений
		if ($xml->ПакетПредложений->Предложения->Предложение) {
			
			foreach ($xml->ПакетПредложений->Предложения->Предложение as $offer) {
				$new_product = (!isset($data));
				$offer_cnt++;
				
				if (!$this->config->get('exchange1c_relatedoptions') || $new_product) {
					$this->PRODUCT = array();
					$this->PRODUCT['price'] = 0;
					
					//UUID без номера после #
					$uuid = explode("#", $offer->Ид);
					$this->PRODUCT['1c_id'] = $uuid[0];
					$this->PRODUCT['product_id'] = $this->getProductIdBy1CProductId ($uuid[0]);
					// + 1.6.1.7
					if (!$this->PRODUCT['product_id']) {
						$this->log("	[ERR] Не найден товар по Ид: '" . $this->PRODUCT['1c_id'] . "', загрузка предложения прервана!");
						continue;
					} else {
						$this->log("	[=] Найден товар по Ид: '" . $this->PRODUCT['1c_id'] . "', id: '" . $this->PRODUCT['product_id'] . "'");
					}
					// - 1.6.1.7
					
					if ($offer->Цены) $this->parsePrice($offer->Цены->Цена);
					$this->PRODUCT['quantity'] = isset($offer->Количество) ? (int)$offer->Количество : 0;
				}

				//Характеристики
				if ($offer->ХарактеристикиТовара->ХарактеристикаТовара) $this->parseСharacteristics($offer->ХарактеристикиТовара->ХарактеристикаТовара); 

				// Установка остатков
				$this->setQuantity($offer);
				
				if (!$this->config->get('exchange1c_relatedoptions') || $new_product) {
					
					if ($offer->СкидкиНаценки) {
						$value = array();
						foreach ($offer->СкидкиНаценки->СкидкаНаценка as $discount) {
							$value = array(
								 'customer_group_id'	=> 1
								,'priority'     => isset($discount->Приоритет) ? (int)$discount->Приоритет : 0
								,'price'        => (int)(($this->PRODUCT['price'] * (100 - (float)str_replace(',', '.', (string)$discount->Процент))) / 100)
								,'date_start'   => isset($discount->ДатаНачала) ? (string)$discount->ДатаНачала : ''
								,'date_end'     => isset($discount->ДатаОкончания) ? (string)$discount->ДатаОкончания : ''
								,'quantity'     => 0
							);
	
							$this->PRODUCT['product_discount'][] = $value;
	
							if ($discount->ЗначениеУсловия) {
								$value['quantity'] = (int)$discount->ЗначениеУсловия;
							}
	
							unset($value);
						}
					}
	
					if ($offer->Статус) {
						$this->PRODUCT['status'] = (string)$offer->Статус;
					}
					
				}
				
				if (!$this->config->get('exchange1c_relatedoptions') || $offer_cnt == count($xml->ПакетПредложений->Предложения->Предложение)
					|| $this->PRODUCT['1c_id'] != substr($xml->ПакетПредложений->Предложения->Предложение[$offer_cnt]->Ид, 0, strlen($this->PRODUCT['1c_id'])) )
						$this->updateProduct($this->PRODUCT, $language_id);
				
			}
		}
		$this->cache->delete('product');
		$this->log("========== Окончен разбор предложений ==========");
	} // parseOffers()

	/**
	 * Загружает характеристики товара из каталога товаров (import.xml)
	 * Вызывается из parseProducts()
	 * !!! Требует переработки
	 * 
	 * @param	SimpleXMLElement
	 */
	private function parseOptions($xml) {
		$this->log("	--- Загружаются характеристики в 'option_desc': parseOptions()");
		$count_options = count($xml->ХарактеристикаТовара);
		$this->PRODUCT['option_desc'] = '';

		foreach($xml->ХарактеристикаТовара as $option ) {
			$this->PRODUCT['option_desc'] .= (string)$option->Наименование . ': ' . (string)$option->Значение . ';';
		}
		$this->PRODUCT['option_desc'] = ";\n";
		$this->log("		[+] Характеристики товара: " . $this->PRODUCT['option_desc']);
	} // parseOptions()

	/**
	 * Загружает картинки товара из каталога товаров (import.xml)
	 * Вызывается из parseProducts()
	 * 
	 * @param	SimpleXMLElement
	 */
	private function parseImages($xml) {
		$this->log("	--- Загружаются картинки: parseImages()");
		$watermark = $this->config->get('exchange1c_watermark');

		foreach ($xml as $image) {
			// проверим существование картинки
			if (file_exists(DIR_IMAGE . (string)$image)) {
				$newimage = empty($watermark) ? (string)$image : $this->applyWatermark((string)$image);
			}
			else {
				$newimage = 'no_image.png';
				$this->log("		[!] Картинка не найдена: " . (string)$image . ", будет добавлена картинка 'no_image.png'");
			}
			
			if (empty($this->PRODUCT['image'])) {
				// Первая картинка
				$this->PRODUCT['image'] = $newimage;
			}
			else {
				$this->PRODUCT['product_image'][] = array(
					'image' => $newimage,
					'sort_order' => 0
				);
			}
			$this->log("		[+] Добавлена картинка: " . $newimage);
			
		}
	} // parseImages()

	/**
	 * Находит или создает производителя в базе и устанавливает manufacturer_id в товар
	 * Вызывается из parseProperties() или parseProducts()
	 * 
	 * @param	string	Наименование производителя
	 */
	private function setManufacturer($manufacturer_name) {
		$this->log("	--- Устанавливается производитель: setManufacturer()");
		$this->load->model('catalog/manufacturer');
		
		$results = $this->model_catalog_manufacturer->getManufacturers(array('filter_name' => $manufacturer_name));
		if ($results) {
			$this->PRODUCT['manufacturer_id'] = $results[0]['manufacturer_id'];
			$this->log("		[=] Найден производитель: " . $manufacturer_name . ", id: " . $this->PRODUCT['manufacturer_id']);
		}
		else {
			$data_manufacturer = array(
				'name' => $manufacturer_name,
				'keyword' => '',
				'sort_order' => 0,
				'manufacturer_store' => array(0 => $this->STORE)
			);
			$data_manufacturer['manufacturer_description'] = array(
				$this->LANG => array(
					'meta_keyword' => '',
					'meta_description' => '',
					'description' => '',
					'seo_title' => '',
					'seo_h1' => ''
				),
			);
			$this->PRODUCT['manufacturer_id'] = $this->model_catalog_manufacturer->addManufacturer($data_manufacturer);
			$this->log("		[+] Добавлен производитель: " . $manufacturer_name . ", id: " . $this->PRODUCT['manufacturer_id']);

			//только если тип 'translit'
			if ($this->config->get('exchange1c_seo_url') == 2) {
				$man_name = "brand-" . $manufacturer_name;
				$this->setSeoURL('manufacturer_id', $this->PRODUCT['manufacturer_id'], $man_name);
			}
		}
	} // setManufacturer()

	/**
	 * Загружает свойства товара
	 * Вызывается из parseProducts()
	 * 
	 * @param	SimpleXMLElement
	 */
	private function parseProperties($xml) {
		$this->log("	--- загружаются свойства товара: parseProperties()");
		foreach ($xml->ЗначенияСвойства as $property) {
			if (isset($this->PROPERTIES[(string)$property->Ид]['name'])) {
				$attribute = $this->PROPERTIES[(string)$property->Ид];

				if (isset($attribute['values'][(string)$property->Значение])) {
					$attribute_value = str_replace("'", "&apos;", (string)$attribute['values'][(string)$property->Значение]);
				}
				else if ((string)$property->Значение != '') {
					$attribute_value = str_replace("'", "&apos;", (string)$property->Значение);
				}
				else {
					continue;
				}
				$this->log("		[+] Свойство: " . $attribute['name'] . " : " . $attribute_value);

				switch ($attribute['name']) {
					case 'Производитель':
						$this->setManufacturer($attribute_value);
					break;
					case 'oc.seo_h1':
						$this->PRODUCT['seo_h1'] = $attribute_value;
					break;
					case 'oc.seo_title':
						$this->PRODUCT['seo_title'] = $attribute_value;
					break;
					case 'oc.sort_order':
						$this->PRODUCT['sort_order'] = $attribute_value;
					break;
					default:
						$this->PRODUCT['product_attribute'][] = array(
							'attribute_id'			=> $attribute['attribute_id'],
							'product_attribute_description'	=> array(
								$this->LANG => array(
									'text' => $attribute_value
								)
							)
						);
				}
			}
		}
	} // parseProperties()

	/**
	 * Загружает реквизиты товара из XML
	 * Вызывается из parseProducts()
	 * 
	 * @param	SimpleXMLElement
	 */
 	private function parseRequisite($xml){
		$this->log("	--- загружаются реквизиты товара: parseRequisite()");
		foreach ($xml->ЗначениеРеквизита as $requisite){
			switch ($requisite->Наименование){
				case 'Вес':
					$this->PRODUCT['weight'] = $requisite->Значение ? (float)$requisite->Значение : 0;
				break;
				case 'ОписаниеФайла':
					// XML v2.04
					$desc_file = explode("#", (string)$requisite->Значение);
					$this->log("		[?] " . (string)$requisite->Наименование . " : " . $desc_file[1]);
				break;
				case 'ТипНоменклатуры':
					$this->PRODUCT['type'] = $requisite->Значение ? (string)$requisite->Значение : '';
					$this->log("		[++] " . (string)$requisite->Наименование. " : " . (string)$requisite->Значение);
				break;
				case 'ОписаниеВФорматеHTML':
					$this->PRODUCT['description'] = $requisite->Значение ? htmlspecialchars((string)$requisite->Значение) : '';
					$this->log("		[++] " . (string)$requisite->Наименование . ", длина строки: " . strlen($this->PRODUCT['description']));
				break;
				case 'Полное наименование':
					$this->PRODUCT['meta_description'] = $requisite->Значение ? htmlspecialchars((string)$requisite->Значение) : '';
					$this->PRODUCT['name'] = $requisite->Значение ? (string)$requisite->Значение : '';
					$this->log("		[++] " . (string)$requisite->Наименование . ", длина строки: " . strlen($this->PRODUCT['meta_description']));
				break;
				default:
					$this->log("		[+] " . (string)$requisite->Наименование. " : " . (string)$requisite->Значение);
			}
		}
 	} // parseRequisite()
	 
	/**
	 * Загружает все категории из классификатора
	 * Вызывается из parseImport()
	 *
	 * @param	SimpleXMLElement
	 * @param	int
	 */
	private function parseCategories($xml, $parent = 0) {
		$this->load->model('catalog/category');
		
		foreach ($xml as $category){

			if (isset($category->Ид) && isset($category->Наименование) ){ 
				$uuid =  (string)$category->Ид;
				$data = array();

				$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'category_to_1c` WHERE `1c_category_id` = "' . $this->db->escape($uuid) . '"');

				if ($query->num_rows) {
					$category_id = (int)$query->row['category_id'];
					$data = $this->model_catalog_category->getCategory($category_id);
					$data['category_description'] = $this->model_catalog_category->getCategoryDescriptions($category_id);
					$data['status'] = 1;
					$data = $this->initCategory($category, $parent, $data, $this->LANG);
					$this->model_catalog_category->editCategory($category_id, $data);
					//$this->log("	> такая категория уже есть, id: " . $category_id);
				}
				else {
					$data = $this->initCategory($category, $parent, array(), $this->LANG);
					//$category_id = $this->getCategoryIdByName($data['category_description'][1]['name']) ? $this->getCategoryIdByName($data['category_description'][1]['name']) : $this->model_catalog_category->addCategory($data);
					$category_id = $this->model_catalog_category->addCategory($data);
					$this->db->query('INSERT INTO `' . DB_PREFIX . 'category_to_1c` SET category_id = ' . (int)$category_id . ', `1c_category_id` = "' . $this->db->escape($uuid) . '"');
					//$this->log("	> создана новая категория, id: " . $category_id);
				}

				$this->CATEGORIES[$uuid] = $category_id;
			}

			$this->log("	[=] Найдена категория: " . (string)$category->Наименование . ", Ид: " . $uuid . ", id: " . $category_id);
			
			//только если тип 'translit'
			if ($this->config->get('exchange1c_seo_url') == 2) {
				$cat_name = "category-" . $data['parent_id'] . "-" . $data['category_description'][$this->LANG]['name'];
				$this->setSeoURL('category_id', $category_id, $cat_name);
			}

			if ($category->Группы) $this->parseCategories($category->Группы->Группа, $category_id, $this->LANG);
		}
		unset($xml);
	} // parseCategories()

	/**
	 * Читает значения из свойств для XML версии 2.04.1CBitrix
	 * Вызывается из parseAttribute()
	 *
	 * @param 	SimpleXMLElement
	 */
	 private function parseAttributeValues2041($xml, $values) {
		$this->log("	--- Добавление значений в атрибут: parseAttributeValues2041()");
		if ((string)$xml->ТипыЗначений) {
			if ((string)$xml->ТипыЗначений->ТипЗначений->Тип == 'Справочник') {
				foreach($xml->ТипыЗначений->ТипЗначений as $type_value){
					if (!count($type_value->ВариантыЗначений->ВариантЗначения)) {
						$this->log("		[!] У свойства тип Справочник нет значений.");
						return $values;
					}
					foreach($type_value->ВариантыЗначений->ВариантЗначения as $option_value){
						if (empty($option_value->Ид) || empty($option_value->Значение))
							continue;
						$values[(string)$option_value->Ид] = (string)$option_value->Значение;
						$this->log("		[=] Найдено значение справочника: " . (string)$option_value->Значение . ", Ид: " .  (string)$option_value->Ид);
					}
				}
			} elseif ((string)$xml->ТипыЗначений->ТипЗначений->Тип == 'Булево') {
				// пока не реализовано
			} elseif ((string)$xml->ТипыЗначений->ТипЗначений->Тип == 'Число') {
				// пока не реализовано
			} elseif ((string)$xml->ТипыЗначений->ТипЗначений->Тип == 'Строка') {
				// пока не реализовано
			}
		}
		return $values;
	 } // parseAttributeValues2041()
	 

	/**
	 * Читает значения из свойств для XML других версий: 2.07
	 * Вызывается из parseAttribute()
	 *
	 * @param 	SimpleXMLElement
	 */
	 private function parseAttributeValues($xml, $values) {
		$this->log("	--- Добавление значений в атрибут: parseAttributeValues()");
		if ((string)$xml->ТипЗначений) {
			if ((string)$xml->ТипЗначений == 'Справочник') {
				if (!count($xml->ВариантыЗначений->Справочник)) {
					$this->log("		[!] У свойства тип Справочник нет значений.");
					return $values;
				}
				foreach($xml->ВариантыЗначений->Справочник as $option_value){
					if (empty($option_value->ИдЗначения) || empty($option_value->Значение))
						continue;
					$values[(string)$option_value->ИдЗначения] = (string)$option_value->Значение;
					$this->log("		[=] Найдено значение справочника: " . (string)$option_value->Значение . ", Ид: " .  (string)$option_value->ИдЗначения);
				}
			} elseif ((string)$xml->Типыначений == 'Булево') {
				// пока не реализовано
			} elseif ((string)$xml->ТипЗначений == 'Число') {
				// пока не реализовано
			} elseif ((string)$xml->ТипЗначений == 'Строка') {
				// пока не реализовано
			}
		}
		return $values;
	 } // parseAttributeValues()
	 
	/**
	 * Ищет или создает группу для свойств "Свойства"
	 * Вызывается из parseAttributes()
	 *
	 * @param 	SimpleXMLElement
	 */
	private function setAttributeGroup() {
		$this->log("	--- Установка группы для атрибута: setAttributeGroup()");
		// Ищем группу атрибутов с именем "Свойства"
		$query = $this->db->query("SELECT attribute_group_id FROM " . DB_PREFIX . "attribute_group_description WHERE name = 'Свойства'");
		if ($query->rows) {
			$attribute_group_id = $query->row['attribute_group_id']; 
			$this->log("	[=] Нашли группу свойств 'Свойства', id: " . $attribute_group_id);
		} else {
			// Не нашли, создаем...
			$attribute_group_description[$this->LANG] = array (
				'name'	=> 'Свойства' 
			);
			$data = array (
				'sort_order'					=> 0,
				'attribute_group_description'	=> $attribute_group_description
			);
			$this->load->model('catalog/attribute_group');
			$attribute_group_id = $this->model_catalog_attribute_group->addAttributeGroup($data);
			$this->log("	[+] Создан атрибут 'Свойства', id: " . $attribute_group_id);
		}
		return $attribute_group_id;
	}

	/**
	 * Загружает из классификатора свойства и значения у объектов
	 * Вызывается из parseImport()
	 *
	 * @param 	SimpleXMLElement
	 */
	private function parseAttributes($xml) {
		$this->log("	--- Начало загрузки свойств в атрибутф: parseAttributes()");
		// если нет свойст - выходим
		if (!count($xml)) return 0;

		// Временная переменная для подсчета свойств
		$count = 0;
		
		$attribute_group_id = $this->setAttributeGroup();
			
		$this->load->model('catalog/attribute');
		foreach ($xml as $attribute) {
			$values	= array();
			$uuid	= (string)$attribute->Ид;
			$name = (string)$attribute->Наименование;

			$this->log("		[i] Ищем значения свойства '" . $name .", Ид: " . $uuid);
			if ($this->VERSION_XML == "2.04.1CBitrix") {
				$values = $this->parseAttributeValues2041($attribute, $values);
			} else {
				$values = $this->parseAttributeValues($attribute, $values);
			}

			$data = array (
				'attribute_group_id'    => $attribute_group_id,
				'sort_order'            => 0,
			);

			$data['attribute_description'][$this->LANG] = array('name' => (string)$name);

			// Если атрибут уже был добавлен, то возвращаем старый id, если атрибута нет, то создаем его и возвращаем его id
			$query = $this->db->query('SELECT attribute_id FROM ' . DB_PREFIX . 'attribute_to_1c WHERE 1c_attribute_id = "' . $uuid . '"');
			if (!$query->num_rows) {
				$attribute_id = $this->model_catalog_attribute->addAttribute($data);
				$this->db->query('INSERT INTO `' .  DB_PREFIX . 'attribute_to_1c` SET attribute_id = ' . (int)$attribute_id . ', `1c_attribute_id` = "' . $uuid . '"');
			}
			else {
				$attribute_id = $query->row['attribute_id'];
			}

			$this->PROPERTIES[$uuid] = array(
				'attribute_id'	=> $attribute_id,
				'name'			=> $name,
				'values'		=> $values
			);
			$this->log("	[I] Найдено свойство: " . $name . ", Ид: " . $uuid . ", id: " . $attribute_id . ", значений: " . count($values));
			$count ++;
		}
		$this->log("	[i] Всего загружено свойств: " . $count);
		unset($xml);
	} // parseAttributes()

	/**
	 * Загружает все товары из XML
	 * Вызывается из parseImport()
	 */
	private function parseProducts($xml) {
		$this->load->model('catalog/manufacturer');

		// Количество загруженных товаров
		$count = 0;
		
		$this->log("	[i] Всего товаров в XML: ".count($xml));
		
		foreach ($xml as $product) {
			$this->PRODUCT = array();

			$uuid = explode('#', (string)$product->Ид);
			$this->PRODUCT['1c_id'] = $uuid[0];

			$this->PRODUCT['model'] = $product->Артикул? (string)$product->Артикул : 'не задана';
			$this->PRODUCT['name'] = $product->Наименование? (string)$product->Наименование : 'не задано';
			$this->PRODUCT['weight'] = $product->Вес? (float)$product->Вес : null;
			$this->PRODUCT['sku'] = $product->Артикул? (string)$product->Артикул : '';

			$this->log("	[=] Найден товар: '" . $this->PRODUCT['name'] . ", Ид: '" . $this->PRODUCT['1c_id'] . "'");
			$this->log("	[=] Артикул: '" . $this->PRODUCT['sku']. "'");
			
			if ($product->Картинка) 			$this->parseImages($product->Картинка);
			if ($product->Изготовитель)			$this->setManufacturer((string)$product->Изготовитель->Наименование);
			if ($product->ХарактеристикиТовара) $this->parseOptions($product->ХарактеристикиТовара);
			if ($product->Группы) 				$this->PRODUCT['category_1c_id'] = $product->Группы->Ид;
			if ($product->Описание) 			$this->PRODUCT['description'] = (string)$product->Описание;
			if ($product->Статус) 				$this->PRODUCT['status'] = (string)$product->Статус;
			
			// XML v2.04
			if ($product->ЗначенияСвойств) $this->parseProperties($product->ЗначенияСвойств);
			
			if($product->ЗначенияРеквизитов) $this->parseRequisite($product->ЗначенияРеквизитов);

			$this->setProduct($this->PRODUCT);
		}
		unset($xml);
	} // parseProducts()


	/**
	 * Парсит товары и категории
	 */
	public function parseImport($filename, $language_id) {

		$this->log("========== Начало разбора каталога ==========");
		$this->data = array();
		$importFile = DIR_CACHE . 'exchange1c/' . $filename;
		$this->LANG  = $language_id;
		$xml = simplexml_load_file($importFile);
		$this->log("[i] Версия OpenCart: " . VERSION);
		 
		// Версия XML
		if ($xml) $this->VERSION_XML = (string)$xml['ВерсияСхемы'];
		$this->log("[i] Версия XML: " . $this->VERSION_XML);
		$this->log("--- Начало загрузки классификатора: '" . (string)$xml->Классификатор->Наименование . "' ----");

		// Определим в какой магазин загружать каталог 1С
		if($xml->Классификатор) $this->getStores($xml->Классификатор);
		
		// Прочитаем название организации и ее реквизиты (нужны в дальнейшем для оформления документов) 
		if($xml->Классификатор->Владелец) $this->getCompany($xml->Классификатор->Владелец);
		
		// Загрузим категории товаров
		$this->log("	--- Начало загрузки категорий: parseCategories()");
		if($xml->Классификатор->Группы) $this->parseCategories($xml->Классификатор->Группы->Группа, 0);
		$this->log("	--- Окончание загрузки категорий");
		
		// Загрузим свойства
		if ($xml->Классификатор->Свойства) $this->parseAttributes($xml->Классификатор->Свойства->Свойство);

		$this->log("--- Окончание загрузки классификатора ---");


		// Загрузим товары
		$this->log("	--- Начало загрузки товаров: parseProducts()");
		if ($xml->Каталог->Товары) $this->parseProducts($xml->Каталог->Товары->Товар);
		$this->log("	--- Окончание загрузки товаров ----");

		unset($xml);
		$this->log("========== Окончен разбор каталога ==========");
		
	} // parseImport()


	/**
	 * Инициализируем данные для категории дабы обновлять данные, а не затирать
	 *
	 * @param	array	старые данные
	 * @param	int	id родительской категории
	 * @param	array	новые данные
	 * @return	array
	 */
	private function initCategory($category, $parent, $data = array()) {

		$result = array(
			 'status'         => isset($data['status']) ? $data['status'] : 1
			,'top'            => isset($data['top']) ? $data['top'] : 1
			,'parent_id'      => $parent
			,'category_store' => array($this->STORE)
			,'keyword'        => isset($data['keyword']) ? $data['keyword'] : ''
			,'image'          => (isset($category->Картинка)) ? (string)$category->Картинка : ((isset($data['image'])) ? $data['image'] : '')
			,'sort_order'     => (isset($category->Сортировка)) ? (int)$category->Сортировка : ((isset($data['sort_order'])) ? $data['sort_order'] : 0)
			,'column'         => isset($data['column']) ? $data['column'] : 1
		);
		
		$result['category_description'] = array(
			$this->LANG => array(
				 'name'             => (string)$category->Наименование
				,'meta_keyword'     => (isset($data['category_description'][$this->LANG]['meta_keyword'])) ? $data['category_description'][$this->LANG]['meta_keyword'] : ''
				,'meta_description'	=> (isset($data['category_description'][$this->LANG]['meta_description'])) ? $data['category_description'][$this->LANG]['meta_description'] : ''
				,'description'		=> (isset($category->Описание)) ? (string)$category->Описание : ((isset($data['category_description'][$this->LANG]['description'])) ? $data['category_description'][$this->LANG]['description'] : '')
				,'meta_title'		=> (isset($data['category_description'][$this->LANG]['seo_title'])) ? $data['category_description'][$this->LANG]['seo_title'] : ''
				,'seo_h1'           => (isset($data['category_description'][$this->LANG]['seo_h1'])) ? $data['category_description'][$this->LANG]['seo_h1'] : ''
			),
		);

		// ocshop
		if (version_compare(VERSION, '2.1.0.1.4', '=')) {
			$result['noindex'] = isset($data['noindex']) ? $data['noindex'] : '';
			$result['category_description'][$this->LANG]['meta_h1'] = (isset($data['category_description'][$this->LANG]['meta_h1'])) ? $data['category_description'][$this->LANG]['meta_h1'] : '';
		}

		return $result;
	}

	/**
	* Функция работы с продуктом
	* @param	int
	* @return	array
	*/

	private function getProductWithAllData() {
		$this->load->model('catalog/product');
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . $this->PRODUCT['product_id'] . "' AND pd.language_id = '" . $this->LANG . "'");

		$data = array();

		if ($query->num_rows) {

			$data = $query->row;

			$data = array_merge($data, array('product_description' => $this->model_catalog_product->getProductDescriptions($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_option' => $this->model_catalog_product->getProductOptions($this->PRODUCT['product_id'])));

			$data['product_image'] = array();

			$results = $this->model_catalog_product->getProductImages($this->PRODUCT['product_id']);

			foreach ($results as $result) {
				$data['product_image'][] = array(
					'image' => $result['image'],
					'sort_order' => $result['sort_order']
				);
			}

			if (method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
				$data = array_merge($data, array('main_category_id' => $this->model_catalog_product->getProductMainCategoryId($this->PRODUCT['product_id'])));
			}
			
			$data = array_merge($data, array('product_discount' => $this->model_catalog_product->getProductDiscounts($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_special' => $this->model_catalog_product->getProductSpecials($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_download' => $this->model_catalog_product->getProductDownloads($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_category' => $this->model_catalog_product->getProductCategories($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_store' => $this->model_catalog_product->getProductStores($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_related' => $this->model_catalog_product->getProductRelated($this->PRODUCT['product_id'])));
			$data = array_merge($data, array('product_attribute' => $this->model_catalog_product->getProductAttributes($this->PRODUCT['product_id'])));

		}

		$query = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "product_id=' . $this->PRODUCT['product_id'] . '"');
		if ($query->num_rows) $data['keyword'] = $query->row['keyword'];

		return $data;
	}

	/**
	 * Обновляет массив с информацией о продукте
	 *
	 * @param	array	новые данные
	 * @param	array	обновляемые данные
	 * @return	array
	 */
	private function initProduct($product, $data = array()) {

		$this->load->model('tool/image');

		$result = array(
			'product_description'	=> array()
			,'model'				=> isset($product['model']) ? $product['model'] : (isset($data['model']) ? $data['model']: '')
			,'sku'					=> isset($product['sku']) ? $product['sku'] : (isset($data['sku']) ? $data['sku']: '')
			,'upc'					=> isset($product['upc']) ? $product['upc'] : (isset($data['upc']) ? $data['upc']: '')
			,'ean'					=> isset($product['ean']) ? $product['ean'] : (isset($data['ean']) ? $data['ean']: '')
			,'jan'					=> isset($product['jan']) ? $product['jan'] : (isset($data['jan']) ? $data['jan']: '')
			,'isbn'					=> isset($product['isbn']) ? $product['isbn'] : (isset($data['isbn']) ? $data['isbn']: '')
			,'mpn'					=> isset($product['mpn']) ? $product['mpn'] : (isset($data['mpn']) ? $data['mpn']: '')

			,'location'				=> isset($product['location']) ? $product['location'] : (isset($data['location']) ? $data['location']: '')
			,'price'				=> isset($product['price']) ? $product['price'] : (isset($data['price']) ? $data['price']: 0)
			,'tax_class_id'			=> isset($product['tax_class_id']) ? $product['tax_class_id'] : (isset($data['tax_class_id']) ? $data['tax_class_id']: 0)
			,'quantity'				=> isset($product['quantity']) ? $product['quantity'] : (isset($data['quantity']) ? $data['quantity']: 0)
			,'minimum'				=> isset($product['minimum']) ? $product['minimum'] : (isset($data['minimum']) ? $data['minimum']: 1)
			,'subtract'				=> isset($product['subtract']) ? $product['subtract'] : (isset($data['subtract']) ? $data['subtract']: 1)
			,'stock_status_id'		=> 5
			,'shipping'				=> isset($product['shipping']) ? $product['shipping'] : (isset($data['shipping']) ? $data['shipping']: 1)
			,'keyword'				=> isset($product['keyword']) ? $product['keyword'] : (isset($data['keyword']) ? $data['keyword']: '')
			,'image'				=> isset($product['image']) ? $product['image'] : (isset($data['image']) ? $data['image'] : '')
			,'date_available'		=> date('Y-m-d', time() - 86400)
			,'length'				=> isset($product['length']) ? $product['length'] : (isset($data['length']) ? $data['length']: '')
			,'width'				=> isset($product['width']) ? $product['width'] : (isset($data['width']) ? $data['width']: '')
			,'height'				=> isset($product['height']) ? $product['height'] : (isset($data['height']) ? $data['height']: '')
			,'length_class_id'		=> isset($product['length_class_id']) ? $product['length_class_id'] : (isset($data['length_class_id']) ? $data['length_class_id']: 1)
			,'weight'				=> isset($product['weight']) ? $product['weight'] : (isset($data['weight']) ? $data['weight']: 0)
			,'weight_class_id'		=> isset($product['weight_class_id']) ? $product['weight_class_id'] : (isset($data['weight_class_id']) ? $data['weight_class_id']: 1)
			,'status'				=> isset($product['status']) ? $product['status'] : (isset($data['status']) ? $data['status']: 1)
			,'sort_order'			=> isset($product['sort_order']) ? $product['sort_order'] : (isset($data['sort_order']) ? $data['sort_order']: 1)
			,'manufacturer_id'		=> isset($product['manufacturer_id']) ? $product['manufacturer_id'] : (isset($data['manufacturer_id']) ? $data['manufacturer_id']: 0)
			,'main_category_id'		=> 0
			,'product_store'		=> array($this->STORE)
			,'product_option'		=> array()
			,'points'				=> isset($product['points']) ? $product['points'] : (isset($data['points']) ? $data['points']: 0)
			,'product_image'		=> isset($product['product_image']) ? $product['product_image'] : (isset($data['product_image']) ? $data['product_image'] : array())
			,'preview'				=> $this->model_tool_image->resize('no_image.jpg', 100, 100)
			,'cost'					=> isset($product['cost']) ? $product['cost'] : (isset($data['cost']) ? $data['cost']: 0)
			,'product_discount'		=> isset($product['product_discount']) ? $product['product_discount'] : (isset($data['product_discount']) ? $data['product_discount']: array())
			,'product_special'		=> isset($product['product_special']) ? $product['product_special'] : (isset($data['product_special']) ? $data['product_special']: array())
			,'product_download'		=> isset($product['product_download']) ? $product['product_download'] : (isset($data['product_download']) ? $data['product_download']: array())
			,'product_related'		=> isset($product['product_related']) ? $product['product_related'] : (isset($data['product_related']) ? $data['product_related']: array())
			,'product_attribute'	=> isset($product['product_attribute']) ? $product['product_attribute'] : (isset($data['product_attribute']) ? $data['product_attribute']: array())
		);

		$result['product_description'] = array(
			$this->LANG => array(
				'name'				=> isset($product['name']) ? $product['name'] : (isset($data['product_description'][$this->LANG]['name']) ? $data['product_description'][$this->LANG]['name']: 'Имя не задано')
				,'seo_h1'			=> isset($product['seo_h1']) ? $product['seo_h1']: (isset($data['product_description'][$this->LANG]['seo_h1']) ? $data['product_description'][$this->LANG]['seo_h1']: '')
				,'meta_title'		=> isset($product['meta_title']) ? $product['meta_title']: (isset($data['product_description'][$this->LANG]['meta_title']) ? $data['product_description'][$this->LANG]['meta_title']: '')
				,'meta_keyword'		=> isset($product['meta_keyword']) ? trim($product['meta_keyword']): (isset($data['product_description'][$this->LANG]['meta_keyword']) ? $data['product_description'][$this->LANG]['meta_keyword']: '')
				,'meta_description' => isset($product['meta_description']) ? trim($product['meta_description']): (isset($data['product_description'][$this->LANG]['meta_description']) ? $data['product_description'][$this->LANG]['meta_description']: '')
				,'description'		=> isset($product['description']) ? nl2br($product['description']): (isset($data['product_description'][$this->LANG]['description']) ? $data['product_description'][$this->LANG]['description']: '')
				,'tag'				=> isset($product['tag']) ? $product['tag']: (isset($data['product_description'][$this->LANG]['tag']) ? $data['product_description'][$this->LANG]['tag']: '')
			),
		);

		// ocshop
		if (version_compare(VERSION, '2.1.0.1.4', '=')) {
			$result['noindex'] = isset($data['noindex']) ? (int)$data['noindex'] : 1;
			$result['product_description'][$this->LANG]['meta_h1'] = isset($product['meta_h1']) ? $product['meta_h1']: (isset($data['product_description'][$this->LANG]['meta_h1']) ? $data['product_description'][$this->LANG]['meta_h1']: '');
			
		}

		if (isset($product['product_option'])) {
			$product['product_option_id'] = '';
			$product['name'] = '';
			if(!empty($product['product_option']) && isset($product['product_option'][0]['type'])){
				$result['product_option'] = $product['product_option'];
				if(!empty($data['product_option'])){
					$result['product_option'][0]['product_option_value'] = array_merge($product['product_option'][0]['product_option_value'],$data['product_option'][0]['product_option_value']);
				}
			}
			else {
				$result['product_option'] = $data['product_option'];
			}
		}
		else {
			$product['product_option'] = array();
		}

		if (isset($product['category_1c_id'])) {
			if (is_object($product['category_1c_id'])) {
				foreach ($product['category_1c_id'] as $category_item) {
					if (isset($this->CATEGORIES[(string)$category_item])) {
						$result['product_category'][] = (int)$this->CATEGORIES[(string)$category_item];
						$result['main_category_id'] = 0;
					}
				}
			} else {
				$product['category_1c_id'] = (string)$product['category_1c_id'];
				if (isset($this->CATEGORIES[$product['category_1c_id']])) {
					$result['product_category'] = array((int)$this->CATEGORIES[$product['category_1c_id']]);
					$result['main_category_id'] = (int)$this->CATEGORIES[$product['category_1c_id']];
				} else {
					$result['product_category'] = isset($data['product_category']) ? $data['product_category'] : array(0);
					$result['main_category_id'] = isset($data['main_category_id']) ? $data['main_category_id'] : 0;
				}
			}
		}

		if (!isset($result['product_category']) && isset($data['product_category'])) {
			$result['product_category'] = $data['product_category'];
		}
		
		if (isset($product['related_options_use'])) {
			$result['related_options_use'] = $product['related_options_use'];
		}
		if (isset($product['related_options_variant_search'])) {
			$result['related_options_variant_search'] = $product['related_options_variant_search'];
		}
		if (isset($product['relatedoptions'])) {
			$result['relatedoptions'] = $product['relatedoptions'];
		}

		return $result;
	}



	/**
	 * Функция работы с продуктом
	 *
	 * @param array
	 */
	private function setProduct($product) {

		if (!$product) return;

		// Проверяем, связан ли 1c_id с product_id
		$this->PRODUCT['product_id'] = $this->getProductIdBy1CProductId($product['1c_id']);

		if ($this->PRODUCT['product_id']) {
			$this->updateProduct($product);
		} else {
			$data = $this->initProduct($product, array());
			
			if ($this->config->get('exchange1c_dont_use_artsync')) {
				$this->load->model('catalog/product');
				$this->PRODUCT['product_id'] =	$this->model_catalog_product->addProduct($data);
			} else {
				// Проверяем, существует ли товар с тем-же артикулом
				// Если есть, то обновляем его
				$this->PRODUCT['product_id'] = $this->getProductBySKU($data['sku']);
				if ($this->PRODUCT['product_id'] !== false) {
					$this->updateProduct($product);
				} else {
					// Если нет, то создаем новый
					$this->load->model('catalog/product');
					$this->PRODUCT['product_id'] = $this->model_catalog_product->addProduct($data);
				}
			}

			// Добавляем линк
			if ($this->PRODUCT['product_id']){
				$this->db->query('INSERT INTO `' .  DB_PREFIX . 'product_to_1c` SET product_id = ' . $this->PRODUCT['product_id'] . ', `1c_id` = "' . $this->db->escape($product['1c_id']) . '"');
			}
		}
		// Устанавливаем SEO URL
		if ($this->PRODUCT['product_id']){
			//только если тип 'translit'
			if ($this->config->get('exchange1c_seo_url') == 2) {
				$this->setSeoURL('product_id', $this->PRODUCT['product_id'], $product['name']);
			}
		}
	}

	/**
	 * Обновляет продукт
	 *
	 * @param array
	 * @param int
	 */
	private function updateProduct($product) {

		// Проверяем что обновлять?
		if ($this->config->get('exchange1c_relatedoptions')) {
			if ($this->PRODUCT['product_id'] == false) {
				$this->setProduct($product);
				return;
			}
		} else {
			if ($this->PRODUCT['product_id'] !== false) {
				$this->PRODUCT['product_id'] = $this->getProductIdBy1CProductId($product['1c_id']);
			}
		}

		// Обновляем описание продукта
		$product_old = $this->getProductWithAllData();

		// Работаем с ценой на разные варианты товаров.
		if(!empty($product['product_option'][0])){
			if(isset($product_old['price']) && (float) $product_old['price'] > 0){

				$price = (float) $product_old['price'] - (float) $product['product_option'][0]['product_option_value'][0]['price'];

				$product['product_option'][0]['product_option_value'][0]['price_prefix'] = ($price > 0) ? '-':'+';
				$product['product_option'][0]['product_option_value'][0]['price'] = abs($price);

				$product['price'] = (float) $product_old['price'];

			}
			else{
				$product['product_option'][0]['product_option_value'][0]['price'] = 0;
			}

		}

		$this->load->model('catalog/product');

		$product_old = $this->initProduct($product, $product_old);

		//Редактируем продукт
		$this->model_catalog_product->editProduct($this->PRODUCT['product_id'], $product_old);

	}

	/**
	 * Устанавливает SEO URL (ЧПУ) для заданного товара
	 *
	 * @param 	inf
	 * @param 	string
	 */
	private function setSeoURL($url_type, $element_id, $element_name) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "url_alias` WHERE `query` = '" . $url_type . "=" . $element_id . "'");
		$this->db->query("INSERT INTO `" . DB_PREFIX . "url_alias` SET `query` = '" . $url_type . "=" . $element_id ."', `keyword`='" . $this->transString($element_name) . "'");
	}

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
	}

	/**
	 * Получает product_id по артикулу
	 *
	 * @param 	string
	 * @return 	int|bool
	 */
	private function getProductBySKU($sku) {

		$query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "product` WHERE `sku` = '" . $this->db->escape($sku) . "'");

        if ($query->num_rows) {
			return $query->row['product_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает 1c_id из product_id
	 *
	 * @param	int
	 * @return	string|bool
	 */
	private function get1CProductIdByProductId($product_id) {
		$query = $this->db->query('SELECT 1c_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `product_id` = ' . $product_id);

		if ($query->num_rows) {
			return $query->row['1c_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает product_id из 1c_id
	 *
	 * @param	string
	 * @return	int|bool
	 */
	private function getProductIdBy1CProductId($product_id) {

		$query = $this->db->query('SELECT product_id FROM ' . DB_PREFIX . 'product_to_1c WHERE `1c_id` = "' . $product_id . '"');

		if ($query->num_rows) {
			return $query->row['product_id'];
		}
		else {
			return false;
		}
	}

	private function getCategoryIdByName($name) {
		$query = $this->db->query("SELECT category_id FROM `" . DB_PREFIX . "category_description` WHERE `name` = '" . $name . "'");
		if ($query->num_rows) {
			return $query->row['category_id'];
		}
		else {
			return false;
		}
	}

	/**
	 * Получает путь к картинке и накладывает водяные знаки
	 *
	 * @param	string
	 * @return	string
	 */
	private function applyWatermark($filename) {
		if (!empty($filename)) {
			$info = pathinfo($filename);
			$wmfile = DIR_IMAGE . $this->config->get('exchange1c_watermark');
			if (is_file($wmfile)) {
				$extension = $info['extension'];
				$minfo = getimagesize($wmfile);
				$image = new Image(DIR_IMAGE . $filename);
				$image->watermark($wmfile);
				$new_image = utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '_watermark.' . $extension;
				$image->save(DIR_IMAGE . $new_image);
				return $new_image;
			}
			else {
				return $filename;
			}
		}
		else {
			return 'no_image.jpg';
		}
	}

	/**
	 * Заполняет продуктами родительские категории
	 */
	public function fillParentsCategories() {
		$this->load->model('catalog/product');
		if (!method_exists($this->model_catalog_product, 'getProductMainCategoryId')) {
			$this->log("  !!!: Заполнение родительскими категориями отменено. Отсутствует main_category_id.");
			return;
		}

		$this->db->query('DELETE FROM `' .DB_PREFIX . 'product_to_category` WHERE `main_category` = 0');
		$query = $this->db->query('SELECT * FROM `' . DB_PREFIX . 'product_to_category` WHERE `main_category` = 1');

		if ($query->num_rows) {
			foreach ($query->rows as $row) {
				$parents = $this->findParentsCategories($row['category_id']);
				foreach ($parents as $parent) {
					if ($row['category_id'] != $parent && $parent != 0) {
						$this->db->query('INSERT INTO `' .DB_PREFIX . 'product_to_category` SET `product_id` = ' . $row['product_id'] . ', `category_id` = ' . $parent . ', `main_category` = 0');
					}
				}
			}
		}
	}

	/**
	 * Ищет все родительские категории
	 *
	 * @param	int
	 * @return	array
	 */
	private function findParentsCategories($category_id) {
		$query = $this->db->query('SELECT * FROM `'.DB_PREFIX.'category` WHERE `category_id` = "'.$category_id.'"');
		if (isset($query->row['parent_id'])) {
			$result = $this->findParentsCategories($query->row['parent_id']);
		}
		$result[] = $category_id;
		return $result;
	}

	/**
	 * Получает language_id из code (ru, en, etc)
	 * Как ни странно, подходящей функции в API не нашлось
	 *
	 * @param	string
	 * @return	int
	 */
	public function getLanguageId($lang) {
		$query = $this->db->query('SELECT `language_id` FROM `' . DB_PREFIX . 'language` WHERE `code` = "'.$lang.'"');
		return $query->row['language_id'];
	}


	/**
	 * Очищает таблицы магазина
	 */
	public function flushDb($params) {

		// Удаляем товары
		if ($params['product']) {
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
				$this->log("Очистка таблиц реляционных опций...");
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_to_char`');
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions`');
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_option`');
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant`');
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_option`');
				$this->db->query('TRUNCATE TABLE `' . DB_PREFIX . 'relatedoptions_variant_product`');
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
		}

		// Очищает таблицы категорий
		if ($params['category']) {
			$this->log("Очистка таблиц категорий...");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category'); 
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_store');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_layout');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_path');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'category_to_1c');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%category_id=%"');
		}

		// Очищает таблицы от всех производителей
		if ($params['manufacturer']) {
			$this->log("Очистка таблиц производителей...");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer');
			$query = $this->db->query("SHOW TABLES FROM " . DB_DATABASE . " LIKE '" . DB_PREFIX . "manufacturer_description'");
			if ($query->num_rows) {
				$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_description');
			}
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'manufacturer_to_store');
			$this->db->query('DELETE FROM ' . DB_PREFIX . 'url_alias WHERE query LIKE "%manufacturer_id=%"');
		}

		// Очищает атрибуты
		if ($params['attribute']) {
			$this->log("Очистка таблиц атрибутов...");
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_description');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_to_1c');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'attribute_group_description');
		}

		// Выставляем кол-во товаров в 0
		if($params['quantity']) {
			$this->log("Очистка остатков...");
			$this->db->query('UPDATE ' . DB_PREFIX . 'product ' . 'SET quantity = 0');
			$this->db->query('TRUNCATE TABLE ' . DB_PREFIX . 'warehouse');
		}
	}

	public function update() {
		$this->load->model('setting/setting');
		// Получим настройки модуля
		$settings = $this->model_setting_setting->getSetting('exchange1c', 0);
		// Установим номер версии
		$settings['exchange1c_version'] = "1.6.1.7";
		// Сохраним настройки
		$this->model_setting_setting->editSetting('exchange1c', $settings);
	}

}
