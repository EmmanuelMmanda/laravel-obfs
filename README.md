# Laravel Obfuscation Package 🔒

A Simple wrapper for YakPro-po obfuscator for Laravel Framework.
Due Accreditation : <a href="https://github.com/pk-fr/yakpro-po">YakPro-po</a> 🛩️

## Installation

**Requirements**

```bash

PHP version ^7.1|^8.0
Laravel Framework ^7.0|^8.0|^9.0|^10.0
pmdunggh/yakpro-po dev-master
nikic/php-parser ^4.0

```

### Manual Installation via Composer

1. **Add Repository**: Add the following repository entry to your `composer.json` file:

   ```json
   "repositories": [
       {
           "type": "vcs",
           "url": "https://github.com/EmmanuelMmanda/laravel-obfs"
       }
   ]
   ```

2. **Minimum stability**: Add Minimum Stability Settings: Ensure your composer.json includes minimum-stability and prefer-stable:

   ```json
       "minimum-stability": "dev",
       "prefer-stable": true
   ```

3. **install package**:

   ```bash
       composer require mmanda/laravel-obfs
   ```

   **incase of php compatability issues**
   Manually add the latest version on composer.json 'require' object:

   ```json
       "require": {
       "mmanda/laravel-obfs": "^<use latest version>"
            }
   ```

   **Then update dependancies with**:

   ```bash
       composer update --ignore-platform-reqs
   ```

4. **IMPORTANT :: Publish configuration files**: if you skip this default obfuscation configuration will be used
   Obfuscation configuratioin will be published at 
   <br>
     **PROJECTROOTDIR/config/mObfs.php and PROJECTROOTDIR/config/mObfs.php**

   ```bash
       php artisan vendor:publish --provider="Mmanda\\LaravelObfs\\Providers\\ObfuscateServiceProvider"
   ```

### Usage

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

### Contributing

Work in progress !! Contributions are welcome! Please read the contribution guidelines before submitting a pull request.

### License

This package is licensed under the MIT License. See the LICENSE file for more information.

### Author

```bash

Emmanuel Mmanda
Email: luneya17@gmail.com

```
