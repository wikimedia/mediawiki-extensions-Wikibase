<?php

namespace Wikibase\Test;
use \Wikibase\EntityCacheTable;
use \Wikibase\Entity;

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
 * @group WikibaseEntityLookup
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCacheTableTest extends \ORMTableTest {


	public function setup() {
		if ( \Wikibase\Settings::get( 'repoDatabase' ) !== null ) {
			$this->markTestSkipped( "Cache is not usable if WikibaseClient is configured for direct access to the repo database" );
		}

		parent::setup();
	}

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

		$entity = \Wikibase\Item::newEmpty();
		$entity->setId( 1 );
		$entity->setLabel( 'en', 'foobar' );
		$entities[] = $entity;

		$entity = \Wikibase\Property::newEmpty();
		$entity->setId( 42 );
		$entity->setLabel( 'en', 'foobar' );
		$entities[] = $entity;

		$entity = \Wikibase\Query::newEmpty();
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
		$this->assertTrue( $table->hasEntity( $entity->getId() ) );

		//TODO: test with revision ID
		$obtainedEntity = $table->getEntity( $entity->getId() );

		$this->assertTrue( $entity->getDiff( $obtainedEntity )->isEmpty() );

		$obtainedEntity = $table->selectRow(
			null,
			array(
				'entity_id' => $entity->getId()->getNumericId(),
				'entity_type' => $entity->getType(),
			)
		)->getEntity();

		$this->assertTrue( $entity->getDiff( $obtainedEntity )->isEmpty() );

		$this->assertDeleteEntity( $entity );
	}

	/**
	 * @param \Wikibase\Entity $entity
	 * @todo: make this a separate test case, depending on testAddEntity.
	 *       For some reason, passing the parameter doesn't work if we do that...
	 */
	protected function assertDeleteEntity( Entity $entity ) {
		$this->assertTrue( $this->getTable()->deleteEntity( $entity->getId() ) );
		$this->assertFalse( $this->getTable()->hasEntity( $entity->getId() ) );
		$this->assertTrue( $this->getTable()->deleteEntity( $entity->getId() ) );
	}
}
