<?php

declare(strict_types=1);

namespace Nets\Checkout\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use \Nets\Checkout\Service\Easy\CheckoutService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
/**
 * Description of NetsCheckout
 *
 * @author mabe
 */
class Checkout implements AsynchronousPaymentHandlerInterface {

    /**
     * @var CheckoutService
     */
    private $checkout;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(CheckoutService $checkout, SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->checkout = $checkout;
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void {
        
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse {
        $result = $this->checkout->createPayment($transaction->getOrder(), $this->systemConfigService);

        if (201 == $result->getHttpStatus() || 200 == $result->getHttpStatus())  {
            $resultDecoded = json_decode(  $result->getResponse(), true );
            $resultDecoded['hostedPaymentPageUrl'];
        } else {
            return new RedirectResponse('https://vonnahuy.com');
        }

        return new RedirectResponse($resultDecoded['hostedPaymentPageUrl']);
    }

}
