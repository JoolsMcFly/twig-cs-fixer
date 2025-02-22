<?php

declare(strict_types=1);

namespace TwigCsFixer\Cache;

use InvalidArgumentException;
use RuntimeException;

final class CacheFileHandler implements CacheFileHandlerInterface
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function read(): ?Cache
    {
        if (!file_exists($this->file)) {
            return null;
        }

        $content = file_get_contents($this->file);
        if (false === $content) {
            return null;
        }

        try {
            return CacheEncoder::fromJson($content);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    public function write(Cache $cache): void
    {
        if (file_exists($this->file)) {
            if (is_dir($this->file)) {
                throw new RuntimeException(
                    sprintf('Cannot write cache file "%s" as the location exists as directory.', $this->file),
                );
            }

            if (!is_writable($this->file)) {
                throw new RuntimeException(
                    sprintf('Cannot write to file "%s" as it is not writable.', $this->file),
                );
            }
        } else {
            $dir = \dirname($this->file);

            if (!is_dir($dir)) {
                throw new RuntimeException(
                    sprintf('Directory of cache file "%s" does not exists.', $this->file),
                );
            }

            @touch($this->file);
            @chmod($this->file, 0666);
        }

        file_put_contents($this->file, CacheEncoder::toJson($cache));
    }
}
