<?php

declare(strict_types=1);

namespace App\Repositories\FlexIVR;

use App\Logging\ApiLogger;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;

class WrappedBaseRepository extends BaseRepository
{
    public function __construct(
        private readonly Client $guzzleClient,
        private readonly Repository $config,
        private readonly ApiLogger $logger,
    ) {
        parent::__construct(
            guzzleClient: $this->guzzleClient,
            config: $this->config,
            logger: $this->logger
        );

        $wrapperUrl = $this->config->get('flex_ivr.api_wrapper_url');
        $wrapperKeyCheck = $this->config->get('flex_ivr.api_wrapper_key_check');

        if (null !== $wrapperUrl && null !== $wrapperKeyCheck) {
            $this->baseUrl = $wrapperUrl;

            $apiKey = $this->config->get('flex_ivr.api_key');
            $this->headers = [
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
                'X-Api-Key-Check' => md5(sprintf('%s%s', $apiKey, $wrapperKeyCheck)),
            ];
        }
    }
}
