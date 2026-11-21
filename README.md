# Laravel Obfuscation Package 🔒

A wrapper for [YAK Pro PHP Obfuscator](https://github.com/pk-fr/yakpro-po) for Laravel.

## Requirements

- PHP 7.4 or newer. Laravel 12 requires PHP 8.2 or newer.
- Laravel 7 through 12.
- `douglas-fortunatus/yakpro-po` 3.x and `nikic/php-parser` 5.9 or newer, installed by Composer.

## Installation

```bash
composer require mmanda/laravel-obfs:^2.0 -W
```
The package does not download dependencies while an Artisan command runs.

## Publish Assets

Publish configuration files to customize obfuscation. Without published files, package defaults apply.
Configuration files are published to `PROJECTROOTDIR/config/mObfs.php` and `PROJECTROOTDIR/config/mObfs.cnf`

```bash
php artisan vendor:publish --provider=Mmanda\LaravelObfs\Providers\ObfuscateServiceProvider --tag=mConfig
```

Edit `config/mObfs.cnf` to choose obfuscation options. Its `parser_mode` may be
`HOST_VERSION`, `NEWEST_VERSION`, or a PHP version such as `8.2`.


## Usage

Artisan Commands
The package provides several Artisan commands to obfuscate PHP files within your Laravel project.

**Obfuscate Core PHP Files**
Obfuscates PHP files in `app` and `routes` (configurable via `config/mObfs.php`):

```bash
php artisan mObfuscate:all
```

**Obfuscate Specific Directory**
To obfuscate PHP files in a specific directory:

```bash
php artisan mObfuscate:directory {directory}
```

**Obfuscate Specific File**
To obfuscate a specific PHP file:

```bash
php artisan mObfuscate:file {somefile or dir/file}
```

**Backup and Restore**
Backup
Backups are stored in `PROJECTROOTDIR/M_obfuscate_backups`. You can create backups of obfuscated files with the --backup option:

```bash
php artisan mObfuscate:all --backup
```

Restore
To restore a backed-up file or directory:

```bash
php artisan mObfuscate:restore {backup_file_name}
```

## Contributing

Work in progress !! Contributions are welcome! Please read the contribution guidelines before submitting a pull request.

## Upgrade from 1.x

Version 2 requires PHP 7.4+ and uses YAK Pro 3 with PHP-Parser 5. Existing
published `config/mObfs.php` files containing the old default
`vendor/pmdunggh/yakpro-po/yakpro-po.php` path resolve to the new engine
automatically. Custom executable paths stay unchanged.

An existing default `PREFER_PHP7` parser mode maps to `HOST_VERSION`. Replace
other legacy parser modes in `config/mObfs.cnf` with a supported mode before
running obfuscation. Back up application files before upgrading: output may
change between YAK Pro versions.

### License

This package is licensed under the MIT License. See the ,<a href="https://github.com/EmmanuelMmanda/laravel-obfs/blob/master/LICENSE">LICENSE</a> file for more information.

### Author

```bash
Emmanuel Mmanda
Email: luneya17@gmail.com
```
