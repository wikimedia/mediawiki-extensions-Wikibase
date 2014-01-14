<?php

namespace Wikibase\Test;

use RuntimeException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyContent;
use Wikibase\PropertyInfoStore;
use Wikibase\PropertyInfoTable;
use Wikibase\PropertyInfoTableBuilder;
use Wikibase\WikiPageEntityLookup;

/**
 * @covers Wikibase\PropertyInfoTableBuilder
 *
 * @license GPL 2+
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group WikibaseRepo
 * @group Database
 * @group medium
 *
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilderTest extends \MediaWikiTestCase {

	protected static function initProperties() {
		static $properties = null;

		if ( $properties === null ) {
			$infos = array(
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'test' => 'one' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'test' => 'two' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'time', 'test' => 'three' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'time', 'test' => 'four' ),
				array( PropertyInfoStore::KEY_DATA_TYPE => 'string', 'test' => 'five' ),
			);

			foreach ( $infos as $info ) {
				$dataType = $info[ PropertyInfoStore::KEY_DATA_TYPE ];
				$label = $info[ 'test' ];

				$content = PropertyContent::newEmpty();
				$content->getProperty()->setDataTypeId( $dataType  );
				$content->getProperty()->setDescription( 'en', $label );

				$status = $content->save( "test", null, EDIT_NEW );

				if ( !$status->isOK() ) {
					throw new RuntimeException( "could not save property: " . $status->getWikiText() );
				}

				$id = $content->getProperty()->getId()->getSerialization();
				$properties[$id] = $info;
			}
		}

		return $properties;
	}

	public function testRebuildPropertyInfo() {
		$properties = self::initProperties();
		$propertyIds = array_keys( $properties );

		$entityLookup = new WikiPageEntityLookup( false );
		$table = new PropertyInfoTable( false );
		$builder = new PropertyInfoTableBuilder( $table, $entityLookup );
		$builder->setBatchSize( 3 );

		// rebuild all ----
		$builder->setFromId( 0 );
		$builder->setRebuildAll( true );

		$builder->rebuildPropertyInfo();

		foreach ( $properties as $id => $expected ) {
			$info = $table->getPropertyInfo( new PropertyId( $id ) );
			$this->assertEquals( $expected[PropertyInfoStore::KEY_DATA_TYPE], $info[PropertyInfoStore::KEY_DATA_TYPE], "Property $id" );
		}

		// make table incomplete ----
		$propId1 = new PropertyId( $propertyIds[0] );
		$table->removePropertyInfo( $propId1 );

		// rebuild from offset, with no effect ----
		$builder->setFromId( $propId1->getNumericId() +1 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		$info = $table->getPropertyInfo( $propId1 );
		$this->assertNull( $info, "rebuild missing from offset should have skipped this" );

		// rebuild all from offset, with no effect ----
		$builder->setFromId( $propId1->getNumericId() +1 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		$info = $table->getPropertyInfo( $propId1 );
		$this->assertNull( $info, "rebuild all from offset should have skipped this" );

		// rebuild missing  ----
		$builder->setFromId( 0 );
		$builder->setRebuildAll( false );

		$builder->rebuildPropertyInfo();

		foreach ( $properties as $propId => $expected ) {
			$info = $table->getPropertyInfo( new PropertyId( $propId ) );
			$this->assertEquals( $expected[PropertyInfoStore::KEY_DATA_TYPE], $info[PropertyInfoStore::KEY_DATA_TYPE], "Property $propId" );
		}

		// rebuild again ----
		$builder->setFromId( 0 );
		$builder->setRebuildAll( false );

		$c = $builder->rebuildPropertyInfo();
		$this->assertEquals( 0, $c, "Thre should be nothing left to rebuild" );
	}

}
