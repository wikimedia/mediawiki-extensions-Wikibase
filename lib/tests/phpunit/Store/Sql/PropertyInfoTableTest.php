<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWikiTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\PropertyInfoTable
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

	protected function setUp() : void {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_property_info table." );
		}

		$this->tablesUsed[] = 'wb_property_info';
	}

	private function newPropertyInfoTable() {
		$irrelevantPropertyNamespaceId = 200;

		return new PropertyInfoTable(
			$this->getEntityComposer(),
			new EntitySource(
				'testsource',
				false,
				[
					'property' => [ 'namespaceId' => $irrelevantPropertyNamespaceId, 'slot' => 'main' ],
				],
				'',
				'',
				'',
				''
			)
		);
	}

	public function testGivenNoDataTypeInInfo_setPropertyInfoThrowsException() {
		$table = $this->newPropertyInfoTable();

		$this->expectException( InvalidArgumentException::class );

		$table->setPropertyInfo( new PropertyId( 'P123' ), [ 'foo' => 'bar' ] );
	}

	public function testGivenUnknownPropertyId_getPropertyInfoReturnsNull() {
		$table = $this->newPropertyInfoTable();

		$this->assertNull( $table->getPropertyInfo( new PropertyId( 'P123' ) ) );
	}

	public function testGivenKnownPropertyId_getPropertyInfoReturnsTheInfo() {
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

	public function testGivenDataTypeUsedInSomeProperties_getPropertyInfoForDataTypeReturnsInfoForRelevantOnes() {
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

	public function testGivenPropertyIdAndSourceDoesNotProvideProperties_setPropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTableForItemOnlySource();
		$this->expectException( InvalidArgumentException::class );
		$infoTable->setPropertyInfo( new PropertyId( 'P1' ), [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );
	}

	public function testGivenPropertyIdAndSourceDoesNotProvideProperties_getPropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTableForItemOnlySource();
		$this->expectException( InvalidArgumentException::class );
		$infoTable->getPropertyInfo( new PropertyId( 'P1' ) );
	}

	public function testGivenPropertyIdAndSourceDoesNotProvideProperties_removePropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTableForItemOnlySource();
		$this->expectException( InvalidArgumentException::class );
		$infoTable->removePropertyInfo( new PropertyId( 'P1' ) );
	}

	public function testGivenPropertyIdAndNotLocalSource_setPropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTableForNonLocalOnlySource();
		$this->expectException( InvalidArgumentException::class );
		$infoTable->setPropertyInfo( new PropertyId( 'P1' ), [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );
	}

	public function testGivenPropertyIdAndNotLocalSource_removePropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTableForNonLocalOnlySource();
		$this->expectException( InvalidArgumentException::class );
		$infoTable->removePropertyInfo( new PropertyId( 'P1' ) );
	}

	private function newPropertyInfoTableForItemOnlySource() {
		$irrelevantItemNamespaceId = 100;

		return new PropertyInfoTable(
			$this->getEntityComposer(),
			new EntitySource(
				'testsource',
				false,
				[ 'item' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => 'main' ] ],
				'',
				'',
				'',
				''
			)
		);
	}

	private function newPropertyInfoTableForNonLocalOnlySource() {
		$irrelevantItemNamespaceId = 100;

		return new PropertyInfoTable(
			$this->getEntityComposer(),
			new EntitySource(
				'testsource',
				'nonlocaldb',
				[ 'property' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => 'main' ] ],
				'',
				'',
				'',
				''
			)
		);
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $repository, $uniquePart ) {
				return PropertyId::newFromRepositoryAndNumber( $repository, $uniquePart );
			},
		] );
	}

}
