<?php

declare(strict_types=1);

namespace App\Repositories\AptivePayment;

use App\Exceptions\Account\CleoCrmAccountNotFoundException;
use App\Interfaces\Repository\CleoCrmRepository;
use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;

class AptivePaymentBaseRepository
{
    public const LOG_REQUEST_MESSAGE = 'payment-service-api.request: %s';
    public const LOG_RESPONSE_MESSAGE = 'payment-service-api.response: %s';

    protected string $baseUrl;

    /**
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * @param Client $guzzleClient
     * @param Repository $config
     * @param ApiLogger $logger
     * @param CleoCrmRepository $cleoCrmRepository
     */
    public function __construct(
        private readonly Client $guzzleClient,
        private readonly Repository $config,
        private readonly ApiLogger $logger,
        private readonly CleoCrmRepository $cleoCrmRepository,
    ) {
        $this->baseUrl = $this->config->get('payment.api_url');
        $this->headers = [
            'Api-Key' => $this->config->get('payment.api_key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|object
     *
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    protected function sendGetRequest(string $endpoint, array $data = []): array|object
    {
        $this->convertCustomerIdToCleoCrmAccountId($data);

        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            parameters: $data
        );

        $response = $this->guzzleClient->get(
            $this->baseUrl . '/' . $endpoint,
            [
                'headers' => $this->headers,
                'query' => $data,
            ]
        );
        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode(json: $responseContent, associative: false, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|object
     *
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    protected function sendPostRequest(string $endpoint, array $data = []): array|object
    {
        $this->convertCustomerIdToCleoCrmAccountId($data);

        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            httpMethod: 'POST',
            parameters: $data,
        );

        $response = $this->guzzleClient->post(
            $this->baseUrl . '/' . $endpoint,
            [
                'headers' => $this->headers,
                'json' => $data,
            ]
        );

        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode(json: $responseContent, associative: false, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|object
     *
     * @throws GuzzleException
     * @throws \JsonException
     * @throws CleoCrmAccountNotFoundException
     */
    protected function sendPatchRequest(string $endpoint, array $data = []): array|object
    {
        $this->convertCustomerIdToCleoCrmAccountId($data);

        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            httpMethod: 'PATCH',
            parameters: $data,
        );

        $response = $this->guzzleClient->patch(
            $this->baseUrl . '/' . $endpoint,
            [
                'headers' => $this->headers,
                'json' => $data,
            ]
        );
        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode(json: $responseContent, associative: false, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $endpoint
     *
     * @return array<string, mixed>|object
     *
     * @throws GuzzleException
     * @throws \JsonException
     */
    protected function sendDeleteRequest(string $endpoint): array|object
    {
        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            httpMethod: 'DELETE',
        );

        $response = $this->guzzleClient->delete(
            $this->baseUrl . '/' . $endpoint,
            [
                'headers' => $this->headers,
            ]
        );
        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode(json: $responseContent, associative: false, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string|mixed> $data
     *
     * @throws CleoCrmAccountNotFoundException
     */
    private function convertCustomerIdToCleoCrmAccountId(array &$data): void
    {
        if (!array_key_exists('customer_id', $data)) {
            return;
        }

        $account = $this->cleoCrmRepository->getAccount($data['customer_id']);

        if (null === $account) {
            throw new CleoCrmAccountNotFoundException();
        }

        $data['account_id'] = $account->id;
        unset($data['customer_id']);
    }
}
