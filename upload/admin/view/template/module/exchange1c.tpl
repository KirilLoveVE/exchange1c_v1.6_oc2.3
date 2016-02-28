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
		<div class="panel-body">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#tab-general" data-toggle="tab"><?php echo $lang['text_tab_general']; ?></a></li>
				<li><a href="#tab-product" data-toggle="tab"><?php echo $lang['text_tab_product']; ?></a></li>
				<li><a href="#tab-seo" data-toggle="tab"><?php echo $lang['text_tab_seo']; ?></a></li>
				<li><a href="#tab-order" data-toggle="tab"><?php echo $lang['text_tab_order']; ?></a></li>
				<li><a href="#tab-manual" data-toggle="tab"><?php echo $lang['text_tab_manual']; ?></a></li>
				<li><a href="#tab-developing" data-toggle="tab"><?php echo $lang['text_tab_developing']; ?></a></li>
			</ul>
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-1c" class="form-horizontal">
				<div class="tab-content">
					<div class="tab-pane active" id="tab-general">
						<fieldset>
							<legend><?php echo $lang['text_legend_store']; ?></legend>
							<div class="alert alert-info">
								<i class="fa fa-info-circle"></i>
								Определяет какое название каталога из 1С в какой магазин загружать. Если не указан ни один каталог, то загружает все подряд в магазин по-умолчанию.
							</div>
							<div class="table-responsive">
								<table id="exchange1c_store_id" class="table table-bordered table-hover">
									<thead>
										<tr>
											<td class="text-left"><?php echo $lang['entry_catalog_1c']; ?></td>
											<td class="text-left"><?php echo $lang['entry_store']; ?></td>
											<td class="text-right" width="10%"><?php echo $lang['entry_action']; ?></td>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($exchange1c_stores as $config_store_id => $config_store) { ?>
											<?php if ($config_store_id == 0) {?>
												<tr id="exchange1c_store_row<?php echo $config_store_id; ?>">
													<td class="left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store_id; ?>][keyword]" value="<?php echo $config_store['keyword']; ?>" class="form-control"/></td>
													<td class="left"><?php echo $store_default; ?><input type="hidden" name="exchange1c_stores[<?php echo $config_store_id; ?>][store_id]" value="0" /></td>
													<td class="left">&nbsp;</td>
												</tr>
											<?php } else { ?>
												<tr id="exchange1c_store_row<?php echo $config_store_id; ?>">
													<td class="left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store_id; ?>][keyword]" value="<?php echo $config_store['keyword']; ?>" /></td>
													<td class="left"><select name="exchange1c_stores[<?php echo $config_store_id; ?>][store_id]">
												<?php foreach ($stores as $store) { ?>
														<?php if ($store['store_id'] == $config_store['store_id']) { ?>
															<option value="<?php echo $store['store_id']; ?>" selected="selected"><?php echo $store['name']; ?></option>
														<?php } else { ?>
															<option value="<?php echo $store['store_id']; ?>"><?php echo $store['name']; ?></option>
														<?php } ?>
													<?php } ?>
													</select></td>
													<td class="center">
													<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_store_row<?php echo $config_store_id; ?>').remove() : false;"><i class="fa fa-trash-o"></i></button>
													</td>
												</tr>
											<?php } ?>
										<?php } ?>
									</tbody>
									<tfoot>
										<?php if (count($stores)) { ?>
										<tr>
											<td colspan="2"></td>
											<td class="left">
												<a onclick="addStore();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
											</td>
										</tr>
										<?php } ?>
									</tfoot>
								</table>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_auth']; ?></legend>
							<?php echo $form_exchange1c_username; ?>
							<?php echo $form_exchange1c_password; ?>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_security']; ?></legend>
							<?php echo $form_exchange1c_allow_ip; ?>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_other']; ?></legend>
							<?php echo $form_exchange1c_status; ?>
							<?php echo $form_exchange1c_file_zip; ?>
							<?php echo $form_exchange1c_full_log; ?>
						</div>
					</fieldset>
		
					<div class="tab-pane" id="tab-product">
						<fieldset>
							<legend><?php echo $lang['text_legend_price']; ?></legend>
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
													<td class="left"><input type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" class="form-control"/></td>
													<td class="left"><?php echo $lang['text_price_default']; ?><input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]" value="<?php echo $obj['customer_group_id']; ?>" /></td>
													<td class="center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="1" /></td>
													<td class="center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="0" /></td>
													<td class="left">&nbsp;</td>
												</tr>
											<?php } else { ?>
												<tr id="exchange1c_price_type_row<?php echo $price_row; ?>">
													<td class="left"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" /></td>
													<td class="left"><select name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]">
												<?php foreach ($customer_groups as $customer_group) { ?>
														<?php if ($customer_group['customer_group_id'] == $obj['customer_group_id']) { ?>
															<option value="<?php echo $customer_group['customer_group_id']; ?>" selected="selected"><?php echo $customer_group['name']; ?></option>
														<?php } else { ?>
															<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>
														<?php } ?>
													<?php } ?>
													</select></td>
													<td class="center"><input type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="<?php echo $obj['quantity']; ?>" size="2" /></td>
													<td class="center"><input type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="<?php echo $obj['priority']; ?>" size="2" /></td>
													<td class="center">
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
											<td class="left">
												<a onclick="addConfigPriceType();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
											</td>
										</tr>
										<?php } ?>
									</tfoot>
								</table>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_cleaning_db']; ?></legend>
							<div class="form-group">
								<!-- Очищать таблицы -->
								<label class="col-sm-2 control-label"><?php echo $lang['entry_cleaning_db'] ?></label>
								<div class="col-sm-3">
									<button id="button-clean" class="btn btn-primary" type="button" data-loading-text="<?php echo $lang['entry_clean_button'] ?>">
										<i class="fa fa-trash-o fa-lg"></i>
										<?php echo $lang['entry_clean_button'] ?>
									</button>
								</div>
								<div class="col-sm-7">
									<div class="alert alert-info">
										<i class="fa fa-info-circle"></i>
										<?php echo $lang['desc_cleaning_db'] ?>
									</div>
								</div>
							</div>
							<?php echo $form_exchange1c_flush_quantity; ?>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_images']; ?></legend>
							<div class="form-group">
								<!-- Водяные знаки -->
								<label class="col-sm-2 control-label" for="exchange1c_apply_watermark"><?php echo $lang['entry_apply_watermark']; ?></label>
								<div class="col-sm-3">
									<a title="" class="img_thumbnail" id="thumb-image0" aria-describedby="popover" href="" data-original-title="" data-toggle="image">
										<img src="<?php echo $thumb; ?>" data-placeholder="<?php echo $placeholder; ?>" alt="" />
										<input name="exchange1c_watermark" id="input_image0" value="<?php echo $exchange1c_watermark; ?>" type="hidden" />
									</a>
								</div>
								<div class="col-sm-7">
									<div class="alert alert-info">
										<i class="fa fa-info-circle"></i>
										<?php echo $lang['desc_apply_watermark']; ?>
									</div>
								</div>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_fields_update']; ?></legend>
							<?php echo $form_exchange1c_product_fields_update; ?>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_other']; ?></legend>
							<?php echo $form_exchange1c_parse_only_types_item; ?>
							<?php echo $form_exchange1c_fill_parent_cats; ?>
							<?php echo $form_exchange1c_default_stock_status; ?>
							<?php echo $form_exchange1c_product_disable_if_zero; ?>
							<?php echo $form_exchange1c_dont_use_artsync; ?>
							<?php echo $form_exchange1c_product_name_field; ?>
							<?php echo $form_exchange1c_synchronize_uuid_to_id; ?>
						</fieldset>
					</div>
					
					<div class="tab-pane" id="tab-seo">
						<div class="panel panel-default">
							<!-- SEO товар -->
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-pencil"></i><?php echo $lang['text_legend_seo_product']; ?></h3>
							</div>
							<div class="panel-body">
								<div class="form-group">
									<input type="hidden" name="exchange1c_seo_product_tags" value="<?php echo $exchange1c_seo_product_tags; ?>"/>
									<label class="col-sm-12 text-left">
										<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_product_tags; ?>
									</label>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_product_overwrite; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_product_seo_url; ?>
									<?php echo $form_exchange1c_seo_product_seo_url_template; ?>
									<p class="col-sm-12 text-left">
										<?php echo $lang['label_property_name_from_1c']; echo $exchange1c_seo_product_seo_url_import; ?>
									</p>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_product_meta_title; ?>
									<?php echo $form_exchange1c_seo_product_meta_title_template; ?>
									<p class="col-sm-12 text-left">
										<?php echo $lang['label_property_name_from_1c']; echo $exchange1c_seo_product_meta_title_import; ?>
									</p>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_product_meta_description; ?>
									<?php echo $form_exchange1c_seo_product_meta_description_template; ?>
									<p class="col-sm-12 text-left">
										<?php echo $lang['label_property_name_from_1c']; echo $exchange1c_seo_product_meta_description_import; ?>
									</p>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_product_meta_keyword; ?>
									<?php echo $form_exchange1c_seo_product_meta_keyword_template; ?>
									<p class="col-sm-12 text-left">
										<?php echo $lang['label_property_name_from_1c']; echo $exchange1c_seo_product_meta_keyword_import; ?>
									</p>
								</div>
							</div>
						</div>
						<div class="panel panel-default">
							<!-- SEO категория -->
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-pencil"></i><?php echo $lang['text_legend_seo_category']; ?></h3>
							</div>
							<div class="panel-body">
								<div class="form-group">
									<input type="hidden" name="exchange1c_seo_category_tags" value="<?php echo $exchange1c_seo_category_tags; ?>"/>
									<label class="col-sm-12 text-left">
										<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_category_tags; ?>
									</label>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_category_overwrite; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_category_seo_url; ?>
									<?php echo $form_exchange1c_seo_category_seo_url_template; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_category_meta_title; ?>
									<?php echo $form_exchange1c_seo_category_meta_title_template; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_category_meta_description; ?>
									<?php echo $form_exchange1c_seo_category_meta_description_template; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_category_meta_keyword; ?>
									<?php echo $form_exchange1c_seo_category_meta_keyword_template; ?>
								</div>
							</div>
						</div>
						
						<div class="panel panel-default">
							<!-- SEO Производителя -->
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-pencil"></i><?php echo $lang['text_legend_seo_manufacturer']; ?></h3>
							</div>
							<div class="panel-body">
								<div class="form-group">
									<input type="hidden" name="exchange1c_seo_manufacturer_tags" value="<?php echo $exchange1c_seo_manufacturer_tags; ?>"/>
									<label class="col-sm-12 text-left">
										<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_manufacturer_tags; ?>
									</label>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_manufacturer_overwrite; ?>
								</div>
								<div class="form-group">
									<?php echo $form_exchange1c_seo_manufacturer_seo_url; ?>
									<?php echo $form_exchange1c_seo_manufacturer_seo_url_template; ?>
								</div>
							</div>
						</div>
					</div>

					<div class="tab-pane" id="tab-order">
						<fieldset>
							<legend><?php echo $lang['text_legend_export_orders']; ?></legend>
							<div class="form-group">
								<?php echo $form_exchange1c_order_status_to_exchange; ?>
							</div>
							<div class="form-group">
								<?php echo $form_exchange1c_order_status_change; ?>
							</div>
							<div class="form-group">
								<?php echo $form_exchange1c_order_status_canceled; ?>
							</div>
							<div class="form-group">
								<?php echo $form_exchange1c_order_status_completed; ?>
							</div>
							<div class="form-group">
								<?php echo $form_exchange1c_order_currency; ?>
							</div>
							<div class="form-group">
								<?php echo $form_exchange1c_order_notify; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['text_legend_import_orders']; ?></legend>
							<h2><?php echo $lang['text_in_developing']; ?></h2>
						</fieldset>
					</div>
		
					<div class="tab-pane" id="tab-manual">
						<div class="form-group">
							<label class="col-sm-2 control-label" for="button-upload">
								<span title="" data-original-title="<?php echo $lang['help_upload']; ?>" data-toggle="tooltip"><?php echo $lang['entry_upload']; ?></span>
							</label>
							<button id="button-upload" class="col-sm-2 btn btn-primary" type="button" data-loading-text="<?php echo $lang['button_upload']; ?>">
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
					</div>
					
					<div class="tab-pane" id="tab-developing">
						<div class="form-group">
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.7 от 17.11.2015:</legend>
									<ul>
										<li>Загрузка каталогов из 1С в указанный магазин в настройках (Система -> Настройки).</li>
										<li>Временно убраны "Связанные опции".</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
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
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.9 от 26.11.2015:</legend>
									<ul>
										<li>Исправлены ошибки</li>
										<li>Включено вывод в лог информации о попытке и способе авторизации из программ.</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.10 от 28.11.2015:</legend>
									<ul>
										<li>Исправлены ошибки при загрузке предложений.</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.11 от 03.12.2015:</legend>
									<ul>
										<li>Доработана опция "Заполнение родительскими категориями", теперь работает на любых CMS.</li>
										<li>Добавлена опция "Обрабатывать только указанные типы номенклатуры", если поле оставить пустым, будет грузить все подряд.</li>
										<li>Добавлена опция "Запись Ид из 1С товаров и категорий в id". Корректно продолжает работать и при отключении ее, т.к. в связи с 1С Ид также записываются. Ид должно быть числовым максимальной длиной 11 символов, если Ид окажется неверным значением, то загрузка пройдет как будто опция отключена.</li>
										<li>Исправлена ошибка при выгрузке заказов. Временно не работает смена статуса заказа при выгрузке, будет работать в следующей версии.</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.12 от 06.12.2015:</legend>
									<ul>
										<li>Добавлены события, при удалении товара или категории из админки, которые удаляют также связи с 1С и картинки товара с диска.</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.1.13 от 10.12.2015:</legend>
									<ul>
										<li>Добавлена опция выбора записи наименования товара из 1С "Наименование полное" или "Наименование".</li>
										<li>Доработана функция смены статуса заказа при выгрузке заказа в 1С.</li>
										<li>Исправлена ошибка с обновлением товара.</li>
										<li>При удалении товаров и категорий из админки удаляются и связи с 1С.</li>
										<li>Если не заполнять типы цен, по умолчанию в основную загрузит только первую цену.</li>
										<li>Добавлен список полей, в котором можно указывать какие поля товара будут</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Изменения в версии 1.6.2.0 от 08.02.2016 (beta версии описывать подробно не буду):</legend>
									<ul>
										<li>Перелопачен по-новой весь код модуля.</li>
										<li>Оптимизирована загрузка памяти, контроль за расходом памяти. В бета версии будет выводить информацию в лог.</li>
										<li>Добавлены описания опций.</li>
										<li>Добавлена поддержка Deadcow SEO 3.0.</li>
										<li>Доработана загрузка zip архивов, таким образом обмен будет происходить быстрее, но файл обмена будет большим за счет содержания картинок. Содержание архива: в корне файлы *.xml <em>(названия роли не играет)</em> и папка с картинками <strong>import_files</strong>.</li>
										<li>Добавлено встроенное SEO.</li>
										<li>Добавлена выборочная загрузка товара</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Ожидаемые изменения в следующих версиях:</legend>
									<ul>
										<li>Скачивание заказов, для ручной загрузки в 1С.</li>
										<li>Загрузка заказов из 1С.</li>
										<li>Загрузка в режиме связанных опций.</li>
										<li>При загрузке предложений выбор по опциям что обновлять(остатки, цены, опции).</li>
									</ul>
								</fieldset>
							</div>
							<div class="col-sm-12">
								<fieldset>
									<legend>Если Вас заинтересуют какие-нибудь еще возможности модуля, пишите, рассмотрю все варианты.</legend>
									<p>Демонстрационные сервера (логин/пароль: demo/demo):</p>
									<ul>
										<li><a href="http://ocshop21014.ptr-print.ru">OCSHOP.PRO 2.1.0.1.4</a></li>
										<li><a href="http://opencart2101.ptr-print.ru">OPENCART 2.1.0.1 (english)</a></li>
										<li><a href="http://opencart2031.ptr-print.ru">OPENCART 2.0.3.1 (русская сборка)</a></li>
									</ul>
									
								</fieldset>
							</div>
						</div>
					</div>
					
				</div>
			</form>
		</div>
	</div>
	<div style="text-align:center; opacity: .5">
		<p><?php echo $version; ?> | <a href=https://github.com/KirilLoveVE/opencart2-exchange1c><?php echo $lang['text_source_code']; ?></a><br />
		<?php echo $lang['text_change']; ?></p>
	</div>
