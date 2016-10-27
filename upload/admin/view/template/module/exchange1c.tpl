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
					<li><a href="#tab-category" data-toggle="tab"><?php echo $lang['text_tab_category']; ?></a></li>
					<li><a href="#tab-prices" data-toggle="tab"><?php echo $lang['text_tab_prices']; ?></a></li>
					<li><a href="#tab-stock" data-toggle="tab"><?php echo $lang['text_tab_stock']; ?></a></li>
					<li><a href="#tab-seo" data-toggle="tab"><?php echo $lang['text_tab_seo']; ?></a></li>
					<li><a href="#tab-order" data-toggle="tab"><?php echo $lang['text_tab_order']; ?></a></li>
					<li><a href="#tab-manual" data-toggle="tab"><?php echo $lang['text_tab_manual']; ?></a></li>
					<li><a href="#tab-info" data-toggle="tab"><?php echo $lang['text_tab_info']; ?></a></li>
					<li><a href="#tab-help" data-toggle="tab"><?php echo $lang['text_tab_help']; ?></a></li>
				</ul>
				<div class="tab-content">

					<!-- ОСНОВНЫЕ -->
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
												<td class="col-sm-8 text-left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][name]" value="<?php echo $config_store['name']; ?>" class="form-control"/></td>
												<td class="col-sm-3 text-left"><?php echo $stores[$config_store['store_id']] ?><input type="hidden" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][store_id]" value="0" /></td>
												<td class="col-sm-1 text-left">&nbsp;</td>
											</tr>
										<?php } else { ?>
											<tr id="exchange1c_store_row<?php echo $store_row ?>">
												<td class="col-sm-8 text-left"><input class="form-control" type="text" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][name]" value="<?php echo $config_store['name']; ?>" /></td>
												<td class="col-sm-3 text-left"><select class="form-control" name="exchange1c_stores[<?php echo $config_store['store_id'] ?>][store_id]">
												<?php foreach ($stores as $store_id => $name) { ?>
													<?php if ($store_id == $config_store['store_id']) { ?>
														<option value="<?php echo $store_id ?>" selected="selected"><?php echo $name ?></option>
													<?php } else { ?>
														<option value="<?php echo $store_id ?>"><?php echo $name ?></option>
													<?php } ?>
												<?php } ?>
												</select></td>
												<td class="col-sm-1 text-center">
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
							<?php echo $html_synchronize_uuid_to_id ?>
						</div>
						<div class="form-group">
							<?php echo $html_cleaning_db ?>
							<?php echo $html_cleaning_links ?>
						</div>
						<div class="form-group">
							<?php echo $html_file_exchange; ?>
							<?php echo $html_log_level; ?>
							<?php echo $html_flush_log; ?>
						</div>
					</div><!-- tab general -->

					<!-- ТОВАРЫ -->
					<div class="tab-pane" id="tab-product">
						<legend><?php echo $lang['legend_new_product']; ?></legend>
						<div class="form-group">
							<?php echo $html_synchronize_new_product_by ?>
							<?php echo $html_status_new_product ?>
						</div>
						<div class="form-group">
						</div>
						<legend><?php echo $lang['legend_update_in_product']; ?></legend>
						<div class="form-group">
							<?php echo $html_import_product_name ?>
							<?php echo $html_import_product_description ?>
							<?php echo $html_import_categories ?>
							<?php echo $html_import_product_manufacturer ?>
							<?php echo $html_import_images ?>
							<?php echo $html_parse_only_types_item ?>
						</div>
						<div class="form-group">
							<?php echo $html_description_html ?>
						</div>
						<legend><?php echo $lang['legend_product_features']; ?></legend>
						<div class="form-group">
							<?php echo $html_product_options_mode ?>
							<?php echo $html_product_options_subtract ?>
						</div>
						<legend><?php echo $lang['legend_product_images']; ?></legend>
						<div class="form-group">
							<?php echo $html_watermark ?>
						</div>

						<legend><?php echo $lang['legend_product_std_from_ts']; ?></legend>
						<div class="table-responsive">
							<table id="exchange1c_product_std" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-2 text-left"><?php echo $lang['text_product_tag_name_ts']; ?></td>
										<td class="col-sm-7 text-left"><?php echo $lang['text_desc']; ?></td>
										<td class="col-sm-1 text-left"><?php echo $lang['text_database_field']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_database_table']; ?></td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Ид</td>
										<td>Используется для связи Ид торговой системы и ID CMS, также это Ид выгружается в заказах.</td>
										<td>1c_id</td>
										<td>product_to_1c</td>
									</tr>
									<tr>
										<td>Артикул</td>
										<td>Артикул товара будет записан одновременно в оба поля, если это поле будет пустым, то в Модель (model) будет записан Ид.</td>
										<td>sku, model</td>
										<td>product</td>
									</tr>
									<tr>
										<td>Штрихкод</td>
										<td>Штрихкод товара.</td>
										<td>ean</td>
										<td>product</td>
									</tr>
									<tr>
										<td>БазоваяЕдиница</td>
										<td>Читается базовая единица и коэффициент пересчета</td>
										<td>unit_id</td>
										<td>product_unit, product_quantity, product_price</td>
									</tr>
									<tr>
										<td>ПолноеНаименование</td>
										<td>В некоторых конфигурациях это поле присутствует в XML, но приоритет имеет реквизит</td>
										<td>name</td>
										<td>product</td>
									</tr>
									<tr>
										<td>Описание</td>
										<td>Описание товара произвольной длины, обычно в ТС ограничивают 3000 символами</td>
										<td>description</td>
										<td>product_description</td>
									</tr>
									<tr>
										<td>Изготовитель</td>
										<td>Производитель или Изготовитель - одно и тоже, имеет приоритет перед свойством</td>
										<td>manufacturer_id</td>
										<td>product</td>
									</tr>
								</tbody>
							</table>
						</div> <!-- table -->

						<legend><?php echo $lang['legend_product_requisite_std_from_ts']; ?></legend>
						<div class="table-responsive">
							<table id="exchange1c_requisite_standart" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-2 text-left"><?php echo $lang['text_requisite_name_ts']; ?></td>
										<td class="col-sm-7 text-left"><?php echo $lang['text_requisite_desc']; ?></td>
										<td class="col-sm-1 text-left"><?php echo $lang['text_database_field']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_product_field_cms']; ?></td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Вес</td>
										<td>Загружается до определения свойств, если в свойстве будет такое же определение, то приоритет будет у свойства.</td>
										<td>weight</td>
										<td>Производитель</td>
									</tr>
									<tr>
										<td>ТипНоменклатуры</td>
										<td>По этому полю происходит фильтр опции "Обрабатывать только типы номенклатуры".</td>
										<td></td>
										<td></td>
									</tr>
									<tr>
										<td>ВидНоменклатуры</td>
										<td>Не используется</td>
										<td></td>
										<td></td>
									</tr>
									<tr>
										<td>ОписаниеВФорматеHTML</td>
										<td>Используется для форматирования описания товара.</td>
										<td></td>
										<td></td>
									</tr>
									<tr>
										<td>Полное наименование</td>
										<td>Читается полное наименование товара и в зависимости от опции "Наименование товара" будет записана в наименование товара.</td>
										<td>name</td>
										<td>Наименование</td>
									</tr>
								</tbody>
							</table>
						</div> <!-- table -->

						<legend><?php echo $lang['legend_product_properties_std_from_ts']; ?></legend>
						<div class="table-responsive">
							<table id="exchange1c_property_standart" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-2 text-left"><?php echo $lang['text_property_name_ts']; ?></td>
										<td class="col-sm-7 text-left"><?php echo $lang['text_property_desc']; ?></td>
										<td class="col-sm-1 text-left"><?php echo $lang['text_database_field']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_product_field_cms']; ?></td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Производитель</td>
										<td>Загружается в том случае если не был определен тег &lt;Изготовитель&gt; в разделе товара. Производится поиск по наименованию и записывается ID</td>
										<td>manufacturer_id</td>
										<td>Производитель</td>
									</tr>
									<tr>
										<td>Вес</td>
										<td>Вес товара, единице веса задается в CMS</td>
										<td>weight</td>
										<td>Вес</td>
									</tr>
									<tr>
										<td>Ширина</td>
										<td>Ширина товара, единица длины задается в CMS</td>
										<td>width</td>
										<td>Ширина</td>
									</tr>
									<tr>
										<td>Высота</td>
										<td>Высота товара, единица длины задается в CMS</td>
										<td>height</td>
										<td>Высота</td>
									</tr>
									<tr>
										<td>Длина</td>
										<td>Длина товара, единица длины задается в CMS</td>
										<td>length</td>
										<td>Длина</td>
									</tr>
									<tr>
										<td>Модель</td>
										<td>Это свойство имеет приоритет и перезапишет значение поля Модель.</td>
										<td>model</td>
										<td>Модель</td>
									</tr>
								</tbody>
							</table>
						</div> <!-- table -->

						<legend><?php echo $lang['legend_product_properties_from_ts']; ?></legend>
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i>
							<?php echo $lang['desc_product_properties_from_ts']; ?>
						</div>
						<div class="table-responsive">
							<table id="exchange1c_property_id" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-1 text-left"><?php echo $lang['text_database_field']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_property_name_ts']; ?></td>
										<td class="col-sm-6 text-left"><?php echo $lang['text_template']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_product_field']; ?></td>
										<td class="col-sm-1 text-right"><?php echo $lang['text_action']; ?></td>
									</tr>
								</thead>
								<tbody>
									<?php $property_row = 0 ?>
									<?php foreach ($exchange1c_properties as $property_id => $property) { ?>
										<tr id="exchange1c_property_row<?php echo $property_row ?>">
											<?php foreach ($product_fields as $name => $description) { ?>
												<?php if ($name == $property['product_field_name']) { ?>
												<td class="col-sm-1 text-left" id="property_index<?php echo $property_id; ?>"><?php echo $name; ?></td>
												<?php } ?>
											<?php } ?>
											<td class="text-left"><input class="form-control" type="text" name="exchange1c_properties[<?php echo $property_id ?>][name]" value="<?php echo $property['name']; ?>" /></td>
											<td class="text-left"><input class="form-control" type="text" name="exchange1c_properties[<?php echo $property_id ?>][template]" value="<?php echo $property['template']; ?>">
											<td class="text-left"><select class="form-control" name="exchange1c_properties[<?php echo $property_id ?>][product_field_name]" onchange="changeProductField(<?php echo $property_row ?>, this.options[this.selectedIndex].value)">
											<?php foreach ($product_fields as $name => $description) { ?>
												<?php if ($name == $property['product_field_name']) { ?>
													<option value="<?php echo $name ?>" selected="selected"><?php echo $description ?></option>
												<?php } else { ?>
													<option value="<?php echo $name ?>"><?php echo $description ?></option>
												<?php } ?>
											<?php } ?>
											</select></td>
											<td class="text-center">
											<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_property_row<?php echo $property_row ?>').remove() : false;"><i class="fa fa-minus-circle"></i></button>
											</td>
										</tr>
										<?php $property_row++ ?>
									<?php } // foreach ?>
								</tbody>
								<tfoot>
									<tr>
										<td colspan="4"></td>
										<td class="text-center">
											<a onclick="addProperty();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
										</td>
									</tr>
								</tfoot>
							</table>
						</div> <!-- table -->
					</div>

					<!-- КАТЕГОРИИ -->
					<div class="tab-pane" id="tab-category">
						<legend><?php echo $lang['legend_new_category']; ?></legend>
						<div class="form-group">
							<?php echo $html_status_new_category ?>
						</div>
						<legend><?php echo $lang['legend_other']; ?></legend>
						<div class="form-group">
							<?php echo $html_fill_parent_cats ?>
						</div>
					</div><!-- tab product -->

					<!-- ЦЕНЫ -->
					<div class="tab-pane" id="tab-prices">
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
												<td class="col-sm-4 text-left"><input type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" class="form-control"/></td>
												<td class="col-sm-3 text-left"><?php echo $lang['text_price_default']; ?><input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]" value="<?php echo $obj['customer_group_id']; ?>" /></td>
												<td class="col-sm-1 text-center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="1" /></td>
												<td class="col-sm-1 text-center">-<input type="hidden" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="0" /></td>
												<td class="col-sm-1 text-left">&nbsp;</td>
											</tr>
										<?php } else { ?>
											<tr id="exchange1c_price_type_row<?php echo $price_row; ?>">
												<td class="col-sm-4 text-left"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" /></td>
												<td class="col-sm-3 text-left"><select class="form-control" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]">
											<?php foreach ($customer_groups as $customer_group) { ?>
													<?php if ($customer_group['customer_group_id'] == $obj['customer_group_id']) { ?>
														<option value="<?php echo $customer_group['customer_group_id']; ?>" selected="selected"><?php echo $customer_group['name']; ?></option>
													<?php } else { ?>
														<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>
													<?php } ?>
												<?php } ?>
												</select></td>
												<td class="col-sm-1 text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="<?php echo $obj['quantity']; ?>" size="2" /></td>
												<td class="col-sm-1 text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="<?php echo $obj['priority']; ?>" size="2" /></td>
												<td class="col-sm-1 text-center">
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
						<div class="form-group">
							<?php echo $html_ignore_price_zero ?>
						</div>
						<div class="form-group">
							<?php echo $html_currency_convert ?>
						</div>
					</div>

					<!-- ОСТАТКИ -->
					<div class="tab-pane" id="tab-stock">
						<legend><?php echo $lang['legend_stock']; ?></legend>
						<div class="form-group">
							<?php echo $html_default_stock_status; ?>
							<?php echo $html_product_disable_if_zero ?>
							<?php echo $html_flush_quantity ?>
						</div>
					</div>

					<!-- SEO -->
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

					<!-- ЗАКАЗЫ -->
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

					<!-- РУЧНАЯ ОБРАБОТКА -->
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
								<?php echo $lang['button_download']; ?>
							</button>
						</div>
					</div><!-- tab-manual -->

					<!-- ИНФОРМАЦИЯ -->
					<div class="tab-pane" id="tab-info">
						<div class="form-group">
							<label class="col-sm-2 control-label"><?php echo $lang['entry_num_links_product_info'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $links_product_info ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_num_links_product_info'] ?>
								</div>
							</div>
							<label class="col-sm-2 control-label"><?php echo $lang['entry_num_links_category_info'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $links_category_info ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_num_links_category_info'] ?>
								</div>
							</div>
							<label class="col-sm-2 control-label"><?php echo $lang['entry_num_links_manufacturer_info'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $links_manufacturer_info ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_num_links_manufacturer_info'] ?>
								</div>
							</div>
							<label class="col-sm-2 control-label"><?php echo $lang['entry_num_links_attribute_info'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $links_attribute_info ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_num_links_attribute_info'] ?>
								</div>
							</div>
						</div>
					</div>
					<!-- СПРАВКА -->
					<div class="tab-pane" id="tab-help">
						В стадии разработки...
					<text class="news">
					</div><!-- tab-help -->
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

$('#exchange1c-button-cleaning_links').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=module/exchange1c/manualCleaningLinks&token=<?php echo $token; ?>',
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
	html += '<td class="col-sm-4 text-left"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][keyword]" value="" /></td>';
	html += '<td class="col-sm-3 text-left"><select class="form-control" name="exchange1c_price_type[' + price_row + '][customer_group_id]">';
	<?php foreach ($customer_groups as $customer_group) { ?>
	html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="col-sm-1 text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][quantity]" value="1" size="2" /></td>';
	html += '<td class="col-sm-1 text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][priority]" value="<?php echo $price_row+1; ?>" size="2" /></td>';
	html += '<td class="col-sm-1 text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_price_type_row' + price_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#exchange1c_price_type_id tbody').append(html);
	price_row++;
}
//--></script>

<script type="text/javascript"><!--
var store_row = <?php echo $store_row ?>;

function addStore() {
	html = '<tr id="exchange1c_store_row' + store_row + '">';
	html += '<td class="col-sm-8 text-left"><input class="form-control" type="text" name="exchange1c_stores[' + store_row + '][name]" value="" /></td>';
	html += '<td class="col-sm-3 text-left"><select class="form-control" name="exchange1c_stores[' + store_row + '][store_id]">';
	<?php foreach ($stores as $store_id => $name) { ?>
	html += '<option value="<?php echo $store_id ?>"><?php echo $name ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="col-sm-1 text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_store_row' + store_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#exchange1c_store_id tbody').append(html);

	store_row++;
}
//--></script>


<script type="text/javascript"><!--
var property_row = <?php echo $property_row ?>;
var property = [];

function addProperty() {
	html = '<tr id="exchange1c_property_row' + property_row + '">';
	html += '<td class="col-sm-1 text-left" id="property_index' + property_row + '">' + property[property_row] + '</td>';
	html += '<td class="col-sm-2 text-left"><input class="form-control" type="text" name="exchange1c_properties[' + property_row + '][name]" value="" /></td>';
	html += '<td class="col-sm-6 text-left"><input class="form-control" type="text" name="exchange1c_properties[' + property_row + '][template]" value="" /></td>';
	html += '<td class="text-left"><select class="form-control" name="exchange1c_properties[' + property_row + '][product_field_name]" onchange="changeProductField(' + property_row + ', this.options[this.selectedIndex].value)">';
<?php foreach ($product_fields as $field_name => $description) { ?>
	html += '<option value="<?php echo $field_name ?>"><?php echo $description ?></option>';
<?php } ?>
	html += '</select></td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_property_row' + property_row + '\').remove() : false; property_row--;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#exchange1c_property_id tbody').append(html);
	property_row++;

<?php foreach ($product_fields as $field_name => $description) { ?>
	changeProductField(property_row-1, '<?php echo $field_name ?>');
<?php break; ?>
<?php } ?>
}

function changeProductField(row, value) {
	$('#property_index'+row).text(value);
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
