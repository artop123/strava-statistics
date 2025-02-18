<?php

namespace App\Console;

use App\Domain\Strava\Activity\ImportActivities\ImportActivities;
use App\Domain\Strava\Activity\Split\ImportActivitySplits\ImportActivitySplits;
use App\Domain\Strava\Activity\Stream\CalculateBestStreamAverages\CalculateBestStreamAverages;
use App\Domain\Strava\Activity\Stream\ImportActivityStreams\ImportActivityStreams;
use App\Domain\Strava\Athlete\ImportAthlete\ImportAthlete;
use App\Domain\Strava\Athlete\Weight\ImportAthleteWeight\ImportAthleteWeight;
use App\Domain\Strava\Challenge\ImportChallenges\ImportChallenges;
use App\Domain\Strava\Ftp\ImportFtp\ImportFtp;
use App\Domain\Strava\Gear\ImportGear\ImportGear;
use App\Domain\Strava\Segment\ImportSegments\ImportSegments;
use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\MigrationRunner;
use App\Infrastructure\FileSystem\PermissionChecker;
use App\Infrastructure\Time\ResourceUsage\ResourceUsage;
use Doctrine\DBAL\Connection;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:strava:recalculate', description: 'Recalculate Strava data')]
final class CalculateStreamConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly PermissionChecker $fileSystemPermissionChecker,
        private readonly MigrationRunner $migrationRunner,
        private readonly ResourceUsage $resourceUsage,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->fileSystemPermissionChecker->ensureWriteAccess();
        } catch (UnableToWriteFile|UnableToCreateDirectory) {
            $output->writeln('<error>Make sure the container has write permissions to "storage/database" and "storage/files" on the host system</error>');

            return Command::FAILURE;
        }

        $this->resourceUsage->startTimer();

        $output->writeln('Running database migrations...');
        $this->migrationRunner->run();

        $this->commandBus->dispatch(new CalculateBestStreamAverages($output, true));

        $this->connection->executeStatement('VACUUM');
        $output->writeln('Database got vacuumed 🧹');

        $this->resourceUsage->stopTimer();
        $output->writeln(sprintf(
            '<info>%s</info>',
            $this->resourceUsage->format(),
        ));

        return Command::SUCCESS;
    }
}
