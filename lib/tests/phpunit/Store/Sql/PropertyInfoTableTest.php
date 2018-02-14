<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWikiTestCase;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\WikibaseSettings;

/**
 * @covers Wikibase\Lib\Store\Sql\PropertyInfoTable
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoTableTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_property_info table." );
		}

		$this->tablesUsed[] = 'wb_property_info';
	}

	private function newPropertyInfoTable( $repository = '' ) {
		return new PropertyInfoTable( $this->getEntityComposer(), false, $repository );
	}

	public function testGivenNoDataTypeInInfo_setPropertyInfoThrowsException() {
		$table = $this->newPropertyInfoTable();

		$this->setExpectedException( InvalidArgumentException::class );

		$table->setPropertyInfo( new PropertyId( 'P123' ), [ 'foo' => 'bar' ] );
	}

	public function testGivenUnknownPropertyId_getPropertyInfoReturnsNull() {
		$table = $this->newPropertyInfoTable();

		$this->assertNull( $table->getPropertyInfo( new PropertyId( 'P123' ) ) );
	}

	public function testGivenKnownPropertyId_getPropertyInfoTheInfo() {
		$table = $this->newPropertyInfoTable();
		$propertyId = new PropertyId( 'P123' );

		$table->setPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ] );

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			$table->getPropertyInfo( $propertyId )
		);
	}

	public function testGivenNoInfo_getAllPropertyInfoReturnsEmptyList() {
		$table = $this->newPropertyInfoTable();

		$this->assertSame( [], $table->getAllPropertyInfo() );
	}

	public function testGivenSomeProperties_getAllPropertyInfoReturnsAllInfo() {
		$table = $this->newPropertyInfoTable();
		$table->setPropertyInfo(
			new PropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);
		$table->setPropertyInfo(
			new PropertyId( 'P456' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ]
		);

		$this->assertSame(
			[
				'P123' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
				'P456' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ],
			],
			$table->getAllPropertyInfo()
		);
	}

	public function testGivenDataTypeNotUsedInProperties_getPropertyInfoForDataTypeReturnsEmptyList() {
		$table = $this->newPropertyInfoTable();
		$table->setPropertyInfo(
			new PropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);

		$this->assertSame( [], $table->getPropertyInfoForDataType( 'external-id' ) );
	}

	public function testGivenDataTypeUsedInSomeProperties_getPropertyInfoForDataTypeReturnsInfoForRelevantProperties() {
		$table = $this->newPropertyInfoTable();
		$table->setPropertyInfo(
			new PropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);
		$table->setPropertyInfo(
			new PropertyId( 'P456' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ]
		);

		$this->assertSame(
			[
				'P456' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ],
			],
			$table->getPropertyInfoForDataType( 'external-id' )
		);
	}

	public function testGivenKnownPropertyId_removePropertyInfoRemovesTheEntryAndReturnsTrue() {
		$table = $this->newPropertyInfoTable();
		$propertyId = new PropertyId( 'P123' );

		$table->setPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$table->getPropertyInfo( $propertyId )
		);

		$this->assertTrue( $table->removePropertyInfo( $propertyId ) );

		$this->assertNull( $table->getPropertyInfo( $propertyId ) );
	}

	public function testGivenUnknownPropertyId_removePropertyInfoReturnsFalse() {
		$table = $this->newPropertyInfoTable();
		$propertyId = new PropertyId( 'P123' );

		$this->assertNull( $table->getPropertyInfo( $propertyId ) );

		$this->assertFalse( $table->removePropertyInfo( $propertyId ) );

		$this->assertNull( $table->getPropertyInfo( $propertyId ) );
	}

	public function testSettingAndRemovingPropertyInfoIsPersistent() {
		$tableOne = $this->newPropertyInfoTable();
		$tableTwo = $this->newPropertyInfoTable();
		$propertyId = new PropertyId( 'P123' );

		$this->assertNull( $tableOne->getPropertyInfo( $propertyId ) );
		$this->assertNull( $tableTwo->getPropertyInfo( $propertyId ) );

		$tableOne->setPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );

		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$tableOne->getPropertyInfo( $propertyId )
		);
		$this->assertSame(
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			$tableTwo->getPropertyInfo( $propertyId )
		);

		$tableTwo->removePropertyInfo( $propertyId );

		$this->assertNull( $tableTwo->getPropertyInfo( $propertyId ) );
		$this->assertNull( $tableOne->getPropertyInfo( $propertyId ) );
	}

	/**
	 * @dataProvider invalidRepositoryNameProvider
	 */
	public function testGivenInvalidRepositoryName_throwsException( $name ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new PropertyInfoTable( $this->getEntityComposer(), false, $name );
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

		$infoTable = new PropertyInfoTable( $this->getEntityComposer(), false, $repositoryName );
		$infoTable->setPropertyInfo( $id, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );
	}

	/**
	 * @dataProvider incompatibleRepositoryNameAndPropertyIdProvider
	 */
	public function testGivenPropertyIdFromWrongRepository_getPropertyInfoThrowsException( $repositoryName, PropertyId $id ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$infoTable = new PropertyInfoTable( $this->getEntityComposer(), false, $repositoryName );
		$infoTable->getPropertyInfo( $id );
	}

	/**
	 * @dataProvider incompatibleRepositoryNameAndPropertyIdProvider
	 */
	public function testGivenPropertyIdFromWrongRepository_removePropertyInfoThrowsException( $repositoryName, PropertyId $id ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$infoTable = new PropertyInfoTable( $this->getEntityComposer(), false, $repositoryName );
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
			'P123' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P23' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'commonsMedia' ],
			'P1337' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ],
		];

		foreach ( $infos as $id => $info ) {
			$table->setPropertyInfo( new PropertyId( $id ), $info );
		}
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $repository, $uniquePart ) {
				return PropertyId::newFromRepositoryAndNumber( $repository, $uniquePart );
			},
		] );
	}

}
