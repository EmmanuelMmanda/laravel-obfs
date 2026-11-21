<?php

// Run inside a disposable Laravel 10-13 application with this package installed.
require $argv[1] . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$app = realpath($argv[1]);
$source = $app . '/obfs-fixture/Sample.php';
$original = "<?php\nclass ObfsFixture { public static function value() { return 'working'; } }\n";
$configDir = $app . '/config';
$configFile = $configDir . '/mObfs.php';
$engine = $app . '/vendor/douglas-fortunatus/yakpro-po/yakpro-po.php';

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function artisan($app, array $arguments)
{
    $process = new Process(array_merge([PHP_BINARY, $app . '/artisan'], $arguments), $app);
    $process->run();
    check($process->isSuccessful(), implode(' ', $arguments) . ': ' . $process->getErrorOutput() . $process->getOutput());
    return $process->getOutput();
}

function artisanFails($app, array $arguments)
{
    $process = new Process(array_merge([PHP_BINARY, $app . '/artisan'], $arguments), $app);
    $process->run();
    check(!$process->isSuccessful(), implode(' ', $arguments) . ' unexpectedly succeeded');
}

check(is_file($engine), 'Composer Yakpro engine missing');
check(!is_dir(dirname($engine) . '/PHP-Parser'), 'Engine must not install parser into vendor');
if (!is_dir(dirname($source))) mkdir(dirname($source), 0755, true);
if (!is_dir($configDir)) mkdir($configDir, 0755, true);
@unlink($app . '/obfs-fixture/Syntax84.php');
@unlink($app . '/obfs-fixture/Syntax85.php');
file_put_contents($source, $original);
$priorBackups = glob($app . '/M_obfuscate_backups/M2_*') ?: [];

$parserManifest = $app . '/vendor/nikic/php-parser/composer.json';
$before = hash_file('sha256', $parserManifest);

// Published configuration from old package version must still resolve new engine.
file_put_contents($configFile, "<?php\nreturn ['mObfs_path' => base_path('vendor/pmdunggh/yakpro-po/yakpro-po.php'), 'config_file' => base_path('config/mObfs.cnf'), 'target_directories' => ['obfs-fixture']];\n");
file_put_contents($configDir . '/mObfs.cnf', "<?php\n// YAK Pro - Php Obfuscator: Config File\n\$conf->parser_mode = 'PREFER_PHP7';\n\$conf->obfuscate_function_name = false;\n\$conf->obfuscate_class_name = false;\n");
artisan($app, ['mObfuscate:file', 'obfs-fixture/Sample.php', '--backup']);
check(strpos(file_get_contents($source), 'working') === false, 'File was not obfuscated');
$lint = new Process([PHP_BINARY, '-l', $source]);
$lint->run();
check($lint->isSuccessful(), 'Obfuscated output has invalid PHP syntax');
$backups = array_values(array_diff(glob($app . '/M_obfuscate_backups/M2_*') ?: [], $priorBackups));
check(count($backups) === 1, 'Expected one backup');
artisan($app, ['mObfuscate:restore', basename($backups[0])]);
check(file_get_contents($source) === $original, 'Restore did not reproduce original');
artisan($app, ['mObfuscate:directory', 'obfs-fixture', '--jobs=4']);
check(strpos(file_get_contents($source), 'working') === false, 'Directory command failed');
file_put_contents($source, $original);
artisan($app, ['mObfuscate:all']);
check(strpos(file_get_contents($source), 'working') === false, 'All command failed');
check(hash_file('sha256', $parserManifest) === $before, 'Vendor parser was modified');
check(!is_dir(dirname($engine) . '/PHP-Parser'), 'Engine added parser into vendor');

$legacyName = 'M_' . base64_encode('obfs-fixture/Sample.php') . '_' . time();
file_put_contents($app . '/M_obfuscate_backups/' . $legacyName, $original);
artisan($app, ['mObfuscate:restore', $legacyName]);
check(file_get_contents($source) === $original, 'Legacy backup restore failed');
artisanFails($app, ['mObfuscate:restore', '../' . $legacyName]);
artisanFails($app, ['mObfuscate:restore', 'M_' . base64_encode('../escape.php') . '_123']);
artisanFails($app, ['mObfuscate:restore', 'M_bad!_123']);
artisanFails($app, ['mObfuscate:file', '../escape.php']);
artisanFails($app, ['mObfuscate:file', 'vendor/autoload.php']);
artisanFails($app, ['mObfuscate:directory', 'vendor']);
artisanFails($app, ['mObfuscate:directory', 'obfs-fixture', '--jobs=0']);
$link = $app . '/obfs-fixture/External.php';
@unlink($link);
symlink($source, $link);
artisanFails($app, ['mObfuscate:file', 'obfs-fixture/External.php']);
check(strpos(artisan($app, ['mObfuscate:directory', 'obfs-fixture', '--jobs=2']), 'Skipped 1 symlinks') !== false, 'Symlink skip was not reported');
check(is_link($link), 'Directory command changed symlink');
@unlink($link);

$failureDir = $app . '/obfs-failure';
if (!is_dir($failureDir)) mkdir($failureDir);
$good = $failureDir . '/Good.php';
$bad = $failureDir . '/Bad.php';
file_put_contents($good, "<?php function goodFixture() { return 'good'; }\n");
file_put_contents($bad, "<?php function badFixture( {\n");
artisanFails($app, ['mObfuscate:directory', 'obfs-failure', '--jobs=2']);
check(file_get_contents($bad) === "<?php function badFixture( {\n", 'Failed job changed source');
check(strpos(file_get_contents($good), "'good'") === false, 'Completed job was not kept after batch failure');
check(!(glob($failureDir . '/.mObfs-*') ?: []), 'Failed batch left temporary outputs');

if (PHP_VERSION_ID >= 80400) {
    $syntax84 = $app . '/obfs-fixture/Syntax84.php';
    file_put_contents($syntax84, <<<'PHP'
<?php
class Syntax84 {
    public string $value {
        get => strtoupper($this->value);
        set => trim($value);
    }
}
PHP
    );
    artisan($app, ['mObfuscate:file', 'obfs-fixture/Syntax84.php']);
    $lint = new Process([PHP_BINARY, '-l', $syntax84]);
    $lint->run();
    check($lint->isSuccessful(), 'PHP 8.4 output invalid');
}

if (PHP_VERSION_ID >= 80500) {
    $syntax85 = $app . '/obfs-fixture/Syntax85.php';
    file_put_contents($syntax85, <<<'PHP'
<?php
function syntax85(string $value): string {
    return $value |> trim(...) |> strtoupper(...);
}
PHP
    );
    artisan($app, ['mObfuscate:file', 'obfs-fixture/Syntax85.php']);
    $run = new Process([PHP_BINARY, '-r', 'require ' . var_export($syntax85, true) . '; echo syntax85(" hi ");']);
    $run->run();
    check($run->isSuccessful() && trim($run->getOutput()) === 'HI', 'PHP 8.5 output changed behavior');
}

file_put_contents($source, $original);
file_put_contents($configFile, "<?php\nreturn ['mObfs_path' => base_path('missing-custom-engine.php'), 'config_file' => base_path('config/mObfs.cnf')];\n");
$failure = new Process([PHP_BINARY, $app . '/artisan', 'mObfuscate:file', 'obfs-fixture/Sample.php'], $app);
$failure->run();
check(!$failure->isSuccessful(), 'Missing custom engine should return failure');
check(file_get_contents($source) === $original, 'Failed command changed source');

echo "Laravel package integration passed\n";
