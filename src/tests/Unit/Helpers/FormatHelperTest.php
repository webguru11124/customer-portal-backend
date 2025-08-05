<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FormatHelper;
use PHPUnit\Framework\TestCase;

class FormatHelperTest extends TestCase
{
    /**
     * @dataProvider stringToHashtagDataProvider
     */
    public function test_if_transforms_string_to_hashtag(string|array $input, string|array $expectedResult): void
    {
        $result = FormatHelper::stringToHashtag($input);

        self::assertEquals($expectedResult, $result);
    }

    public function stringToHashtagDataProvider(): iterable
    {
        yield [
            'testString',
            '{testString}',
        ];
        yield [
            ['testString1', 'testString2'],
            ['{testString1}', '{testString2}'],
        ];
    }
}
