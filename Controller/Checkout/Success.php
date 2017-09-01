<?php

namespace Certegy\EziPayPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;
use Certegy\EziPayPaymentGateway\Helper\Crypto;
use Certegy\EziPayPaymentGateway\Helper\Data;
use Certegy\EziPayPaymentGateway\Gateway\Config\Config;
use Certegy\EziPayPaymentGateway\Controller\Checkout\AbstractAction;

/**
 * @package Certegy\EziPayPaymentGateway\Controller\Checkout
 */
class Success extends AbstractAction {

    public function execute() {
        $isValid = $this->getCryptoHelper()->isValidSignature($this->getRequest()->getParams(), $this->getGatewayConfig()->getApiKey());
        $result = $this->getRequest()->get("x_result");
        $orderId = $this->getRequest()->get("x_reference");
        $transactionId = $this->getRequest()->get("x_gateway_reference");
        $amount = $this->getRequest()->get("x_amount");

        if(!$isValid) {
            $this->getLogger()->debug('Possible site forgery detected: invalid response signature.');
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        if(!$orderId) {
            $this->getLogger()->debug("EziPay returned a null order id. This may indicate an issue with the EziPay payment gateway.");
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        $order = $this->getOrderById($orderId);
        if(!$order) {
            $this->getLogger()->debug("EziPay returned an id for an order that could not be retrieved: $orderId");
            $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            return;
        }

        if($result == "completed" && $order->getState() === Order::STATE_PROCESSING) {
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
            return;
        }

        if($result == "failed" && $order->getState() === Order::STATE_CANCELED) {
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
            return;
        }

        if ($result == "completed") {
            $orderState = Order::STATE_PROCESSING;

            $orderStatus = $this->getGatewayConfig()->getEziPayApprovedOrderStatus();
            if (!$this->statusExists($orderStatus)) {
                $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
            }

            $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

            $order->setState($orderState)
                ->setStatus($orderStatus)
                ->addStatusHistoryComment("EziPay authorisation success. Transaction #$transactionId")
                ->setIsCustomerNotified($emailCustomer);

            $order->save();

            $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
            if ($invoiceAutomatically) {
                $this->invoiceOrder($order, $transactionId);
            }
            
            $this->getMessageManager()->addSuccessMessage(__("Your payment with EziPay is complete"));
            $this->_redirect('checkout/onepage/success', array('_secure'=> false));
        } else {
            $this->getCheckoutHelper()->cancelCurrentOrder("Order #".($order->getId())." was rejected by EziPay. Transaction #$transactionId.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addErrorMessage(__("There was an error in the EziPay payment"));
            $this->_redirect('checkout/cart', array('_secure'=> false));
        }

    }

    private function statusExists($orderStatus)
    {
        $statuses = $this->getObjectManager()
            ->get('Magento\Sales\Model\Order\Status')
            ->getResourceCollection()
            ->getData();
        foreach ($statuses as $status) {
            if ($orderStatus === $status["status"]) return true;
        }
        return false;
    }

    private function invoiceOrder($order, $transactionId)
    {
        if(!$order->canInvoice()){
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Cannot create an invoice.')
                );
        }
        
        $invoice = $this->getObjectManager()
            ->create('Magento\Sales\Model\Service\InvoiceService')
            ->prepareInvoice($order);
        
        if (!$invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                    __('You can\'t create an invoice without products.')
                );
        }
        
        /*
         * Look Magento/Sales/Model/Order/Invoice.register() for CAPTURE_OFFLINE explanation.
         * Basically, if !config/can_capture and config/is_gateway and CAPTURE_OFFLINE and 
         * Payment.IsTransactionPending => pay (Invoice.STATE = STATE_PAID...)
         */
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = $this->getObjectManager()->create('Magento\Framework\DB\Transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();
    }

}
