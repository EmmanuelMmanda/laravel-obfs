<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Support\Facades\File;

class ObfuscateFileCommand extends BaseObfuscateCommand
{
    protected $signature = 'mObfuscate:file {sourcefile} {--backup : Create a backup of the file}';
    protected $description = 'Obfuscate a specific PHP file';

    public function handle()
    {
        $sourcefile = $this->argument('sourcefile');
        $backup = $this->option('backup');
        $filePath = base_path($sourcefile);

        if (!File::exists($filePath)) {
            $this->error("Source file '$filePath' does not exist.");
            return;
        }

        $this->obfuscateFile($filePath, $backup);
    }
}
