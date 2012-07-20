<?php

namespace Diff\Test;
use Diff\Diff as Diff;
use Diff\DiffOpAdd as DiffOpAdd;
use Diff\DiffOpRemove as DiffOpRemove;
use Diff\DiffOpChange as DiffOpChange;

/**
 * Tests for the Diff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Diff
 * @ingroup Test
 * @group Diff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffTest extends \MediaWikiTestCase {

	public function stuffProvider() {
		return array(
			array( array(
				new DiffOpAdd( 'ohi' )
			) ),
			array( array(
				new DiffOpRemove( 'ohi' )
			) ),
			array( array(
				new DiffOpAdd( 'ohi' ),
				new DiffOpRemove( 'there' )
			) ),
			array( array(
			) ),
			array( array(
				new DiffOpAdd( 'ohi' ),
				new DiffOpRemove( 'there' ),
				new DiffOpChange( 'ohi', 'there' )
			) ),
			array( array(
				'1' => new DiffOpAdd( 'ohi' ),
				'33' => new DiffOpRemove( 'there' ),
				'7' => new DiffOpChange( 'ohi', 'there' )
			) ),
		);
	}

	/**
	 * @dataProvider stuffProvider
	 */
	public function testStuff( array $operations ) {
		$diff = new Diff( $operations );

		$this->assertInstanceOf( '\Diff\IDiff', $diff );
		$this->assertInstanceOf( '\ArrayIterator', $diff );

		$types = array();

		foreach ( $diff as $operation ) {
			$this->assertInstanceOf( '\Diff\IDiffOp', $operation );
			if ( !in_array( $operation->getType(), $types ) ) {
				$types[] = $operation->getType();
			}
		}

		$count = 0;

		foreach ( $types as $type ) {
			$count += count( $diff->getTypeOperations( $type ) );
		}

		$this->assertEquals( $count, $diff->count() );
	}

}
	
