<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\PropertyInfoBuilder;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\PropertyInfoTableBuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\PropertyInfoTableBuilder
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilderTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wb_property_info';
	}

	private function initProperties() {
		$infos = array(
			array( PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'one' ),
			array( PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'two', PropertyInfoLookup::KEY_FORMATTER_URL => 'foo' ),
			array( PropertyInfoLookup::KEY_DATA_TYPE => 'time', 'test' => 'three' ),
			array( PropertyInfoLookup::KEY_DATA_TYPE => 'time', 'test' => 'four' ),
			array( PropertyInfoLookup::KEY_DATA_TYPE => 'string', 'test' => 'five', PropertyInfoLookup::KEY_FORMATTER_URL => 'bar' ),
		);

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$properties = array();

		foreach ( $infos as $info ) {
			$property = Property::newFromType( $info[PropertyInfoLookup::KEY_DATA_TYPE] );
			$property->setDescription( 'en', $info['test'] );

			if ( isset( $info[PropertyInfoLookup::KEY_FORMATTER_URL] ) ) {
				$mainSnak = new PropertyValueSnak( 1630, new StringValue( $info[PropertyInfoLookup::KEY_FORMATTER_URL] ) );
				$property->getStatements()->addNewStatement( $mainSnak );
			}

			$revision = $store->saveEntity( $property, "test", $GLOBALS['wgUser'], EDIT_NEW );

			$id = $revision->getEntity()->getId()->getSerialization();
			$properties[$id] = $info;
		}

		return $properties;
	}

	private function resetPropertyInfoTable( PropertyInfoTable $table ) {
		$dbw = $table->getWriteConnection();
		$dbw->delete( 'wb_property_info', '*' );
	}

	public function testRebuildPropertyInfo() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$table = new PropertyInfoTable( $wikibaseRepo->getEntityIdComposer() );
		$this->resetPropertyInfoTable( $table );
		$properties = $this->initProperties();
		$propertyIds = array_keys( $properties );

		// NOTE: We use the EntityStore from WikibaseRepo in initProperties,
		//       so we should also use the EntityLookup from WikibaseRepo.
		$entityLookup = $wikibaseRepo->getEntityLookup( 'uncached' );

		$propertyInfoBuilder = new PropertyInfoBuilder( new PropertyId( 'P1630' ) );
		$builder = new PropertyInfoTableBuilder(
			$table,
			$entityLookup,
			$propertyInfoBuilder,
			$wikibaseRepo->getEntityIdComposer(),
			$wikibaseRepo->getEntityNamespaceLookup()
		);
		$builder->setBatchSize( 3 );

		// rebuild all ----
		$builder->setFromPageId( 0 );
		$builder->setRebuildAll( true );

		$builder->rebuildPropertyInfo();

		$this->assertTableHasProperties( $properties, $table );

		// make table incomplete ----
		$propId1 = new PropertyId( $propertyIds[0] );
		$table->removePropertyInfo( $propId1 );

		// rebuild from offset, with no effect ----
		$builder->setFromPageId( $builder->getFromPageId() + 2 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		$info = $table->getPropertyInfo( $propId1 );
		$this->assertNull( $info, "rebuild missing from offset should have skipped this" );

		// rebuild all from offset, with no effect ----
		$builder->setFromPageId( $builder->getFromPageId() + 2 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		$info = $table->getPropertyInfo( $propId1 );
		$this->assertNull( $info, "rebuild all from offset should have skipped this" );

		// rebuild missing  ----
		$builder->setFromPageId( 0 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		$this->assertTableHasProperties( $properties, $table );

		// rebuild again ----
		$builder->setFromPageId( 0 );
		$builder->setRebuildAll( false );

		$c = $builder->rebuildPropertyInfo();
		$this->assertEquals( 0, $c, "There should be nothing left to rebuild" );
	}

	private function assertTableHasProperties( array $properties, PropertyInfoTable $table ) {
		foreach ( $properties as $propId => $expected ) {
			$info = $table->getPropertyInfo( new PropertyId( $propId ) );
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
		}
	}

}
