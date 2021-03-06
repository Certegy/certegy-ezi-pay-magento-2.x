<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Certegy\EzipayPaymentGateway\Gateway\Config;

/**
 * Class Config.
 * Values returned from Magento\Payment\Gateway\Config\Config.getValue()
 * are taken by default from ScopeInterface::SCOPE_STORE
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'ezipay_gateway';

    const KEY_ACTIVE = 'active';
    const KEY_TITLE = 'title';
    const KEY_DESCRIPTION = 'description';
    const KEY_GATEWAY_LOGO = 'gateway_logo';
    const KEY_MERCHANT_NUMBER = 'merchant_number';
    const KEY_API_KEY = 'api_key';
    const KEY_GATEWAY_URL = 'gateway_url';
    const KEY_DEBUG = 'debug';
    const KEY_SPECIFIC_COUNTRY = 'specificcountry';
    const KEY_APPROVED_ORDER_STATUS = 'ezipay_approved_order_status';
    const KEY_EMAIL_CUSTOMER = 'email_customer';
    const KEY_AUTOMATIC_INVOICE = 'automatic_invoice';
    const KEY_MIN_ORDER_TOTAL = 'minimum_order_total';
    const KEY_MAX_ORDER_TOTAL = 'maximum_order_total';

    /**
     * Get Merchant number
     *
     * @return string
     */
    public function getMerchantNumber() {
        return $this->getValue(self::KEY_MERCHANT_NUMBER);
    }

    /**
     * Get Merchant number
     *
     * @return string
     */
    public function getTitle() {
        return $this->getValue(self::KEY_TITLE);
    }

    /**
     * Get Logo
     *
     * @return string
     */
    public function getLogo() {
        return $this->getValue(self::KEY_GATEWAY_LOGO);
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription() {
        return $this->getValue(self::KEY_DESCRIPTION);
    }

    /**
     * Get Gateway URL
     *
     * @return string
     */
    public function getGatewayUrl() {
        return $this->getValue(self::KEY_GATEWAY_URL);
    }

    /**
     * Get Gateway URL
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->getValue(self::KEY_API_KEY);
    }

    /**
     * Get Approved Order Status
     *
     * @return string
     */
    public function getApprovedOrderStatus()
    {
        return $this->getValue(self::KEY_APPROVED_ORDER_STATUS);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isEmailCustomer()
    {
        return (bool) $this->getValue(self::KEY_EMAIL_CUSTOMER);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isAutomaticInvoice()
    {
        return (bool) $this->getValue(self::KEY_AUTOMATIC_INVOICE);
    }

    /**
     * Get Payment configuration status
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    public function getMinimumOrderTotal()
    {

        return $this->getValue(self::KEY_MIN_ORDER_TOTAL);
    }

    public function  getMaximumOrderTotal()
    {
        return $this->getValue(self::KEY_MAX_ORDER_TOTAL);
    }

    /**
     * Get specific country
     *
     * @return string
     */
    public function getSpecificCountry()
    {
        return $this->getValue(self::KEY_SPECIFIC_COUNTRY);
    }

}
