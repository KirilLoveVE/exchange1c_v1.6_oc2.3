<?php
class ModelCatalogWarehouse extends Model {

	public function getWarehouse($warehouse_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse` WHERE `warehouse_id` = " . $warehouse_id);
		return $query->row;
	}

	public function getWarehouses() {

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "warehouse`");
		return $query->rows;

	}

}
