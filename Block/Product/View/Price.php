<?php
namespace Certegy\EzipayPaymentGateway\Block\Product\View;

/**
 * This class provides helper methods to the view engine to allow us to extract
 * out logic related to when the widget should be displayed
 */
class Price extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Returns the current product
     * 
     * @return Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    /**
     * Determines if the price widget should be visible 
     * 
     * @return boolean
     */ 
    public function displayPriceWidget()
    {
        $product = $this->getProduct();
        
        $isActive = (boolean)$this->_scopeConfig->getValue('payment/ezipay_gateway/active');     
        $minOrderTotal = $this->_scopeConfig->getValue('payment/ezipay_gateway/minimum_order_total');
        $maxOrderTotal = $this->_scopeConfig->getValue('payment/ezipay_gateway/maximum_order_total');
        
        return $isActive && ($product->getFinalPrice() >= $minOrderTotal) && ($product->getFinalPrice() <= $maxOrderTotal);
    }


    /**
     * @return  array|float
     */
    public function getPrice()
    {
        $product = $this->_coreRegistry->registry('product');
        return $product->getFormatedPrice();
    }
}
