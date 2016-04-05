<?php

namespace Wikibase\Test;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\GenericEntityInfoBuilder;

/**
 * @covers Wikibase\Lib\Store\EntityInfo
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityInfoTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param EntityDocument[] $entities
	 *
	 * @return EntityInfo
	 */
	private function getEntityInfo( array $entities ) {
		$entityRevisionLookup = new MockRepository();
		$ids = [];

		foreach ( $entities as $entity ) {
			$entityRevisionLookup->putEntity( $entity );
			$ids[] = $entity->getId();
		}

		$builder = new GenericEntityInfoBuilder(
			$ids,
			new BasicEntityIdParser(),
			$entityRevisionLookup
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
		$item = new Item( new ItemId( $id ) );

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
		$item = new Item( new ItemId( $id ) );

		$item->setDescription( 'en', $label );

		return $item;
	}

	public function asArrayProvider() {
		return array(
			'empty' => array( [] ),
			'labels' => array( array(
				'Q11' => array(
					'id' => 'Q11',
					'type' => 'item',
					'labels' => array(
						'de' => array(
							'language' => 'de',
							'value' => 'London'
						),
						'la' => array(
							'language' => 'la',
							'value' => 'Londinium'
						),
					)
				)
			) ),
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

		$this->setExpectedException( OutOfBoundsException::class );
		$info->getEntityInfo( new ItemId( 'Q99' ) );
	}

	public function testGetLabel() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( 'London', $info->getLabel( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getLabel( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getLabel( new ItemId( 'Q11' ), 'zh' ) );
	}

	public function testGetLabels() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
			$this->makeItemWithDescription( 'Q66', 'Barcelona' ),
		) );

		$this->assertEquals( array( 'en' => 'London' ), $info->getLabels( new ItemId( 'Q11' ) ) );
		$this->assertEquals( array( 'en' => 'Berlin' ), $info->getLabels( new ItemId( 'Q33' ) ) );
		$this->assertEquals( [], $info->getLabels( new ItemId( 'Q33' ), array( 'de' ) ) );
		$this->assertEquals( [], $info->getLabels( new ItemId( 'Q66' ) ) );
	}

	public function testGetDescription() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
		) );

		$this->assertEquals( 'London', $info->getDescription( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getDescription( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getDescription( new ItemId( 'Q11' ), 'zh' ) );
	}

	public function testGetDescriptions() {
		$info = $this->getEntityInfo( array(
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
			$this->makeItemWithLabel( 'Q66', 'Barcelona' ),
		) );

		$this->assertEquals( array( 'en' => 'London' ), $info->getDescriptions( new ItemId( 'Q11' ) ) );
		$this->assertEquals( array( 'en' => 'Berlin' ), $info->getDescriptions( new ItemId( 'Q33' ) ) );
		$this->assertEquals( [], $info->getDescriptions( new ItemId( 'Q33' ), array( 'de' ) ) );
		$this->assertEquals( [], $info->getDescriptions( new ItemId( 'Q66' ) ) );
	}

	public function provideBlankInfo() {
		return array(
			'unknown item' => array( [] ),
			'unknown terms' => array(
				array(
					'Q99' => array(
						'id' => 'Q99',
						'type' => 'item',
					)
				)
			),
		);
	}

	/**
	 * @dataProvider provideBlankInfo
	 */
	public function testGetLabel_exception( $data ) {
		$info = new EntityInfo( $data );
		$this->setExpectedException( OutOfBoundsException::class );
		$info->getLabel( new ItemId( 'Q99' ), 'en' );
	}

	/**
	 * @dataProvider provideBlankInfo
	 */
	public function testGetLabels_exception( $data ) {
		$info = new EntityInfo( $data );
		$this->setExpectedException( OutOfBoundsException::class );
		$info->getLabels( new ItemId( 'Q99' ) );
	}

	/**
	 * @dataProvider provideBlankInfo
	 */
	public function testGetDescription_exception( $data ) {
		$info = new EntityInfo( $data );
		$this->setExpectedException( OutOfBoundsException::class );
		$info->getDescription( new ItemId( 'Q99' ), 'en' );
	}

	/**
	 * @dataProvider provideBlankInfo
	 */
	public function testGetDescriptions_exception( $data ) {
		$info = new EntityInfo( $data );
		$this->setExpectedException( OutOfBoundsException::class );
		$info->getDescriptions( new ItemId( 'Q99' ) );
	}

	public function invalidArrayProvider() {
		return array(
			'value incomplete' => array(
				array( 'Q99' => array( 'labels' => array( 'en' => [] ) ) )
			),
			'value invalid' => array(
				array( 'Q99' => array( 'labels' => array( 'en' => 'not an array' ) ) )
			),
			'labels invalid' => array(
				array( 'Q99' => array( 'labels' => 'not an array' ) )
			),
			'entity invalid' => array(
				array( 'Q99' => 'not an array' )
			),
		);
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGetLabelWithInvalidArray_throwsRuntimeException( $array ) {
		$info = new EntityInfo( $array );
		$this->setExpectedException( RuntimeException::class );
		$info->getLabel( new ItemId( 'Q99' ), 'en' );
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGetLabelsWithInvalidArray_throwsRuntimeException( $array ) {
		$info = new EntityInfo( $array );
		$this->setExpectedException( RuntimeException::class );
		$info->getLabels( new ItemId( 'Q99' ) );
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGetDescriptionWithInvalidArray_throwsRuntimeException( $array ) {
		$info = new EntityInfo( $array );
		$this->setExpectedException( RuntimeException::class );
		$info->getDescription( new ItemId( 'Q99' ), 'en' );
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGetDescriptionsWithInvalidArray_throwsRuntimeException( $array ) {
		$info = new EntityInfo( $array );
		$this->setExpectedException( RuntimeException::class );
		$info->getDescriptions( new ItemId( 'Q99' ) );
	}

}
