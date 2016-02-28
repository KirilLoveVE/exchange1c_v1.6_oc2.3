<?php
// Heading
$_['heading_title']							= 'Exchange 1C v8.x';

// Option name
// General
// Table catalog
$_['entry_catalog_1c']						= 'Catalog Name in 1C';
$_['entry_store']							= 'Shop';
$_['entry_action']							= 'Action';
// auth
$_['entry_username']						= 'Login:';
$_['placeholder_username']					= 'Username';
$_['desc_username']							= 'If you do not specify a login, it executes the exchange without checking the login/password, i.e. at any.';
$_['entry_password']						= 'Password:';
$_['placeholder_password']					= 'Enter password';
// security
$_['entry_allow_ip']						= 'Allow IPs (Share a line break. If empty, all addresses are allowed.):';
$_['desc_allow_ip']							= 'To specify an IP address line, if empty is allowed with any.';
$_['entry_status']							= 'Status:';
$_['desc_status']							= 'If the module is disabled, the exchange will be free. 1C is disabled when the module responds with an error message that authorization failed!';
// Other
$_['entry_file_zip']						= 'Format download data';
$_['desc_file_zip']							= 'By default, each file is transmitted separately, that is, the first loaded image, then xml files. When a large number of images loading can be very time-consuming than loading a single file. <br />If the selected format download <strong>ZIP</strong>, the beginning of the exchange of the module reports that 1C wants to take in the ZIP file and the maximum size of an uploaded file, in response 1C creates an archive file one or more if the file size exceeds the maximum allowed for upload to the server.';
$_['entry_full_log']						= 'Enable log';
$_['desc_full_log']							= 'Displays the log entry in detail, only needed for debugging, because consumes more memory and longer running exchange. When this option is disabled in the log will display only error messages.';

// Products
// Table price
$_['entry_config_price_type']				= 'Price type:';
$_['entry_customer_group']					= 'Customer group:';
$_['entry_quantity']						= 'Quantity:';
$_['entry_priority']						= 'Priority:';
$_['text_price_default']					= 'Price for OpenCart';
// button clean
$_['entry_cleaning_db']						= 'Cleaning database';
$_['desc_cleaning_db']						= 'Cleans the products, categories, options, features, producers, balances and rates <strong>all stores</strong>!';
$_['entry_clean_button']					= 'Clean tables';
// Stock
$_['entry_flush_quantity']					= 'Install the balance of goods to 0:';
$_['desc_flush_quantity']					= 'Before sharing the <strong>only downloadable items from the catalog</strong> (file import.xml) will set the balance to zero..';
// Images
// Watermark
$_['entry_apply_watermark']					= 'Apply watermark';
$_['desc_apply_watermark']					= 'When sharing a picture of the product is superimposed on this image. The image should be with transparent background, PNG format recommended';
// update
$_['entry_product_fields_update']			= 'To update the product fields when importing:';
$_['desc_product_fields_update']			= 'If this option is selected it will be updated';
// item
$_['entry_parse_only_types_item']			= 'Parse only types of items:';
$_['desc_parse_only_types_item']			= 'This option specifies which <strong>item types</strong> from 1C will be processed, not to be confused with <strong>item types</strong>. The types of items listed just like in 1C in one line, separated by any character. For example: merchandise, supplies, service';
// parent category
$_['entry_fill_parent_cats']				= 'Fill parent categories:';
$_['desc_fill_parent_cats']					= 'TEMPORARILY NOT WORKING. Communication fills in the item with all the categories which includes the main. For example, the item is in the category <strong>Category 1->Category 2->Category 3</strong>, the General category will be <strong>category 3</strong> and links will be listed the category <strong>Category 1</strong> and <strong>Category 2</strong>. That is, items on the site will be displayed in all three categories.';
// Stosk status
$_['entry_default_stock_status']			= 'Status in the absence of stock:';
$_['desc_default_stock_status']				= 'An experimental option. Sets the status of the goods, if the remainder is zero.';
// disable
$_['entry_product_disable_if_zero'] 		= 'Does not show items on the site if the balance is equal to or less than zero.';
$_['desc_product_disable_if_zero'] 			= 'Suppresses the display of goods in the directory of the site, if in the exchange the goods were unloaded and the rest of it is less than or equal to zero.';
// SKU
$_['entry_dont_use_artsync'] 				= 'Do not search products by sku:';
$_['desc_dont_use_artsync'] 				= 'When you exchange looks for the item internal identifier in 1C, if this is not (the item was not previously uploaded on the website), when this option is disabled, the module will search for a product by part number (SKU), well if you can\'t find neither there nor there will be created a new product.<br /><strong>ATTENTION! When this option is disabled, if empty articles, the module will find the same product at a empty article! So if no items, enable this option.</strong>';
// Product name
$_['entry_product_name_field']				= 'Product name to read from:';
$_['desc_product_name_field']				= 'Downloads in the name of the product from 1C field "Name" or "Name full"';
$_['text_product_name']						= 'Name';
$_['text_product_fullname']					= 'Full name';

