<?php

namespace NS\DistanceBundle\Command;

use \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \KzykHys\CsvParser\CsvParser;

class ImportPostalCodesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('distance:import')
            ->setDescription('Import Postal Codes')
            ->setDefinition(array(
                new InputArgument(
                    'file', InputArgument::REQUIRED, 'Path to canadian csv file'
                ),));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('file');
        if (!is_file($path)) {
            throw new \Exception("Path not found: $path");
        }

        $file     = basename($path);
        $rFile    = rand(150, 1500);
        $newFile  = $this->getContainer()->get('kernel')->getRootDir().DIRECTORY_SEPARATOR . '..'.DIRECTORY_SEPARATOR . $rFile . $file;
        $dir = $this->getContainer()->get('kernel')->getRootDir().DIRECTORY_SEPARATOR . '..'.DIRECTORY_SEPARATOR . 'postal_codes'.DIRECTORY_SEPARATOR.'postalcodes'.uniqid();
        $newFile2 = str_replace(".zip", "", $dir.DIRECTORY_SEPARATOR. 'CA.txt');

        copy($path, $newFile);
        exec("unzip $newFile -d $dir");

        $output->writeln("Unzipped $newFile");

        chmod($newFile2, 777);

        $parser = CsvParser::fromFile($newFile2, [
            'delimiter' => "\t"
        ]);
        $results = $parser->parse();

        $con  = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();
        $output->writeln("Truncating postal codes.");
        $con->exec("TRUNCATE postalcodes");

        $progressBar = new ProgressBar($output, count($results));
        $output->writeln("Loading rows:");
        $progressBar->start();
        $i = 0;
        $x = 0;
        foreach($results as $result)
        {
            try
            {
                $stmt  = $con->prepare("REPLACE INTO postalcodes (city, province, postal_code, latitude, longitude) VALUES (?, ?, ?, ?, ?);");
                $stmt->execute([$result[2], $result[4], $result[1], $result[9], $result[10]]);
                $progressBar->advance();
                $i++;
            }
            catch(DBALException $exception)
            {
                $output->writeln('Unable to load row. '.$exception->getMessage());
                $x++;
                continue;
            }
        }

        $progressBar->finish();
        $output->writeln('');

        $output->writeln("Loaded $i rows, $x failed");
    }
}

