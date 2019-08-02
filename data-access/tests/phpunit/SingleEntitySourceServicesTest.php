<?php

namespace Wikibase\DataAccess\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use MediaWiki\Storage\NameTableStore;
use PHPUnit4And6Compat;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PrefetchingTermLookup;
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
	use PHPUnit4And6Compat;

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

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGivenEntitySourceDoesNotProvideProperties_getPropertyInfoLookupThrowsException() {
		$services = new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[ null ],
			[]
		);

		$services->getPropertyInfoLookup();
	}

	public function testInvalidConstruction_deserializeFactoryCallbacks() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[ null ],
			[]
		);
	}

	public function testInvalidConstruction_entityMetaDataAccessorCallbacks() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			DataAccessSettingsFactory::anySettings(),
			new EntitySource( 'source', 'sourcedb', [], '', '', '', '' ),
			[],
			[ null ]
		);
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
	 * @return \PHPUnit_Framework_MockObject_MockObject|NameTableStore
	 */
	private function getMockNameTableStore() {
		$m = $this->getMockBuilder( NameTableStore::class );
		return $m->disableOriginalConstructor()->getMock();
	}

	// TODO test entityUpdated
	// TODO test redirectUpdated
	// TODO test entityDeleted

}
