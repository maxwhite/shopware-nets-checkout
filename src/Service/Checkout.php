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
use \Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;
use \Nets\Checkout\Service\Easy\Api\Exception\EasyApiExceptionHandler;

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

    private $easyApiExceptionHandler;

    public function __construct(CheckoutService $checkout, SystemConfigService $systemConfigService, EasyApiExceptionHandler $easyApiExceptionHandler)
    {
        $this->systemConfigService = $systemConfigService;
        $this->checkout = $checkout;
        $this->easyApiExceptionHandler = $easyApiExceptionHandler;
    }

    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void {
        
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse {

        try {
            $result = $this->checkout->createPayment($transaction->getOrder(), $this->systemConfigService);
            $resultDecoded = json_decode($result, true);
            $resultDecoded['hostedPaymentPageUrl'];
            return new RedirectResponse($resultDecoded['hostedPaymentPageUrl']);
        } catch(EasyApiException $e) {
            $this->easyApiExceptionHandler->handle($e);
        }
        return new RedirectResponse('/');
    }

}
