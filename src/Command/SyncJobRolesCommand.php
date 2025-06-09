<?php

namespace App\Command;

use App\Service\JobRoleSyncService;
use App\Service\CareersApiClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-jobs',
    description: 'Sync job roles from Careers NZ API'
)]
class SyncJobRolesCommand extends Command
{
    public function __construct(
        private JobRoleSyncService $syncService,
        private CareersApiClient $apiClient
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('job-code', InputArgument::OPTIONAL, 'Sync specific job by job code')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be synced without making changes')
            ->addOption('status', null, InputOption::VALUE_NONE, 'Show sync status information')
            ->setHelp('
This command syncs job roles from the Careers NZ API.

Examples:
  php bin/console app:sync-jobs                    # Sync all jobs
  php bin/console app:sync-jobs J80312            # Sync specific job
  php bin/console app:sync-jobs --status          # Show sync status
  php bin/console app:sync-jobs --dry-run         # Preview sync without changes
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Show status if requested
        if ($input->getOption('status')) {
            return $this->showStatus($io);
        }

        // Dry run mode
        if ($input->getOption('dry-run')) {
            $io->warning('DRY RUN MODE - No changes will be made');

            $io->info('Dry run not implemented yet');
            return Command::SUCCESS;
        }

        $jobCode = $input->getArgument('job-code');

        try {
            if ($jobCode) {
                return $this->syncSingleJob($io, $jobCode);
            } else {
                return $this->syncAllJobs($io);
            }
        } catch (\Exception $e) {
            $io->error('Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function syncAllJobs(SymfonyStyle $io): int
    {
        $io->title('Syncing All Jobs from Careers NZ API');

        // Validate API connection first
        if (!$this->validateApiConnection($io)) {
            return Command::FAILURE;
        }

        $io->info('Starting full job sync...');
        $io->progressStart();

        $result = $this->syncService->syncAllJobs();

        $io->progressFinish();
        $io->newLine();


        $summary = $result->getSummary();

        if ($result->isSuccessful()) {
            $io->success('Sync completed successfully!');
        } else {
            $io->warning('Sync completed with errors');
        }


        $io->table(['Metric', 'Count'], [
            ['Total Processed', $summary['total_processed']],
            ['Created', $summary['created']],
            ['Updated', $summary['updated']],
            ['Skipped', $summary['skipped']],
            ['Failed', $summary['failed']],
            ['Archived', $summary['archived']],
            ['Success Rate', $summary['success_rate'] . '%'],
            ['Duration', $summary['duration']],
        ]);


        if (!empty($result->getErrors())) {
            $io->section('Errors');
            foreach ($result->getErrors() as $error) {
                $io->error(sprintf('[%s] %s: %s',
                    $error['timestamp']->format('H:i:s'),
                    $error['identifier'],
                    $error['error']
                ));
            }
        }

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }

    private function syncSingleJob(SymfonyStyle $io, string $jobCode): int
    {
        $io->title('Syncing Single Job: ' . $jobCode);

        // Validate API connection first
        if (!$this->validateApiConnection($io)) {
            return Command::FAILURE;
        }

        $success = $this->syncService->syncSingleJob($jobCode);

        if ($success) {
            $io->success('Job synced successfully!');
            return Command::SUCCESS;
        } else {
            $io->warning('Job was skipped (may not exist or manually edited)');
            return Command::SUCCESS;
        }
    }

    private function showStatus(SymfonyStyle $io): int
    {
        $io->title('Sync Status');

        $status = $this->syncService->getLastSyncStatus();


        $io->section('Database Status');
        $io->writeln("Total Jobs: {$status['total_jobs']}");
        $io->writeln("Successfully Synced: {$status['synced_jobs']}");
        $io->writeln("Failed Syncs: {$status['failed_jobs']}");
        $io->writeln("Last Sync: " . ($status['last_sync_at']?->format('Y-m-d H:i:s') ?? 'Never'));


        $io->section('API Status');
        $apiConnected = $status['api_connection'];
        $io->writeln("Connection: " . ($apiConnected ? 'Connected' : 'Failed'));

        if ($apiConnected) {
            try {
                $totalApiJobs = $this->apiClient->getJobsCount();
                $io->writeln("Total API Jobs: {$totalApiJobs}");

                $syncedPercentage = $status['total_jobs'] > 0
                    ? round(($status['synced_jobs'] / $status['total_jobs']) * 100, 1)
                    : 0;
                $io->writeln("Sync Coverage: {$syncedPercentage}%");

                if ($status['total_jobs'] < $totalApiJobs) {
                    $missing = $totalApiJobs - $status['total_jobs'];
                    $io->writeln("Missing Jobs: {$missing}");
                }
            } catch (\Exception $e) {
                $io->writeln("API Error: " . $e->getMessage());
            }
        }


        $io->section('Recommendations');
        if ($status['failed_jobs'] > 0) {
            $io->writeln("• Run full sync to retry failed jobs");
        }
        if (!$apiConnected) {
            $io->writeln("• Check API credentials and network connection");
        }
        if ($status['last_sync_at'] === null) {
            $io->writeln("• Run initial sync to populate job database");
        } elseif ($status['last_sync_at'] < new \DateTimeImmutable('-1 week')) {
            $io->writeln("• Consider running sync (last sync over 1 week ago)");
        }

        return $apiConnected ? Command::SUCCESS : Command::FAILURE;
    }

    private function validateApiConnection(SymfonyStyle $io): bool
    {
        $io->info('Validating API connection...');

        try {
            $isConnected = $this->apiClient->testConnection();

            if (!$isConnected) {
                $io->error('Cannot connect to Careers API. Please check your credentials and network connection.');
                return false;
            }

            $io->info('API connection validated successfully.');
            return true;
        } catch (\Exception $e) {
            $io->error('API connection failed: ' . $e->getMessage());
            return false;
        }
    }
}
