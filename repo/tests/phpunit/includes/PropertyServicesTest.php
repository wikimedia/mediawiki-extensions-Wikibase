<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\PropertyServices;

/**
 * @covers \Wikibase\Repo\PropertyServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyServicesTest extends TestCase {

	public function testGetServiceByName(): void {
		$serviceName = 'some-service';
		$definitions = [
			$serviceName => [
				ApiEntitySource::TYPE => function () {
					return 'api service';
				},
				DatabaseEntitySource::TYPE => function () {
					return 'db service';
				},
			],
		];

		$apiSourceName = 'apisource';
		$dbSourceName = 'dbsource';
		$services = new PropertyServices(
			new EntitySourceDefinitions( [
				new ApiEntitySource( $apiSourceName, [ 'property' ], 'someUrl', '', '', '' ),
				NewDatabaseEntitySource::havingName( $dbSourceName )->build(),
			], new SubEntityTypesMapper( [] ) ),
			$definitions
		);

		$serviceCallbacksBySource = $services->get( $serviceName );

		$this->assertArrayHasKey( $apiSourceName, $serviceCallbacksBySource );
		$this->assertArrayHasKey( $dbSourceName, $serviceCallbacksBySource );

		$this->assertSame( 'api service', $serviceCallbacksBySource[$apiSourceName]() );
		$this->assertSame( 'db service', $serviceCallbacksBySource[$dbSourceName]() );
	}

	public function testGivenUndefinedServiceName_throws(): void {
		$sourceDefinitions = $this->createStub( EntitySourceDefinitions::class );
		$sourceDefinitions->method( 'getSources' )->willReturn( [] );
		$services = new PropertyServices( $sourceDefinitions, [] );

		$this->expectException( LogicException::class );

		$services->get( 'notaservice' );
	}

}
