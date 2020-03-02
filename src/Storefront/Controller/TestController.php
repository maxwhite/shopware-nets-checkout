<?php declare(strict_types=1);

namespace Nets\Checkout\Storefront\Controller;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;

use Nets\Checkout\Service\Easy\CheckoutService;

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

    public function __construct(EntityRepositoryInterface $orderRepository,
                                \Psr\Log\LoggerInterface $logger,
                                \Nets\Checkout\Service\Easy\CheckoutService $checkout,
                                SystemConfigService $systemConfigService

    ) {
        $this->orderRepository = $orderRepository;
        $this->context = Context::createDefaultContext();
        $this->logger = $logger;
        $this->checkout = $checkout;
        $this->systemConfigService = $systemConfigService;
    }
    
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/nets/test", name="nets.test.controller", options={"seo"="false"}, methods={"GET"})
     */
    public function clearCart(SalesChannelContext $context)
    {
       $criteria = new Criteria();
       $criteria->addFilter(new EqualsFilter('orderNumber', 10022))
            ->addAssociation('transactions');
       $order = $this->orderRepository->search($criteria, $this->context)->first();
//       error_log(serialize($order));
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

        var_dump( $_REQUEST );
        exit;
    }
}