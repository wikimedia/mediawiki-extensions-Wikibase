<?php

namespace Wikibase\DataAccess\Tests;

use DataValues\Deserializers\DataValueDeserializer;
use MediaWiki\Storage\NameTableStore;
use PHPUnit4And6Compat;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityRevisionLookup;
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
			[ 'getEntityRevisionLookup', EntityRevisionLookup::class, true ]
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

	public function testInvalidConstruction_deserializeFactoryCallbacks() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			new DataAccessSettings( 10, true, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySource( 'source', 'sourcedb', [] ),
			[ null ],
			[]
		);
	}

	public function testInvalidConstruction_entityMetaDataAccessorCallbacks() {
		$this->setExpectedException( ParameterElementTypeException::class );
		new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			new DataAccessSettings( 10, true, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySource( 'source', 'sourcedb', [] ),
			[],
			[ null ]
		);
	}

	public function newSingleEntitySourceServices() {
		return new SingleEntitySourceServices(
			$this->newGenericServices(),
			new BasicEntityIdParser(),
			new DataValueDeserializer( [] ),
			$this->getMockNameTableStore(),
			new DataAccessSettings( 10, true, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			new EntitySource( 'source', 'sourcedb', [] ),
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
