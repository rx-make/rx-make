<?php

declare(strict_types=1);

namespace RxMake\Console;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

readonly class ConsoleIO
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @psalm-suppress UnusedProperty
     * @noinspection   PhpPropertyOnlyWrittenInspection
     * */
    public function __construct(private InputInterface $input, private OutputInterface $output)
    {
        $formatter = $output->getFormatter();
        $formatter->setStyle('error', new OutputFormatterStyle(
            'white',
            'red',
            ['bold'],
        ));
        $formatter->setStyle('warning', new OutputFormatterStyle(
            'black',
            'yellow',
            ['bold'],
        ));
        $formatter->setStyle('success', new OutputFormatterStyle(
            'black',
            'bright-green',
            ['bold'],
        ));
        $formatter->setStyle('info', new OutputFormatterStyle(
            'white',
            'cyan',
            ['bold']
        ));
    }

    /**
     * Write labeled content to console output.
     *
     * @param 'error'|'warning'|'success'|'info' $type
     * @param string                             $content
     *
     * @return void
     * @psalm-api
     */
    public function write(string $type, string $content): void
    {
        $this->output->writeln(
            sprintf(
                '<%s> %s </> %s',
                $type,
                strtoupper($type),
                $content
            )
        );
    }

    /**
     * Write error labeled content to console output.
     *
     * @param string $content
     *
     * @return void
     * @psalm-api
     */
    public function error(string $content): void
    {
        $this->write('error', $content);
    }

    /**
     * Write warning labeled content to console output.
     *
     * @param string $content
     *
     * @return void
     * @psalm-api
     */
    public function warning(string $content): void
    {
        $this->write('warning', $content);
    }

    /**
     * Write success labeled content to console output.
     *
     * @param string $content
     *
     * @return void
     * @psalm-api
     */
    public function success(string $content): void
    {
        $this->write('success', $content);
    }

    /**
     * Write info labeled content to console output.
     *
     * @param string $content
     *
     * @return void
     * @psalm-api
     */
    public function info(string $content): void
    {
        $this->write('info', $content);
    }

    /**
     * Render table to console output.
     *
     * @param list<array<string, string>|TableSeparator> $rows
     * @param array<string> $headerOrders
     *
     * @return void
     * @psalm-api
     */
    public function table(array $rows, array $headerOrders = []): void
    {
        $realHeaders = $headerOrders;
        foreach ($rows as $row) {
            if ($row instanceof TableSeparator) {
                continue;
            }
            foreach ($row as $header => $_) {
                if (!in_array($header, $realHeaders)) {
                    $realHeaders[] = $header;
                }
            }
        }

        $realRows = [];
        foreach ($rows as $row) {
            if ($row instanceof TableSeparator) {
                $realRows[] = $row;
                continue;
            }
            $realRow = [];
            foreach ($realHeaders as $realHeader) {
                $realRow[$realHeader] = $row[$realHeader] ?? '';
            }
            $realRows[] = $realRow;
        }

        $table = new Table($this->output);
        $table->setHeaders($realHeaders);
        $table->setRows($realRows);
        $table->render();
    }
}
