<?php

namespace Wikibase\Test;
use Wikibase\Item;
use Wikibase\Query;
use Wikibase\EntityLookup;
use Wikibase\EntityId;
use Wikibase\Property;

use DataTypes\DataTypeFactory;

/**
 * Base class for testing EntityLookup implementations
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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityLookupTest extends EntityTestCase {

	/**
	 * @param \Wikibase\Entity[]     $entities
	 *
	 * @todo: Support for multiple revisions per entity.
	 *        Needs a way to return the revision IDs.
	 *
	 * @return EntityLookup
	 */
	protected abstract function newEntityLoader( array $entities );

	/**
	 * @return \Wikibase\Entity[]
	 */
	protected function getTestEntities() {
		static $entities = null;

		if ( $entities === null ) {
			$item = Item::newEmpty();
			$item->setId( 42 );

			$entities[1] = $item;

			$item = $item->copy();
			$item->setLabel( 'en', "Foo" );

			$entities[2] = $item;

			$dtf = $factory = new DataTypeFactory( $GLOBALS['wgDataTypes'] );

			$prop = Property::newEmpty();
			$prop->setId( 753 );
			$prop->setDataType( $dtf->getType( "string" ) );

			$entities[3] = $prop;
		}

		return $entities;
	}

	protected function getLookup() {
		$entities = $this->getTestEntities();
		$lookup = $this->newEntityLoader( $entities );

		return $lookup;
	}

	protected function resolveLogicalRevision( $revision ) {
		return $revision;
	}

	public static function provideGetEntity() {
		$cases = array(
			array( // #0: any revision
				'q42', 0, true,
			),
			array( // #1: first revision
				'q42', 1, true,
			),
			array( // #2: second revision
				'q42', 2, true,
			),
			array( // #3: bad revision
				'q42', 600000, false, 'Wikibase\StorageException',
			),
			array( // #4: wrong type
				'q753', 0, false,
			),
			array( // #5: bad revision
				'p753', 1, false, 'Wikibase\StorageException',
			),
			array( // #6: some revision
				'p753', 0, true,
			),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideGetEntity
	 *
	 * @param string|EntityId $id       The entity to get
	 * @param int             $revision The revision to get (or 0)
	 * @param bool            $shouldExist
	 * @param string|null     $expectException
	 */
	public function testGetEntity( $id, $revision, $shouldExist, $expectException = null ) {
		if ( $expectException !== null ) {
			$this->setExpectedException( $expectException );
		}

		if ( is_string( $id ) ) {
			$id = EntityId::newFromPrefixedId( $id );
		}

		$revision = $this->resolveLogicalRevision( $revision );

		$lookup = $this->getLookup();
		$entity = $lookup->getEntity( $id, $revision );

		if ( $shouldExist == true ) {
			$this->assertNotNull( $entity, "ID " . $id->getPrefixedId() );
			$this->assertEquals( $id->getPrefixedId(), $entity->getPrefixedId() );
		} else {
			$this->assertNull( $entity, "ID " . $id->getPrefixedId() );
		}
	}

	public static function provideGetEntities() {
		return array(
			array( // #0
				array(),
				array(),
				array( 'q42' ),
				array( 'q42' => 'q42' ),
			),
			array( // #1
				array( 'q42', 'q33' ),
				array( 'q42' => 'q42', 'q33' => null ),
				array( 'q42', 'p753', 'p777' ),
				array( 'q42' => 'q42', 'p753' => 'p753', 'p777' => null ),
			),
		);
	}

	/**
	 * @dataProvider provideGetEntities()
	 *
	 * @note check two batches to make sure overlapping batches don't confuse caching.
	 *
	 */
	public function testGetEntities( $batch1, $expected1, $batch2, $expected2 ) {
		$lookup = $this->getLookup();

		// check first batch
		$batch1 = self::makeEntityIds( $batch1 );
		$entities1 = $lookup->getEntities( $batch1 );
		$ids1 = self::getEntityIds( $entities1 );

		$this->assertArrayEquals( self::makeEntityIds( $expected1 ), $ids1, false, true );

		// check second batch
		$batch2 = self::makeEntityIds( $batch2 );
		$entities2 = $lookup->getEntities( $batch2 );
		$ids2 = self::getEntityIds( $entities2 );

		$this->assertArrayEquals( self::makeEntityIds( $expected2 ), $ids2, false, true );
	}

	protected static function getEntityIds( $entities ) {
		return array_map( function( $entity ) use ( $entities ) {
			return $entity == null ? null : $entity->getId();
		}, $entities );
	}

	protected static function makeEntityIds( $ids ) {
		return array_map( function( $id ) use ( $ids ) {
			if ( is_string( $id ) ) {
				$id = EntityId::newFromPrefixedId( $id );
			}

			return $id;
		}, $ids );
	}

}

