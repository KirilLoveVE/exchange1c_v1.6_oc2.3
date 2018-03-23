<?php
class ModelCatalogExchange1c extends Model {

	/**
	 * ver 1
	 * update 2017-12-28
	 * Формирует варианты значений для связанных опций
	 */
	public function getProductFeatures($product_id, $customer_group_id, $currency_value, $tax_class_id) {
		$option_values = array();
		$query_features = $this->db->query("SELECT `pfv`.`product_feature_id`, `pov`.`product_option_id`, `pfv`.`product_option_value_id` FROM `" . DB_PREFIX . "product_feature_value` `pfv` LEFT JOIN `" . DB_PREFIX . "product_option_value` `pov` ON (`pfv`.`product_option_value_id` = `pov`.`product_option_value_id`) LEFT JOIN `" . DB_PREFIX . "option` `o` ON (`pov`.`option_id` = `o`.`option_id`) WHERE `pfv`.`product_id` = " . (int)$product_id . " ORDER BY `o`.`sort_order`");

		foreach ($query_features->rows as $feature_value) {
			//$this->log->write("1");
			//$this->log->write($feature_value);
			if (empty($option_values[$feature_value['product_option_value_id']])) {
				$option_values[$feature_value['product_option_value_id']] = array();
			}
      		foreach ($query_features->rows as $feature_value1) {
      			//$this->log->write("2");
      			//$this->log->write($feature_value1);
				if ($feature_value1['product_feature_id'] == $feature_value['product_feature_id'] && $feature_value1['product_option_value_id'] <> $feature_value['product_option_value_id']) {
					$option_values[$feature_value['product_option_value_id']][] = $feature_value1['product_option_value_id'];
				}
			}
		}

		$query = $this->db->query("SELECT `product_feature_id`, `price` FROM `" . DB_PREFIX . "product_price` WHERE `product_id` = " . (int)$product_id . " AND `customer_group_id` = " . (int)$customer_group_id);

		$features_price = array();
		$features_options = array();
		$features_options_values = array();

		foreach ($query->rows as $query_price) {
			$features_price[$query_price['product_feature_id']] = array(
				'value' 	=> $query_price['price'] * $currency_value,
				'tax'		=> $this->tax->calculate($query_price['price'], $tax_class_id, $this->config->get('config_tax')) * $currency_value
			);
			foreach ($query_features->rows as $feature) {
				if ($feature['product_feature_id'] == $query_price['product_feature_id']) {
					if (!isset($features_options[$feature['product_feature_id']])) {
						$features_options[$feature['product_feature_id']] = array();
					}
					$features_options[$feature['product_feature_id']][$feature['product_option_id']] = $feature['product_option_value_id'];
					$features_options_values[$feature['product_option_value_id']] = $feature['product_feature_id'];
				}
			}
		}
		return array(
			'option_values'				=> $option_values,
			'features_price'			=> $features_price,
			'features_options'			=> $features_options,
			'features_options_values'	=> $features_options_values
		);
	}


	/**
	 * ver 1
	 * update 2017-12-28
	 * Считывает цены характеристик
	 */
	public function getProductQuantity($product_id, $customer_group_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_quantity` WHERE `product_id` = " . (int)$this->request->get['product_id']);
		$quantity_total = 0;
		$product_quantity = array();
		if ($query->num_rows) {
			foreach ($query->rows as $query_quantity) {

				if (!isset($product_quantity[$query_quantity['product_feature_id']])) {
					$product_quantity[$query_quantity['product_feature_id']] = array();
				}
				$quantity = &$product_quantity[$query_quantity['product_feature_id']];

				if (!isset($quantity[$query_quantity['warehouse_id']])) {
					$quantity[$query_quantity['warehouse_id']] = $query_quantity['quantity'];
					$quantity_total += $query_quantity['quantity'];
				}

			} // foreach
		}
		return array (
			'quantity'	=> $product_quantity,
			'total'		=> $quantity_total
		);
	}
}
?>