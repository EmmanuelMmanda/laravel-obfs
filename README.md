# Laravel Obfuscation Package 🔒

A Simple wrapper for YakPro-po obfuscator for Laravel Framework.
Due Accreditation : <a href="https://github.com/pk-fr/yakpro-po">YakPro-po</a> 🛩️

## Requirements

```bash

PHP version ^7.1|^8.0

Laravel Framework ^7.0|^8.0|^9.0|^10.0

pmdunggh/yakpro-po dev-master

nikic/php-parser ^4.0

```

## Installation

```bash
composer require mmanda/laravel-obfs
```
If you get requirements compatabilities with your existing package just use .

```bash
composer require mmanda/laravel-obfs -W
```

## Publish Assets

**NB** : Publish configuration files if you skip this default obfuscation configuration will be used
Obfuscation configuratioin will be published at `PROJECTROOTDIR/config/mObfs.php` and `PROJECTROOTDIR/config/mObfs.cnf`

```bash
php artisan vendor:publish --provider=Mmanda\LaravelObfs\Providers\ObfuscateServiceProvider
```

`mObfs.cnf`
   Update the confguration as specified and your level of obfuscation desired.


## Usage

Artisan Commands
The package provides several Artisan commands to obfuscate PHP files within your Laravel project.

**Obfuscate All PHP Files**
To obfuscate all PHP files in your Laravel project:

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
You can create backups of obfuscated files with the --backup option:

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

### License

This package is licensed under the MIT License. See the ,<a href="https://github.com/EmmanuelMmanda/laravel-obfs/blob/master/LICENSE">LICENSE</a> file for more information.

### Author

```bash
Emmanuel Mmanda
Email: luneya17@gmail.com
```
