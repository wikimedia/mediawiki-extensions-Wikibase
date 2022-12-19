<?php

namespace Wikibase\Lib\Tests\Units;

use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Lib\Units\CSVUnitStorage;
use Wikibase\Lib\Units\InMemoryUnitStorage;
use Wikibase\Lib\Units\JsonUnitStorage;
use Wikibase\Lib\Units\UnitStorage;
use Wikimedia\ObjectFactory\ObjectFactory;

/**
 * @covers \Wikibase\Lib\Units\JsonUnitStorage
 * @covers \Wikibase\Lib\Units\CSVUnitStorage
 * @covers \Wikibase\Lib\Units\BaseUnitStorage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnitStorageTest extends MediaWikiIntegrationTestCase {

	public function storageModels() {
		return [
			[ JsonUnitStorage::class, [ __DIR__ . '/testunits.json' ] ],
			[ CSVUnitStorage::class, [ __DIR__ . '/testunits.csv' ] ],
			[
				InMemoryUnitStorage::class,
				[ [
					'Q1' => [ '1.0', 'Q1' ],
					'Q2' => [ '22.234', 'Q1' ],
					'Q3' => [ '1', 'Q3' ],
					'Q4' => [
						'factor' => '0.0000000000000000000243885945',
						'unit' => 'Q3',
						'otherdata' => 'should be ignored',
					],
				] ],
			],
		];
	}

	/**
	 * @dataProvider storageModels
	 */
	public function testStorage( $class, array $args ) {
		$def = [ 'class' => $class, 'args' => $args ];
		$storage = ObjectFactory::getObjectFromSpec( $def );
		/**
		 * @var UnitStorage $storage
		 */
		$this->assertInstanceOf( UnitStorage::class, $storage );

		$this->assertTrue( $storage->isPrimaryUnit( 'Q1' ) );
		$this->assertFalse( $storage->isPrimaryUnit( 'Q2' ) );
		$this->assertTrue( $storage->isPrimaryUnit( 'Q3' ) );
		$this->assertFalse( $storage->isPrimaryUnit( 'Q4' ) );

		$this->assertSame( [ 'factor' => '1.0', 'unit' => 'Q1' ], $storage->getConversion( 'Q1' ) );
		$this->assertSame( [ 'factor' => '22.234', 'unit' => 'Q1' ],
			$storage->getConversion( 'Q2' ) );
		$this->assertSame( [ 'factor' => '1', 'unit' => 'Q3' ], $storage->getConversion( 'Q3' ) );
		$expected = [ 'factor' => '0.0000000000000000000243885945', 'unit' => 'Q3' ];
		$this->assertEquals(
			$expected,
			array_intersect_assoc( $storage->getConversion( 'Q4' ), $expected )
		);
		$this->assertNull( $storage->getConversion( 'Q5' ) );
	}

	public function badStorageModels() {
		return [
			[ JsonUnitStorage::class, [ 'nosuchfile' ] ],
			[ CSVUnitStorage::class, [ 'nosuchfile' ] ],
			[ InMemoryUnitStorage::class, [ null ] ],
		];
	}

	/**
	 * @dataProvider badStorageModels
	 */
	public function testBadStorage( $class, array $args ) {
		$def = [ 'class' => $class, 'args' => $args ];
		$storage = ObjectFactory::getObjectFromSpec( $def );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Failed to load unit storage' );
		$storage->getConversion( 'Q1' );
	}

	public function testEmptyStorage() {
		$storage = new InMemoryUnitStorage( [] );

		$this->assertNull( $storage->getConversion( 'Q1' ) );
	}

}
