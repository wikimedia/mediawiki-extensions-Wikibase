<?php

namespace Wikibase\Test;

use Wikibase\EntityDiff;

/**
 * @covers Wikibase\EntityDiff
 *
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDiffTest extends \PHPUnit_Framework_TestCase {

	public function isEmptyProvider() {
		$argLists = array();

		$argLists[] = array( array(), true );

		$fields = array( 'aliases', 'label', 'description', 'claim' );

		foreach ( $fields as $field ) {
			$argLists[] = array( array( $field => new \Diff\Diff( array() ) ), true );
		}

		$diffOps = array();

		foreach ( $fields as $field ) {
			$diffOps[$field] = new \Diff\Diff( array() );
		}

		$argLists[] = array( $diffOps, true );

		foreach ( $fields as $field ) {
			$argLists[] = array( array( $field => new \Diff\Diff( array( new \Diff\DiffOpAdd( 42 ) ) ) ), false );
		}

		return $argLists;
	}

	/**
	 * @dataProvider isEmptyProvider
	 *
	 * @param array $diffOps
	 * @param boolean $isEmpty
	 */
	public function testIsEmpty( array $diffOps, $isEmpty ) {
		$diff = new \Wikibase\EntityDiff( $diffOps );
		$this->assertEquals( $isEmpty, $diff->isEmpty() );
	}

	public function diffProvider() {
		$diffs = array();

		$diffOps = array(
			'label' => new \Diff\Diff( array(
				'en' => new \Diff\DiffOpAdd( 'foobar' ),
				'de' => new \Diff\DiffOpRemove( 'onoez' ),
				'nl' => new \Diff\DiffOpChange( 'foo', 'bar' ),
			), true )
		);

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['description'] = new \Diff\Diff( array(
			'en' => new \Diff\DiffOpAdd( 'foobar' ),
			'de' => new \Diff\DiffOpRemove( 'onoez' ),
			'nl' => new \Diff\DiffOpChange( 'foo', 'bar' ),
		), true );

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['aliases'] = new \Diff\Diff( array(
			'en' => new \Diff\Diff( array( new \Diff\DiffOpAdd( 'foobar' ), new \Diff\DiffOpRemove( 'onoez' ) ), false ),
			'de' => new \Diff\Diff( array( new \Diff\DiffOpRemove( 'foo' ) ), false ),
		), true );

		$diffs[] = new EntityDiff( $diffOps );

		$claims = new \Wikibase\Claims( array( new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak( 42 ) ) ) );

		$diffOps['claim'] = $claims->getDiff( new \Wikibase\Claims() );

		$diffs[] = new EntityDiff( $diffOps );

		$argLists = array();

		foreach ( $diffs as $diff ) {
			$argLists[] = array( $diff );
		}

		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetClaimsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getClaimsDiff();

		$this->assertInstanceOf( '\Diff\Diff', $diff );
		$this->assertTrue( $diff->isAssociative() );

		foreach ( $diff as $diffOp ) {
			$this->assertTrue( $diffOp instanceof \Diff\DiffOpAdd || $diffOp instanceof \Diff\DiffOpRemove );

			$claim = $diffOp instanceof \Diff\DiffOpAdd ? $diffOp->getNewValue() : $diffOp->getOldValue();
			$this->assertInstanceOf( '\Wikibase\Claim', $claim );
		}
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetDescriptionsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getDescriptionsDiff();

		$this->assertInstanceOf( '\Diff\Diff', $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetLabelsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getLabelsDiff();

		$this->assertInstanceOf( '\Diff\Diff', $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetAliasesDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getAliasesDiff();

		$this->assertInstanceOf( '\Diff\Diff', $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

}
