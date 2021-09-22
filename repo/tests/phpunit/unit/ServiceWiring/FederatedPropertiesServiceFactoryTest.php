<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use HashConfig;
use LogicException;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesServiceFactoryTest extends ServiceWiringTestCase {

	public function testFederatedPropertiesEnabled(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => true,
				'federatedPropertiesSourceScriptUrl' => 'https://wiki.example/w/',
			] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getHttpRequestFactory' );
		$this->mockService( 'WikibaseRepo.ContentModelMappings',
			[] );
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [] ) );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getMainConfig' )
			->willReturn( new HashConfig( [
				'ServerName' => 'https://other-wiki.example/w/',
			] ) );
		$source = NewDatabaseEntitySource::havingName( 'some source' )
			->withConceptBaseUri( 'http://wikidata.org/entity/' )
			->build();
		$subEntityTypesMapper = new SubEntityTypesMapper( [] );
		$entitySourceDefinitions = new EntitySourceDefinitions( [ $source ], $subEntityTypesMapper );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions', $entitySourceDefinitions );

		$this->assertInstanceOf(
			ApiServiceFactory::class,
			$this->getService( 'WikibaseRepo.FederatedPropertiesServiceFactory' )
		);
	}

	/** @dataProvider provideSettingsWithoutFederatedProperties */
	public function testFederatedPropertiesNotEnabled( array $settings ): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( $settings ) );

		$this->expectException( LogicException::class );
		$this->getService( 'WikibaseRepo.FederatedPropertiesServiceFactory' );
	}

	public function provideSettingsWithoutFederatedProperties(): iterable {
		yield 'federated properties not enabled' => [ [
			'federatedPropertiesEnabled' => false,
		] ];
		yield 'source script URL not configured' => [ [
			'federatedPropertiesEnabled' => true,
		] ];
	}

}
