<?php

namespace Certegy\EzipayPaymentGateway\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Certegy\EzipayPaymentGateway\Helper\Crypto;
use Certegy\EzipayPaymentGateway\Helper\Data;
use Certegy\EzipayPaymentGateway\Helper\Checkout;
use Certegy\EzipayPaymentGateway\Gateway\Config\Config;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

/**
 * @package Certegy\EzipayPaymentGateway\Controller\Checkout
 */
abstract class AbstractAction extends Action {

    const LOG_FILE = 'ezi-pay.log';
    const EZIPAY_DEFAULT_CURRENCY_CODE = 'AUD';
    const EZIPAY_DEFAULT_COUNTRY_CODE = 'AU';

    private $_context;

    private $_checkoutSession;

    private $_orderFactory;

    private $_cryptoHelper;

    private $_dataHelper;

    private $_checkoutHelper;

    private $_gatewayConfig;

    private $_messageManager;

    private $_logger;

    private $_jsonResultFactory;

    public function __construct(
        Config $gatewayConfig,
        Session $checkoutSession,
        Context $context,
        OrderFactory $orderFactory,
        Crypto $cryptoHelper,
        Data $dataHelper,
        Checkout $checkoutHelper,
        LoggerInterface $logger,
        JsonFactory $jsonResultFactory
        
        ) {
            parent::__construct($context);
            $this->_checkoutSession = $checkoutSession;
            $this->_orderFactory = $orderFactory;
            $this->_cryptoHelper = $cryptoHelper;
            $this->_dataHelper = $dataHelper;
            $this->_checkoutHelper = $checkoutHelper;
            $this->_gatewayConfig = $gatewayConfig;
            $this->_messageManager = $context->getMessageManager();
            $this->_logger = $logger;
            $this->_jsonResultFactory  = $jsonResultFactory;
            
    }
    
    protected function getContext() {
        return $this->_context;
    }

    protected function getCheckoutSession() {
        return $this->_checkoutSession;
    }

    protected function getOrderFactory() {
        return $this->_orderFactory;
    }

    protected function getCryptoHelper() {
        return $this->_cryptoHelper;
    }

    protected function getDataHelper() {
        return $this->_dataHelper;
    }

    protected function getCheckoutHelper() {
        return $this->_checkoutHelper;
    }

    protected function getGatewayConfig() {
        return $this->_gatewayConfig;
    }

    protected function getMessageManager() {
        return $this->_messageManager;
    }

    protected function getLogger() {
        return $this->_logger;
    }

    protected function getResultJsonFactory() {
        return $this->_jsonResultFactory;
    }
    
    protected function getOrder()
    {
        $orderId = $this->_checkoutSession->getLastRealOrderId();

        if (!isset($orderId)) {
            return null;
        }

        return $this->getOrderById($orderId);
    }

    protected function getOrderById($orderId)
    {
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return null;
        }

        return $order;
    }

    protected function getObjectManager()
    {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    protected function validateRequest($params){

        $isValid       = $this->getCryptoHelper()->isValidSignature($params, $this->getGatewayConfig()->getApiKey());
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
    }
}
