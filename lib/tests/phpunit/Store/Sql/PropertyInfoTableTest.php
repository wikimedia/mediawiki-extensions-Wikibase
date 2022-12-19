<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
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
class PropertyInfoTableTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function setUp(): void {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local wb_property_info table." );
		}

		$this->tablesUsed[] = 'wb_property_info';
	}

	private function newPropertyInfoTable( bool $allowWrites = true ) {
		return new PropertyInfoTable(
			$this->getEntityComposer(),
			$this->getRepoDomainDb(),
			$allowWrites
		);
	}

	public function testGivenNoDataTypeInInfo_setPropertyInfoThrowsException() {
		$table = $this->newPropertyInfoTable();

		$this->expectException( InvalidArgumentException::class );

		$table->setPropertyInfo( new NumericPropertyId( 'P123' ), [ 'foo' => 'bar' ] );
	}

	public function testGivenUnknownPropertyId_getPropertyInfoReturnsNull() {
		$table = $this->newPropertyInfoTable();

		$this->assertNull( $table->getPropertyInfo( new NumericPropertyId( 'P123' ) ) );
	}

	public function testGivenKnownPropertyId_getPropertyInfoReturnsTheInfo() {
		$table = $this->newPropertyInfoTable();
		$propertyId = new NumericPropertyId( 'P123' );

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
			new NumericPropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);
		$table->setPropertyInfo(
			new NumericPropertyId( 'P456' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ]
		);

		$this->assertSame(
			[
				'P123' => [ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
				'P456' => [
					PropertyInfoLookup::KEY_DATA_TYPE => 'external-id',
					PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1',
				],
			],
			$table->getAllPropertyInfo()
		);
	}

	public function testGivenDataTypeNotUsedInProperties_getPropertyInfoForDataTypeReturnsEmptyList() {
		$table = $this->newPropertyInfoTable();
		$table->setPropertyInfo(
			new NumericPropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);

		$this->assertSame( [], $table->getPropertyInfoForDataType( 'external-id' ) );
	}

	public function testGivenDataTypeUsedInSomeProperties_getPropertyInfoForDataTypeReturnsInfoForRelevantOnes() {
		$table = $this->newPropertyInfoTable();
		$table->setPropertyInfo(
			new NumericPropertyId( 'P123' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ]
		);
		$table->setPropertyInfo(
			new NumericPropertyId( 'P456' ),
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'external-id', PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1' ]
		);

		$this->assertSame(
			[
				'P456' => [
					PropertyInfoLookup::KEY_DATA_TYPE => 'external-id',
					PropertyInfoLookup::KEY_FORMATTER_URL => 'http://foo.bar/$1',
				],
			],
			$table->getPropertyInfoForDataType( 'external-id' )
		);
	}

	public function testGivenKnownPropertyId_removePropertyInfoRemovesTheEntryAndReturnsTrue() {
		$table = $this->newPropertyInfoTable();
		$propertyId = new NumericPropertyId( 'P123' );

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
		$propertyId = new NumericPropertyId( 'P123' );

		$this->assertNull( $table->getPropertyInfo( $propertyId ) );

		$this->assertFalse( $table->removePropertyInfo( $propertyId ) );

		$this->assertNull( $table->getPropertyInfo( $propertyId ) );
	}

	public function testSettingAndRemovingPropertyInfoIsPersistent() {
		$tableOne = $this->newPropertyInfoTable();
		$tableTwo = $this->newPropertyInfoTable();
		$propertyId = new NumericPropertyId( 'P123' );

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

	public function testGivenNonWriting_setPropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTable( false );
		$this->expectException( InvalidArgumentException::class );
		$infoTable->setPropertyInfo( new NumericPropertyId( 'P1' ), [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );
	}

	public function testGivenNonWriting_removePropertyInfoThrowsException() {
		$infoTable = $this->newPropertyInfoTable( false );
		$this->expectException( InvalidArgumentException::class );
		$infoTable->removePropertyInfo( new NumericPropertyId( 'P1' ) );
	}

	private function getEntityComposer() {
		return new EntityIdComposer( [
			Property::ENTITY_TYPE => function( $repository, $uniquePart ) {
				return NumericPropertyId::newFromRepositoryAndNumber( $repository, $uniquePart );
			},
		] );
	}

}
