<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ObfuscateAllCommand extends BaseObfuscateCommand
{
    protected $signature = 'mObfuscate:all {--backup : Create a backup of each file}';
    protected $description = 'Obfuscate all PHP files in the Laravel project';

    public function handle()
    {
        $backup = $this->option('backup');
        $this->obfuscationService->obfuscateAll($backup);
        $this->info('All PHP files obfuscated successfully.');
    }
}
