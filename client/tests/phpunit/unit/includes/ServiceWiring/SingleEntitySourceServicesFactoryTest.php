<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServicesFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->mockService(
			'WikibaseClient.EntityIdParser',
			new DispatchingEntityIdParser( [] )
		);

		$this->mockService(
			'WikibaseClient.EntityIdComposer',
			new EntityIdComposer( [] )
		);

		$this->mockService(
			'WikibaseClient.DataValueDeserializer',
			new DataValueDeserializer()
		);

		$this->serviceContainer->expects( $this->once() )
			->method( 'getNameTableStoreFactory' );

		$this->mockService(
			'WikibaseClient.DataAccessSettings',
			new DataAccessSettings( 0 )
		);

		$this->mockService(
			'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);

		$this->assertInstanceOf(
			SingleEntitySourceServicesFactory::class,
			$this->getService( 'WikibaseClient.SingleEntitySourceServicesFactory' )
		);
	}
}
