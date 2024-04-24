<?php

declare(strict_types=1);

namespace Zxin\Think\Model\ModelGenerator\Command;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\console\Table;
use Zxin\Think\Model\ModelGenerator\ModelGeneratorService;

/**
 * 批量创建数据结构到模型
 *
 * @template SingleItemOptions of array{table: array<string>, dir: string, namespace: string}
 */
class ModelToolCommand extends Command
{
    public function configure(): void
    {
        $this->setName('model:generator')
            ->addOption('try-run', null, Option::VALUE_NONE, '尝试运行')
            ->addOption('connect', 'c', Option::VALUE_OPTIONAL, '指定连接', '')
            ->addArgument('table', Argument::OPTIONAL, '指定表');
    }

    public function execute(Input $input, Output $output): int
    {
        $logger = new class ($output) extends AbstractLogger {
            public function __construct(
                private Output $output,
            ) {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $message = "[{$level}] {$message}";
                $message = match ($level) {
                    LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG => "<info>{$message}</info>",
                    LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR => "<error>{$message}</error>",
                    LogLevel::WARNING => "<warning>{$message}</warning>",
                    default => $message,
                };

                $this->output->writeln($message);
            }
        };

        $generatorService = new ModelGeneratorService($logger);

        if (!$generatorService->loadConfig()) {
            return 1;
        }
        $tableResult = $generatorService->execute(true);

        $table = new Table();
        $table->setHeader(['connect', 'table', 'model', 'status']);

        foreach ($tableResult->getRecordRows() as $row) {
            $table->addRow([
                $row->getConnect(),
                $row->getTable(),
                $row->getClassName(),
                $row->getStatus(),
            ]);
        }

        $this->output->write($table->render());

        return 0;
    }
}
