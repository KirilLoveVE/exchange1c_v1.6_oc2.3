<?php

// Configuration
require_once('../admin/config.php');

require_once(DIR_SYSTEM . 'startup.php');
require_once(DIR_SYSTEM . 'library/currency.php');
require_once(DIR_SYSTEM . 'library/user.php');
require_once(DIR_SYSTEM . 'library/weight.php');
require_once(DIR_SYSTEM . 'library/length.php');

// Registry
$registry = new Registry();

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Config
$config = new Config();
$registry->set('config', $config);

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
$registry->set('db', $db);

// Store
if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
	$store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`ssl`, 'www.', '') = '" . $db->escape('https://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
} else {
	$store_query = $db->query("SELECT * FROM " . DB_PREFIX . "store WHERE REPLACE(`url`, 'www.', '') = '" . $db->escape('http://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/.\\') . '/') . "'");
}

if ($store_query->num_rows) {
	$config->set('config_store_id', $store_query->row['store_id']);
} else {
	$config->set('config_store_id', 0);
}

// Settings
$query = $db->query("SELECT * FROM `" . DB_PREFIX . "setting` WHERE store_id = '0' OR store_id = '" . (int)$config->get('config_store_id') . "' ORDER BY store_id ASC");

foreach ($query->rows as $result) {
	if (!$result['serialized']) {
		$config->set($result['key'], $result['value']);
	} else {
		$config->set($result['key'], json_decode($result['value'], true));
	}
}

if (!$store_query->num_rows) {
	$config->set('config_url', HTTP_SERVER);
	$config->set('config_ssl', HTTPS_SERVER);
}

// Log 
$log = new Log($config->get('config_error_filename'));
$registry->set('log', $log);

// Error Handler
function error_handler($errno, $errstr, $errfile, $errline) {
	global $config, $log;

	if (0 === error_reporting()) return TRUE;
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			break;
		default:
			$error = 'Unknown';
			break;
	}

	if ($config->get('config_error_display')) {
		echo '<b>' . $error . '</b>: ' . $errstr . ' in <b>' . $errfile . '</b> on line <b>' . $errline . '</b>';
	}
	
	if ($config->get('config_error_log')) {
		$log->write('PHP ' . $error . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
	}

	return TRUE;
}

// Error Handler
set_error_handler('error_handler');

// Request
$request = new Request();
$registry->set('request', $request);

// Response
$response = new Response();
$response->addHeader('Content-Type: text/html; charset=utf-8');
$registry->set('response', $response); 

// Session
$registry->set('session', new Session());

// Cache
$registry->set('cache', new Cache('file'));

// Document
$registry->set('document', new Document());

// Language
$languages = array();

$query = $db->query("SELECT * FROM " . DB_PREFIX . "language"); 

foreach ($query->rows as $result) {
	$languages[$result['code']] = array(
		'language_id'	=> $result['language_id'],
		'name'		=> $result['name'],
		'code'		=> $result['code'],
		'locale'	=> $result['locale'],
		'directory'	=> $result['directory']
	);
}

$config->set('config_language_id', $languages[$config->get('config_admin_language')]['language_id']);

// Language
$language = new Language($languages[$config->get('config_admin_language')]['directory']);
$language->load($languages[$config->get('config_admin_language')]['directory']);
$registry->set('language', $language);

// Currency
$registry->set('currency', new Currency($registry));

// Weight
$registry->set('weight', new Weight($registry));

// Length
$registry->set('length', new Length($registry));

// User
$registry->set('user', new User($registry));

//OpenBay Pro
$registry->set('openbay', new Openbay($registry));

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Front Controller
$controller = new Front($registry);

// Принудительное использование HTTP авторизации, если она отключена на сервере
if (!isset($_SERVER['PHP_AUTH_USER'])) {
	$remote_user = $_SERVER["REMOTE_USER"] ? $_SERVER["REMOTE_USER"]: $_SERVER["REDIRECT_REMOTE_USER"];
	$strTmp = base64_decode(substr($remote_user,6));
	if($strTmp) list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', $strTmp);
	$log->write('Включен режим принудительной авторизации в beta версии!');
}

// Информация используется для поиска и отладки возможных ошибок в beta версиях
$sapi = php_sapi_name();
if ($sapi=='cli') 
	$log->write('Запуск веб сервера из командной строки');
elseif (substr($sapi,0,3)=='cgi') 
	$log->write('Запуск веб сервера в режиме CGI');
elseif (substr($sapi,0,6)=='apache') 
	$log->write('Запуск веб сервера в режиме модуля Apache');
else 
	$log->write('Запуск веб сервера в режиме модуля сервера '.$sapi);

// Router
if (isset($request->get['mode']) && $request->get['type'] == 'catalog') {

	switch ($request->get['mode']) {
		case 'checkauth':
			$action = new Action('module/exchange1c/modeCheckauth');
		break;
		
		case 'init':
			$action = new Action('module/exchange1c/modeCatalogInit');
		break;

		case 'file':
			$action = new Action('module/exchange1c/modeFile');
		break;

		case 'import':
			$action = new Action('module/exchange1c/modeImport');
		break;

		default:
			echo "success\n";
	}

} else if (isset($request->get['mode']) && $request->get['type'] == 'sale') {
	
	switch ($request->get['mode']) {
		case 'checkauth':
			$action = new Action('module/exchange1c/modeCheckauth');
		break;
		
		case 'init':
			$action = new Action('module/exchange1c/modeSaleInit');
		break;

		case 'query':
			$action = new Action('module/exchange1c/modeQueryOrders');
		break;

		case 'file':
			$action = new Action('module/exchange1c/modeFile');
		break;

		case 'import':
			$action = new Action('module/exchange1c/modeImport');
		break;

		case 'success':
			$action = new Action('module/exchange1c/modeOrdersChangeStatus');
		break;

		default:
			echo "success\n";

	}

} else if (isset($request->get['mode']) && $request->get['type'] == 'get_catalog') {
	
	switch ($request->get['mode']) {
		case 'init':
			$action = new Action('module/exchange1c/modeInitGetCatalog');
		break;

		case 'query':
			$action = new Action('module/exchange1c/modeQueryGetCatalog');
		break;


		default:
			echo "success\n";
			$log->write('type=get_catalog -> success');

	}


} else {
	echo "success\n";
	exit;
}

// Dispatch
if (isset($action)) {
	$controller->dispatch($action, new Action('error/not_found'));
}

// Output
$response->output();
?>
