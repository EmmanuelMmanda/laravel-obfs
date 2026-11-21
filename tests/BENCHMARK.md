# Directory obfuscation benchmark

Run `php tests/benchmark.php /path/to/disposable-laravel-app` after installing
this checkout into the app. The script creates 100 small PHP files, restores
their original contents before each run, and reports the median of three runs
for one and four workers. It is a manual benchmark, not a CI timing gate.

| Environment | One worker | Four workers | Speedup |
| --- | ---: | ---: | ---: |
| macOS arm64, PHP 8.5.13, Laravel 12 fixture, 2026-11-21 | 5.938s | 1.690s | 3.51× |
