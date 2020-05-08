<?php declare(strict_types=1);

namespace Nets\Checkout\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Context;
use Nets\Checkout\Service\ConfigService;
use Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;
use Nets\Checkout\Service\Easy\Api\EasyApiService;

class PaymentController extends StorefrontController
{
    private $orderRepository;

    /** @var Context $context */
    private $context;

    /**
     * @var SystemConfigService
     */
    public $systemConfigService;

    private $configService;

    private $logger;

    private $checkout;

    private $easyApiService;

    private $kernel;

    public function __construct(EntityRepositoryInterface $orderRepository,
                                \Psr\Log\LoggerInterface $logger,
                                \Nets\Checkout\Service\Easy\CheckoutService $checkout,
                                SystemConfigService $systemConfigService,
                                EasyApiService $easyApiService,
                                \Symfony\Component\HttpKernel\KernelInterface $kernel,
                                ConfigService $configService

    ) {
        $this->orderRepository = $orderRepository;
        $this->context = Context::createDefaultContext();
        $this->logger = $logger;
        $this->checkout = $checkout;
        $this->systemConfigService = $systemConfigService;
        $this->easyApiService = $easyApiService;
        $this->kernel = $kernel;
        $this->configService = $configService;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/nets/test", name="nets.test.controller", options={"seo"="false"}, methods={"GET"})
     */
    public function clearCart(SalesChannelContext $context)
    {


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
