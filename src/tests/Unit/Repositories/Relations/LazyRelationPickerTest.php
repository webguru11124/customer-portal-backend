<?php

namespace Tests\Unit\Repositories\Relations;

use App\Interfaces\Repository\ExternalRepository;
use App\Repositories\Relations\LazyRelationPicker;
use PHPUnit\Framework\TestCase;

class LazyRelationPickerTest extends TestCase
{
    public function test_it_pickes_unique_values(): void
    {
        $values = [];

        for ($i = 1; $i <= 100; $i++) {
            $values[$i] = random_int(1, 5);
        }

        $picker = new LazyRelationPicker(
            $this->createMock(ExternalRepository::class),
            'anyForeignKey'
        );

        foreach ($values as $value) {
            $picker->pick($value);
        }

        $result = $picker->getValues();
        $result = array_values($result);
        sort($result);

        $expected = array_unique($values, SORT_REGULAR);
        $expected = array_values($expected);
        sort($expected);

        self::assertEquals($expected, $result);
    }
}
