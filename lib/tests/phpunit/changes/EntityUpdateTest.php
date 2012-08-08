<?php

namespace Wikibase\Test;
use Wikibase\EntityUpdate as EntityUpdate;
use Wikibase\Entity as Entity;

/**
 * Tests for the Wikibase\EntityUpdate class.
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
	
