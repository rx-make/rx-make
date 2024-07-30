<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Console\Commands;

use RxMake\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'link',
    description: 'Link bootstrap.php and all installed modules to public directory',
)]
class LinkCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        try {
            $returnCode = $this->getApplication()?->doRun(new ArrayInput([
                'command' => 'link:bootstrap',
            ]), $output);
            if ($returnCode !== null && $returnCode !== 0) {
                return $returnCode;
            }

            $returnCode = $this->getApplication()?->doRun(new ArrayInput([
                'command' => 'link:module',
                'namespace' => '@/..'
            ]), $output);
            if ($returnCode !== null && $returnCode !== 0) {
                return $returnCode;
            }

            return 0;
        }
        catch (Throwable $e) {
            $io?->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Create a symlink that $destination forwards to $source.
     *
     * @param string $source Original file path.
     * @param string $destination Symlink file path.
     *
     * @return array{
     *     source: string,
     *     destination: string,
     *     status: 'success'|'already-done'|'failed'
     * }
     */
    public static function link(string $source, string $destination): array
    {
        $result = [
            'source' => $source,
            'destination' => $destination,
        ];
        if (file_exists($destination)) {
            $result['status'] = (realpath($source) === realpath($destination)) ? 'already-done' : 'failed';
        }
        else {
            $result['status'] = symlink($source, $destination) ? 'success' : 'failed';
        }
        return $result;
    }
}
