<?php

namespace Certegy\EzipayPaymentGateway\Controller\Checkout;

use Certegy\EzipayPaymentGateway\Helper\Crypto;
use Certegy\EzipayPaymentGateway\Helper\Data;
use Certegy\EzipayPaymentGateway\Gateway\Config\Config;
use Certegy\EzipayPaymentGateway\Controller\Checkout\AbstractAction;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * @package Certegy\EzipayPaymentGateway\Controller\Checkout
 */
class Success extends AbstractAction {

    public function execute() {
        $request = $this->getRequest();
        $params = $request->getParams();

        $isValid       = $this->getCryptoHelper()->isValidSignature($params, $this->getGatewayConfig()->getApiKey());
        $result        = $request->get("x_result");
        $orderId       = $request->get("x_reference");
        $transactionId = $request->get("x_gateway_reference");
        $amount        = $request->get("x_amount");

        if(!$isValid) {
            $msg = 'Possible site forgery detected: invalid response signature.';
            $this->getLogger()->debug($msg);        
            
            if ($this->isPost($request)) {
                $this->sendJsonResponse(['failed' => $msg]);
            } else {
                $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            }
            return;
        }
        
        if(!$orderId) {

            $msg = 'Certegy Ezi-Pay returned a null order id. This may indicate an issue with the Certegy Ezi-Pay payment gateway.';
            $this->getLogger()->debug($msg);
        
            if ($this->isPost($request)) {
                return $this->sendJsonResponse(['failed' => $msg]);
            } else {
                $response = $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            }
            return;
        }

        $order = $this->getOrderById($orderId);
        if(!$order) {
            $msg = sprintf("Certegy Ezi-Pay returned an id for an order that could not be retrieved: %s", $orderId);
            $this->getLogger()->debug($msg);
            
            if ($this->isPost($request)) {
                return $this->sendJsonResponse(['failed' => $msg]);
            } else {
                $this->_redirect('checkout/onepage/error', array('_secure'=> false));
            }
            return;
        }

        if($result == "completed" && $order->getState() === Order::STATE_PROCESSING) {
            $this->getLogger()->debug('Order is already complete. Taking no action.');
            if ($this->isPost($request)) {
                return $this->sendJsonResponse(['success', 'Order is already complete. Taking no action.']);
            } else {
                $this->_redirect('checkout/onepage/success', array('_secure'=> false));
            }
            return;
        }

        if($result == "failed" && $order->getState() === Order::STATE_CANCELED) {
            $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
            return;
        }

        if ($result == "completed") {
            $orderState = Order::STATE_PROCESSING;

            $orderStatus = $this->getGatewayConfig()->getApprovedOrderStatus();
            if (!$this->statusExists($orderStatus)) {
                $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
            }

            $emailCustomer = $this->getGatewayConfig()->isEmailCustomer();

            $order->setState($orderState)
                  ->setStatus($orderStatus)
                  ->addStatusHistoryComment("Certegy Ezi-Pay authorisation success. Transaction #$transactionId")
                  ->setIsCustomerNotified($emailCustomer);

            $order->save();

            $invoiceAutomatically = $this->getGatewayConfig()->isAutomaticInvoice();
            if ($invoiceAutomatically) {
                $this->invoiceOrder($order, $transactionId);
            }            
            
            $this->getMessageManager()->addSuccessMessage(__("Your payment with Certegy Ezi-Pay is complete"));
            if ($this->isPost($request)) {
                $this->sendJsonResponse(['success' =>  "Transaction: #$transactionId completed within Sellers system"]);
            } else {
                $response = $this->_redirect('checkout/onepage/success', array('_secure'=> false));
            }
        } else {
            $this->getCheckoutHelper()->cancelCurrentOrder("Order #".($order->getId())." was rejected by Certegy Ezi-Pay. Transaction #$transactionId.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addErrorMessage(__("There was an error in the Certegy Ezi-Pay payment"));


            if ($this->isPost($request)) {
                $this->sendJsonResponse(['failed' => 'Order was rejected and has been cancelled by the Sellers system']);
            } else {
                $response = $this->_redirect('checkout/cart', array('_secure'=> false));
            }

            $this->sendResponse($response);
        }

    }

    /**
     * Determines if we have a POST request
     * 
     * @return bool
     */
    private function isPost($request)
    {
        // to do make generic
        return  ($request->getMethod() == "POST");
    }

    /**
     * Responds appropriately depending on client request.
     * If the request is a POST then the Async callback is initiaing the request
     */
    private function sendJsonResponse(array $args)
    {
        // look at the URL to see if it failed
        // checkout/onepage/error
        $resultFactory = $this->getResultJsonFactory();
        $resultJson    = $resultFactory->create();

        $resultJson->setData(json_encode($args, true));
        return $resultJson;
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
