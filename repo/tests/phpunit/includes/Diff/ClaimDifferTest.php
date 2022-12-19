<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifference;

/**
 * @covers \Wikibase\Repo\Diff\ClaimDiffer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class ClaimDifferTest extends \PHPUnit\Framework\TestCase {

	public function diffClaimsProvider() {
		$argLists = [];

		$noValueForP42 = new Statement( new PropertyNoValueSnak( 42 ) );
		$noValueForP43 = new Statement( new PropertyNoValueSnak( 43 ) );

		$argLists[] = [
			null,
			null,
			new ClaimDifference(),
		];

		$argLists[] = [
			$noValueForP42,
			$noValueForP42,
			new ClaimDifference(),
		];

		$argLists[] = [
			$noValueForP42,
			$noValueForP43,
			new ClaimDifference( new DiffOpChange( new PropertyNoValueSnak( 42 ), new PropertyNoValueSnak( 43 ) ) ),
		];

		$qualifiers = new SnakList( [ new PropertyNoValueSnak( 1 ) ] );
		$withQualifiers = clone $noValueForP42;
		$withQualifiers->setQualifiers( $qualifiers );

		$argLists[] = [
			$noValueForP42,
			$withQualifiers,
			new ClaimDifference(
				null,
				new Diff( [
					new DiffOpAdd( new PropertyNoValueSnak( 1 ) ),
				], false )
			),
		];

		$references = new ReferenceList( [ new Reference( [ new PropertyNoValueSnak( 2 ) ] ) ] );
		$withReferences = clone $noValueForP42;
		$withReferences->setReferences( $references );

		$argLists[] = [
			$noValueForP42,
			$withReferences,
			new ClaimDifference(
				null,
				null,
				new Diff( [
					new DiffOpAdd( new Reference( [ new PropertyNoValueSnak( 2 ) ] ) ),
				], false )
			),
		];

		$argLists[] = [
			$withQualifiers,
			$withReferences,
			new ClaimDifference(
				null,
				new Diff( [
					new DiffOpRemove( new PropertyNoValueSnak( 1 ) ),
				], false ),
				new Diff( [
					new DiffOpAdd( new Reference( [ new PropertyNoValueSnak( 2 ) ] ) ),
				], false )
			),
		];

		$argLists[] = [
			$withReferences,
			null,
			new ClaimDifference(
				new DiffOpChange( new PropertyNoValueSnak( 42 ), null ),
				null,
				new Diff( [
					new DiffOpRemove( new Reference( [ new PropertyNoValueSnak( 2 ) ] ) ),
				], false ),
				new DiffOpChange( 1, null )
			),
		];

		$argLists[] = [
			null,
			$withReferences,
			new ClaimDifference(
				new DiffOpChange( null, new PropertyNoValueSnak( 42 ) ),
				null,
				new Diff( [
					new DiffOpAdd( new Reference( [ new PropertyNoValueSnak( 2 ) ] ) ),
				], false ),
				new DiffOpChange( null, 1 )
			),
		];

		$argLists[] = [
			null,
			new Statement( new PropertyNoValueSnak( 42 ), null, new ReferenceList() ),
			new ClaimDifference(
				new DiffOpChange( null, new PropertyNoValueSnak( 42 ) ),
				null,
				null,
				new DiffOpChange( null, 1 )
			),
		];

		$noValueForP42Preferred = clone $noValueForP42;
		$noValueForP42Preferred->setRank( Statement::RANK_PREFERRED );

		$argLists[] = [
			$noValueForP42,
			$noValueForP42Preferred,
			new ClaimDifference(
				null,
				null,
				null,
				new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
			),
		];

		return $argLists;
	}

	/**
	 * @dataProvider diffClaimsProvider
	 */
	public function testDiffClaims(
		?Statement $oldStatement,
		?Statement $newStatement,
		ClaimDifference $expected
	) {
		$differ = new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) );
		$actual = $differ->diffClaims( $oldStatement, $newStatement );

		$this->assertTrue( $expected->equals( $actual ) );
	}

}
