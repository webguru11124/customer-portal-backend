<?php

namespace App\Repositories\WorldPay;

use Aptive\Worldpay\CredentialsRepository\Credentials\Credentials;
use Aptive\Worldpay\CredentialsRepository\CredentialsRepository;
use DOMDocument;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;

/**
 * Handle PestRoutes office related API calls.
 */
abstract class WorldPayBaseRepository
{
    public const REQUEST_TIMEOUT = 10;

    protected const TYPE_TRANSACTION = 'transaction';
    private const TYPE_SERVICE = 'service';

    private const SERVICE_XML_NS = 'https://services.elementexpress.com';
    private const TRANSACTION_XML_NS = 'https://transaction.elementexpress.com';

    private string $serviceUrl;
    private string $transactionUrl;

    public function __construct(
        private readonly CredentialsRepository $credentialsRepository,
        protected readonly Repository $config
    ) {
        $this->loadBaseUrl();
    }

    /**
     * @param string $type
     * @param string $url
     * @param string $xml
     * @param array<string, scalar> $queryParameters
     *
     * @return string
     */
    protected function post(string $type, string $url, string $xml, array $queryParameters = []): string
    {
        $fullUrl = $this->makeUrl($type, $url, $queryParameters);

        // actual call to worldpay - redirect
        /** @var Response $response */
        $response = Http::withBody($xml, 'text/xml; charset=utf-8')
            ->timeout($this->config->get('worldpay.timeout', self::REQUEST_TIMEOUT))
            ->post($fullUrl);

        return $response->body();
    }

    /**
     * @param int $branchId
     * @param string $type
     * @param string $mainNode
     * @param array<string, mixed> $data
     *
     * @return string
     */
    protected function preparePayload(int $branchId, string $type, string $mainNode, array $data): string
    {
        $payloadData = array_merge($this->getDefaultPayloadData($branchId), $data);

        return $this->createXMLString($type, $mainNode, $payloadData);
    }

    /**
     * Gets base URL.
     *
     * @param string $type
     *
     * @return string
     */
    private function getBaseUrl(string $type): string
    {
        return match ($type) {
            self::TYPE_SERVICE => $this->serviceUrl,
            self::TYPE_TRANSACTION => $this->transactionUrl,
            default => throw new InvalidArgumentException('World Pay transaction type not supported'),
        };
    }

    /**
     * Loads and treat base url.
     *
     * @return void
     */
    private function loadBaseUrl(): void
    {
        $this->serviceUrl = $this->config->get('worldpay.service_url');
        $this->transactionUrl = $this->config->get('worldpay.transaction_url');

        if (!Str::endsWith($this->serviceUrl, '/')) {
            $this->serviceUrl .= '/';
        }

        if (!Str::endsWith($this->transactionUrl, '/')) {
            $this->transactionUrl .= '/';
        }
    }

    /**
     * Get WorldPay's credentials.
     */
    private function getCredentials(int $branchId): Credentials
    {
        return $this->credentialsRepository->get($branchId);
    }

    /**
     * Create a FULL url based on the given endpoint.
     *
     * @param string $type
     * @param string $url
     * @param array<string, scalar> $queryParameters
     *
     * @return string
     */
    private function makeUrl(string $type, string $url, array $queryParameters = []): string
    {
        $queryString = http_build_query($queryParameters);

        return sprintf('%s%s?%s', $this->getBaseUrl($type), $url, $queryString);
    }

    /**
     * @param string $type
     * @param string $mainNode
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * @throws Exception
     */
    private function createXMLString(string $type, string $mainNode, array $data): string
    {
        $ns = $this->getXMLNameSpace($type);

        $xml = new SimpleXMLElement("<$mainNode xmlns=\"{$ns}\"/>");
        $this->array_to_xml($data, $xml);

        if (($xmlString = $xml->asXML()) === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Building XML string failed');
            // @codeCoverageIgnoreEnd
        }

        // TO PRETTY PRINT OUTPUT
        $domxml = new DOMDocument('1.0', 'utf-8');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = false;
        $domxml->loadXML($xmlString);

        if (($formattedXml = $domxml->saveXML()) === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Building formatted XML string failed');
            // @codeCoverageIgnoreEnd
        }

        return $formattedXml;
    }

    /**
     * @param array<string, mixed> $array
     * @param SimpleXMLElement $xml
     */
    private function array_to_xml(array $array, SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->array_to_xml($value, $xml->addChild($key));

                continue;
            }

            $xml->addChild($key, htmlspecialchars($value));
        }
    }

    /**
     * @param int $branchId
     *
     * @return array<string, array<string, string>>
     */
    private function getDefaultPayloadData(int $branchId): array
    {
        $credentials = $this->getCredentials($branchId);

        return [
            'Credentials' => [
                'AccountID' => $credentials->tokenization()->accountId(),
                'AccountToken' => $credentials->tokenization()->accountToken(),
                'AcceptorID' => $credentials->tokenization()->acceptorId(),
            ],
            'Application' => [
                'ApplicationID' => $this->config->get('worldpay.application.application_id'),
                'ApplicationName' => $this->config->get('worldpay.application.application_name'),
                'ApplicationVersion' => $this->config->get('worldpay.application.application_version'),
            ],
        ];
    }

    private function getXMLNameSpace(string $type): string
    {
        return match ($type) {
            self::TYPE_SERVICE => self::SERVICE_XML_NS,
            self::TYPE_TRANSACTION => self::TRANSACTION_XML_NS,
            default => throw new InvalidArgumentException('World Pay transaction type not supported'),
        };
    }
}
