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

    public function __construct(EasyApiService $easyApiService)
    {
        $this->easyApiService = $easyApiService;

    }

    public function createPayment(OrderEntity $orderEntity, SystemConfigService $systemConfigService) {
        $salesChannelId = $orderEntity->getSalesChannelId();
        $secretKey = 'test-secret-key-844664d582a8429aa149508a2e657c35'; //$systemConfigService->get('NetsCheckout.config.liveCheckoutKey', $salesChannelId);
        $this->easyApiService->setEnv('test');
        $this->easyApiService->setAuthorizationKey($secretKey);
        $payload = json_encode($this->collectRequestParams($orderEntity,  $systemConfigService));
        return $this->easyApiService->createPayment($payload);
    }

    /*
     *
     */
    private function collectRequestParams(OrderEntity $orderEntity, SystemConfigService $systemConfigService) {
        $data =  [
            'order' => [
                'items' => $this->getOrderItems($orderEntity),
                'amount' => $this->prepareAmount($orderEntity->getAmountTotal()),
                'currency' => 'SEK',//$orderEntity->getCurrency()->getIsoCode(),
                'reference' =>  $orderEntity->getOrderNumber(),
              ]];

        $data['checkout']['returnUrl'] = 'http://shopware.local/nets/caheckout/validate';
        $data['checkout']['integrationType'] = 'HostedPaymentPage';
        $data['checkout']['termsUrl'] = $systemConfigService->get( 'NetsCheckout.config.termsUrl', $orderEntity->getSalesChannelId());


        $data['notifications'] =
            ['webhooks' =>
                [
                    ['eventName' => 'payment.checkout.completed',
                        'url' => 'https://korrespondent.net',
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