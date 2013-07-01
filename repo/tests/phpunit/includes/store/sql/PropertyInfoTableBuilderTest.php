<?php
 /**
 *
 * Copyright © 01.07.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibasePropertyInfo
 * @group Database
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test;


use RuntimeException;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyInfoStore;
use Wikibase\PropertyInfoTable;
use Wikibase\PropertyInfoTableBuilder;
use Wikibase\WikiPageEntityLookup;

/**
 * Class PropertyInfoTableBuilderTest
 *
 * @covers PropertyInfoTableBuilder
 *
 * @package Wikibase\Test
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

				$id = $content->getProperty()->getId()->getNumericId();
				$properties[$id] = $info;
			}
		}

		return $properties;
	}

	public function testRebuildPropertyInfo() {
		$properties = self::initProperties();
		$propertyIds = array_keys( $properties );

		$entityLookup = new WikiPageEntityLookup( false, false );
		$table = new PropertyInfoTable( false );
		$builder = new PropertyInfoTableBuilder( $table, $entityLookup );
		$builder->setBatchSize( 3 );

		// rebuild all ----
		$builder->setFromId( 0 );
		$builder->setRebuildAll( true );

		$builder->rebuildPropertyInfo();

		foreach ( $properties as $id => $expected ) {
			$info = $table->getPropertyInfo( new EntityId( Property::ENTITY_TYPE, $id ) );
			$this->assertEquals( $expected[PropertyInfoStore::KEY_DATA_TYPE], $info[PropertyInfoStore::KEY_DATA_TYPE], "Property $id" );
		}

		// make table incomplete ----
		$propId1 = new EntityId( Property::ENTITY_TYPE, $propertyIds[0] );
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
			$info = $table->getPropertyInfo( new EntityId( Property::ENTITY_TYPE, $propId ) );
			$this->assertEquals( $expected[PropertyInfoStore::KEY_DATA_TYPE], $info[PropertyInfoStore::KEY_DATA_TYPE], "Property $id" );
		}

		// rebuild again ----
		$builder->setFromId( 0 );
		$builder->setRebuildAll( false );

		$c = $builder->rebuildPropertyInfo();
		$this->assertEquals( 0, $c, "Thre should be nothing left to rebuild" );
	}

}