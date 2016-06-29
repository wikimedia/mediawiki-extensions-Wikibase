<?php
namespace Wikibase\Test;

use MediaWikiTestCase;
use ObjectFactory;
use Wikibase\Lib\CSVUnitStorage;
use Wikibase\Lib\JsonUnitStorage;
use Wikibase\Lib\UnitStorage;
use RuntimeException;

/**
 * @covers Wikibase\Lib\UnitConverter
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class UnitStorageTest extends MediaWikiTestCase {

	public function storageModels() {
		return [
			[ JsonUnitStorage::class, [ __DIR__ . '/testunits.json' ] ],
			[ CSVUnitStorage::class, [ __DIR__ . '/testunits.csv' ] ]
		];
	}

	/**
	 * @dataProvider storageModels
	 * @param $class
	 * @param $args
	 */
	public function testStorage( $class, $args ) {
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

		$this->assertNull( $storage->getConversion( 'Q1' ) );
		$this->assertEquals( [ 'multiplier' => '22.234', 'unit' => 'Q1' ],
			$storage->getConversion( 'Q2' ) );
		$this->assertNull( $storage->getConversion( 'Q3' ) );
		$this->assertEquals( [ 'multiplier' => '0.0000000000000000000243885945', 'unit' => 'Q3' ],
			$storage->getConversion( 'Q4' ) );
		$this->assertNull( $storage->getConversion( 'Q5' ) );

	}

	/**
	 * @dataProvider storageModels
	 * @param $class
	 * @param $args
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage Failed to load unit storage
	 */
	public function testBadStorage( $class, $args ) {
		$def = [ 'class' => $class, 'args' => [ 'nosuchfile' ] ];
		$storage = ObjectFactory::getObjectFromSpec( $def );

		$this->assertNull( $storage->getConversion( 'Q1' ) );
	}

}
