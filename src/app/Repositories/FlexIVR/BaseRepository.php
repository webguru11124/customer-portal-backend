<?php

declare(strict_types=1);

namespace App\Repositories\FlexIVR;

use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;
use JsonException;

abstract class BaseRepository
{
    public const LOG_REQUEST_MESSAGE = 'ivr-api.request: %s';
    public const LOG_RESPONSE_MESSAGE = 'ivr-api.response: %s';

    protected string $baseUrl;

    /**
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * @param Client $guzzleClient
     * @param Repository $config
     * @param ApiLogger $logger
     */
    public function __construct(
        private readonly Client $guzzleClient,
        private readonly Repository $config,
        private readonly ApiLogger $logger,
    ) {
        $this->baseUrl = $this->config->get('flex_ivr.api_url');
        $this->headers = [
            'X-API-KEY' => $this->config->get('flex_ivr.api_key'),
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return object
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    final protected function sendGetRequest(string $endpoint, array $data): object
    {
        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            httpMethod: 'GET',
            parameters: $data
        );

        $response = $this->guzzleClient
            ->get($this->baseUrl . '/' . $endpoint, [
                'headers' => $this->headers,
                'query' => $data
            ]);
        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode($responseContent, false, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return object
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    final protected function sendPutRequest(string $endpoint, array $data): object
    {
        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
            httpMethod: 'PUT',
            parameters: $data
        );

        $response = $this->guzzleClient
            ->put($this->baseUrl . '/' . $endpoint, [
                'headers' => $this->headers,
                'json' => $data
            ]);
        $responseContent = $response->getBody()->getContents();

        $this->logger->logExternalResponse(
            message: sprintf(self::LOG_RESPONSE_MESSAGE, $endpoint),
            headers: $response->getHeaders(),
            body: $responseContent,
            statusCode: $response->getStatusCode()
        );

        return json_decode($responseContent, false, 512, JSON_THROW_ON_ERROR);
    }
}
