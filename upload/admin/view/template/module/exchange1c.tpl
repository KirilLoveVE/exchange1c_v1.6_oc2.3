<?php echo $header; ?><?php echo $column_left; ?>

<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right">
				<button type="submit" form="form-1c" data-toggle="tooltip" title="<?php echo $lang['button_save']; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
				<a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $lang['button_cancel']; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
			</div>
			<h1><?php echo $lang['heading_title']; ?></h1>
			<ul class="breadcrumb">
				<?php foreach ($breadcrumbs as $breadcrumb) { ?>
					<li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
	<div class="container-fluid">
		<?php if ($error_warning) { ?>
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
				<button type="button" class="close" data-dismiss="alert">&times;</button>
			</div>
		<?php } ?>
		<?php if ($update) { ?>
			<div class="alert alert-info">
				<i class="fa fa-info-circle"></i>
				<?php echo $update; ?>
			</div>
		<?php } ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-pencil"></i> Редактирование настроек обмена</h3>
			</div>
			<div class="panel-body">
				<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-exchange1c" class="form-horizontal">
				<ul class="nav nav-tabs">
					<li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $lang['text_tab_general']; ?></a></li>
					<li><a href="#tab-product" data-toggle="tab"><?php echo $lang['text_tab_product']; ?></a></li>
					<li><a href="#tab-seo" data-toggle="tab"><?php echo $lang['text_tab_seo']; ?></a></li>
					<li><a href="#tab-order" data-toggle="tab"><?php echo $lang['text_tab_order']; ?></a></li>
					<li><a href="#tab-manual" data-toggle="tab"><?php echo $lang['text_tab_manual']; ?></a></li>
					<li><a href="#tab-developing" data-toggle="tab"><?php echo $lang['text_tab_developing']; ?></a></li>
				</ul>
				<div class="tab-content">
					<div class="tab-pane active" id="tab-general">
						<legend><?php echo $lang['legend_stores']; ?></legend>
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i>
							<?php echo $lang['desc_stores']; ?>
						</div>
						<div class="table-responsive">
							<table id="exchange1c_store_id" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="text-left"><?php echo $lang['entry_catalog_1c']; ?></td>
										<td class="text-left"><?php echo $lang['entry_store']; ?></td>
										<td class="text-right"><?php echo $lang['entry_action']; ?></td>
									</tr>
								</thead>
								<tbody>
									<?php $store_row = 0 ?>
									<?php foreach ($exchange1c_stores as $config_store) { ?>
										<?php if ($config_store['store_id'] == 0) {?>
											<tr id="exchange1c_store_row<?php echo $store_row ?>">
												<td class="text-left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][name]" value="<?php echo $config_store['name']; ?>" class="form-control"/></td>
												<td class="text-left"><?php echo $stores[$config_store['store_id']] ?><input type="hidden" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][store_id]" value="0" /></td>
												<td class="text-left">&nbsp;</td>
											</tr>
										<?php } else { ?>
											<tr id="exchange1c_store_row<?php echo $store_row ?>">
												<td class="text-left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][name]" value="<?php echo $config_store['name']; ?>" /></td>
												<td class="text-left"><select class="form-control" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][store_id]">
												<?php foreach ($stores as $store_id => $name) { ?>
													<?php if ($store_id == $config_store['store_id']) { ?>
														<option value="<?php echo $store_id ?>" selected="selected"><?php echo $name ?></option>
													<?php } else { ?>
														<option value="<?php echo $store_id ?>"><?php echo $name ?></option>
													<?php } ?>
												<?php } ?>
												</select></td>
												<td class="text-center">
												<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_store_row<?php echo $config_store['store_id'] ?>').remove() : false;"><i class="fa fa-trash-o"></i></button>
												</td>
											</tr>
										<?php } // if ?>
										<?php $store_row++ ?>
									<?php } // foreach ?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="2"></td>
										<td class="text-center">
											<a onclick="addStore();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
										</td>
									</tr>
								</tfoot>
							</table>
						</div> <!-- table -->
						<legend><?php echo $lang['legend_auth']; ?></legend>
						<div class="form-group">
							<?php echo $html_username; ?>
							<?php echo $html_password; ?>
							<?php echo $html_status; ?>
						</div>
						<legend><?php echo $lang['legend_security']; ?></legend>
						<div class="form-group">
							<?php echo $html_allow_ip; ?>
						</div>
						<legend><?php echo $lang['legend_other']; ?></legend>
						<div class="form-group">
							<?php echo $html_cleaning_db ?>
						</div>
						<div class="form-group">
							<?php echo $html_file_exchange; ?>
							<?php echo $html_log_level; ?>
							<?php echo $html_flush_log; ?>
						</div>
					</div><!-- tab general -->                                 m 
					<div class="tab-pane" id="tab-product">
						<legend><?php echo $lang['legend_prices']; ?></legend>
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i>
							<?php echo $lang['desc_prices']; ?>
						</div>
						<div class="table-responsive">
							<table id="exchange1c_price_type_id" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="text-left"><?php echo $lang['entry_config_price_type']; ?></td>
										<td class="text-left"><?php echo $lang['entry_customer_group']; ?></td>
										<td class="text-right"><?php echo $lang['entry_quantity']; ?></td>
										<td class="text-right"><?php echo $lang['entry_priority']; ?></td>
										<td class="text-right"><?php echo $lang['entry_action']; ?></td>
									</tr>
								</thead>
								<tbody>
									<?php $price_row = 0; ?>
									<?php foreach ($exchange1c_price_type as $obj) { ?>
										<?php if ($price_row == 0) {?>
											<tr id="exchange1c_price_type_row<?php echo $price_row; ?>">
												<td class="text-left"><input type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" class="form-control"/></td>
												<td class="text-left"><?php echo $lang['text_price_default']; ?><input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]" value="<?php echo $obj['customer_group_id']; ?>" /></td>
												<td class="text-center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="1" /></td>
												<td class="text-center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="0" /></td>
												<td class="text-left">&nbsp;</td>
											</tr>
										<?php } else { ?>
											<tr id="exchange1c_price_type_row<?php echo $price_row; ?>">
												<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" /></td>
												<td class="text-left"><select class="form-control" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]">
											<?php foreach ($customer_groups as $customer_group) { ?>
													<?php if ($customer_group['customer_group_id'] == $obj['customer_group_id']) { ?>
														<option value="<?php echo $customer_group['customer_group_id']; ?>" selected="selected"><?php echo $customer_group['name']; ?></option>
													<?php } else { ?>
														<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>
													<?php } ?>
												<?php } ?>
												</select></td>
												<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="<?php echo $obj['quantity']; ?>" size="2" /></td>
												<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="<?php echo $obj['priority']; ?>" size="2" /></td>
												<td class="text-center">
												<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_price_type_row<?php echo $price_row; ?>').remove() : false;"><i class="fa fa-trash-o"></i></button>
												</td>
											</tr>
										<?php } ?>
										<?php $price_row++; ?>
									<?php } ?>
								</tbody>
								<tfoot>
									<?php if (count($customer_groups)) { ?>
									<tr>
										<td colspan="4"></td>
										<td class="text-center">
											<a onclick="addConfigPriceType();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
										</td>
									</tr>
									<?php } ?>
								</tfoot>
							</table>
						</div> <!-- table -->
						<legend><?php echo $lang['legend_new_product_and_cats']; ?></legend>
						<div class="form-group">
							<?php echo $html_new_product_status_off ?>
							<?php echo $html_new_category_status_off ?>
							<?php echo $html_synchronize_new_product_by ?>
						</div>
						<legend><?php echo $lang['legend_import']; ?></legend>
						<div class="form-group">
							<?php echo $html_import_product_name ?>
							<?php echo $html_import_product_description ?>
							<?php echo $html_import_categories ?>
							<?php echo $html_import_product_manufacturer ?>
							<?php echo $html_import_images ?>
							<?php echo $html_parse_only_types_item ?>
						</div>
						<legend><?php echo $lang['legend_product_options']; ?></legend>
						<div class="form-group">
							<?php echo $html_product_options_mode ?>
							<?php echo $html_product_options_name ?>
							<?php echo $html_product_options_type ?>
							<?php echo $html_product_options_subtract ?>
						</div>
						<legend><?php echo $lang['legend_quantity']; ?></legend>
						<div class="form-group">
							<?php echo $html_default_stock_status; ?>
							<?php echo $html_product_disable_if_zero ?>
							<?php echo $html_flush_quantity ?>
						</div>
						<legend><?php echo $lang['legend_other']; ?></legend>
						<div class="form-group">
							<?php echo $html_description_html ?>
						</div>
						<div class="form-group">
							<?php echo $html_fill_parent_cats ?>
						</div>
						<div class="form-group">
							<?php echo $html_synchronize_uuid_to_id ?>
						</div>
						<div class="form-group">
							<?php echo $html_currency_convert ?>
						</div>
						<div class="form-group">
							<?php echo $html_watermark ?>
						</div>
					</div><!-- tab product -->
					<div class="tab-pane" id="tab-seo">
						<!-- SEO товар -->
						<legend><?php echo $lang['legend_seo_product']; ?></legend>
						<div class="form-group">
							<?php echo $html_seo_product_overwrite; ?>
							<label class="col-sm-12 text-left">
								<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_product_tags; ?>
								<input type="hidden" name="exchange1c_seo_product_tags" value="<?php echo $exchange1c_seo_product_tags; ?>"/>
							</label>
						</div>
						<div class="form-group">
							<?php echo $html_seo_product_seo_url; ?>
							<?php echo $html_seo_product_seo_url_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_product_meta_title; ?>
							<?php echo $html_seo_product_meta_title_template; ?>
						</div>
						<?php if(isset($html_seo_product_meta_h1)) { ?>
						<div class="form-group">
							<?php echo $html_seo_product_meta_h1 ?>
							<?php echo $html_seo_product_meta_h1_template ?>
						</div>
						<?php } ?>
						<div class="form-group">
							<?php echo $html_seo_product_meta_description; ?>
							<?php echo $html_seo_product_meta_description_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_product_meta_keyword; ?>
							<?php echo $html_seo_product_meta_keyword_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_product_tag; ?>
							<?php echo $html_seo_product_tag_template; ?>
						</div>
						<!-- SEO категория -->
						<legend><?php echo $lang['legend_seo_category']; ?></legend>
						<div class="form-group">
							<?php echo $html_seo_category_overwrite; ?>
							<label class="col-sm-12 text-left">
								<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_category_tags; ?>
							</label>
							<input type="hidden" name="exchange1c_seo_category_tags" value="<?php echo $exchange1c_seo_category_tags; ?>"/>
						</div>
						<div class="form-group">
							<?php echo $html_seo_category_seo_url; ?>
							<?php echo $html_seo_category_seo_url_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_category_meta_title; ?>
							<?php echo $html_seo_category_meta_title_template; ?>
						</div>
						<?php if(isset($html_seo_category_meta_h1)) { ?>
						<div class="form-group">
							<?php echo $html_seo_category_meta_h1 ?>
							<?php echo $html_seo_category_meta_h1_template ?>
						</div>
						<?php } ?>
						<div class="form-group">
							<?php echo $html_seo_category_meta_description; ?>
							<?php echo $html_seo_category_meta_description_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_category_meta_keyword; ?>
							<?php echo $html_seo_category_meta_keyword_template; ?>
						</div>
						<!-- SEO Производителя -->
						<legend><?php echo $lang['legend_seo_manufacturer']; ?></legend>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_overwrite; ?>
							<label class="col-sm-12 text-left">
								<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_manufacturer_tags; ?>
							</label>
							<input type="hidden" name="exchange1c_seo_manufacturer_tags" value="<?php echo $exchange1c_seo_manufacturer_tags; ?>"/>
						</div>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_seo_url; ?>
							<?php echo $html_seo_manufacturer_seo_url_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_meta_title; ?>
							<?php echo $html_seo_manufacturer_meta_title_template; ?>
						</div>
						<?php if(isset($html_seo_manufacturer_meta_h1)) { ?>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_meta_h1 ?>
							<?php echo $html_seo_manufacturer_meta_h1_template ?>
						</div>
						<?php } ?>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_meta_description; ?>
							<?php echo $html_seo_manufacturer_meta_description_template; ?>
						</div>
						<div class="form-group">
							<?php echo $html_seo_manufacturer_meta_keyword; ?>
							<?php echo $html_seo_manufacturer_meta_keyword_template; ?>
						</div>
					</div><!-- tab-seo -->
					<div class="tab-pane" id="tab-order">
						<legend><?php echo $lang['legend_export_orders']; ?></legend>
						<?php echo $html_order_status_to_exchange; ?>
						<?php echo $html_order_status_change; ?>
						<?php echo $html_order_status_canceled; ?>
						<?php echo $html_order_status_completed; ?>
						<?php echo $html_order_currency; ?>
						<?php echo $html_order_notify; ?>
						<legend><?php echo $lang['legend_import_orders']; ?></legend>
						<h2><?php echo $lang['text_in_developing']; ?></h2>
					</div><!-- tab-order -->
					<div class="tab-pane" id="tab-manual">
						<div class="form-group">
							<label class="col-sm-2 control-label" for="button-upload">
								<span title="" data-original-title="<?php echo $lang['help_upload']; ?>" data-toggle="tooltip"><?php echo $lang['entry_upload']; ?></span>
							</label>
							<button id="exchange1c-button-upload" class="col-sm-2 btn btn-primary" type="button" data-loading-text="<?php echo $lang['button_upload']; ?>">
								<i class="fa fa-upload"></i>
								<?php echo $lang['button_upload']; ?>
							</button>
							<label class="col-sm-3 control-label">Upload max file size : <?php echo $upload_max_filesize; ?><br />Maximum size of POST data : <?php echo $post_max_size; ?></label>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="button-download-orders">
								<span title="" data-original-title="<?php echo $lang['help_download_orders']; ?>" data-toggle="tooltip"><?php echo $lang['entry_download_orders']; ?></span>
							</label>
							<button id="button-download-orders" class="col-sm-2 btn btn-primary" type="button" data-loading-text="<?php echo $lang['button_download_orders']; ?>">
								<i class="fa fa-download"></i>
								<?php echo $lang['button_download_orders']; ?>
							</button>
						</div>
					</div><!-- tab-manual -->
					<div class="tab-pane" id="tab-developing">
						<div class="col-sm-12">
							<legend>Изменения в версии 1.6.1.7 от 17.11.2015:</legend>
							<ul>
								<li>Загрузка каталогов из 1С в указанный магазин в настройках (Система -> Настройки).</li>
								<li>Временно убраны "Связанные опции".</li>
							</ul>
							<legend>Изменения в версии 1.6.1.8 от 17.11.2015:</legend>
							<ul>
								<li>Исправление ошибок с загрузкой цен.</li>
								<li>В режиме доработки загрузка каталога с 1С в разные магазины.</li>
								<li>В режиме доработки загрузка опций.</li>
								<li>Добавлена опция - отключение товаров, если количество меньше или равно нулю. То есть на сайте эти товары не будут отображаться, т.к. статус этих товаров будет в режиме "Отключено".</li>
								<li>
									<p>Загрузка свойств из import.xml:</p>
									<ul>
										<li>Производитель</li>
										<li>oc.seo_h1</li>
										<li>oc.seo_title</li>
										<li>oc.sort_order</li>
									</ul>
								</li>
								<li>
									<p>Загрузка реквизитов из import.xml:</p>
									<ul>
										<li>ОписаниеФайла - не реализовано</li>
										<li>Вес [height]</li>
										<li>ТипНоменклатуры [item_type] - загружается только Товар.</li>
										<li>ВидНоменклатуры [item_view] - не реализовано.</li>
										<li>ОписаниеВФорматеHTML [description].</li>
										<li>Полное наименование [meta_description],[name].</li>
									</ul>
								</li>
							</ul>
							<legend>Изменения в версии 1.6.1.9 от 26.11.2015:</legend>
							<ul>
								<li>Исправлены ошибки</li>
								<li>Включено вывод в лог информации о попытке и способе авторизации из программ.</li>
							</ul>
							<legend>Изменения в версии 1.6.1.10 от 28.11.2015:</legend>
							<ul>
								<li>Исправлены ошибки при загрузке предложений.</li>
							</ul>
							<legend>Изменения в версии 1.6.1.11 от 03.12.2015:</legend>
							<ul>
								<li>Доработана опция "Заполнение родительскими категориями", теперь работает на любых CMS.</li>
								<li>Добавлена опция "Обрабатывать только указанные типы номенклатуры", если поле оставить пустым, будет грузить все подряд.</li>
								<li>Добавлена опция "Запись Ид из 1С товаров и категорий в id". Корректно продолжает работать и при отключении ее, т.к. в связи с 1С Ид также записываются. Ид должно быть числовым максимальной длиной 11 символов, если Ид окажется неверным значением, то загрузка пройдет как будто опция отключена.</li>
								<li>Исправлена ошибка при выгрузке заказов. Временно не работает смена статуса заказа при выгрузке, будет работать в следующей версии.</li>
							</ul>
							<legend>Изменения в версии 1.6.1.12 от 06.12.2015:</legend>
							<ul>
								<li>Добавлены события, при удалении товара или категории из админки, которые удаляют также связи с 1С и картинки товара с диска.</li>
							</ul>
							<legend>Изменения в версии 1.6.1.13 от 10.12.2015:</legend>
							<ul>
								<li>Добавлена опция выбора записи наименования товара из 1С "Наименование полное" или "Наименование".</li>
								<li>Доработана функция смены статуса заказа при выгрузке заказа в 1С.</li>
								<li>Исправлена ошибка с обновлением товара.</li>
								<li>При удалении товаров и категорий из админки удаляются и связи с 1С.</li>
								<li>Если не заполнять типы цен, по умолчанию в основную загрузит только первую цену.</li>
								<li>Добавлен список полей, в котором можно указывать какие поля товара будут</li>
							</ul>
							<legend>Изменения в версии 1.6.2.0 от 08.02.2016 (beta версии описывать подробно не буду):</legend>
							<ul>
								<li>Перелопачен по-новой весь код модуля.</li>
								<li>Оптимизирована загрузка памяти, контроль за расходом памяти. В бета версии будет выводить информацию в лог.</li>
								<li>Добавлены описания опций.</li>
								<li>Добавлена поддержка Deadcow SEO 3.0.</li>
								<li>Доработана загрузка zip архивов, таким образом обмен будет происходить быстрее, но файл обмена будет большим за счет содержания картинок. Содержание архива: в корне файлы *.xml <em>(названия роли не играет)</em> и папка с картинками <strong>import_files</strong>.</li>
								<li>Добавлено встроенное SEO.</li>
								<li>Добавлена загрузка характеристик из 1С. Режима два - первый все характеристики объединяет в одну опцию, например: "Размер: XL, Цвет: белый", а второй режим загружает каждую в отдельную опцию, например опция "Размер" и опция "Цвет" в шаблоне потребуется связывать эти опции чтобы отображались только существующие варианты.</li>
								<li>В версии 1.6.2.b7 остатки хранятся теперь с плавающей точкой, т.е. можно хранить 0.4 единицы товара</li>
								<li>Производители теперь загружаются из свойств или из поля "Изготовитель" в разделе "Товар" import.xml. Если есть и то и другое, то первым загружается поле "Изготовитель", а свойство "Производитель" будет пропущено.</li>
								<li>Исправлена проблема к кнопкой "Загрузить" при выборе картинки (водяные знаки).</li>
							</ul>
							<legend>Изменения в версии 1.6.2.b8 от 12.05.2016:</legend>
							<ul>
								<li>Добавлена загрузка свойств: Вес, Ширина, Высота, Длина, Модель, Производитель в стандартные поля товара</li>
								<li>Оптимизировано обновление товаров и категорий</li>
								<li>Добавлена опция "Описание в формате HTML", которая правильно форматирует текст</li>
								<li>При загрузке характеристик с 1С, цены хранятся в таблице <strong>product_price</strong>, а остатки <strong>product_quantity</strong>. Характеристику теперь можно выбрать в товаре, там же отображается остаток и цена (это без правки шаблона, пока так сделал). Кстати характеристики не удаляются при удалении их с 1С! В будущем реализую эту возможность.</li>
								<li>Добавлены опции отключающие товар и/или категорию при добавлении в каталог</li>
								<li>Исправлена ошибка в ранних версиях opencart 2.x (2.0.3.1) не читалась настройка цен и цены не грузились. Причина в хранении настроек, раньше применялась упаковка через serialise и unserialize, а в последующих json_encode и json_decode</li>
								<li>Добавлена загрузка характеристик из 1С в опции, пока только в режиме - одна характеристика - одна опция. Связанные опции будут позже</li>
								<li>Добавлен пересчет валюты в базовую при загрузке видов цен в другой валюте, по курсу в opencart. Также добавлена опция отключающая эту функцию.</li>
								<li>Переработаны функции формирования шаблона, переписан полностью шаблон под стиль OpenCart.</li>
								<li>Изменены настройки загрузки наименования товара, немного изменен шаблон.</li>
							</ul>
							<legend>Ожидаемые изменения в следующих версиях:</legend>
							<ul>
								<li>Скачивание заказов, для ручной загрузки в 1С.</li>
								<li>Загрузка заказов из 1С.</li>
								<li>Загрузка в режиме связанных опций.</li>
								<li>При загрузке предложений выбор по опциям что обновлять(остатки, цены, опции).</li>
							</ul>
							<legend>Если Вас заинтересуют какие-нибудь еще возможности модуля, пишите, рассмотрю все варианты.</legend>
							<p>Демонстрационные сервера (логин/пароль: demo/demo):</p>
							<ul>
								<li><a href="http://ocshop21014.ptr-print.ru">OCSHOP.PRO 2.1.0.1.4 (русская сборка)</a></li>
								<li><a href="http://opencart2101.ptr-print.ru">OPENCART 2.1.0.1 (english)</a></li>
								<li><a href="http://opencart2031.ptr-print.ru">OPENCART 2.0.3.1 (русская сборка)</a></li>
								<li><a href="http://ocstore21021.ptr-print.ru">OCSTORE 2.1.0.2.1 (русская сборка)</a></li>
							</ul>
						</div><!-- col-sm-12 -->
					</div><!-- tab-developing -->
	 			</div><!-- tab-content -->
			</form>
 			</div><!-- panel-body-->
		</div><!-- panel panel-default -->
	</div><!-- container-fluid  -->
	<div style="text-align:center; opacity: .5">
		<p><?php echo $version; ?> | <a href=https://github.com/KirilLoveVE/opencart2-exchange1c><?php echo $lang['text_source_code']; ?></a><br />
		<?php echo $lang['text_change']; ?></p>
	</div>
