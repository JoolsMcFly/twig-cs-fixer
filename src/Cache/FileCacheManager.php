<?php

declare(strict_types=1);

namespace TwigCsFixer\Cache;

use BadMethodCallException;
use RuntimeException;

/**
 * Class supports caching information about state of fixing files.
 *
 * Cache is supported only for phar version and version installed via composer.
 *
 * File will be processed by PHP CS Fixer only if any of the following conditions is fulfilled:
 *  - cache is corrupt
 *  - fixer version changed
 *  - rules changed
 *  - file is new
 *  - file changed
 */
final class FileCacheManager implements CacheManagerInterface
{
    private CacheFileHandlerInterface $handler;

    private Signature $signature;

    private Cache $cache;

    private Directory $cacheDirectory;

    public function __construct(CacheFileHandlerInterface $handler, Signature $signature)
    {
        $this->handler = $handler;
        $this->signature = $signature;
        $this->cacheDirectory = new Directory(\dirname($handler->getFile()));

        $this->readCache();
    }

    /**
     * @throws RuntimeException
     */
    public function __destruct()
    {
        $this->writeCache();
    }

    /**
     * This class is not intended to be serialized,
     * and cannot be deserialized (see __wakeup method).
     */
    public function __sleep(): array
    {
        throw new BadMethodCallException(sprintf('Cannot serialize %s.', self::class));
    }

    /**
     * Disable the deserialization of the class to prevent attacker executing
     * code by leveraging the __destruct method.
     *
     * @see https://owasp.org/www-community/vulnerabilities/PHP_Object_Injection
     */
    public function __wakeup(): void
    {
        throw new BadMethodCallException(sprintf('Cannot unserialize %s.', self::class));
    }

    public function needFixing(string $file, string $fileContent): bool
    {
        $file = $this->cacheDirectory->getRelativePathTo($file);

        return !$this->cache->has($file) || $this->cache->get($file) !== $this->calcHash($fileContent);
    }

    public function setFile(string $file, string $fileContent): void
    {
        $file = $this->cacheDirectory->getRelativePathTo($file);

        $hash = $this->calcHash($fileContent);

        $this->cache->set($file, $hash);
    }

    private function readCache(): void
    {
        $cache = $this->handler->read();

        if (null === $cache || !$this->signature->equals($cache->getSignature())) {
            $cache = new Cache($this->signature);
        }

        $this->cache = $cache;
    }

    /**
     * @throws RuntimeException
     */
    private function writeCache(): void
    {
        $this->handler->write($this->cache);
    }

    private function calcHash(string $content): string
    {
        return md5($content);
    }
}
