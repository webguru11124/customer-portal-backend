<?php

declare(strict_types=1);

namespace App\Repositories\CleoCrm;

use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;

class CleoCrmBaseRepository
{
    public const LOG_REQUEST_MESSAGE = 'cleo-crm-api.request: %s';
    public const LOG_RESPONSE_MESSAGE = 'cleo-crm-api.response: %s';

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
        $this->baseUrl = $this->config->get('cleo_crm.api_url');
        $this->headers = [
            'Aptive-Api-Account-ID' => $this->config->get('cleo_crm.api_account_id'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
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
     */
    protected function sendGetRequest(string $endpoint, array $data = []): array|object
    {
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
}
