<?php

namespace Wikibase\DataAccess\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use LogicException;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\NameTableStore;
use Serializers\DispatchingSerializer;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikimedia\Assert\ParameterElementTypeException;
use Wikimedia\Rdbms\ILBFactory;

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
			[ 'getEntityPrefetcher', EntityPrefetcher::class, true ],
			[ 'getPropertyInfoLookup', PropertyInfoLookup::class, true ],
			[ 'getEntitySource', DatabaseEntitySource::class, true ],
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
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->createMock( NameTableStore::class ),
			DataAccessSettingsFactory::anySettings(),
			new DatabaseEntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			new LanguageFallbackChainFactory(),
			new DispatchingSerializer(),
			new RepoDomainDb( $this->createMock( ILBFactory::class ), 'some domain' ),
			[ 'strval' ],
			[],
			[],
			[]
		);

		$this->expectException( LogicException::class );
		$services->getPropertyInfoLookup();
	}

	public function testInvalidConstruction_deserializeFactoryCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->createMock( NameTableStore::class ),
			DataAccessSettingsFactory::anySettings(),
			new DatabaseEntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			new LanguageFallbackChainFactory(),
			new DispatchingSerializer(),
			new RepoDomainDb( $this->createMock( ILBFactory::class ), 'some domain' ),
			[ null ],
			[],
			[],
			[]
		);
	}

	public function testInvalidConstruction_entityMetaDataAccessorCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->createMock( NameTableStore::class ),
			DataAccessSettingsFactory::anySettings(),
			new DatabaseEntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			new LanguageFallbackChainFactory(),
			new DispatchingSerializer(),
			new RepoDomainDb( $this->createMock( ILBFactory::class ), 'some domain' ),
			[],
			[ null ],
			[],
			[]
		);
	}

	public function testInvalidConstruction_prefetchingTermLookupCallbacks() {
		$this->expectException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->createMock( NameTableStore::class ),
			DataAccessSettingsFactory::anySettings(),
			new DatabaseEntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			new LanguageFallbackChainFactory(),
			new DispatchingSerializer(),
			new RepoDomainDb( $this->createMock( ILBFactory::class ), 'some domain' ),
			[],
			[],
			[ null ],
			[]
		);
	}

	public function newSingleEntitySourceServices() {
		return new SingleEntitySourceServices(
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->createMock( NameTableStore::class ),
			DataAccessSettingsFactory::anySettings(),
			new DatabaseEntitySource(
				'source',
				'sourcedb',
				[ 'property' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ] ],
				'',
				'',
				'',
				''
			),
			new LanguageFallbackChainFactory(),
			new DispatchingSerializer(),
			new RepoDomainDb( $this->createMock( ILBFactory::class ), 'some domain' ),
			[],
			[],
			[],
			[]
		);
	}

	// TODO test entityUpdated
	// TODO test redirectUpdated
	// TODO test entityDeleted

}
