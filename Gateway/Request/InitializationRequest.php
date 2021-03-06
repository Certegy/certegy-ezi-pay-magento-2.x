<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Certegy\EzipayPaymentGateway\Gateway\Request;

use Magento\Sales\Model\Order;
use Magento\Payment\Gateway\Data\Order\OrderAdapter;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session;
use Certegy\EzipayPaymentGateway\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

class InitializationRequest implements BuilderInterface
{
    protected $_logger;
    protected $_session;
    protected $_gatewayConfig;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        Config $gatewayConfig,
        LoggerInterface $logger,
        Session $session
    ) {
        $this->_gatewayConfig = $gatewayConfig;
        $this->_logger = $logger;
        $this->_session = $session;
    }

    /**
     * Checks the quote for validity
     * @throws Mage_Api_Exception
     */
    protected function validateQuote(OrderAdapter $order) {
        
        // @todo use config
        if($order->getGrandTotalAmount() < $this->_gatewayConfig->getMinimumOrderTotal()) {
            $this->_session->setEziPayErrorMessage(__("Certegy Ezi-Pay doesn't support purchases less than ".$this->_gatewayConfig->getMinimumOrderTotal()));
            return false;
        }

        // @todo use config
        if($order->getGrandTotalAmount() > $this->_gatewayConfig->getMaximumOrderTotal()) {
            $this->_session->setEziPayErrorMessage(__("Certegy Ezi-Pay doesn't support purchases greater than ".$this->_gatewayConfig->getMaximumOrderTotal()));
            return false;
        }

        $this->_logger->debug('[InitializationRequest][validateQuote]$this->_gatewayConfig->getSpecificCountry():'.($this->_gatewayConfig->getSpecificCountry()));
        $allowedCountriesArray = explode(',', $this->_gatewayConfig->getSpecificCountry());

        $this->_logger->debug('[InitializationRequest][validateQuote]$order->getBillingAddress()->getCountryId():'.($order->getBillingAddress()->getCountryId()));
        if (!in_array($order->getBillingAddress()->getCountryId(), $allowedCountriesArray)) {
            $this->_logger->debug('[InitializationRequest][validateQuote]Country is not in array');
            $this->_session->setEziPayErrorMessage(__('Orders from this country are not supported by Certegy Ezi-Pay. Please select a different payment option.'));
            return false;
        }

        if ($order->getShippingAddress() != null) {
            $this->_logger->debug('[InitializationRequest][validateQuote]$order->getShippingAddress()->getCountryId():'.($order->getShippingAddress()->getCountryId()));

            if ( !in_array($order->getShippingAddress()->getCountryId(), $allowedCountriesArray)) {
                $this->_session->setEziPayErrorMessage(__('Orders shipped to this country are not supported by Certegy Ezi-Pay. Please select a different payment option.'));
                return false;
            }
        } else {
            $this->_logger->debug('[InitializationRequest][validateQuote] Shipping Address is null');
        }        

        return true;
    }

    /**
     * Builds ENV request
     * From: https://github.com/magento/magento2/blob/2.1.3/app/code/Magento/Payment/Model/Method/Adapter.php
     * The $buildSubject contains:
     * 'payment' => $this->getInfoInstance()
     * 'paymentAction' => $paymentAction
     * 'stateObject' => $stateObject
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject) {

        $payment = $buildSubject['payment'];
        $stateObject = $buildSubject['stateObject'];

        $order = $payment->getOrder();
        $isValid = false;
        try {

            $isValid = $this->validateQuote($order);
        } catch(Exception $e) {
            $this->_logger->debug('[InitializationRequest]'.$e->getMessage());
        }

        if($this->validateQuote($order)) {
            $stateObject->setState(Order::STATE_PENDING_PAYMENT);
            $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
            $stateObject->setIsNotified(false);
        } else {
            $stateObject->setState(Order::STATE_CANCELED);
            $stateObject->setStatus(Order::STATE_CANCELED);
            $stateObject->setIsNotified(false);
        }
        
        return [ 'IGNORED' => [ 'IGNORED' ] ];

    }
}