</div><!-- content -->

									
<script type="text/javascript"><!--
$('#exchange1c-button-upload').on('click', function() {
	$('#form-upload').remove();
	
	$('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" value="" /></form>');
	
	$('#form-upload input[name=\'file\']').trigger('click');
	
	if (typeof timer != 'undefined') {
	clearInterval(timer);
	}
		
	timer = setInterval(function() {
		if ($('#form-upload input[name=\'file\']').val() != '') {
			clearInterval(timer);
			
			$.ajax({
				url: 'index.php?route=module/exchange1c/manualImport&token=<?php echo $token; ?>',
				type: 'post',		
				dataType: 'json',
				data: new FormData($('#form-upload')[0]),
				cache: false,
				contentType: false,
				processData: false,		
				beforeSend: function() {
					$('#button-upload i').replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
					$('#button-upload').prop('disabled', true);
				},
				complete: function() {
					$('#button-upload i').replaceWith('<i class="fa fa-upload"></i>');
					$('#button-upload').prop('disabled', false);
				},
				success: function(json) {
					if (json['error']) {
						alert(json['error']);
					}
					
					if (json['success']) {
						alert(json['success']);
						
						$('#button-refresh').trigger('click');
					}
				},			
				error: function(xhr, ajaxOptions, thrownError) {
					alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
				}
			});	
		}
	}, 500);
});
//--></script>

