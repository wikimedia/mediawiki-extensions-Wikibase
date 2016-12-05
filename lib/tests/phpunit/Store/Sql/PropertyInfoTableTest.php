<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWikiTestCase;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Tests\Store\PropertyInfoStoreTestHelper;
use Wikibase\PropertyInfoStore;
use Wikibase\PropertyInfoTable;

/**
 * @covers Wikibase\PropertyInfoTable
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoTableTest extends MediaWikiTestCase {

	/**
	 * @var PropertyInfoStoreTestHelper
	 */
	private $helper;

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->helper = new PropertyInfoStoreTestHelper( $this, array( $this, 'newPropertyInfoTable' ) );
	}

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_property_info table." );
		}

		$this->tablesUsed[] = 'wb_property_info';
	}

	public function newPropertyInfoTable( $repository = '' ) {
		return new PropertyInfoTable( false, $this->getEntityComposer(), false, $repository );
	}

	public function provideSetPropertyInfo() {
		return $this->helper->provideSetPropertyInfo();
	}

	/**
	 * @dataProvider provideSetPropertyInfo
	 */
	public function testSetPropertyInfo( PropertyId $id, array $info, $expectedException ) {
		$this->helper->testSetPropertyInfo( $id, $info, $expectedException );
	}

	public function testGetPropertyInfo() {
		$this->helper->testGetPropertyInfo();
	}

	public function testGetPropertyInfoForDataType() {
		$this->helper->testGetPropertyInfoForDataType();
	}

	public function testGetAllPropertyInfo() {
		$this->helper->testGetAllPropertyInfo();
	}

	public function testRemovePropertyInfo() {
		$this->helper->testRemovePropertyInfo();
	}

	public function testPropertyInfoPersistance() {
		$this->helper->testPropertyInfoPersistance();
	}

	/**
	 * @dataProvider invalidRepositoryNameProvider
	 */
	public function testGivenInvalidRepositoryName_throwsException( $name ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new PropertyInfoTable( false, $this->getEntityComposer(), false, $name );
	}

	public function invalidRepositoryNameProvider() {
		return [
			[ 123, ],
			[ false, ],
			[ null, ],
			[ ':foo', ],
			[ 'foo:bar', ],
		];
	}

	/**
	 * @dataProvider incompatibleRepositoryNameAndPropertyIdProvider
	 */
	public function testGivenPropertyIdFromWrongRepository_setPropertyInfoThrowsException( $repositoryName, PropertyId $id ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$infoTable = new PropertyInfoTable( false, $this->getEntityComposer(), false, $repositoryName );
		$infoTable->setPropertyInfo( $id, [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ] );
	}

	/**
	 * @dataProvider incompatibleRepositoryNameAndPropertyIdProvider
	 */
	public function testGivenPropertyIdFromWrongRepository_getPropertyInfoThrowsException( $repositoryName, PropertyId $id ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$infoTable = new PropertyInfoTable( false, $this->getEntityComposer(), false, $repositoryName );
		$infoTable->getPropertyInfo( $id );
	}

	/**
	 * @dataProvider incompatibleRepositoryNameAndPropertyIdProvider
	 */
	public function testGivenPropertyIdFromWrongRepository_removePropertyInfoThrowsException( $repositoryName, PropertyId $id ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$infoTable = new PropertyInfoTable( false, $this->getEntityComposer(), false, $repositoryName );
		$infoTable->removePropertyInfo( $id );
	}

	public function incompatibleRepositoryNameAndPropertyIdProvider() {
		return [
			[ '', new PropertyId( 'foo:P123' ) ],
			[ 'foo', new PropertyId( 'P123' ) ],
			[ 'foo', new PropertyId( 'bar:P123' ) ],
		];
	}

	/**
	 * @dataProvider repositoryNameProvider
	 */
	public function testGivenRepositoryName_getAllPropertyInfoReturnsPropertyIdsFromCorrectRepo( $repository ) {
		$this->persistInfos();
		$table = $this->newPropertyInfoTable( $repository );

		foreach ( $table->getAllPropertyInfo() as $id => $info ) {
			$this->assertSame( $repository, ( new PropertyId( $id ) )->getRepositoryName() );
		}
	}

	/**
	 * @dataProvider repositoryNameProvider
	 */
	public function testGivenRepositoryName_getPropertyInfoForDataTypeReturnsPropertyIdsFromCorrectRepo( $repository ) {
		$this->persistInfos();
		$table = $this->newPropertyInfoTable( $repository );

		foreach ( $table->getPropertyInfoForDataType( 'string' ) as $id => $info ) {
			$this->assertSame( $repository, ( new PropertyId( $id ) )->getRepositoryName() );
		}
		foreach ( $table->getPropertyInfoForDataType( 'commonsMedia' ) as $id => $info ) {
			$this->assertSame( $repository, ( new PropertyId( $id ) )->getRepositoryName() );
		}
	}

	public function repositoryNameProvider() {
		return [
			[ '' ],
			[ 'foo' ],
			[ 'bar' ],
		];
	}

	private function persistInfos() {
		$table = $this->newPropertyInfoTable();
		$infos = [
			'P123' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			'P23' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoStore::KEY_DATA_TYPE => 'commonsMedia' ],
			'P1337' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
		];

		foreach ( $infos as $id => $info ) {
			$table->setPropertyInfo( new PropertyId( $id ), $info );
		}
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $repository, $uniquePart ) {
				return new PropertyId( EntityId::joinSerialization( [ $repository, '', "P$uniquePart" ] ) );
			},
		] );
	}

}