// xml_id - id
$_['entry_synchronize_uuid_to_id']			= 'Write the object ID from 1C ID in opencart';
$_['desc_synchronize_uuid_to_id']			= 'THIS VERSION DOES NOT WORK! An experimental option. The module file from the <strong>ID</strong> item and categories trying to record respectively in the <strong>id</strong> item and categories Opencart. For this it is necessary that 1C was recorded <strong>ID code of the goods and categories</strong> without a letter prefix, either will be taken only numbers from the beginning of the field to the first letter. The length of this field <strong>must not be greater than 11 characters</strong>, otherwise this option is ignored. Checking for duplicates is not checked.';

// SEO
$_['text_legend_seo_product']				= 'Products';
$_['text_legend_seo_category']				= 'Catergory';
$_['text_legend_seo_manufacturer']			= 'Manufacturer';
// Head table
$_['label_available_patterns']				= 'Available patterns: ';
$_['label_property_name_from_1c']			= 'The property name for the import template from 1C: ';
// Overwrite
$_['text_seo_overwrite']					= 'Overwrite';
$_['text_seo_if_empty']						= 'If empty field';
$_['text_disable']							= 'Disabled';
$_['text_template']							= 'From template';
$_['text_import']							= 'Import from 1C';
// Product
$_['entry_seo_product_overwrite']			= 'Overwrite:';
$_['entry_seo_product_seo_url']					= 'SEO URL:';
$_['entry_seo_product_seo_url_template']		= 'Template:';
$_['placeholder_seo_product_seo_url_template']	= 'Template. Sample: {prod_id}.html';
$_['entry_seo_product_meta_title']			= 'Meta-tag Title:';
$_['entry_seo_product_meta_description']	= 'Meta-tag Description:';
$_['placeholder_seo_product_meta_description_template']	= 'Template. Sample: Товар {name}';

$_['entry_seo_product_description']			= 'Description:';
$_['entry_seo_product_meta_keyword']		= 'Meta-tag Keyword:';
$_['entry_seo_product_meta_keyword_template']	= 'Template:';
// Category
$_['entry_seo_category_overwrite']			= 'Overwrite:';
$_['entry_seo_category_seo_url_template']	= 'Template:';
$_['entry_seo_category_seo_url']			= 'SEO URL:';
$_['entry_seo_category_meta_title']			= 'Meta-tag Title:';
$_['entry_seo_category_meta_description']	= 'Meta-tag Description:';
$_['entry_seo_category_description']		= 'Description:';
$_['entry_seo_category_meta_keyword']		= 'Meta-tag Keyword:';
// Manufacturer
$_['entry_seo_manufacturer_overwrite']		= 'Overwrite:';
$_['entry_seo_manufacturer_seo_url_template']	= 'Template:';
$_['entry_seo_manufacturer_seo_url']			= 'SEO URL:';


