<?php

namespace Wikibase\DataAccess\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use LogicException;
use MediaWiki\Storage\NameTableStore;
use PHPUnit\Framework\MockObject\MockObject;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikimedia\Assert\ParameterElementTypeException;

/**
 * @covers \Wikibase\DataAccess\SingleEntitySourceServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServicesTest extends \PHPUnit\Framework\TestCase {

	public function testValidConstruction() {
		$this->newSingleEntitySourceServices();
		$this->assertTrue( true );
	}

	public function provideSimpleServiceGetters() {
		return [
			[ 'getEntityRevisionLookup', EntityRevisionLookup::class, true ],
			[ 'getEntityInfoBuilder', EntityInfoBuilder::class, true ],
			[ 'getTermSearchInteractorFactory', TermSearchInteractorFactory::class, true ],
			[ 'getPrefetchingTermLookup', PrefetchingTermLookup::class, true ],
			[ 'getEntityPrefetcher', EntityPrefetcher::class, true ],
			[ 'getPropertyInfoLookup', PropertyInfoLookup::class, true ],
		];
	}

	/**
	 * @dataProvider provideSimpleServiceGetters
	 */
	public function testSimpleServiceGetters( $function, $expected, $expectSame ) {
		$services = $this->newSingleEntitySourceServices();

		$serviceOne = $services->$function();
		$serviceTwo = $services->$function();

		$this->assertInstanceOf( $expected, $serviceOne );

		if ( $expectSame ) {
			$this->assertSame( $serviceOne, $serviceTwo );
		} else {
			$this->assertNotSame( $serviceOne, $serviceTwo );
		}
	}

	public function testGivenEntitySourceDoesNotProvideProperties_getPropertyInfoLookupThrowsException() {
		$services = new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[ 'strval' ],
			[],
			[]
		);

		$this->expectException( LogicException::class );
		$services->getPropertyInfoLookup();
	}

	public function testInvalidConstruction_deserializeFactoryCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[ null ],
			[],
			[]
		);
	}

	public function testInvalidConstruction_entityMetaDataAccessorCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[],
			[ null ],
			[]
		);
	}

	public function testInvalidConstruction_prefetchingTermLookupCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[],
			[],
			[ null ]
		);
	}

	public function testGivenCustomLookupConstructingCallbacks_getPrefetchingTermLookupReturnsCustomLookup() {
		$customLookup = $this->createMock( PrefetchingTermLookup::class );
		$customLookup->method( $this->anything() )
			->willReturn( 'CUSTOM' );

		$customItemLookupCallback = function() use ( $customLookup ) {
			return $customLookup;
		};

		$services = new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource(
				'source',
				'sourcedb',
				[ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ], 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ],
				'',
				'',
				'',
				''
			),
			[],
			[],
			[ 'item' => $customItemLookupCallback ]
		);

		$lookup = $services->getPrefetchingTermLookup();

		$this->assertSame( 'CUSTOM', $lookup->getLabel( new ItemId( 'Q123' ), 'fake' ) );
	}

	public function newSingleEntitySourceServices() {
		return new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ], '', '', '', '' ),
			[],
			[],
			[]
		);
	}

	public function newGenericServices() {
		return new GenericServices(
			new EntityTypeDefinitions( [] ),
			[],
			[]
		);
	}

	/**
	 * @return MockObject|NameTableStore
	 */
	private function getMockNameTableStore() {
		$m = $this->getMockBuilder( NameTableStore::class );
		return $m->disableOriginalConstructor()->getMock();
	}

	// TODO test entityUpdated
	// TODO test redirectUpdated
	// TODO test entityDeleted

}
