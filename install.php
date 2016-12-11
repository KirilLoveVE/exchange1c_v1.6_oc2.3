<?php
$this->load->model('user/user_group');
$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'module/exchange1c');
$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'module/exchange1c');
?>