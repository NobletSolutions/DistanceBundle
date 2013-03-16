<?php

namespace NS\DistanceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('distance:calculate')
           ->setDescription('Import Postal Codes')
           ->setDefinition(array(
                    new InputArgument(
                           'source',
                           InputArgument::REQUIRED,
                           'Source Postal Code'
                    ),
                    new InputArgument(
                           'dest',
                           InputArgument::REQUIRED,
                           'Destination Postal Code'
                   ),
               ));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $dest   = $input->getArgument('dest');
        
        $distance = $this->getContainer()->get('ns_distance.calculator')->getDistanceBetweenPostalCodes($source,$dest);
    
        $output->writeln("The distance between $source and $dest is $distance KM");
    }
}
