<?php declare(strict_types=1);

namespace Nets\Checkout;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;

class Checkout extends Plugin
{
    public function activate(ActivateContext $context): void
    {
        error_log('plugin has ');
    }
}
