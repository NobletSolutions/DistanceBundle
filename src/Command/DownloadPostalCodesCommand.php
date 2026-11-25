<?php

namespace NS\DistanceBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DownloadPostalCodesCommand extends Command
{
    private HttpClientInterface $client;
    private string $projectDir;

    public function __construct(HttpClientInterface $client, string $projectDir)
    {
        $this->client = $client;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('distance:download')
            ->setDescription('Download Postal Codes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->client->request('GET', 'https://download.geonames.org/export/zip/CA.zip');
            (new Filesystem())->dumpFile($this->projectDir.'/Canada.txt.zip', $response->getContent());
        } catch (ExceptionInterface | IOExceptionInterface $exception) {
            $output->writeln('Unable to download postal codes. '.$exception->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
