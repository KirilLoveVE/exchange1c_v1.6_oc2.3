<?php echo $header; ?><?php echo $column_left; ?>


<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right">
				<button type="submit" form="form-exchange1c" class="btn btn-primary" id="form-save" data-toggle="tooltip" title="<?php echo $lang['button_save']; ?>"><i class="fa fa-save"></i></button>
				<button type="submit" form="form-exchange1c" class="btn btn-primary" id="form-save-refresh" data-toggle="tooltip" title="<?php echo $lang['button_apply']; ?>" onclick="$('#form-exchange1c').attr('action','<?php echo $refresh; ?>&refresh=1').submit()"><i class="fa fa-save"></i> + <i class="fa fa-refresh"></i></button>
				<a href="<?php echo $cancel; ?>" class="btn btn-default" data-toggle="tooltip" title="<?php echo $lang['button_cancel']; ?>" ><i class="fa fa-reply"></i></a>
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
		<div id="danger" style="display:none">
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>&nbsp;ВНИМАНИЕ! модуль отключен. Обмен через http/https работать не будет!</div>
		</div>
		<div id="modification" style="display:none">
			<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>&nbsp;ВНИМАНИЕ! Настройки не сохранены!</div>
		</div>
		<?php if ($text_info) { ?>
			<div class="alert alert-info">
				<i class="fa fa-info-circle"></i>
				<?php echo $text_info; ?>
			</div>
		<?php } ?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $lang['text_edit']; ?></h3>
			</div>
			<div class="panel-body">
				<form action="index.php?route=extension/module/exchange1c/downloadOrders&token=<?php echo $token; ?>" method="post" enctype="multipart/form-data" id="form-download-orders" class="form-horizontal"></form>
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
					<li><a href="#tab-service" data-toggle="tab"><?php echo $lang['text_tab_service']; ?></a></li>
					<li><a href="#tab-help" data-toggle="tab"><?php echo $lang['text_tab_error']; ?></a></li>
					<li><a href="#tab-updates" data-toggle="tab"><?php echo $lang['text_tab_updates']; ?></a></li>
				</ul>
				<div class="tab-content">

					<!-- ОСНОВНЫЕ -->
					<div class="tab-pane active" id="tab-general">
						<div class="form-group">
							<?php echo $html_module_status; ?>
						</div>
						<div class="form-group">
							<?php echo $html_export_system; ?>
						</div>
						<fieldset id="auth">
							<legend><?php echo $lang['legend_auth']; ?></legend>
							<div class="alert alert-info">
								<i class="fa fa-info-circle"></i>
								<?php echo $lang['info_connect'] . '<strong>' . $url_connect . '</strong>'; ?>
							</div>
							<div class="form-group">
								<?php echo $html_username; ?>
							</div>
							<div class="form-group">
								<?php echo $html_password; ?>
							</div>
							<legend><?php echo $lang['legend_security']; ?></legend>
							<div class="form-group">
								<?php echo $html_allow_ip; ?>
							</div>
							<div class="form-group">
								<?php echo $html_export_module_to_all; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_file_format']; ?></legend>
							<?php if ($zip_support) { ?>
							<div class="form-group">
								<?php echo $html_file_zip; ?>
							</div>
							<?php } ?>
							<label class="alert alert-info"><i class="fa fa-info-circle"></i> upload_file_size : <?php echo $upload_max_filesize; ?></label>
							<label class="alert alert-info"><i class="fa fa-info-circle"></i> post_max_size : <?php echo $post_max_size; ?></label>
							<div class="form-group">
								<?php echo $html_file_max_size; ?>
							</div>
							<div class="form-group">
								<?php echo $html_not_delete_files_after_import; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_cron']; ?></legend>
							<div class="form-group">
								<?php echo $html_cron_import_filename; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_log']; ?></legend>
							<div class="form-group">
								<?php echo $html_log_level; ?>
							</div>
						</fieldset>
						<fieldset id="log-enable">
							<div class="form-group">
								<?php echo $html_log_filename; ?>
							</div>
							<div class="form-group">
								<?php echo $html_log_debug_line_view; ?>
							</div>
						</fieldset>
					</div><!-- tab general -->

					<!-- ТОВАРЫ -->
					<div class="tab-pane" id="tab-product">

						<div class="panel-heading">
							<h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $lang['title_tab_product_edit']; ?></h3>
						</div>
						<div class="panel-body">
							<ul class="nav nav-tabs">
								<li class="active"><a href="#tab-product-general" data-toggle="tab"><?php echo $lang['text_tab_product_general']; ?></a></li>
								<li><a href="#tab-product-images" data-toggle="tab"><?php echo $lang['text_tab_product_images']; ?></a></li>
								<li><a href="#tab-product-properties" data-toggle="tab"><?php echo $lang['text_tab_product_properties']; ?></a></li>
								<li><a href="#tab-product-requisites" data-toggle="tab"><?php echo $lang['text_tab_product_requisites']; ?></a></li>
								<li><a href="#tab-product-features" data-toggle="tab"><?php echo $lang['text_tab_product_features']; ?></a></li>
							</ul>
							<div class="tab-content">

								<!-- ОСНОВНЫЕ -->
								<div class="tab-pane active" id="tab-product-general">
									<fieldset id="product_sync">
										<legend><?php echo $lang['legend_product_sync']; ?></legend>
										<div class="form-group">
											<?php echo $html_product_rules_pre_parse ?>
										</div>
										<div class="form-group" id="product_sync_mode">
											<?php echo $html_product_sync_mode ?>
										</div>
										<div class="form-group" id="product_no_create">
											<?php echo $html_product_no_create ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_delete_mode ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_new_status_disable ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_not_found_stop_error ?>
										</div>
									</fieldset>
									<fieldset id="product_field_update">
										<legend><?php echo $lang['legend_product_field_update']; ?></legend>
										<div class="form-group">
											<?php echo $html_product_disable_before_full_import ?>
										</div>
										<div class="form-group" id="product_name">
											<?php echo $html_product_name ?>
										</div>
										<div class="form-group" id="product_name_field">
											<?php echo $html_product_name_field ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_description_no_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_category_no_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_taxes_no_import ?>
										</div>
									</fieldset>
									<fieldset>
										<legend><?php echo $lang['legend_product_manufacturer']; ?></legend>
										<div class="form-group" id="product_manufacturer_no_import">
											<?php echo $html_product_manufacturer_no_import ?>
										</div>
										<div class="form-group" id="product_manufacturer_tag">
											<?php echo $html_product_manufacturer_tag ?>
										</div>
									</fieldset>
									<fieldset>
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
														<td><?php echo $lang['desc_product_guid']; ?></td>
														<td>1c_id</td>
														<td>product_to_1c</td>
													</tr>
													<tr>
														<td>Артикул</td>
														<td><?php echo $lang['desc_product_sku']; ?></td>
															<td>sku, model</td>
														<td>product</td>
														</tr>
													<tr>
														<td>Штрихкод</td>
														<td><?php echo $lang['desc_product_ean']; ?></td>
														<td>ean</td>
														<td>product</td>
													</tr>
													<tr>
														<td>БазоваяЕдиница</td>
														<td><?php echo $lang['desc_product_unit']; ?></td>
														<td>unit_id</td>
														<td>product_unit, product_quantity, product_price</td>
													</tr>
													<tr>
														<td>ПолноеНаименование</td>
														<td><?php echo $lang['desc_product_fullname']; ?></td>
														<td>name</td>
														<td>product</td>
													</tr>
													<tr>
														<td>Описание</td>
														<td><?php echo $lang['desc_product_description']; ?></td>
														<td>description</td>
														<td>product_description</td>
													</tr>
													<tr>
														<td>Изготовитель</td>
														<td><?php echo $lang['desc_product_manufacturer']; ?></td>
														<td>manufacturer_id</td>
														<td>product</td>
													</tr>
												</tbody>
											</table>
										</div> <!-- table -->
									</fieldset>
								</div><!-- tab-product-general -->

								<!-- ИЗОБРАЖЕНИЯ -->
								<div class="tab-pane" id="tab-product-images">
									<fieldset id="product_images">
										<legend><?php echo $lang['legend_product_images']; ?></legend>
										<div class="form-group">
											<?php echo $html_product_images_no_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_images_cache_clean ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_images_check ?>
										</div>
									</fieldset>
								</div><!-- tab-product-images -->

								<!-- СВОЙСТВА (АТРИБУТЫ) -->
								<div class="tab-pane" id="tab-product-properties">
									<fieldset id="product_properties">
										<div class="form-group">
											<?php echo $html_product_attribute_not_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_property_type_no_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_synchronize_attribute_by ?>
										</div>
										<legend><?php echo $lang['legend_product_attributes_groups']; ?></legend>
										<div class="form-group">
											<?php echo $html_attribute_group_mode ?>
										</div>
										<div class="form-group">
											<?php echo $html_attribute_group_name_mode ?>
										</div>
										<div class="form-group" id="attribute_group_name">
											<?php echo $html_attribute_group_name ?>
										</div>
									</fieldset>
									<fieldset>
										<legend><?php echo $lang['legend_product_properties_from_ts']; ?></legend>
										<div class="alert alert-info">
											<i class="fa fa-info-circle"></i>
											<?php echo $lang['desc_product_properties_from_ts']; ?>
										</div>
									</fieldset>
								</div><!-- tab-product-properties -->

								<!-- РЕКВИЗИТЫ -->
								<div class="tab-pane" id="tab-product-requisites">
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
													<td><?php echo $lang['desc_product_req_weight']; ?></td>
													<td>weight</td>
													<td>Вес</td>
												</tr>
												<tr>
													<td>ТипНоменклатуры</td>
													<td><?php echo $lang['desc_product_req_type']; ?></td>
													<td></td>
													<td></td>
												</tr>
												<tr>
													<td>ВидНоменклатуры</td>
													<td><?php echo $lang['desc_product_req_view']; ?></td>
													<td></td>
													<td></td>
												</tr>
												<tr>
													<td>ОписаниеВФорматеHTML</td>
													<td><?php echo $lang['desc_product_req_desc_html']; ?></td>
													<td></td>
													<td></td>
												</tr>
												<tr>
													<td>Полное наименование</td>
													<td><?php echo $lang['desc_product_req_fullname']; ?></td>
													<td>name</td>
													<td>Наименование</td>
												</tr>
												<tr>
													<td>Код</td>
													<td><?php echo $lang['desc_product_req_code']; ?></td>
													<td>id</td>
													<td><?php echo $lang['text_hide']; ?></td>
												</tr>
												<tr>
													<td>ISBN</td>
													<td><?php echo $lang['desc_product_isbn']; ?></td>
													<td>isbn</td>
													<td>ISBN</td>
												</tr>
											</tbody>
										</table>
									</div> <!-- table -->
								</div><!-- tab-product-requisites -->

								<!-- ХАРАКТЕРИСТИКИ (ОПЦИИ) -->
								<div class="tab-pane" id="tab-product-features">
									<fieldset>
										<div class="form-group">
											<?php echo $html_product_options_mode ?>
										</div>
										<div class="form-group">
											<?php echo $html_feature_price_mode ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_options_empty_ignore ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_options_type ?>
										</div>
										<div class="form-group">
											<?php echo $html_product_options_subtract ?>
										</div>
										<div class="form-group">
											<?php echo $html_option_image_import ?>
										</div>
										<div class="form-group">
											<?php echo $html_delete_text_in_brackets_option ?>
										</div>
									</fieldset>

								</div><!-- tab-product-features -->

							</div><!-- tab-content -->

						</div><!-- panel-body -->

					</div>
					<!-- ТОВАРЫ -->

					<!-- КАТЕГОРИИ -->
					<div class="tab-pane" id="tab-category">
						<div class="form-group">
							<?php echo $html_categories_no_import ?>
						</div>
						<div class="form-group">
							<?php echo $html_category_new_no_create ?>
						</div>
						<div class="form-group">
							<?php echo $html_category_new_status_disable ?>
						</div>
						<div class="form-group">
							<?php echo $html_category_empty_disable ?>
						</div>
						<div class="form-group">
							<?php echo $html_category_disable_before_full_import ?>
						</div>
						<div class="form-group">
							<?php echo $html_category_exist_status_enable ?>
						</div>
						<div class="form-group">
							<?php echo $html_fill_parent_cats ?>
						</div>

						<legend><?php echo $lang['legend_category_properties_from_ts']; ?></legend>
						<div class="table-responsive">
							<div class="alert alert-info">
								<i class="fa fa-info-circle"></i>
								<?php echo $lang['desc_category_properties']; ?>
							</div>
							<table id="exchange1c_category_property" class="table table-bordered table-hover">
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
										<td>Картинка</td>
										<td><?php echo $lang['desc_category_prop_image']; ?></td>
										<td>image</td>
										<td>Картинка</td>
									</tr>
									<tr>
										<td>Сортировка</td>
										<td><?php echo $lang['desc_category_prop_sort_order']; ?></td>
										<td>sort_order</td>
										<td>Сортировка</td>
									</tr>
								</tbody>
							</table>
						</div> <!-- table -->

						<legend><?php echo $lang['legend_category_name_split']; ?></legend>
						<div class="table-responsive">
							<div class="alert alert-info">
								<i class="fa fa-info-circle"></i>
								<?php echo $lang['desc_category_name_split']; ?>
							</div>
						</div> <!-- table -->

					</div>
					<!-- КАТЕГОРИИ -->

					<!-- ЦЕНЫ -->
					<div class="tab-pane" id="tab-prices">
						<div class="form-group">
							<?php echo $html_product_price_no_import ?>
						</div>
						<div class="form-group">
							<?php echo $html_price_types_auto_load ?>
						</div>
						<div class="form-group">
							<?php echo $html_product_disable_if_price_zero ?>
						</div>
						<div class="alert alert-info">
							<i class="fa fa-info-circle"></i>
							<?php echo $lang['desc_prices']; ?>
						</div>
						<legend><?php echo $lang['legend_prices']; ?></legend>
						<div class="table-responsive">
							<table id="exchange1c_price_type_id" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-3 text-left"><?php echo $lang['text_config_price_type']; ?></td>
										<td class="col-sm-3 text-left"><?php echo $lang['text_config_price_id_cml']; ?></td>
										<td class="col-sm-2 text-left"><?php echo $lang['text_customer_group']; ?></td>
										<td class="text-left" style="width: 10%;"><?php echo $lang['text_table']; ?></td>
										<td class="col-sm-1 text-right"><?php echo $lang['text_quantity']; ?></td>
										<td class="col-sm-1 text-right"><?php echo $lang['text_priority']; ?></td>
										<td><?php echo $lang['text_action']; ?></td>
									</tr>
								</thead>
								<tbody>
									<?php $price_row = 0; ?>
									<?php foreach ($exchange1c_price_type as $obj) { ?>
										<tr id="exchange1c_price_type_row<?php echo $price_row; ?>">
											<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][keyword]" value="<?php echo $obj['keyword']; ?>" /></td>
											<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][guid]" value="<?php echo isset($obj['guid']) ? $obj['guid'] : ''; ?>" /></td>
											<td class="text-left"><select class="form-control" name="exchange1c_price_type[<?php echo $price_row; ?>][customer_group_id]">
											<?php foreach ($customer_groups as $customer_group) { ?>
												<?php if ($customer_group['customer_group_id'] == $obj['customer_group_id']) { ?>
													<option value="<?php echo $customer_group['customer_group_id']; ?>" selected="selected"><?php echo $customer_group['name']; ?></option>
												<?php } else { ?>
													<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>
												<?php } ?>
											<?php } ?>
											</select></td>
											<td class="text-left"><select class="form-control" name="exchange1c_price_type[<?php echo $price_row; ?>][table_price]">
											<?php foreach ($table_prices as $table_price) { ?>
												<?php if ($table_price['name'] == $obj['table_price']) { ?>
													<option value="<?php echo $table_price['name']; ?>" selected="selected"><?php echo $table_price['desc']; ?></option>
												<?php } else { ?>
													<option value="<?php echo $table_price['name']; ?>"><?php echo $table_price['desc']; ?></option>
												<?php } ?>
											<?php } ?>
											</td>
											<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][quantity]" value="<?php echo $obj['quantity']; ?>" size="2" /></td>
											<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[<?php echo $price_row; ?>][priority]" value="<?php echo $obj['priority']; ?>" size="2" /></td>
											<td class="text-center">
											<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_price_type_row<?php echo $price_row; ?>').remove() : false;"><i class="fa fa-minus-circle"></i></button>
											</td>
										</tr>
										<?php $price_row++; ?>
									<?php } ?>
								</tbody>
								<tfoot>
									<?php if (count($customer_groups)) { ?>
									<tr>
										<td colspan="6"></td>
										<td class="text-center">
											<button type="button" id="btn_add_price_type" onclick="addConfigPriceType();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></button>
										</td>
									</tr>
									<?php } ?>
								</tfoot>
							</table>
						</div> <!-- table -->
						<legend><?php echo $lang['legend_currency']; ?></legend>
						<div class="table-responsive">
							<table id="exchange1c_currency" class="table table-bordered table-hover">
								<thead>
									<tr>
										<td class="col-sm-6 text-left"><?php echo $lang['text_currency_name_1c']; ?></td>
										<td class="col-sm-6 text-left"><?php echo $lang['text_currency']; ?></td>
										<td class="text-center" style="width: 50px;"><?php echo $lang['text_action']; ?></td>
									</tr>
								</thead>
								<tbody>
									<?php $currency_row = 0; ?>
									<?php foreach ($exchange1c_currency as $config_currency) { ?>
										<tr id="exchange1c_currency_row<?php echo $currency_row; ?>">
											<td class="text-left"><input class="form-control" type="text" name="exchange1c_currency[<?php echo $currency_row; ?>][name]" value="<?php echo $config_currency['name']; ?>" /></td>
											<td class="text-left"><select class="form-control" id="currency_id" name="exchange1c_currency[<?php echo $currency_row; ?>][currency_id]">
											<?php foreach ($currencies as $currency) { ?>
												<?php if ($currency['currency_id'] == $config_currency['currency_id']) { ?>
													<option value="<?php echo $currency['currency_id']; ?>" selected="selected"><?php echo $currency['title']; ?></option>
												<?php } else { ?>
													<option value="<?php echo $currency['currency_id']; ?>"><?php echo $currency['title']; ?></option>
												<?php } ?>
											<?php } ?>
											</td>
											<td class="text-center">
											<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_currency_row<?php echo $currency_row; ?>').remove() : false;"><i class="fa fa-minus-circle"></i></button>
											</td>
										</tr>
										<?php $currency_row++; ?>
									<?php } ?>
								</tbody>
								<tfoot>
									<?php if (count($currencies)) { ?>
									<tr>
										<td colspan="2"></td>
										<td class="text-center">
											<button type="button" id="btn_add_currency" onclick="addConfigCurrency();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></button>
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
					<!-- ЦЕНЫ -->

					<!-- ОСТАТКИ -->
					<div class="tab-pane" id="tab-stock">
						<legend><?php echo $lang['legend_stock']; ?></legend>
						<div class="form-group">
							<?php echo $html_warehouse_quantity_import; ?>
						</div>
						<div class="form-group">
							<?php echo $html_product_stock_status_off; ?>
						</div>
						<div class="form-group">
							<?php echo $html_product_stock_status_on; ?>
						</div>
						<div class="form-group">
							<?php echo $html_product_disable_if_quantity_zero ?>
						</div>
						<div class="form-group">
							<?php echo $html_flush_quantity ?>
						</div>
					</div>
					<!-- ОСТАТКИ -->

					<!-- SEO -->
					<div class="tab-pane" id="tab-seo">
						<!-- SEO товар -->
						<fieldset>
							<legend><?php echo $lang['legend_generate_seo']; ?></legend>
							<div class="form-group">
								<?php echo $html_generate_seo ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_seo_product']; ?></legend>
							<div class="form-group">
								<?php echo $html_seo_product_mode; ?>
								<label class="col-sm-12 text-left">
									<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_product_tags; ?>
									<input type="hidden" name="exchange1c_seo_product_tags" value="<?php echo $exchange1c_seo_product_tags; ?>" />
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
						</fieldset>
						<!-- SEO категория -->
						<fieldset>
							<legend><?php echo $lang['legend_seo_category']; ?></legend>
							<div class="form-group">
								<?php echo $html_seo_category_mode; ?>
								<label class="col-sm-12 text-left">
									<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_category_tags; ?>
								</label>
								<input type="hidden" name="exchange1c_seo_category_tags" value="<?php echo $exchange1c_seo_category_tags; ?>" />
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
						</fieldset>
						<!-- SEO Производителя -->
						<fieldset>
						<legend><?php echo $lang['legend_seo_manufacturer']; ?></legend>
							<div class="form-group">
								<?php echo $html_seo_manufacturer_mode; ?>
								<label class="col-sm-12 text-left">
									<?php echo $lang['label_available_patterns']; echo $exchange1c_seo_manufacturer_tags; ?>
								</label>
								<input type="hidden" name="exchange1c_seo_manufacturer_tags" value="<?php echo $exchange1c_seo_manufacturer_tags; ?>" />
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
						</fieldset>
					</div><!-- tab-seo -->

					<!-- ЗАКАЗЫ -->
					<div class="tab-pane" id="tab-order">
						<fieldset>
							<legend><?php echo $lang['legend_order_export']; ?></legend>
							<div class="form-group">
								<?php echo $html_orders_export_modify; ?>
							</div>
							<div class="form-group" id="order_date_export">
								<label class="col-sm-2 control-label"><?php echo $lang['entry_order_date_export'] ?></label>
								<div class="col-sm-2">
									<input type="datetime-local" name="exchange1c_order_date" class="form-control" value="<?php echo $order_date_export ?>" />
								</div>
								<div class="col-sm-8">
									<div class="alert alert-info"><i class="fa fa-info-circle"></i>
										<?php echo $lang['desc_order_date_export'] ?>
									</div>
								</div>
							</div>
							<div class="form-group">
								<?php echo $html_order_status_export; ?>
							</div>
							<div class="form-group" id="order_status_export">
								<?php echo $html_order_status_exported; ?>
							</div>
							<div class="form-group">
								<?php echo $html_order_reserve_product; ?>
							</div>
							<div class="form-group">
								<?php echo $html_convert_orders_cp1251; ?>
							</div>
							<div class="form-group">
								<?php echo $html_order_customer_default; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_set_order_status']; ?></legend>
							<div class="form-group" id="orders_import">
								<?php echo $html_orders_import; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_order_other']; ?></legend>
							<div class="form-group">
								<?php echo $html_order_currency; ?>
							</div>
						</fieldset>
						<fieldset>
							<legend><?php echo $lang['legend_setting_order_shipping']; ?></legend>
							<div class="alert alert-info">
								<i class="fa fa-info-circle"></i>
								<?php echo $lang['i_setting_order_shipping']; ?>
							</div>
							<!-- ТАБЛИЦА Настройка видов доставки для экспорта в заказы в ТС -->
							<div class="table-responsive">
								<table id="orders_delivery" class="table table-bordered table-hover">
									<thead>
										<tr>
											<td class="col-sm-5 text-left"><?php echo $lang['text_types_of_delivery']; ?></td>
											<td class="col-sm-6 text-left"><?php echo $lang['text_mapped_services']; ?></td>
											<td class="col-sm-1 text-center"><?php echo $lang['text_action']; ?></td>
										</tr>
									</thead>
									<tbody>
										<?php $order_delivery_row = 0 ?>
										<?php foreach ($exchange1c_order_delivery as $row_id => $data_row) { ?>
											<tr id="exchange1c_order_delivery_row<?php echo $order_delivery_row ?>">
												<td class="text-left"><select class="form-control" name="exchange1c_order_delivery[<?php echo $row_id ?>][delivery_id]">
												<?php foreach ($order_types_of_delivery as $delivery_id => $name) { ?>
													<?php if ($data_row['delivery_id'] == $delivery_id) { ?>
														<option value="<?php echo $delivery_id ?>" selected="selected"><?php echo $name ?></option>
													<?php } else { ?>
														<option value="<?php echo $delivery_id ?>"><?php echo $name ?></option>
													<?php } ?>
												<?php } ?>
												</select></td>
												<td class="text-left">
												<div><input class="form-control" type="text" name="exchange1c_order_delivery[<?php echo $row_id ?>][delivery_service_name]" value="<?php echo $data_row['delivery_service_name'] ?>" /></div>
												</td>
												<td class="text-center">
												<button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm('<?php echo $lang['text_confirm']; ?>') ? $('#exchange1c_order_delivery_row<?php echo $order_delivery_row ?>').remove() : false;"><i class="fa fa-minus-circle"></i></button>
												</td>
											</tr>
											<?php $order_delivery_row++ ?>
										<?php } // foreach ?>
									</tbody>
									<tfoot>
										<tr>
											<td colspan="2"></td>
											<td class="text-center">
												<a onclick="addOrderDelivery();" data-toggle="tooltip" title="<?php echo $lang['button_add']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i></a>
											</td>
										</tr>
									</tfoot>
								</table>
							</div> <!-- table -->
							<!-- ТАБЛИЦА Настройка видов доставки для экспорта в заказы в ТС -->
						</fieldset>

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
							<div class="col-sm-8">
								<label class="alert alert-info"><i class="fa fa-info-circle"></i> Upload max file size : <?php echo $upload_max_filesize; ?></label>
								<label class="alert alert-info"><i class="fa fa-info-circle"></i> Maximum size of POST data : <?php echo $post_max_size; ?></label>
								<label class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $lang['desc_upload_file']; ?></label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label" for="button-download-orders">
								<span title="" data-original-title="<?php echo $lang['help_download_orders']; ?>" data-toggle="tooltip"><?php echo $lang['entry_download_orders']; ?></span>
							</label>
							<button class="col-sm-2 btn btn-primary" form="form-download-orders" type="submit" data-loading-text="<?php echo $lang['button_download_orders']; ?>">
							<i class="fa fa-download"></i>
							<?php echo $lang['button_download']; ?>
							</button>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo $lang['desc_download_orders']; ?></div>
							</div>
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
							<label class="col-sm-2 control-label"><?php echo $lang['entry_max_execution_time'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $max_execution_time ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_max_execution_time'] ?>
								</div>
							</div>
							<label class="col-sm-2 control-label"><?php echo $lang['entry_memory_limit'] ?></label>
							<div class="col-sm-2">
								<div class="form-control"><?php echo $memory_limit ?></div>
							</div>
							<div class="col-sm-8">
								<div class="alert alert-info"><i class="fa fa-info-circle"></i>
									<?php echo $lang['desc_memory_limit'] ?>
								</div>
							</div>
						</div>
						<fieldset>
							<legend>Statistics:</legend>
							<div class="form-group">
								<label class="col-sm-2 control-label">Last exchange:</label>
								<div class="col-sm-2">
									<div class="form-control"><?php echo $exchange_date ?></div>
								</div>
								<div class="col-sm-8">
									<div class="alert alert-info"><i class="fa fa-info-circle"></i>
										Last exchange date and time
									</div>
								</div>
							</div>
							<!-- ТАБЛИЦА Настройка видов доставки для экспорта в заказы в ТС -->
							<div class="table-responsive">
								<table id="orders_delivery" class="table table-bordered table-hover">
									<thead>
										<tr>
											<td class="col-sm-2 text-center">file name</td>
											<td class="col-sm-8 text-center">info</td>
										</tr>
									</thead>
									<tbody>
										<?php foreach($statistics as $filename => $data_info) { ?>
										<tr>
											<td class="text-left"><?php echo $filename; ?></td>
											<td class="text-left"><ul><?php foreach ($data_info as $str => $info) { ?><li><?php echo $str . " = " . $info; ?></li><?php } ?></ul></td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div> <!-- table -->
							<!-- ТАБЛИЦА Настройка видов доставки для экспорта в заказы в ТС -->
						</fieldset>
					</div><!-- tab-info -->

					<!-- СЕРВИСЫЕ ФУНКЦИИ -->
					<div class="tab-pane" id="tab-service">
						<fieldset>
							<div class="form-group">
								<?php echo $html_cleaning_db ?>
							</div>
							<div class="form-group">
								<?php echo $html_delete_import_data; ?>
							</div>
							<div class="form-group">
								<?php echo $html_cleaning_links ?>
							</div>
							<div class="form-group">
								<?php echo $html_cleaning_old_images ?>
							</div>
							<div class="form-group">
								<?php echo $html_cleaning_cache ?>
							</div>
							<div class="form-group">
								<?php echo $html_remove_doubles_links; ?>
							</div>
							<div class="form-group">
								<?php echo $html_delete_double_url_alias; ?>
							</div>
							<div class="form-group">
								<?php echo $html_remove_unised_manufacturers; ?>
							</div>
						</fieldset>
					</div><!-- tab-service -->

					<!-- СПРАВКА -->
					<div class="tab-pane" id="tab-help">
						<fieldset>
							<legend>ERROR CODE DESCRIPTION</legend>
							<div class="table-responsive">
								<table id="errors_description" class="table table-bordered table-hover">
									<thead>
										<tr>
											<td class="col-sm-1 text-center">CODE</td>
											<td class="col-sm-8 text-center">DESCRIPTION</td>
										</tr>
									</thead>
									<tbody>
										<?php foreach($error_description as $error_code => $data_info) { ?>
										<tr>
											<td class="text-center"><?php echo $error_code; ?></td>
											<td class="text-left"><?php echo $data_info; ?></td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div> <!-- table -->
						</fieldset>
					</div><!-- tab-help -->

					<!-- ОБНОВЛЕНИЯ -->
					<div class="tab-pane" id="tab-updates">
						<fieldset>
							<legend>1.6.4.1 от 10.03.2018</legend>
							<div class="form-group">
								<ul>
									<li>Удалена опция "Уведомлять покупателя" при смене статуса заказа</li>
									<li>Переработана функция изменения статусов заказов после отправки в УС, статус будет изменен только у новых заказов, у остальных заказов статус меняется при загрузке заказов от УС</li>
									<li>Добавлена опция в настройказ заказа "Статус отмененных", этот статус будет установлен у заказов отмененных в 1С:Предприятие УНФ 1.6, для других не тестировалось, присылайте Ваши файлы orders.xml внесу поправки</li>
									<li>Исправлена ошибка при изменении даты выгрузки заказов, ошибка была в том изменения не сохранялись, но автоматически менялась с момента последней выгрузки</li>
								</ul>
							</div>
							<legend>1.6.4.3 от 23.04.2018</legend>
							<div class="form-group">
								<ul>
									<li>Удалена настройка "Удалять загруженные файлы" на вкладке <strong>Основные</strong>, вместо нее добавлена другая</li>
									<li>Добавлена настройка "Очищать кэш" на вкладке <strong>Основные</strong>, эта опция более корректно работает</li>
									<li>Сделал полноценную загрузку заказов из УС с характеристиками, теперь на сайте заказы обновляются с учетом опций</li>
									<li>Заказы теперь меняют статусы с начального только один раз. Ошибка была связана с тем что когда статус с "Ожидание" после выгрузки менялся "В обработке" и после обновления его в УС он был обновлен и на сайте, а при включенной опции "Выгружать измененные" он снова выгружался и тут статус установленный документом менялся снова на "В обработке".</li>
									<li>Восстановил работу настройки "Конвертация валюты" на вкладке <strong>Цены</strong>, теперь она работает, но курс пока берет из opencart</li>
								</ul>
							</div>
							<legend>1.6.4.4 от 24.04.2018</legend>
							<div class="form-group">
								<ul>
									<li>Добавлена функция для обновления с версии 1.6.3</li>
									<li>Добавлена к версии буква беты при изменении версии, например версия 1.6.4.4 может корректироваться (1.6.4.4b1 ... 1.6.4.4b12) и исправлять ошибки ежедневно, и для этих мелких ошибок будет увеличиваться счетчик бета версий, при смене версии счетчик бета сбросится. Если ошибок больше найдено не будет, то бета версия не будет добавляться. Бета версия означает сколько раз было внесено изменений для исправления ошибок.</li>
									<li>Исправлены ошибки с производителями</li>
									<li>Исправлена ошибка при формировании SEO производителей.</li>
								</ul>
							</div>
							<legend>1.6.4.4b34 от 03.07.2018</legend>
							<div class="form-group">
								<ul>
									<li>Удалены водяные знаки</li>
									<li>Включена удаление категорий которых нет в файле при полной выгрузке</li>
									<li>Исправлены ошибки с производителями</li>
									<li>Изменена настройка очистки кэша на удаления файлов после загрузки</li>
									<li>Добавлены отдельные настройки по статусу товара</li>
									<li>Удалены некоторые ненужные настройки</li>
									<li>Убраны склады</li>
									<li>Добавлена кнопка удаления не используемых единиц измерений</li>
									<li>Удалена настройка "Выгружать покупателя" - если не выгружать то будет ошибка в УС</li>
									<li>Включена проверка наличия поддержки PHP класса ZipArchive, если не поддерживается, тогда настройка "Выгружать в ZIP" не отображается и для УС сообщается zip=no</li>
									<li>Исправлена ошибка при включении и отключения модуля</li>
									<li>Поправлена ошибка при выполнении ручной генерациии SEO (не проверялось)</li>
									<li>Добавлено отключение генерации SEO при добавлении и обновлении товаров, категорий и производителей.</li>
									<li>Включена опция в которой можно перечислить названия опций, которые будут игнорированы</li>
									<li>Добавлена настройка вычисления базовой цены как минимальная цена опции (ПОКА ЕЩЕ НЕ ДОДЕЛАНО)</li>
									<li>Включена поддержка CommerceML версий 2.03 и 2.04</li>
									<li>Включено удаление архива в кэше</li>
									<li>Отключение пустых категорий после загрузки каталога</li>
									<li>Включение не пустых категорий после загрузки каталога</li>
									<li>Исправлена ошибка иногда вылазит при проверке XML файла при распаковке архива</li>
									<li>Добавлено отключение товаров и категорий по отдельности при полной загрузке</li>
									<li>Добавлено отключение товаров при наличии тега Статус со значением Удален</li>
								</ul>
							</div>
							<legend>1.6.4.4b36 от 07.08.2018</legend>
							<div class="form-group">
								<ul>
									<li>В процессе доработка если контрагент зарегистрирован как Оргранизация и в 1С он переименован...</li>
								</ul>
							</div>
							<legend>1.6.4.4b37 от 08.08.2018</legend>
							<div class="form-group">
								<ul>
									<li>Подправлена опция "Не показывать товар с нулевым остатком"</li>
								</ul>
							</div>
							<legend>1.6.4.5 от 04.10.2018</legend>
							<div class="form-group">
								<ul>
									<li>Корректно теперь выгружает заказы в кодировке windows-1251</li>
									<li>Добавил для УС УТ 11 выгрузку тега БазоваяЕдиница в товарах заказа. Всегда будет выгружаться "Штука" и Код "796"</li>
									<li>Поиск производителя теперь будет производится без учета регистра, то есть Samsung = SAMSUNG = samsung</li>
									<li>2018-10-15. Исправлена ошибка при авторизации 1С с модулем при наличии REMOTE_USER но при отсутствиии REDIRECT_REMOTE_USER</li>
								</ul>
							</div>
							<legend>1.6.4.7 от 12.11.2018</legend>
							<div class="form-group">
								<ul>
									<li>Исправлена ошибка с производителями для ocstore</li>
									<li>Переработаны функции загрузки предложений</li>
									<li>Добавлена поддержка 1С УТ 10.3, загрузка нулевых остатков</li>
									<li>Добавлена настройка по пересчету цен для предложений с характеристиками (опций)</li>
									<li>Удаление пустых опций</li>
									<li>При включении не отображать товар с нулевой ценой или количеством, товар будет отключен еще при загрузке каталога, и будет включен если модуль увидит цену и остаток</li>
									<li>Добавлена возможность выгружать заказы в учетную систему от одного покупателя, который будет указан в настройках модуля</li>
									<li>Доработана система установки статуса товара на складе, статус может меняться как при загрузке import так и в offers в зависимости от количества товара.</li>
									<li>Исправлена проблема с выгрузкой телефона и эл.почты в заказ 1С УТ 11</li>
								</ul>
							</div>
						</fieldset>
					</div><!-- tab-updates -->

	 			</div><!-- tab-content -->
			</form>
 			</div><!-- panel-body-->
		</div><!-- panel panel-default -->
	</div><!-- container-fluid  -->
	<div style="text-align:center; opacity: .5">
		<p>Version <?php echo $version; ?> | <a href=https://github.com/KirilLoveVE/opencart2-exchange1c><?php echo $lang['text_source_code']; ?></a> | <a href=http://tesla-chita.ru/export/exchange1c.php?module=export>Last version available</a><br />
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
				url: 'index.php?route=extension/module/exchange1c/manualImport&token=<?php echo $token; ?>',
				type: 'post',
				dataType: 'json',
				data: new FormData($('#form-upload')[0]),
				cache: false,
				contentType: false,
				processData: false,
				beforeSend: function() {
					$('#exchange1c-button-upload i').replaceWith('<i class="fa fa-circle-o-notch fa-spin"></i>');
					$('#exchange1c-button-upload').prop('disabled', true);
				},
				complete: function() {
					$('#exchange1c-button-upload i').replaceWith('<i class="fa fa-upload"></i>');
					$('#exchange1c-button-upload').prop('disabled', false);
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
	}, 500);
});


$('#exchange1c-button-cleaning_db').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualCleaning&token=<?php echo $token; ?>',
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
			url: 'index.php?route=extension/module/exchange1c/manualCleaningLinks&token=<?php echo $token; ?>',
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


$('#exchange1c-button-cleaning_old_images').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualCleaningOldImages&token=<?php echo $token; ?>',
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


$('#exchange1c-button-cleaning_cache').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualCleaningCache&token=<?php echo $token; ?>',
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


$('#exchange1c-button-generate_seo').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualGenerateSeo&token=<?php echo $token; ?>',
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


$('#exchange1c-button-remove_doubles_links').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualRemoveDoublesLinks&token=<?php echo $token; ?>',
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


$('#exchange1c-button-delete_import_data').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualDeleteImportData&token=<?php echo $token; ?>',
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

$('#exchange1c-button-delete_double_url_alias').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualDeleteDoubleUrlAlias&token=<?php echo $token; ?>',
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

$('#exchange1c-button-remove_unised_manufacturers').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualRemoveUnisedManufacturers&token=<?php echo $token; ?>',
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

$('#exchange1c-button-remove_unised_units').on('click', function() {
	$('#form-clean').remove();
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualRemoveUnisedUnits&token=<?php echo $token; ?>',
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

function update(ver) {
	if (confirm('<?php echo $lang['text_confirm'] ?>')) {
		$.ajax({
			url: 'index.php?route=extension/module/exchange1c/manualUpdate&version=' + ver + '&token=<?php echo $token; ?>',
			type: 'post',
			dataType: 'json',
			data: new FormData($('#form-exchange1c')[0]),
			cache: false,
			contentType: false,
			processData: false,
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
}


var price_row = <?php echo $price_row; ?>;
function addConfigPriceType() {
	html = '<tr id="exchange1c_price_type_row' + price_row + '">';
	html += '<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][keyword]" value="" /></td>';
	html += '<td class="text-left"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][id_cml]" value="" /></td>';
	html += '<td class="text-left"><select class="form-control" name="exchange1c_price_type[' + price_row + '][customer_group_id]">';
	<?php foreach ($customer_groups as $customer_group) { ?>
	html += '<option value="<?php echo $customer_group['customer_group_id']; ?>"><?php echo $customer_group['name']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="text-left"><select class="form-control" name="exchange1c_price_type[<?php echo $price_row; ?>][table_price]">';
	<?php foreach ($table_prices as $table_price) { ?>
		<?php $selected = '' ?>
		<?php if (count($exchange1c_price_type)) { ?>
		<?php if ($table_price['name'] == $obj['table_price']) { ?>
		<?php $selected = ' selected="selected"' ?>
		<?php } ?>
		<?php } ?>
	html += '<option value="<?php echo $table_price['name']; ?>"<?php echo $selected; ?>><?php echo $table_price['desc']; ?></option>';
	<?php } ?>
	html += '</td>';
	html += '<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][quantity]" value="1" size="2" /></td>';
	html += '<td class="text-center"><input class="form-control" type="text" name="exchange1c_price_type[' + price_row + '][priority]" value="<?php echo $price_row+1; ?>" size="2" /></td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_price_type_row' + price_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#exchange1c_price_type_id tbody').append(html);
	price_row++;
	$('select#customer_group').change();
}


var currency_row = <?php echo $currency_row; ?>;
function addConfigCurrency() {
	html = '<tr id="exchange1c_price_type_row' + currency_row + '">';
	html += '<td class="text-left"><input class="form-control" type="text" name="exchange1c_currency[' + currency_row + '][name]" value="" /></td>';
	html += '<td class="text-left"><select class="form-control" id="customer_group" name="exchange1c_currency[' + currency_row + '][currency_id]">';
	<?php foreach ($currencies as $currency) { ?>
	html += '<option value="<?php echo $currency['currency_id']; ?>"><?php echo $currency['title']; ?></option>';
	<?php } ?>
	html += '</select></td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_currency_row' + currency_row + '\').remove() : false;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#exchange1c_currency tbody').append(html);
	currency_row++;
}


var order_delivery_row = <?php echo $order_delivery_row ?>;
function addOrderDelivery() {
	html = '<tr id="exchange1c_order_delivery_row' + order_import_row + '">';
	html += '<td class="text-left"><select class="form-control" name="exchange1c_order_delivery[' + order_delivery_row + '][delivery_id]">';
<?php foreach ($order_types_of_delivery as $delivery_id => $name) { ?>
	html += '<option value="<?php echo $delivery_id ?>"><?php echo $name ?></option>';
<?php } ?>
	html += '<td class="text-left">';
	html += '<div><input class="form-control" type="text" name="exchange1c_order_delivery[' + order_delivery_row + '][delivery_service_name]" /></div>';
	html += '</td>';
	html += '<td class="text-center"><button type="button" data-toggle="tooltip" title="<?php echo $lang['button_remove']; ?>" class="btn btn-danger" onclick="confirm(\'<?php echo $lang['text_confirm']; ?>\') ? $(\'#exchange1c_order_delivery_row' + order_delivery_row + '\').remove() : false; order_delivery_row--;"><i class="fa fa-minus-circle"></i></button></td>';
	html += '</tr>';

	$('#orders_delivery tbody').append(html);
	order_delivery_row++;
}


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


function checkOptionSynchronize_by_code() {

	var val = $('input[name="exchange1c_synchronize_by_code"]:checked').val();
	if (val == 1) {
		$('select[name="exchange1c_synchronize_new_product_by"]').attr('disabled', 'disabled');
	} else {
		$('select[name="exchange1c_synchronize_new_product_by"]').attr('disabled', null);
	}

} // checkOptionSynchronize_by_code()


function checkOptionImport_product_name() {
	var val = $('select[name="exchange1c_import_product_name"]').val();
	if (val == "manually") {
		$('div#import_product_name_field_select').slideDown;
	} else {
		$('div#import_product_name_field').slideUp();
	}

} // checkOptionImport_product_name()


$('input[name="exchange1c_synchronize_by_code"]').change(function(){
	checkOptionSynchronize_by_code();
})


$('select[name="exchange1c_import_product_name"]').change(function(){
	checkOptionImport_product_name();
});

// Статус модуля
$('input[name="exchange1c_module_status"]').change(function(){
	checkStatus(this.value);
})

function checkStatus(status) {
	if (status != 1) {
		$('#danger').slideDown();
		$('#auth').slideUp();
		$('#security').slideUp();
	} else {
		$('#danger').slideUp();
		$('#auth').slideDown();
		$('#security').slideDown();
	}
}

// Вывод уведомления об изменении формы
$('input').change(function() {
	$('#modification').slideDown();
	modificationForm(this);
})
$('select').change(function() {
	$('#modification').slideDown();
	modificationForm(this);
})
$('checkbox').change(function() {
	$('#modification').slideDown();
	modificationForm(this);
})

function productImportMode(value) {
	// Режим обмена товарами
	if (value == 'all') {
		$('#product_sync').slideDown();
		$('#product_status').slideDown();
		$('#product_field_update').slideDown();
		$('#product_new_status_disable').slideDown();
	} else if (value == 'only_new') {
		$('#product_sync').slideDown();
		$('#product_status').slideDown();
		$('#product_field_update').slideDown();
		$('#product_new_status_disable').slideDown();
	} else if (value == 'only_update') {
		$('#product_sync').slideDown();
		$('#product_status').slideDown();
		$('#product_field_update').slideDown();
		$('#product_new_status_disable').slideUp();
	} else if (value == 'not_change') {
		$('#product_sync').slideUp();
		$('#product_status').slideUp();
		$('#product_field_update').slideUp();
		$('#product_new_status_disable').slideUp();
	}
}

function productNameManually(value) {
	// Название товара из поля указанного вручную
	if (value == 'manually') {
		$('#product_name_field').slideDown();
	} else {
		$('#product_name_field').slideUp();
	}
}

function logMode(value) {
	// Отображение настроек журнала
	if (value != '0') {
		$('#log-enable').slideDown();
	} else {
		$('#log-enable').slideUp();
	}
}

function groupName($object) {
	if ($object.val() == "brackets") {
		$('#attribute_group_name').slideUp();
	} else {
		$('#attribute_group_name').slideDown();
	}
}


function modificationForm($object) {

	if ($object) {
		if ($object.name == 'exchange1c_product_import_mode') {
			productImportMode($object.value);
		} else if ($object.name == 'exchange1c_product_name') {
			productNameManually($object.value);
		} else if ($object.name == 'exchange1c_log_level') {
			logMode($object.value);
		} else if ($object.name == 'exchange1c_attribute_group_name_mode') {
			//groupName($('select[name="exchange1c_groupname_in_brackets_attribute"]'));
		}
	} else {
		productImportMode($('select[name="exchange1c_product_import_mode"]').val());
		productNameManually($('select[name="exchange1c_product_name"]').val());
		logMode($('select[name="exchange1c_log_level"]').val());
		//groupName($('select[name="exchange1c_groupname_in_brackets_attribute"]'))

	}

}

$(document).ready(function() {

	checkStatus($('input[name="exchange1c_module_status"]:checked').val());
	modificationForm();
	checkOptionSynchronize_by_code();
	checkOptionImport_product_name();

});

//--></script>

<?php echo $footer; ?>
