<?php

/**
 *
 *  iDEALplugins.nl
 *  TargetPay plugin for Opencart 2.0+
 *
 *  (C) Copyright Yellow Melon 2014
 *
 *  @file    TargetPay Admin Controller
 *  @author  Yellow Melon B.V. / www.mrcashplugins.nl
 *
 */
require_once("../system/helper/targetpay.class.php");

class ControllerExtensionPaymentCreditcard extends Controller
{

    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/creditcard');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('creditcard', $this->request->post);
            
            $this->session->data['success'] = $this->language->get('text_success');
            
            $this->response->redirect(
                $this->url->link(
                    'extension/extension',
                    'token=' . $this->session->data['token'] . '&type=payment',
                    'SSL'
                )
            );
        }
        
        $data['heading_title'] = $this->language->get('heading_title');
        
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        
        $data['entry_rtlo'] = $this->language->get('entry_rtlo');
        $data['entry_test'] = $this->language->get('entry_test');
        $data['entry_transaction'] = $this->language->get('entry_transaction');
        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        
        $data['entry_canceled_status'] = $this->language->get('entry_canceled_status');
        $data['entry_pending_status'] = $this->language->get('entry_pending_status');
        
        $data['help_test'] = $this->language->get('help_test');
        $data['help_debug'] = $this->language->get('help_debug');
        $data['help_total'] = $this->language->get('help_total');
        
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        
        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_status'] = $this->language->get('tab_status');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
        
        if (isset($this->error['rtlo'])) {
            $data['error_rtlo'] = $this->error['rtlo'];
        } else {
            $data['error_rtlo'] = '';
        }
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link(
                'common/home',
                'token=' . $this->session->data['token'],
                'SSL'
            )
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link(
                'extension/extension',
                'token=' . $this->session->data['token'] . '&type=payment',
                'SSL'
            )
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link(
                'extension/payment/creditcard',
                'token=' . $this->session->data['token'],
                'SSL'
            )
        );
        
        $data['action'] = $this->url->link(
            'extension/payment/creditcard',
            'token=' . $this->session->data['token'],
            'SSL'
        );
        
        $data['cancel'] = $this->url->link(
            'extension/extension',
            'token=' . $this->session->data['token'] . '&type=payment',
            'SSL'
        );
        
        if (isset($this->request->post['creditcard_rtlo'])) {
            $data['creditcard_rtlo'] = $this->request->post['creditcard_rtlo'];
        } else {
            $data['creditcard_rtlo'] = $this->config->get('creditcard_rtlo');
        }
        
        if (! isset($data['creditcard_rtlo'])) {
            $data['creditcard_rtlo'] = TargetPayCore::DEFAULT_RTLO; // Default TargetPay
        }
        
        if (isset($this->request->post['creditcard_test'])) {
            $data['creditcard_test'] = $this->request->post['creditcard_test'];
        } else {
            $data['creditcard_test'] = $this->config->get('creditcard_test');
        }
        
        if (isset($this->request->post['creditcard_total'])) {
            $data['creditcard_total'] = $this->request->post['creditcard_total'];
        } else {
            $data['creditcard_total'] = $this->config->get('creditcard_total');
        }
        
        if (! isset($data['creditcard_total'])) {
            $data['creditcard_total'] = 4;
        }
        
        if (isset($this->request->post['creditcard_canceled_id'])) {
            $data['creditcard_canceled_status_id'] = $this->request->post['creditcard_canceled_status_id'];
        } else {
            $data['creditcard_canceled_status_id'] = $this->config->get('creditcard_canceled_status_id');
        }
        
        if (isset($this->request->post['creditcard_pending_status_id'])) {
            $data['creditcard_pending_status_id'] = $this->request->post['creditcard_pending_status_id'];
        } else {
            $data['creditcard_pending_status_id'] = $this->config->get('creditcard_pending_status_id');
        }
        
        // Bug fix for 2.0.0.0 ... everything defaults to canceled, not user friendly
        
        if (is_null($data['creditcard_pending_status_id'])) {
            $data['creditcard_pending_status_id'] = 1;
        }

        if (is_null($data['creditcard_canceled_status_id'])) {
            $data['creditcard_canceled_status_id'] = 7;
        }
        
        $this->load->model('localisation/order_status');
        
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        
        if (isset($this->request->post['creditcard_geo_zone_id'])) {
            $data['creditcard_geo_zone_id'] = $this->request->post['creditcard_geo_zone_id'];
        } else {
            $data['creditcard_geo_zone_id'] = $this->config->get('creditcard_geo_zone_id');
        }
        
        $this->load->model('localisation/geo_zone');
        
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        
        if (isset($this->request->post['creditcard_status'])) {
            $data['creditcard_status'] = $this->request->post['creditcard_status'];
        } else {
            $data['creditcard_status'] = $this->config->get('creditcard_status');
        }
        
        if (isset($this->request->post['creditcard_sort_order'])) {
            $data['creditcard_sort_order'] = $this->request->post['creditcard_sort_order'];
        } else {
            $data['creditcard_sort_order'] = $this->config->get('creditcard_sort_order');
        }
        
        if (! isset($data['creditcard_sort_order'])) {
            $data['creditcard_sort_order'] = 1;
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/payment/creditcard.tpl', $data));
    }

    private function validate()
    {
        if (! $this->user->hasPermission('modify', 'extension/payment/creditcard')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (! $this->request->post['creditcard_rtlo'] ||
            $this->request->post['creditcard_rtlo'] == TargetPayCore::DEFAULT_RTLO) {
            $this->error['rtlo'] = $this->language->get('error_rtlo');
        }
        
        if (! $this->error) {
            return true;
        } else {
            return false;
        }
    }

    public function install()
    {
        $this->load->model('extension/payment/creditcard');
        $this->model_extension_payment_creditcard->createTable();
        
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            TargetPayCore::METHOD_CREDIT_CARD,
            array(TargetPayCore::METHOD_CREDIT_CARD . '_status' => 1)
        );
    }
}
