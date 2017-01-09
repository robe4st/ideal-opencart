<?php

/**
 *  iDEALplugins.nl
 *  TargetPay plugin for Opencart 2.0+
 *
 *  (C) Copyright Yellow Melon 2014
 *
 * @file       TargetPay Catalog Controller
 * @author     Yellow Melon B.V. / www.sofortplugins.nl
 * @release    5 nov 2014
 */
require_once ("system/helper/targetpay.class.php");

class ControllerExtensionPaymentIdeal extends Controller
{

    private $paymentType = 'IDE';

    /**
     * Select bank
     */
    public function index()
    {
        $targetCore = new TargetPayCore($this->paymentType);
        $this->language->load('extension/payment/ideal');
        
        $data['text_title'] = $this->language->get('text_title');
        $data['text_wait'] = $this->language->get('text_wait');
        
        $data['entry_bank_id'] = $this->language->get('entry_bank_id');
        $data['button_confirm'] = $this->language->get('button_confirm');
        
        $data['custom'] = $this->session->data['order_id'];
        $data['banks'] = $targetCore->getBankList();
        
        return $this->load->view($this->config->get('config_template') . 'extension/payment/ideal.tpl', $data);
    }

    /**
     * Start payment
     */
    public function send()
    {
        $paymentType = $this->paymentType;
        
        $this->load->model('checkout/order');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        $rtlo = ($this->config->get('ideal_rtlo')) ? $this->config->get('ideal_rtlo') : TargetPayCore::DEFAULT_RTLO; // Default TargetPay
        
        if ($order_info['currency_code'] != "EUR") {
            $this->log->write("Invalid currency code " . $order_info['currency_code']);
            $json['error'] = "Invalid currency code " . $order_info['currency_code'];
        } else {
            $targetPay = new TargetPayCore($paymentType, $rtlo, TargetPayCore::APP_ID, "nl", false);
            $targetPay->setAmount(round($order_info['total'] * 100));
            $targetPay->setDescription("Order #" . $this->session->data['order_id']);
            if (! empty($this->request->post['bank_id'])) {
                $targetPay->setBankId($this->request->post['bank_id']);
            }
            
            $targetPay->setCancelUrl($this->url->link('checkout/cart', '', 'SSL'));
            $targetPay->setReturnUrl($this->url->link('checkout/success', '', 'SSL'));
            
            $params = array('order_id=' . $this->session->data['order_id'],'payment_type=' . $paymentType);
            $targetPay->setReportUrl($this->url->link('extension/payment/tp_callback/report', $params, 'SSL'));
            
            $bankUrl = $targetPay->startPayment();
            
            $this->storeTxid($targetPay->getPayMethod(), $targetPay->getTransactionId(), $this->session->data['order_id']);
            
            if (! $bankUrl) {
                $this->log->write('TargetPay start payment failed: ' . $targetPay->getErrorMessage());
                $json['error'] = 'TargetPay start payment failed: ' . $targetPay->getErrorMessage();
            } else {
                $json['success'] = $bankUrl;
            }
        }
        
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Save txid/order_id pair in database
     */
    public function storeTxid($method, $txid, $order_id)
    {
        $sql = "INSERT INTO `" . DB_PREFIX . TargetPayCore::TARGETPAY_PREFIX . TargetPayCore::METHOD_IDEAL . "` SET " . "`order_id`='" .
        $this->db->escape($order_id) . "', " . "`method`='" .
        $this->db->escape($method) .
        "', `" . TargetPayCore::METHOD_IDEAL . "_txid`='" . $this->db->escape($txid) . "'";
        
        $this->db->query($sql);
    }
}
