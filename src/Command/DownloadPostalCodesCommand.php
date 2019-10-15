<?php

namespace NS\DistanceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadPostalCodesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('distance:download')
            ->setDescription('Download Postal Codes')
            ->setDefinition(array());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $this->getContainer()->get('kernel')->getRootDir(). '/../Canada.txt.zip';

        exec("wget -O $filename http://download.geonames.org/export/zip/CA.zip");
    }
}
