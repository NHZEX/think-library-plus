<?php

declare(strict_types=1);

namespace Zxin\Think\Annotation\Core;

final class DumpCommand
{
    public const FORMAT_TEXT = 'text';
    public const FORMAT_JSON = 'json';

    private const SUPPORTS = '--format=json,--verbose,--quiet,--help';

    private string $format = self::FORMAT_TEXT;
    private bool $verbose = false;
    private bool $quiet = false;
    private bool $help = false;

    /**
     * @param list<string> $argv
     */
    public static function fromArgv(array $argv): self
    {
        $self = new self();

        foreach (array_slice($argv, 1) as $arg) {
            if ('--json' === $arg || '--format=json' === $arg) {
                $self->format = self::FORMAT_JSON;
                continue;
            }
            if (str_starts_with($arg, '--format=')) {
                $format = substr($arg, \strlen('--format='));
                if (self::FORMAT_JSON === $format || self::FORMAT_TEXT === $format) {
                    $self->format = $format;
                    continue;
                }
                throw new \InvalidArgumentException("Unsupported output format: {$format}");
            }
            if ('--verbose' === $arg || '-v' === $arg) {
                $self->verbose = true;
                continue;
            }
            if ('--quiet' === $arg || '-q' === $arg) {
                $self->quiet = true;
                continue;
            }
            if ('--help' === $arg || '-h' === $arg) {
                $self->help = true;
                continue;
            }

            throw new \InvalidArgumentException("Unknown option: {$arg}");
        }

        return $self;
    }

    public static function default(): self
    {
        return new self();
    }

    /**
     * @param list<string> $argv
     */
    public static function forErrorOutput(array $argv): self
    {
        $self = new self();

        foreach (array_slice($argv, 1) as $arg) {
            if ('--json' === $arg || '--format=json' === $arg) {
                $self->format = self::FORMAT_JSON;
                continue;
            }
            if ('--verbose' === $arg || '-v' === $arg) {
                $self->verbose = true;
                continue;
            }
            if ('--quiet' === $arg || '-q' === $arg) {
                $self->quiet = true;
            }
        }

        return $self;
    }

    public function isJson(): bool
    {
        return self::FORMAT_JSON === $this->format;
    }

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    public function shouldShowHelp(): bool
    {
        return $this->help;
    }

    public function help(string $command, string $title): string
    {
        return <<<HELP
            Usage:
              {$command} [--format=json|text] [--verbose] [--quiet]

            {$title}

            Options:
              --format=json, --json  Output compact JSON only.
              --format=text          Output text summary. This is the default.
              --verbose, -v          Include scanned item details.
              --quiet, -q            Suppress stdout on success.
              --help, -h             Show this help.

            Examples:
              {$command}
              {$command} --json
              {$command} --verbose
            HELP . PHP_EOL;
    }

    /**
     * @param array<string, scalar|null> $summary
     * @param list<array<string, mixed>> $details
     * @param list<string> $warnings
     */
    public function render(
        string $command,
        string $title,
        string $status,
        bool $changed,
        string $dumpFile,
        float $elapsedMs,
        array $summary,
        array $details = [],
        array $warnings = []
    ): string {
        if ($this->quiet) {
            return '';
        }

        if ($this->isJson()) {
            $payload = [
                'command' => $command,
                'status' => $status,
                'changed' => $changed,
                'dump_file' => $dumpFile,
                'elapsed_ms' => round($elapsedMs, 3),
                'summary' => $summary,
                'warnings' => $warnings,
            ];
            if ($this->verbose) {
                $payload['details'] = $details;
            }
            return json_encode($payload, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        $detailMode = $this->verbose ? 'verbose' : 'summary';
        $lines = [
            "# dump-output v1 command={$command} format=text detail={$detailMode} supports=" . self::SUPPORTS,
            $this->summaryLine($title, $status, $changed, $dumpFile, $elapsedMs, $summary),
        ];

        foreach ($warnings as $warning) {
            $lines[] = "warning={$warning}";
        }

        if ($this->verbose && [] !== $details) {
            $lines[] = 'details:';
            foreach ($details as $detail) {
                $lines[] = '  - ' . $this->formatKeyValues($detail);
            }
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, scalar|null> $summary
     */
    private function summaryLine(
        string $title,
        string $status,
        bool $changed,
        string $dumpFile,
        float $elapsedMs,
        array $summary
    ): string {
        return \sprintf(
            '%s: %s changed=%s file=%s elapsed=%sms %s',
            $title,
            $status,
            $changed ? 'yes' : 'no',
            $dumpFile,
            (string) round($elapsedMs, 3),
            $this->formatKeyValues($summary)
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function formatKeyValues(array $values): string
    {
        $items = [];
        foreach ($values as $key => $value) {
            if (null === $value) {
                continue;
            }
            if (\is_bool($value)) {
                $value = $value ? 'yes' : 'no';
            } elseif (\is_array($value)) {
                $value = implode(',', array_map(static fn ($item): string => (string) $item, $value));
            }
            $items[] = "{$key}={$value}";
        }

        return implode(' ', $items);
    }
}
