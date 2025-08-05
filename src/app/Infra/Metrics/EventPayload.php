<?php

declare(strict_types=1);

namespace App\Infra\Metrics;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use JsonSerializable;

final class EventPayload implements JsonSerializable
{
    public readonly DateTimeInterface $dateCreated;
    public readonly SourceName $sourceName;
    public readonly ProductName $productName;

    /**
     * @param TrackedEventName $eventName
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly TrackedEventName $eventName,
        public readonly array $data = [],
    ) {
        $this->dateCreated = Carbon::now();
        $this->sourceName = SourceName::Backend;
        $this->productName = ProductName::CustomerPortal;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'date_created' => $this->dateCreated->format(DateTimeInterface::RFC3339_EXTENDED),
            'from' => $this->sourceName->value,
            'product' => $this->productName->value,
            'name' => $this->eventName->value,
            'data' => $this->data,
        ];
    }
}
