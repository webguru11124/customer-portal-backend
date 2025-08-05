<?php

declare(strict_types=1);

namespace App\Repositories\PlanBuilder;

use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Config\Repository;
use JsonException;

class BaseRepository
{
    public const LOG_REQUEST_MESSAGE = 'plan-builder-api.request: %s';
    public const LOG_RESPONSE_MESSAGE = 'plan-builder-api.response: %s';

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
        $this->baseUrl = $this->config->get('planbuilder.api_url');
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->config->get('planbuilder.api_key'),
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @param string $endpoint
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|object
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    protected function sendGetRequest(string $endpoint, array $data = []): array|object
    {
        $this->logger->logExternalRequest(
            message: sprintf(self::LOG_REQUEST_MESSAGE, $endpoint),
            uri: $endpoint,
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

        return json_decode(json: $responseContent, associative: false, flags: JSON_THROW_ON_ERROR);
    }
}
