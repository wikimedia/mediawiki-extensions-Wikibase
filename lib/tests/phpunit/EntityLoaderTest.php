<?php

namespace Wikibase\Test;
use Wikibase\CachingEntityLoader;
use Wikibase\Item;
use Wikibase\Query;
use Wikibase\EntityLoader;
use Wikibase\EntityId;
use Wikibase\Property;

/**
 * Tests for the Wikibase\EntityLoader class.
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
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityLoaderTest extends \MediaWikiTestCase {

	public function loaderProvider() {
		$loaders = array();

		$loaders[] = new CachingEntityLoader();

		$loader = new CachingEntityLoader();

		$item = Item::newEmpty();
		$item->setId( 42 );

		$query = Query::newEmpty();
		$query->setId( 9001 );

		$loader->setEntities( array( $item, $query ) );

		$loaders[] = $loader;

		return $this->arrayWrap( $loaders );
	}

	/**
	 * @dataProvider loaderProvider
	 *
	 * @param \Wikibase\EntityLoader $loader
	 */
	public function testGetEntity( EntityLoader $loader ) {
		$entityIds = array(
			new EntityId( Item::ENTITY_TYPE, 1 ),
			new EntityId( Item::ENTITY_TYPE, 42 ),
			new EntityId( Property::ENTITY_TYPE, 9001 ),
			new EntityId( Query::ENTITY_TYPE, 9001 ),
			new EntityId( Query::ENTITY_TYPE, 2 ),
		);

		foreach ( $entityIds as $entityId ) {
			$entity = $loader->getEntity( $entityId );
			$this->assertTypeOrValue( '\Wikibase\Entity', $entity, null );
		}
	}

	/**
	 * @dataProvider loaderProvider
	 *
	 * @param \Wikibase\EntityLoader $loader
	 */
	public function testGetEntities( EntityLoader $loader ) {
		$entityIds = array(
			new EntityId( Item::ENTITY_TYPE, 1 ),
			new EntityId( Item::ENTITY_TYPE, 42 ),
			new EntityId( Property::ENTITY_TYPE, 9001 ),
			new EntityId( Query::ENTITY_TYPE, 9001 ),
			new EntityId( Query::ENTITY_TYPE, 2 ),
		);

		$entities = $loader->getEntities( $entityIds );

		foreach ( $entities as $entity ) {
			$this->assertTypeOrValue( '\Wikibase\Entity', $entity, null );
		}

		$this->assertArrayEquals( $entities, $loader->getEntities( $entityIds ) );
	}

}

