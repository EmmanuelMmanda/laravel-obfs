<?php

// Run inside a disposable Laravel application with this package installed.
require $argv[1] . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$app = realpath($argv[1]);
$dir = $app . '/obfs-benchmark';
file_put_contents($app . '/config/mObfs.php', "<?php\nreturn ['mObfs_path' => base_path('vendor/douglas-fortunatus/yakpro-po/yakpro-po.php'), 'config_file' => base_path('config/mObfs.cnf')];\n");
if (!is_dir($dir)) {
    mkdir($dir, 0755);
}
$source = "<?php\nclass Benchmark%03d { public static function value(): string { return 'benchmark'; } }\n";
for ($i = 0; $i < 100; ++$i) {
    file_put_contents(sprintf('%s/File%03d.php', $dir, $i), sprintf($source, $i));
}

$results = [];
foreach ([1, 4] as $workers) {
    for ($round = 0; $round < 3; ++$round) {
        for ($i = 0; $i < 100; ++$i) {
            file_put_contents(sprintf('%s/File%03d.php', $dir, $i), sprintf($source, $i));
        }
        $process = new Process([PHP_BINARY, $app . '/artisan', 'mObfuscate:directory', 'obfs-benchmark', '--jobs=' . $workers], $app);
        $process->setTimeout(600);
        $start = microtime(true);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getErrorOutput() . $process->getOutput());
        }
        $results[$workers][] = microtime(true) - $start;
    }
}
$median = static function (array $values): float {
    sort($values);
    return $values[1];
};
$single = $median($results[1]);
$parallel = $median($results[4]);
printf("PHP %s; %s; 100 files; 3 rounds\n", PHP_VERSION, PHP_OS_FAMILY);
printf("1 worker median: %.3fs; 4 workers median: %.3fs; speedup: %.2fx\n", $single, $parallel, $single / $parallel);