<script type="text/javascript"><!--
$('#exchange1c-button-cleaning_db').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=module/exchange1c/manualCleaning&token=<?php echo $token; ?>',
			type: 'post',		
			dataType: 'json',
			data: new FormData($('#form-clean')[0]),
			cache: false,
			contentType: false,
			processData: false,		
			beforeSend: function() {
				$('#button-clean i').replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
				$('#button-clean').prop('disabled', true);
			},
			complete: function() {
				$('#button-clean i').replaceWith('<i class="fa fa-trash-o"></i>');
				$('#button-clean').prop('disabled', false);
			},
			success: function(json) {
				if (json['error']) {
					alert(json['error']);
				}
				
				if (json['success']) {
					alert(json['success']);
				}
			},			
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}	
});
//--></script>

<script type="text/javascript"><!--
var price_row = <?php echo $price_row; ?>;
function addConfigPriceType() {
	html = '<tr id="exchange1c_price_type_row' + price_row + '">'; 
	html += '<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][keyword]" value="" /></td>';
	html += '<td class="text-left"><select class="form-control" name="exchange1c_price_type[' + price_row + '][customer_group_id]">';
	<?php foreach ($customer_groups as $customer_group) { ?>
	html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][quantity]" value="1" size="2" /></td>';
	html += '<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][priority]" value="<?php echo $price_row+1; ?>" size="2" /></td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_price_type_row' + price_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';
	
	$('#exchange1c_price_type_id tbody').append(html);
	price_row++;
}
//--></script>

