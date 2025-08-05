<?php

declare(strict_types=1);

namespace App\Cache;

use App\Exceptions\Cache\CachedWrapperException;
use App\Helpers\FormatHelper;
use Illuminate\Support\Facades\Cache;

abstract class AbstractCachedWrapper
{
    public const HASH_ALGORITHM = 'md5';

    protected mixed $wrapped;
    /** @var string[] */
    protected array $tags = [];

    /**
     * @param string[] $tags
     */
    public function tags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    protected function resetTags(): static
    {
        $this->tags = [];

        return $this;
    }

    protected function cached(string $methodName, mixed ...$methodArguments): mixed
    {
        $tags = array_merge(
            [static::getHashTag($methodName)],
            $this->tags
        );

        $this->resetTags();

        return Cache::tags($tags)->remember(
            self::buildKey($methodName, $methodArguments),
            $this->getCacheTtl($methodName),
            fn () => $this->getWrapped()->$methodName(...$methodArguments)
        );
    }

    /**
     * @param string $methodName
     * @param mixed[] $methodArguments
     *
     * @return string
     */
    public static function buildKey(string $methodName, array $methodArguments): string
    {
        $hashTag = self::getHashTag($methodName);

        return $hashTag . '.' . hash(self::HASH_ALGORITHM, $methodName . serialize($methodArguments));
    }

    public static function getHashTag(string $methodName): string
    {
        /** @var string $hashTag */
        $hashTag = FormatHelper::stringToHashtag(
            hash(self::HASH_ALGORITHM, static::class . '::' . $methodName)
        );

        return $hashTag;
    }

    /**
     * Returns wrapped object.
     */
    protected function getWrapped(): mixed
    {
        $selfImplementations = class_implements($this);
        $wrappedImplementations = class_implements($this->wrapped);

        if (!empty($selfImplementations) && !empty($wrappedImplementations) &&
            $selfImplementations !== array_intersect($wrappedImplementations, $selfImplementations)
        ) {
            throw CachedWrapperException::wrappedClassImplementationMismatch();
        }

        return $this->wrapped;
    }

    abstract protected function getCacheTtl(string $methodName): int;
}
