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

class OaBackupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oa:backup')
            ->setDescription('...')
            //->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            //->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
                'root' => $this->getContainer()->get('kernel')->getRootDir() . '/../private'
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
        $fs->remove($this->getContainer()->get('kernel')->getRootDir() . '/../private/database.sql.gz');
        $fs->remove($this->getContainer()->get('kernel')->getRootDir() . '/../private/uploads.zip');
    
        $manager->makeBackup()->run('base', [new Destination('local', 'database.sql')], 'gzip');
        $output->writeln('Done !');
        
        $output->writeln('Start backup files');
        $jar = new ZipArchive($this->getContainer()->get('kernel')->getRootDir() . '/../private/' . 'uploads.zip');
        $jar->addFolder($this->getContainer()->get('kernel')->getRootDir() . '/../web/uploads', 'uploads')->close();
        $output->writeln('Done !');
    }

}
