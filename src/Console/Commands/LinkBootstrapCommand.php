<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace RxMake\Console\Commands;

use RxMake\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(
    name: 'link:bootstrap',
    description: 'Link bootstrap.php to public/config/config.user.inc.php file',
)]
class LinkBootstrapCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        try {
            $output = LinkCommand::link(
                $source = APP_DIR . '/bootstrap.php',
                $destination = RHYMIX_DIR . '/config/config.user.inc.php',
            );
            if ($output['status'] === 'failed') {
                $io?->error('bootstrap.php has not been successfully linked');
                return 1;
            }
            else if ($output['status'] === 'already-done') {
                $io?->info('bootstrap.php has already been linked');
            }
            else if ($output['status'] === 'success') {
                $io?->success('bootstrap.php has been successfully linked');
            }
            $io?->table([
                [ 'Source' => $source, 'Destination' => $destination ],
            ]);
            return 0;
        }
        catch (Throwable $e) {
            $io?->error($e->getMessage());
            return 1;
        }
    }
}
