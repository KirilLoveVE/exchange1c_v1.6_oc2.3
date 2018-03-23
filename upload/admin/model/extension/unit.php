<?php

class ModelExtensionUnit extends Model {

	/**
	 * ver 1
	 * update 2017-06-12
	 * Устанавливает классификатор единиц измерений
	 */
	public function installUnit() {

		// Единицы измерения товара (упаковки товара)
		// Если используются упаковки, то в эту таблицу записываются дополнительные единицы измерения
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");
		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_unit` (
				`product_unit_id`			INT(11) 		NOT NULL AUTO_INCREMENT	COMMENT 'Счетчик',
				`product_id` 				INT(11) 		NOT NULL 				COMMENT 'ID товара',
				`product_feature_id` 		INT(11) 		NOT NULL DEFAULT 0		COMMENT 'ID характеристики товара',
				`unit_id` 					INT(11) 		NOT NULL DEFAULT 0		COMMENT 'ID единицы измерения',
				`ratio` 					INT(9) 			NOT NULL DEFAULT 1 		COMMENT 'Коэффициент пересчета количества',
				PRIMARY KEY (`product_unit_id`),
				UNIQUE KEY `product_unit_key` (`product_id`, `product_feature_id`, `unit_id`),
				FOREIGN KEY (`product_id`) 				REFERENCES `" . DB_PREFIX . "product`(`product_id`),
				FOREIGN KEY (`product_feature_id`) 		REFERENCES `" . DB_PREFIX . "product_feature`(`product_feature_id`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `" . DB_PREFIX . "unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Привязка единиц измерения к торговой системе
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
		$this->db->query(
			"CREATE TABLE `" . DB_PREFIX . "unit_to_1c` (
				`unit_id` 					SMALLINT(6) 	NOT NULL AUTO_INCREMENT COMMENT 'ID единицы измерения по каталогу',
				`guid` 						VARCHAR(64) 	NOT NULL 				COMMENT 'Ид единицы измерения',
				`name` 						VARCHAR(16) 	NOT NULL 				COMMENT 'Наименование краткое',
				`code` 						VARCHAR(5) 		NOT NULL 				COMMENT 'Код числовой',
				`full_name` 				VARCHAR(50) 	NOT NULL 				COMMENT 'Наименование полное',
				`version` 					VARCHAR(32) 	NOT NULL				COMMENT 'Версия объекта',
				UNIQUE KEY `unit_link` (`unit_id`, `guid`, `name`),
				INDEX `unit_name` (`name`),
				INDEX `unit_guid` (`guid`),
				FOREIGN KEY (`unit_id`) 				REFERENCES `". DB_PREFIX ."unit`(`unit_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8"
		);

		// Изменения в корзине
		$result = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "cart` WHERE `field` = 'unit_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` ADD  `unit_id` INT( 11 ) NOT NULL DEFAULT 0 AFTER  `option`");
		}
		$result = $this->db->query("SHOW INDEX FROM `" . DB_PREFIX . "cart` WHERE `key_name` = 'cart_id'");
		if (!$result->num_rows) {
			$this->db->query("ALTER TABLE  `" . DB_PREFIX . "cart` DROP INDEX  `cart_id` ,	ADD INDEX  `cart_id` (  `customer_id` ,  `session_id` ,  `product_id` ,  `recurring_id` ,  `product_feature_id` , `unit_id`)");
		}

	} // installUnit()

    public function uninstallUnit() {

		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "unit_to_1c`");
  		$query = $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_unit`");

   	} // uninstallUnit()
}
?>