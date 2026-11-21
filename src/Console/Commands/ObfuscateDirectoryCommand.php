<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Support\Facades\File;

class ObfuscateDirectoryCommand extends BaseObfuscateCommand
{
    protected $signature = 'mObfuscate:directory {directory} {--backup : Create a backup of each file} {--jobs=4 : Parallel file processes (1-32)}';
    protected $description = 'Obfuscate all PHP files in the specified directory';

    public function handle()
    {
        $directory = $this->argument('directory');
        $backup = $this->option('backup');
        $directoryPath = base_path($directory);

        if (!File::isDirectory($directoryPath)) {
            $this->error("Directory '$directoryPath' does not exist.");
            return 1;
        }

        return $this->obfuscateDirectory($directoryPath, $backup, $this->option('jobs'));
    }
}
