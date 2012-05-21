<?php

namespace Wikibase\Test;
use Wikibase\Diff as Diff;

/**
 * Tests for the Diff class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffTest extends \MediaWikiTestCase {

	public function stuffProvider() {
		return array(
			array( array(
				new \Wikibase\DiffOpAdd( 'ohi' )
			) ),
			array( array(
				new \Wikibase\DiffOpRemove( 'ohi' )
			) ),
			array( array(
				new \Wikibase\DiffOpAdd( 'ohi' ),
				new \Wikibase\DiffOpRemove( 'there' )
			) ),
			array( array(
			) ),
			array( array(
				new \Wikibase\DiffOpAdd( 'ohi' ),
				new \Wikibase\DiffOpRemove( 'there' ),
				new \Wikibase\DiffOpChange( 'ohi', 'there' )
			) ),
			array( array(
				'1' => new \Wikibase\DiffOpAdd( 'ohi' ),
				'33' => new \Wikibase\DiffOpRemove( 'there' ),
				'7' => new \Wikibase\DiffOpChange( 'ohi', 'there' )
			) ),
		);
	}

	/**
	 * @dataProvider stuffProvider
	 */
	public function testStuff( array $operations ) {
		$diff = new Diff( $operations );

		$this->assertInstanceOf( '\Wikibase\IDiff', $diff );
		$this->assertInstanceOf( '\ArrayIterator', $diff );

		$types = array();

		foreach ( $diff as $operation ) {
			$this->assertInstanceOf( '\Wikibase\IDiffOp', $operation );
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
	
