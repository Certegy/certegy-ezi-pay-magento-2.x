<?php

namespace Certegy\EzipayPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;
use Certegy\EzipayPaymentGateway\Helper\Crypto;
use Certegy\EzipayPaymentGateway\Helper\Data;
use Certegy\EzipayPaymentGateway\Gateway\Config\Config;
use Certegy\EzipayPaymentGateway\Controller\Checkout\AbstractAction;

/**
 * @package Certegy\EzipayPaymentGateway\Controller\Checkout
 */
class Cancel extends AbstractAction {
    
    public function execute() {
        $orderId = $this->getRequest()->get('orderId');
        $order =  $this->getOrderById($orderId);

        // $request = $this->getRequest();
        // $params = $request->getParams();
        // $this->validateRequest($params);

        if ($order && $order->getId() && ($order->getState() == Order::STATE_PENDING_PAYMENT)) {
            $this->getLogger()->debug('Requested order cancellation by customer. OrderId: ' . $order->getIncrementId());
            $this->getCheckoutHelper()->cancelCurrentOrder("EziPay: ".($order->getId())." was cancelled by the customer.");
            $this->getMessageManager()->addWarningMessage(__("You have successfully canceled your Certegy Ezi-Pay payment. Please click on 'Update Shopping Cart'."));
        }
        $this->_redirect('checkout/cart');
    }
}
