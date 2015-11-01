<?php
$this->load->model('user/user_group');
$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'module/exchange1c');
$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'module/exchange1c');

//$this->load->model('setting/setting');
//if  ($this->config->get('exchange1c_version')) {
//	$settings = $this->model_setting_setting->getSetting('exchange1c');
//	$settings['exchange1c_version'] = '1.6.1.6b';
//} else {
//	$settings = array('exchange1c_version' => '1.6.1.6b');
//}
//$this->model_setting_setting->editSetting('exchange1c', $settings);

?>