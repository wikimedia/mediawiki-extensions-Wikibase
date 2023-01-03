<?php

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use DataValues\Deserializers\DataValueDeserializer;
use MediaWiki\Revision\SlotRecord;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseServicesTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions(
				[ new DatabaseEntitySource(
					'item',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 0, 'slot' => SlotRecord::MAIN ] ],
					'https://item.test/entity/',
					'',
					'',
					'item'
				) ],
				new SubEntityTypesMapper( [] )
			) );
		$this->mockService( 'WikibaseClient.EntityIdParser',
			new DispatchingEntityIdParser( [] ) );
		$this->mockService( 'WikibaseClient.EntityIdComposer',
			new EntityIdComposer( [] ) );
		$this->mockService( 'WikibaseClient.DataValueDeserializer',
			new DataValueDeserializer() );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getNameTableStoreFactory' );
		$this->mockService( 'WikibaseClient.DataAccessSettings',
			new DataAccessSettings( 0 ) );
		$this->mockService( 'WikibaseClient.LanguageFallbackChainFactory',
			$this->createMock( LanguageFallbackChainFactory::class ) );
		$this->mockService( 'WikibaseClient.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] ) );
		$this->mockService( 'WikibaseClient.RepoDomainDbFactory',
			$this->createMock( RepoDomainDbFactory::class ) );

		$this->assertInstanceOf(
			WikibaseServices::class,
			$this->getService( 'WikibaseClient.WikibaseServices' )
		);
	}
}
