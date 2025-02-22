<?php

declare(strict_types=1);

namespace TwigCsFixer\Report;

use InvalidArgumentException;

/**
 * Report contains all violations with stats.
 */
final class Report
{
    public const MESSAGE_TYPE_NOTICE = 'NOTICE';
    public const MESSAGE_TYPE_WARNING = 'WARNING';
    public const MESSAGE_TYPE_ERROR = 'ERROR';
    public const MESSAGE_TYPE_FATAL = 'FATAL';

    /**
     * @var array<string, list<SniffViolation>>
     */
    private array $messagesByFiles = [];

    /**
     * @var list<string>
     */
    private array $files = [];

    private int $totalNotices = 0;

    private int $totalWarnings = 0;

    private int $totalErrors = 0;

    public function addMessage(SniffViolation $sniffViolation): self
    {
        $filename = $sniffViolation->getFilename();
        if (!\in_array($filename, $this->getFiles(), true)) {
            throw new InvalidArgumentException(
                sprintf('The file "%s" is not handled by this report.', $filename)
            );
        }

        // Update stats
        switch ($sniffViolation->getLevel()) {
            case SniffViolation::LEVEL_NOTICE:
                $this->totalNotices++;
                break;
            case SniffViolation::LEVEL_WARNING:
                $this->totalWarnings++;
                break;
            case SniffViolation::LEVEL_ERROR:
            case SniffViolation::LEVEL_FATAL:
                $this->totalErrors++;
                break;
        }

        $this->messagesByFiles[$filename][] = $sniffViolation;

        return $this;
    }

    /**
     * @return array<string, list<SniffViolation>>
     */
    public function getMessagesByFiles(?string $level = null): array
    {
        if (null === $level) {
            return $this->messagesByFiles;
        }

        return array_map(static fn (array $messages): array => array_values(
            array_filter(
                $messages,
                static fn (SniffViolation $message): bool => $message->getLevel() >= SniffViolation::getLevelAsInt($level)
            )
        ), $this->messagesByFiles);
    }

    public function addFile(string $file): void
    {
        $this->files[] = $file;
        $this->messagesByFiles[$file] = [];
    }

    /**
     * @return list<string>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function getTotalFiles(): int
    {
        return \count($this->files);
    }

    public function getTotalNotices(): int
    {
        return $this->totalNotices;
    }

    public function getTotalWarnings(): int
    {
        return $this->totalWarnings;
    }

    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }
}
