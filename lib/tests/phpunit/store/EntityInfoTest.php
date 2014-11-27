<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;

/**
 * @covers Wikibase\Lib\Store\EntityInfo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityInfoTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param Entity[] $entities
	 *
	 * @return EntityInfo
	 */
	private function getEntityInfo( array $entities ) {
		$repo = new MockRepository();
		$ids = array();

		foreach ( $entities as $entity ) {
			$repo->putEntity( $entity );
			$ids[] = $entity->getId();
		}

		$builder = new GenericEntityInfoBuilder(
			$ids,
			new BasicEntityIdParser(),
			$repo
		);

		$builder->collectTerms();

		return $builder->getEntityInfo();
	}

	/**
	 * @param string $id
	 * @param string $label
	 *
	 * @return Item
	 */
	private function makeItemWithLabel( $id, $label ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $id ) );

		$item->setLabel( 'en', $label );

		return $item;
	}

	/**
	 * @param string $id
	 * @param string $label
	 *
	 * @return Item
	 */
	private function makeItemWithDescription( $id, $label ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $id ) );

		$item->setDescription( 'en', $label );

		return $item;
	}

	public function asArrayProvider() {
		$infoWithLabels = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		return array(
			'empty' => array( array() ),
			'labels' => array( $infoWithLabels->asArray() ),
		);
	}

	/**
	 * @dataProvider asArrayProvider
	 */
	public function testAsArray( array $records ) {
		$entityInfo = new EntityInfo( $records );

		$actual = $entityInfo->asArray();
		$this->assertEquals( $records, $actual );
	}

	public function testHasEntityInfo() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		$this->assertTrue( $info->hasEntityInfo( new ItemId( 'Q11' ) ) );
		$this->assertTrue( $info->hasEntityInfo( new ItemId( 'Q33' ) ) );
		$this->assertFalse( $info->hasEntityInfo( new ItemId( 'Q99' ) ) );
	}

	public function testGetEntityInfo() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		$record = $info->getEntityInfo( new ItemId( 'Q11' ) );
		$this->assertInternalType( 'array', $record );
		$this->assertEquals( 'Q11', $record['id'] );

		$record = $info->getEntityInfo( new ItemId( 'Q33' ) );
		$this->assertInternalType( 'array', $record );
		$this->assertEquals( 'Q33', $record['id'] );
		$this->assertArrayHasKey( 'labels', $record );

		$record = $info->getEntityInfo( new ItemId( 'Q99' ) );
		$this->assertInternalType( 'array', $record );
		$this->assertEquals( 'Q99', $record['id'] );
		$this->assertArrayNotHasKey( 'labels', $record );
	}

	public function testGetLabel() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( 'London', $info->getLabel( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getLabel( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getLabel( new ItemId( 'Q11' ), 'zh' ) );
		$this->assertNull( $info->getLabel( new ItemId( 'Q99' ), 'en' ) );
	}

	public function testGetLabels() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( array( 'en' => 'London' ), $info->getLabels( new ItemId( 'Q11' ) ) );
		$this->assertEquals( array( 'en' => 'Berlin' ), $info->getLabels( new ItemId( 'Q33' ) ) );
		$this->assertEquals( array(), $info->getLabels( new ItemId( 'Q99' ) ) );
	}

	public function testGetDescription() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( 'London', $info->getDescription( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getDescription( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getDescription( new ItemId( 'Q11' ), 'zh' ) );
		$this->assertNull( $info->getDescription( new ItemId( 'Q99' ), 'en' ) );
	}

	public function testGetDescriptions() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( array( 'en' => 'London' ), $info->getDescriptions( new ItemId( 'Q11' ) ) );
		$this->assertEquals( array( 'en' => 'Berlin' ), $info->getDescriptions( new ItemId( 'Q33' ) ) );
		$this->assertEquals( array(), $info->getDescriptions( new ItemId( 'Q99' ) ) );
	}

}
