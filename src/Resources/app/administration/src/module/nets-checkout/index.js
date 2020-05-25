const { Module } = Shopware;

import './extension/sw-order';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

Module.register('nets-checkout', {
    type: 'plugin',
    name: 'NetsCheckout',
    title: 'payone-payment.general.mainMenuItemGeneral',
    description: 'payone-payment.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    icon: 'default-action-settings',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
});
