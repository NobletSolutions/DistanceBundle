<?php

namespace NS\DistanceBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\NSDistanceBundle;
use NS\DistanceBundle\Repositories\PostalCodeRepository;
use NS\DistanceBundle\Services\DistanceCalculator;
use NS\DistanceBundle\Services\PostalCodeChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use ZipArchive;

class BundleTest extends TestCase
{
    private ?Kernel $kernel = null;
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('This integration test requires pdo_sqlite.');
        }

        $this->kernel = new class('test', false) extends Kernel {
            private string $temporaryDir;

            public function __construct(string $environment, bool $debug)
            {
                parent::__construct($environment, $debug);
                $this->temporaryDir = sys_get_temp_dir().'/ns-distance-'.bin2hex(random_bytes(8));
            }

            public function getCacheDir(): string
            {
                return $this->temporaryDir;
            }

            public function registerBundles(): iterable
            {
                yield new FrameworkBundle();
                yield new DoctrineBundle();
                yield new NSDistanceBundle();
            }

            public function registerContainerConfiguration(LoaderInterface $loader): void
            {
                $loader->load(static function (ContainerBuilder $container): void {
                    $container->loadFromExtension('framework', [
                        'secret' => 'test',
                        'router' => ['resource' => '@NSDistanceBundle/Resources/config/routing.yml', 'utf8' => true],
                    ]);
                    $container->loadFromExtension('doctrine', [
                        'dbal' => ['driver' => 'pdo_sqlite', 'memory' => true],
                        'orm' => ['auto_mapping' => true],
                    ]);
                    $container->loadFromExtension('ns_distance', ['api_key' => 'test']);
                });
            }
        };

        $this->kernel->boot();
        $this->manager = $this->kernel->getContainer()->get('doctrine')->getManager();
        (new SchemaTool($this->manager))->createSchema([$this->manager->getClassMetadata(PostalCode::class)]);
    }

    protected function tearDown(): void
    {
        if ($this->kernel !== null) {
            $this->kernel->shutdown();
            (new Filesystem())->remove($this->kernel->getCacheDir());
        }
    }

    public function testBundleRoutesCommandsAndMapping(): void
    {
        $container = $this->kernel->getContainer();
        $this->assertInstanceOf(DistanceCalculator::class, $container->get('ns_distance.calculator'));
        $checker = $container->get('ns_distance.postalcode_checker');
        $this->assertInstanceOf(PostalCodeChecker::class, $checker);

        $metadata = $this->manager->getClassMetadata(PostalCode::class);
        $this->assertSame(['postal_code'], $metadata->table['uniqueConstraints']['postal_code_idx']['columns']);
        $this->seedPostalCodes(['T3A5J4' => 51.02, 'T2L0W2' => 51.09]);

        $this->assertInstanceOf(PostalCodeRepository::class, $this->manager->getRepository(PostalCode::class));
        $this->assertSame('T3A5J4', $checker->getLatitudeAndLongitude('T3A 5J4')->getPostalCode());

        $response = $this->kernel->handle(Request::create('/rest/distance/T3A5J4/T2L0W2,T3A5J4'));
        $this->assertSame(200, $response->getStatusCode());
        $distances = json_decode($response->getContent(), true);
        $this->assertEquals(0, $distances['T3A5J4']['T3A5J4']['distance']['K']);
        $this->assertGreaterThan(0, $distances['T3A5J4']['T2L0W2']['distance']['K']);

        $application = new Application($this->kernel);
        $this->assertTrue($application->has('distance:download'));
        $command = new CommandTester($application->find('distance:calculate'));
        $this->assertSame(0, $command->execute(['source' => 't3a 5j4', 'dest' => 'T2L0W2']));
        $this->assertStringContainsString('The distance between T3A5J4 and T2L0W2 is', $command->getDisplay());
    }

    public function testImportRefreshesPrefixesAndPreservesExactCodes(): void
    {
        $this->seedPostalCodes(['T3A5J4' => 51.09, 'T3A' => 50.0, 'T0Z' => 49.5]);
        $connection = $this->manager->getConnection();
        $connection->insert('postalcodes', [
            'postal_code' => 'T3T 0E5', 'latitude' => 51.09, 'longitude' => -114.11,
            'city' => 'Tsuut’ina', 'province' => 'AB',
        ]);

        $path = $this->makeArchive([
            'T3A' => 51.02, 'T2L' => 51.09, 'T3T' => 51.02,
            ' t3a 5j4 ' => 49.0, ' t3t 0e5 ' => 49.1, ' v3y 0h2 ' => 49.2,
        ]);
        $application = new Application($this->kernel);
        $import = new CommandTester($application->find('distance:import'));
        $this->assertSame(0, $import->execute(['file' => $path]), $import->getDisplay());
        $this->assertSame(0, $import->execute(['file' => $path]), $import->getDisplay());
        $this->assertSame(
            ['T2L', 'T3A', 'T3A5J4', 'T3T', 'T3T 0E5', 'V3Y0H2'],
            $connection->fetchFirstColumn('SELECT postal_code FROM postalcodes ORDER BY postal_code')
        );

        $response = $this->kernel->handle(Request::create('/rest/distance/T2L0W2/T3A5J4,T3A0A1,T3T0E5,T2L0W3'));
        $this->assertSame(200, $response->getStatusCode());
        $distances = json_decode($response->getContent(), true)['T2L0W2'];
        $this->assertEquals(0, $distances['T3A5J4']['distance']['K']);
        $this->assertGreaterThan(0, $distances['T3A0A1']['distance']['K']);
        $this->assertEquals(0, $distances['T3T0E5']['distance']['K']);
        $this->assertEquals(0, $distances['T2L0W3']['distance']['K']);

        $command = new CommandTester($application->find('distance:calculate'));
        $this->assertSame(0, $command->execute(['source' => 'T3A0A1', 'dest' => 'T2L0W2']));
        $this->assertStringContainsString('The distance between T3A0A1 and T2L0W2 is', $command->getDisplay());
        $this->assertSame(1, $command->execute(['source' => 'T3A0A1', 'dest' => 'K1A0B1']));
        $this->assertStringContainsString('No distances found', $command->getDisplay());
    }

    public function testFailedImportPreservesExistingRows(): void
    {
        $this->seedPostalCodes(['T3A5J4' => 51.09, 'T3A' => 51.02]);
        $connection = $this->manager->getConnection();
        $connection->executeStatement("CREATE TRIGGER reject_import BEFORE INSERT ON postalcodes
            WHEN NEW.postal_code = 'T2L' BEGIN SELECT RAISE(ABORT, 'test import failure'); END");

        $path = $this->makeArchive(['T0Z' => 49.5, 'T2L' => 51.09]);
        $import = new CommandTester((new Application($this->kernel))->find('distance:import'));
        $this->assertSame(1, $import->execute(['file' => $path]));
        $this->assertSame(['T3A', 'T3A5J4'], $connection->fetchFirstColumn('SELECT postal_code FROM postalcodes ORDER BY postal_code'));
    }

    public function testRestReturnsZeroForDistinctCodesAtTheSameCoordinates(): void
    {
        $this->seedPostalCodes(['T3A5J4' => 0.08, 'T2L0W2' => 0.08]);
        $response = $this->kernel->handle(Request::create('/rest/distance/T3A5J4/T2L0W2'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(0, json_decode($response->getContent(), true)['T3A5J4']['T2L0W2']['distance']['K']);
    }

    public function testRepeatedGeocodingBeforeFlushReusesThePendingEntity(): void
    {
        $fixture = file_get_contents(__DIR__.'/../Services/response.json');
        $client = new MockHttpClient([new MockResponse($fixture)]);
        $checker = new PostalCodeChecker($this->manager, $client, 'test');

        $first = $checker->getLatitudeAndLongitude('T3A 5J4');
        $this->assertInstanceOf(PostalCode::class, $first);
        $this->assertSame($first, $checker->getLatitudeAndLongitude('t3a5j4'));
        $this->manager->flush();
        $this->assertSame(['T3A5J4'], $this->manager->getConnection()->fetchFirstColumn('SELECT postal_code FROM postalcodes'));
    }

    private function seedPostalCodes(array $latitudes): void
    {
        foreach ($latitudes as $code => $latitude) {
            $this->manager->persist((new PostalCode())->setPostalCode($code)->setLatitude($latitude)
                ->setLongitude(-114.11)->setCity('Calgary')->setProvince('AB'));
        }
        $this->manager->flush();
    }

    private function makeArchive(array $latitudes): string
    {
        $path = $this->kernel->getCacheDir().'/Canada.txt.zip';
        $archive = new ZipArchive();
        $this->assertTrue($archive->open($path, ZipArchive::CREATE));
        $rows = '';
        foreach ($latitudes as $code => $latitude) {
            $rows .= "CA\t$code\tCalgary\tAlberta\tAB\t\t\t\t\t$latitude\t-114.11\t6\n";
        }
        $archive->addFromString('CA.txt', $rows);
        $archive->close();

        return $path;
    }
}
