<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\Diff\StatementListDiffer;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\EntityDiff
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityDiffTest extends TestCase {

	/**
	 * @dataProvider newForTypeProvider
	 */
	public function testNewForType( $entityType, $expected ) {
		$diff = EntityDiff::newForType( $entityType );
		$this->assertInstanceOf( $expected, $diff );
	}

	public function newForTypeProvider() {
		return [
			[ 'item', ItemDiff::class ],
			[ 'anything', EntityDiff::class ],
		];
	}

	public function isEmptyProvider() {
		$argLists = [];

		$argLists[] = [ [], true ];

		$fields = [ 'aliases', 'label', 'description', 'claim' ];

		foreach ( $fields as $field ) {
			$argLists[] = [ [ $field => new Diff( [] ) ], true ];
		}

		$diffOps = [];

		foreach ( $fields as $field ) {
			$diffOps[$field] = new Diff( [] );
		}

		$argLists[] = [ $diffOps, true ];

		foreach ( $fields as $field ) {
			$argLists[] = [ [ $field => new Diff( [ new DiffOpAdd( 42 ) ] ) ], false ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider isEmptyProvider
	 * @param Diff[] $diffOps
	 * @param bool $isEmpty
	 */
	public function testIsEmpty( array $diffOps, $isEmpty ) {
		$diff = new EntityDiff( $diffOps );
		$this->assertEquals( $isEmpty, $diff->isEmpty() );
	}

	public function diffProvider() {
		$diffs = [];

		$diffOps = [
			'label' => new Diff( [
				'en' => new DiffOpAdd( 'foobar' ),
				'de' => new DiffOpRemove( 'onoez' ),
				'nl' => new DiffOpChange( 'foo', 'bar' ),
			], true ),
		];

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['description'] = new Diff( [
			'en' => new DiffOpAdd( 'foobar' ),
			'de' => new DiffOpRemove( 'onoez' ),
			'nl' => new DiffOpChange( 'foo', 'bar' ),
		], true );

		$diffs[] = new EntityDiff( $diffOps );

		$diffOps['aliases'] = new Diff( [
			'en' => new Diff( [ new DiffOpAdd( 'foobar' ), new DiffOpRemove( 'onoez' ) ], false ),
			'de' => new Diff( [ new DiffOpRemove( 'foo' ) ], false ),
		], true );

		$diffs[] = new EntityDiff( $diffOps );

		$statement = new Statement( new PropertyNoValueSnak( 42 ) );
		$statement->setGuid( 'EntityDiffTest$foo' );

		$statementListDiffer = new StatementListDiffer();
		$diffOps['claim'] = $statementListDiffer->getDiff(
			new StatementList( $statement ),
			new StatementList()
		);

		$diffs[] = new EntityDiff( $diffOps );

		$argLists = [];

		foreach ( $diffs as $diff ) {
			$argLists[] = [ $diff ];
		}

		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetClaimsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getClaimsDiff();

		$this->assertInstanceOf( Diff::class, $diff );
		$this->assertTrue( $diff->isAssociative() );

		foreach ( $diff as $diffOp ) {
			$this->assertTrue( $diffOp instanceof DiffOpAdd || $diffOp instanceof DiffOpRemove );

			$statement = $diffOp instanceof DiffOpAdd ? $diffOp->getNewValue() : $diffOp->getOldValue();
			$this->assertInstanceOf( Statement::class, $statement );
		}
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetDescriptionsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getDescriptionsDiff();

		$this->assertInstanceOf( Diff::class, $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetLabelsDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getLabelsDiff();

		$this->assertInstanceOf( Diff::class, $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetAliasesDiff( EntityDiff $entityDiff ) {
		$diff = $entityDiff->getAliasesDiff();

		$this->assertInstanceOf( Diff::class, $diff );
		$this->assertTrue( $diff->isAssociative() );
	}

	public function testGetType() {
		$diff = new EntityDiff();
		$this->assertSame( 'diff/entity', $diff->getType() );
	}

}
