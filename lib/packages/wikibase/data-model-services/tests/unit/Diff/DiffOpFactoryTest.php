<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Services\Diff\DiffOpFactory;

/**
 * @covers Wikibase\DataModel\Services\Diff\DiffOpFactory
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class DiffOpFactoryTest extends \PHPUnit_Framework_TestCase {

	public function diffOpProvider() {
		$diffOps = array();

		$diffOps[] = new DiffOpAdd( 42 );
		$diffOps['foo bar'] = new DiffOpAdd( '42' );
		$diffOps[9001] = new DiffOpAdd( 4.2 );
		$diffOps['42'] = new DiffOpAdd( array( 42, array( 9001 ) ) );
		$diffOps[] = new DiffOpRemove( 42 );
		$diffOps[] = new DiffOpAdd( new DiffOpChange( 'spam', 'moar spam' ) );

		$atomicDiffOps = $diffOps;

		foreach ( array( true, false, null ) as $isAssoc ) {
			$diffOps[] = new Diff( $atomicDiffOps, $isAssoc );
		}

		$diffOps[] = new DiffOpChange( 42, '9001' );

		$diffOps[] = new Diff( $diffOps );

		return $this->arrayWrap( $diffOps );
	}

	/**
	 * @dataProvider diffOpProvider
	 *
	 * @param DiffOp $diffOp
	 */
	public function testNewFromArray( DiffOp $diffOp ) {
		$factory = new DiffOpFactory();

		// try without conversion callback
		$array = $diffOp->toArray();
		$newInstance = $factory->newFromArray( $array );

		// If an equality method is implemented in DiffOp, it should be used here
		$this->assertEquals( $diffOp, $newInstance );
		$this->assertEquals( $diffOp->getType(), $newInstance->getType() );
	}

	private function arrayWrap( array $elements ) {
		return array_map(
			function( $element ) {
				return array( $element );
			},
			$elements
		);
	}

}
