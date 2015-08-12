<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Services\Diff\DiffOpFactory;
use Wikibase\DataModel\Services\Diff\ItemDiff;

/**
 * @covers Wikibase\DataModel\Services\Diff\DiffOpFactory
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffOpFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testGivenNormalDiffOpArray_diffOpIsReturned() {
		$factory = new DiffOpFactory();

		$diffOp = new DiffOpAdd( 42 );
		$newDiffOp = $factory->newFromArray( $diffOp->toArray() );

		$this->assertEquals( $diffOp, $newDiffOp );
	}

	public function testGivenInvalidDiffOp_exceptionIsThrown() {
		$factory = new DiffOpFactory();
		$this->setExpectedException( 'InvalidArgumentException' );
		$factory->newFromArray( array( 'wee' ) );
	}

	public function testGivenEntityDiffOpArray_entityDiffOpisReturned() {
		$factory = new DiffOpFactory();

		$diffOp = new ItemDiff( array() );
		$newDiffOp = $factory->newFromArray( $diffOp->toArray() );

		$this->assertEquals( $diffOp, $newDiffOp );
	}

}
