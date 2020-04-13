<?php

namespace Nets\Checkout\Service\Easy;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Nets\Checkout\Service\Easy\Api\EasyApiService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;

class CheckoutService
{

    private $easyApiService;

    private $salesChannelContext;

    const ALLOWED_CHARACTERS_PATTERN = '/[^\x{00A1}-\x{00AC}\x{00AE}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}'
    . '\x{0250}-\x{02AF}\x{02B0}-\x{02FF}\x{0300}-\x{036F}'
    . 'A-Za-z0-9\!\#\$\%\(\)*\+\,\-\.\/\:\;\\=\?\@\[\]\\^\_\`\{\}\~ ]+/u';

    public function __construct(EasyApiService $easyApiService)
    {
        $this->easyApiService = $easyApiService;
        $this->salesChannelContext = 1;

    }

    public function createPayment(AsyncPaymentTransactionStruct $transaction, SystemConfigService $systemConfigService, SalesChannelContext $salesChannelContext) {
        $salesChannelId = $transaction->getOrder()->getSalesChannelId();
        $environment = $systemConfigService->get('NetsCheckout.config.environment', $salesChannelId) ?? 'test';
        if('live' ==  $environment) {
            $secretKey = $systemConfigService->get('NetsCheckout.config.liveSecretKey', $salesChannelId);
        }
        if('test' ==  $environment) {
            $secretKey = $systemConfigService->get('NetsCheckout.config.testSecretKey', $salesChannelId);
        }
        $this->easyApiService->setEnv($environment);
        $this->easyApiService->setAuthorizationKey($secretKey);
        $payload = json_encode($this->collectRequestParams($transaction,  $systemConfigService, $salesChannelContext));
        return $this->easyApiService->createPayment($payload);
    }

    /*
     *
     */
    private function collectRequestParams(AsyncPaymentTransactionStruct $transaction, SystemConfigService $systemConfigService, SalesChannelContext $salesChannelContext) {
        $orderEntity = $transaction->getOrder();
        $data =  [
            'order' => [
                'items' => $this->getOrderItems($orderEntity),
                'amount' => $this->prepareAmount($orderEntity->getAmountTotal()),
                'currency' => $salesChannelContext->getCurrency()->getIsoCode(),
                'reference' =>  $orderEntity->getOrderNumber(),
              ]];

        $data['checkout']['returnUrl'] = $transaction->getReturnUrl();
        $data['checkout']['integrationType'] = 'HostedPaymentPage';
        $data['checkout']['termsUrl'] = $systemConfigService->get('NetsCheckout.config.termsUrl', $orderEntity->getSalesChannelId());
        $data['checkout']['merchantHandlesConsumerData'] = true;


        $data['checkout']['consumer'] =
            ['email' =>  $salesChannelContext->getCustomer()->getEmail(),
                //'shippingAddress' =>  $salesChannelContext->getCustomer()->getActiveShippingAddress()->getAdditionalAddressLine1(),

                /*
                'shippingAddress' => [
                    "addressLine1" => "string 211",
                    "addressLine2" => "string 211",
                    "postalCode" => 83162,
                    "city" => "string",
                    "country" => "SWE"
                ],
                */

                /*'phoneNumber' => [
                    'prefix' => $phoneNumber['prefix'],
                    'number' => $phoneNumber['phone']],
                */
                'privatePerson' => [
                    'firstName' => $this->stringFilter($salesChannelContext->getCustomer()->getFirstname()),
                    'lastName' => $this->stringFilter($salesChannelContext->getCustomer()->getLastname())]
            ];


        $data['notifications'] =
            ['webhooks' =>
                [
                    ['eventName' => 'payment.checkout.completed',
                        'url' => 'https://some-url.com',
                        'authorization' => substr(str_shuffle(MD5(microtime())), 0, 10)]
                  ]];
        return $data;
    }

    /**
     *
     * @param type $checkout
     * @return type
     */
    private function getOrderItems(OrderEntity $orderEntity) {
        $items = [];
        // Products
        foreach ($orderEntity->getLineItems() as $item) {
            $taxes = $this->getRowTaxes($item->getPrice()->getCalculatedTaxes());

        $items[] = [
                'reference' => $item->getProductId(),
                'name' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'unit' => 'pcs',
                'unitPrice' => $this->prepareAmount($item->getUnitPrice() - $taxes['taxAmount']),
                'taxRate' => $this->prepareAmount($taxes['taxRate']),
                'taxAmount' => $this->prepareAmount($taxes['taxAmount']),
                'grossTotalAmount' => $this->prepareAmount($item->getTotalPrice()),
                'netTotalAmount' => $this->prepareAmount($item->getTotalPrice() - $taxes['taxAmount'])];
       }
       if($orderEntity->getShippingTotal()) {
            $items[] = $this->shippingCostLine();
        }
        return $items;
    }

    private function getRowTaxes(CalculatedTaxCollection $calculatedTaxCollection) {
        $taxAmount = 0;
        $taxRate = 0;
        foreach($calculatedTaxCollection as $calculatedTax) {
            $taxRate += $calculatedTax->getTaxRate();
            $taxAmount += $calculatedTax->getTax();
        }
        return ['taxRate' => $taxRate,
                'taxAmount' => $taxAmount];
    }

    private function shippingCostLine(OrderEntity $orderEntity) {
        return [
            'reference' => 'shipping121',
            'name' => 'Shipping',
            'quantity' => 1,
            'unit' => 'pcs',
            'unitPrice' => $this->prepareAmount($orderEntity->getShippingTotal()),
            'taxRate' => 0,
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($orderEntity->getShippingTotal()),
            'netTotalAmount' => $this->prepareAmount( $orderEntity->getShippingTotal() )


        ];
    }

    private function prepareAmount($amount) {
        return (int)round($amount * 100);
    }

    public function stringFilter($string) {
        $string = substr($string, 0, 128);
        return preg_replace(self::ALLOWED_CHARACTERS_PATTERN, '', $string);
    }
}