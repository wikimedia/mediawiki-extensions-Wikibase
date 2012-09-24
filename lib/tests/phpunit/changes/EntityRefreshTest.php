<?php

namespace Wikibase\Test;
use Wikibase\EntityRefresh as EntityRefresh;
use Wikibase\Entity as Entity;

/**
 * Tests for the Wikibase\EntityRefresh class.
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
class EntityRefreshTest extends \MediaWikiTestCase {

	protected function getEntities() {
		$entities = array();

		$emptyEntities = array(
			\Wikibase\ItemObject::newEmpty(),
			\Wikibase\PropertyObject::newEmpty(),
			\Wikibase\QueryObject::newEmpty(),
		);

		/**
		 * @var Entity $entity
		 */
		foreach ( $emptyEntities as $entity ) {
			$entities[] = $entity->copy();

			$entity->setDescription( 'en', 'foo' );
			$entity->setLabel( 'de', 'bar' );
			$entity->setAliases( 'nl', array( 'o', 'h', 'i' ) );

			$entities[] = $entity;
		}

		return $entities;
	}

	public function entityProvider() {
		return array_map(
			function( Entity $entity ) {
				return array( $entity );
			},
			$this->getEntities()
		);
	}

	protected function getClass() {
		return 'Wikibase\EntityRefresh';
	}

	public function instanceProvider() {
		$class = $this->getClass();

		return array_map(
			function( Entity $entity ) use ( $class ) {
				return array( $class::newFromEntity( $entity ) );
			},
			$this->getEntities()
		);
	}

	/**
	 * @dataProvider entityProvider
	 *
	 * @param \Wikibase\Entity $entity
	 */
	public function testNewFromEntity( Entity $entity ) {
		$class = $this->getClass();

		$entityRefresh = $class::newFromEntity( $entity );
		$this->assertInstanceOf( $class, $entityRefresh );

		$this->assertEquals( $entity, $entityRefresh->getEntity() );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetType( EntityRefresh $entityRefresh ) {
		$this->assertInternalType( 'string', $entityRefresh->getType() );
	}

	public function testSetAndGetEntity() {
		$class = $this->getClass();

		$entityRefresh = $class::newFromEntity( \Wikibase\ItemObject::newEmpty() );

		/**
		 * @var Entity $entity
		 * @var EntityRefresh $entityRefresh
		 */
		foreach ( $this->getEntities() as $entity ) {
			$entityRefresh->setEntity( $entity );
			$this->assertEquals( $entity, $entityRefresh->getEntity() );
		}
	}

}
