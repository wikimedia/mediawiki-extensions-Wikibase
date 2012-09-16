<?php

namespace Wikibase\Test;
use \Wikibase\EntityCache as EntityCache;
use \Wikibase\EntityCacheTable as EntityCacheTable;
use \Wikibase\Entity as Entity;

/**
 * Tests for the Wikibase\EntityCacheTable class.
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
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseEntityCache
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCacheTest extends ORMTableTest {

	/**
	 * @see ORMTableTest::getRowClass
	 * @since 0.1
	 * @return string
	 */
	protected function getTableClass() {
		return '\Wikibase\EntityCacheTable';
	}

	public function entityProvider() {
		$entities = array();

		$entity = \Wikibase\ItemObject::newEmpty();
		$entity->setId( 1 );
		$entity->setLabel( 'en', 'foobar' );
		$entities[] = $entity;

		$entity = \Wikibase\PropertyObject::newEmpty();
		$entity->setId( 42 );
		$entity->setLabel( 'en', 'foobar' );
		$entities[] = $entity;

		$entity = \Wikibase\QueryObject::newEmpty();
		$entity->setId( 9001 );
		$entity->setLabel( 'en', 'foobar' );
		$entities[] = $entity;

		return array_map( function( Entity $entity ) { return array( $entity ); }, $entities );
	}

	/**
	 * @see ORMTableTest::getTable()
	 * @since 0.1
	 * @return EntityCacheTable
	 */
	public function getTable() {
		return parent::getTable();
	}

	/**
	 * @dataProvider entityProvider
	 *
	 * @param \Wikibase\Entity $entity
	 */
	public function testAddEntity( Entity $entity ) {
		$table = $this->getTable();
		$table->delete( array() );

		$this->assertTrue( $table->addEntity( $entity ) );
		$this->assertTrue( $table->hasEntity( $entity ) );

		$obtainedEntity = $table->getEntity( $entity->getType(), $entity->getId() );

		$this->assertTrue( $entity->getDiff( $obtainedEntity )->isEmpty() );

		$obtainedEntity = $table->selectRow(
			null,
			array(
				'entity_id' => $entity->getId(),
				'entity_type' => $entity->getType(),
			)
		)->getEntity();

		$this->assertTrue( $entity->getDiff( $obtainedEntity )->isEmpty() );

		$this->testDeleteEntity( $entity );
	}

	/**
	 * @param \Wikibase\Entity $entity
	 */
	protected function testDeleteEntity( Entity $entity ) {
		$this->assertTrue( $this->getTable()->deleteEntity( $entity ) );
		$this->assertFalse( $this->getTable()->hasEntity( $entity ) );
		$this->assertTrue( $this->getTable()->deleteEntity( $entity ) );
	}

	/**
	 * @dataProvider entityProvider
	 *
	 * @param \Wikibase\Entity $entity
	 */
	public function testUpdateEntity( Entity $entity ) {
		$table = $this->getTable();

		$this->assertTrue( $table->updateEntity( $entity ) );

		$entity->setAliases( 'en', array( 'foobar' ) );

		$this->assertTrue( $table->updateEntity( $entity ) );

		$obtainedEntity = $table->getEntity( $entity->getType(), $entity->getId() );

		$this->assertTrue( $entity->getDiff( $obtainedEntity )->isEmpty() );
	}

}

abstract class ORMTableTest extends \MediaWikiTestCase {

	/**
	 * @since 1.20
	 * @return string
	 */
	protected abstract function getTableClass();

	/**
	 * @since 1.20
	 * @return \IORMTable
	 */
	public function getTable() {
		$class = $this->getTableClass();
		return $class::singleton();
	}

	/**
	 * @since 1.20
	 * @return string
	 */
	public function getRowClass() {
		return $this->getTable()->getRowClass();
	}

	/**
	 * @since 1.20
	 */
	public function testSingleton() {
		$class = $this->getTableClass();

		$this->assertInstanceOf( $class, $class::singleton() );
		$this->assertTrue( $class::singleton() === $class::singleton() );
	}

}