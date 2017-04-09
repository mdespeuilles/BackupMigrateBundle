# BackupMigrateBundle

This bundle provide a command to backup and Restore files and database for your Symfony application.

## Installation

To install BackupMigrateBundle with Composer just type in your terminal:

```bash
php composer.phar require mdespeuilles/backupmigrateBundle
```

Now update your ``AppKernel.php`` file, and
register the new bundle:

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new Mdespeuilles\BackupMigrateBundle\MdespeuillesBackupMigrateBundleBundle(),
    // ...
);
```

## Configuration

Configure the bundle in your config.yml

```yml

mdespeuilles_backup_migrate:
    # Destination path for backup
    destination_path: "%kernel.root_dir%/../private"
    
    #files folders to backup
    files_folder:
        uploads:
            path: "%kernel.root_dir%/../web/uploads"
        medias:
            path: "%kernel.root_dir%/../web/medias"
        ....
            
```

## Usage

### Backup

Run this command to start a backup : 

```bash
php bin/console bm:backup
```

if you want to backup only database : 

```bash
php bin/console bm:backup --database-only
```

if you want to backup only files : 

```bash
php bin/console bm:backup --files-only
```

### Restore

Run this command to start a restore : 

```bash
php bin/console bm:restore
```

if you want to restore only database : 

```bash
php bin/console bm:restore --database-only
```

if you want to restore only files : 

```bash
php bin/console bm:restore --files-only
```
