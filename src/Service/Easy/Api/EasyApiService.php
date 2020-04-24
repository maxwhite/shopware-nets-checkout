<?php

namespace Nets\Checkout\Service\Easy\Api;

use GuzzleHttp\Exception\RequestException;
use Nets\Checkout\Service\Easy\Api\Client;
use Nets\Checkout\Service\Easy\Api\Exception\EasyApiException;
use Nets\Checkout\Service\Easy\Api\Payment;

/**
 * Description of EasyApiService
 *
 * @author mabe
 */
class EasyApiService {

    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';

    const ENDPOINT_TEST_CHARGES = 'https://test.api.dibspayment.eu/v1/charges/';
    const ENDPOINT_LIVE_CHARGES = 'https://api.dibspayment.eu/v1/charges/';

    const ENV_LIVE = 'live';
    const ENV_TEST = 'test';

    /**
     *
     * @var Nets\Checkout\Service\Easy\Api\Client
     */
    private $client;

    private $env;

    public function __construct(Client $client) {
      $this->client = $client;
      $this->setEnv(self::ENV_LIVE);
    }

    public function setEnv(string $env = self::ENV_LIVE) {
        $this->env = $env;
    }

    public function getEnv() {
        return $this->env;
    }

    public function setAuthorizationKey(string $key) {
      $this->client->setHeader('Authorization', str_replace('-', '', trim($key)));
    }

    /**
     * @param string $data
     * @return string
     * @throws EasyApiException
     */
    public function createPayment(string $data) {
      $this->client->setHeader('commercePlatformTag:', 'easy_shopify_inject');
      $url = $this->getCreatePaymentUrl();
      return $this->handleResponse($this->client->post($url, $data));
    }

    /**
     *
     * @param string $paymentId
     * @return \App\Payment
     */
    public function getPayment(string $paymentId) {
      $url = $this->getGetPaymentUrl($paymentId);
      return new Payment($this->handleResponse($this->client->get($url)));
    }

   public function updateReference(string $paymentId, string $data) {
      $url = $this->getUpdateReferenceUrl($paymentId);
      $this->client->put($url, $data, true);
      $this->handleResponse($this->client);
    }

    public function chargePayment(string $paymentId, string $data) {
      $url = $this->getChargePaymentUrl($paymentId);
      $this->client->post($url, $data);
      $this->handleResponse($this->client);
    }

    public function refundPayment(string $chargeId, string $data) {
      $url = $this->getRefundPaymentUrl($chargeId);
      $this->client->post($url, $data);
      $this->handleResponse($this->client);
    }

    public function voidPayment(string $paymentId, string $data) {
      $url = $this->getVoidPaymentUrl($paymentId);
      $this->client->post($url, $data);
      $this->handleResponse($this->client);
    }

    protected function handleResponse($response) {
        $statusCode = $response->getStatusCode();
        if (200 == $statusCode || 201 == $statusCode) {
              return (string)$response->getBody();
        }
    }

    protected function getCreatePaymentUrl() {
       return ($this->getEnv() == self::ENV_LIVE) ?
               self::ENDPOINT_LIVE : self::ENDPOINT_TEST;
    }

    protected function getGetPaymentUrl(string $paymentId) {
        return ($this->getEnv() == self::ENV_LIVE) ?
                self::ENDPOINT_LIVE . $paymentId:
                self::ENDPOINT_TEST . $paymentId;
    }

    public function getUpdateReferenceUrl(string $paymentId) {
        return ($this->getEnv() == self::ENV_LIVE) ?
                self::ENDPOINT_LIVE . $paymentId .'/referenceinformation':
                self::ENDPOINT_TEST . $paymentId .'/referenceinformation';
    }

    public function getChargePaymentUrl(string $paymentId) {
        return ($this->getEnv() == self::ENV_LIVE) ?
            self::ENDPOINT_LIVE . $paymentId . '/charges':
            self::ENDPOINT_TEST . $paymentId . '/charges';
    }

    public function getVoidPaymentUrl(string $paymentId) {
        return ($this->getEnv() == self::ENV_LIVE) ?
                self::ENDPOINT_LIVE . $paymentId . '/cancels':
                self::ENDPOINT_TEST . $paymentId . '/cancels';
    }

    public function getRefundPaymentUrl(string $chargeId) {
        return ($this->getEnv() == self::ENV_LIVE) ?
               self::ENDPOINT_LIVE_CHARGES . $chargeId . '/refunds':
               self::ENDPOINT_TEST_CHARGES . $chargeId . '/refunds';
    }
}
