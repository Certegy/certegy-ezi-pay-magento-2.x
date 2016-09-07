<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SamplePaymentGateway\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;

class OxipayClient implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;
	const OXIPAY_URL = "https://172.16.0.1/Oxipay?platform=Magento";

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

	private $clientFactory;
	
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
		ZendClientFactory $clientFactory
    ) {
        $this->logger = $logger;
		$this->clientFactory = $clientFactory;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
		$client = $clientFactory->create();
		$client->setUri(self::OXIPAY_URL);
		$client->setHeaders(['Content-Type: application/json']);
		$client->setMethod(\Zend_Http_Client::POST);
		
		$jsontext = json_encode($transferObject->getBody());
		
		$client->setRawData($jsontext);
		
		/* Body contains the JSON request string */
        $response = $this->generateResponseForCode(
            $this->getResultCode(
                $transferObject
            )
        );

        $this->logger->debug(
            [
                'request' => $transferObject->getBody(),
                'response' => $response
            ]
        );

        return $response;
    }
}