<script type="text/javascript"><!--
var store_row = <?php echo $store_row ?>;

function addStore() {
	html = '<tr id="exchange1c_store_row' + store_row + '">'; 
	html += '<td class="text-left"><input class="form-control" type="text" name="exchange1c_stores[' + store_row + '][name]" value="" /></td>';
	html += '<td class="text-left"><select name="exchange1c_stores[' + store_row + '][store_id]">';
	<?php foreach ($stores as $store_id => $name) { ?>
	html += '<option value="<?php echo $store_id ?>"><?php echo $name ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_store_row' + store_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';
	
	$('#exchange1c_store_id tbody').append(html);

	store_row++;
}
//--></script>


<script type="text/javascript"><!--
function image_upload(field, thumb) {
	$('#dialog').remove();

	$('#content').prepend('<div id="dialog" style="padding: 3px 0px 0px 0px;"><iframe src="index.php?route=common/filemanager&token=<?php echo $token; ?>&field=' + encodeURIComponent(field) + '" style="padding:0; margin: 0; display: block; width: 100%; height: 100%;" frameborder="no" scrolling="auto"></iframe></div>');

	$('#dialog').dialog({
		title: '<?php echo $lang['text_image_manager']; ?>',
		close: function (event, ui) {
			if ($('#' + field).attr('value')) {
				$.ajax({
					url: 'index.php?route=common/filemanager/image&token=<?php echo $token; ?>&image=' + encodeURIComponent($('#' + field).val()),
					dataType: 'text',
					success: function(data) {
						$('#' + thumb).replaceWith('<img src="' + data + '" alt="" id="' + thumb + '" />');
					}
				});
			}
		},
		bgiframe: false,
		width: 800,
		height: 400,
		resizable: false,
		modal: false
	});
};
//--></script>

<?php echo $footer; ?>
