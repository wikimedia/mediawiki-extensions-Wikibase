<?php

namespace Wikibase\Lib\Tests\Store;

use OutOfBoundsException;
use PHPUnit4And6Compat;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Lib\Store\EntityInfo
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityInfoTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
			new ItemIdParser(),
			$entityRevisionLookup
		);

		return $builder->collectEntityInfo( $ids, [ 'en' ] );
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
		return [
			'empty' => [ [] ],
			'labels' => [ [
				'Q11' => [
					'id' => 'Q11',
					'type' => 'item',
					'labels' => [
						'de' => [
							'language' => 'de',
							'value' => 'London'
						],
						'la' => [
							'language' => 'la',
							'value' => 'Londinium'
						],
					]
				]
			] ],
		];
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
		$info = $this->getEntityInfo( [
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		] );

		$this->assertTrue( $info->hasEntityInfo( new ItemId( 'Q11' ) ) );
		$this->assertTrue( $info->hasEntityInfo( new ItemId( 'Q33' ) ) );
		$this->assertFalse( $info->hasEntityInfo( new ItemId( 'Q99' ) ) );
	}

	public function testGetEntityInfo() {
		$info = $this->getEntityInfo( [
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		] );

		$record = $info->getEntityInfo( new ItemId( 'Q11' ) );
		$this->assertInternalType( 'array', $record );
		$this->assertEquals(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'London' ] ], 'descriptions' => [] ],
			$record
		);

		$record = $info->getEntityInfo( new ItemId( 'Q33' ) );
		$this->assertInternalType( 'array', $record );
		$this->assertEquals(
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Berlin' ] ], 'descriptions' => [] ],
			$record
		);

		$this->setExpectedException( OutOfBoundsException::class );
		$info->getEntityInfo( new ItemId( 'Q99' ) );
	}

	public function testGetLabel() {
		$info = $this->getEntityInfo( [
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
		] );

		$this->assertEquals( 'London', $info->getLabel( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getLabel( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getLabel( new ItemId( 'Q11' ), 'zh' ) );
	}

	public function testGetLabels() {
		$info = $this->getEntityInfo( [
			$this->makeItemWithLabel( 'Q11', 'London' ),
			$this->makeItemWithLabel( 'Q33', 'Berlin' ),
			$this->makeItemWithDescription( 'Q66', 'Barcelona' ),
		] );

		$this->assertEquals( [ 'en' => 'London' ], $info->getLabels( new ItemId( 'Q11' ) ) );
		$this->assertEquals( [ 'en' => 'Berlin' ], $info->getLabels( new ItemId( 'Q33' ) ) );
		$this->assertEquals( [], $info->getLabels( new ItemId( 'Q33' ), [ 'de' ] ) );
		$this->assertEquals( [], $info->getLabels( new ItemId( 'Q66' ) ) );
	}

	public function testGetDescription() {
		$info = $this->getEntityInfo( [
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
		] );

		$this->assertEquals( 'London', $info->getDescription( new ItemId( 'Q11' ), 'en' ) );
		$this->assertEquals( 'Berlin', $info->getDescription( new ItemId( 'Q33' ), 'en' ) );
		$this->assertNull( $info->getDescription( new ItemId( 'Q11' ), 'zh' ) );
	}

	public function testGetDescriptions() {
		$info = $this->getEntityInfo( [
			$this->makeItemWithDescription( 'Q11', 'London' ),
			$this->makeItemWithDescription( 'Q33', 'Berlin' ),
			$this->makeItemWithLabel( 'Q66', 'Barcelona' ),
		] );

		$this->assertEquals( [ 'en' => 'London' ], $info->getDescriptions( new ItemId( 'Q11' ) ) );
		$this->assertEquals( [ 'en' => 'Berlin' ], $info->getDescriptions( new ItemId( 'Q33' ) ) );
		$this->assertEquals( [], $info->getDescriptions( new ItemId( 'Q33' ), [ 'de' ] ) );
		$this->assertEquals( [], $info->getDescriptions( new ItemId( 'Q66' ) ) );
	}

	public function provideBlankInfo() {
		return [
			'unknown item' => [ [] ],
			'unknown terms' => [
				[
					'Q99' => [
						'id' => 'Q99',
						'type' => 'item',
					]
				]
			],
		];
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
		return [
			'value incomplete' => [
				[ 'Q99' => [ 'labels' => [ 'en' => [] ] ] ]
			],
			'value invalid' => [
				[ 'Q99' => [ 'labels' => [ 'en' => 'not an array' ] ] ]
			],
			'labels invalid' => [
				[ 'Q99' => [ 'labels' => 'not an array' ] ]
			],
			'entity invalid' => [
				[ 'Q99' => 'not an array' ]
			],
		];
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
