<?php

namespace Mmanda\LaravelObfs\Console\Commands;

use Illuminate\Console\Command;
use Mmanda\LaravelObfs\Services\ObfuscationService;

abstract class BaseObfuscateCommand extends Command
{
    protected $obfuscationService;

    public function __construct(ObfuscationService $obfuscationService)
    {
        parent::__construct();
        $this->obfuscationService = $obfuscationService;
    }

    protected function obfuscateDirectory($directoryPath, $backup = false)
    {
        try {
            $this->obfuscationService->obfuscateDirectory($directoryPath, $backup);
            $this->info("Directory '$directoryPath' obfuscated successfully.");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function obfuscateFile($filePath, $backup = false)
    {
        try {
            $this->obfuscationService->obfuscateFile($filePath, $backup);
            $this->info("File '$filePath' obfuscated successfully.");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
