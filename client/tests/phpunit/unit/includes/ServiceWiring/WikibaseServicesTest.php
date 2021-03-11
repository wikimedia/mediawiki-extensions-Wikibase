<?php

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\EntityIdParser;
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
class WikibaseServicesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'something' => []
			] ) );

		$this->mockService( 'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				[ new EntitySource(
					'item',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 0, 'slot' => 'main' ] ],
					'https://item.test/entity/',
					'',
					'',
					'item'
				) ],
				new EntityTypeDefinitions( [] )
			) );
		$this->mockService(
			'WikibaseClient.EntityIdParser',
			$this->createMock( EntityIdParser::class )
		);
		$this->mockService(
			'WikibaseClient.EntityIdComposer',
			$this->createMock( EntityIdComposer::class )
		);
		$this->mockService(
			'WikibaseClient.DataValueDeserializer',
			$this->createMock( DataValueDeserializer::class )
		);
		$this->mockService(
			'WikibaseClient.DataAccessSettings',
			$this->createMock( DataAccessSettings::class )
		);
		$this->mockService(
			'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class )
		);

		$this->assertInstanceOf(
			WikibaseServices::class,
			$this->getService( 'WikibaseClient.WikibaseServices' )
		);
	}
}
