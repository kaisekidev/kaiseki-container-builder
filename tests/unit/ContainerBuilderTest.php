<?php

declare(strict_types=1);

namespace Kaiseki\Test\Unit\ContainerBuilder;

use InvalidArgumentException;
use Kaiseki\ContainerBuilder\ContainerBuilder;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Di\ConfigInterface as LamnasDiConfigInterface;
use PHPUnit\Framework\TestCase;

class ContainerBuilderTest extends TestCase
{
    public function testContainerConfigKeyIsSet(): void
    {
        $builder = new ContainerBuilder([]);

        self::assertIsArray($builder->build()->get('config'));
    }

    public function testWithCachedConfigFileEnablesCache(): void
    {
        $builder = (new ContainerBuilder([]))->withCachedConfigFile('/cache/config.php');

        $config = $builder->build()->get('config');

        self::assertIsArray($config);
        self::assertArrayHasKey(ConfigAggregator::ENABLE_CACHE, $config);
        self::assertTrue($config[ConfigAggregator::ENABLE_CACHE]);
    }

    public function testThrowsExceptionWhenConfigFolderDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ContainerBuilder([]))->withConfigFolder('/does/not/exist')->build();
    }

    public function testBuildContainerOnlyOnce(): void
    {
        $builder = new ContainerBuilder([]);
        $containerA = $builder->build();

        $containerB = $builder->build();

        self::assertSame($containerA, $containerB);
    }

    public function testLaminasDiConfigProviderIsAdded(): void
    {
        $builder = new ContainerBuilder([]);

        self::assertTrue($builder->build()->has(LamnasDiConfigInterface::class));
    }

    /**
     * @dataProvider instanceIsClonedCases
     * @param callable(ContainerBuilder):ContainerBuilder $modifyBuilder
     */
    public function testInstanceIsCloned(callable $modifyBuilder): void
    {
        $builderA = new ContainerBuilder([]);

        $builderB = ($modifyBuilder)($builderA);

        self::assertNotSame($builderA, $builderB);
    }

    /**
     * @return iterable<string, array{callable(ContainerBuilder):ContainerBuilder}>
     */
    public function instanceIsClonedCases(): iterable
    {
        yield 'withConfigFolder' => [
            fn(ContainerBuilder $builder): ContainerBuilder => $builder->withConfigFolder('/')
        ];
        yield 'withCachedConfigFile' => [
            fn(ContainerBuilder $builder): ContainerBuilder => $builder->withCachedConfigFile('cached-config.php')
        ];
    }
}
