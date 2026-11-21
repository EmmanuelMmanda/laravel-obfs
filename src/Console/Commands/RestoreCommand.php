<?php

namespace Mmanda\LaravelObfs\Console\Commands;

class RestoreCommand extends BaseObfuscateCommand
{
    protected $signature = 'mObfuscate:restore {backup}';
    protected $description = 'Restore a backed-up file or directory';

    public function handle()
    {
        try {
            $this->obfuscationService->restoreBackupByName($this->argument('backup'));
            $this->info('Backup restored successfully.');
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
