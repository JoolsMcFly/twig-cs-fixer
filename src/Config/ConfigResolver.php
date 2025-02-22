<?php

declare(strict_types=1);

namespace TwigCsFixer\Config;

use Composer\InstalledVersions;
use LogicException;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use TwigCsFixer\Cache\CacheFileHandler;
use TwigCsFixer\Cache\CacheManagerInterface;
use TwigCsFixer\Cache\FileCacheManager;
use TwigCsFixer\Cache\Signature;
use TwigCsFixer\File\Finder as TwigCsFinder;
use TwigCsFixer\Ruleset\Ruleset;

/**
 * Resolve config from `.twig-cs-fixer.php` is provided
 */
final class ConfigResolver
{
    private const PACKAGE_NAME = 'vincentlanglet/twig-cs-fixer';

    private string $workingDir;

    public function __construct(string $workingDir)
    {
        $this->workingDir = $workingDir;
    }

    /**
     * @param string[] $paths
     *
     * @throws RuntimeException
     */
    public function resolveConfig(
        array $paths = [],
        ?string $configPath = null,
        bool $disableCache = false
    ): Config {
        $config = $this->getConfig($configPath);
        $config->setFinder($this->resolveFinder($config->getFinder(), $paths));

        if ($disableCache) {
            $config->setCacheFile(null);
            $config->setCacheManager(null);
        } else {
            $config->setCacheManager($this->resolveCacheManager(
                $config->getCacheManager(),
                $config->getCacheFile(),
                $config->getRuleset()
            ));
        }

        return $config;
    }

    /**
     * @throws RuntimeException
     */
    private function getConfig(?string $configPath = null): Config
    {
        if (null !== $configPath) {
            $configPath = $this->isAbsolutePath($configPath)
                ? $configPath
                : $this->workingDir.\DIRECTORY_SEPARATOR.$configPath;

            return $this->getConfigFromPath($configPath);
        }

        $defaultPath = $this->workingDir.\DIRECTORY_SEPARATOR.Config::DEFAULT_PATH;
        if (file_exists($defaultPath)) {
            return $this->getConfigFromPath($defaultPath);
        }

        return new Config();
    }

    /**
     * @throws RuntimeException
     */
    private function getConfigFromPath(string $configPath): Config
    {
        if (!file_exists($configPath)) {
            throw new RuntimeException(sprintf('Cannot find the config file "%s".', $configPath));
        }

        $config = require $configPath;
        if (!$config instanceof Config) {
            throw new RuntimeException(sprintf('The config file must return a "%s" object.', Config::class));
        }

        return $config;
    }

    /**
     * @param string[] $paths
     */
    private function resolveFinder(Finder $finder, array $paths): Finder
    {
        $nestedFinder = null;
        try {
            $nestedFinder = $finder->getIterator();
        } catch (LogicException $exception) {
            // Only way to know if in() method has not been called
        }

        if ([] === $paths) {
            if (null === $nestedFinder) {
                return $finder->in('./');
            }

            return $finder;
        }

        $files = [];
        $directories = [];
        foreach ($paths as $path) {
            if (is_file($path)) {
                $files[] = $path;
            } else {
                $directories[] = $path;
            }
        }

        if (null === $nestedFinder) {
            return $finder->in($directories)->append($files);
        }

        return TwigCsFinder::create()->in($directories)->append($files);
    }

    private function resolveCacheManager(
        ?CacheManagerInterface $cacheManager,
        ?string $cacheFile,
        Ruleset $ruleset
    ): ?CacheManagerInterface {
        if (null !== $cacheManager) {
            return $cacheManager;
        }

        if (null === $cacheFile) {
            return null;
        }

        $cacheFile = $this->isAbsolutePath($cacheFile)
            ? $cacheFile
            : $this->workingDir.\DIRECTORY_SEPARATOR.$cacheFile;

        return new FileCacheManager(
            new CacheFileHandler($cacheFile),
            new Signature(
                \PHP_VERSION,
                InstalledVersions::getReference(self::PACKAGE_NAME) ?? '0',
                $ruleset
            )
        );
    }

    private function isAbsolutePath(string $path): bool
    {
        return '' !== $path && (
            '/' === $path[0]
            || '\\' === $path[0]
            || 1 === preg_match('#^[a-zA-Z]:\\\\#', $path)
        );
    }
}
