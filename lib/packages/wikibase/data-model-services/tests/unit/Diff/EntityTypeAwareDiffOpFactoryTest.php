<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\DiffOpAdd;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\EntityTypeAwareDiffOpFactory;
use Wikibase\DataModel\Services\Diff\ItemDiff;

/**
 * @covers \Wikibase\DataModel\Services\Diff\EntityTypeAwareDiffOpFactory
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityTypeAwareDiffOpFactoryTest extends TestCase {

	public function testGivenNormalDiffOpArray_diffOpIsReturned() {
		$factory = new EntityTypeAwareDiffOpFactory();

		$diffOp = new DiffOpAdd( 42 );
		$newDiffOp = $factory->newFromArray( $diffOp->toArray() );

		$this->assertEquals( $diffOp, $newDiffOp );
	}

	public function testGivenInvalidDiffOp_exceptionIsThrown() {
		$factory = new EntityTypeAwareDiffOpFactory();
		$this->expectException( InvalidArgumentException::class );
		$factory->newFromArray( [ 'wee' ] );
	}

	public function testGivenEntityDiffOpArray_entityDiffOpisReturned() {
		$factory = new EntityTypeAwareDiffOpFactory();

		$diffOp = new ItemDiff( [] );
		$newDiffOp = $factory->newFromArray( $diffOp->toArray() );

		$this->assertEquals( $diffOp, $newDiffOp );
	}

}
