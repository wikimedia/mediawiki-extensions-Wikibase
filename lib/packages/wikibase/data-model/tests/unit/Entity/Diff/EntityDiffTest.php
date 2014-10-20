<?php

namespace Wikibase\Test\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListDiffer;

/**
 * @covers Wikibase\DataModel\Entity\Diff\EntityDiff
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

		$statement = new Statement( new Claim( new PropertyNoValueSnak( 42 ) ) );
		$statement->setGuid( 'EntityDiffTest$foo' );

		$statementListDiffer = new StatementListDiffer();
		$diffOps['claim'] = $statementListDiffer->getDiff(
			new StatementList( array( $statement ) ),
			new StatementList()
		);

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
			$this->assertInstanceOf( 'Wikibase\DataModel\Claim\Claim', $claim );
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
