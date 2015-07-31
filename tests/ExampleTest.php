<?php

namespace Hochstrasser\Wirecard\Test;

use Hochstrasser\Wirecard\Client;
use Hochstrasser\Wirecard\Adapter;
use Hochstrasser\Wirecard\Context;
use Hochstrasser\Wirecard\Request\Seamless\Frontend\InitDataStorageRequest;
use Hochstrasser\Wirecard\Request\Seamless\Frontend\ReadDataStorageRequest;
use Hochstrasser\Wirecard\Request\Seamless\Frontend\InitPaymentRequest;
use Hochstrasser\Wirecard\Model\Common\PaymentType;

class ExampleTest extends \PHPUnit_Framework_TestCase
{
    private function getContext()
    {
        return new Context('D200001', 'B8AKTPWBRMNBV455FG6M2DANE99WU2', 'de', 'qmore');
    }

    private function getClient()
    {
        $context = $this->getContext();
        $client = new Client($context, Adapter::defaultAdapter());

        return $client;
    }

    public function test()
    {
        $client = $this->getClient();

        $response = $client->send(InitDataStorageRequest::withOrderIdentAndReturnUrl(
            1234,
            'http://www.example.com'
        ));

        $params = $response->toArray();

        $this->assertEmpty($response->getErrors());
        $this->assertArrayHasKey('storageId', $params);
        $this->assertArrayHasKey('javascriptUrl', $params);

        $model = $response->toObject();
        $this->assertNotNull($model);

        $this->assertNotEmpty($model->getStorageId());
        $this->assertNotEmpty($model->getJavascriptUrl());
    }

    public function testWrongSecret()
    {
        $context = new Context('D200001', 'B8AKTPWBRMNBV455FG6M2DANE99WU2a', 'de', 'qmore');
        $client = new Client($context, Adapter::defaultAdapter());

        $response = $client->send(InitDataStorageRequest::withOrderIdentAndReturnUrl(
            1234,
            'http://www.example.com'
        ));

        $this->assertTrue($response->hasErrors());
        $this->assertCount(1, $response->getErrors());
    }

    public function testReadRequest()
    {
        $client = $this->getClient();

        $response = $client->send(InitDataStorageRequest::withOrderIdentAndReturnUrl(
            1234,
            'http://www.example.com'
        ));
        $this->assertNotNull($response->toObject());

        $storageId = $response->toObject()->getStorageId();

        $response = $client
            ->send(ReadDataStorageRequest::withStorageId($storageId));

        $this->assertNotNull($response->toObject());

        $this->assertEmpty($response->getErrors());
        $this->assertNotEmpty($response->toObject()->getStorageId());
        $this->assertCount(0, $response->toObject()->getPaymentInformation());
    }

    function testSerialize()
    {
        $request = InitDataStorageRequest::withOrderIdentAndReturnUrl(
            1234,
            'http://www.example.com'
        );
        $request->setContext($this->getContext());

        $data = serialize($request);

        $this->assertNotEmpty($data);
    }

    function testPayment()
    {
        $client = $this->getClient();

        $response = $client->send(
            InitPaymentRequest::with()
            ->addParam('paymentType', PaymentType::PayPal)
            ->addParam('amount', 12.01)
            ->addParam('currency', 'EUR')
            ->addParam('orderDescription', 'Some test order')
            ->addParam('successUrl', 'http://www.example.com')
            ->addParam('failureUrl', 'http://www.example.com')
            ->addParam('cancelUrl', 'http://www.example.com')
            ->addParam('serviceUrl', 'http://www.example.com')
            ->addParam('confirmUrl', 'http://www.example.com')
            ->addParam('consumerIpAddress', '127.0.0.1')
            ->addParam('consumerUserAgent', 'Mozilla')
        );

        $this->assertArrayHasKey('redirectUrl', $response->toArray());
    }
}
