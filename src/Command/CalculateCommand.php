<?php

namespace NS\DistanceBundle\Command;

use NS\DistanceBundle\Exceptions\UnknownPostalCodeException;
use NS\DistanceBundle\Services\DistanceCalculator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateCommand extends Command
{
    private DistanceCalculator $calculator;

    public function __construct(DistanceCalculator $calculator)
    {
        $this->calculator = $calculator;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('distance:calculate')
            ->setDescription('Calculate distances between postal codes')
            ->setDefinition(array(
                new InputArgument(
                    'source', InputArgument::REQUIRED, 'Source Postal Code'
                ),
                new InputArgument(
                    'dest', InputArgument::REQUIRED, 'Destination Postal Code'
                ),
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument('source');
        $dest   = $input->getArgument('dest');

        if (strpos($dest, ',') !== FALSE) {
            $dest = explode(',', $dest);
        }

        try {
            $distances = $this->calculator->getDistanceBetweenPostalCodes($source, $dest);
        } catch (UnknownPostalCodeException $exception) {
            $output->writeln($exception->getMessage(), OutputInterface::OUTPUT_RAW);
            return Command::FAILURE;
        }

        if ($distances === []) {
            $output->writeln('No distances found for the requested postal codes.');
            return Command::FAILURE;
        }

        foreach ($distances as $sourceCode => $destinations) {
            foreach ($destinations as $destinationCode => $distance) {
                $output->writeln(sprintf('The distance between %s and %s is %s KM', $sourceCode, $destinationCode, $distance->getDistance()));
            }
        }

        return Command::SUCCESS;
    }
}

