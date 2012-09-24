<?php

namespace Wikibase\Test;
use Wikibase\EntityUpdate as EntityUpdate;
use Wikibase\Entity as Entity;

/**
 * Tests for the Wikibase\EntityUpdate class.
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
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityUpdateTest extends \MediaWikiTestCase {

	/**
	 * @since 0.1
	 *
	 * @return array
	 */
	public function newFromEntitiesProvider() {
		$argLists = array();

		$oldEntities = array(
			\Wikibase\ItemObject::newEmpty(),
			\Wikibase\PropertyObject::newEmpty(),
			\Wikibase\QueryObject::newEmpty(),
		);

		/**
		 * @var Entity $oldEntity
		 */
		foreach ( $oldEntities as $oldEntity ) {
			$oldEntity->setId( 42 );
			$oldEntity->setDescription( 'en', 'foobar' );

			$newEntity = $oldEntity->copy();
			$newEntity->setDescription( 'en', 'baz' );

			$argLists[] = array( $oldEntity, $newEntity );

			$newEntity->setAliases( 'en', array( 'o', 'h', 'i' ) );

			$argLists[] = array( $oldEntity, $newEntity );
		}

		return $argLists;
	}

	/**
	 * @dataProvider newFromEntitiesProvider
	 * @param \Wikibase\Entity $oldEntity
	 * @param \Wikibase\Entity $newEntity
	 */
	public function testNewFromEntities( Entity $oldEntity, Entity $newEntity ) {
		$entityUpdate = EntityUpdate::newFromEntities( $oldEntity, $newEntity );
		$this->assertInstanceOf( 'Wikibase\EntityUpdate', $entityUpdate );

		$this->assertEquals( $newEntity, $entityUpdate->getEntity() );

		$this->assertEquals( $oldEntity->getDiff( $newEntity ), $entityUpdate->getDiff() );
	}


}
	
