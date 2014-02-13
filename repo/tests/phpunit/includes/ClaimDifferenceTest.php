<?php

namespace Wikibase\Test;

use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Wikibase\ClaimDifference;
use Wikibase\PropertyNoValueSnak;
use Wikibase\Reference;
use Wikibase\Statement;

/**
 * @covers Wikibase\ClaimDifference
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDifferenceTest extends \MediaWikiTestCase {

	public function testGetReferenceChanges() {
		$expected = new Diff( array(
			new DiffOpAdd( new Reference() )
		), false );

		$difference = new ClaimDifference( null, null, $expected );

		$actual = $difference->getReferenceChanges();

		$this->assertInstanceOf( 'Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetQualifierChanges() {
		$expected = new Diff( array(
			new DiffOpAdd( new PropertyNoValueSnak( 42 ) )
		), false );

		$difference = new ClaimDifference( null, $expected );

		$actual = $difference->getQualifierChanges();

		$this->assertInstanceOf( 'Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetMainSnakChange() {
		$expected = new DiffOpChange(
			new PropertyNoValueSnak( 42 ),
			new PropertyNoValueSnak( 43 )
		);

		$difference = new ClaimDifference( $expected );

		$actual = $difference->getMainSnakChange();

		$this->assertInstanceOf( 'Diff\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRankChange() {
		$expected = new DiffOpChange(
			Statement::RANK_PREFERRED,
			Statement::RANK_DEPRECATED
		);

		$difference = new ClaimDifference( null, null, null, $expected );

		$actual = $difference->getRankChange();

		$this->assertInstanceOf( 'Diff\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

}
