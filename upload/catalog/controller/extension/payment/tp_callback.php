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

class ControllerExtensionPaymentTPCallback extends Controller
{
    /**
     *
     * Handle payment result from report url
     * @param string $payment_type
     * @param number $order_id
     *
     * example usage: $targetPay->setReportUrl(
     * $this->url->link('extension/payment/tp_callback/report', array('order_id=' . $this->session->data['order_id'], 'payment_type=IDE'), 'SSL')
     * );
     */
    public function report()
    {
        $payment_type = (! empty($this->request->get['payment_type'])) ? $this->request->get['payment_type'] : 0;   //output: IDE, used in class TargetPayCore()
        
        $order_id = 0;
        if (! empty($this->request->get["order_id"])) {
            $order_id = (int) $this->request->get["order_id"];
        }
        
        if (! empty($this->request->get["amp;order_id"])) {
            $order_id = (int) $this->request->get["amp;order_id"]; // Buggy redirects
        }
        
        if (empty($order_id) || empty($payment_type)) {
            $this->log->write('TargetPay tp_callback(), no order_id passed');
            echo "NoOrderId ";
            die();
        }
        
        // Array mapping
        $method_mapping = array(
            "IDE" => TargetPayCore::METHOD_IDEAL,
            "MRC" => TargetPayCore::METHOD_MRCASH,
            "DEB" => TargetPayCore::METHOD_SOFORT,
            "WAL" => TargetPayCore::METHOD_PAYSAFE,
            "CC"  => TargetPayCore::METHOD_CREDIT_CARD
        );
        $var_type = $method_mapping[$payment_type]; // output: ideal
        
        $this->load->model('checkout/order');
        
        $trxid = empty($this->request->post["trxid"]) ? null : $this->request->post["trxid"];
        
        $targetPayTx = !empty($trxid) ? $this->getTxid($order_id, $trxid, $var_type) : null;
        
        if (! $targetPayTx) {
            $this->log->write('Could not find TargetPay transaction data for order_id=' . $order_id);
            echo "TxNotFound ";
            die();
        }
        
        $rtlo = ($this->config->get($var_type . '_rtlo')) ? $this->config->get($var_type . '_rtlo') : TargetPayCore::DEFAULT_RTLO; // Default TargetPay
        
        $targetPay = new TargetPayCore($payment_type, $rtlo, TargetPayCore::APP_ID, "nl", false);
        $targetPay->checkPayment($targetPayTx[$var_type . "_txid"]);
        
        if ($targetPay->getPaidStatus() || $this->config->get($var_type . '_test')) {
            $this->updateTxid($order_id, true, $var_type);
            $order_status_id = $this->config->get($var_type . '_pending_status_id');
            if (! $order_status_id) {
                $order_status_id = 1;
            } // Default to 'pending' after payment
            $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);
            echo "Paid... ";
        } else {
            echo "Not paid " . $targetPay->getErrorMessage() . "... ";
        }
        
        echo "(Opencart-2.x, 23-04-2015)";
        die();
    }

    /**
     * Get txid/order_id pair from database
     */
    public function getTxid($order_id, $txid, $var_type)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . TargetPayCore::TARGETPAY_PREFIX . $var_type . "` WHERE `order_id`='" . $this->db->escape($order_id) . "' AND `" . $var_type . "_txid`='" . $this->db->escape($txid) . "'";
        $result = $this->db->query($sql);
        
        return $result->rows[0];
    }

    /**
     * Update txid/order_id pair in database
     */
    public function updateTxid($order_id, $paid, $var_type, $tpResponse = false)
    {
        if ($paid) {
            $sql = "UPDATE `" . DB_PREFIX . TargetPayCore::TARGETPAY_PREFIX . $var_type . "` SET `paid`=now() WHERE `order_id`='" . $this->db->escape($order_id) . "'";
        } else {
            $sql = "UPDATE `" . DB_PREFIX . TargetPayCore::TARGETPAY_PREFIX . $var_type . "` SET `" . $var_type . "_response`='" . $this->db->escape($tpResponse) . "' WHERE `order_id`='" . $this->db->escape($order_id) . "'";
        }
        
        $this->db->query($sql);
    }
}
