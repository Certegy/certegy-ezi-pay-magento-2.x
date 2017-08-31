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
class Cancel extends AbstractAction {
    
    public function execute() {
        $orderId = $this->getRequest()->get('orderId');
        $order =  $this->getOrderById($orderId);

        if ($order && $order->getId()) {
            $this->getLogger()->debug('Requested order cancellation by customer. OrderId: ' . $order->getIncrementId());
            $this->getCheckoutHelper()->cancelCurrentOrder("Certegy Ezi-Pay: ".($order->getId())." was cancelled by the customer.");
            $this->getCheckoutHelper()->restoreQuote(); //restore cart
            $this->getMessageManager()->addWarningMessage(__("You have successfully canceled your Certegy Ezi-Pay payment. Please click on 'Update Shopping Cart'."));
        }
        $this->_redirect('checkout/cart');
    }

}
