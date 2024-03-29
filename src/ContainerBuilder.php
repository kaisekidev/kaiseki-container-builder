<?php

declare(strict_types=1);

namespace Kaiseki\ContainerBuilder;

use InvalidArgumentException;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use Laminas\Di\ConfigProvider;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;

use function is_dir;

/**
 * @phpstan-type Providers list<class-string|callable(): array<array-key, mixed>|PhpFileProvider>
 */
final class ContainerBuilder
{
    /** @var ContainerInterface|null */
    private ?ContainerInterface $container = null;

    /** @phpstan-var Providers */
    private array $providers;

    /** @var string|null */
    private ?string $configFolder = null;

    /** @var non-empty-string|null */
    private ?string $cachedConfigFile = null;

    /**
     * @phpstan-param Providers $providers Array of providers. These may be callables, or string values
     *                                         representing classes that act as providers. If the latter, they must
     *                                     be instantiable without constructor arguments.
     *
     * @param ?array  $providers
     * @param ?string $env
     */
    public function __construct(
        ?array $providers = null,
        private readonly ?string $env = null
    ) {
        $this->providers = $providers === null ? [] : [
            ConfigProvider::class,
            ...$providers,
        ];
    }

    /**
     * @param string $configFolder Configuration folder; Files that end with .global.php or .local.php
     *                             are loaded into the configuration.
     */
    public function withConfigFolder(string $configFolder): self
    {
        if (!is_dir($configFolder)) {
            throw new InvalidArgumentException("$configFolder is not a directory");
        }
        $clone = clone $this;
        $clone->container = null;
        $clone->configFolder = $configFolder;
        $pattern = $this->env !== null ? '/{{,*.}global,{,*.}' . $this->env . ',{,*.}testing}.php' : '/{{,*.}global,{,*.}testing}.php';
        $clone->providers[] = new PhpFileProvider($clone->configFolder . $pattern);

        return $clone;
    }

    /**
     * @param non-empty-string $cachedConfigFile configuration cache file; config is loaded from this file if present,
     *                                           and written to it if not
     */
    public function withCachedConfigFile(string $cachedConfigFile): self
    {
        $clone = clone $this;
        $clone->container = null;
        $clone->cachedConfigFile = $cachedConfigFile;
        $clone->providers[] = new ArrayProvider([ConfigAggregator::ENABLE_CACHE => true]);

        return $clone;
    }

    public function build(): ContainerInterface
    {
        if ($this->container !== null) {
            return $this->container;
        }

        $config = $this->buildConfig();
        $this->container = $this->buildContainer($config);

        return $this->container;
    }

    private function buildConfig(): ConfigAggregator
    {
        return new ConfigAggregator($this->providers, $this->cachedConfigFile);
    }

    private function buildContainer(ConfigAggregator $config): ContainerInterface
    {
        $config = $config->getMergedConfig();
        $dependencies = $config['dependencies'] ?? [];
        $dependencies['services']['config'] = $config;

        return new ServiceManager($dependencies);
    }
}
