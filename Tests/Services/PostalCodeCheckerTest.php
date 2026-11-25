<?php

namespace NS\DistanceBundle\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\Repositories\PostalCodeRepository;
use NS\DistanceBundle\Services\PostalCodeChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PostalCodeCheckerTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var PostalCodeRepository|MockObject */
    private $repository;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var PostalCodeChecker */
    private $checker;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->httpClient    = $this->createMock(HttpClientInterface::class);
        $this->repository    = $this->createMock(PostalCodeRepository::class);
        $this->checker       = new PostalCodeChecker($this->entityManager, $this->httpClient, 'apiKey');

        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->entityManager->method('getUnitOfWork')->willReturn($unitOfWork);
    }

    public function testResultIsInDB(): void
    {
        $postCode = new PostalCode();
        $this->repository->expects($this->once())->method('getByCode')->with('T3A5J4')->willReturn($postCode);
        $this->entityManager->expects($this->once())->method('getRepository')->with(PostalCode::class)->willReturn($this->repository);
        $this->assertEquals($postCode, $this->checker->getLatitudeAndLongitude('T3A5J4'));
    }

    public function testResultIsNotInDB(): void
    {
        $this->repository->expects($this->once())->method('getByCode')->with('T3A5J4')->willReturn(null);
        $this->entityManager->expects($this->once())->method('getRepository')->with(PostalCode::class)->willReturn($this->repository);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getContent')->willReturn(file_get_contents(__DIR__.'/response.json'));
        $this->httpClient->expects($this->once())->method('request')->with('GET', 'https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:T3A5J4&sensor=false&key=apiKey')->willReturn($response);
        $postCode = $this->checker->getLatitudeAndLongitude('T3A5J4');
        self::assertInstanceOf(PostalCode::class, $postCode);
        $this->assertEquals('T3A5J4', $postCode->getPostalCode());
        $this->assertEquals(-114.1077228, $postCode->getLongitude());
        $this->assertEquals(51.149276800000003, $postCode->getLatitude());
    }

    public function testRequestIsEmpty(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository')->with(PostalCode::class)->willReturn($this->repository);
        $this->repository->expects($this->never())->method('getByCode')->willReturn(null);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertNull($this->checker->getLatitudeAndLongitude(''));
        $this->assertNull($this->checker->getLatitudeAndLongitude(null));
    }

    public function testGeocodingUsesComponentTypes(): void
    {
        $result = json_decode(file_get_contents(__DIR__.'/response.json'), true);
        $result['results'][0]['address_components'] = [
            ['short_name' => 'ON', 'types' => ['administrative_area_level_1', 'political']],
            ['short_name' => 'Toronto', 'types' => ['locality', 'political']],
            ['short_name' => 'CA', 'types' => ['country', 'political']],
            ['short_name' => 'Old Toronto', 'types' => ['postal_town']],
        ];
        $this->mockGeocodingResult($result);
        $this->entityManager->expects($this->once())->method('persist')->with($this->callback(
            static fn (PostalCode $point): bool => $point->getCity() === 'Toronto' && $point->getProvince() === 'ON'
        ));

        $point = $this->checker->getLatitudeAndLongitude('M5V3L9');
        $this->assertSame('Toronto', $point->getCity());
        $this->assertSame('ON', $point->getProvince());
    }

    public function testGeocodingWithMissingAddressFieldsReturnsCoordinatesWithoutCaching(): void
    {
        $result = json_decode(file_get_contents(__DIR__.'/response.json'), true);
        $result['results'][0]['address_components'] = [
            ['short_name' => 'CA', 'types' => ['country', 'political']],
        ];
        $this->mockGeocodingResult($result);
        $this->entityManager->expects($this->never())->method('persist');

        $point = $this->checker->getLatitudeAndLongitude('T3A5J4');
        $this->assertSame(51.1492768, $point->getLatitude());
        $this->assertSame(-114.1077228, $point->getLongitude());
        $this->assertNull($point->getCity());
        $this->assertNull($point->getProvince());
    }

    private function mockGeocodingResult(array $result): void
    {
        $this->repository->method('getByCode')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($this->repository);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn(json_encode($result));
        $this->httpClient->expects($this->once())->method('request')->willReturn($response);
    }
}
