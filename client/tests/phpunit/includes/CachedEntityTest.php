<?php

namespace Wikibase\Test;
use Wikibase\CachedEntity as CachedEntity;
use Wikibase\EntityCache as EntityCache;
use Wikibase\Item as Item;
use Wikibase\Property as Property;
use Wikibase\ItemObject as ItemObject;
use Wikibase\PropertyObject as PropertyObject;

/**
 * Tests for the Wikibase\CachedEntity class.
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
class CachedEntityTest extends \ORMRowTest {

	/**
	 * @see ORMRowTest::getRowClass()
	 * @since 0.1
	 * @return string
	 */
	protected function getRowClass() {
		return '\Wikibase\CachedEntity';
	}

	/**
	 * @see ORMRowTest::getTableInstance()
	 * @since 0.1
	 * @return \IORMTable
	 */
	protected function getTableInstance() {
		return new \Wikibase\EntityCacheTable();
	}

	public function constructorTestProvider() {
		return array(
			array(
				array(
					'entity_id' => 42,
					'entity_type' => Item::ENTITY_TYPE,
					'entity_data' => ItemObject::newEmpty(),
				),
				true
			),
			array(
				array(
					'entity_id' => 42,
					'entity_type' => Property::ENTITY_TYPE,
					'entity_data' => PropertyObject::newEmpty(),
				),
				true
			),
		);
	}

	/**
	 * @dataProvider constructorTestProvider
	 */
	public function testGetEntity( array $data, $loadDefaults ) {
		$cachedEntity = $this->getRowInstance( $data, $loadDefaults );

		$this->assertInstanceOf( '\Wikibase\Entity', $cachedEntity->getEntity() );
	}

}
	