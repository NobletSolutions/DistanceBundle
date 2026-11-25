<?php

namespace NS\DistanceBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class ImportPostalCodesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('distance:import')
            ->setDescription('Import Postal Codes')
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the Canadian GeoNames ZIP archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('file');
        if (!is_file($path)) {
            $output->writeln("<error>Path not found: $path</error>");
            return Command::FAILURE;
        }

        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            $output->writeln('Unable to open the postal code archive.');
            return Command::FAILURE;
        }

        if (false === $stream = $archive->getStream('CA.txt')) {
            $archive->close();
            $output->writeln('The postal code archive does not contain CA.txt.');
            return Command::FAILURE;
        }

        $rows = [];
        try {
            while (($row = fgetcsv($stream, 0, "\t", '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                if (count($row) < 11) {
                    $output->writeln('CA.txt contains an incomplete postal code row.');
                    return Command::FAILURE;
                }

                $rows[] = [$row[2], $row[4], $row[1], $row[9], $row[10]];
            }
        } finally {
            fclose($stream);
            $archive->close();
        }

        if ($rows === []) {
            $output->writeln('CA.txt contains no postal codes.');
            return Command::FAILURE;
        }

        $connection = $this->entityManager->getConnection();
        $progressBar = new ProgressBar($output, count($rows));
        $output->writeln('Replacing postal codes.');
        $progressBar->start();

        try {
            $connection->transactional(static function (Connection $connection) use ($rows, $progressBar): void {
                $connection->executeStatement('DELETE FROM postalcodes');
                $statement = $connection->prepare(
                    'REPLACE INTO postalcodes (city, province, postal_code, latitude, longitude) VALUES (?, ?, ?, ?, ?)'
                );
                foreach ($rows as $row) {
                    foreach ($row as $index => $value) {
                        $statement->bindValue($index + 1, $value);
                    }
                    $statement->executeStatement();
                    $progressBar->advance();
                }
            });
        } catch (DBALException $exception) {
            $output->writeln(['', 'Unable to import postal codes. '.$exception->getMessage()]);
            return Command::FAILURE;
        }

        $progressBar->finish();
        $output->writeln(['', sprintf('Loaded %d rows', count($rows))]);

        return Command::SUCCESS;
    }
}