// ORDERS
$_['text_order_status_to_exchange_not'] 	= "- don not use -";
$_['entry_order_status_to_exchange'] 		= 'Orders status to exchange:';
$_['entry_order_status_change']				= 'Uploaded orders status:';
$_['entry_order_status_canceled']			= 'Status of canceled orders:';
$_['entry_order_status_completed']			= 'Status of completed orders:';
$_['entry_order_notify']					= 'Notify users of status change:';
$_['entry_order_currency']					= 'Orders currency: (RUR.)';
$_['placeholder_order_currency'] 			= 'RUR.';



// Buttons
$_['button_upload']							= 'Upload';
$_['button_add']							= 'Add';
$_['button_download_orders']				= 'Download Orders';



// Footer module
$_['text_homepage']							= 'Module homepage';
$_['text_source_code']						= 'Source code in GitHub';
$_['text_change']							= 'Modified and support by: Vitaly E. Kirillov (Skype: KirilLoveVE, ICQ: 39927648, Viber: +79144306000, email: vitaly.kirillove@mail.ru)';















// Text
$_['text_module']							= 'Modules';
$_['text_success']							= 'Settings saved!';

$_['text_tab_store']						= 'Store';
$_['text_tab_general']						= 'General';
$_['text_tab_product']						= 'Products';
$_['text_tab_order']						= 'Orders';
$_['text_tab_manual']						= 'Manual processing';
$_['text_tab_developing']					= 'Developing';
$_['text_tab_seo']							= 'SEO';

$_['text_upload_success']					= 'Import completed';
$_['text_upload_error']						= 'Unknown error';
$_['text_max_filesize']						= 'Max upload size %s MB';

$_['text_empty']							= 'Settings are missing';


// Help

$_['text_image_manager'] 					= 'Image Manager';
$_['text_browse']        					= 'Browse Files';
$_['text_clear']         					= 'Clear Image';

$_['entry_relatedoptions']					= 'Load characteristics as related options (need extension <a href="http://opencartforum.ru/files/file/1501-связанные-опции/">Related Options</a>):';
$_['entry_relatedoptions_help']				= 'Related options settings should be turned on: "Recalc quantity", "Update options", "Use different related options variants" ';

$_['entry_upload']							= 'Select file:';
$_['entry_download_orders']					= 'Download orders.xml';
$_['entry_no_image']						= 'Image if empty:';

// Error
$_['error_permission']						= 'Premission denied!';

// Help
$_['help_allow_ip']							= 'From which IP allowed to access';
$_['help_upload']							= 'Accepted import.xml, offers.xml, orders.xml. <br> file names may be different.';
$_['help_download_orders']					= 'Download selected orders in CML format.';
$_['help_synchronize_uuid_to_id']			= 'ATTENTION! For this option 1C needs ID in upload item code and not the object ID.';

$_['text_legend_store']						= 'Linking Catalog in 1C to Store';
$_['text_legend_auth']						= 'Authorization';
$_['text_legend_security']					= 'Security';
$_['text_legend_other']						= 'Other';
$_['text_legend_price']						= 'Linking Price types in 1C to Customer groups';
$_['text_legend_cleaning_db']				= 'Cleaning database';
$_['text_legend_images']					= 'Images';
$_['text_legend_export_orders']				= 'Export Orders to 1C';
$_['text_legend_import_orders']				= 'Import Orders from 1C';
$_['text_legend_fields_update']				= 'Updating fields when importing';

$_['text_product_field_column']				= 'Product column';
$_['text_product_field_images']				= 'Product images';
$_['text_product_field_sort_order']			= 'Product sort order';
$_['text_product_field_category']			= 'Product category';
$_['text_product_field_name']				= 'Product name';
$_['text_in_developing']					= 'In Developing';