</div>

<script type="text/javascript"><!--
$('#button-clean').on('click', function() {
	$('#form-clean').remove();
	
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
});
//--></script>

<script type="text/javascript"><!--
var price_row = <?php echo $price_row; ?>;
function addConfigPriceType() {
	html= '';
	html += '<tr id="exchange1c_price_type_row' + price_row + '">'; 
	html += '<td class="left"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][keyword]" value="" /></td>';
	html += '<td class="left"><select name="exchange1c_price_type[' + price_row + '][customer_group_id]">';
	<?php foreach ($customer_groups as $customer_group) { ?>
	html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="center"><input type="text" name="exchange1c_price_type[' + price_row + '][quantity]" value="1" size="2" /></td>';
	html += '<td class="center"><input type="text" name="exchange1c_price_type[' + price_row + '][priority]" value="<?php echo $price_row+1; ?>" size="2" /></td>';
	html += '<td class="center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_price_type_row<?php echo $price_row; ?>\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';
	
	$('#exchange1c_price_type_id tbody').append(html);
	
	$('#config_price_type_row' + price_row + ' .date').datepicker({dateFormat: 'yy-mm-dd'});
	price_row++;
}
//--></script>

<script type="text/javascript"><!--
var store_row = <?php echo count($exchange1c_stores); ?>;
function addStore() {
	html= '';
	html += '<tr id="exchange1c_store_row' + store_row + '">'; 
	html += '<td class="left"><input class="form-control" type="text" name="exchange1c_stores[' + store_row + '][keyword]" value="" /></td>';
	html += '<td class="left"><select name="exchange1c_stores[' + store_row + '][store_id]">';
	<?php foreach ($stores as $store) { ?>
	html += '<option value="<?php echo $store['store_id']; ?>"><?php echo $store['name']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_store_row<?php echo count($exchange1c_stores); ?>\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';
	
	$('#exchange1c_store_id tbody').append(html);
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
