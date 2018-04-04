<?php

namespace Wikibase\Lib\Tests\Units;

use MediaWikiTestCase;
use Wikibase\Lib\Units\CSVUnitStorage;
use Wikibase\Lib\Units\InMemoryUnitStorage;
use Wikibase\Lib\Units\JsonUnitStorage;
use Wikibase\Lib\Units\UnitStorage;
use Wikimedia\ObjectFactory;
use RuntimeException;

/**
 * @covers Wikibase\Lib\Units\JsonUnitStorage
 * @covers Wikibase\Lib\Units\CSVUnitStorage
 * @covers Wikibase\Lib\Units\BaseUnitStorage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnitStorageTest extends MediaWikiTestCase {

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
					]
				] ]
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

		$this->assertEquals( [ 'factor' => '1', 'unit' => 'Q1' ], $storage->getConversion( 'Q1' ) );
		$this->assertEquals( [ 'factor' => '22.234', 'unit' => 'Q1' ],
			$storage->getConversion( 'Q2' ) );
		$this->assertEquals( [ 'factor' => '1', 'unit' => 'Q3' ], $storage->getConversion( 'Q3' ) );
		$this->assertArraySubset( [ 'factor' => '0.0000000000000000000243885945', 'unit' => 'Q3' ],
			$storage->getConversion( 'Q4' ) );
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
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Failed to load unit storage
	 */
	public function testBadStorage( $class, array $args ) {
		$def = [ 'class' => $class, 'args' => $args ];
		$storage = ObjectFactory::getObjectFromSpec( $def );

		$storage->getConversion( 'Q1' );
	}

	public function testEmptyStorage() {
		$storage = new InMemoryUnitStorage( [] );

		$this->assertNull( $storage->getConversion( 'Q1' ) );
	}

}
