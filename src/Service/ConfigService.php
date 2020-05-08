<?php
namespace Nets\Checkout\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ConfigService
{

    const CONFIG_PREFIX = 'NetsCheckout.config.';

   /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * ConfigService constructor.
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return array|mixed|null|string
     */
    public function getSecretKey(SalesChannelContext $salesChannelContext) {
        $env = 'testSecretKey';
        if('live' == $this->getEnvironment($salesChannelContext)) {
            $env = 'liveSecretKey';
        }

        return $this->systemConfigService->get( self::CONFIG_PREFIX . $env, $salesChannelContext->getSalesChannel()->getId());
   }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return array|mixed|null|string
     */
    public function getEnvironment(SalesChannelContext $salesChannelContext) {
        return $this->systemConfigService->get( self::CONFIG_PREFIX  . 'enviromnent', $salesChannelContext->getSalesChannel()->getId());
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return array|mixed|null|string
     */
    public function getLanguage(SalesChannelContext $salesChannelContext) {
        return $this->systemConfigService->get( self::CONFIG_PREFIX  . 'language', $salesChannelContext->getSalesChannel()->getId());
    }

}
