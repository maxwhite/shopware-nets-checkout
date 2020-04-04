<?php declare(strict_types=1);

namespace Nets\Checkout\Storefront\Controller;

use GuzzleHttp\Client;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

use Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;

use Nets\Checkout\Service\Easy\Api\EasyApiService;

use Nets\Checkout\Service\Easy\CheckoutService;

use Monolog\Handler\StreamHandler;

class TestController extends StorefrontController
{
    private $orderRepository;

    /** @var Context $context */
    private $context;

    /**
     * @var SystemConfigService
     */
    public $systemConfigService;

    private $logger;

    private $checkout;

    private $easyApiService;

    private $kernel;

    public function __construct(EntityRepositoryInterface $orderRepository,
                                \Psr\Log\LoggerInterface $logger,
                                \Nets\Checkout\Service\Easy\CheckoutService $checkout,
                                SystemConfigService $systemConfigService,
                                EasyApiService $easyApiService,
                                \Symfony\Component\HttpKernel\KernelInterface $kernel

    ) {
        $this->orderRepository = $orderRepository;
        $this->context = Context::createDefaultContext();
        $this->logger = $logger;
        $this->checkout = $checkout;
        $this->systemConfigService = $systemConfigService;
        $this->easyApiService = $easyApiService;
        $this->kernel = $kernel;
    }
    
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/nets/test", name="nets.test.controller", options={"seo"="false"}, methods={"GET"})
     */
    public function clearCart(SalesChannelContext $context)
    {

        //var_dump($_SERVER);

       // echo $this->get('kernel')->getProjectDir();

        //echo $this->kernel->getLogDir();

        __DIR__;
        $this->logger->pushHandler(new StreamHandler($this->kernel->getLogDir() . '/nets-easy-log.log' ));
        $this->logger->debug('DEBUG!!!');


        $handlers = $this->logger->getHandlers();

        $this->logger->popHandler();

        $handlers = $this->logger->getHandlers();

        exit;
        /*
       $client = new \GuzzleHttp\Client();
       $params = ['headers' =>
                        ['Content-Type' => 'text/json',
                         'Accept' => 'test/json',
                         'Authorization' => 'test-secret-key-844664d582a8429aa149508a2e657c35']
                 ];

       $response = $client->request('GET', 'https://test.api.dibspayment.eu/v1/payments/026d00005e67cae13d7337325c775028', $params);
       */
       //get_class$response->getBody();

       //exit;

       //echo 1244;
       $this->easyApiService->setEnv('test');
       $this->easyApiService->setAuthorizationKey('test-secret-key-844664d582a8429aa149508a2e657c35');

       //$payment = $this->easyApiService->getPayment('026d00005e67cae13d7337325c775028');

        $payload = '{"order":{"items":[{"reference":4263177715809,"name":"ADIDAS
M\u00c4STARE TEE  some
else","quantity":1,"unit":"pcs","unitPrice":62305,"taxRate":0,"taxAmount":0,"grossTotalAmount":62305,"netTotalAmount":62305},{"reference":"4e309dbbee1aee25ed645160ff2aecf2","name":"Standard
Shipping","quantity":1,"unit":"pcs","unitPrice":500,"taxRate":0,"taxAmount":0,"grossTotalAmount":500,"netTotalAmount":500},{"reference":"tax","name":"Tax","quantity":1,"unit":"pcs","unitPrice":0,"taxRate":0,"taxAmount":0,"grossTotalAmount":0,"netTotalAmount":0}],"amount":62805,"currency":"DKK","reference":"12506079363169"},"checkout":{"termsUrl":"https:\/\/test-terms.com","consumer":{"email":"mabe@dibs.dk","shippingAddress":{"addressLine1":"Address
20
\\","addressLine2":"20","postalCode":"1050","city":"Test","country":"DNK"},"company":{"name":"Dibs","contact":{"firstName":"Test","lastName":"Test"}},"phoneNumber":{"prefix":"+38","number":"0661788009"}},"merchantHandlesConsumerData":true,"returnUrl":"http:\/\/e3a5fed1.ngrok.io\/return_t?x_url_complete=https:\/\/buratino3.myshopify.com\/24840798305\/checkouts\/5ac0b9801d21416943bebcebac98072c\/offsite_gateway_callback&origin=buratino3.myshopify.com&checkout_id=12506079363169&x_url_cancel=https:\/\/buratino3.myshopify.com\/24840798305\/checkouts\/5ac0b9801d21416943bebcebac98072c?key=f9289288e119580d577c6df2f9528a5b","integrationType":"HostedPaymentPage"},"notifications":{"webhooks":[{"eventName":"payment.checkout.completed","url":"https:\/\/e3a5fed1.ngrok.io\/callback?callback_url=https:\/\/buratino3.myshopify.com\/services\/ping\/notify_integration\/dibs_easy_checkout_test\/24840798305&x_reference=12506079363169&shop_url=buratino3.myshopify.com","authorization":"a0d53a1fa9"},{"eventName":"payment.charge.created","url":"https:\/\/e3a5fed1.ngrok.io\/charge_created?x_reference=12506079363169","authorization":"a31ea430ac"},{"eventName":"payment.refund.completed","url":"https:\/\/e3a5fed1.ngrok.io\/refund_hook?x_reference=12506079363169","authorization":"43e5fc23c2"},{"eventName":"payment.cancel.created","url":"https:\/\/e3a5fed1.ngrok.io\/cancel_hook?x_reference=12506079363169","authorization":"354db23f82"}]}}';

        $this->easyApiService->createPayment($payload);

        //echo $payment->getReservedAmount();


       exit;
       $criteria = new Criteria();
       $criteria->addFilter(new EqualsFilter('orderNumber', 10041))
            ->addAssociation('transactions');
       $order = $this->orderRepository->search($criteria, $this->context)->first();
       //error_log(serialize($order));
       $file = '/var/www/order.txt';
       /*
       $fl = fopen($file, 'w+');
       fputs($fl, serialize($order));
       fclose($fl);
       */
       //exit;
       $order1 = file_get_contents($file);
       $order1 = unserialize($order1);
       $result = $this->checkout->createPayment($order1, $this->systemConfigService);
       var_dump($result); exit;
       if (201 == $result->getHttpStatus() || 200 == $result->getHttpStatus())  {
           $resultDecoded = json_decode(  $result->getResponse(), true );
           echo  $resultDecoded['hostedPaymentPageUrl'];
       } else {

       }
       echo $result->getResponse();
       exit;
    }
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/nets/caheckout/validate", name="nets.test.controller.validate", options={"seo"="false"}, methods={"GET"})
     */
    public function validate() {
        try {
            $this->easyApiService->setEnv('test');
            $this->easyApiService->setAuthorizationKey('test-secret-key-844664d582a8429aa149508a2e657c35');
            $payment = $this->easyApiService->getPayment($_REQUEST['paymentid']);
            if (empty($payment->getReservedAmount())) {
                   return $this->redirectToRoute('frontend.checkout.cart.page');
               }
            }catch (EasyApiException $ex) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
    }
}