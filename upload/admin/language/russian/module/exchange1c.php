<?php
// Heading
$_['heading_title']							= 'Обмен данными с 1C v8.x';

// Text
$_['text_module']							= 'Модули';
$_['text_success']							= 'Настройки модуля обновлены!';

$_['text_tab_store']						= 'Магазин';
$_['text_tab_general']						= 'Основное';
$_['text_tab_product']						= 'Обмен товарами';
$_['text_tab_order']						= 'Обмен заказами';
$_['text_tab_manual']						= 'Ручная обработка';
$_['text_tab_developing']					= 'Разработка';

$_['text_upload_success']					= 'Импорт завершен';
$_['text_upload_error']						= 'Что-то пошло не так. Загляните в &laquo;Система &rarr; Журнал ошибок&raquo;';
$_['text_max_filesize']						= 'Загружаемый файл не должен превышать %s Мб';

$_['text_empty']							= 'Настроек пока нет';
$_['text_homepage']							= 'Домашняя страничка модуля';
$_['source_code']       					= 'Исходный код на GitHub';
$_['text_price_default']					= 'Цена на сайте';


// Вкладка "Основное"
$_['entry_username']						= 'Логин:';
$_['entry_password']						= 'Пароль:';
$_['entry_status']							= 'Статус:';
$_['entry_allow_ip']						= 'Разрешенные IP адреса (Разделять переносом строки. Если пусто, разрешены все адреса.):';

// Таблицы
$_['entry_config_price_type']				= 'Тип цены в 1С';
$_['entry_customer_group']					= 'Группа покупателей';
$_['entry_quantity']						= 'Количество';
$_['entry_priority']						= 'Приоритет';
$_['entry_catalog_1c']						= 'Имя каталога в 1С';
$_['entry_store']							= 'Магазин';
$_['entry_action']							= 'Действия';
$_['entry_product_status_disable_if_quantity_zero_help']	= 'Не показывает товар на сайте если остаток равен или меньше нуля.';
$_['entry_parse_only_types_item']			= 'Обрабатывать только типы номенклатуры:';

$_['entry_flush_product']					= 'Сбрасывать товары:';
$_['entry_flush_category']					= 'Сбрасывать категории:';
$_['entry_flush_manufacturer']				= 'Сбрасывать производителей:';
$_['entry_flush_quantity']					= 'Сбрасывать количество товаров:';
$_['entry_flush_attribute']					= 'Сбрасывать атрибуты:';
$_['entry_fill_parent_cats']				= 'Заполнять родительские категории:';
$_['entry_seo_url']							= 'Генерировать SEO URL';
$_['entry_seo_url_translit']				= 'Транслитерация';
$_['entry_product_status_disable_if_quantity_zero']  = 'Отключать товар, если остаток меньше единицы';
$_['entry_synchronize_uuid_to_id']			= 'Запись Ид объекта из 1С в ID opencart';

$_['entry_full_log']						= 'Включить подробный лог загрузки';
$_['entry_apply_watermark']					= 'Накладывать водяные знаки при загрузке';
$_['entry_product_name_or_fullname']		= 'Наименование товара брать из:';

$_['text_image_manager']					= 'Менеджер изображений';
$_['text_browse']							= 'Обзор';
$_['text_clear']							= 'Очистить';
$_['text_source_code']						= 'Исходный код на GitHub';
$_['text_change']							= 'Доработка и поддержка: Кириллов Виталий (Skype: KirilLoveVE, ICQ: 39927648, Viber: +79144306000, email: vitaly.kirillove@mail.ru)';
$_['text_product_name']						= 'Наименование';
$_['text_product_fullname']					= 'Полное наименование';

$_['entry_order_status_to_exchange'] 		= 'Выгружать заказы со статусом:';
$_['entry_order_status_to_exchange_not'] 	= '- не использовать -';
$_['entry_relatedoptions']					= 'Загружать характеристики как связанные опции (требуется модуль <a href="http://opencartforum.ru/files/file/1501-связанные-опции/">Связанные опции</a>):';
$_['entry_relatedoptions_help']				= 'в настройках связанных опций обязательно должна быть включены галки: "Пересчитывать количество", "Обновлять опции", "Использовать различные варианты связанных опций" ';
$_['entry_dont_use_artsync'] 				= 'Не искать товары по артикулам:';

$_['entry_order_status'] 					= 'Статус выгруженых заказов:';
$_['entry_order_status_cancel'] 			= 'Статус отмененных заказов:';
$_['entry_order_status_completed']			= 'Статус выполненных заказов:';
$_['entry_order_notify']					= 'Уведомлять пользователей о смене статуса:';
$_['entry_order_currency'] 					= 'Обозначение валюты (руб.):';
$_['entry_upload']							= 'Выберите файл *.XML для загрузки';
$_['entry_download_orders']					= 'Скачать orders.xml';
$_['entry_product_fields_update']			= 'Обновлять поля товара при импорте:';
$_['entry_default_stock_status']			= 'Статус при отстутствии на складе:';

// Кнопки
$_['button_upload']							= 'Загрузить';
$_['button_add']							= 'Добавить';
$_['button_download_orders']				= 'Скачать заказы';

// Error
$_['error_permission']						= 'У Вас нет прав для управления этим модулем!';

// Справка
$_['help_allow_ip']							= 'С каких IP разрешен доступ';
$_['help_upload']							= 'Принимается import.xml, offers.xml, orders.xml.';
$_['help_download_orders']					= 'Скачивание выбранных заказов в ХМЛ формате.';
$_['help_synchronize_uuid_to_id']			= 'ВНИМАНИЕ! Для работы этой опции 1С должна в Ид выгружать код товара а не Ид объекта.';

$_['text_legend_store']						= 'Связь каталога в 1С с магазином';
$_['text_legend_auth']						= 'Авторизация';
$_['text_legend_security']					= 'Безопасность';
$_['text_legend_other']						= 'Прочее';
$_['text_legend_price']						= 'Связь типов цен (соглашений) в 1С с группами покупателей';
$_['text_legend_cleaning']					= 'Очистка';
$_['text_legend_images']					= 'Картинки';
$_['text_legend_export_orders']				= 'Выгрузка заказов в 1С';
$_['text_legend_import_orders']				= 'Загрузка заказов из 1С';
$_['text_legend_fields_update']				= 'Обновление полей при импорте';

$_['text_product_field_column']				= 'Колонки';
$_['text_product_field_sort_order']			= 'Порядок сортировки';

$_['text_in_developing']					= 'В разработке';
