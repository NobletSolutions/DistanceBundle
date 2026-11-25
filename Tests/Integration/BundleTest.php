<?php

namespace NS\DistanceBundle\Tests\Integration;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use ZipArchive;

class BundleTest extends TestCase
{
    public function testBundleRoutesCommandsAndMapping(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('This integration test requires pdo_sqlite.');
        }

        $kernel = new class('test', false) extends Kernel {
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

        try {
            $kernel->boot();
            $container = $kernel->getContainer();
            $this->assertInstanceOf(DistanceCalculator::class, $container->get('ns_distance.calculator'));
            $checker = $container->get('ns_distance.postalcode_checker');
            $this->assertInstanceOf(PostalCodeChecker::class, $checker);

            $manager = $container->get('doctrine')->getManager();
            $metadata = $manager->getClassMetadata(PostalCode::class);
            $this->assertSame(['postal_code'], $metadata->table['uniqueConstraints']['postal_code_idx']['columns']);
            (new SchemaTool($manager))->createSchema([$metadata]);
            foreach (['T3A5J4' => 51.02, 'T2L0W2' => 51.09] as $code => $latitude) {
                $manager->persist((new PostalCode())->setPostalCode($code)->setLatitude($latitude)
                    ->setLongitude(-114.11)->setCity('Calgary')->setProvince('AB'));
            }
            $manager->flush();

            $this->assertInstanceOf(PostalCodeRepository::class, $manager->getRepository(PostalCode::class));
            $this->assertSame('T3A5J4', $checker->getLatitudeAndLongitude('T3A 5J4')->getPostalCode());

            $response = $kernel->handle(Request::create('/rest/distance/T3A5J4/T2L0W2,T3A5J4'));
            $this->assertSame(200, $response->getStatusCode());
            $distances = json_decode($response->getContent(), true);
            $this->assertEquals(0, $distances['T3A5J4']['T3A5J4']['distance']['K']);
            $this->assertGreaterThan(0, $distances['T3A5J4']['T2L0W2']['distance']['K']);

            $application = new Application($kernel);
            $this->assertTrue($application->has('distance:download'));
            $command = new CommandTester($application->find('distance:calculate'));
            $this->assertSame(0, $command->execute(['source' => 't3a 5j4', 'dest' => 'T2L0W2']));
            $this->assertStringContainsString('The distance between T3A5J4 and T2L0W2 is', $command->getDisplay());

            $path = $kernel->getCacheDir().'/Canada.txt.zip';
            $archive = new ZipArchive();
            $this->assertTrue($archive->open($path, ZipArchive::CREATE));
            $archive->addFromString('CA.txt', "CA\tT3A\tCalgary\tAlberta\tAB\t\t\t\t\t51.02\t-114.11\t6\n");
            $archive->close();

            $import = new CommandTester($application->find('distance:import'));
            $this->assertSame(0, $import->execute(['file' => $path]), $import->getDisplay());
            $connection = $manager->getConnection();
            $this->assertSame(['T3A'], $connection->fetchFirstColumn('SELECT postal_code FROM postalcodes'));

            $connection->executeStatement("CREATE TRIGGER reject_import BEFORE INSERT ON postalcodes
                BEGIN SELECT RAISE(ABORT, 'test import failure'); END");
            $this->assertSame(1, $import->execute(['file' => $path]));
            $this->assertSame(['T3A'], $connection->fetchFirstColumn('SELECT postal_code FROM postalcodes'));
        } finally {
            $kernel->shutdown();
            (new Filesystem())->remove($kernel->getCacheDir());
        }
    }
}
