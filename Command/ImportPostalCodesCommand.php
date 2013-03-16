<?php

namespace NS\DistanceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportPostalCodesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('distance:import')
           ->setDescription('Import Postal Codes')
           ->setDefinition(array());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getContainer()->get('kernel');
        $path   = $kernel->locateResource('@NSDistanceBundle/Resources/config/Canada.csv.gz');
        if(!is_file($path))
            throw new \Exception("Path not found: $path");
        
        $file     = basename($path);
        $r        = rand(150,1500);
        $newFile  = sys_get_temp_dir().DIRECTORY_SEPARATOR.$r.$file; 
        $newFile2 = str_replace(".gz","", $newFile);
        
        copy($path, $newFile);
        exec("gunzip $newFile");
        
        $output->writeln("Gunzip'd $newFile");
        
        chmod($newFile2,744);
        
        $con = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();
        $con->exec("TRUNCATE postalcodes");
        $rows = $con->exec("LOAD DATA INFILE '$newFile2' INTO TABLE postalcodes FIELDS TERMINATED BY ',';");
    
        $output->writeln("Loaded $rows rows");
    }
}
