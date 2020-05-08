import template from './module/extension/nets-checkout/sw-order.html.twig';

Shopware.Component.override('sw-order-user-card', {
    template,

    methods: {
        getTransactionId(currentOrder) {
            var transaction = currentOrder.transactions.first();
            var result = false;
            if(transaction.hasOwnProperty('customFields') && transaction['customFields']) {
                if(transaction.customFields.hasOwnProperty('nets_easy_payment_details') &&
                    transaction.customFields['nets_easy_payment_details']) {
                    result = transaction.customFields.nets_easy_payment_details.transaction_id;
                }
            }
            return result;
        }
    }
});
