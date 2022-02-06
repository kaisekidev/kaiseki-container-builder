<?php

declare(strict_types=1);

namespace Kaiseki\Test\Functional\ContainerBuilder;

use Kaiseki\ContainerBuilder\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class ContainerBuilderTest extends TestCase
{
    public function testWithConfigFolderReadsConfigFromPhpFiles(): void
    {
        $builder = new ContainerBuilder();
        $container = $builder->withConfigFolder(__DIR__ . '/../config')->build();

        $config = $container->get('config');
        self::assertIsArray($config);
        self::assertSame('bar', $config['foo']);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }
}
