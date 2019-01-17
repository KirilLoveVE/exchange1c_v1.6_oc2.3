<?php
// Добавление прав на управление модулем
$this->load->model('user/user_group');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/exchange1c');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/exchange1c');

// Проверка на наличие и добавление доп.колонок в таблицу БД
$this->db->query("ALTER TABLE `" . DB_PREFIX . "modification` CHANGE `xml` `xml` MEDIUMTEXT NOT NULL");
$chk = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "modification` WHERE `field` = 'date_modified'");
if (!$chk->num_rows) {
    $this->db->query("ALTER TABLE `" . DB_PREFIX . "modification` ADD COLUMN  `date_modified` datetime NOT NULL");
    $this->db->query("UPDATE `" . DB_PREFIX . "modification` SET `date_modified` = `date_added` WHERE `date_modified` = '0000-00-00 00:00:00'");
}
$this->log->write(DIR_UPLOAD . $this->request->post['path']);
$dir = DIR_UPLOAD . $this->request->post['path'];
$files = scandir($dir);
$this->load->model('extension/modification');
foreach ($files as $file) {
	if (strpos($file, ".xml") && $file != 'install.xml') {
		$this->log->write($file);
		$xml = file_get_contents($dir . '/' .$file);
			if ($xml) {
				try {
					$dom = new DOMDocument('1.0', 'UTF-8');
					$dom->loadXml($xml);

					$name = $dom->getElementsByTagName('name')->item(0);

					if ($name) {
						$name = $name->nodeValue;
					} else {
						$name = '';
					}

					$code = $dom->getElementsByTagName('code')->item(0);

					if ($code) {
						$code = $code->nodeValue;

						// Check to see if the modification is already installed or not.
						$modification_info = $this->model_extension_modification->getModificationByCode($code);

						if ($modification_info) {
							$json['error'] = sprintf($this->language->get('error_exists'), $modification_info['name']);
						}
					} else {
						$json['error'] = $this->language->get('error_code');
					}

					$author = $dom->getElementsByTagName('author')->item(0);

					if ($author) {
						$author = $author->nodeValue;
					} else {
						$author = '';
					}

					$version = $dom->getElementsByTagName('version')->item(0);

					if ($version) {
						$version = $version->nodeValue;
					} else {
						$version = '';
					}

					$link = $dom->getElementsByTagName('link')->item(0);

					if ($link) {
						$link = $link->nodeValue;
					} else {
						$link = '';
					}

					$modification_data = array(
						'name'    => $name,
						'code'    => $code,
						'author'  => $author,
						'version' => $version,
						'link'    => $link,
						'xml'     => $xml,
						'status'  => 1
					);

					if (!$json) {
						$this->model_extension_modification->addModification($modification_data);
					}
				} catch(Exception $exception) {
					$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
				}
		}
	}
}
?>
