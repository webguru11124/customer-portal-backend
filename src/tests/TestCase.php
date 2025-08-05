<?php

namespace Tests;

use App\Http\Middleware\Authorize;
use App\Http\Middleware\EnsureValidAccountNumber;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use ReflectionClass;
use ReflectionObject;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected const URL_PREFIX = 'customer/';

    protected static function callProtectedMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    private function clearProperties(): void
    {
        foreach ((new ReflectionObject($this))->getProperties() as $property) {
            if (!$property->isStatic() && get_class($this) === $property->getDeclaringClass()->getName()) {
                unset($this->{$property->getName()});
            }
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->clearProperties();
        Container::setInstance();
        gc_collect_cycles();
    }

    protected function disableAuthorization(): static
    {
        return $this->withoutMiddleware([
            Authorize::class,
            \Auth0\Laravel\Http\Middleware\Stateless\Authorize::class,
            EnsureValidAccountNumber::class,
        ]);
    }

    public function assertErrorResponse(TestResponse $response, int $status): TestResponse
    {
        return $response->assertStatus($status)->assertJsonStructure(['errors']);
    }
}
