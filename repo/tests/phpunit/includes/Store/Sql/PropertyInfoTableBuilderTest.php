<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use DataValues\StringValue;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Repo\PropertyInfoBuilder;
use Wikibase\Repo\Store\Sql\PropertyInfoTableBuilder;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\Sql\PropertyInfoTableBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilderTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'wb_property_info';
	}

	private function initProperties() {
		$infos = [
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'one' ],
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'two', PropertyInfoLookup::KEY_FORMATTER_URL => 'foo' ],
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'time', 'test' => 'three' ],
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'time', 'test' => 'four' ],
			[ PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'five', PropertyInfoLookup::KEY_FORMATTER_URL => 'bar' ],
			[
				PropertyInfoLookup::KEY_DATA_TYPE => 'string',
				'test' => 'six',
				PropertyInfoStore::KEY_CANONICAL_URI => 'zoo',
			],
		];

		$store = WikibaseRepo::getEntityStore();
		$properties = [];

		foreach ( $infos as $info ) {
			$property = Property::newFromType( $info[PropertyInfoLookup::KEY_DATA_TYPE] );
			$property->setDescription( 'en', $info['test'] );

			if ( isset( $info[PropertyInfoLookup::KEY_FORMATTER_URL] ) ) {
				$mainSnak = new PropertyValueSnak( 1630, new StringValue( $info[PropertyInfoLookup::KEY_FORMATTER_URL] ) );
				$property->getStatements()->addNewStatement( $mainSnak );
			}

			if ( isset( $info[PropertyInfoStore::KEY_CANONICAL_URI] ) ) {
				$mainSnak = new PropertyValueSnak(
					1640,
					new StringValue( $info[PropertyInfoStore::KEY_CANONICAL_URI] )
				);
				$property->getStatements()->addNewStatement( $mainSnak );
			}

			$revision = $store->saveEntity( $property, "test", $this->getTestUser()->getUser(), EDIT_NEW );

			$id = $revision->getEntity()->getId()->getSerialization();
			$properties[$id] = $info;
		}

		return $properties;
	}

	private function resetPropertyInfoTable( PropertyInfoTable $table ) {
		$dbw = $table->getDomainDb()->connections()->getWriteConnection();
		$dbw->delete( 'wb_property_info', '*' );
	}

	public function testRebuildPropertyInfo() {
		$table = new PropertyInfoTable(
			WikibaseRepo::getEntityIdComposer(),
			$this->getRepoDomainDb(),
			true
		);
		$this->resetPropertyInfoTable( $table );
		$properties = $this->initProperties();

		// NOTE: We use the EntityStore from WikibaseRepo in initProperties,
		//       so we should also use the EntityLookup from WikibaseRepo.
		$propertyLookup = new LegacyAdapterPropertyLookup(
			WikibaseRepo::getStore()->getEntityLookup( Store::LOOKUP_CACHING_DISABLED )
		);

		$propertyInfoBuilder = new PropertyInfoBuilder( [
			PropertyInfoLookup::KEY_FORMATTER_URL => new NumericPropertyId( 'P1630' ),
			PropertyInfoStore::KEY_CANONICAL_URI => new NumericPropertyId( 'P1640' ),
		] );
		$builder = new PropertyInfoTableBuilder(
			$table,
			$propertyLookup,
			$propertyInfoBuilder,
			WikibaseRepo::getEntityNamespaceLookup()
		);
		$builder->setBatchSize( 3 );

		$builder->setRebuildAll( true );

		$builder->rebuildPropertyInfo();

		$this->assertTableHasProperties( $properties, $table );
	}

	private function assertTableHasProperties( array $properties, PropertyInfoTable $table ) {
		foreach ( $properties as $propId => $expected ) {
			$info = $table->getPropertyInfo( new NumericPropertyId( $propId ) );
			$this->assertEquals(
				$expected[PropertyInfoLookup::KEY_DATA_TYPE],
				$info[PropertyInfoLookup::KEY_DATA_TYPE],
				"Property $propId"
			);

			if ( isset( $expected[PropertyInfoLookup::KEY_FORMATTER_URL] ) ) {
				$this->assertEquals(
					$expected[PropertyInfoLookup::KEY_FORMATTER_URL],
					$info[PropertyInfoLookup::KEY_FORMATTER_URL]
				);
			} else {
				$this->assertArrayNotHasKey( PropertyInfoLookup::KEY_FORMATTER_URL, $info );
			}

			if ( isset( $expected[PropertyInfoStore::KEY_CANONICAL_URI] ) ) {
				$this->assertEquals(
					$expected[PropertyInfoStore::KEY_CANONICAL_URI],
					$info[PropertyInfoStore::KEY_CANONICAL_URI]
				);
			} else {
				$this->assertArrayNotHasKey( PropertyInfoStore::KEY_CANONICAL_URI, $info );
			}
		}
	}

}
