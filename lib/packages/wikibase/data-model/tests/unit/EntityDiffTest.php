<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;

/**
 * @covers Wikibase\DataModel\Entity\EntityDiff
 *
 * @group Wikibase
 * @group WikibaseDataModel
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
			$argLists[] = array( array( $field => new Diff( array() ) ), true );
		}

		$diffOps = array();

		foreach ( $fields as $field ) {
			$diffOps[$field] = new Diff( array() );
		}

		$argLists[] = array( $diffOps, true );

		foreach ( $fields as $field ) {
			$argLists[] = array( array( $field => new Diff( array( new DiffOpAdd( 42 ) ) ) ), false );
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
		$diff = new EntityDiff( $diffOps );
		$this->assertEquals( $isEmpty, $diff->isEmpty() );
	}

	public function diffProvider() {
		$diffs = array();

		$diffOps = array(
			'label' => new Diff( array(
				'en' => new DiffOpAdd( 'foobar' ),
				'de' => new DiffOpRemove( 'onoez' ),
				'nl' => new DiffOpChange( 'foo', 'bar' ),
			), true )
		);

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['description'] = new Diff( array(
			'en' => new DiffOpAdd( 'foobar' ),
			'de' => new DiffOpRemove( 'onoez' ),
			'nl' => new DiffOpChange( 'foo', 'bar' ),
		), true );

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['aliases'] = new Diff( array(
			'en' => new Diff( array( new DiffOpAdd( 'foobar' ), new DiffOpRemove( 'onoez' ) ), false ),
			'de' => new Diff( array( new DiffOpRemove( 'foo' ) ), false ),
		), true );

		$diffs[] = new EntityDiff( $diffOps );

		$claim = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim->setGuid( 'EntityDiffTest$foo' );

		$claims = new Claims( array( $claim ) );

		$diffOps['claim'] = $claims->getDiff( new Claims() );

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
			$this->assertTrue( $diffOp instanceof DiffOpAdd || $diffOp instanceof DiffOpRemove );

			$claim = $diffOp instanceof DiffOpAdd ? $diffOp->getNewValue() : $diffOp->getOldValue();
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
