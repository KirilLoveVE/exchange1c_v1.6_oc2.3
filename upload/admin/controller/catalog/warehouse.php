<?php
class ControllerCatalogWarehouse extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('catalog/warehouse');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/warehouse');

		$this->getList();
	}

	protected function getList() {

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['add'] = $this->url->link('catalog/warehouse/add', 'token=' . $this->session->data['token'] . $url, 'SSL');
		$data['delete'] = $this->url->link('catalog/warehouse/delete', 'token=' . $this->session->data['token'] . $url, 'SSL');

		$data['warehouses'] = array();

		$results = $this->model_catalog_warehouse->getWarehouses();

		foreach ($results as $result) {
			$data['warehouses'][] = array(
				'warehouse_id'	=> $result['warehouse_id'],
				'name'			=> $result['name'],
				'guid'			=> $result['guid'],
				'edit'			=> $this->url->link('catalog/warehouse/edit', 'token=' . $this->session->data['token'] . '&warehouse_id=' . $result['warehouse_id'] . $url, 'SSL')
			);
		}

		$data['heading_title']		= $this->language->get('heading_title');

		$data['text_no_results']	= $this->language->get('text_no_results');
		$data['text_confirm']		= $this->language->get('text_confirm');

		$data['text_list']			= $this->language->get('text_list');
		$data['column_name']		= $this->language->get('column_name');
		$data['column_guid']		= $this->language->get('column_guid');
		$data['column_action']		= $this->language->get('column_action');

		$data['button_add']			= $this->language->get('button_add');
		$data['button_edit']		= $this->language->get('button_edit');
		$data['button_delete']		= $this->language->get('button_delete');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/warehouse', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$pagination = new Pagination();
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/warehouse', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), 0,  ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), 1, $this->config->get('config_limit_admin'));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/warehouse_list.tpl', $data));
	}

}