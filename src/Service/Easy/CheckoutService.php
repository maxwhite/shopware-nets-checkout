<?php

namespace Nets\Checkout\Service\Easy;

use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Nets\Checkout\Service\Easy\Api\EasyApiService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;
use Nets\Checkout\Service\ConfigService;

class CheckoutService
{
    /**
     * @var EasyApiService
     */
    private $easyApiService;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * regexp for filtering strings
     */
    const ALLOWED_CHARACTERS_PATTERN = '/[^\x{00A1}-\x{00AC}\x{00AE}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}'
    . '\x{0250}-\x{02AF}\x{02B0}-\x{02FF}\x{0300}-\x{036F}'
    . 'A-Za-z0-9\!\#\$\%\(\)*\+\,\-\.\/\:\;\\=\?\@\[\]\\^\_\`\{\}\~ ]+/u';

    /**
     * CheckoutService constructor.
     * @param EasyApiService $easyApiService
     * @param ConfigService $configService
     */
    public function __construct(EasyApiService $easyApiService,
                                ConfigService $configService)
    {
        $this->easyApiService = $easyApiService;

        $this->configService = $configService;
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param SystemConfigService $systemConfigService
     * @param SalesChannelContext $salesChannelContext
     * @return string
     * @throws EasyApiException
     */
    public function createPayment(AsyncPaymentTransactionStruct $transaction, SystemConfigService $systemConfigService, SalesChannelContext $salesChannelContext) {
        $environment = $this->configService->getEnvironment($salesChannelContext);
        $secretKey = $this->configService->getSecretKey($salesChannelContext);
        $this->easyApiService->setEnv($environment);
        $this->easyApiService->setAuthorizationKey($secretKey);
        $payload = json_encode($this->collectRequestParams($transaction,  $systemConfigService, $salesChannelContext));
        return $this->easyApiService->createPayment($payload);
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param SystemConfigService $systemConfigService
     * @param SalesChannelContext $salesChannelContext
     * @return array
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
     * @param OrderEntity $orderEntity
     * @return array
     */
    private function getOrderItems(OrderEntity $orderEntity) {
        $items = [];
        // Products
        foreach ($orderEntity->getLineItems() as $item) {
            $taxes = $this->getRowTaxes($item->getPrice()->getCalculatedTaxes());

        $items[] = [
                'reference' => $item->getProductId(),
                'name' => $this->stringFilter($item->getLabel()),
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

    /**
     * @param CalculatedTaxCollection $calculatedTaxCollection
     * @return array
     */
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

    /**
     * @param OrderEntity $orderEntity
     * @return array
     */
    private function shippingCostLine(OrderEntity $orderEntity) {
        return [
            'reference' => 'shipping',
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

    /**
     * @param $amount
     * @return int
     */
    private function prepareAmount($amount = 0) {
        return (int)round($amount * 100);
    }

    /**
     * @param $string
     * @return string
     */
    public function stringFilter($string = '') {
        $string = substr($string, 0, 128);
        return preg_replace(self::ALLOWED_CHARACTERS_PATTERN, '', $string);
    }
}
