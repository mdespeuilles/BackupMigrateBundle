<?php

namespace Mdespeuilles\BackupMigrateBundle\Command;

use BackupManager\Compressors\CompressorProvider;
use BackupManager\Compressors\GzipCompressor;
use BackupManager\Config\Config;
use BackupManager\Databases\DatabaseProvider;
use BackupManager\Databases\MysqlDatabase;
use BackupManager\Filesystems\Destination;
use BackupManager\Filesystems\FilesystemProvider;
use BackupManager\Filesystems\LocalFilesystem;
use BackupManager\Manager;
use deit\compression\ZipArchive;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class BackupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('bm:backup')
            ->setDescription('Backup databases and files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination_path = $this->getContainer()->getParameter('mdespeuilles_backup_migrate.destination_path');
        $files_folder = $this->getContainer()->getParameter('mdespeuilles_backup_migrate.files_folder');
        
        $output->writeln('Start backup database');
        $database = new Config([
            'base' => [
                'type' => 'mysql',
                'host' => $this->getContainer()->getParameter('database_host'),
                'port' => '3306',
                'user' => $this->getContainer()->getParameter('database_user'),
                'pass' => $this->getContainer()->getParameter('database_password'),
                'database' => $this->getContainer()->getParameter('database_name'),
                'singleTransaction' => false,
                'ignoreTables' => [],
            ]
        ]);
        
        $storage = new Config([
            'local' => [
                'type' => 'Local',
                'root' => $destination_path
            ]
        ]);
        
        $filesystems = new FilesystemProvider($storage);
        $filesystems->add(new LocalFilesystem);
        $database = new DatabaseProvider($database);
        $database->add(new MysqlDatabase);
        
        $compressors = new CompressorProvider;
        $compressors->add(new GzipCompressor);
        
        $manager = new Manager($filesystems, $database, $compressors);
        
        $fs = new Filesystem();
        $fs->remove($destination_path . '/database.sql.gz');
    
        $manager->makeBackup()->run('base', [new Destination('local', 'database.sql')], 'gzip');
        $output->writeln('<info>Done !</info>');
        
        $output->writeln('Start backup files');
        
        foreach ($files_folder as $key => $folder) {
            $fs->remove($destination_path . "/" .$key.'.zip');
            $jar = new ZipArchive($destination_path . '/'.$key.'.zip');
            $jar->addFolder($folder['path'], $key)->close();
        }
    
        $output->writeln('<info>Done !</info>');
    }

}
