<?php

namespace Nets\Checkout\Service\Easy;

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

            /*
            if($checkoutObject->isTaxesIncluded()) {
                $unitPrice =  round($item['price'] / (1 + $this->getTaxRate($item)) * 100);
                $taxRate =  round($this->getTaxRate($item) * 10000);
                $taxAmount = round($this->getTaxPrice($item) * 100);
                $grossTotalAmount = round($item['price'] * 100) * $item['quantity'];
                $netTotalAmount =  round($item['price'] *  $item['quantity'] / (1 + $this->getTaxRate($item)) * 100);
            } else {
                $unitPrice =  round($item['price'] * 100);
                $taxRate =  0;
                $taxAmount = round($checkoutObject->getTotalTax() * 100);
                $grossTotalAmount = round(($item['price'] * 100)) * $item['quantity'];
                $netTotalAmount =  round($item['price'] *  $item['quantity'] * 100);

            }
            */
            $items[] = [
                'reference' => $item->getProductId(),
                'name' => $item->getProductId(),
                'quantity' => $item->getQuantity(),
                'unit' => 'pcs',
                'unitPrice' => $this->prepareAmount($item->getUnitPrice()),
                'taxRate' => 0,
                'taxAmount' => 0,
                'grossTotalAmount' => $this->prepareAmount($item->getTotalPrice()),
                'netTotalAmount' => $this->prepareAmount($item->getTotalPrice())];



        }

        if($orderEntity->getShippingTotal()) {
            $items[] = $this->shippingCostLine();
        }


        return $items;

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
            'netTotalAmount' => $this->prepareAmount($orderEntity->getShippingTotal())];
    }

    private function prepareAmount($amount) {
        return (int)round($amount * 100);
    }
}