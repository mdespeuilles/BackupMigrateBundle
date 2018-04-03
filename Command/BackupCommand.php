<?php

namespace Mdespeuilles\BackupMigrateBundle\Command;

use App\Kernel;
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
            ->addOption('files-only', null, InputOption::VALUE_NONE, 'Backup only files', null)
            ->addOption('database-only', null, InputOption::VALUE_NONE, 'Backup only database', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $destination_path = $this->getContainer()->getParameter('mdespeuilles_backup_migrate.destination_path');
        $files_folder = $this->getContainer()->getParameter('mdespeuilles_backup_migrate.files_folder');
    
        $fs = new Filesystem();
        
        if (!$input->getOption('files-only')) {
            $output->writeln('Start backup database');
            
            $host = null;
            $port = '3306';
            $user = null;
            $pass = null;
            $database = null;
            $type = 'mysql';

            if (Kernel::VERSION_ID >= 40000) {
                $host = parse_url(getenv('DATABASE_URL'), PHP_URL_HOST);
                $type = parse_url(getenv('DATABASE_URL'), PHP_URL_SCHEME);
                $port = parse_url(getenv('DATABASE_URL'), PHP_URL_PORT);
                $user = parse_url(getenv('DATABASE_URL'), PHP_URL_USER);
                $pass = parse_url(getenv('DATABASE_URL'), PHP_URL_PASS);
                $database = str_replace("/", "", parse_url(getenv('DATABASE_URL'), PHP_URL_PATH));
            }
            else {
                $host = $this->getContainer()->getParameter('database_host');
                $user = $this->getContainer()->getParameter('database_user');
                $pass = $this->getContainer()->getParameter('database_password');
                $database = $this->getContainer()->getParameter('database_name');
            }
            
            $database = new Config([
                'base' => [
                    'type' => $type,
                    'host' => $host,
                    'port' => $port,
                    'user' => $user,
                    'pass' => $pass,
                    'database' => $database,
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
            
            $fs->remove($destination_path . '/database.sql.gz');
    
            $manager->makeBackup()->run('base', [new Destination('local', 'database.sql')], 'gzip');
            $output->writeln('<info>Done !</info>');
        }
    
        if (!$input->getOption('database-only')) {
            $output->writeln('Start backup files');
    
            foreach ($files_folder as $key => $folder) {
                $fs->remove($destination_path . "/" .$key.'.zip');
                $jar = new ZipArchive($destination_path . '/'.$key.'.zip');
                $jar->addFolder($folder['path'], $key)->close();
            }
    
            $output->writeln('<info>Done !</info>');
        }
    }

}
