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
        $context = Context::createDefaultContext();


        $criteria = new Criteria(['3623da704b704cf2b04e260723eb856d']);
        $criteria->addAssociation('lineItems.payload')
                  ->addAssociation('deliveries.shippingCosts')
                  ->addAssociation('deliveries.shippingMethod')
                  ->addAssociation('deliveries.shippingOrderAddress.country')
                  ->addAssociation('cartPrice.calculatedTaxes')
                  ->addAssociation('transactions.paymentMethod')
                  ->addAssociation('currency')
                  ->addAssociation('addresses.country');


        $order = $this->orderRepository->search($criteria, $context)->first();

        /* @var \Shopware\Core\Checkout\Order\OrderEntity $order */
        $items = $order->getLineItems();

        foreach ($order->getLineItems() as $item) {
             foreach( $item->getPrice()->getCalculatedTaxes() as $calculatedTax) {
                 echo $calculatedTax->getTaxRate();
                 echo $calculatedTax->getTax();
             }
        }


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