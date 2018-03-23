<?php
class ModelCatalogUnit extends Model {
	public function getUnitName($unit_id) {
		$query = $this->db->query("SELECT name FROM `" . DB_PREFIX . "unit_to_1c` WHERE unit_id = " . (int)$unit_id);
		if ($query->num_rows) {
			return $query->row['name'];
		}
		return "unit_id=0";
	}

	public function getUnitByName($unit_name) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "unit_to_1c` WHERE `name` LIKE '" . $this->db->escape($unit_name) . "' LIMIT 1");
		return $query->row;
	}

	public function getProductUnits($product_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "unit_to_1c`");
		return $query->rows;
	}

	public function getProductUnit($unit_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "unit_to_1c` WHERE unit_id = " . (int)$unit_id);
		if ($query->num_rows) {
			return $query->row;
		}

		return false;
	}
}