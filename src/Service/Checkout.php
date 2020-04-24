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
use \Nets\Checkout\Service\Easy\Api\EasyApiService;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;



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

    private $easyApiService;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var OrderTransactionStateHandler
     */
    private $transactionStateHandler;

    public function __construct(CheckoutService $checkout,
                                SystemConfigService $systemConfigService,
                                EasyApiExceptionHandler $easyApiExceptionHandler,
                                OrderTransactionStateHandler $transactionStateHandler,
                                EasyApiService $easyApiService,
                                EntityRepositoryInterface $orderTransactionRepo)     {
        $this->systemConfigService = $systemConfigService;
        $this->checkout = $checkout;
        $this->easyApiExceptionHandler = $easyApiExceptionHandler;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->easyApiService = $easyApiService;
        $this->orderTransactionRepo = $orderTransactionRepo;
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $salesChannelId = $transaction->getOrder()->getSalesChannelId();
        $environment = $this->systemConfigService->get('NetsCheckout.config.environment', $salesChannelId) ?? 'test';
        if('live' ==  $environment) {
            $secretKey = $this->systemConfigService->get('NetsCheckout.config.liveSecretKey', $salesChannelId);
        }
        if('test' ==  $environment) {
            $secretKey = $this->systemConfigService->get('NetsCheckout.config.testSecretKey', $salesChannelId);
        }

        try {
            $this->easyApiService->setEnv($environment);
            $this->easyApiService->setAuthorizationKey($secretKey);

            // it is incorrect check for captured amount
            $payment = $this->easyApiService->getPayment($_REQUEST['paymentid']);

            $transactionId = $transaction->getOrderTransaction()->getId();


            $context = $salesChannelContext->getContext();

            $this->orderTransactionRepo->update([[
                'id' => $transactionId,
                'customFields' => [
                    'nets_easy_payment_details' => ['transaction_id' => $_REQUEST['paymentid']],
                ],
            ]], $context);



            if (empty($payment->getReservedAmount())) {
                throw new CustomerCanceledAsyncPaymentException(
                    $transactionId,
                    'Customer canceled the payment on the Easy payment page'
                );
            }
        }catch (EasyApiException $ex) {
            throw new CustomerCanceledAsyncPaymentException(
                $transactionId,
                'Customer canceled the payment on the Easy payment page'
            );
        }

        $paymentState = $request->query->getAlpha('status');

        $context = $salesChannelContext->getContext();
        if (true) {
        } else {
            // Payment not completed, set transaction status to "open"
            $this->transactionStateHandler->reopen($transaction->getOrderTransaction()->getId(), $context);
        }
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $salesChannelContext
     * @return RedirectResponse
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse {
        try {
            $result = $this->checkout->createPayment($transaction, $this->systemConfigService, $salesChannelContext);
            $PaymentCreateResult = json_decode($result, true);
        } catch(EasyApiException $e) {
            $this->easyApiExceptionHandler->handle($e);
            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId() , $e->getMessage());
        }
        return new RedirectResponse($PaymentCreateResult['hostedPaymentPageUrl']);
    }
}
