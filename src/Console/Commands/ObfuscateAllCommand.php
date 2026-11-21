<?php

namespace Mmanda\LaravelObfs\Console\Commands;

class ObfuscateAllCommand extends BaseObfuscateCommand
{
    protected $signature = 'mObfuscate:all {--backup : Create a backup of each file} {--jobs=4 : Parallel file processes (1-32)}';
    protected $description = 'Obfuscate all PHP files in the Laravel project';

    public function handle()
    {
        $backup = $this->option('backup');
        try {
            $skipped = $this->obfuscationService->obfuscateAll($backup, $this->option('jobs'));
            $this->info("All PHP files obfuscated successfully. Skipped {$skipped} symlinks.");
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
